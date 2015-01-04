<?php
/**
 * Joomla! component Dynamic404
 *
 * @package    Dynamic404
 * @author     Yireo <info@yireo.com>
 * @copyright  Copyright 2015 Yireo (http://www.yireo.com/)
 * @license    GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link       http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('JPATH_BASE') or die;

/*
 * Check helper
 */

class Dynamic404HelperCheck
{
	static public function checkDynamic404SystemPlugin()
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . '=' . $db->quote('plugin'))
			->where($db->quoteName('folder') . '=' . $db->quote('system'))
			->where($db->quoteName('element') . '=' . $db->quote('dynamic404'))
			->where($db->quoteName('enabled') . '= 1');
		$db->setQuery($query);
		$row = $db->loadObject();

		if (!empty($row))
		{
			$status = 'ok';
			$message = JText::_('COM_DYNAMIC404_CHECK_DYNAMIC404SYSTEMPLUGIN_ENABLED');
		}
		elseif (file_exists(JPATH_SITE . '/plugins/system/dynamic404/dynamic404.php'))
		{
			$link = 'index.php?option=com_dynamic404&task=pluginD404';
			$message = JText::sprintf('COM_DYNAMIC404_CHECK_DYNAMIC404SYSTEMPLUGIN_DISABLED', $link);
			$status = 'warning';
		}
		else
		{
			$link = 'https://www.yireo.com/software/joomla-extensions/dynamic404/downloads';
			$message = JText::sprintf('COM_DYNAMIC404_CHECK_DYNAMIC404SYSTEMPLUGIN_NOTINSTALLED', $link);
			$status = 'error';
		}

		return array(
			'label' => 'COM_DYNAMIC404_CHECK_DYNAMIC404SYSTEMPLUGIN_LABEL',
			'status' => $status,
			'message' => $message,
		);
	}

	static public function checkRedirectSystemPlugin()
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . '=' . $db->quote('plugin'))
			->where($db->quoteName('folder') . '=' . $db->quote('system'))
			->where($db->quoteName('element') . '=' . $db->quote('redirect'))
			->where($db->quoteName('enabled') . '= 1');
		$db->setQuery($query);
		$row = $db->loadObject();

		if (empty($row))
		{
			$status = 'ok';
			$message = JText::_('COM_DYNAMIC404_CHECK_REDIRECTSYSTEMPLUGIN_DISABLED');
		}
		else
		{
			$link = 'index.php?option=com_dynamic404&task=pluginRedirect';
			$message = JText::sprintf('COM_DYNAMIC404_CHECK_REDIRECTSYSTEMPLUGIN_ENABLED', $link);
			$status = 'warning';
		}

		return array(
			'label' => 'COM_DYNAMIC404_CHECK_REDIRECTSYSTEMPLUGIN_LABEL',
			'status' => $status,
			'message' => $message,
		);
	}

	static public function checkSefEnabled()
	{
		$sef = (bool) JFactory::getConfig()->get('sef');

		if ($sef)
		{
			$status = 'ok';
			$message = JText::_('COM_DYNAMIC404_CHECK_SEF_ENABLED');
		}
		else
		{
			$message = JText::_('COM_DYNAMIC404_CHECK_SEF_DISABLED');
			$status = 'warning';
		}

		return array(
			'label' => 'COM_DYNAMIC404_CHECK_SEF_LABEL',
			'status' => $status,
			'message' => $message,
		);
	}

	static public function checkSefRewritesEnabled()
	{
		$sefRewrites = (bool) JFactory::getConfig()->get('sef_rewrite');

		if ($sefRewrites)
		{
			$status = 'ok';
			$message = JText::_('COM_DYNAMIC404_CHECK_SEFREWRITES_ENABLED');
		}
		else
		{
			$message = JText::_('COM_DYNAMIC404_CHECK_SEFREWRITES_DISABLED');
			$status = 'warning';
		}

		return array(
			'label' => 'COM_DYNAMIC404_CHECK_SEFREWRITES_LABEL',
			'status' => $status,
			'message' => $message,
		);
	}

	static public function checkAutoRedirectEnabled()
	{
		$autoRedirect = (bool) JComponentHelper::getParams('com_dynamic404')->get('enable_redirect', 1);

		if ($autoRedirect)
		{
			$status = 'ok';
			$message = JText::_('COM_DYNAMIC404_CHECK_AUTOREDIRECT_ENABLED');
		}
		else
		{
			$message = JText::_('COM_DYNAMIC404_CHECK_AUTOREDIRECT_DISABLED');
			$status = 'warning';
		}

		return array(
			'label' => 'COM_DYNAMIC404_CHECK_AUTOREDIRECT_LABEL',
			'status' => $status,
			'message' => $message,
		);
	}
}
