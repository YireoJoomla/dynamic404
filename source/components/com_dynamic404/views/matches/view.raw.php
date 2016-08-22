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
	/**
	 * @var string
	 */
	protected $url = null;

	/**
	 * @var array
	 */
	protected $matches = array();

	/*
	 * Method to prepare for HTML output
	 *
	 * @access public
	 * @param string $tpl
	 * @return null
	 */
	public function display($tpl = null)
	{
		ini_set('display_errors', 0);

		$this->url = $this->app->input->get->getBase64('url');
		$this->url = base64_decode($this->url);

		if (!empty($this->url))
		{
			$this->matches = $this->getMatches($this->url);
		}

		echo json_encode($this->matches);
	}

	/**
	 * @param $url
	 *
	 * @return array
	 */
	public function getMatches($url)
	{
		$matchHelper = new Dynamic404HelperMatch($url);
		$matches = $matchHelper->getMatches();

		return $matches;
	}
}
