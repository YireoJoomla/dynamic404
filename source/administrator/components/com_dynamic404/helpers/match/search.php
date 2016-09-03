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
 * Class Dynamic404HelperMatchSearch
 */
class Dynamic404HelperMatchSearch
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
		$this->params = $params;
		$this->request = $request;
	}

	/**
	 * @return array|bool
	 */
	public function getMatches()
	{
		if ($this->params->get('search_plugins', 1) == 0)
		{
			return false;
		}

		$keywords = array();

		if (!empty($this->request['uri_last']))
		{
			$keywords = explode('-', $this->request['uri_last']);
		}

		if (empty($keywords))
		{
			return false;
		}

		$search   = implode(' ', $keywords);
		$match    = 'all';
		$ordering = 'popular';
		$active   = null;

		// Include old helper if it exists (Joomla! bug?)
		$helper = JPATH_ADMINISTRATOR . '/components/com_search/helpers/search.php';

		if (file_exists($helper))
		{
			require_once $helper;
		}

		// Include Search Plugins
		JPluginHelper::importPlugin('search');
		$dispatcher = JEventDispatcher::getInstance();
		$areas      = $dispatcher->trigger('onContentSearch', array($search, $match, $ordering, $active));

		// Loop through the search results and add them to the matches
		$matches = array();

		foreach ($areas as $area)
		{
			foreach ($area as $row)
			{
				// Construct the match
				$match             = new Dynamic404ModelMatch;
				$match->rating     = $this->params->get('rating_search_plugins', 80);
				$match->match_note = 'search plugin';
				$match->type       = 'component';
				$match->name       = $row->title;
				$match->url        = $row->href;

				// Increase the rating if the title matches directly
				$keywordMatch = 0;

				foreach ($keywords as $keyword)
				{
					if (empty($keyword))
					{
						continue;
					}

					if (stristr($match->name, $keyword))
					{
						$keywordMatch++;
					}
				}

				if ($keywordMatch == count($keywords))
				{
					$match->increaseRating(2);
				}
				elseif ($keywordMatch > 0)
				{
					$match->increaseRating(1);
				}

				$matches[] = $match;
			}
		}
		
		return $matches;
	}
}