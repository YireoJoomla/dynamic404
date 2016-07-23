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
		$this->addIcons();
		$this->addUrls();


		JToolbarHelper::custom('updateQueries', 'archive', '', 'DB Upgrade', false);

		parent::display($tpl);
	}

	/**
	 * Add icons
	 */
	protected function addIcons()
	{
		$icons = array();
		$icons[] = $this->icon('setup', 'Setup', 'setup.png');
		$icons[] = $this->icon('redirects', 'Redirects', 'redirect.png');
		$icons[] = $this->icon('logs', 'Logs', 'info.png');
		$icons[] = $this->icon('matches', 'Test Matches', 'search.png');

		$this->icons = $icons;
	}

	/**
	 * Add URLs
	 */
	protected function addUrls()
	{
		$urls = array();
		$urls['twitter'] = 'http://twitter.com/yireo';
		$urls['facebook'] = 'http://www.facebook.com/yireo';
		$urls['tutorials'] = 'https://www.yireo.com/tutorials/dynamic404';
		$urls['jed'] = 'http://extensions.joomla.org/extensions/extension/site-management/error-pages/dynamic404';
		
		$this->urls = $urls;
	}
}