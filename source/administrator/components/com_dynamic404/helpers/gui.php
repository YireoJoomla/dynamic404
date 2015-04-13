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

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Class Dynamic404HelperGUI
 */
class Dynamic404HelperGUI
{
	/**
	 * Method to get the different match-types
	 *
	 * @param null
	 *
	 * @return null
	 */
	static public function getMatchTypes()
	{
		$options = array(
			array('value' => 'full_url', 'title' => JText::_('COM_DYNAMIC404_REDIRECT_FIELD_TYPE_OPTION_FULL_URL')),
			array('value' => 'last_segment', 'title' => JText::_('COM_DYNAMIC404_REDIRECT_FIELD_TYPE_OPTION_LAST_SEGMENT')),
			array('value' => 'any_segment', 'title' => JText::_('COM_DYNAMIC404_REDIRECT_FIELD_TYPE_OPTION_ANY_SEGMENT')),
			array('value' => 'fuzzy', 'title' => JText::_('COM_DYNAMIC404_REDIRECT_FIELD_TYPE_OPTION_FUZZY')),
		);

		return $options;
	}

	/**
	 * Method to get the different redirect-types
	 *
	 * @param null
	 *
	 * @return null
	 */
	static public function getRedirectTypes()
	{
		$types = array(301, 302, 303, 307);
		$options = array();

		foreach ($types as $type)
		{
			$options[] = array('value' => $type, 'title' => Dynamic404HelperCore::getHttpStatusDescription($type));
		}

		return $options;
	}

	/**
	 * Method to get a specific type-title
	 *
	 * @param string $value
	 *
	 * @return null
	 */
	static public function getTypeTitle($value)
	{
		$types = self::getMatchTypes();

		foreach ($types as $type)
		{
			if ($type['value'] == $value)
			{
				return $type['title'];
			}
		}

		return null;
	}

	/**
	 * Method to set the title for the administration pages
	 *
	 * @param string $match
	 * @param string $type
	 *
	 * @return null
	 */
	static public function getItemMatchLink($match = null, $type = null)
	{
		if ($type != 'full_url')
		{
			return null;
		}

		$uri = JURI::getInstance();
		$base = $uri->toString(array('scheme', 'host', 'port', 'prefix'));

		return $base . '/' . preg_replace('/^\//', '', $match);
	}

	/**
	 * Method to set the title for the administration pages
	 *
	 * @param string $url
	 *
	 * @return null
	 */
	static public function getItemUrlLink($url = null)
	{
		if (preg_match('/^(http|https|ftp):\/\//', $url))
		{
			return $url;
		}

		$uri = JURI::getInstance();
		$base = $uri->toString(array('scheme', 'host', 'port', 'prefix'));

		return $base . '/' . preg_replace('/^\//', '', $url);
	}
}
