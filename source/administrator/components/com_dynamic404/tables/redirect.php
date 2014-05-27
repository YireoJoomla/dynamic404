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
class TableRedirect extends YireoTable
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
        $params = JComponentHelper::getParams('com_dynamic404');

        // Initialize the fields
        $this->_fields = array( 
            'redirect_id' => null,
            'match' => preg_replace('/^\//', '', base64_decode(JRequest::getString('match'))),
            'url' => null,
            'http_status' => $params->get('http_status', 301),
            'description' => null,
            'type' => null,
        );

        // Set the required fields
        $this->_required = array( 
            'match', 
            'url',
        );

        // Call the constructor
		parent::__construct('#__dynamic404_redirects', 'redirect_id', $db);
	}
}
