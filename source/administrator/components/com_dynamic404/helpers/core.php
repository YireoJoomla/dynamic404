<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('JPATH_BASE') or die;

/**
 * Core helper
 */
class Dynamic404HelperCore
{
	/**
	 * Method to log a 404 occurance to the database
	 *
	 * @param string $uri
	 * @param int $httpStatus
	 * @param string $message
	 *
	 * @return bool
	 */
	static public function log($uri = null, $httpStatus = 404, $message = '')
	{
		$db = JFactory::getDBO();

		// Try to load the current row
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('log_id', 'request', 'http_status', 'message', 'hits')))
			->from($db->quoteName('#__dynamic404_logs'))
			->where($db->quoteName('request') . '=' . $db->quote($uri))
			->where($db->quoteName('http_status') . '=' . $db->quote($httpStatus))
			->where($db->quoteName('log_id') . '> 0');

		$db->setQuery($query);

		try
		{
			$row = $db->loadObject();
		}
		catch (RuntimeException $e)
		{
			return false;
		}

		// Update or insert
		if (!empty($row))
		{
			$hits = $row->hits + 1;

			$query = $db->getQuery(true);
			$fields = array(
				$db->quoteName('http_status') . ' = ' . $db->quote($httpStatus),
				$db->quoteName('message') . ' = ' . $db->quote($message),
				$db->quoteName('timestamp') . ' = ' . time(),
				$db->quoteName('hits') . ' = ' . $hits
			);

			$conditions = array(
				$db->quoteName('log_id') . ' = ' . (int) $row->log_id
			);

			$query
				->update($db->quoteName('#__dynamic404_logs'))
				->set($fields)
				->where($conditions);
		}
		else
		{
			$columns = array('request', 'http_status', 'message', 'hits', 'timestamp');
			$values = array($db->quote($uri), $db->quote($httpStatus), $db->quote($message), 1, time());
			$query
				->insert($db->quoteName('#__dynamic404_logs'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));
		}

		$db->setQuery($query);
		$db->execute();

		return true;
	}

	/**
	 * Method to get the Itemid of the search-component
	 *
	 * @return int
	 */
	static public function getSearchItemid()
	{
		$menu = JFactory::getApplication()->getMenu();
		$component = JComponentHelper::getComponent('com_search');

		if (!empty($menu))
		{
			$items = $menu->getItems('component_id', $component->id);
		}
		else
		{
			return 0;
		}

		if (is_array($items) && !empty($items))
		{
			$item = $items[0];

			return $item->id;
		}

		return 0;
	}

	/**
	 * Method to get the description for a certain HTTP Status code
	 *
	 * @param int $http_status
	 *
	 * @return bool
	 */
	static public function getHttpStatusDescription($http_status = 0)
	{
		switch ($http_status)
		{
			case 302:
				return '302 Found';
			case 303:
				return '303 See Other';
			case 307:
				return '307 Temporary Redirect';
			default:
				return '301 Moved Permanently';
		}
	}

	/**
	 * Method to get the current version
	 *
	 * @return bool
	 */
	static public function getCurrentVersion()
	{
		$file = JPATH_ADMINISTRATOR . '/components/com_dynamic404/dynamic404.xml';
		$data = JApplicationHelper::parseXMLInstallFile($file);

		return $data['version'];
	}
}
