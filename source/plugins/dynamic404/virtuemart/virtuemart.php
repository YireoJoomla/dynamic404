<?php
/**
 * Joomla! plugin for Dynamic404 - VirtueMart 
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
 * Dynamic404 Plugin for VirtueMart
 */
class plgDynamic404VirtueMart extends JPlugin
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
            $plugin = JPluginHelper::getPlugin('dynamic404', 'virtuemart');
            $params = new JParameter($plugin->params);
            return $params;
        } else {
            return $this->params;
        }
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
        if(!is_dir(JPATH_SITE.'/components/com_virtuemart')) {
            return false;
        }
        return true;
    }

    /**
     * Determine if this is VirtueMart 1 or not
     * 
     * @access private
     * @param null
     * @return JParameter
     */
    private function isVm1()
    {
        if(!is_dir(JPATH_SITE.'/components/com_virtuemart/views')) {
            return false;
        }
        return true;
    }

    /**
     * Get the current locale
     * 
     * @access private
     * @param null
     * @return JParameter
     */
    private function getLanguageCode()
    {
        $language = JFactory::getLanguage();
        return str_replace('-', '_', $language->lang_code);
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
        
        // Find a matching product
        $product = $this->findProduct($urilast, JRequest::getInt('product_id'));
        if(!empty($product)) $matches[] = $product;

        // Find a matching category
        $category = $this->findCategory($urilast, JRequest::getInt('category_id'));
        if(!empty($category)) $matches[] = $category;

        return $matches;
    }

    /**
     * Method to match possible products
     *
     * @access private
     * @param object $item
     * @return string
     */
    private function findCategory($urilast, $category_id) 
    {
        $db = JFactory::getDBO();

        if($this->isVm1()) {
            $query = "SELECT c.category_name FROM `#__vm_category` AS c "
                . " WHERE c.`category_id`=".(int)$category_id
                ." LIMIT 0,1"
            ;
        } else {
            $query = "SELECT l.category_name FROM `#__virtuemart_categories_".$this->getLanguageCode()."` AS l "
                . " LEFT JOIN `#__virtuemart_categories` AS c ON c.`virtuemart_category_id` = l.`virtuemart_category_id`" 
                . " WHERE (l.`virtuemart_category_id`=".(int)$product_id." OR l.`slug` LIKE '%".$urilast."%')"
                . " AND c.`published`=1 "
                . " LIMIT 0,1"
            ;
        }

        $db->setQuery( $query );
        $category = $db->loadObject();
        $category->match_note = 'virtuemart category';
        if(empty($category)) {
            return null;
        }

        $category->type = 'component';
        $category->name = $category->category_name;
        $category->rating = $this->getParams()->get('rating', 85) - 1;

        if($this->isVm1()) {
            $category->url = JRoute::_('index.php?page=shop.browse&category_id='.$category_id);
        } else {
            $category->url = JRoute::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$category_id);
        }
        return $category;
    }

    /**
     * Method to match possible products
     *
     * @access private
     * @param object $item
     * @return string
     */
    private function findProduct($urilast, $product_id) 
    {
        $db = JFactory::getDBO();

        if($this->isVm1()) {
            $query = "SELECT p.`product_name`, x.`category_id` FROM `#__vm_product` AS p "
                . " LEFT JOIN `#__vm_product_category_xref` AS x ON x.`product_id` = p.`product_id` "
                . " WHERE p.`product_id`=".(int)$product_id." AND `product_publish`='Y' "
                . " LIMIT 0,1"
            ;
        } else {
            $query = "SELECT l.product_name, c.`virtuemart_category_id` AS category_id FROM `#__virtuemart_products_".$this->getLanguageCode()."` AS l "
                . " LEFT JOIN `#__virtuemart_product_categories` AS c ON c.`virtuemart_product_id` = l.`virtuemart_product_id`" 
                . " LEFT JOIN `#__virtuemart_product` AS p ON p.`virtuemart_product_id` = l.`virtuemart_product_id`" 
                . " WHERE (l.`virtuemart_product_id`=".(int)$product_id." OR l.`slug` LIKE '%".$urilast."%')"
                . " AND p.`published`=1 "
                . " LIMIT 0,1"
            ;
        }

        $db->setQuery( $query );
        $product = $db->loadObject();
        if(empty($product)) {
            return null;
        }

        $category_id = $product->category_id;
        $product->type = 'component';
        $product->name = $product->product_name;
        $product->match_note = 'virtuemart product';
        $product->rating = $this->getParams()->get('rating', 85);

        if($this->isVm1()) {
            $product->url = JRoute::_('index.php?page=shop.product_details&flypage=flypage.tpl&product_id='.$product_id.'&category_id='.$category_id);
        } else {
            $product->url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$product_id.'&virtuemart_category_id='.$category_id);
        }
        return $product;
    }
}
