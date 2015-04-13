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
		$url = JURI::root();
		$host = $uri->toString(array('host'));

		$this->assignRef('url', $url);
		$this->assignRef('host', $host);

		parent::display();
	}
}
