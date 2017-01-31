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
 * Class Dynamic404ModelLogs
 */
class Dynamic404ModelLogs extends YireoModelItems
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$rt = parent::__construct('log');

		$this->setConfig('search_fields', ['request']);
		$this->setConfig('table_prefix_auto', true);
		$this->_checkout = false;
		$this->_limit_query = true;

		$this->setConfig('orderby_default', 'timestamp');

		return $rt;
	}
}
