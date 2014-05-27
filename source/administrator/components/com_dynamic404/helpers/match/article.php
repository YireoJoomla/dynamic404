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

class Dynamic404HelperMatchArticle
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
     * @access public
     * @param null
     * @return null
     */
    public function findNumericMatches($id) 
    {
        $row = $this->getArticleById($id);
        $row = $this->prepareArticle($row);
        if (empty($row)) return null;
        $row->match_note = 'article id';
        return array($row);
    }

    /**
     * Method to find matches within Joomla! articles
     *
     * @access public
     * @param null
     * @return null
     */
    public function findTextMatches($text1, $text2) 
    {
        $matches = array();

        // Match the number only
        if (preg_match('/^([0-9]+)\-/', $text1, $match)) {
            $row = $this->getArticleById($match[0]);
            $row = $this->prepareArticle($row);
            if (!empty($row)) {
                $row->rating = 95;
                $matches[] = $row;
            }
        }

        // Match the alias
        $rows = $this->getArticleList($text1, $text2);
        if (!empty($rows)) {
            foreach ( $rows as $row ) {

                if (!isset($row->alias) || empty($row->alias)) {
                    continue;
                }

                if (Dynamic404HelperMatch::matchTextString($row->alias, $text1) || Dynamic404HelperMatch::matchTextString($row->alias, $text2)) {
                    $row = $this->prepareArticle($row);
                    if (!empty($row)) {
                        $row->match_note = 'article alias';
                        $matches[] = $row;
                    }
                    continue;

                } else {
                    $row->match_parts = array();
                    $row->match_parts = array_merge($row->match_parts, Dynamic404HelperMatch::matchTextParts($row->alias, $text1));
                    $row->match_parts = array_merge($row->match_parts, Dynamic404HelperMatch::matchTextParts($row->alias, $text2));
                    if (!empty($row->match_parts)) {
                        $row = $this->prepareArticle($row);
                        if (!empty($row)) {
                            $row->match_note = 'article alias';
                            $row->rating = $row->rating - 10 + count($row->match_parts);
                            $matches[] = $row;
                        }
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Method to redirect to a specific match
     *
     * @access private
     * @param string $slug
     * @param int $catid
     * @param int $sectionid
     * @return string
     */
    private function getArticleLink($slug, $catid, $sectionid = null) 
    {
        require_once JPATH_SITE.'/components/com_content/helpers/route.php' ;
        if($sectionid > 0) {
            return ContentHelperRoute::getArticleRoute( $slug, $catid, $sectionid );
        } else {
            return ContentHelperRoute::getArticleRoute( $slug, $catid);
        }
    }

    /**
     * Method to get an article by ID
     *
     * @access private
     * @param null
     * @return array
     */
    private function getArticleById($id) 
    {
        $db = JFactory::getDBO();
        $db->setQuery( 'SELECT * FROM #__content WHERE state = 1 AND id = '.(int)$id.' ORDER BY ordering LIMIT 1' );
        if($this->params->get('debug', 0) == 1) echo 'Dynamic404HelperMatchArticle::getArticleById = '.$db->getQuery().'<br/>';
        return $db->loadObject();
    }

    /**
     * Method to get a list of articles
     *
     * @access private
     * @param null
     * @return array
     */
    private function getArticleList($text1, $text2) 
    {
        static $rows = null;
        if (empty($rows)) {
            $db = JFactory::getDBO();

            $query = 'SELECT * FROM `#__content` WHERE `state` = 1 ';
            if($this->params->get('load_all_articles', 0) == 0) {
                $text1 = $db->Quote('%'.$text1.'%');
                $text2 = $db->Quote('%'.$text2.'%');
                $query .= 'AND (`alias` LIKE '.$text1.' OR `alias` LIKE '.$text2.')';
            }
            $query .= ' ORDER BY `ordering`';

            $db->setQuery($query);
            if($this->params->get('debug') == 1) echo 'Dynamic404HelperMatchArticle::getArticleList = '.$db->getQuery().'<br/>';
            $rows = $db->loadObjectList();
        }

        return $rows;
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
        // Sanity checks
        if (empty($item) || !is_object($item)) {
            return null;
        }

        // Check access for 1.5
        if (Dynamic404HelperCore::isJoomla15()) {
            $user = &JFactory::getUser();
            if (isset($item->access) && $item->access > $user->get('aid', 0)) {
                return null;
            }
        }

        $item->type = 'component';
        $item->name = $item->title;
        $item->rating = $this->params->get('rating_articles', 85);
        if(isset($item->sectionid)) {
            $item->url = $this->getArticleLink( $item->id.':'.$item->alias, $item->catid, $item->sectionid );
        } else {
            $item->url = $this->getArticleLink( $item->id.':'.$item->alias, $item->catid);
        }
        return $item;
    }
}
