<?php
/**
 * Joomla! plugin for Dynamic404 - EasyBlog
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
 * Dynamic404 Plugin for EasyBlog 
 */
class plgDynamic404EasyBlog extends JPlugin
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
        if(!is_dir(JPATH_SITE.'/components/com_easyblog')) {
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

        $rows = $this->getItems($urilast);
        if(!empty($rows)) {
            foreach( $rows as $row ) {

                if(!isset($row->permalink) || empty($row->permalink) || (empty($urilast) && empty($urilast2))) {
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
     * Get all EasyBlog items
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
            $query = $db->getQuery(true);
            $query->select($db->quoteName(array('id', 'title', 'permalink')));
            $query->from($db->quoteName('#__easyblog_post'));
            $query->where($db->quoteName('published') . ' = 1');
            $query->where($db->quoteName('permalink') . ' LIKE '. $db->quote('%'.$alias.'%'));
            $db->setQuery($query);
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
     * Method to prepare an item
     *
     * @access private
     * @param object $item
     * @return string
     */
    private function prepareItem($item)
    {
        if (empty($item->id)) {
            return null;
        }

        $item->type = 'component';
        $item->name = $item->title;
        $item->rating = $this->params->get('rating', 85);
        $item->match_note = 'easyblog item';

        switch($item->row_type) {
    
            case 'item':
            default:
                $this->includeFiles();
                $item->url = EasyBlogRouter::_('index.php?option=com_easyblog&view=entry&id='.(int)$item->id);
                break;
        }

        return $item;
    }

    private function includeFiles()
    {
        $files = array(
            JPATH_SITE.'/administrator/components/com_easyblog/includes/easyblog.php',
            JPATH_SITE.'/components/com_easyblog/helpers/router.php',
        );

        foreach($files as $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
}
