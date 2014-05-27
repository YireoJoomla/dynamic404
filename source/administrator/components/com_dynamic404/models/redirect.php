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

class Dynamic404ModelRedirect extends YireoModel
{
    /**
     * Indicator if this is a model for multiple or single entries
     */
    protected $_single = true;
    //protected $_debug = true;

    /**
     * Constructor
     *
     * @access public
     * @param null
     * @return null
     */
    public function __construct()
    {
        $this->_orderby_title = 'description';
        parent::__construct('redirect');
    }

    /**
     * Method to store the model
     *
     * @access public
     * @subpackage Yireo
     * @param mixed $data
     * @return bool
     */
    public function store($data)
    {
        if (isset($data['match']) && $data['match'] != '/') {
            $data['match'] = preg_replace( '/^\//', '', $data['match']);
        }

        return parent::store($data);
    }
}
