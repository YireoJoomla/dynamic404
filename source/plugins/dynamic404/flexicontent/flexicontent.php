<?php
/**
 * Joomla! plugin for Dynamic404 - FLEXIcontent plugin
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
 * Dynamic404 Plugin for FLEXIcontent
 */
class plgDynamic404FLEXIcontent extends JPlugin
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
            $plugin = JPluginHelper::getPlugin('dynamic404', 'flexicontent');
            $params = new JParameter($plugin->params);
            return $params;
        } else {
            return $this->params;
        }
    }

    /**
     * Return on all numeric matches
     *
     * @access public
     * @param array $arguments
     * @return null
     */     
    public function getNumericMatches($number = null)
    {
        $db = JFactory::getDBO();
        $nullDate = $db->getNullDate();
        $now = JFactory::getDate();
        $now = (method_exists('JDate', 'toSql')) ? $now->toSql() : $now->toMySQL();
        $query = 'SELECT a.id, a.title, a.alias, a.catid, c.alias AS catalias, a.access FROM #__content AS a'
            . ' LEFT JOIN `#__categories` AS c ON c.id = a.catid'
            . ' WHERE a.state = 1 '
            . ' AND ( a.publish_up = "'.$nullDate.'" OR a.publish_up <= "'.$now.'" )'
            . ' AND ( a.publish_down = "'.$nullDate.'" OR a.publish_down >= "'.$now.'" )'
            . ' AND a.id = '.(int)$number
            . ' ORDER BY ordering'
        ;

        $db->setQuery( $query );
        $rows = $db->loadObjectList();

        $matches = array();
        if(!empty($rows)) {
            foreach( $rows as $row ) {
                $row = $this->prepareArticle($row);
                if(!empty($row)) $matches[] = $row;
            }
        }   

        return $matches;
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
        $matches = array();
        $urilast = preg_replace( '/^([0-9]+)-/', '', $urilast );
        $urilast = preg_replace( '/\?(.*)$/', '', $urilast );
        $urilast2 = str_replace( '_', '-', $urilast );

        $db = JFactory::getDBO();
        $nullDate = $db->getNullDate();
        $now = JFactory::getDate();
        $now = (method_exists('JDate', 'toSql')) ? $now->toSql() : $now->toMySQL();
        $query = 'SELECT a.id, a.title, a.alias, a.catid, c.alias AS catalias, a.access FROM #__content AS a'
            . ' LEFT JOIN `#__categories` AS c ON c.id = a.catid'
            . ' WHERE a.state = 1 '
            . ' AND ( a.publish_up = "'.$nullDate.'" OR a.publish_up <= "'.$now.'" )'
            . ' AND ( a.publish_down = "'.$nullDate.'" OR a.publish_down >= "'.$now.'" )'
            . ' AND (a.alias LIKE "%'.$urilast.'%" OR a.alias LIKE "%'.$urilast2.'%")' 
            . ' AND a.id IN (SELECT itemid FROM yio_flexicontent_cats_item_relations WHERE itemid = a.id)'
            . ' ORDER BY ordering'
        ;

        $section = $this->getParams()->get('section');
        if(!empty($section) && $section > 0) {
            $query .= ' AND a.sectionid = '.(int)$section;
        }

        $db->setQuery( $query );
        $rows = $db->loadObjectList();

        if(!empty($rows)) {
            foreach( $rows as $row ) {

                if(!isset($row->alias) || empty($row->alias) || (empty($urilast) && empty($urilast2))) {
                    continue;
                }

                if($row->alias == $urilast || strstr($row->alias, $urilast) || strstr($urilast, $row->alias) ||
                    $row->alias == $urilast2 || strstr($row->alias, $urilast2) || strstr($urilast2, $row->alias)) {
            
                    $row = $this->prepareArticle($row);
                    if(!empty($row)) $matches[] = $row;
                    continue;
                }
            }
        }

        return $matches;
    }

    /**
     * Method to prepare an article
     *
     * @access private
     * @param object $item
     * @return string
     */
    private function prepareArticle($item) 
    {
        // Check access
        $user = JFactory::getUser();
        $accessLevels = $user->getAuthorisedViewLevels();
        if(isset($item->access) && $item->access > 0 && !in_array($item->access, $accessLevels)) {
            return null;
        }


        $item->type = 'component';
        $item->name = $item->title;
        $item->rating = $this->getParams()->get('rating_articles', 85);
        $item->match_note = 'flexicontent alias';

        $slug = $item->id.':'.$item->alias;
        $catslug = $item->catid.':'.$item->catalias;
        $item->url = JRoute::_( 'index.php?option=com_flexicontent&view=items&cid='.$catslug.'&id='.$slug );
        return $item;
    }
}
