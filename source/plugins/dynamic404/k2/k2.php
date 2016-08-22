<?php
/**
 * Joomla! plugin for Dynamic404 - K2
 *
 * @author      Yireo
 * @package     Dynamic404
 * @copyright   Copyright 2016 Yireo
 * @license     GNU Public License (GPL)
 * @link        https://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Import the parent class
jimport('joomla.plugin.plugin');

/**
 * Dynamic404 Plugin for SimpleLists
 */
class plgDynamic404K2 extends JPlugin
{
	/**
	 * Determine whether this plugin could be used
	 *
	 * @return boolean
	 */
	private function isEnabled()
	{
		if (!is_dir(JPATH_SITE . '/components/com_k2'))
		{
			return false;
		}

		return true;
	}

	/**
	 * Return on all matches
	 *
	 * @param string $urilast
	 *
	 * @return array
	 */
	public function getMatches($urilast = null)
	{
		$matches = array();

		if ($this->isEnabled() == false)
		{
			return $matches;
		}

		$rows = array();

		if ($this->params->get('search_items', 1))
		{
			$rows = array_merge($rows, $this->getItems($urilast));
		}

		if ($this->params->get('search_categories', 0))
		{
			$rows = array_merge($rows, $this->getCategories($urilast));
		}

		if (!empty($rows))
		{
			foreach ($rows as $row)
			{
				if (!isset($row->alias) || empty($row->alias) || (empty($urilast) && empty($urilast2)))
				{
					continue;
				}

				if ($row->alias == $urilast || strstr($row->alias, $urilast) || strstr($urilast, $row->alias))
				{
					$row = $this->prepareItem($row);

					if (!empty($row))
					{
						$matches[] = $row;
					}

					continue;
				}
			}
		}

		return $matches;
	}

	/**
	 * Get all K2 items
	 *
	 * @param string $alias
	 *
	 * @return array
	 */
	public function getItems($alias = null)
	{
		static $rows = null;

		if (empty($rows))
		{
			$db = JFactory::getDbo();
			$db->setQuery('SELECT id,alias,access,title AS name,catid FROM #__k2_items WHERE published=1 AND alias LIKE "%' . $alias . '%"');
			$rows = $db->loadObjectList();

			if (!empty($rows))
			{
				foreach ($rows as $index => $row)
				{
					if ($this->isItemExcluded($row->id))
					{
						continue;
					}

					if ($this->isCategoryExcluded($row->catid))
					{
						continue;
					}

					$row->row_type = 'item';
					$rows[$index]  = $row;
				}
			}
		}

		return $rows;
	}

	/**
	 * Get all K2 categories
	 *
	 * @param string $alias
	 *
	 * @return array
	 */
	public function getCategories($alias = null)
	{
		static $rows = null;

		if (empty($rows))
		{
			$db = JFactory::getDbo();
			$db->setQuery('SELECT id,alias,access,name FROM #__k2_categories WHERE published=1 AND alias LIKE "%' . $alias . '%"');
			$rows = $db->loadObjectList();

			if (!empty($rows))
			{
				foreach ($rows as $index => $row)
				{
					if ($this->isCategoryExcluded($row->id))
					{
						continue;
					}

					$row->row_type = 'category';
					$rows[$index]  = $row;
				}
			}
		}

		return $rows;
	}

	/**
	 * Method to prepare an item
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	private function prepareItem($item)
	{
		// Check access
		$user         = JFactory::getUser();
		$accessLevels = $user->getAuthorisedViewLevels();

		if (isset($item->access) && $item->access > 0 && !in_array($item->access, $accessLevels))
		{
			return null;
		}

		// Set common options
		$item->type       = 'component';
		$item->rating     = $this->params->get('rating', 85);
		$item->match_note = 'k2 alias';

		// Require the K2 helper
		require_once JPATH_SITE . '/components/com_k2/helpers/route.php';

		switch ($item->row_type)
		{
			case 'category':
				$url       = K2HelperRoute::getCategoryRoute($item->id . ':' . $item->alias);
				$item->url = JRoute::_($url);
				break;

			case 'item':
			default:
				$url       = K2HelperRoute::getItemRoute($item->id . ':' . $item->alias, $item->catid);
				$item->url = JRoute::_($url);
				break;
		}

		return $item;
	}

	/**
	 * @return bool
	 */
	private function isCategoryExcluded($categoryId)
	{
		$excludedCategories = $this->getArrayFromParam('exclude_categories');

		if (in_array($categoryId, $excludedCategories))
		{
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private function isItemExcluded($itemId)
	{
		$excludedItems = $this->getArrayFromParam('exclude_items');

		if (in_array($itemId, $excludedItems))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $param string
	 *
	 * @return array
	 */
	private function getArrayFromParam($param)
	{
		$items = array();
		$paramItems = trim($this->params->get($param));

		if (empty($paramItems))
		{
			return $items;
		}

		$paramItems = explode(',', $paramItems);

		foreach ($paramItems as $paramItem)
		{
			$paramItem = trim($paramItem);
			$paramItem = (int) $paramItem;

			if (!empty($paramItem))
			{
				$items[] = $paramItem;
			}
		}

		return $items;
	}
}
