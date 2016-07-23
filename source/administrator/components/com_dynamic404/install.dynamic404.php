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

// Definitions
define('DYNAMIC404_ERROR_PATCH', JPATH_ADMINISTRATOR . '/components/com_dynamic404/lib/error.php');
define('DYNAMIC404_ERROR_TARGET', JPATH_SITE . '/templates/system/error.php');
define('DYNAMIC404_ERROR_BACKUP', JPATH_SITE . '/templates/system/error.before-dynamic404.php');

// Restore the original backup
jimport('joomla.filesystem.file');

if (file_exists(DYNAMIC404_ERROR_PATCH) && md5_file(DYNAMIC404_ERROR_PATCH) != md5_file(DYNAMIC404_ERROR_TARGET))
{
	if (!file_exists(DYNAMIC404_ERROR_BACKUP))
	{
		JFile::copy(DYNAMIC404_ERROR_TARGET, DYNAMIC404_ERROR_BACKUP);
	}
	
	JFile::copy(DYNAMIC404_ERROR_PATCH, DYNAMIC404_ERROR_TARGET);
}

// Perform extra queries
$db = JFactory::getDbo();
$queries = array(
	'ALTER TABLE  `#__dynamic404_redirects` ADD  `http_status` INT( 3 ) NOT NULL AFTER  `url`',
);

foreach ($queries as $query)
{
	$db->setQuery($query);
	$db->execute();
}
