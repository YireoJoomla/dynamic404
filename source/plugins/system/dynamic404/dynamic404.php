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

/**
 * Plugin class for reusing redirection of the Dynamic404 component
 *
 * @package       Dynamic404
 * @subpackage    plgSystemDynamic404
 */
class PlgSystemDynamic404 extends JPlugin
{
	/**
	 * Instance of JApplication
	 */
	protected $app = null;

	/**
	 * File path to the Dynamic404 library loader
	 *
	 * @var string
	 */
	protected $loaderFile = null;

	/**
	 * Constructor.
	 *
	 * @param   object &$subject The object to observe.
	 * @param   array  $config   An optional associative array of configuration settings.
	 */
	public function __construct(&$subject, $config)
	{
		// Internal variables
		$this->app = JFactory::getApplication();
		$this->loaderFile = JPATH_ADMINISTRATOR . '/components/com_dynamic404/lib/loader.php';

		// Include the parent library
		$this->includeLibrary();

		parent::__construct($subject, $config);

		// Set the error handler for E_ERROR to be the class handleError method.
		if ($this->app->isSite() && $this->hasComponent())
		{
			JError::setErrorHandling(E_ERROR, 'callback', array('PlgSystemDynamic404', 'handleError'));
			set_exception_handler(array('PlgSystemDynamic404', 'handleError'));
		}
	}

	/**
	 * Method to catch Joomla! error-handling
	 *
	 * @param object &$error JError object
	 */
	static public function handleError(&$error)
	{
		if (empty($error) || $error == false)
		{
			$error = JError::getError();
		}

		// Include the 404 Helper-class
		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/helper.php';

		// Instantiate the helper with the argument of how many matches to show
		$helper = new Dynamic404Helper(true, null, $error);
		$app = JFactory::getApplication();

        if (method_exists($error, 'getCode'))
        {
            $errorCode = $error->getCode();
        }
        else
        {
            $errorCode = $error->get('code');    
        }

        // Make sure the error is a 404 and we are not in the administrator.
        if (!$app->isAdmin() and $errorCode == 404)
		{
			// Log the 404 entry
			$helper->log();

			// Get the possible matches
			$helper->getMatches();

			// Get the last segment - nice for searching
			$helper->getLast();

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
			$helper->displayErrorPage();
			$app->close(0);
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
		// Make sure we are not in the administrator.
		if ($this->app->isSite() == false)
		{
			return null;
		}

		// Redirect non-www to www
		$this->redirectToWww();

		// Redirect to the enforced domain
		$this->enforceDomain();

		// Force lowercase
		$this->forceLowerCase();

		// Test for non-existent components
		$this->stopNonexistingComponents();

		// Redirect static rules
		$this->redirectStatic();
	}

	/**
	 * Prevent calls to non-existing components
	 */
	protected function stopNonexistingComponents()
	{
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
	}

	/**
	 * Method to force the current URL to be in lower-case
	 */
	protected function forceLowerCase()
	{
		$force_lowercase = $this->params->get('force_lowercase', 0);

		if ($force_lowercase == 1)
		{
			$uri = JURI::current();
			$lowercase_uri = strtolower($uri);

			if ($uri != $lowercase_uri)
			{
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: ' . $lowercase_uri);
				$this->app->close();
				exit;
			}
		}
	}

	/**
	 * Method to enforce a certain domain upon the current URL
	 */
	protected function enforceDomain()
	{
		$enforce_domain = trim($this->params->get('enforce_domain'));

		if (!empty($enforce_domain))
		{
			$uri = JURI::current();

			if (preg_match('/^(http|https)\:\/\/([^\/]+)(.*)/', $uri, $match))
			{
				$hostname = $match[2];

				if ($hostname != $enforce_domain)
				{
					$newUrl = str_replace($hostname, $enforce_domain, $uri);
					header('HTTP/1.1 301 Moved Permanently');
					header('Location: ' . $newUrl);
					$this->app->close();
					exit;
				}
			}
		}
	}

	/**
	 * Method to redirect the current domain to an alternative with www prefix
	 */
	protected function redirectToWww()
	{
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
					$this->app->close();
					exit;
				}
			}
		}
	}

	/**
	 * Method to redirect based on rules that are marked as static
	 */
	protected function redirectStatic()
	{
		$redirect_static = $this->params->get('redirect_static', 0);

		if ($redirect_static == 0)
		{
			return false;
		}

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(redirect_id)')
			->from($db->quoteName('#__dynamic404_redirects'))
			->where($db->quoteName('static') . '=1');
		$db->setQuery($query);
		$rs = $db->loadResult();

		if ($rs == 0)
		{
			return false;
		}

		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/debug.php';
		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/helper.php';
		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match.php';

		$helper = new Dynamic404Helper(false, null, null, true);
		$helper->getMatches();
		$helper->doRedirect();
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
		// Make sure we are not in the administrator.
		if ($this->app->isSite() == false)
		{
			return null;
		}

		if ($this->hasComponent() == false)
		{
			return;
		}

		// Expand URLs
		$url = JURI::current();
		$params = JComponentHelper::getParams('com_dynamic404');

		if (!empty($params) && $params->get('expand_ids', 1) == 1 && preg_match('/\/([0-9]+)/', $url))
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
		if ($this->hasComponent() == false)
		{
			return;
		}

		$url = JURI::current();
		$component = $this->app->input->get('option');
		$view = $this->app->input->get('view');
		$id = $this->app->input->get('id');
		$newUrl = null;

		// Check for the article view
		if ($component == 'com_content' && $view == 'article')
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match/article.php';

			$matchHelper = new Dynamic404HelperMatchArticle;
			$newUrl = JRoute::_($matchHelper->getArticleLink($id));
			$newUrl = JURI::base() . substr($newUrl, YireoHelper::strlen(JURI::base(true)) + 1);
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

					$plugin = new $className($dispatcher, (array) $plugin);

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
			$this->app->redirect($newUrl);
			$this->app->close();
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
        require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/debug.php';
		$debug = Dynamic404HelperDebug::getInstance();
		$debugMessages = $debug->getMessages();

		if (!empty($debugMessages))
		{
			$body = JResponse::getBody();
			$debugHtml = array();

			foreach ($debugMessages as $debugMessage)
			{
				$debugHtml[] = 'console.log("[dynamic404] ' . htmlentities(trim($debugMessage)) . '")';
			}

			$debugHtml = '<script>' . implode('', $debugHtml) . '</script>';
			$body = str_replace('</body>', $debugHtml . '</body>', $body);
			JResponse::setBody($body);
		}

		$this->handleMessageQueue();
	}

	/**
	 * Method to handle the message queue to see if any stupid errors await there
	 *
	 * @throws Exception
	 */
	protected function handleMessageQueue()
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
		if ($this->hasComponent() == false)
		{
			return true;
		}

		// Extension definitions
		$componentName = 'com_dynamic404';
		$pluginName = 'plg_system_dynamic404';

		// Exit when we trying to update another extension
		if (preg_match('/' . $componentName . '/', $url) == false && preg_match('/' . $pluginName . '/', $url) == false)
		{
			return true;
		}

		// Fetch the support key
		$support_key = $this->getSupportKey($componentName);

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

	/**
	 * Get the support key stored with the component
	 *
	 * @param $componentName
	 *
	 * @return mixed
	 */
	public function getSupportKey($componentName)
	{
		// Fetch the support key
		JLoader::import('joomla.application.component.helper');
		$component = JComponentHelper::getComponent($componentName);
		$support_key = $component->params->get('support_key', '');

		return $support_key;
	}

	/**
	 * Check whether the component has been installed
	 *
	 * @return bool
	 */
	public function hasComponent()
	{
		if (file_exists($this->loaderFile))
		{
			return true;
		}

		return false;
	}

	/**
	 * Check whether the Dynamic404 can be included and if not, try to include
	 */
	public function includeLibrary()
	{
		if ($this->hasComponent())
		{
			require_once $this->loaderFile;
		}
	}
}
