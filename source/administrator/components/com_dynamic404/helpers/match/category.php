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
 * Class Dynamic404HelperMatchCategory
 */
class Dynamic404HelperMatchCategory
{
	/*
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
	 * @param   int  $id  Numerical value to match
	 *
	 * @return mixed|null
	 */
	public function findNumericMatches($id)
	{
		$row = $this->getCategoryById($id);
		$row = $this->prepareCategory($row);

		if (empty($row))
		{
			return null;
		}

		$row->match_note = 'category id';

		return array($row);
	}

	/**
	 * Method to find matches within Joomla! categories
	 *
	 * @param   string  $text1  First text to match
	 * @param   string  $text2  Alternative text to match
	 *
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
						$row->match_note = 'category alias = ' . $row->alias . ' / #' . $row->id;
						$matches[] = $row;
					}

					continue;
				}
				else
				{
					$row->match_parts = array();
					$row->match_parts = array_merge($row->match_parts, Dynamic404HelperMatch::matchTextParts($row->alias, $text1));
					$row->match_parts = array_merge($row->match_parts, Dynamic404HelperMatch::matchTextParts($row->alias, $text2));

					if (!empty($row->match_parts))
					{
						$row = $this->prepareCategory($row);

						if (!empty($row))
						{
						    $row->match_note = 'category alias = ' . $row->alias . ' / #' . $row->id;
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
	 * @param   string  $id  Category ID
	 *
	 * @return string
	 */
	private function getCategoryLink($id)
	{
		require_once JPATH_SITE . '/components/com_content/helpers/route.php';

		return ContentHelperRoute::getCategoryRoute($id);
	}

	/**
	 * Method to get an category by ID
	 *
	 * @param   string  $id  Category ID
	 *
	 * @return array
	 */
	private function getCategoryById($id)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id', 'title', 'alias', 'access'))
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('extension') . '=' . $db->quote('com_content'))
			->where($db->quoteName('published') . '= 1')
			->where($db->quoteName('id') . ' = ' . (int) $id);

		$db->setQuery($query, 0, 1);

		if ($this->params->get('debug') == 1)
		{
			$this->debug('MatchCategory::getCategoryById', $db->getQuery());
		}

		return $db->loadObject();
	}

	/**
	 * Method to get a list of categories
	 *
	 * @param   string  $text1  First text to match
	 * @param   string  $text2  Alternative text to match
	 *
	 * @return array
	 */
	private function getCategoryList($text1, $text2)
	{
		static $rows = null;

		if (empty($rows))
		{
			$db = JFactory::getDbo();

			$query = $db->getQuery(true);
			$query->select('*')
				->from('#__categories')
			    ->where($db->quoteName('extension') . '=' . $db->quote('com_content'))
				->where($db->quoteName('published') . '= 1');

			if ($this->params->get('load_all_categories', 0) == 0)
			{
				$text1 = $db->quote('%' . $text1 . '%');
				$text2 = $db->quote('%' . $text2 . '%');

				$query->where('('
					. $db->quoteName('alias') . ' LIKE ' . $text1
					. ' OR '
					. $db->quoteName('alias') . ' LIKE ' . $text2
					. ')');
			}

			$db->setQuery($query);

			if ($this->params->get('debug') == 1)
			{
				$this->debug('MatchCategory::getCategoryList', $db->getQuery());
			}

			$rows = $db->loadObjectList();
		}

		return $rows;
	}

	/**
	 * Method to prepare a category
	 *
	 * @param   object  $item  Category object
	 *
	 * @return object
	 */
	private function prepareCategory($item)
	{
		// Sanity checks
		if (empty($item) || !is_object($item) || empty($item->id))
		{
			return null;
		}

		$item->type = 'component';
		$item->name = $item->title;
		$item->rating = $this->params->get('rating_categories', 85);
		$item->url = $this->getCategoryLink($item->id . ':' . $item->alias);

		return $item;
	}

	/**
	 * Method alias for debugging
	 *
	 * @param   string  $msg       Debugging message
	 * @param   null    $variable  Optional variable to dump
	 */
	public function debug($msg, $variable = null)
	{
		Dynamic404HelperDebug::debug($msg, $variable);
	}
}
