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
defined('_JEXEC') or die();

/**
 * Class Dynamic404ModelPlugins
 */
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
