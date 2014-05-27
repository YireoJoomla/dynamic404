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

/**
 * Dynamic404 Controller
 *
 * @package     Dynamic404
 */
class Dynamic404Controller extends YireoController
{
    /**
     * Constructor
     *
     * @access public
     * @param null
     * @return null
     */
    public function __construct()
    {
        $this->_default_view = 'home';
        parent::__construct();
    }

    /**
     * Method to restore the original error.php file
     *
     * @access public
     * @param null
     * @return null
     */
    public function deleteAll()
    {
        $db = JFactory::getDBO();
        $db->setQuery('DELETE FROM #__dynamic404_logs');
        $db->query();

        // Redirect
        $msg = 'Removed all logs';
        $this->setRedirect('index.php?option=com_dynamic404&view=logs', $msg);
    }

    /**
     * Method to restore the original error.php file
     *
     * @access public
     * @param null
     * @return null
     */
    public function restore()
    {
        // Import file-library
        jimport('joomla.filesystem.file');

        // Only restore if the MD5-checksum matches
        if (is_file(DYNAMIC404_ERROR_BACKUP) && md5_file(DYNAMIC404_ERROR_BACKUP) != md5_file(DYNAMIC404_ERROR_TARGET)) {

            // Restore the files
            $rt = JFile::move(DYNAMIC404_ERROR_BACKUP, DYNAMIC404_ERROR_TARGET);
            if ($rt == true) {
                $msg = JText::_('Original files restored'); 
            } else {
                $msg = JText::_('Failed to restore files'); 
            }
        } else {
            $msg = JText::_('No backup available'); 
        }

        // Redirect
        $this->setRedirect('index.php?option=com_dynamic404&view=setup', $msg);
    }

    /**
     * Method to patch the system error.php file
     *
     * @access public
     * @param null
     * @return null
     */
    public function patch()
    {
        // Import the file-library
        jimport('joomla.filesystem.file');

        // Only patch if the files match
        if (md5_file(DYNAMIC404_ERROR_PATCH) != md5_file(DYNAMIC404_ERROR_TARGET)) {

            // Create a backup
            if (!file_exists(DYNAMIC404_ERROR_BACKUP)) {
                JFile::copy(DYNAMIC404_ERROR_TARGET, DYNAMIC404_ERROR_BACKUP);
            }

            // Patch the file
            JFile::copy(DYNAMIC404_ERROR_PATCH, DYNAMIC404_ERROR_TARGET);
        }

        // Redirect
        $this->setRedirect('index.php?option=com_dynamic404&view=setup');
    }

    /**
     * Method to enable the Dynamic404 System Plugin
     *
     * @access public
     * @param null
     * @return null
     */
    public function pluginD404()
    {
        // Perform the database query
        $db = JFactory::getDBO();
        $query = 'UPDATE #__extensions SET `enabled`=1 WHERE `type`="plugin" AND `element`="dynamic404" AND `folder`="system"';
        $db->setQuery($query);
        $db->query();

        // Redirect
        $this->setRedirect('index.php?option=com_dynamic404&view=setup');
    }

    /**
     * Method to enable the Dynamic404 System Plugin
     *
     * @access public
     * @param null
     * @return null
     */
    public function pluginRedirect()
    {
        // Perform the database query
        $db = JFactory::getDBO();
        $query = 'UPDATE #__extensions SET `enabled`=0 WHERE `type`="plugin" AND `element`="redirect" AND `folder`="system"';
        $db->setQuery($query);
        $db->query();

        // Redirect
        $this->setRedirect('index.php?option=com_dynamic404&view=setup');
    }
}
