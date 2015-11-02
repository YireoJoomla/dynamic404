<?php
/**
 * Joomla! component Dynamic404
 *
 * @package    Dynamic404
 * @author     Yireo <info@yireo.com>
 * @copyright  Copyright 2015 Yireo (http://www.yireo.com/)
 * @license    GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link       http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Class Dynamic404HelperMatchMenu
 */
class Dynamic404HelperMatchMenu
{
	/**
	 * Component parameters
	 */
	private $params = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->params = JComponentHelper::getParams('com_dynamic404');
	}

	/**
	 * Method to find matches when the last segment seems to be an ID
	 *
	 * @param   int $id Numerical value to match
	 *
	 * @return mixed|null
	 */
	public function findNumericMatches($id)
	{
		$rows = $this->getMenuItems();
		$matches = array();

		if (!empty($rows))
		{
			foreach ($rows as $row)
			{
				if ($row->id == $id)
				{
					$row = $this->prepareMenuItem($row);

					if (!empty($row))
					{
						$matches[] = $row;
					}
				}
			}
		}

		return $matches;
	}

	/**
	 * Method to find matches within the Menu-Items
	 *
	 * @param   string $text1     First text to match
	 * @param   string $text2     Alternative text to match
	 * @param   string $uri       Current URL
	 * @param   array  $uri_parts Array of current URL segments
	 *
	 * @return null
	 */
	public function findTextMatches($text1, $text2, $uri, $uri_parts)
	{
		$this->debug('MatchMenu::findTextMatches parameters: ' . $text1 . ', ' . $text2 . ', ' . $uri);

		// Initialize
		$matches = array();

		// Fetch the menu-items and try to find a match
		$items = $this->getMenuItems();

		// Try to find a menu-item that matches one of the URI parts
		if (!empty($items))
		{
			foreach ($items as $item)
			{
				// Each item is given a rating
				$item->rating = 10;

				// Convert URL to lowercase for best matching
				$item->alias = strtolower($item->alias);
				$item->route = strtolower($item->route);

				// Only match component-pages
				if ($item->type != 'component')
				{
					continue;
				}

				// If the items alias is equal to the last part in the requested URL, it is the most likely match
				if (Dynamic404HelperMatch::matchTextString($item->alias, $text1) || Dynamic404HelperMatch::matchTextString($item->alias, $text2))
				{
					$item->rating = $this->params->get('rating_menuitems', 80);
					$item->url = $this->getMenuItemUrl($item);
					$item->match_note = 'menu alias "' . $item->alias . '"';
					$item = $this->prepareMenuItem($item);

					if (!empty($item))
					{
						$this->debug('MatchMenu::findTextMatches: Match with alias', $item->id);
						$matches[] = $item;
					}

					continue;
				}

				// If the items alias is found directly in the URL
				if (!empty($item->alias) && in_array($item->alias, $uri_parts))
				{
					// To rate this correctly, we count how many segments in the Menu-Item are the same as in the requested URL
					$segments = array_unique(explode('/', $item->route));
					$count = 0;

					foreach ($segments as $segment)
					{
						if (in_array($segment, $uri_parts))
						{
							$count++;
						}
					}

					$item->rating = 100 - (count($uri_parts) + $count) * 8;
					$item->url = $this->getMenuItemUrl($item);
					$item->match_note = 'menu route';
					$item = $this->prepareMenuItem($item);

					if (!empty($item))
					{
						$this->debug('MatchMenu::findTextMatches: Match partially with alias');
						$matches[] = $item;
					}

					continue;
				}

				// Try to find a match between the requested URL and a Menu-Items route
				if (Dynamic404HelperMatch::matchTextString($item->route, $uri))
				{
					// Reset the base-rating
					if (substr($item->route, 0, YireoHelper::strlen($uri)) == $uri)
					{
						$item->rating = 89;
					}
					else
					{
						$item->rating = 79;
					}

					// Try to make an improvement on the base rating
					$max = YireoHelper::strlen($item->route);

					for ($i = 1; $i < $max; $i++)
					{
						if (abs(YireoHelper::strlen($item->route) - YireoHelper::strlen($uri)) <= $i)
						{
							// Give this match a rating depending on the characters that differ
							// @todo: Find a way to calculate the total string-length as well
							$item->rating = $item->rating - ($i * 2);
							break;
						}
					}

					// Reset the rating if it has become too low
					if ($item->rating < 10)
					{
						$item->rating = 10;
					}

					$item->match_note = 'menu fuzzy alias';
					$item->url = $this->getMenuItemUrl($item);
					$item = $this->prepareMenuItem($item);

					if (!empty($item))
					{
						$this->debug('MatchMenu::findTextMatches: Fuzzy match with alias');
						$matches[] = $item;
					}

					continue;
				}
			}
		}

		return $matches;
	}

	/**
	 * Method to get a list of menu-items
	 *
	 * @return array
	 */
	private function getMenuItems()
	{
		static $rows = null;

		if (empty($rows))
		{
			$app = JApplicationCms::getInstance('site');
			$menu = $app->getMenu();
			$rows = $menu->getMenu();

			$this->debug('MatchMenu::getMenuItems() = ' . count($rows) . ' items');
		}

		return $rows;
	}

	/**
	 * Method to get the URL for a specific Menu-Item
	 *
	 * @param   object $item Menu-Item object
	 *
	 * @return string
	 */
	private function getMenuItemUrl($item)
	{
		$currentLanguage = JFactory::getLanguage();

		if ($item->type == 'component')
		{
			if (in_array($item->component, array('com_hikashop')))
			{
				$item->link = 'index.php?option=' . $item->component;
			}

			$link = $item->link . '&Itemid=' . $item->id;

			if (!empty($item->language) && $item->language != '*' && $item->language != $currentLanguage->getTag())
			{
				$link .= '&lang=' . $item->language;
			}

			$item->url = JRoute::_($link);
		}
		else
		{
			$item->url = $item->link;
		}

		return $item->url;
	}

	/**
	 * Method to prepare a menu-item
	 *
	 * @param   object $item Menu-Item object
	 *
	 * @return string
	 */
	private function prepareMenuItem($item)
	{
		$item->name = $item->title;

		if (empty($item->name))
		{
			return null;
		}

		// Cast this match to the right class
		$item = Dynamic404ModelMatch::getInstance($item);

		// Parse the language of this item
		$item->parseLanguage();

		$item->type = 'component';
		$item->url = $this->getMenuItemUrl($item);


		return $item;
	}

	/**
	 * Method alias for debugging
	 *
	 * @param   string $msg      Debugging message
	 * @param   null   $variable Optional variable to dump
	 */
	public function debug($msg, $variable = null)
	{
		Dynamic404HelperDebug::debug($msg, $variable);
	}
}
