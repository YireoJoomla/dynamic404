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

// Load extra helpers
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/core.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/lib/loader.php';

class Dynamic404Helper
{
	/*
	 * Component parameters
	 */
	private $params = null;

	/*
	 * List of possible matches
	 */
	private $matches = null;

	/*
	 * List of additional errors
	 */
	private $errors = null;

	/**
	 * Constructor
	 *
	 * @param   bool  $init  Initialize the helper
	 * @param   int   $max   Maximum amount of entries to fetch
	 */
	public function __construct($init = true, $max = null)
	{
		// Read the component parameters
		$this->params = JComponentHelper::getParams('com_dynamic404');

		if ($this->params->get('debug', 0) == 1)
		{
			ini_set('display_errors', 1);
		}

		// Initialize some other variables
		$this->jinput = JFactory::getApplication()->input;

		// Prevent common hacks
		$this->preventHacks();

		// Initialize the redirect-helper
		$this->matchHelper = new Dynamic404HelperMatch;

		// Load the language-file
		$language = JFactory::getLanguage();
		$language->load('com_dynamic404', JPATH_SITE, JFactory::getLanguage()->getTag(), true);

		// Run the tasks if available
		if ($init == true)
		{
			$this->log();
			$this->doRedirect();

			$this->debug('PHP memory-usage', memory_get_usage());

			$this->setHttpStatus();
			$this->displayCustomPage();
			$this->displayErrorPage();
		}
	}

    public function debug($msg, $variable = null)
    {
		if ($this->params->get('debug', 0) == 0)
        {
            return;
        }

        if(!empty($variable))
        {
            $msg .= ' = '.var_export($variable, true);
        }

        $msg .= "\n";
        echo $msg.'<br/>';
    }

	/**
	 * Method to log a 404 occurance to the database
	 *
	 * @return bool
	 */
	public function log()
	{
		if ($this->params->get('enable_logging', 1) == 0)
		{
			return false;
		}

		$matches = $this->getMatches();

		if ($this->params->get('log_all', 0) == 0 && $this->params->get('enable_redirect', 1) && !empty($matches))
		{
			return false;
		}

		return Dynamic404HelperCore::log($this->matchHelper->getRequest('uri'));
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
	 * @return int
	 */
	public function getMax()
	{
		return $this->params->get('max_suggestions', 5);
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
		$segment = $this->matchHelper->getRequest('uri_last');
		$strip_extensions = explode(',', $this->params->get('strip_extensions'));
		if (!empty($strip_extensions))
		{
			foreach ($strip_extensions as $strip_extension)
			{
				$strip_extension = preg_replace('/([^a-zA-Z0-9\.\-\_]+)/', '', $strip_extension);
				$segment = preg_replace('/\.' . $strip_extension . '$/', '', $segment);
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
		$Itemid = Dynamic404HelperCore::getSearchItemid();
		if ($Itemid > 0)
		{
			return JRoute::_('index.php?option=com_search&searchword=' . $this->getLastSegment() . '&Itemid=' . $Itemid);
		}
		else
		{
			return JRoute::_('index.php?option=com_search&searchword=' . $this->getLastSegment());
		}
	}

	/**
	 * Method to get a default error-object for error.php
	 *
	 * @param   $error  null|mixed  Error object
	 *
	 * @return string
	 */
	public function getErrorObject($error = null)
	{
		if (empty($error) || $error == false)
        {
            $error = JError::getError();
        }

		if (empty($error) || $error == false)
        {
            $error = (object) null;
            $error->code = 404;
            $error->message = JText::_('Not found');
        }

		return $error;
	}

	/**
	 * Method to run all the match-functions and return the resulting matches
	 *
	 * @return array
	 */
	public function getMatches()
	{
		// Do not search for matches, if this is not a valid URL
		$url = $this->matchHelper->getRequest('uri');

		if (preg_match('/^(templates|media|images)\//', $url))
		{
			return array();
		}

		// Do not search for matches, with extremely large requests
		$block_large_requests_size = (int) $this->params->get('block_large_requests', 1000);

		if ($block_large_requests_size > 0 && strlen($url) > $block_large_requests_size)
		{
			return array();
		}

		// Search for matches
		if (!is_array($this->matches))
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
	 * @return object
	 */
	public function getArticle($error = '404')
	{
		$params = $this->params->toArray();
		if (empty($params['article_id_' . $error]))
		{
			return false;
		}

		$article = $params['article_id_' . $error];

		$db = JFactory::getDBO();
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
	 * @param   string  $url  URL
	 *
	 * @return bool
	 */
	public function checkNoRedirectLoop($url = null)
	{
		$conf = JFactory::getConfig();
        if($conf->get('offline') == 1)
        {
            return true;
        }

		if (empty($url))
		{
			return false;
		}

		$user_agent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : null;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 0);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_MAXCONNECTS, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

		$curl_head = curl_exec($ch);
		$curl_info = curl_getinfo($ch);
		$curl_error = curl_error($ch);
		curl_close($ch);

		if (empty($curl_head))
		{
			$this->errors[] = JText::_('COM_DYNAMIC404_ADDITIONAL_ERROR_ENDLESS_REDIRECT') . ': ' . $curl_error;

			return false;

		}
		elseif (isset($curl_info['redirect_url']) && !empty($curl_info['redirect_url']))
		{
			$this->errors[] = JText::_('COM_DYNAMIC404_ADDITIONAL_ERROR_DOUBLE_REDIRECT') . ': ' . $curl_error;

			return false;
		}

		return true;
	}

	/**
	 * Method to redirect to a specific match
	 *
	 * @return bool
	 */
	public function doRedirect()
	{
		$application = JFactory::getApplication();

		// Do not redirect, if the redirect flag is off
		$redirect = $this->jinput->getInt('noredirect');

		if ($redirect == 1)
		{
			return false;
		}

		// Do not redirect, if this is not a valid URL
		$url = $this->matchHelper->getRequest('uri');

		if (preg_match('/^(templates|media|images)\//', $url))
		{
            $this->debug('No redirect for templates|media|images');
			return false;
		}

		// Do not redirect, if the HTTP-status is not a 4xx error
		if (empty($error))
		{
			$errorCode = '404';
		}
		else
		{
			$errorCode = (int) $this->getErrorCode($error);

			if ($this->params->get('redirect_non404', 0) == 0 && preg_match('/^4/', $errorCode) == false)
			{
                $this->debug('No redirect for non-404 errors');
				return false;
			}
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

		// Do not redirect, if configured not to
		if (empty($match->params))
		{
			$match->params = null;
            $matchRedirect = $this->params->get('enable_redirect', 1);
		}
        else
        {
		    $params = YireoHelper::toRegistry($match->params);
            $matchRedirect = $params->get('redirect', $this->params->get('enable_redirect', 1));
        }

		if (isset($match->type) && $match->type == 'component' && $matchRedirect == 0)
		{
			return false;
		}
		elseif ($matchRedirect = 2 && $this->params->get('enable_redirect', 1) == 0)
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
			$url = JURI::getInstance()->toString(array('scheme', 'host', 'port')) . '/' . preg_replace('/^\//', '', $url);
		}

		// Perform a simple HEAD-test to check for redirects or endless redirects
		if ($this->params->get('prevent_loops', 1) == 1 && function_exists('curl_init'))
		{
			$rt = $this->checkNoRedirectLoop($url);

			if ($rt == false)
			{
				return false;
			}
		}

		// Set the HTTP Redirect-status
		if (isset($match->http_status) && $match->http_status > 0)
		{
			$http_status = $match->http_status;
		}
		else
		{
			$params = JComponentHelper::getParams('com_dynamic404');
			$http_status = $params->get('http_status', 301);
		}

		// Perform the actual redirect
		header('HTTP/1.1 ' . Dynamic404HelperCore::getHttpStatusDescription($http_status));
		header('Location: ' . $url);
		header('Connection: close');
		$application->close();

		return true;
	}

	/**
	 * Method to display a custom page based on an existing Menu-Item
	 *
	 * @return bool
	 */
	public function displayCustomPage($error = null)
	{
		// Check for the error
		if (empty($error))
		{
			$error = $this->getErrorObject();
		}

		// Check the parameters
		$componentParams = JComponentHelper::getParams('com_dynamic404');

		if ($componentParams->get('error_page', 0) != 2)
		{
			return false;
		}

		// Check the parameters
		$Itemid = null;
		$article = null;
		$params = $this->params->toArray();
        $errorCode = $this->getErrorCode($error);

		foreach ($params as $name => $value)
		{
			if ($value > 0 && preg_match('/^menuitem_id_([0-9]+)/', $name, $match))
			{
				if ($errorCode == $match[1])
				{
					$Itemid = (int) $value;
				}
			}

			if ($value > 0 && preg_match('/^article_id_([0-9]+)/', $name, $match))
			{
				if ($errorCode == $match[1])
				{
					$article = (int) $value;
				}
			}
		}

		// Don't continue if no item is there
		if ($Itemid > 0 == false && $article > 0 == false)
		{
			return false;
		}

		// Check whether the current page is already the Dynamic404-page
		if ($this->jinput->getCmd('option') == 'com_dynamic404')
		{
			return false;
		}

		// Fetch the system variables
		$app = JFactory::getApplication();
		$cache = JFactory::getCache();

		// Determine the URL by Menu-Item
		if ($Itemid > 0)
		{
			// Load the configured Menu-Item
			$menu = JFactory::getApplication()->getMenu();
			$item = $menu->getItem($Itemid);

			if (empty($item) || !is_object($item) || !isset($item->query['option']))
			{
				return false;
			}

			// Construct the URL
			if (isset($item->component) && $item->component == 'com_dynamic404')
			{
				$currentUrl = JURI::current();
				$currentUrl = str_replace('?noredirect=1', '', $currentUrl);
				$url = JRoute::_('index.php?option=com_dynamic404&Itemid=' . $Itemid . '&uri=' . base64_encode($currentUrl));
			}
			else
			{
				$url = JRoute::_('index.php?Itemid=' . $Itemid);
			}

			// Article
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
		$languages = JLanguageHelper::getLanguages('sef');

		foreach ($languages as $language)
		{
			if ($language->lang_code == $currentLanguage->getTag())
			{
				$languageSef = $language->sef;
			}
		}

		// Add the language to the URL
		if (!empty($languageSef))
		{
			$url = (strstr($url, '?')) ? $url . '&lang=' . $languageSef : $url . '?lang=' . $languageSef;
		}

		// Fetch the content
		if ($this->params->get('caching', 1) == 1)
		{
			$cache->setCaching(1);
			$contents = $cache->call(array('Dynamic404Helper', 'fetchPage'), $url); // @todo: This violates E_STRICT
		}
		else
		{
			$contents = self::fetchPage($url);
		}

		// Output the content
		print $contents;
		$app->close();

		return true;
	}

	/**
	 * Method to fetch a specific page
	 *
	 * @param string $url
	 * @param string $useragent
	 *
	 * @return string
	 */
	static public function fetchPage($url, $useragent = null)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/lib/loader.php';

		return YireoHelper::fetchRemote($url, $useragent);
	}

	/**
	 * Method to re-initialize the Joomla! bootstrap and call upon the component again
	 *
	 * @param   int  $Itemid  Menu item ID
	 *
	 * @return null
	 */
	protected function showComponentPage($Itemid)
	{
		// Load the configured Menu-Item
		$menu = JFactory::getApplication()->getMenu();
		$item = $menu->getItem($Itemid);

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
		JFactory::getApplication()->close();

		return null;
	}

	/**
	 * Method to handle the default error page
	 *
	 * @return null
	 */
	public function displayErrorPage()
	{
		// System variables
		$application = JFactory::getApplication();
		$document = JFactory::getDocument();

		// Add some common variables to the error-page
		$this->error = $this->getErrorObject();
		$this->baseurl = JURI::base();
		$this->template = $application->getTemplate();
		$this->debug = 0;

		// Check the parameters
		$componentParams = JComponentHelper::getParams('com_dynamic404');

		if ($componentParams->get('error_page', 0) == 1)
		{
			$file = JPATH_SITE . '/templates/' . $application->getTemplate() . '/error.php';
		}

		if (empty($file) || file_exists($file) == false)
		{
			$file = JPATH_ADMINISTRATOR . '/components/com_dynamic404/lib/error.php';
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
		$error = JError::getError();
        $errorCode = $this->getErrorCode($error);
		$document = JFactory::getDocument();

		if (YireoHelper::isJoomla25())
		{
			$document->setError($error);
		}

		$document->setTitle(JText::_('Error') . ': ' . $errorCode);

		switch ($errorCode)
		{
			case '400':
				header('HTTP/1.0 400 Bad Request');
				break;
			case '401':
				header('HTTP/1.0 401 Unauthorized');
				break;
			case '402':
				header('HTTP/1.0 402 Payment Required');
				break;
			case '403':
				header('HTTP/1.0 403 Forbidden');
				break;
			case '404':
				header('HTTP/1.0 404 Not Found');
				break;
			case '405':
				header('HTTP/1.0 405 Method Not Allowed');
				break;
			case '406':
				header('HTTP/1.0 406 Not Acceptable');
				break;
			case '408':
				header('HTTP/1.0 408 Request Timeout');
				break;
			case '409':
				header('HTTP/1.0 409 Conflict');
				break;
			case '410':
				header('HTTP/1.0 410 Gone');
				break;
			case '500':
				header('HTTP/1.0 500 Internal Server Error');
				break;
			case '501':
				header('HTTP/1.0 501 Not Implemented');
				break;
			case '502':
				header('HTTP/1.0 502 Bad Gateway');
				break;
			case '503':
				header('HTTP/1.0 503 Service Unavailable');
				break;
			default:
				header('HTTP/1.0 404 Not Found');
				break;
		}
		return;
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
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('match'))
			->from($db->quoteName('#__dynamic404_redirects'))
			->where($db->quoteName('url') . '=' . $db->quote($url))
			;

		$db->setQuery($query);
		$match = $db->loadResult();

		if (empty($match))
		{
			$match = self::generateRandomString();

			$columns = array(
				'match',
				'url',
				'http_status',
				'type',
				'published',
			);
			$values = array(
				$db->quote($match),
				$db->quote($url),
				0,
				$db->quote('full_url'),
				1,
			);

			$query = $db->getQuery(true);
			$query->insert($db->quoteName('#__dynamic404_redirects'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values))
				;
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
		$length = 8;
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$string = '';

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
	 */
	public function preventHacks()
	{
		$url = JURI::current();
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
					$block = true;
					break;
				}
			}
		}

		// Block access to non-existing components
		if ($this->params->get('block_nonexisting_components', 1) == 1)
		{
			$cmd = $this->jinput->getCmd('option');

			if (!empty($cmd) && is_dir(JPATH_SITE . '/components/' . $cmd) == false)
			{
				$block = true;
			}
		}

		if ($block == false)
		{
			return true;
		}

		header('HTTP/1.1 403 Forbidden');
		die('Access Forbidden');

		return null;
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

    public function getErrorCode($error)
    {
        if(is_object($error)) {
            if(method_exists($error, 'get')) {
                return $error->get('code');
            } else {
                return $error->code;
            }
        }
    
        if(is_numeric($error)) {
            return $error;
        }
        
        return 404;
    }
}
