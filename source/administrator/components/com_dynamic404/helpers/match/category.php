<?php
/**
 * Joomla! component Dynamic404
 *
 * @package    Dynamic404
 * @author     Yireo <info@yireo.com>
 * @copyright  Copyright (C) 2014 Yireo (http://www.yireo.com/)
 * @license    GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link       http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class Dynamic404HelperMatchCategory
{
	/*
	 * Component parameters
	 */
	private $params = null;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param null
	 * @return null
	 */
	public function __construct()
	{
		$this->params = JComponentHelper::getParams('com_dynamic404');
	}

	/**
	 * Method to find matches when the last segment seems to be an ID
	 *
	 * @access public
	 * @param null
	 * @return null
	 */
	public function findNumericMatches($id)
	{
		$row = $this->getCategoryById($id);
		$row = $this->prepareCategory($row);
		if (empty($row)) return null;
		$row->match_note = 'category id';
		return array($row);
	}

	/**
	 * Method to find matches within Joomla! categories
	 *
	 * @access public
	 * @param null
	 * @return null
	 */
	public function findTextMatches($text1, $text2)
	{
		$matches = array();

		// Match the number only
		if (preg_match('/^([0-9]+)\-/', $text1, $match))
		{
			$row = $this->getCategoryById($match[0]);
			$row = $this->prepareCategory($row);
			if (!empty($row))
			{
				$row->rating = 95;
				$matches[] = $row;
			}
		}

		// Match the alias
		$rows = $this->getCategoryList($text1, $text2);
		if (!empty($rows))
		{
			foreach ($rows as $row)
			{

				if (!isset($row->alias) || empty($row->alias))
				{
					continue;
				}

				if (Dynamic404HelperMatch::matchTextString($row->alias, $text1) || Dynamic404HelperMatch::matchTextString($row->alias, $text2))
				{
					$row = $this->prepareCategory($row);
					if (!empty($row))
					{
						$row->match_note = 'category alias';
						$matches[] = $row;
					}
					continue;

				} else
				{
					$row->match_parts = array();
					$row->match_parts = array_merge($row->match_parts, Dynamic404HelperMatch::matchTextParts($row->alias, $text1));
					$row->match_parts = array_merge($row->match_parts, Dynamic404HelperMatch::matchTextParts($row->alias, $text2));
					if (!empty($row->match_parts))
					{
						$row = $this->prepareCategory($row);
						if (!empty($row))
						{
							$row->match_note = 'category alias';
							$row->rating = $row->rating - 10 + count($row->match_parts);
							$matches[] = $row;
						}
					}
				}
			}
		}

		return $matches;
	}

	/**
	 * Method to redirect to a specific match
	 *
	 * @access private
	 * @param string $id
	 * @return string
	 */
	private function getCategoryLink($id)
	{
		require_once JPATH_SITE . '/components/com_content/helpers/route.php';
		return JRoute::_(ContentHelperRoute::getCategoryRoute($id));
	}

	/**
	 * Method to get an category by ID
	 *
	 * @access private
	 * @param null
	 * @return array
	 */
	private function getCategoryById($id)
	{
		$db = JFactory::getDBO();
		$db->setQuery('SELECT id,title,alias,access FROM #__categories WHERE extension = "com_content" AND published = 1 AND id = ' . (int)$id . ' LIMIT 1');
		if ($this->params->get('debug') == 1) echo 'Dynamic404HelperMatchCategory::getCategoryById = ' . $db->getQuery() . '<br/>';
		return $db->loadObject();
	}

	/**
	 * Method to get a list of categories
	 *
	 * @access private
	 * @param null
	 * @return array
	 */
	private function getCategoryList($text1, $text2)
	{
		static $rows = null;
		if (empty($rows))
		{
			$db = JFactory::getDBO();

			$query = 'SELECT * FROM `#__categories` WHERE `published` = 1 ';
			if ($this->params->get('load_all_categories', 0) == 0)
			{
				$text1 = $db->Quote('%' . $text1 . '%');
				$text2 = $db->Quote('%' . $text2 . '%');
				$query .= 'AND (`alias` LIKE ' . $text1 . ' OR `alias` LIKE ' . $text2 . ')';
			}
			//$query .= ' ORDER BY `ordering`';

			$db->setQuery($query);
			if ($this->params->get('debug') == 1) echo 'Dynamic404HelperMatchCategory::getCategoryList = ' . $db->getQuery() . '<br/>';
			$rows = $db->loadObjectList();
		}

		return $rows;
	}

	/**
	 * Method to prepare a category
	 *
	 * @access private
	 * @param object $item
	 * @return string
	 */
	private function prepareCategory($item)
	{
		// Sanity checks
		if (empty($item) || !is_object($item))
		{
			return null;
		}

		// Check access for 1.5
		if (Dynamic404HelperCore::isJoomla15())
		{
			$user = & JFactory::getUser();
			if (isset($item->access) && $item->access > $user->get('aid', 0))
			{
				return null;
			}
		}

		$item->type = 'component';
		$item->name = $item->title;
		$item->rating = $this->params->get('rating_categories', 85);
		$item->url = $this->getCategoryLink($item->id . ':' . $item->alias);
		return $item;
	}
}
