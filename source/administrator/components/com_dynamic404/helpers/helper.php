<?php
/**
 * Joomla! component Dynamic404
 *
 * @package    Dynamic404
 * @author     Yireo <info@yireo.com>
 * @copyright  Copyright 2017 Yireo (https://www.yireo.com/)
 * @license    GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link       https://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Load extra helpers
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/core.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/debug.php';

jimport('yireo.loader');

/**
 * Class Dynamic404Helper
 *
 * @todo: Rename to Dynamic404Handler
 */
class Dynamic404Helper
{
	/**
	 * Constant for a Dynamic404 error-page
	 */
	const ERROR_PAGE_DYNAMIC404 = 0;
	/**
	 * Constant for a default error-page
	 */
	const ERROR_PAGE_DEFAULT = 1;
	/**
	 * Constant for an error-page using a Menu-Item that is fetched through CURL
	 */
	const ERROR_PAGE_MENUITEM_INTERNAL = 2;
	/**
	 * Constant for an error-page using a Menu-Item that is fetched through AJAX
	 */
	const ERROR_PAGE_MENUITEM_JSREDIRECT = 3;

	/**
	 * @var Error|Exception
	 */
	private $error;

	/**
	 * Component parameters
	 *
	 * @var null
	 */
	private $params = null;

	/**
	 * List of possible matches
	 *
	 * @var array
	 */
	private $matches = [];

	/**
	 * List of additional errors
	 *
	 * @var array
	 */
	private $errors = [];

	/**
	 * Only allow static redirects
	 *
	 * @var boolean
	 */
	protected $staticRulesOnly = false;

	/**
	 * Constructor
	 *
	 * @param   bool $init            Initialize the helper
	 * @param   int $max              Maximum amount of entries to fetch
	 * @param   mixed $error          Error object or variable
	 * @param   bool $staticRulesOnly Boolean flag
	 */
	public function __construct($init = true, $max = null, $error = null, $staticRulesOnly = false)
	{
		// Read the component parameters
		$this->params = JComponentHelper::getParams('com_dynamic404');

		if ($this->params->get('debug', 0) === 1)
		{
			ini_set('display_errors', 1);
		}

		$this->setErrorObject($error);

		// Initialize some other variables
		$this->jinput = JFactory::getApplication()->input;

		// Prevent common hacks
		$this->preventHacks();

		// Set the static flag
		$this->staticRulesOnly = $staticRulesOnly;

		// Initialize the redirect-helper
		$this->matchHelper = new Dynamic404HelperMatch(null, $this->staticRulesOnly);

		// Load the language-file
		$language = JFactory::getLanguage();
		$language->load('com_dynamic404', JPATH_SITE, $language->getTag(), true);

		// Run the tasks if available
		if ($init === true)
		{
			$this->init();
		}
	}

	/**
	 * Initialize tasks
	 *
	 * @return void
	 */
	public function init()
	{
		$this->log();
		$this->doRedirect();

		$this->debug('PHP memory-usage', memory_get_usage());

		$this->setHttpStatus();
		$this->displayCustomPage();
		$this->displayErrorPage();
	}

	/**
	 * @param   Dynamic404HelperMatch $matchHelper Match helper
	 *
	 * @return void
	 */
	public function setMatchHelper(Dynamic404HelperMatch $matchHelper)
	{
		$this->matchHelper = $matchHelper;
	}

	/**
	 * Method alias for debugging
	 *
	 * @param   string $msg     Debugging message
	 * @param   mixed $variable Optional variable to dump
	 *
	 * @return void
	 */
	public function debug($msg, $variable = null)
	{
		Dynamic404HelperDebug::debug($msg, $variable);
	}

	/**
	 * Method to determine whether to log or not
	 *
	 * @param   array $matches List of possible matches
	 *
	 * @return boolean
	 */
	protected function allowLog($matches)
	{
		if ($this->params->get('enable_logging', 1) === 0)
		{
			return false;
		}

		if (empty($matches))
		{
			return true;
		}

		if ($this->params->get('log_all', 0) === 1)
		{
			return true;
		}

		if ($this->params->get('enable_redirect', 1))
		{
			return false;
		}

		foreach ($matches as $match)
		{
			if (empty($match->params))
			{
				continue;
			}

			$params = json_decode($match->params);

			if (isset($params->redirect) && $params->redirect === 1)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to log a 404 occurance to the database
	 *
	 * @return boolean
	 */
	public function log()
	{
		$matches = $this->getMatches();

		if ($this->allowLog($matches) === false)
		{
			return false;
		}

		$error        = $this->getErrorObject();
		$errorCode    = $error->getCode();
		$errorMessage = $error->getMessage();

		return Dynamic404HelperCore::log($this->matchHelper->getRequest('uri'), $errorCode, $errorMessage);
	}

	/**
	 * Method to get the current SEF-configuration
	 *
	 * @return null
	 */
	public function getSefEnabled()
	{
		$conf = JFactory::getConfig();

		return $conf->get('sef');
	}

	/**
	 * Method to get the maximum rating for a rule
	 *
	 * @return integer
	 */
	public function getMax()
	{
		return $this->params->get('max_suggestions', 5);
	}

	/**
	 * Method to get the search string
	 *
	 * @return string
	 */
	public function getSearchString()
	{
		$last = $this->getLast();
		$last = str_replace('-', ' ', $last);
		$last = str_replace('_', ' ', $last);
		$last = str_replace(':', ' ', $last);

		return $last;
	}

	/**
	 * Method to get the last segment of the URL
	 *
	 * @return string
	 */
	public function getLast()
	{
		return $this->getLastSegment();
	}

	/**
	 * Method to get the last segment of the URL
	 *
	 * @return string
	 */
	public function getLastSegment()
	{
		$segment         = $this->matchHelper->getRequest('uri_last');
		$stripExtensions = explode(',', $this->params->get('strip_extensions'));

		if (!empty($stripExtensions))
		{
			foreach ($stripExtensions as $stripExtension)
			{
				$stripExtension = preg_replace('/([^a-zA-Z0-9\.\-\_]+)/', '', $stripExtension);
				$segment        = preg_replace('/\.' . $stripExtension . '$/', '', $segment);
			}
		}

		return $segment;
	}

	/**
	 * Method to get the search URL
	 *
	 * @return string
	 */
	public function getSearchLink()
	{
		$itemId = Dynamic404HelperCore::getSearchItemid();

		if ($itemId > 0)
		{
			return JRoute::_('index.php?option=com_search&searchword=' . $this->getLastSegment() . '&Itemid=' . $itemId);
		}

		return JRoute::_('index.php?option=com_search&searchword=' . $this->getLastSegment());
	}

	/**
	 * Method to set the current error object
	 *
	 * @param   null|mixed $error Error object
	 *
	 * @return string
	 */
	public function setErrorObject($error = null)
	{
		if ($error instanceof Error || $error instanceof Exception)
		{
			$this->error = $error;

			return $this->error;
		}

		$error = JError::getError();

		if (!empty($error))
		{
			$this->error = $error;
		}

		if (empty($this->error) || $this->error === false)
		{
			$code        = 404;
			$message     = JText::_('Not found');
			$this->error = new Exception($message, $code);
		}

		return $this->error;
	}

	/**
	 * Method to get a default error-object for error.php
	 *
	 * @param   $error  null|mixed  Error object
	 *
	 * @return string
	 */
	public function getErrorObject()
	{
		return $this->error;
	}

	/**
	 * Method to run all the match-functions and return the resulting matches
	 *
	 * @return array
	 */
	public function getMatches()
	{
		// Check if this is a 500 error
		$errorCode = $this->getErrorCode();

		if ($errorCode === 500 && $this->params->get('redirect_non404', 0) === 0)
		{
			return array();
		}

		// Do not search for matches, if this is not a valid URL
		$url = $this->matchHelper->getRequest('uri');

		if (preg_match('/^(templates|media|images)\//', $url))
		{
			return array();
		}

		// Do not search for matches, with extremely large requests
		$blockLargeRequestsSize = (int) $this->params->get('block_large_requests', 1000);

		if ($blockLargeRequestsSize > 0 && strlen($url) > $blockLargeRequestsSize)
		{
			return array();
		}

		// Search for matches
		if (empty($this->matches))
		{
			$this->matches = $this->matchHelper->getMatches();
		}

		return array_slice($this->matches, 0, $this->getMax());
	}

	/**
	 * Method to get only the direct matches
	 *
	 * @return array
	 */
	public function getDirectMatches()
	{
		if (!is_array($this->matches))
		{
			$this->matches = $this->matchHelper->findRedirectMatches();
		}

		return array_slice($this->matches, 0, $this->getMax());
	}

	/**
	 * Method to get the configured article
	 *
	 * @param   string $error Error code
	 *
	 * @return false|object
	 */
	public function getArticle($error = '404')
	{
		$params = $this->params->toArray();

		if (empty($params['article_id_' . $error]))
		{
			return false;
		}

		$article = $params['article_id_' . $error];

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__content'));
		$query->where($db->quoteName('id') . '=' . (int) $article);

		$db->setQuery($query);
		$row = $db->loadObject();

		if (empty($row))
		{
			return false;
		}

		$row->text = (!empty($row->fulltext)) ? $row->fulltext : $row->introtext;

		return $row;
	}

	/**
	 * Method to check whether a certain URL causes a loop or not
	 *
	 * @param   string $url URL
	 *
	 * @return boolean
	 */
	public function checkNoRedirectLoop($url = null, $checkOffline = true)
	{
		if ($this->params->get('prevent_loops', 1) === 0)
		{
			return true;
		}

		if (!function_exists('curl_init'))
		{
			return true;
		}

		$conf = JFactory::getConfig();

		if ($checkOffline === true && $conf->get('offline') === 1)
		{
			return true;
		}

		if (empty($url))
		{
			return false;
		}

		$redirectLoopChecker = $this->params->get('redirect_loop_checker', 'internal');

		if ($redirectLoopChecker === 'yireo')
		{
			try
			{
				$rt = $this->givesNoRedirectLoopYireoApi($url);
			}
			catch (Exception $e)
			{
				$rt = $this->givesNoRedirectLoopLocal($url);
			}
		}
		else
		{
			$rt = $this->givesNoRedirectLoopLocal($url);
		}

		return $rt;
	}

	/**
	 * @param   string $url URL
	 *
	 * @return boolean
	 */
	private function givesNoRedirectLoopLocal($url)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 0);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_MAXCONNECTS, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 1);

		$defaultUserAgent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
		curl_setopt($ch, CURLOPT_USERAGENT, $defaultUserAgent);

		$curlHead = curl_exec($ch);
		$curlInfo = curl_getinfo($ch);
		curl_close($ch);

		if (empty($curlHead))
		{
			$this->errors[] = 'Endless redirect detected';

			return false;
		}

		if (isset($curlInfo['redirect_url']) && !empty($curlInfo['redirect_url']))
		{
			$this->errors[] = 'Double redirect detected';

			return false;
		}

		return true;
	}

	/**
	 * @param   string $url URL
	 *
	 * @return boolean
	 * @throws RuntimeException
	 */
	private function givesNoRedirectLoopYireoApi($url)
	{
		$userAgent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$key       = trim($this->params->get('support_key'));

		if (empty($key))
		{
			throw new RuntimeException('Invalid key');
		}

		$url = urlencode(base64_encode($url));
		$url = 'https://api.yireo.com/redirect_check.php?key=' . $key . '&url=' . $url;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_MAXCONNECTS, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

		$content = curl_exec($ch);
		curl_close($ch);

		if (!empty($content))
		{
			$this->errors[] = $content;

			return false;
		}

		return true;
	}

	/**
	 * Method to check whether we are able to redirect or not
	 *
	 * @return boolean
	 */
	public function allowRedirect($errorCode, $match)
	{
		// Allow when the static flag is set
		if ($this->staticRulesOnly)
		{
			return true;
		}

		// Do not redirect, if the redirect flag is off
		$redirect = $this->jinput->getInt('noredirect');

		if ($redirect === 1)
		{
			return false;
		}

		// Do not redirect, if this is not a valid URL
		$currentUrl = $this->matchHelper->getRequest('uri');

		if (preg_match('/^(templates|media|images)\//', $currentUrl))
		{
			$this->debug('No redirect for templates|media|images');

			return false;
		}

		// Do not redirect, if the HTTP-status is not a 4xx error
		if ($this->params->get('redirect_non404', 0) === 0 && preg_match('/^4/', $errorCode) === false)
		{
			$this->debug('No redirect for non-404 errors');

			return false;
		}

		// Fetch the global direct values
		$globalRedirect  = (bool) $this->params->get('enable_redirect', 1);
		$redirectMinimum = (int) $this->params->get('redirect_minimum', 99);

		// Check the rating boundary
		if ($globalRedirect === false && $match->rating >= $redirectMinimum)
		{
			return $match;
		}

		// Determine the redirection default for this match
		if (empty($match->params))
		{
			$match->params = null;
			$matchRedirect = (int) $globalRedirect;
		}
		else
		{
			/** @var \Joomla\Registry\Registry $params */
			$params        = YireoHelper::toRegistry($match->params);
			$matchRedirect = (int) $params->get('redirect', $globalRedirect);
		}

		// Set global redirect value
		if ($matchRedirect === 2)
		{
			$matchRedirect = $globalRedirect;
		}

		// Check match redirect
		if ($matchRedirect === 0)
		{
			return false;
		}

		return $match;
	}

	/**
	 * Method to redirect to a specific match
	 *
	 * @return boolean
	 */
	public function doRedirect()
	{
		$application = JFactory::getApplication();

		// Set the error-code
		if (!empty($error))
		{
			$errorCode = (int) $this->getErrorCode($error);
		}
		else
		{
			$errorCode = '404';
		}

		// Check for matches
		$matches = $this->getMatches();

		if (empty($matches))
		{
			return false;
		}

		// Take the first match
		$match = $matches[0];

		if (empty($match))
		{
			return false;
		}

		$allowRedirect = $this->allowRedirect($errorCode, $match);

		if ($allowRedirect === false)
		{
			return false;
		}

		// Check for the URL
		$url = $match->url;

		if (empty($url))
		{
			return false;
		}

		// Get the fully qualified URL
		if (!preg_match('/^(http|https):\/\//', $url))
		{
			$uri = JUri::getInstance();
			$url = $uri->toString(array('scheme', 'host', 'port')) . '/' . preg_replace('/^\//', '', $url);
		}

		// Perform a simple HEAD-test to check for redirects or endless redirects
		$rt = $this->checkNoRedirectLoop($url);

		if ($rt === false)
		{
			header('Status: 508 Loop Detected');

			return false;
		}

		$httpStatus = $this->getHttpStatusByMatch($match);

		// Perform the actual redirect
		header('HTTP/1.1 ' . Dynamic404HelperCore::getHttpStatusDescription($httpStatus));
		header('Location: ' . $url);
		header('Connection: close');
		$application->close();

		return true;
	}

	/**
	 * Method to get the Menu-Item error-page URL
	 *
	 * @param   string $errorCode Error code
	 *
	 * @return boolean
	 */
	public function getMenuItemUrl($errorCode)
	{
		// Check the parameters
		$params  = $this->params->toArray();
		$itemId  = null;
		$article = null;

		foreach ($params as $name => $value)
		{
			if ($value > 0 && preg_match('/^menuitem_id_([0-9]+)/', $name, $match))
			{
				if ((int) $errorCode === (int) $match[1])
				{
					$itemId = (int) $value;
				}
			}

			if ($value > 0 && preg_match('/^article_id_([0-9]+)/', $name, $match))
			{
				if ((int) $errorCode === (int) $match[1])
				{
					$article = (int) $value;
				}
			}
		}

		// Don't continue if no item is there
		if ($itemId > 0 === false && $article > 0 === false)
		{
			return false;
		}

		// Check whether the current page is already the Dynamic404-page
		if ($this->jinput->getCmd('option') === 'com_dynamic404')
		{
			return false;
		}

		// Fetch the system variables
		$app = JFactory::getApplication();

		// Determine the URL by Menu-Item
		if ($itemId > 0)
		{
			// Load the configured Menu-Item
			$menu = $app->getMenu();
			$item = $menu->getItem($itemId);

			if (empty($item) || !is_object($item) || !isset($item->query['option']))
			{
				return false;
			}

			// Construct the URL
			if (isset($item->component) && $item->component === 'com_dynamic404')
			{
				$currentUrl = JURI::current();
				$currentUrl = str_replace('?noredirect=1', '', $currentUrl);
				$url        = JRoute::_('index.php?option=com_dynamic404&Itemid=' . $itemId . '&uri=' . base64_encode($currentUrl));
			}
			else
			{
				$url = JRoute::_('index.php?Itemid=' . $itemId);
			}
		}
		else
		{
			// Load the configured article
			$row = $this->getArticle($errorCode);

			if (empty($row))
			{
				return false;
			}

			require_once JPATH_SITE . '/components/com_content/helpers/route.php';
			$url = ContentHelperRoute::getArticleRoute($article . ':' . $row->alias, $row->catid);
			$url = JRoute::_($url);
		}

		// Complete the URL
		$url = JURI::base() . substr($url, strlen(JURI::base(true)) + 1);

		// Detect the language-SEF
		$currentLanguage = JFactory::getLanguage();
		$languages       = JLanguageHelper::getLanguages('sef');

		foreach ($languages as $language)
		{
			if ($language->lang_code === $currentLanguage->getTag())
			{
				$languageSef = $language->sef;
			}
		}

		// Add the language to the URL
		if (!empty($languageSef))
		{
			$url = (strstr($url, '?')) ? $url . '&lang=' . $languageSef : $url . '?lang=' . $languageSef;
		}

		return $url;
	}

	/**
	 * Helper method to determine whether to use JS to redirect to the Menu-Item page
	 *
	 * @return boolean
	 */
	public function isMenuItemJsRedirect()
	{
		$componentParams = JComponentHelper::getParams('com_dynamic404');

		if ($componentParams->get('error_page', self::ERROR_PAGE_DYNAMIC404) == self::ERROR_PAGE_MENUITEM_JSREDIRECT)
		{
			return true;
		}

		return false;
	}

	/**
	 * Method to display a custom page based on an existing Menu-Item
	 *
	 * @return boolean
	 */
	public function displayCustomPage()
	{
		// Check for the error
		if (empty($this->error))
		{
			$this->error = $this->getErrorObject();
		}

		// Check the parameters
		$componentParams = JComponentHelper::getParams('com_dynamic404');
		$app             = JFactory::getApplication();
		$cache           = JFactory::getCache();

		if (!in_array($componentParams->get('error_page', self::ERROR_PAGE_DYNAMIC404), array(self::ERROR_PAGE_MENUITEM_INTERNAL)))
		{
			return false;
		}

		$url = $this->getMenuItemUrl($this->getErrorCode($this->error));

		if (empty($url))
		{
			return false;
		}

		$this->debug('Internal URL', $url);

		// Fetch the content
		if ($this->params->get('caching', 1) === 1)
		{
			$cache->setCaching(1);
			$contents = $cache->call(array('Dynamic404Helper', 'fetchPage'), $url, null, true);
		}
		else
		{
			$contents = self::fetchPage($url, null, true);
		}

		// Output the content
		header('Content-Type: text/html; charset=utf-8');
		print $contents;
		$app->close();

		return true;
	}

	/**
	 * Method to fetch a specific page
	 *
	 * @param   string $url          URL
	 * @param   string $useragent    Browser user agent
	 * @param   bool $allowRedirects Allow for redirects or not
	 * @param   int $redirect        Number of redirects
	 *
	 * @return string
	 * @throws Exception
	 */
	static public function fetchPage($url, $useragent = null, $allowRedirects = false, $redirect = 0)
	{
		if (function_exists('curl_init') === false)
		{
			throw new Exception('CURL not installed');
		}

		if ($redirect > 10)
		{
			throw new Exception('CURL redirected too many times');
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_MAXCONNECTS, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 1);

		$defaultUserAgent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$userAgent = (!empty($useragent)) ? $useragent : $defaultUserAgent;
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

		$response = curl_exec($ch);

		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header     = substr($response, 0, $headerSize);
		$body       = substr($response, $headerSize);

		$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($allowRedirects && ($httpStatusCode === 301 || $httpStatusCode === 302))
		{
			preg_match("/(Location:|URI:)[^(\n)]*/", $header, $matches);
			$url        = trim(str_replace($matches[1], "", $matches[0]));
			$url_parsed = parse_url($url);

			if (!empty($url_parsed))
			{
				$redirect++;

				return self::fetchPage($url, $useragent, $allowRedirects, $redirect);
			}
		}

		if ($body === false)
		{
			throw new Exception('CURL error: ' . curl_error($ch) . ' = ' . var_export(curl_getinfo($ch), true));
		}

		return $body;
	}

	/**
	 * @param $match
	 *
	 * @return mixed
	 */
	public function getHttpStatusByMatch($match)
	{
		// Set the HTTP Redirect-status
		if (isset($match->http_status) && $match->http_status > 0)
		{
			$httpStatus = $match->http_status;

			return $httpStatus;
		}
		else
		{
			$params     = JComponentHelper::getParams('com_dynamic404');
			$httpStatus = $params->get('http_status', 301);

			return $httpStatus;
		}
	}

	/**
	 * Method to re-initialize the Joomla! bootstrap and call upon the component again
	 *
	 * @param   int $itemId Menu item ID
	 *
	 * @return null
	 */
	protected function showComponentPage($itemId)
	{
		$app = JFactory::getApplication();

		// Load the configured Menu-Item
		$menu = $app->getMenu();
		$item = $menu->getItem($itemId);

		if (empty($item) || !is_object($item) || !isset($item->query['option']))
		{
			return false;
		}

		// Set the component-variable
		$component = $item->query['option'];

		// Reload the component
		$lang = JFactory::getLanguage();
		$lang->load($component, JPATH_SITE);

		// Loop through the items query-values and add them to the request
		foreach ($item->query as $name => $value)
		{
			$this->jinput->set($name, $value);
		}

		// Call upon the components entry-file
		$entry = preg_replace('/^com_/', '', $component);
		include_once JPATH_SITE . '/components/' . $component . '/' . $entry;

		// So now Joomla! is corrupt, so stop right away
		$app->close();

		return null;
	}

	/**
	 * Method to handle the default error page
	 *
	 * @return null
	 * @throws Exception
	 */
	public function displayErrorPage()
	{
		// System variables
		$application = JFactory::getApplication();

		// Add some common variables to the error-page
		$this->error    = $this->getErrorObject();
		$this->baseurl  = JURI::base();
		$this->template = $application->getTemplate();
		$this->debug    = 0;

		if (empty($this->title))
		{
			$this->title = $this->error->getMessage();
		}

		// Check the parameters
		$componentParams = JComponentHelper::getParams('com_dynamic404');
		$language        = JFactory::getLanguage();
		$languageTag     = $language->getTag();

		if ($componentParams->get('error_page', self::ERROR_PAGE_DYNAMIC404) === self::ERROR_PAGE_DEFAULT)
		{
			$file = JPATH_SITE . '/templates/' . $application->getTemplate() . '/error_' . $languageTag . '.php';

			if (file_exists($file) === false)
			{
				$file = JPATH_SITE . '/templates/' . $application->getTemplate() . '/error.php';
			}
		}

		if (empty($file) || file_exists($file) === false)
		{
			$file = JPATH_ADMINISTRATOR . '/components/com_dynamic404/libraries/error.php';
		}

		if (file_exists($file) === false)
		{
			throw new Exception('Error file not found');
		}

		JResponse::allowCache(false);
		require_once $file;

		$application->close(0);

		return null;
	}

	/**
	 * Method to handle the default error page
	 *
	 * @return null
	 */
	public function setHttpStatus()
	{
		$error     = $this->getErrorObject();
		$errorCode = $this->getErrorCode($error);
		$document  = JFactory::getDocument();
		$document->setTitle(JText::_('Error') . ': ' . $errorCode);

		$httpStatusText = $this->getHttpStatusText($errorCode);
		header('HTTP/1.1 ' . $httpStatusText);

		return;
	}

	/**
	 * Return a HTTP status text per HTTP status code
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public function getHttpStatusText($code)
	{
		switch ($code)
		{
			case '400':
				return '400 Bad Request';
			case '401':
				return '401 Unauthorized';
			case '402':
				return '402 Payment Required';
			case '403':
				return '403 Forbidden';
			case '404':
				return '404 Not Found';
			case '405':
				return '405 Method Not Allowed';
			case '406':
				return '406 Not Acceptable';
			case '408':
				return '408 Request Timeout';
			case '409':
				return '409 Conflict';
			case '410':
				return '410 Gone';
			case '500':
				return '500 Internal Server Error';
			case '501':
				return '501 Not Implemented';
			case '502':
				return '502 Bad Gateway';
			case '503':
				return '503 Service Unavailable';
		}

		return '404 Not Found';
	}

	/*
	 * Method to generate a new short URL
	 *
	 * @param   string  $url      URL
	 * @param   bool    $fullurl  Flag to indicate whether this is a full URL or not
	 *
	 * @return string
	 */
	public function generateShortUrl($url = null, $fullurl = true)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('match'))
			->from($db->quoteName('#__dynamic404_redirects'))
			->where($db->quoteName('url') . '=' . $db->quote($url));

		$db->setQuery($query);
		$match = $db->loadResult();

		if (empty($match))
		{
			$match = self::generateRandomString();

			$columns = array('match', 'url', 'http_status', 'type', 'published',);
			$values  = array($db->quote($match), $db->quote($url), 0, $db->quote('full_url'), 1,);

			$query = $db->getQuery(true);
			$query->insert($db->quoteName('#__dynamic404_redirects'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));
			$db->setQuery($query);
			$db->execute();
		}

		if ($fullurl)
		{
			return JURI::root() . $match;
		}
		else
		{
			return $match;
		}
	}

	/*
	 * Method to generate a random string
	 *
	 * @return   string $string  Random string
	 */
	public function generateRandomString()
	{
		$length     = 8;
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$string     = '';

		for ($p = 0; $p < $length; $p++)
		{
			$string .= $characters[mt_rand(0, strlen($characters))];
		}

		return $string;
	}

	/**
	 * Method to block certain hack attempts
	 *
	 * @return bool|null
	 * @throws Exception
	 */
	public function preventHacks()
	{
		$url   = JURI::current();
		$block = false;

		// Block certain hack strings
		$hacksFile = __DIR__ . '/hacks.php';

		if (file_exists($hacksFile))
		{
			include_once $hacksFile;
		}

		if (!empty($hacks))
		{
			foreach ($hacks as $hack)
			{
				if (stristr($url, $hack))
				{
					$message = $hack;
					$block   = true;
					break;
				}
			}
		}

		// Block access to non-existing components
		if ($this->params->get('block_nonexisting_components', 1) === 1)
		{
			$cmd     = $this->jinput->getCmd('option');
			$message = $cmd;

			if (!empty($cmd) && !is_dir(JPATH_SITE . '/components/' . $cmd) && is_dir(JPATH_ADMINISTRATOR . '/components/' . $cmd))
			{
				$block = true;
			}
		}

		if ($block === false)
		{
			return true;
		}

		header('HTTP/1.1 403 Forbidden');
		throw new Exception('Access Forbidden: ' . $message);
	}

	/**
	 * Method to return the errors if found
	 *
	 * @return null
	 */
	public function getAdditionalErrors()
	{
		return $this->errors;
	}

	/**
	 * Method to return the correct HTTP status code based on the current error
	 *
	 * @param mixed $error
	 *
	 * @return int
	 */
	public function getErrorCode($error = null)
	{
		if (empty($error))
		{
			$error = $this->getErrorObject();
		}

		if (is_numeric($error))
		{
			return $error;
		}

		if (is_object($error) && method_exists($error, 'getCode'))
		{
			$errorCode = $error->getCode();

			if (!empty($errorCode))
			{
				return $errorCode;
			}
		}

		if (is_object($error) && method_exists($error, 'get'))
		{
			return $error->get('code');
		}

		if (is_object($error) && $error instanceof Exception)
		{
			if (stristr($error->getMessage(), '404'))
			{
				return 404;
			}

			return 500;
		}

		if (is_object($error) && class_exists('Error') && $error instanceof Error)
		{
			if (stristr($error->getMessage(), '404'))
			{
				return 404;
			}

			return 500;
		}

		if (is_object($error) && isset($error->code))
		{
			return $error->code;
		}

		return 404;
	}
}
