<?php
/**
 * Joomla! component Dynamic404
 *
 * @package    Dynamic404
 * @author     Yireo <info@yireo.com>
 * @copyright  Copyright 2016 Yireo (https://www.yireo.com/)
 * @license    GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link       https://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Class Dynamic404HelperMatchManual
 */
class Dynamic404HelperMatchManual
{
	/**
	 * @var array
	 */
	protected $request;

	/**
	 * @var \Joomla\Registry\Registry
	 */
	protected $params;

	/**
	 * Dynamic404HelperMatchSearch constructor.
	 *
	 * @param $params
	 * @param $request
	 */
	public function __construct($params, $request)
	{
		$this->params  = $params;
		$this->request = $request;
	}

	/**
	 * @param bool $staticRulesOnly
	 * 
	 * @return array
	 * @throws Exception
	 */
	public function getMatches($staticRulesOnly = false)
	{
		// Fetch all redirects from the Dynamic404 database-tables
		$db  = JFactory::getDbo();
		$app = JFactory::getApplication();

		$selectFields = array(
			'redirect_id',
			'match',
			'url',
			'http_status',
			'type',
			'description',
			'params'
		);

		$query = $db->getQuery(true);
		$query->select($db->quoteName($selectFields))
			->from('#__dynamic404_redirects')
			->where($db->quoteName('published') . '= 1')
			->where($db->quoteName('match') . ' != ""')
			->order($db->quoteName('ordering'));

		if ($staticRulesOnly)
		{
			$query->where($db->quoteName('static') . '=1');
		}

		$db->setQuery($query);
		$rows = $db->loadObjectList();

		// Loop through the redirect-rules to see how to apply them
		$matches = array();

		if (!empty($rows))
		{
			foreach ($rows as $row)
			{
				// Add a match note
				$row->handler    = 'rule';
				$row->match_note = 'manual redirect #' . $row->redirect_id;
				$row->title      = $row->description;

				// Construct the URL
				if (is_numeric(trim($row->url)))
				{
					$menu     = $app->getMenu();
					$menuItem = $menu->getItem($row->url);

					if (!empty($menuItem))
					{
						$row->name = $menuItem->title;
						$row->url  = $menuItem->link;

						if (strstr($row->url, 'Itemid=') == false)
						{
							$row->url .= '&Itemid=' . $menuItem->id;
						}

						$row->url  = JRoute::_($row->url);
						$row->link = $row->url;
					}
				}
				elseif (preg_match('/Itemid=([0-9]+)/', $row->url, $match))
				{
					$menu     = $app->getMenu();
					$menuItem = $menu->getItem($match[1]);

					if (!empty($menuItem))
					{
						$row->name = $menuItem->title;
						$row->url  = $menuItem->link;

						if (strstr($row->url, 'Itemid=') == false)
						{
							$row->url .= '&Itemid=' . $menuItem->id;
						}

						$row->url  = JRoute::_($row->url);
						$row->link = $row->url;
					}
				}

				// Complete the item
				$params = YireoHelper::toRegistry($row->params);

				if (!empty($row->description) && $params->get('show_description', 0) == 1)
				{
					$row->name = $row->description;
				}

				if (empty($row->name))
				{
					$row->name = $row->url;
				}

				if (empty($row->link))
				{
					$row->link = $row->url;
				}

				// Copy the match-parts
				$uri_last  = $this->request['uri_last'];
				$uri       = $this->request['uri'];
				$uri_parts = $this->request['uri_parts'];

				// Convert to lower case
				if ($params->get('match_case', 0) == 0)
				{
					$row->match = strtolower($row->match);
					$uri_last   = strtolower($uri_last);
					$uri        = strtolower($uri);

					foreach ($uri_parts as $uri_part_index => $uri_part)
					{
						$uri_parts[$uri_part_index] = strtolower($uri_part);
					}
				}

				// Match the full URLs
				if ($row->type == 'full_url' && !empty($uri) && strstr($row->match, $uri))
				{
					$row->type   = 'component';
					$row->rating = $params->get('rating', 0);

					if (empty($row->rating))
					{
						$row->rating = $this->params->get('rating_custom_full_url', 95);
					}

					$matches[] = $row;
					break;
				}
				// Match the last URL-segment
				elseif ($row->type == 'last_segment' && !empty($uri_last) && $row->match == $uri_last)
				{
					$row->type   = 'component';
					$row->rating = $params->get('rating', 0);

					if (empty($row->rating))
					{
						$row->rating = $this->params->get('rating_custom_last_segment', 94);
					}

					$matches[] = $row;
					break;
				}
				// Fuzzy matching
				elseif ($row->type == 'fuzzy')
				{
					if ((!empty($uri_last) && strstr($row->match, $uri_last)) || (!empty($uri_last) && strstr($uri_last, $row->match)))
					{
						$row->type   = 'component';
						$row->rating = $params->get('rating', 0);

						if (empty($row->rating))
						{
							$row->rating = $this->params->get('rating_custom_fuzzy', 90);
						}

						$matches[] = $row;
						break;
					}
				}
				// Matching by regular expression
				elseif ($row->type == 'regex')
				{
					$regex = trim($row->match);
					$regex = str_replace('/', '\/', $regex);

					// Add the @ operator on purpose, because we don't know if the regex is valid
					if (@preg_match('/' . $regex . '/i', '/' . $uri, $regexMatch))
					{
						$row->type   = 'regex';
						$row->rating = $params->get('rating', 0);

						if (empty($row->rating))
						{
							$row->rating = $this->params->get('rating_custom_regex', 90);
						}

						foreach ($regexMatch as $regexMatchIndex => $regexMatchParts)
						{
							if ($regexMatchIndex == 0)
							{
								continue;
							}

							$row->url = str_replace('\\' . $regexMatchIndex, $regexMatchParts, $row->url);
						}

						$row->name = $row->url;
						$row->link = $row->url;
						$matches[] = $row;
						break;
					}
				}
				// Match any segment
				elseif ($row->type == 'any_segment' && !empty($uri_parts) && in_array($row->match, $uri_parts))
				{
					$row->type   = 'component';
					$row->rating = $params->get('rating', 0);

					if (empty($row->rating))
					{
						$row->rating = $this->params->get('rating_custom_any_segment', 90);
					}

					$matches[] = $row;
					break;

				}
			}
		}
		
		return $matches;
	}
}