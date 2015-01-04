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
defined('_JEXEC') or die();
        
/**
 * HTML View class 
 */
class Dynamic404ViewLogs extends YireoViewList
{
    /*
     * Flag to determine whether to load edit/copy/new buttons
     */
    protected $loadToolbarEdit = false;

    /*
     * Method to prepare for HTML output
     *
     * @access public
     * @param string $tpl
     * @return null
     */
    public function display($tpl = null)
    {
        JToolBarHelper::custom('deleteAll','deleteall.png','deleteall.png', 'Delete all', false);

        // Automatically fetch items, total and pagination - and assign them to the template
        $this->fetchItems();

        parent::display($tpl);
    }
}
