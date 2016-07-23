<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (https://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        https://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!  
defined('_JEXEC') or die();

/**
 * HTML View class
 */
class Dynamic404ViewBrowse extends YireoView
{
	public function __construct($config = array())
	{
		$this->loadToolbar = false;
		parent::__construct($config);
	}

	/*
	 * Method to prepare for HTML output
	 *
	 * @access public
	 * @param string $tpl
	 * @return null
	 */
	public function display($tpl = null)
	{
		JToolBarHelper::custom( 'refresh', 'preview.png', 'preview_f2.png', 'Browse', false );
		
		$uri = JURI::getInstance();
		$this->url = JURI::root() . 'index.php?option=com_dynamic404&task=test';
		//$this->url = $helper->getMenuItemUrl(404);
		$this->host = $uri->toString(array('host'));

		parent::display();
	}
}
