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
defined( '_JEXEC' ) or die( 'Restricted access' );

// Require the base controller
require_once (JPATH_COMPONENT.'/controller.php');
$controller	= new Dynamic404Controller( );

// Perform the Request task
$controller->execute(null);

