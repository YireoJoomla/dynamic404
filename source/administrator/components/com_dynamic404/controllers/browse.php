<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Dynamic404 Controller
 */
class Dynamic404ControllerBrowse extends YireoController
{
	protected $responses = array();

	/**
	 * Browse task for JSON calls
	 *
	 */
	public function ajax()
	{
		$app = JFactory::getApplication();
		//$helper = new Dynamic404HelperCore;

		$uri = JURI::getInstance();
		$url = JURI::root() . 'index.php?option=com_dynamic404&task=test';
		//$url = $helper->getMenuItemUrl(404);

		$host = $uri->toString(array('host'));

		$this->browse($host, $url);

		echo implode($this->responses, '<br/>');
		$app->close();
	}

	/**
	 * Method to restore the original error.php file
	 *
	 * @param string $host
	 * @param string $url
	 *
	 * @return boolean
	 */
	protected function browse($host, $url)
	{
		if (empty($host) || empty($url))
		{
			$this->responses[] = 'ERROR: Empty host and/or URL';

			return false;
		}

		// Do basic resolving on the host if it is not an IP-address
		if (preg_match('/^([0-9\.]+)$/', $host) == false)
		{
			$host = preg_replace('/\:[0-9]+$/', '', $host);

			if (gethostbyname($host) == $host)
			{
				$this->responses[] = 'ERROR: Failed to resolve hostname "' . $host . '" in DNS';

				return false;
			}
		}

		// Try to open a socket to port 80
		if (fsockopen($host, 80, $errno, $errmsg, 5) == false)
		{
			$this->responses[] = 'ERROR: Failed to open a connection to host "' . $host . '" on port 80. Perhaps a firewall is in the way?';

			return false;
		}

		require_once JPATH_COMPONENT . '/helpers/helper.php';
		$response = Dynamic404Helper::fetchPage($url);
		echo $response;exit;

		// Fetch various responses
		$this->responses[] = 'Basic connection succeeded';
	}
}
