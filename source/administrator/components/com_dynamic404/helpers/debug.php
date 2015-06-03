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
defined('_JEXEC') or die();

/**
 * Class Dynamic404HelperDebug
 */
class Dynamic404HelperDebug
{
	/**
	 * Method to output a certain debugging message
	 *
	 * @param      $msg
	 * @param null $variable
	 *
	 * @return null
	 */
	static public function debug($msg, $variable = null)
	{
		$params = JComponentHelper::getParams('com_dynamic404');

		if ($params->get('debug', 0) == 0)
		{
			return;
		}

		if (!empty($variable))
		{
			$variableDump = self::dump($variable);
			$msg .= ' = <code>' . trim($variableDump) . '</code>';
		}

		$msg .= "\n";

		if (JFactory::getApplication()->isSite())
		{
			echo $msg . '<br/>';
		}
		elseif (JFactory::getApplication()->isAdmin())
		{
			JFactory::getApplication()->enqueueMessage($msg);
		}

	}

	/**
	 * Method to dump a variable to a string
	 *
	 * @param  mixed   $variable  Variable to convert into string
	 *
	 * @return string
	 */
	static public function dump($variable)
	{
		if (is_object($variable))
		{
			if ($variable instanceof JDatabaseQuery)
			{
				$db = JFactory::getDBO();
				$query = (string) $variable;
				$query = str_replace('#__', $db->getPrefix(), $query);

				return '[JDatabaseQuery] ' . $query;
			}
			elseif ($variable instanceof SimpleXML)
			{
				return '[SimpleXML]';
			}
		}

		return var_export($variable, true);
	}
}
