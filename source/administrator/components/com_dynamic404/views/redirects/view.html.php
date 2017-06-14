<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (https://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright 2017 Yireo (https://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        https://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * HTML View class
 *
 * @static
 * @package     Dynamic404
 */
class Dynamic404ViewRedirects extends YireoViewList
{
	/**
	 * @var Dynamic404HelperGUI
	 */
	public $guiHelper;

	/*
	 * Method to prepare for HTML output
	 *
	 * @param string $tpl
	 * @return void
	 */
	public function display($tpl = null)
	{
		// Hackish way of closing this page when it is a modal box
		if ($this->app->input->getInt('modal') == 1)
		{
			echo '<script>window.parent.SqueezeBox.close();</script>';
			$this->app->close();
		}

		// Automatically fetch items, total and pagination - and assign them to the template
		$this->fetchItems();

		foreach ($this->items as $item)
		{
			$item->match = urldecode($item->match);
			$item->url = urldecode($item->url);
		}

		$this->guiHelper = new Dynamic404HelperGUI;

		parent::display($tpl);
	}
}
