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

// Load the Yireo library
jimport('yireo.loader');

/**
 * Dynamic404 Controller
 *
 * @package     Dynamic404
 */
class Dynamic404Controller extends YireoAbstractController
{
	/**
	 * @var JApplicationCms
	 */
	protected $app;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		/** @var JApplicationCms app */
		$this->app = JFactory::getApplication();
        $input = $this->app->input;
        $view = $input->get('view');

        if (empty($view))
        {
		    $input->set('view', 'notfound');
        }

		parent::__construct();
	}

	/**
	 * Simple test action
	 */
	public function test()
	{
		echo 'Basic connection succeeded<br/>';
		echo 'Current URL: ' . JUri::getInstance()->toString(array('scheme', 'host', 'port', 'path', 'params'));
		$this->app->close();
	}
}

