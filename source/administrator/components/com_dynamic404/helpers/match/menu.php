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

class Dynamic404HelperMatchMenu
{
    /*
     * Component parameters
     */
    private $params = null;

    /**
     * Constructor
     *
     * @access public
     * @param null
     * @return null
     */
    public function __construct()
    {
        $this->params = JComponentHelper::getParams('com_dynamic404');
    }

    /**
     * Method to find matches when the last segment seems to be an ID
     *
     * @access private
     * @param null
     * @return null
     */
    public function findNumericMatches($id)
    {
        $rows = $this->getMenuItems();
        $matches = array();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                if ($row->id == $id) {
                    $row = $this->prepareMenuItem($row);
                    if (!empty($row)) $matches[] = $row;
                }
            }
        }
        return $matches;
    }

    /**
     * Method to find matches within the Menu-Items
     *
     * @access private
     * @param null
     * @return null
     */
    public function findTextMatches($text1, $text2, $uri, $uri_parts) 
    {
        // Initialize 
        $matches = array();

        // Fetch the menu-items and try to find a match
        $items = $this->getMenuItems();

        // Try to find a menu-item that matches one of the URI parts
        if (!empty($items)) {
            foreach ( $items as $item ) {

                // Each item is given a rating 
                $item->rating = 10;

                // Convert URL to lowercase for best matching
                $item->alias = strtolower($item->alias);
                $item->route = strtolower($item->route);

                // Only match component-pages
                if ($item->type != 'component') {
                    continue;
                }

                // If the items alias is equal to the last part in the requested URL, it is the most likely match
                if ($item->alias == $text1 || $item->alias == $text2) {
                    $item->rating = $this->params->get('rating_menuitems', 80);
                    $item->url = $this->getMenuItemUrl($item);
                    $item->match_note = 'menu alias';
                    $item = $this->prepareMenuItem($item);
                    if (!empty($item)) $matches[] = $item;
                    continue;
                }
                
                // If the items alias is found directly in the URL
                if (!empty($item->alias) && in_array($item->alias, $uri_parts)) {

                    // To rate this correctly, we count how many segments in the Menu-Item are the same as in the requested URL
                    $segments = array_unique(explode( '/', $item->route ));
                    $count = 0;
                    foreach ($segments as $segment) {
                        if ( in_array( $segment, $uri_parts )) {
                            $count++;
                        }
                    }

                    $item->rating = 100 - (count($uri_parts) + $count)*8;
                    $item->url = $this->getMenuItemUrl($item);
                    $item->match_note = 'menu route';
                    $item = $this->prepareMenuItem($item);
                    if (!empty($item)) $matches[] = $item;
                    continue;
                }
                
                // Try to find a match between the requested URL and a Menu-Items route
                if (Dynamic404HelperMatch::matchTextString($item->route, $uri)) {

                    // Reset the base-rating
                    if (substr($item->route, 0, strlen($uri)) == $uri) {
                        $item->rating = 89;
                    } else {
                        $item->rating = 79;
                    }

                    // Try to make an improvement on the base rating
                    $max = strlen($item->route);
                    for($i = 1; $i < $max; $i++) {
                        if (abs(strlen($item->route) - strlen($uri)) <= $i) {

                            // Give this match a rating depending on the characters that differ
                            // @todo: Find a way to calculate the total string-length as well
                            $item->rating = $item->rating - ($i*2);
                            break;
                        }
                    }

                    // Reset the rating if it has become too low
                    if ($item->rating < 10) {
                        $item->rating = 10;
                    }

                    $item->match_note = 'menu fuzzy alias';
                    $item->url = $this->getMenuItemUrl($item);
                    $item = $this->prepareMenuItem($item);
                    if (!empty($item)) $matches[] = $item;
                    continue;
                }
            }
        }

        return $matches;
    }

    /**
     * Method to get a list of menu-items
     *
     * @access private
     * @param null
     * @return array
     */
    private function getMenuItems() 
    {
        static $rows = null;
        if (empty($rows)) {
            $menu = JFactory::getApplication()->getMenu();
            $rows = $menu->getMenu();
        }

        return $rows;
    }

    /**
     * Method to get the URL for a specific Menu-Item
     *
     * @access private
     * @param object $item
     * @return string
     */
    private function getMenuItemUrl($item) 
    {
        if ($item->type == 'component')
        {
            $link = $item->link.'&Itemid='.$item->id;

            if (!empty($item->language))
            {
                $link .= '&lang='.$item->language;
            }

            $item->url = JRoute::_($link);
        }
        else
        {
            $item->url = $item->link;
        }

        return $item->url;
    }

    /**
     * Method to prepare a menu-item
     *
     * @access private
     * @param object $item
     * @return string
     */
    private function prepareMenuItem($item) 
    {
        // Check access for 1.5
        if (Dynamic404HelperCore::isJoomla15()) {
            $user = &JFactory::getUser();
            if (isset($item->access) && $item->access > $user->get('aid', 0)) {
                return null;
            }
        } else {
            $item->name = $item->title;
        }

        if (empty($item->name)) {
            return null;
        }

        $item->type = 'component';
        $item->url = $this->getMenuItemUrl($item);
        return $item;
    }
}
