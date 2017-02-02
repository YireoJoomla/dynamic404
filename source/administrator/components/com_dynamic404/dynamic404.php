<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        https://www.yireo.com/
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Load the Yireo library
jimport('yireo.loader');

// Check for helper
if (!class_exists('YireoHelperInstall'))
{
	try
	{
		require_once JPATH_COMPONENT . '/helpers/install.php';
		$installer = new YireoHelperInstall;
		$installer->autoInstallLibrary('yireo', 'https://www.yireo.com/documents/lib_yireo_j3x.zip', 'Yireo Library');
		$installer->redirectToInstallManager();
	}
	catch (Exception $e)
	{
		die('Yireo Library is not installed and could not be installed automatically: ' . $e->getMessage());
	}
}

// Check for function
if (!class_exists('\Yireo\System\Autoloader'))
{
	die('Yireo Library is not installed and could not be installed automatically');
}

// Load the helpers
require_once JPATH_COMPONENT . '/helpers/helper.php';
require_once JPATH_COMPONENT . '/helpers/core.php';
require_once JPATH_COMPONENT . '/helpers/gui.php';

// Definitions
define('DYNAMIC404_ERROR_PATCH', JPATH_ADMINISTRATOR . '/components/com_dynamic404/lib/error.php');
define('DYNAMIC404_ERROR_TARGET', JPATH_SITE . '/templates/system/error.php');
define('DYNAMIC404_ERROR_BACKUP', JPATH_SITE . '/templates/system/error.before-dynamic404.php');

// Get the required controller
$view       = JFactory::getApplication()->input->getCmd('view');
$controller = YireoCommonController::getControllerInstance('dynamic404', $view);

// Perform the Request task
$controller->execute(JFactory::getApplication()->input->getCmd('task'));
$controller->redirect();
