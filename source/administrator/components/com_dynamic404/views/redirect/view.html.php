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
        // Automatically fetch the item and assign it to the layout
        $this->fetchItem();

        // Complete this item from the URL
        if(empty($this->item->match)) {
            $this->item->match = base64_decode(JRequest::getString('match'));
        }

        // Add select boxes
        $options = Dynamic404HelperGUI::getMatchTypes();
        $this->lists['type'] = JHTML::_('select.genericlist', $options, 'type', null, 'value', 'title', $this->item->type );

        $options = Dynamic404HelperGUI::getRedirectTypes();
        $this->lists['http_status'] = JHTML::_('select.genericlist', $options, 'http_status', null, 'value', 'title', $this->item->http_status );

		parent::display($tpl);
	}

    /*
     * Helper-method to get a tip-text
     *
     * @access public
     * @param string $title
     * @param string $description
     * @return array
     */
    public function getMessageText($title = null, $description = null)
    {
        return '<span class="hasTip" title="'.JText::_($title).'::'.JText::_($description).'">'
            . ((!empty($title)) ? JText::_($title) : null)
            . '&nbsp; <img src="../media/com_dynamic404/images/info.png" alt="'.JText::_( 'Info' ).'" />'
            . '</span>'
        ;
    }
}
