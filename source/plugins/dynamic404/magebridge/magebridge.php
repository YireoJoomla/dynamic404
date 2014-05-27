<?php
/**
 * Joomla! plugin for Dynamic404 - MageBridge 
 *
 * @author      Yireo (http://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright (c) 2013 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

// Import the parent class
jimport( 'joomla.plugin.plugin' );

/**
 * Dynamic404 Plugin for MageBridge
 */
class plgDynamic404MageBridge extends JPlugin
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
        jimport('joomla.version');
        $version = new JVersion();
        if(version_compare($version->RELEASE, '1.5', 'eq')) {
            $plugin = JPluginHelper::getPlugin('dynamic404', 'magebridge');
            $params = new JParameter($plugin->params);
            return $params;
        } else {
            return $this->params;
        }
    }

    /**
     * Check whether MageBridge is enabled
     * 
     * @access private
     * @param null
     * @return JParameter
     */
    private function isEnabled()
    {
        if(!is_dir(JPATH_SITE.'/components/com_magebridge')) {
            return false;
        }
        require_once JPATH_SITE.'/components/com_magebridge/helpers/loader.php';
        return true;
    }

    /**
     * Return on all matches
     *
     * @access public
     * @param array $arguments
     * @return null
     */
    public function getMatches($urilast = null)
    {
        if($this->isEnabled() == false) return array();

        $matches = array();
        $rows = array();

        if($this->getParams()->get('enable_products', 1) == 1) {
            $products = plgDynamic404MageBridge::getProducts($urilast);
            if(!empty($products)) $rows = array_merge($rows, $products);
        }

        if($this->getParams()->get('enable_categories', 1) == 1) {
            $categories = plgDynamic404MageBridge::getCategories($urilast);
            if(!empty($categories)) $rows = array_merge($rows, $categories);
        }

        if(!empty($rows)) {
            foreach( $rows as $row ) {

                if(!isset($row['url_key']) || empty($row['url_key']) || empty($urilast)) {
                    continue;
                }

                if($row['url_key'] == $urilast || strstr($row['url_key'], $urilast) || strstr($urilast, $row['url_key'])) {

                    $type = (isset($row['product_id'])) ? 'product' : 'category';
                    $row = $this->prepareItem($row, $type);
                    if(!empty($row)) $matches[] = $row;
                    continue;
                }
            }
        }

        return $matches;
    }

    /**
     * Get all MageBridge products
     *
     * @access public
     * @param array $words
     * @return null
     */
    public function getProducts($urlsegment)
    {
        // Parse the words into proper arguments
        $arguments = array('filters' => array('url_key' => array('like' => '%'.$urlsegment.'%')));
    
        // Include the MageBridge register
        $register = MageBridgeModelRegister::getInstance();
        $register->clean();
        $register->add('api', 'magebridge_product.list', $arguments);

        // Include the MageBridge bridge
        $bridge = MageBridgeModelBridge::getInstance();
        $bridge->build();

        // Get the result
        $products = $bridge->getAPI('magebridge_product.list', $arguments);

        return $products;
    }

    /**
     * Get all MageBridge categories
     *
     * @access public
     * @param array $words
     * @return null
     */
    public function getCategories($urlsegment)
    {
        // Parse the words into proper arguments
        $arguments = array('filters' => array('url_key' => array('like' => '%'.$urlsegment.'%')));
    
        // Include the MageBridge register
        $register = MageBridgeModelRegister::getInstance();
        $register->clean();
        $register->add('api', 'magebridge_category.list', $arguments);

        // Include the MageBridge bridge
        $bridge = MageBridgeModelBridge::getInstance();
        $bridge->build();

        // Get the result
        $categories = $bridge->getAPI('magebridge_category.list', $arguments);

        return $categories;
    }

    /**
     * Method to prepare an item
     *
     * @access private
     * @param object $item
     * @return string
     */
    private function prepareItem($item, $type = 'product') 
    {
        $item = JArrayHelper::toObject($item);
        $item->type = 'component';
        $item->name = $type.'; '.$item->name;
        $item->match_note = 'magebridge '.$type;

        if(isset($item->url)) {
            $item->url = JRoute::_('index.php?option=com_magebridge&view=root&request='.$item->url);
        } elseif(isset($item->url_key)) {
            $item->url = JRoute::_('index.php?option=com_magebridge&view=root&request='.$item->url_key);
        }

        if($type == 'product') {
            $item->rating = $this->getParams()->get('product_rating', 85);
        } elseif($type == 'category') {
            $item->rating = $this->getParams()->get('category_rating', 85);
        }
        
        return $item;
    }
}
