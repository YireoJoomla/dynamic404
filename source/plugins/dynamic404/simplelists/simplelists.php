<?php
/**
 * Joomla! plugin for Dynamic404 - Simplelists 
 *
 * @author      Yireo
 * @package     Dynamic404
 * @copyright   Copyright (c) 2014 Yireo
 * @license     GNU Public License (GPL) 
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

// Import the parent class
jimport( 'joomla.plugin.plugin' );

/**
 * Dynamic404 Plugin for SimpleLists
 */
class plgDynamic404Simplelists extends JPlugin
{
    /**
     * Determine whether this plugin could be used
     * 
     * @access private
     * @param null
     * @return boolean
     */
    private function isEnabled()
    {
        if(!is_dir(JPATH_SITE.'/components/com_simplelists')) {
            return false;
        }
        return true;
    }

    /**
     * Return on all matches
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

        $rows = $this->getItems($urilast);
        if(!empty($rows)) {
            foreach( $rows as $row ) {

                if(!isset($row->alias) || empty($row->alias) || (empty($urilast) && empty($urilast2))) {
                    continue;
                }

                if($row->alias == $urilast || strstr($row->alias, $urilast) || strstr($urilast, $row->alias)) {
                    $row = $this->prepareItem($row);
                    if(!empty($row)) $matches[] = $row;
                    continue;
                }
            }
        }

        return $matches;
    }

    /**
     * Get all SimpleLists items
     *
     * @access public
     * @param string $alias
     * @return array
     */
    public function getItems($alias = null)
    {
        static $rows = null;
        if(empty($rows)) {
            $db = JFactory::getDBO();
            $query = $db->getQuery(true);
            $query->select($db->quoteName(array('id', 'title', 'alias', 'access')));
            $query->from($db->quoteName('#__simplelists_items'));
            $query->where($db->quoteName('published') . ' = 1');
            $query->where($db->quoteName('alias') . ' LIKE '. $db->quote('%'.$alias.'%'));
            $db->setQuery($query);
            $rows = $db->loadObjectList();
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
        // Check access
        $user = JFactory::getUser();
        $accessLevels = $user->getAuthorisedViewLevels();
        if(isset($item->access) && $item->access > 0 && !in_array($item->access, $accessLevels)) {
            return null;
        }

        // Add the category-details
        $query = "SELECT c.id, c.title, c.alias FROM #__simplelists_categories AS sc "
            . "LEFT JOIN #__categories AS c ON c.id = sc.category_id "
            . "WHERE sc.id = ".(int)$item->id." LIMIT 1";

        $db = JFactory::getDBO();
        $db->setQuery( $query );
        $category = $db->loadObject();
        if(empty($category)) {
            return null;
        }

        $item->type = 'component';
        $item->name = '['.$category->title.'] '.$item->title;
        $item->rating = $this->params->get('rating', 85);
        $item->url = $this->getUrl($category->id, $category->alias);
        $item->match_note = 'simplelists item';
        return $item;
    }

    /*
     * Helper-function to build a proper SimpleLists system-URL
     * 
     * @access public
     * @param int $category_id
     * @param string $category_alias
     * @return string
     */
    private function getUrl($category_id = 0, $category_alias = '')
    {
        $url = 'index.php?option=com_simplelists&view=simplelist';

        if(empty( $category_alias)) {
            require_once(JPATH_ADMINISTRATOR.'/components/com_simplelists/helpers/category.php');
            $category_alias = SimplelistsCategoryHelper::getAlias($category_id);
        }
        $url .= '&category_id='.(int)$category_id.':'.$category_alias;
        $url = JRoute::_( $url );
        return $url;
    }
}
