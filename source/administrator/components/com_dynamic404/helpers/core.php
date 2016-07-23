<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (https://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        https://www.yireo.com/
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
		$db = JFactory::getDbo();

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


	/**
	 * Method to get the Menu-Item error-page URL
	 */
	public function getMenuItemUrl($errorCode)
	{
		// Check the parameters
		$params = $this->params->toArray();
		$Itemid = null;
		$article = null;

		foreach ($params as $name => $value)
		{
			if ($value > 0 && preg_match('/^menuitem_id_([0-9]+)/', $name, $match))
			{
				if ($errorCode == $match[1])
				{
					$Itemid = (int) $value;
				}
			}

			if ($value > 0 && preg_match('/^article_id_([0-9]+)/', $name, $match))
			{
				if ($errorCode == $match[1])
				{
					$article = (int) $value;
				}
			}
		}

		// Don't continue if no item is there
		if ($Itemid > 0 == false && $article > 0 == false)
		{
			return false;
		}

		// Check whether the current page is already the Dynamic404-page
		if ($this->jinput->getCmd('option') == 'com_dynamic404')
		{
			return false;
		}

		// Fetch the system variables
		$app = JFactory::getApplication();

		// Determine the URL by Menu-Item
		if ($Itemid > 0)
		{
			// Load the configured Menu-Item
			$menu = $app->getMenu();
			$item = $menu->getItem($Itemid);

			if (empty($item) || !is_object($item) || !isset($item->query['option']))
			{
				return false;
			}

			// Construct the URL
			if (isset($item->component) && $item->component == 'com_dynamic404')
			{
				$currentUrl = JURI::current();
				$currentUrl = str_replace('?noredirect=1', '', $currentUrl);
				$url = JRoute::_('index.php?option=com_dynamic404&Itemid=' . $Itemid . '&uri=' . base64_encode($currentUrl));
			}
			else
			{
				$url = JRoute::_('index.php?Itemid=' . $Itemid);
			}
		}
		else
		{
			// Load the configured article
			$row = $this->getArticle($errorCode);

			if (empty($row))
			{
				return false;
			}

			require_once JPATH_SITE . '/components/com_content/helpers/route.php';
			$url = ContentHelperRoute::getArticleRoute($article . ':' . $row->alias, $row->catid);
			$url = JRoute::_($url);
		}

		// Complete the URL
		$url = JURI::base() . substr($url, strlen(JURI::base(true)) + 1);

		// Detect the language-SEF
		$currentLanguage = JFactory::getLanguage();
		$languages = JLanguageHelper::getLanguages('sef');

		foreach ($languages as $language)
		{
			if ($language->lang_code == $currentLanguage->getTag())
			{
				$languageSef = $language->sef;
			}
		}

		// Add the language to the URL
		if (!empty($languageSef))
		{
			$url = (strstr($url, '?')) ? $url . '&lang=' . $languageSef : $url . '?lang=' . $languageSef;
		}

		return $url;
	}
}
