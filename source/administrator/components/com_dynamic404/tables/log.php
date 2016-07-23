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

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Dynamic404 Table class
 */
class Dynamic404TableLog extends YireoTable
{
	/**
	 * Constructor
	 *
	 * @param JDatabase $db
	 */
	public function __construct(& $db)
	{
		// Initialize the fields
		$this->_fields = array(
			'log_id'    => null,
			'request'   => null,
			'timestamp' => null,
			'hits'      => null,
		);

		// Set the required fields
		$this->_required = array('request', 'timestamp');

		// Call the constructor
		parent::__construct('#__dynamic404_logs', 'log_id', $db);
	}
}
