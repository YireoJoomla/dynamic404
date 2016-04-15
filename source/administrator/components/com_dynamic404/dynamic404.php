<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// Load the Yireo library
require_once JPATH_COMPONENT . '/lib/loader.php';

// Load the helpers
require_once JPATH_COMPONENT . '/helpers/helper.php';
require_once JPATH_COMPONENT . '/helpers/core.php';
require_once JPATH_COMPONENT . '/helpers/gui.php';

// Definitions
define('DYNAMIC404_ERROR_PATCH', JPATH_ADMINISTRATOR . '/components/com_dynamic404/lib/error.php');
define('DYNAMIC404_ERROR_TARGET', JPATH_SITE . '/templates/system/error.php');
define('DYNAMIC404_ERROR_BACKUP', JPATH_SITE . '/templates/system/error.before-dynamic404.php');

// Get the required controller
$view = JFactory::getApplication()->input->getCmd('view');
$controller = YireoCommonController::getControllerInstance('dynamic404', $view);

// Perform the Request task
$controller->execute(JFactory::getApplication()->input->getCmd('task'));
$controller->redirect();