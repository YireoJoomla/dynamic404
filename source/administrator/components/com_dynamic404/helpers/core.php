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

/*
 * Core helper
 */
class Dynamic404HelperCore
{
    /**
     * Method to log a 404 occurance to the database
     *
     * @access public
     * @param null
     * @return bool
     */
    static public function log($uri = null) 
    {
        $db = JFactory::getDBO();

        // Try to load the current row 
        $db->setQuery('SELECT * FROM `#__dynamic404_logs` WHERE `request`='.$db->Quote($uri).' AND `log_id` > 0');
        $row = $db->loadObject();

        // Update the current row
        if (!empty($row)) {
            $hits = $row->hits + 1;
            $query = 'UPDATE `#__dynamic404_logs` SET `timestamp`='.time().',`hits`='.$hits.' WHERE `log_id`='.(int)$row->log_id;

        // Insert a new row
        } else {
            $query = 'INSERT INTO `#__dynamic404_logs` SET `request`='.$db->Quote($uri).', `hits`=1, `timestamp`='.time();
        }

        $db->setQuery( $query );
        $db->query();
        return true;
    }

    /**
     * Method to get the Itemid of the search-component
     *
     * @access public
     * @param null
     * @return bool
     */
    static public function getSearchItemid()
    {
        $menu = JFactory::getApplication()->getMenu();
        $component = JComponentHelper::getComponent('com_search');
        if (!empty($menu)) {
            if (self::isJoomla15()) {
                $items = $menu->getItems('componentid', $component->id);
            } else {
                $items = $menu->getItems('component_id', $component->id);
            }
        }

        if (is_array($items) && !empty($items)) {
            $item = $items[0];
            return $item->id;
        }
        return null;
    }

    /**
     * Method to get the description for a certain HTTP Status code
     *
     * @access public
     * @param int $http_status
     * @return bool
     */
    static public function getHttpStatusDescription($http_status = 0)
    {
        switch ($http_status) {
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
     * @access public
     * @param null
     * @return bool
     */
    static public function getCurrentVersion()
    {
        $file = JPATH_ADMINISTRATOR.'/components/com_dynamic404/dynamic404.xml';
        $data = JApplicationHelper::parseXMLInstallFile($file);
        return $data['version'];
    }
}
