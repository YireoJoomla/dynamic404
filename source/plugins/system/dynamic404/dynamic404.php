<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright (c) 2013 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('JPATH_BASE') or die;

jimport('joomla.plugin.plugin');

// Load the Yireo library
require_once(JPATH_ADMINISTRATOR . '/components/com_dynamic404/lib/loader.php');

/**
 * Plugin class for reusing redirection of the Dynamic404 component
 *
 * @package       Dynamic404
 * @subpackage    plgSystemDynamic404
 */
class plgSystemDynamic404 extends JPlugin
{
	/**
	 * Constructor.
	 *
	 * @param object &$subject The object to observe.
	 * @param array  $config   An optional associative array of configuration settings.
	 *
	 * @return null
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		// Set the error handler for E_ERROR to be the class handleError method.
		if (JFactory::getApplication()->isSite())
		{
			JError::setErrorHandling(E_ERROR, 'callback', array('plgSystemDynamic404', 'handleError'));
			set_exception_handler(array('plgSystemDynamic404', 'handleError'));
		}
	}

	/**
	 * Method to catch Joomla! error-handling
	 *
	 * @param object &$error JError object
	 *
	 * @return null
	 */
	static public function handleError(&$error)
	{
		// Get the application object.
		$application = JFactory::getApplication();
		$error = JError::getError();

		// Include the 404 Helper-class
		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/helper.php';

		// Instantiate the helper with the argument of how many matches to show
		$helper = new Dynamic404Helper();

		// Make sure the error is a 404 and we are not in the administrator.
		if (!$application->isAdmin() and ($error->get('code') == 404))
		{

			// Log the 404 entry
			$helper->log();

			// Get the possible matches
			$matches = $helper->getMatches();

			// Get the last segment - nice for searching
			$urilast = $helper->getLast();

			// Redirect to the first found match
			$helper->doRedirect();
		}

		// Render the error page.
		$params = JComponentHelper::getParams('com_dynamic404');
		if ($params->get('error_page', 0) == 1)
		{
			JError::customErrorPage($error);

		}
		else
		{
			$helper->displayErrorPage($error);
			$application->close(0);
		}
	}

	/**
	 * Method to be called after the Joomla! Application has been initialized
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function onAfterInitialise()
	{
		// Get the application object.
		$application = JFactory::getApplication();

		// Make sure we are not in the administrator.
		if ($application->isSite() == false)
		{
			return null;
		}

		// Redirect non-www to www
		$redirect_www = $this->params->get('redirect_www', 0);
		if ($redirect_www == 1)
		{
			$uri = JURI::current();
			if (preg_match('/^(http|https)\:\/\/([^\/]+)(.*)/', $uri, $match))
			{
				$hostname = $match[2];
				if (preg_match('/^www\./', $hostname) == false)
				{
					$newUrl = $match[1] . '://www.' . $hostname . $match[3];
					header('HTTP/1.1 301 Moved Permanently');
					header('Location: ' . $newUrl);
					$application->close();
					exit;
				}
			}
		}

		// Force lowercase
		$force_lowercase = $this->params->get('force_lowercase', 0);
		if ($force_lowercase == 1)
		{
			$uri = JURI::current();
			$lowercase_uri = strtolower($uri);
			if ($uri != $lowercase_uri)
			{
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: ' . $lowercase_uri);
				$application->close();
				exit;
			}
		}

		// Test for non-existent components
		$uri = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : null;
		if (preg_match('/\/component\/([a-zA-Z0-9\.\-\_]+)\//', $uri, $componentMatch))
		{
			$component = preg_replace('/^com_/', '', $componentMatch[1]);
			if (!is_dir(JPATH_SITE . '/components/com_' . $component))
			{

				// Fetch the last segment of this URL
				$segments = explode('/', $uri);
				$lastSegment = trim(array_pop($segments));
				if (empty($lastSegment))
				{
					$lastSegment = trim(array_pop($segments));
				}

				// Strip the ID if possible
				if (preg_match('/^([0-9]+)(.*)/', $lastSegment, $lastSegmentMatch))
				{
					$lastSegment = $lastSegmentMatch[2];
				}

				// Redirect to this fake URL assuming it will trigger a 404-redirect
				$url = JURI::base() . 'component/content/' . $lastSegment;
				header('Location: ' . $url);
				exit;
			}
		}

		/*
		 * @todo: Add setting to enable this behaviour
		if (false) {

			// Include the 404 Helper-class
			require_once JPATH_ADMINISTRATOR.'/components/com_dynamic404/helpers/helper.php';

			// Instantiate the helper with the argument of how many matches to show
			$helper = new Dynamic404Helper(false);

			// Get the possible matches
			$matches = $helper->getDirectMatches();

			// Redirect to the first found match
			if(!empty($matches)) {
				$helper->doRedirect();
			}
		}
		*/
	}

	/**
	 * Method to be called after the component has been routed
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function onAfterRoute()
	{
		// Get the application object.
		$application = JFactory::getApplication();

		// Make sure we are not in the administrator.
		if ($application->isSite() == false)
		{
			return null;
		}

		// Expand URLs
		$url = JURI::current();
		$params = JComponentHelper::getParams('com_dynamic404');
		if ($params->get('expand_ids', 1) == 1 && preg_match('/\/([0-9]+)/', $url))
		{
			$this->doExpandUrl();
		}
	}

	/**
	 * Method to expand the URL
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function doExpandUrl()
	{
		$url = JURI::current();
		$application = JFactory::getApplication();
		$component = $application->input->get('option');
		$view = $application->input->get('view');
		$id = $application->input->get('id');
		$newUrl = null;

		// Check for the article view
		if ($component == 'com_content' && $view == 'article')
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match/article.php';
			$matchHelper = new Dynamic404HelperMatchArticle();
			$newUrl = JRoute::_($matchHelper->getArticleLink($id));
			$newUrl = JURI::base() . substr($newUrl, strlen(JURI::base(true)) + 1);
		}
		else
		{
			// Call upon the plugins for help
			$plugins = JPluginHelper::getPlugin('dynamic404');
			foreach ($plugins as $plugin)
			{
				$className = 'plg' . $plugin->type . $plugin->name;
				$method = 'onDynamic404Link';
				if (class_exists($className))
				{
					if (YireoHelper::isJoomla25())
					{
						$dispatcher = JDispatcher::getInstance();
					}
					else
					{
						$dispatcher = JEventDispatcher::getInstance();
					}
					$plugin = new $className($dispatcher, (array)$plugin);

					if (method_exists($plugin, $method))
					{
						$result = $plugin->$method($component, $view, $id);
						if (!empty($result))
						{
							$newUrl = $result;
							break;
						}
					}
				}
			}
		}

		// Redirect if needed
		if (!empty($newUrl) && $newUrl != $url)
		{
			$application->redirect($newUrl);
			$application->close();
		}
	}

	/**
	 * Method to be called after the component has been rendered
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function onAfterRender()
	{
		$app = JFactory::getApplication();
		if (method_exists($app, 'getMessageQueue'))
		{
			$messageQueue = $app->getMessageQueue();
			if (!empty($messageQueue))
			{
				foreach ($messageQueue as $message)
				{
					if ($message['type'] != 'error')
					{
						continue;
					}
					if (stristr($message['message'], JText::_('JGLOBAL_CATEGORY_NOT_FOUND')))
					{
						$errorCode = 404;
						JError::raiseError($errorCode, $message['message']);
					}
				}
			}
		}
	}

	/*
	 * Method to add support key to download URL
	 *
	 * @param string &$url The URL to download the package from
	 * @param array &$headers An optional associative array of headers
	 * @return boolean
	 */
	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{
		// Extension
		$componentName = 'com_dynamic404';

		// Are we trying to update our extension?
		if (preg_match('/' . $componentName . '/', $url) == false)
		{
			return true;
		}

		// Fetch the support key
		JLoader::import('joomla.application.component.helper');
		$component = JComponentHelper::getComponent($componentName);
		$support_key = $component->params->get('support_key', '');
		if (empty($support_key))
		{
			return false;
		}

		// Add the support key to the URL
		$separator = strpos($url, '?') !== false ? '&' : '?';
		$url_addition = $separator . 'key=' . $support_key;

		// Check if this key is valid
		$tmpUrl = $url . $url_addition . '&validate=1';
		$http = JHttpFactory::getHttp();
		$response = $http->get($tmpUrl, array());
		if (empty($response))
		{
			return false;
		}

		// Add the key to the update URL
		if ($response->body == '1')
		{
			$url .= $url_addition;

			return false;
		}

		return false;
	}
}
