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
		$this->url = $this->app->input->get('url', null, 'raw');
		$this->matches = $this->getMatches($this->url);

		parent::display();
	}

	/**
	 * @param null $url
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getMatches($url = null)
	{
		if (empty($url))
		{
			return array();
		}

		$remoteUrl = JUri::root() . 'index.php?option=com_dynamic404&view=matches&format=raw&url=' . urlencode(base64_encode($url));

		$result = Dynamic404Helper::fetchPage($remoteUrl, null, true);
		$matches = json_decode($result);

		return $matches;
	}
}
