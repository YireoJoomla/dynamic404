<?php
/*
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
 * Dynamic404 Structure
 */
class HelperAbstract
{
	/**
	 * Structural data of this component
	 *
	 * @return array
	 */
	static public function getStructure()
	{
		return array(
			'title' => 'Dynamic404',
			'menu' => array(
				'home' => 'HOME',
				'setup' => 'SETUP',
				'redirects' => 'REDIRECTS',
				'matches' => 'MATCHES',
				'logs' => 'LOGS',
				'option=com_plugins&view=plugins&filter_folder=dynamic404' => 'PLUGINS',
			),
			'views' => array(
				'home' => 'HOME',
				'setup' => 'SETUP',
				'redirect' => 'REDIRECT',
				'redirects' => 'REDIRECTS',
				'log' => 'LOG',
				'logs' => 'LOGS',
			),
			'obsolete_files' => array(
				JPATH_ADMINISTRATOR . '/components/com_dynamic404/views/logs/tmpl/default.php',
				JPATH_ADMINISTRATOR . '/components/com_dynamic404/views/redirects/tmpl/default.php',
				JPATH_ADMINISTRATOR . '/components/com_dynamic404/views/home/tmpl/default.php',
				JPATH_ADMINISTRATOR . '/components/com_dynamic404/views/home/tmpl/default_cpanel.php',
				JPATH_ADMINISTRATOR . '/components/com_dynamic404/views/home/tmpl/default_ads.php',
				JPATH_ADMINISTRATOR . '/components/com_dynamic404/views/home/tmpl/feeds.php',
				JPATH_ADMINISTRATOR . '/components/com_dynamic404/views/setup/tmpl/default_j16.php',
				JPATH_ADMINISTRATOR . '/components/com_dynamic404/views/setup/tmpl/joomla25',
				JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/acl.php',
			),
		);
	}
}
