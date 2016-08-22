<?php
/**
 * Joomla! plugin for Dynamic404 - FLEXIcontent plugin
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
 * Dynamic404 Plugin for FLEXIcontent
 */
class plgDynamic404FLEXIcontent extends JPlugin
{
    /**
     * Return on all numeric matches
     *
     * @access public
     * @param array $arguments
     * @return null
     */     
    public function getNumericMatches($number = null)
    {
        $db = JFactory::getDbo();
        $nullDate = $db->getNullDate();
        $now = JFactory::getDate();
        $now = (method_exists('JDate', 'toSql')) ? $now->toSql() : $now->toMySQL();

        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('a.id', 'a.title', 'a.alias', 'a.catid', 'a.access')));
        $query->select($db->quoteName('c.alias', 'catalias'));
        $query->from($db->quoteName('#__content', 'a'));

        $query->join('LEFT', $db->quoteName('#__categories', 'c') 
            . ' ON (' . $db->quoteName('a.catid') . ' = ' . $db->quoteName('c.id') . ')');

        $query->where($db->quoteName('a.id') . ' = '.(int)$number);
        $query->where($db->quoteName('a.state') . ' = 1');
        $query->where('(' . $db->quoteName('a.publish_up') . ' = '.$db->quote($nullDate) 
            . ' OR ' . $db->quoteName('a.publish_up') . ' <= ' . $db->quote($now) . ')');
        $query->where('(' . $db->quoteName('a.publish_down') . ' = '.$db->quote($nullDate) 
            . ' OR ' . $db->quoteName('a.publish_down') . ' >= ' . $db->quote($now) . ')');

        $query->order($db->quoteName('a.ordering'));

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

        $db = JFactory::getDbo();
        $nullDate = $db->getNullDate();
        $now = JFactory::getDate();
        $now = (method_exists('JDate', 'toSql')) ? $now->toSql() : $now->toMySQL();

        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('a.id', 'a.title', 'a.alias', 'a.catid', 'a.access')));
        $query->select($db->quoteName('c.alias', 'catalias'));
        $query->from($db->quoteName('#__content', 'a'));

        $query->join('LEFT', $db->quoteName('#__categories', 'c') 
            . ' ON (' . $db->quoteName('a.catid') . ' = ' . $db->quoteName('c.id') . ')');

        $query->where($db->quoteName('a.state') . ' = 1');
        $query->where('(' . $db->quoteName('a.publish_up') . ' = '.$db->quote($nullDate) 
            . ' OR ' . $db->quoteName('a.publish_up') . ' <= ' . $db->quote($now) . ')');
        $query->where('(' . $db->quoteName('a.publish_down') . ' = '.$db->quote($nullDate) 
            . ' OR ' . $db->quoteName('a.publish_down') . ' >= ' . $db->quote($now) . ')');
        $query->where('(' . $db->quoteName('a.alias') . ' LIKE ' . $db->quote('%' . $urilast . '%') 
            . ' OR ' . $db->quoteName('a.alias') . ' LIKE ' . $db->quote('%' . $urilast2 . '%'). ')');
        $query->where($db->quoteName('a.id') . ' IN (SELECT itemid FROM #__flexicontent_cats_item_relations WHERE itemid = a.id)');

        $query->order($db->quoteName('a.ordering'));

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
        $item->rating = $this->params->get('rating_articles', 85);
        $item->match_note = 'flexicontent alias';

        $slug = $item->id.':'.$item->alias;
        $catslug = $item->catid.':'.$item->catalias;
        $item->url = JRoute::_( 'index.php?option=com_flexicontent&view=items&cid='.$catslug.'&id='.$slug );
        return $item;
    }
}
