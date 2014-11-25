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
	 *
	 * @access public
	 * @return null
	 */
	public function deleteAll()
	{
		$db = JFactory::getDBO();
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
	 *
	 * @access public
	 * @return null
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
	 *
	 * @access public
	 * @return null
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
	 *
	 * @access public
	 * @return null
	 */
	public function pluginD404()
	{
		// Perform the database query
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . '=1')
			->where(
				array(
					$db->quoteName('type') . '=' . $db->quote('plugin'),
					$db->quoteName('element') . '=' . $db->quote('dynamic404'),
					$db->quoteName('folder') . '=' . $db->quote('system')
				)
			);

		$db->setQuery($query);
		$db->execute();

		// Redirect
		$this->setRedirect('index.php?option=com_dynamic404&view=setup');
	}

	/**
	 * Method to enable the Dynamic404 System Plugin
	 *
	 * @access public
	 * @return null
	 */
	public function pluginRedirect()
	{
		// Perform the database query
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query
			->update($db->quoteName('#__extensions'))
			->set($db->quoteName('enabled') . '=0')
			->where(
				array(
					$db->quoteName('type') . '=' . $db->quote('plugin'),
					$db->quoteName('element') . '=' . $db->quote('redirect'),
					$db->quoteName('folder') . '=' . $db->quote('system')
				)
			);

		$db->setQuery($query);
		$db->execute();

		// Redirect
		$this->setRedirect('index.php?option=com_dynamic404&view=setup');
	}
}
