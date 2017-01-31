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
 * Class Dynamic404ModelRedirects
 */
class Dynamic404ModelRedirects extends YireoModelItems
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$rt = parent::__construct('redirect');
		$this->setConfig('search_fields', array('match', 'url'));

		return $rt;
	}
}
