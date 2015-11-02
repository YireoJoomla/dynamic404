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

// Load extra helpers
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/core.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/debug.php';

/**
 * HTML View class 
 *
 * @static
 * @package     Dynamic404
 */
class Dynamic404ViewMatches extends YireoView
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

		$this->url = $this->application->input->get('url', null, 'raw');
		$this->matches = $this->getMatches($this->url);

		parent::display();
	}

	public function getMatches($url)
	{
		$matchHelper = new Dynamic404HelperMatch($url);
		$matches = $matchHelper->getMatches();

		return $matches;
	}
}
