<?php
/**
 * Joomla! component Dynamic404
 *
 * @package    Dynamic404
 * @author     Yireo <info@yireo.com>
 * @copyright  Copyright 2016 Yireo (https://www.yireo.com/)
 * @license    GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link       https://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Class Dynamic404HelperDebug
 */
class Dynamic404HelperDebug
{
	/**
	 * @var \Joomla\Registry\Registry
	 */
	protected $params = null;

	/**
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Singleton method
	 *
	 * @return Dynamic404HelperDebug
	 */
	public static function getInstance()
	{
		static $instance = null;

		if ($instance === null)
		{
			$instance = new Dynamic404HelperDebug;

			/** @var \Joomla\Registry\Registry $params */
			$params = JComponentHelper::getParams('com_dynamic404');
			$instance->setParams($params);
		}

		return $instance;
	}

	/**
	 * Method to set parameters internally
	 *
	 * @param \Joomla\Registry\Registry $params
	 */
	public function setParams($params)
	{
		$this->params = $params;
	}

	/**
	 * Method to output a certain debugging message
	 *
	 * @param string $message
	 * @param null|mixed $variable
	 *
	 * @return null
	 */
	public function doDebug($message, $variable = null)
	{
		if ($this->params->get('debug', 0) == 0)
		{
			return;
		}

		if (!empty($variable))
		{
			$variableDump = self::dump($variable);
			$message .= ' = ' . trim($variableDump);
		}

		$message .= "\n";
		$app = JFactory::getApplication();

		if ($app->isSite())
		{
			$this->messages[] = $message;
		}
		elseif ($app->isAdmin())
		{
			$app->enqueueMessage($message, 'notice');
		}
	}

	/**
	 * Method to dump a variable to a string
	 *
	 * @param  mixed   $variable  Variable to convert into string
	 *
	 * @return string
	 */
	public function doDump($variable)
	{
		if (is_object($variable))
		{
			if ($variable instanceof JDatabaseQuery)
			{
				$db = JFactory::getDbo();
				$query = (string) $variable;
				$query = str_replace('#__', $db->getPrefix(), $query);

				$breakWords = array('WHERE', 'OR', 'FROM', 'LEFT JOIN');

				foreach ($breakWords as $breakWord)
				{
					$query = str_replace(' ' . $breakWord . ' ', ' <br/>' . $breakWord . ' ', $query);
				}

				return '[JDatabaseQuery] ' . $query;
			}
			elseif ($variable instanceof SimpleXML)
			{
				return '[SimpleXML]';
			}
		}

		return var_export($variable, true);
	}

	/**
	 * Get all messages
	 */
	public function getMessages()
	{
		return $this->messages;
	}

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
		return self::getInstance()->doDebug($msg, $variable);
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
		return self::getInstance()->doDump($variable);
	}
}
