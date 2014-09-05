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

// Load extra helpers
require_once JPATH_ADMINISTRATOR.'/components/com_dynamic404/helpers/match/article.php';
require_once JPATH_ADMINISTRATOR.'/components/com_dynamic404/helpers/match/category.php';
require_once JPATH_ADMINISTRATOR.'/components/com_dynamic404/helpers/match/menu.php';

class Dynamic404HelperMatch
{
    /*
     * Component parameters
     */
    private $params = null;

    /*
     * List of possible matches
     */
    private $matches = array();

    /**
     * Constructor
     *
     * @access public
     * @param null
     * @return null
     */
    public function __construct()
    {
        // Read the component parameters
        $this->params = JComponentHelper::getParams('com_dynamic404');

        // Initialize this helper
        $this->parseRequest();
    }

    /**
     * Method to parse all URI parts from the URL
     *
     * @access public
     * @param null
     * @return null
     */
    public function parseRequest() 
    {
        // System variables
        $application = JFactory::getApplication();

        // Get the URI
        $uri = base64_decode(JRequest::getString('uri'));
        if (empty($uri)) {
            $uri = JRequest::getURI();
        }
        $uri = str_replace('?noredirect=1', '', $uri);
        $uri = preg_replace('/\/$/', '', $uri);

        // Initialize the variables to 
        $uri_parts = array();
        $uri_last = null;
        $uri_lastnum = null;

        // If this looks like a SEF-URL, parse it
        if (strstr($uri, 'index.php?option=') == false && strstr($uri, 'index.php?Itemid=') == false) {

            // Fetch the current request and parse it 
            $uri = preg_replace('/^\//', '', $uri);
            $uri = preg_replace('/\.(html|htm|php)$/', '', $uri);
            $uri = preg_replace('/\?lang=([a-zA-Z0-9]+)$/', '', $uri);
            $uri = preg_replace('/\&Itemid=([0-9]?)$/', '', $uri);

            $uri_parts = explode( '/', $uri );
            if (!empty($uri_parts)) 
            {
                foreach ($uri_parts as $i => $part) 
                {
                    if (empty($part)) 
                    {
                        unset($uri_parts[$i]);
                    }
                }
            }
            $uri_parts = array_values($uri_parts);
            $total = count($uri_parts);

            for ($i = $total; $i > 0; $i--) 
            {
                if (!isset($uri_parts[$i-1])) continue;
                if (!is_numeric($uri_parts[$i-1]))  
                {
                    $uri_last = $uri_parts[$i-1];
                    break;
                } 
                elseif (is_numeric($uri_parts[$i-1])) 
                {
                    $uri_lastnum = $uri_parts[$i-1];
                }
            }

            $this->request = array(
                'uri' => $uri,
                'uri_parts' => $uri_parts,
                'uri_last' => $uri_last,
                'uri_lastnum' => $uri_lastnum,
            );

        // Non-SEF URL
        } 
        else 
        {

            $id = JRequest::getString('id');
            $id = explode(':', $id);
            if (is_numeric($id[0])) $uri_lastnum = $id[0];
            if (is_string($id[0])) $uri_last = $id[0];
            if (!empty($id[1])) $uri_last = $id[1];

            $this->request = array(
                'uri' => $uri,
                'uri_parts' => $uri_parts,
                'uri_last' => $uri_last,
                'uri_lastnum' => $uri_lastnum,
            );
        }
    }

    /**
     * Method to get a specific value from the request-array
     *
     * @access public
     * @param null
     * @return array
     */
    public function getRequest($name) 
    {
        if (isset($this->request[$name])) {
            return $this->request[$name];
        }
    }

    /**
     * Method to run all the match-functions and return the resulting matches
     *
     * @access public
     * @param null
     * @return array
     */
    public function getMatches() 
    {
        // Call the internal matches
        $this->findNumericMatches();
        $this->findTextMatches();
        $this->findSearchPluginMatches();
        $this->findRedirectMatches();

        $this->parseMatches();
        $this->sortMatches();
        return $this->matches;
    }

    /**
     * Method to collect all the numerical matches
     *
     * @access public
     * @param null
     * @return array
     */
    protected function findNumericMatches()
    {
        // Try to find numerical matches
        if ($this->params->get('search_ids', 1) && (is_numeric($this->request['uri_lastnum']) || preg_match('/^(m|a)([0-9]+)$/', $this->request['uri_last'], $match))) {
        
            // Find the right type for this segment (a is article, m is menu-item)
            if (isset($match) && isset($match[1]) && isset($match[2])) {
                $type = ($match[1] == 'a') ? 'article' : 'menuitem';
                $id = $match[2];
            } else {
                $type = 'any';
                $id = (int)$this->request['uri_lastnum'];
            }

            if (!$id > 0) {
                return false;
            }

            // Call the article-helper
            if (($type == 'any' || $type == 'article') && $this->params->get('search_articles',1) == 1) {
                $helper = new Dynamic404HelperMatchArticle($this->params);
                $this->addToMatches($helper->findNumericMatches($id));
            }

            // Call the category-helper
            if (($type == 'any' || $type == 'category') && $this->params->get('search_categories',1) == 1) {
                $helper = new Dynamic404HelperMatchCategory($this->params);
                $this->addToMatches($helper->findNumericMatches($id));
            }

            // Call the menuitem-helper
            if (($type == 'any' || $type == 'menuitem') && $this->params->get('search_menuitems',1) == 1) {
                $helper = new Dynamic404HelperMatchMenu($this->params);
                $this->addToMatches($helper->findNumericMatches($id));
            }

            // Call all dynamic404-plugins
            JPluginHelper::importPlugin('dynamic404');
            $application = JFactory::getApplication();
            $matches = $application->triggerEvent('getNumericMatches', array($id));
            if (isset($matches[0])) $this->addToMatches($matches[0]);

            return true;
        }
            
        return false;
    }

    /**
     * Method to collect all the text matches
     *
     * @access public
     * @param null
     * @return array
     */
    protected function findTextMatches()
    {
        // Try to find text matches
        if ($this->params->get('search_text', 1) && is_string($this->request['uri_last'])) {
        
            // Construct the first text
            $text1 = $this->request['uri_last'];
            $text1 = preg_replace('/([\-\_\.]+)$/', '', $text1);
            $text1 = preg_replace('/^([\-\_\.]+)/', '', $text1);

            // Construct the second text
            $text2 = str_replace('_', '-', $text1);

            if (empty($text1)) {
                return false;
            }

            // Call the article-helper
            if ($this->params->get('search_articles',1) == 1) {
                $helper = new Dynamic404HelperMatchArticle($this->params);
                $this->addToMatches($helper->findTextMatches($text1, $text2));
            }

            // Call the category-helper
            if ($this->params->get('search_categories',1) == 1) {
                $helper = new Dynamic404HelperMatchCategory($this->params);
                $this->addToMatches($helper->findTextMatches($text1, $text2));
            }

            // Call the menuitem-helper
            if ($this->params->get('search_menuitems',1) == 1) {
                $helper = new Dynamic404HelperMatchMenu($this->params);
                $this->addToMatches($helper->findTextMatches($text1, $text2, $this->request['uri'], $this->request['uri_parts']));
            }

            // Call all dynamic404-plugins
            JPluginHelper::importPlugin('dynamic404');
            $application = JFactory::getApplication();
            $matches = $application->triggerEvent('getMatches', array($text1, $text2));
            if (isset($matches[0])) $this->addToMatches($matches[0]);
            if (isset($matches[1])) $this->addToMatches($matches[1]);
        }
    }

    /**
     * Method to find matches using search plugins
     *
     * @access public
     * @param null
     * @return null
     */
    public function findSearchPluginMatches() 
    {
        if ($this->params->get('search_plugins', 1) == 0)
        {
            return false;
        }

        $keywords = array();
        if (!empty($this->request['uri_last']))
        {
            $keywords = explode('-', $this->request['uri_last']);
        }

        if (empty($keywords))
        {
            return false;
        }

        $search = implode(' ', $keywords);
        $match = 'all';
        $ordering = 'popular';
        $active = null;

        $helper = JPATH_ADMINISTRATOR.'/components/com_search/helpers/search.php';
        if(file_exists($helper)) {
            require_once $helper;
        }
    
        JPluginHelper::importPlugin('search');
        $dispatcher = JDispatcher::getInstance();
        $areas = $dispatcher->trigger('onContentSearch', array($search, $match, $ordering, $active));

        // Loop through the search results and add them to the matches
        $matches = array();
        foreach ($areas as $area)
        {
            foreach ($area as $row) 
            {
                // Construct the match
                $match = (object)null;
                $match->rating = $this->params->get('rating_search_plugins', 80);
                $match->match_note = 'search plugin';
                $match->type = 'component';
                $match->name = $row->title;
                $match->url = $row->href;

                // Increase the rating if the title matches directly
                $keywordMatch = 0;
                foreach($keywords as $keyword) {
                    if(empty($keyword)) continue;
                    if(stristr($match->name, $keyword)) $keywordMatch++;
                }
                if($keywordMatch == count($keywords)) {
                    $match->rating += 2;
                } elseif($keywordMatch > 0) {
                    $match->rating += 1;
                }

                $matches[] = $match;
            }
        }

        $this->addToMatches($matches);
        return $matches;
    }

    /**
     * Method to find matches within custom redirects
     *
     * @access public
     * @param null
     * @return null
     */
    public function findRedirectMatches() 
    {
        // Fetch all redirects from the Dynamic404 database-tables
        $db = JFactory::getDBO();
        $query = 'SELECT `match`, `url`, `http_status`, `type`, `description`, `params` '
            . ' FROM `#__dynamic404_redirects` WHERE `published`=1 ORDER BY `ordering`';
        $db->setQuery($query);
        $rows = $db->loadObjectList();

        // Loop through the redirect-rules to see how to apply them
        $matches = array();
        if (!empty($rows)) 
        {
            foreach ($rows as $row) 
            {
                // Make sure the match-field is filled
                if (empty($row->match)) continue;

                // Construct the URL
                if(is_numeric(trim($row->url))) 
                {
                    $menu = JFactory::getApplication()->getMenu();
                    $menuItem = $menu->getItem($row->url);
                    if(!empty($menuItem)) 
                    {
                        $row->name = $menuItem->title;
                        $row->url = $menuItem->link;
                        if(strstr($row->url, 'Itemid=') == false) $row->url .= '&Itemid='.$menuItem->id;
                        $row->url = JRoute::_($row->url);
                        $row->link = $row->url;
                    }
                } 
                elseif(preg_match('/Itemid=([0-9]+)/', $row->url, $match)) 
                {
                    $menu = JFactory::getApplication()->getMenu();
                    $menuItem = $menu->getItem($match[1]);
                    if(!empty($menuItem)) 
                    {
                        $row->name = $menuItem->title;
                        $row->url = $menuItem->link;
                        if(strstr($row->url, 'Itemid=') == false) $row->url .= '&Itemid='.$menuItem->id;
                        $row->url = JRoute::_($row->url);
                        $row->link = $row->url;
                    }
                }

                // Complete the item
                $params = YireoHelper::toRegistry($row->params);
                if (!empty($row->description) && $params->get('show_description', 0) == 1) $row->name = $row->description;
                if (empty($row->name)) $row->name = $row->url;
                if (empty($row->link)) $row->link = $row->url;

                // Copy the match-parts
                $uri_last = $this->request['uri_last'];
                $uri = $this->request['uri'];
                $uri_parts = $this->request['uri_parts'];

                // Convert to lower case
                if($params->get('match_case', 0) == 0) 
                {
                    $row->match = strtolower($row->match);
                    $uri_last = strtolower($uri_last);
                    $uri = strtolower($uri);
                    foreach($uri_parts as $uri_part_index => $uri_part)
                    {
                        $uri_parts[$uri_part_index] = strtolower($uri_part);
                    }
                }

                // Match the full URLs
                if ($row->type == 'full_url' && !empty($uri) && (strstr($row->match, $uri) || strstr($uri, $row->match))) 
                {
                    $row->type = 'component';
                    $row->rating = $params->get('rating', $this->params->get('rating_custom_full_url', 95));
                    $matches[] = $row;
                    break;

                // Match the last URL-segment
                } 
                elseif ($row->type == 'last_segment' && !empty($uri_last) && $row->match == $uri_last) 
                {
                    $row->type = 'component';
                    $row->rating = $params->get('rating', $this->params->get('rating_custom_last_segment', 94));
                    $matches[] = $row;
                    break;

                // Fuzzy matching
                } 
                elseif ($row->type == 'fuzzy') 
                { 
                    if ((!empty($uri_last) && strstr($row->match, $uri_last)) || (!empty($uri_last) && strstr($uri_last, $row->match))) 
                    {
                        $row->type = 'component';
                        $row->rating = $params->get('rating', $this->params->get('rating_custom_fuzzy', 90));
                        $matches[] = $row;
                        break;
                    }

                // Match any segment
                } 
                elseif ($row->type == 'any_segment' && !empty($uri_parts) && in_array($row->match, $uri_parts)) 
                {
                    $row->type = 'component';
                    $row->rating = $params->get('rating', $this->params->get('rating_custom_any_segment', 90));
                    $matches[] = $row;
                    break;

                }
            }
        }

        $this->addToMatches($matches);
        return $matches;
    }

    /**
     * Method to add a valid list of matches to the existing matches
     *
     * @access private
     * @param array $matches 
     * @return null
     */
    private function addToMatches($matches) 
    {
        if (is_array($matches) && !empty($matches)) {
            $this->matches = array_merge($this->matches, $matches);
        }
    }

    /**
     * Method to parse all the matches 
     *
     * @access public
     * @param null
     * @return null
     */
    private function parseMatches() 
    {
        if (!empty($this->matches)) 
        {
            $uri = JURI::getInstance();
            $config = JFactory::getConfig();
            $sef_rewrite = (bool)$config->get('sef_rewrite');
            foreach($this->matches as $index => $match) 
            {
                if(preg_match('/^index.php\?option=com_/', $match->url)) 
                {
                    $match->url = JRoute::_($match->url);
                } 
                elseif(preg_match('/^([a-zA-Z]+)/', $match->url) && preg_match('/^(http|https):\/\//', $match->url) == false) 
                {
                    $base_uri = JURI::base();
                    if($sef_rewrite == false) $base_uri .= 'index.php/';
                    $match->url = $base_uri.$match->url;
                }

                if(preg_match('/^(http|https):\/\//', $match->url) == false && preg_match('/^\//', $match->url) == false) 
                {
                    $base_uri = JURI::base();
                    if($sef_rewrite == false) $base_uri .= 'index.php/';
                    if(preg_match('/^\//', $match->url)) $base_uri = preg_replace('/\/$/', '', $base_uri);
                    $match->url = $base_uri.$match->url;
                }

                $this->matches[$index] = $match;
            }
        }
    }

    /**
     * Method to sort all the matches according to their rating
     *
     * @access public
     * @param null
     * @return null
     */
    private function sortMatches() 
    {
        if (!empty($this->matches)) 
        {
            $sort = array();
            $urls = array();
            foreach ($this->matches as $match) 
            {
                $index = urlencode($match->rating.'-'.$match->url);
                $sort[$index] = array( 'rating' => $match->rating, 'match' => $match );
            }
            ksort($sort);

            $matches = array();
            if (!empty($sort)) {
                foreach ($sort as $s) {
                    $matches[] = $s['match'];
                }
            }
            $this->matches = array_reverse($matches);
        }
    }

    static public function matchTextString($text1, $text2)
    {
        $text1 = strtolower($text1);
        $text2 = strtolower($text2);

        if ($text1 == $text2 || strstr($text1, $text2) || strstr($text2, $text1)) {
            return true;
        }
        return false;
    }

    static public function matchTextParts($text1, $text2)
    {
        $text1 = strtolower($text1);
        $text2 = strtolower($text2);

        $text1_parts = explode('-', $text1);
        $text2_parts = explode('-', $text2);

        $match_parts = array();
        if (!empty($text1_parts)) {
            foreach ($text1_parts as $text1_part) {
                if (in_array($text1_part, $text2_parts)) {
                    $match_parts[] = $text1_part;
                }
            }
        }

        return $match_parts;
    }
}
