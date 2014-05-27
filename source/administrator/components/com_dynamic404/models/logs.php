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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class Dynamic404ModelLogs extends YireoModel
{

    /**
     * Constructor
     *
     * @access public
     * @param null
     * @return null
     */
	public function __construct()
	{
        $this->_search = array('request');
        $this->_checkout = false;
        $this->_limit_query = true;
        $this->_orderby_default = 'ordering';
		parent::__construct('log');
	}

    /**
     * Method to build the database query
     *
     * @access protected
     * @param null
     * @return mixed
     */
    protected function buildQuery($query = '')
    {
        $query = 'SELECT `log`.* FROM `#__dynamic404_logs` AS `log`';
        return parent::buildQuery($query);
    }

    /**
     * Method to build the ORDERBY-segment of the database query
     *
     * @access protected
     * @param null
     * @return mixed
     */
    protected function buildOrderBy()
    {
        $this->setFilter('orderby', 'timestamp');        
        return parent::buildOrderBy();
    }
}
