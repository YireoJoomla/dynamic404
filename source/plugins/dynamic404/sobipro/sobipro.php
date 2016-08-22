<?php
/**
 * Joomla! plugin for Dynamic404 - SobiPro
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
 * Dynamic404 Plugin for SobiPro
 */
class plgDynamic404SobiPro extends JPlugin
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
        if(!is_dir(JPATH_SITE.'/components/com_sobipro')) {
            return false;
        }

        $this->initSB();
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
     * Get all SobiPro entries
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

            $alias = preg_replace('/^([0-9]+)-/', '', $alias);
            $alias = preg_replace('/\?(.*)/', '', $alias);
            $alias = explode('-', $alias);

            $where = array();
            foreach($alias as $a) {
                $where[] = 'o.`nid` LIKE '.$db->Quote('%'.$a.'%').'';
            }

            $query = 'SELECT o.`id` AS `id`, o.`nid` AS `alias`, fd.`baseData` AS `name`, r.`pid` FROM  `#__sobipro_object` AS o'
                . ' LEFT JOIN `#__sobipro_field_data` AS fd ON o.id = fd.sid'
                . ' LEFT JOIN `#__sobipro_field` AS f ON fd.fid = f.fid'
                . ' LEFT JOIN `#__sobipro_relations` AS r ON o.id = r.id'
                . ' WHERE f.nid = "field_name" AND ('.implode(' OR ', $where).')'
            ;
                
            $db->setQuery($query);
            $rows = $db->loadObjectList();

            if(!empty($rows)) { 
                $rowIds = array();
                foreach($rows as $index => $row) {

                    if(in_array($row->id, $rowIds)) {
                        unset($rows[$index]);
                        continue;
                    }
                    $rowIds[] = $row->id;

                    // Check for the number of matches of alias-segments
                    $rating = $this->getParams()->get('rating_entry', 80);
                    foreach($alias as $a) {
                        if(stristr($row->alias, $a)) $rating = $rating + 1;
                    }

                    $row->rating = $rating;
                    $row->row_type = 'item';
                    $rows[$index] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * Get all SobiPro categories
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

            $alias = preg_replace('/^([0-9]+)-/', '', $alias);
            $alias = preg_replace('/\?(.*)/', '', $alias);
            $alias = explode('-', $alias);

            $where = array();
            foreach($alias as $a) {
                $where[] = 'o.`nid` LIKE '.$db->Quote('%'.$a.'%').'';
            }

            $query = 'SELECT o.`id` AS `id`, o.`nid` AS `alias`, o.`name`, r.`pid` FROM  `#__sobipro_object` AS o'
                . ' LEFT JOIN `#__sobipro_category` AS c ON o.id = c.id'
                . ' LEFT JOIN `#__sobipro_relations` AS r ON o.id = r.id'
                . ' WHERE c.id > 0 AND ('.implode(' OR ', $where).')'
            ;
                
            $db->setQuery($query);
            $rows = $db->loadObjectList();

            if(!empty($rows)) { 
                foreach($rows as $index => $row) {

                    // Check for the number of matches of alias-segments
                    $rating = $this->getParams()->get('rating_category', 80);
                    foreach($alias as $a) {
                        if(stristr($row->alias, $a)) $rating = $rating + 1;
                    }

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
                $item->match_note = 'sobipro category';
                $var = array('option' => 'com_sobipro', 'pid' => $item->pid, 'sid' => (int)$item->id.':'.$item->alias);
                $item->url = SPFactory::mainframe()->url($var);
                break;

            case 'item':
            default:
                $item->match_note = 'sobipro entry';
                $var = array('option' => 'com_sobipro', 'pid' => $item->pid, 'sid' => (int)$item->id.':'.$item->alias);
                $item->url = SPFactory::mainframe()->url($var);
                break;
        }
        return $item;
    }

    /**
     * Initialize everything of SobiPro
     *
     * @access private
     * @param null
     * @return null
     */
    private function initSB()
    {
        if(defined('SOBIPRO')) {
            return;
        }

        define( 'SOBI_TESTS', false );
        defined( 'DS' ) || define( 'DS', DIRECTORY_SEPARATOR );
        define( 'SOBI_CMS', 'joomla3');
        define( 'SOBIPRO', true );
        define( 'SOBI_TASK', 'task' );
	    define( 'SOBI_DEFLANG', JFactory::getConfig()->get( 'language', JFactory::getConfig()->get( 'config.language' ) ) );
        define( 'SOBI_ACL', 'front' );
        define( 'SOBI_ROOT', JPATH_ROOT );
        define( 'SOBI_MEDIA', implode( DS, array( JPATH_ROOT, 'media', 'sobipro' ) ) );
        define( 'SOBI_MEDIA_LIVE', JURI::root().'media/sobipro' );
        define( 'SOBI_PATH', SOBI_ROOT.'/components/com_sobipro' );
        define( 'SOBI_LIVE_PATH', 'components/com_sobipro' );

        include_once SOBI_PATH.'/lib/sobi.php';
        include_once SOBI_PATH.'/lib/base/fs/loader.php';

        SPLoader::loadClass('base.exception');
        SPLoader::loadClass('base.const');
        SPLoader::loadClass('base.factory');
        SPLoader::loadClass('base.object');
        SPLoader::loadClass('base.request');

    }
}
