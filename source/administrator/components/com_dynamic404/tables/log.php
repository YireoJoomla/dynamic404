<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright (C) 2014 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
* Dynamic404 Table class
*/
class TableLog extends YireoTable
{
	/**
	 * Constructor
     *
     * @access public
     * @param JDatabase $db
     * @return null
	 */
	public function __construct(& $db) 
    {
        // Initialize the fields
        $this->_fields = array( 
            'log_id' => null,
            'request' => null,
            'timestamp' => null,
            'hits' => null,
        );

        // Set the required fields
        $this->_required = array( 'request', 'timestamp' );

        // Call the constructor
		parent::__construct('#__dynamic404_logs', 'log_id', $db);
	}
}
