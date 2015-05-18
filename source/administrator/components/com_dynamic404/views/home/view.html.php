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
 *
 * @static
 * @package     Dynamic404
 */
class Dynamic404ViewHome extends YireoViewHome
{
	/*
	 * Display method
	 *
	 * @param string $tpl
	 * @return null
	 */
	public function display($tpl = null)
	{
		$icons = array();
		$icons[] = $this->icon('setup', 'Setup', 'setup.png');
		$icons[] = $this->icon('redirects', 'Redirects', 'redirect.png');
		$icons[] = $this->icon('logs', 'Logs', 'info.png');
		$icons[] = $this->icon('matches', 'Test Matches', 'search.png');
		$this->assignRef('icons', $icons);

		$urls = array();
		$urls['twitter'] = 'http://twitter.com/yireo';
		$urls['facebook'] = 'http://www.facebook.com/yireo';
		$urls['tutorials'] = 'http://www.yireo.com/tutorials/dynamic404';
		$urls['jed'] = 'http://extensions.joomla.org/extensions/extension/site-management/error-pages/dynamic404';
		$this->assignRef('urls', $urls);

		parent::display($tpl);
	}
}