<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

// Include the loader
require_once(JPATH_ADMINISTRATOR.'/components/com_dynamic404/lib/loader.php');

// View class
class Dynamic404ViewNotfound extends YireoAbstractView
{
    public function display($tpl = null)
    {
        header('Content-Type: text/html; charset=utf-8');

        // Include the 404 Helper
        require_once JPATH_ADMINISTRATOR.'/components/com_dynamic404/helpers/helper.php';

        // Instantiate the helper with the argument of how many matches to show
        $helper = new Dynamic404Helper(false);
        $helper->log();
        $helper->doRedirect();

        // Get the possible matches
        $matches = $helper->getMatches();

        // Get the last segment - nice for searching
        $urilast = $helper->getLast();

        // Set the title
        $title = '404 Page Not Found';
        $document = JFactory::getDocument();
        $document->setTitle($title);

        // Load the article
        $article = $helper->getArticle();

        // Assign variables to template
        $this->assignRef('title', $title);
        $this->assignRef('matches', $matches);
        $this->assignRef('urilast', $urilast);
        $this->assignRef('helper', $helper);
        $this->assignRef('article', $article);

        parent::display($tpl);
    }
}
