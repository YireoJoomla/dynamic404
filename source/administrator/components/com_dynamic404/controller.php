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
 * Dynamic404 Controller
 *
 * @package     Dynamic404
 */
class Dynamic404Controller extends YireoController
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_default_view = 'home';

		parent::__construct();
	}

	/**
	 * Method to restore the original error.php file
	 */
	public function deleteAll()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__dynamic404_logs'));

		$db->setQuery($query);
		$db->execute();

		// Redirect
		$msg = 'COM_DYNAMIC404_CONTROLLER_DELETEALL_REMOVED_LOGS';
		$this->setRedirect('index.php?option=com_dynamic404&view=logs', $msg);
	}

	/**
	 * Method to restore the original error.php file
	 */
	public function restore()
	{
		// Import file-library
		jimport('joomla.filesystem.file');

		// Only restore if the MD5-checksum matches
		if (is_file(DYNAMIC404_ERROR_BACKUP) && md5_file(DYNAMIC404_ERROR_BACKUP) != md5_file(DYNAMIC404_ERROR_TARGET))
		{
			// Restore the files
			$rt = JFile::move(DYNAMIC404_ERROR_BACKUP, DYNAMIC404_ERROR_TARGET);

			if ($rt == true)
			{
				$msg = JText::_('COM_DYNAMIC404_CONTROLLER_DELETEALL_RESTORE_RESTORED_SUCCESS');
			}
			else
			{
				$msg = JText::_('COM_DYNAMIC404_CONTROLLER_DELETEALL_RESTORE_RESTORED_FAILED');
			}
		}
		else
		{
			$msg = JText::_('COM_DYNAMIC404_CONTROLLER_DELETEALL_RESTORE_NO_BACKUP');
		}

		// Redirect
		$this->setRedirect('index.php?option=com_dynamic404&view=setup', $msg);
	}

	/**
	 * Method to patch the system error.php file
	 */
	public function patch()
	{
		// Import the file-library
		jimport('joomla.filesystem.file');

		// Only patch if the files match
		if (md5_file(DYNAMIC404_ERROR_PATCH) != md5_file(DYNAMIC404_ERROR_TARGET))
		{
			// Create a backup
			if (!file_exists(DYNAMIC404_ERROR_BACKUP))
			{
				JFile::copy(DYNAMIC404_ERROR_TARGET, DYNAMIC404_ERROR_BACKUP);
			}

			// Patch the file
			JFile::copy(DYNAMIC404_ERROR_PATCH, DYNAMIC404_ERROR_TARGET);
		}

		// Redirect
		$this->setRedirect('index.php?option=com_dynamic404&view=setup');
	}

	/**
	 * Method to enable the Dynamic404 System Plugin
	 */
	public function pluginD404()
	{
		// Perform the database query
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . '=1')
			->where(array(
					$db->quoteName('type') . '=' . $db->quote('plugin'),
					$db->quoteName('element') . '=' . $db->quote('dynamic404'),
					$db->quoteName('folder') . '=' . $db->quote('system')
				));

		$db->setQuery($query);
		$db->execute();

		// Redirect
		$this->setRedirect('index.php?option=com_dynamic404&view=setup');
	}

	/**
	 * Method to enable the Dynamic404 System Plugin
	 */
	public function pluginRedirect()
	{
		// Perform the database query
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . '=0')
			->where(array(
					$db->quoteName('type') . '=' . $db->quote('plugin'),
					$db->quoteName('element') . '=' . $db->quote('redirect'),
					$db->quoteName('folder') . '=' . $db->quote('system')
				));

		$db->setQuery($query);
		$db->execute();

		// Redirect
		$this->setRedirect('index.php?option=com_dynamic404&view=setup');
	}

	/**
	 * Method to run SQL-update queries
	 */
	public function updateQueries()
	{
		// Run the update-queries
		require_once JPATH_COMPONENT . '/helpers/update.php';
		Dynamic404Update::runUpdateQueries();

		// Redirect
		$link = 'index.php?option=com_dynamic404&view=home';
		$msg = JText::_('LIB_YIREO_CONTROLLER_DB_UPGRADED');
		$this->setRedirect($link, $msg);
	}

	/**
	 *
	 */
	public function ajax()
	{
		echo 'Success';
		exit;
	}
}
