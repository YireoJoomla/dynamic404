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
class Dynamic404ViewRedirect extends YireoViewForm
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
        // Load Bootstrap
        YireoHelper::bootstrap();

        // Automatically fetch the item and assign it to the layout
        $this->fetchItem();

        // Complete this item from the URL
        if(empty($this->item->match)) {
            $this->item->match = base64_decode(JRequest::getString('match'));
        }

		parent::display($tpl);
	}
}
