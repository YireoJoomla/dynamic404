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

// Require the base controller
require_once JPATH_COMPONENT . '/controller.php';
$controller = new Dynamic404Controller;

// Perform the Request task
$app = JFactory::getApplication();
$controller->execute($app->input->getCmd('task'));
