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
        
/**
 * HTML View class 
 *
 * @static
 * @package     Dynamic404
 */
class Dynamic404ViewRedirects extends YireoViewList
{
    /*
     * Method to prepare for HTML output
     *
     * @access public
     * @param string $tpl
     * @return null
     */
    public function display($tpl = null)
	{
        // Hackish way of closing this page when it is a modal box
        if(JRequest::getInt('modal') == 1) {
            echo '<script>window.parent.SqueezeBox.close();</script>';
            $this->app->close();
        }

        // Automatically fetch items, total and pagination - and assign them to the template
        $this->fetchItems();

		parent::display($tpl);
	}
}
