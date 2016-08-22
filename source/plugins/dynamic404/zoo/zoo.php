<?php
/**
 * Joomla! plugin for Dynamic404 - ZOO
 *
 * @author      Yireo (https://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        https://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

// Import the parent class
jimport( 'joomla.plugin.plugin' );

/**
 * Dynamic404 Plugin for ZOO 
 */
class plgDynamic404Zoo extends JPlugin
{
    /**
     * Load the parameters
     * 
     * @access private
     * @param null
     * @return JParameter
     */
    private function getParams()
    {
        return $this->params;
    }

    /**
     * Determine whether this plugin could be used
     * 
     * @access private
     * @param null
     * @return boolean
     */
    private function isEnabled()
    {
        if(!is_dir(JPATH_SITE.'/components/com_zoo')) {
            return false;
        }
        return true;
    }

    /**
     * Return all possible matches
     *
     * @access public
     * @param string $urilast
     * @return array
     */
    public function getMatches($urilast = null)
    {
        $matches = array();
        if($this->isEnabled() == false) {
            return $matches;
        }

        $rows = array();
        $items = $this->getItems($urilast);
        if(!empty($items)) {
            $rows = array_merge($rows, $items);
        }

        $categories = $this->getCategories($urilast);
        if(!empty($categories)) {
            $rows = array_merge($rows, $categories);
        }

        if(!empty($rows)) {
            foreach( $rows as $row ) {

                if(!isset($row->alias) || empty($row->alias) || (empty($urilast) && empty($urilast2))) {
                    continue;
                }

                $row = $this->prepareItem($row);
                if(empty($row)) {
                    continue;
                }

                $matches[] = $row;
            }
        }

        return $matches;
    }

    /**
     * Get all ZOO items
     *
     * @access public
     * @param string $alias
     * @return array
     */
    public function getItems($alias)
    {
        static $rows = null;
        if(empty($rows)) {
            $db = JFactory::getDbo();
            $db->setQuery('SELECT `id`, `name`, `alias`, `access` FROM `#__zoo_item` WHERE `state`=1 AND `alias` LIKE "%'.$alias.'%"');
            $rows = $db->loadObjectList();

            if(!empty($rows)) { 
                foreach($rows as $index => $row) {
                    $row->row_type = 'item';
                    $rows[$index] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * Get all ZOO categories
     *
     * @access public
     * @param string $alias
     * @return array
     */
    public function getCategories($alias)
    {
        static $rows = null;
        if(empty($rows)) {
            $db = JFactory::getDbo();
            $db->setQuery('SELECT `id`, `name`, `alias` FROM `#__zoo_category` WHERE `published`=1 AND `alias` LIKE "%'.$alias.'%"');
            $rows = $db->loadObjectList();

            if(!empty($rows)) { 
                foreach($rows as $index => $row) {
                    $row->row_type = 'category';
                    $rows[$index] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * Method to prepare an item
     *
     * @access private
     * @param object $item
     * @return string
     */
    private function prepareItem($item)
    {
        // Check access for items
        if(isset($item->row_type) && $item->row_type == 'item') {
            $user = JFactory::getUser();
            $accessLevels = $user->getAuthorisedViewLevels();
            if(isset($item->access) && $item->access > 0 && !in_array($item->access, $accessLevels)) {
                return null;
            }
        }

        $item->type = 'component';

        switch($item->row_type) {
    
            case 'category':
                $item->rating = $this->getParams()->get('category_rating', 85);
                $item->match_note = 'zoo category';
                $item->url = JRoute::_('index.php?option=com_zoo&task=category&category_id='.(int)$item->id.':'.$item->alias);
                break;
        
            case 'item':
            default:
                $item->rating = $this->getParams()->get('item_rating', 85);
                $item->match_note = 'zoo item';
                $item->url = JRoute::_('index.php?option=com_zoo&task=item&item_id='.(int)$item->id.':'.$item->alias);
                break;
        }
        return $item;
    }
}
