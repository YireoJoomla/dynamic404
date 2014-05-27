<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright (C) 2014 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!  
defined('_JEXEC') or die();

// Definitions
define( 'D404_ERROR_PATCH', JPATH_ADMINISTRATOR.'/components/com_dynamic404/lib/error.php' );
define( 'D404_ERROR_TARGET', JPATH_SITE.'/templates/system/error.php' );
define( 'D404_ERROR_BACKUP', JPATH_SITE.'/templates/system/error.before-dynamic404.php' );

class com_dynamic404InstallerScript
{
	public function postflight($action, $installer)
	{
		switch ($action) {
			case 'install':
			case 'update':

                // Patch the error-file
	            jimport('joomla.filesystem.file');
	            if (file_exists(D404_ERROR_PATCH) && md5_file(D404_ERROR_PATCH) != md5_file(D404_ERROR_TARGET)) {
	                if (!file_exists(D404_ERROR_BACKUP)) {
	                    JFile::copy(D404_ERROR_TARGET, D404_ERROR_BACKUP);
	                }
	                JFile::copy(D404_ERROR_PATCH, D404_ERROR_TARGET);
	            }

                // Perform extra queries
                $db = JFactory::getDBO();
                $queries = array(
                    'ALTER TABLE  `#__dynamic404_redirects` ADD  `http_status` INT( 3 ) NOT NULL AFTER  `url`',
                    'UPDATE #__extensions SET `enabled`=1 WHERE `type`="plugin" AND `element`="dynamic404" AND `folder`="system"',
                );
                foreach ($queries as $query) {
                    $db->setQuery($query);
                    $db->query();
                }

                // Remove obsolete files
                $files = array(
                    JPATH_ADMINISTRATOR.'/components/com_dynamic404/views/home/tmpl/default.php',
                    JPATH_ADMINISTRATOR.'/components/com_dynamic404/views/home/tmpl/default_ads.php',
                    JPATH_ADMINISTRATOR.'/components/com_dynamic404/views/home/tmpl/default_cpanel.php',
                    JPATH_ADMINISTRATOR.'/components/com_dynamic404/views/home/tmpl/feeds.php',
                );
                foreach($files as $file) {
                    if(file_exists($file)) @unlink($file);
                }

				break;
				
			default:
	            jimport('joomla.filesystem.file');
	            if (is_file(D404_ERROR_BACKUP) && md5_file(D404_ERROR_BACKUP) != md5_file(D404_ERROR_TARGET)) {
	                $rt = JFile::move(D404_ERROR_BACKUP, D404_ERROR_TARGET);
	            }
				break;
			}
	}
}
