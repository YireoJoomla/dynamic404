<?php
/**
 * Joomla! plugin for Dynamic404 - FLEXIcontent 
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
 * Dynamic404 Plugin for Ninjaboard
 */
class plgDynamic404Ninjaboard extends JPlugin
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
            $plugin = JPluginHelper::getPlugin('dynamic404', 'ninjaboard');
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
        if(!is_dir(JPATH_SITE.'/components/com_ninjaboard')) {
            return false;
        }
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
        $matches = array();
        if($this->isEnabled() == false) {
            return $matches;
        }

        // Find a matching post
        $post = $this->findPost(JRequest::getInt('p'));
        if(!empty($post)) $matches[] = $post;

        // Find a matching product
        $forum = $this->findForum(JRequest::getInt('f'));
        if(!empty($forum)) $matches[] = $forum;

        return $matches;
    }

    /**
     * Method to find a matching post
     *
     * @access private
     * @param object $item
     * @return string
     */
    private function findPost($id) 
    {
        $db = JFactory::getDBO();
        $query = "SELECT `ninjaboard_post_id`, `subject`, `ninjaboard_topic_id` FROM `yio_ninjaboard_posts` WHERE `ninjaboard_post_id` = ".(int)$id;
        $db->setQuery( $query );
        $post = $db->loadObject();
        if(empty($post)) {
            return null;
        }

        $post->type = 'component';
        $post->name = $post->subject;
        $post->match_note = 'ninjaboard post';
        $post->rating = $this->getParams()->get('rating', 85);
        $post->url = JRoute::_( 'index.php?option=com_ninjaboard&view=topic&id='.$post->ninjaboard_topic_id).'#'.$id;
        return $post;
    }

    /**
     * Method to find a matching forum
     *
     * @access private
     * @param object $item
     * @return string
     */
    private function findForum($id) 
    {
        $db = JFactory::getDBO();
        $query = "SELECT `ninjaboard_forum_id` AS id, `alias`, `title` FROM `#__ninjaboard_forums` WHERE `ninjaboard_forum_id`=".(int)$id." LIMIT 0,1";
        $db->setQuery( $query );
        $forum = $db->loadObject();
        if(empty($forum)) {
            return null;
        }

        $forum->type = 'component';
        $forum->name = $forum->title;
        $forum->rating = $this->getParams()->get('rating', 85) - 1;
        $forum->match_note = 'ninjaboard forum';

        $slug = $forum->id.':'.$forum->alias;
        $forum->url = JRoute::_( 'index.php?option=com_ninjaboard&view=forum&id='.$slug);
        return $forum;
    }
}
