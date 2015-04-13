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
defined('_JEXEC') or die();

class Dynamic404ModelPlugins extends YireoModel
{
	/**
	 * Method to build the database query
	 *
	 * @return mixed
	 */
	protected function buildQuery()
	{
		$query = 'SELECT * FROM `#__extensions` WHERE `type`="plugin" AND `folder`="dynamic404"';

		return $query;
	}
}
