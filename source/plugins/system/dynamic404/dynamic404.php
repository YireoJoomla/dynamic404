<?php
/**
 * Joomla! component Dynamic404
 *
 * @package     Dynamic404
 * @author      Yireo <info@yireo.com>
 * @copyright   Copyright 2017 Yireo (https://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        https://www.yireo.com/
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
	 * @var JApplicationCms
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param   object $subject  The object to observe.
	 * @param   array  $config   An optional associative array of configuration settings.
	 */
	public function __construct(&$subject, $config)
	{
		// Internal variables
		$this->app = JFactory::getApplication();

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
	 * @param   object  $error JError object
	 *
	 * @return void
	 */
	static public function handleError($error)
	{
		if (empty($error) || $error === false)
		{
			$error = JError::getError();
		}

		// Include the 404 Helper-class
		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/helper.php';

		// Instantiate the helper with the argument of how many matches to show
		$helper = new Dynamic404Helper(true, null, $error);
		$app    = JFactory::getApplication();

		if (method_exists($error, 'getCode'))
		{
			$errorCode = $error->getCode();
		}
		else
		{
			$errorCode = $error->get('code');
		}

		// Make sure the error is a 404 and we are not in the administrator.
		if (!$app->isAdmin() && $errorCode === 404)
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

		if ($params->get('error_page', 0) === 1)
		{
			JErrorPage::render($error);

			return;
		}

		$helper->displayErrorPage();
		$app->close(0);
	}

	/**
	 * Method to be called after the Joomla! Application has been initialized
	 *
	 * @return void
	 */
	public function onAfterInitialise()
	{
		// Make sure we are not in the administrator.
		if ($this->app->isSite() === false)
		{
			return;
		}

		// Find matches in the database
		$this->findMatches();

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
	 * @return void
	 */
	protected function findMatches()
	{
		$option = $this->app->input->getCmd('option');
		$view   = $this->app->input->getCmd('view');

		if ($option !== 'com_dynamic404' || $view !== 'matches')
		{
			return;
		}

		ini_set('display_errors', 0);

		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/core.php';
		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match.php';
		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/debug.php';

		$url = $this->app->input->getString('url', null, 'raw');
		$url = base64_decode($url);

		if (empty($url))
		{
			echo json_encode(array());
			exit;
		}

		$matchHelper = new Dynamic404HelperMatch($url);
		$matches     = $matchHelper->getMatches();

		echo json_encode($matches);
		exit;
	}

	/**
	 * Prevent calls to non-existing components
	 *
	 * @return void
	 */
	protected function stopNonexistingComponents()
	{
		$uri = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : null;

		if (!preg_match('/\/component\/([a-zA-Z0-9\.\-\_]+)\//', $uri, $componentMatch))
		{
			return;
		}

		$component = preg_replace('/^com_/', '', $componentMatch[1]);

		if (is_dir(JPATH_SITE . '/components/com_' . $component))
		{
			return;
		}

		// Fetch the last segment of this URL
		$segments    = explode('/', $uri);
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
		$url = JUri::base() . 'component/content/' . $lastSegment;
		header('Location: ' . $url);
		exit;
	}

	/**
	 * Method to force the current URL to be in lower-case
	 *
	 * @return void
	 */
	protected function forceLowerCase()
	{
		$forceLowercase = $this->params->get('force_lowercase', 0);

		if ($forceLowercase !== 1)
		{
			return;
		}

		$uri          = JUri::current();
		$lowercaseUri = strtolower($uri);

		if ($uri === $lowercaseUri)
		{
			return;
		}

		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $lowercaseUri);
		$this->app->close();
		exit;
	}

	/**
	 * Method to enforce a certain domain upon the current URL
	 *
	 * @return void
	 */
	protected function enforceDomain()
	{
		$enforceDomain = trim($this->params->get('enforce_domain'));

		if (empty($enforceDomain))
		{
			return;
		}

		$uri = JUri::current();

		if (!preg_match('/^(http|https)\:\/\/([^\/]+)(.*)/', $uri, $match))
		{
			return;
		}

		$hostname = $match[2];

		if ($hostname === $enforceDomain)
		{
			return;
		}

		$newUrl = str_replace($hostname, $enforceDomain, $uri);
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $newUrl);
		$this->app->close();
		exit;
	}

	/**
	 * Method to redirect the current domain to an alternative with www prefix
	 *
	 * @return void
	 */
	protected function redirectToWww()
	{
		$redirectWww = $this->params->get('redirect_www', 0);

		if ($redirectWww !== 1)
		{
			return;
		}

		$uri = JUri::current();

		if (!preg_match('/^(http|https)\:\/\/([^\/]+)(.*)/', $uri, $match))
		{
			return;
		}

		$hostname = $match[2];

		if (preg_match('/^www\./', $hostname))
		{
			return;
		}

		$newUrl = $match[1] . '://www.' . $hostname . $match[3];
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . $newUrl);
		$this->app->close();
		exit;
	}

	/**
	 * Method to redirect based on rules that are marked as static
	 *
	 * @return void
	 */
	protected function redirectStatic()
	{
		$redirectStatic = $this->params->get('redirect_static', 0);

		if ($redirectStatic === 0)
		{
			return;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(redirect_id)')
			->from($db->quoteName('#__dynamic404_redirects'))
			->where($db->quoteName('static') . '=1');
		$db->setQuery($query);
		$rs = $db->loadResult();

		if ($rs === 0)
		{
			return;
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
	 * @return void
	 */
	public function onAfterRoute()
	{
		// Make sure we are not in the administrator.
		if ($this->app->isSite() === false)
		{
			return;
		}

		if ($this->hasComponent() === false)
		{
			return;
		}

		// Expand URLs
		$url    = JUri::current();
		$params = JComponentHelper::getParams('com_dynamic404');

		if (!empty($params) && $params->get('expand_ids', 1) === 1 && preg_match('/\/([0-9]+)/', $url))
		{
			$this->doExpandUrl();
		}
	}

	/**
	 * Method to expand the URL
	 *
	 * @return void
	 */
	public function doExpandUrl()
	{
		if ($this->hasComponent() === false)
		{
			return;
		}

		$url       = JUri::current();
		$component = $this->app->input->get('option');
		$view      = $this->app->input->get('view');
		$id        = $this->app->input->get('id');
		$newUrl    = null;

		// Check for the article view
		if ($component === 'com_content' && $view === 'article')
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match/article.php';

			$matchHelper = new Dynamic404HelperMatchArticle;
			$newUrl      = JRoute::_($matchHelper->getArticleLink($id));
			$newUrl      = JUri::base() . substr($newUrl, YireoHelper::strlen(JUri::base(true)) + 1);
		}
		else
		{
			// Call upon the plugins for help
			$plugins = JPluginHelper::getPlugin('dynamic404');

			foreach ($plugins as $plugin)
			{
				$className = 'plg' . $plugin->type . $plugin->name;
				$method    = 'onDynamic404Link';

				if (!class_exists($className))
				{
					continue;
				}

				$dispatcher = JEventDispatcher::getInstance();
				$plugin     = new $className($dispatcher, (array) $plugin);

				if (!method_exists($plugin, $method))
				{
					continue;
				}

				$result = $plugin->$method($component, $view, $id);

				if (!empty($result))
				{
					$newUrl = $result;
					break;
				}
			}
		}

		$newUrl = preg_replace('/\?(.*)/', '', $newUrl);
		$url    = preg_replace('/\?(.*)/', '', $url);

		// Redirect if needed
		if (!empty($newUrl) && $newUrl !== $url)
		{
			$this->app->redirect($newUrl);
			$this->app->close();
		}
	}

	/**
	 * Method to be called after the component has been rendered
	 *
	 * @return void
	 */
	public function onAfterRender()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/debug.php';
		$debug         = Dynamic404HelperDebug::getInstance();
		$debugMessages = $debug->getMessages();

		if (!empty($debugMessages))
		{
			$body      = $this->app->getBody();
			$debugHtml = array();

			foreach ($debugMessages as $debugMessage)
			{
				$debugHtml[] = 'console.log("[dynamic404] ' . htmlentities(trim($debugMessage)) . '")';
			}

			$debugHtml = '<script>' . implode('', $debugHtml) . '</script>';
			$body      = str_replace('</body>', $debugHtml . '</body>', $body);
			$this->app->setBody($body);
		}

		$this->handleMessageQueue();
		$this->handleTitle();
	}

	/**
	 * Method to handle the message queue to see if any stupid errors await there
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function handleTitle()
	{
		$title = $this->getDocument()
			->getTitle();

		if (strstr($title, JText::_('PRODUCT_NOT_FOUND')))
		{
			if (!preg_match('/^404/', $title))
			{
				$title = '404 - ' . $title;
			}

			throw new Exception($title);
		}
	}

	/**
	 * Method to handle the message queue to see if any stupid errors await there
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function handleMessageQueue()
	{
		if (!method_exists($this->app, 'getMessageQueue'))
		{
			return;
		}

		$messageQueue = $this->app->getMessageQueue();

		if (empty($messageQueue))
		{
			return;
		}

		foreach ($messageQueue as $message)
		{
			if ($message['type'] !== 'error')
			{
				continue;
			}

			if (strstr($message['message'], JText::_('JGLOBAL_CATEGORY_NOT_FOUND')))
			{
				if (!preg_match('/^404/', $message['message']))
				{
					$message['message'] = '404 - ' . $message['message'];
				}

				throw new Exception($message['message']);
			}
		}
	}

	/**
	 * Method to add support key to download URL
	 *
	 * @param   string  $url     The URL to download the package from
	 * @param   array   $headers An optional associative array of headers
	 *
	 * @return boolean
	 */
	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{
		if ($this->hasComponent() === false)
		{
			return true;
		}

		// Extension definitions
		$componentName = 'com_dynamic404';
		$pluginName    = 'plg_system_dynamic404';

		// Exit when we trying to update another extension
		if (preg_match('/' . $componentName . '/', $url) == false && preg_match('/' . $pluginName . '/', $url) == false)
		{
			return true;
		}

		// Fetch the support key
		$supportKey = $this->getSupportKey($componentName);

		if (empty($supportKey))
		{
			return false;
		}

		// Add the support key to the URL
		$separator    = strpos($url, '?') !== false ? '&' : '?';
		$urlAddition = $separator . 'key=' . $supportKey;

		// Check if this key is valid
		$tmpUrl   = $url . $urlAddition . '&validate=1';
		$http     = JHttpFactory::getHttp();
		$response = $http->get($tmpUrl, array());

		if (empty($response))
		{
			return false;
		}

		// Add the key to the update URL
		if ($response->body === '1')
		{
			$url .= $urlAddition;

			return false;
		}

		return false;
	}

	/**
	 * Get the support key stored with the component
	 *
	 * @param   string  $componentName Component name
	 *
	 * @return mixed
	 */
	public function getSupportKey($componentName)
	{
		// Fetch the support key
		JLoader::import('joomla.application.component.helper');
		$component   = JComponentHelper::getComponent($componentName);

		return $component->params->get('support_key', '');
	}

	/**
	 * Check whether the component has been installed
	 *
	 * @return boolean
	 */
	public function hasComponent()
	{
		if (file_exists(JPATH_ADMINISTRATOR . '/components/com_dynamic404/dynamic404.php'))
		{
			return true;
		}

		return false;
	}

	/**
	 * Check whether the Dynamic404 can be included and if not, try to include
	 *
	 * @return boolean
	 */
	protected function includeLibrary()
	{
		if ($this->hasComponent())
		{
			jimport('yireo.loader');

			return true;
		}

		return false;
	}

	/**
	 * @return JDocument
	 */
	protected function getDocument()
	{
		return JFactory::getDocument();
	}
}
