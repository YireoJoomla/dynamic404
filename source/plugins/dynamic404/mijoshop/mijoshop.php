<?php
/**
 * Joomla! plugin for Dynamic404 - MijoShop
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
 * Dynamic404 Plugin for MijoShop 
 */
class plgDynamic404Mijoshop extends JPlugin
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
        if(!is_dir(JPATH_SITE.'/components/com_mijoshop')) {
            return false;
        }

        $mijoshop = JPATH_ROOT.'/components/com_mijoshop/mijoshop/mijoshop.php';
        $library = JPATH_ROOT.'/components/com_mijoshop/opencart/config.php';
        if (!file_exists($mijoshop) or !file_exists($library)) {
            return false;
        }

        require_once($mijoshop);

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

        $rows = $this->getItems($urilast);
        if(!empty($rows)) {
            foreach( $rows as $row ) {

                $row = $this->prepareItem($row);
                if(empty($row)) {
                    continue;
                }

                $matches[] = $row;
            }
        }

        $rows = $this->getCategories($urilast);
        if(!empty($rows)) {
            foreach( $rows as $row ) {

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
     * Get all MijoShop items
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

            $aliasNumber = (int)$aliasNumber;
            $alias = preg_replace('/^([0-9]+)-/', '', $alias);
            $alias = preg_replace('/\?(.*)/', '', $alias);
            $alias = explode('-', $alias);

            $where = array();
            foreach($alias as $a) {
                $where[] = 'pd.`name` LIKE '.$db->Quote('%'.$a.'%').'';
            }

            $db->setQuery('SELECT p.`product_id`, pd.`name`, pc.`category_id` FROM `#__mijoshop_product` AS p'
                . ' LEFT JOIN `#__mijoshop_product_description` AS pd ON pd.`product_id` = p.`product_id`'
                . ' LEFT JOIN `#__mijoshop_product_to_category` AS pc ON pc.`product_id` = p.`product_id`'
                . ' WHERE p.`status`=1 AND ('.implode(' OR ', $where).' OR p.`product_id` = '.$aliasNumber.')');
            $rows = $db->loadObjectList();

            if(!empty($rows)) { 
                foreach($rows as $index => $row) {

                    // Check for the number of matches of alias-segments
                    $rating = $this->getParams()->get('rating_product', 80);
                    foreach($alias as $a) {
                        if(stristr($row->name, $a)) $rating = $rating + 1;
                    }

                    $row->id = $row->product_id;
                    $row->rating = $rating;
                    $row->row_type = 'item';
                    $rows[$index] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * Get all MijoShop categories
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

            $aliasNumber = (int)$aliasNumber;
            $alias = preg_replace('/^([0-9]+)-/', '', $alias);
            $alias = preg_replace('/\?(.*)/', '', $alias);
            $alias = explode('-', $alias);

            $where = array();
            foreach($alias as $a) {
                $where[] = 'cd.`name` LIKE '.$db->Quote('%'.$a.'%').'';
            }

            $db->setQuery('SELECT c.`category_id`, cd.`name` FROM `#__mijoshop_category` AS c'
                . ' LEFT JOIN `#__mijoshop_category_description` AS cd ON cd.`category_id` = c.`category_id`'
                . ' WHERE c.`status`=1 AND ('.implode(' OR ', $where).' OR c.`category_id` = '.$aliasNumber.')');
            $rows = $db->loadObjectList();

            if(!empty($rows)) { 
                foreach($rows as $index => $row) {

                    // Check for the number of matches of alias-segments
                    $rating = $this->getParams()->get('rating_category', 80);
                    foreach($alias as $a) {
                        if(stristr($row->name, $a)) $rating = $rating + 1;
                    }

                    $row->id = $row->category_id;
                    $row->rating = $rating;
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
        $item->type = 'component';
        switch($item->row_type) {
    
            case 'category':
                $item->match_note = 'mijoshop category';
                $route = 'index.php?route=product/category&path='.(int)$item->id;
                $item->url = MijoShop::get('router')->route($route);
                break;

            case 'item':
            default:
                $item->match_note = 'mijoshop item';
                if(isset($item->category_id) && $item->category_id > 0) {
                    $route = 'index.php?route=product/product&path='.(int)$item->category_id.'&product_id='.(int)$item->id;
                } else {
                    $route = 'index.php?route=product/product&product_id='.(int)$item->id;
                }
                $item->url = MijoShop::get('router')->route($route);
                break;
        }
        return $item;
    }
}
