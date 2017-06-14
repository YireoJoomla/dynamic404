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
 */
class Dynamic404ViewPlugins extends YireoView
{
	/*
	 * Method to prepare for HTML output
	 *
	 * @param string $tpl
	 */
	public function display($tpl = null)
	{
		$this->setTitle('Plugins');
		JToolBarHelper::preferences('com_dynamic404', 600, 800);

		(new Dynamic404HelperGUI)->setMenu();

		// Automatically fetch items, total and pagination - and assign them to the template
		$this->fetchItems();

		parent::display($tpl);
	}
}
