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

// Include the loader
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/lib/loader.php';

/**
 * Dynamic404 Controller
 *
 * @package     Dynamic404
 */
class Dynamic404Controller extends YireoAbstractController
{
	protected $app;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		/** @var JApplicationCms app */
		$this->app = JFactory::getApplication();
		$this->app->input->setVar('view', 'notfound');

		parent::__construct();
	}

	public function test()
	{
		echo 'Basic connection succeeded<br/>';
		echo 'Current URL: ' . JURI::getInstance()->toString(array('scheme', 'host', 'port', 'path', 'params'));
		$this->app->close();
	}
}

