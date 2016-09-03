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

// Load extra helpers
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match/article.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match/category.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match/manual.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match/menu.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match/plugin.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/match/search.php';

require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/helpers/uri.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/utility/rating.php';

/**
 * Class Dynamic404HelperMatch
 */
class Dynamic404HelperMatch
{
	/**
	 * Component parameters
	 *
	 * @var \Joomla\Registry\Registry
	 */
	protected $params = null;

	/**
	 * List of possible matches
	 *
	 * @var array
	 */
	protected $matches = array();

	/**
	 * @var array
	 */
	protected $request = array();

	/**
	 * URL to search matches for
	 *
	 * @var null
	 */
	protected $uri = null;

	/**
	 * Only allow static redirects
	 */
	protected $staticRulesOnly = false;

	/**
	 * @var Dynamic404HelperUri
	 */
	protected $uriHelper;

	/**
	 * @var \Yireo\Dynamic404\Utility\Rating
	 */
	protected $ratingHelper;

	/**
	 * Constructor
	 *
	 * @param string $uri
	 * @param bool   $staticRulesOnly
	 */
	public function __construct($uri = null, $staticRulesOnly = false)
	{
		// Read the component parameters
		$this->params = JComponentHelper::getParams('com_dynamic404');

		// Load the model
		require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/models/match.php';

		// Internal parameters
		$this->app   = JFactory::getApplication();
		$this->input = $this->app->input;

		// Helpers
		$this->uriHelper    = new Dynamic404HelperUri;
		$this->ratingHelper = new \Yireo\Dynamic404\Utility\Rating($uri);

		// Set the URI
		$this->setUri($uri);

		// Set the static flag
		$this->staticRulesOnly = $staticRulesOnly;

		// Initialize this helper
		$this->parseUri();
	}

	/**
	 * Method to set the internal URI
	 *
	 * @param null $uri
	 */
	public function setUri($uri = null)
	{
		if (empty($uri))
		{
			$uri = base64_decode($this->input->getString('uri'));
		}

		if (empty($uri))
		{
			$uri = JUri::current();
		}

		$this->uri = $uri;
	}

	/**
	 * Method to get a specific value from the request-array
	 *
	 * @param string $name
	 *
	 * @return array
	 */
	public function getRequest($name)
	{
		if (isset($this->request[$name]))
		{
			return $this->request[$name];
		}
	}

	/**
	 * Method to run all the match-functions and return the resulting matches
	 *
	 * @return array
	 */
	public function getMatches()
	{
		if (empty($this->uri))
		{
			return array();
		}

		// Call the internal matches
		if ($this->staticRulesOnly == true)
		{
			$this->findRedirectMatches();
		}
		else
		{
			$this->findNumericMatches();
			$this->findTextMatches();
			$this->findSearchPluginMatches();
			$this->findRedirectMatches();
		}

		$this->parseMatches();
		$this->sortMatches();

		return $this->matches;
	}

	/**
	 * Method to parse all URI parts from the URL
	 *
	 * @return null
	 */
	protected function parseUri()
	{
		$uri = $this->uri;

		// Replace non-sense strings
		$uri = str_replace('administrator/index.php', '', $uri);
		$uri = str_replace('?noredirect=1', '', $uri);
		$uri = preg_replace('/\/$/', '', $uri);
		$uri = str_replace('_', '-', $uri);

		// If this looks like a SEF-URL, parse it
		if (strstr($uri, 'index.php?option=') == false && strstr($uri, 'index.php?Itemid=') == false)
		{
			$this->parseSefUri($uri);
		}
		elseif (preg_match('/id=([a-zA-Z0-9\.\-\_\:]+)/', $uri, $match))
		{
			$this->parseNonSefUri($uri, $match);
		}

		$this->debug('Current URI', $this->request['uri']);
	}

	/**
	 * Parse a non-SEF URI
	 *
	 * @param $uri
	 * @param $match
	 */
	protected function parseNonSefUri($uri, $match)
	{
		$id          = $match[1];
		$id          = explode(':', $id);
		$uri_lastnum = null;
		$uri_last    = null;

		if (is_numeric($id[0]))
		{
			$uri_lastnum = $id[0];
		}

		if (is_string($id[0]))
		{
			$uri_last = $id[0];
		}

		if (!empty($id[1]))
		{
			$uri_last = $id[1];
		}

		$this->request = array(
			'uri'         => $uri,
			'uri_parts'   => array(),
			'uri_last'    => $uri_last,
			'uri_lastnum' => $uri_lastnum,
		);
	}

	/**
	 * Parse a SEF URI
	 *
	 * @param $uri
	 */
	protected function parseSefUri($uri)
	{
		$juri = JUri::getInstance();

		// Fetch the current request and parse it
		$uri = preg_replace('/^\//', '', $uri);
		$uri = preg_replace('/\.(html|htm|php)$/', '', $uri);
		$uri = preg_replace('/\?lang=([a-zA-Z0-9]+)$/', '', $uri);
		$uri = preg_replace('/\&Itemid=([0-9]?)$/', '', $uri);
		$uri = preg_replace('/^(http|https):\/\//', '', $uri);
		$uri = preg_replace('/^' . $juri->getHost() . '\//', '', $uri);

		if (!stristr($uri, '?'))
		{
			$uri = preg_replace('/\&(.*)$/', '', $uri);
		}

		$uri_parts   = $this->uriHelper->getArrayFromUri($uri);
		$uri_lastnum = null;
		$uri_last    = null;
		$total       = count($uri_parts);

		for ($i = $total; $i > 0; $i--)
		{
			if (!isset($uri_parts[$i - 1]))
			{
				continue;
			}

			if (!is_numeric($uri_parts[$i - 1]))
			{
				$uri_last = $uri_parts[$i - 1];
				break;
			}
			elseif (is_numeric($uri_parts[$i - 1]))
			{
				$uri_lastnum = $uri_parts[$i - 1];
			}
		}

		$this->request = array(
			'uri'         => $uri,
			'uri_parts'   => $uri_parts,
			'uri_last'    => $uri_last,
			'uri_lastnum' => $uri_lastnum,
		);
	}

	/**
	 * Method to collect all the numerical matches
	 *
	 * @return array
	 */
	public function findNumericMatches()
	{
		if ($this->params->get('search_ids', 1) == 0)
		{
			return false;
		}

		// Try to find numerical matches
		if (!is_numeric($this->request['uri_lastnum']) && !preg_match('/^(m|a)([0-9]+)$/', $this->request['uri_last'], $match))
		{
			return false;
		}

		// Find the right type for this segment (a is article, m is menu-item)
		if (isset($match) && isset($match[1]) && isset($match[2]))
		{
			$type = ($match[1] == 'a') ? 'article' : 'menuitem';
			$id   = $match[2];
		}
		else
		{
			$type = 'any';
			$id   = (int) $this->request['uri_lastnum'];
		}

		if ($id > 0 == false)
		{
			return false;
		}

		// Call the article-helper
		if (($type == 'any' || $type == 'article') && $this->params->get('search_articles', 1) == 1)
		{
			$helper = new Dynamic404HelperMatchArticle($this->params);
			$this->addToMatches($helper->findNumericMatches($id));
		}

		// Call the category-helper
		if (($type == 'any' || $type == 'category') && $this->params->get('search_categories', 1) == 1)
		{
			$helper = new Dynamic404HelperMatchCategory($this->params);
			$this->addToMatches($helper->findNumericMatches($id));
		}

		// Call the menuitem-helper
		if (($type == 'any' || $type == 'menuitem') && $this->params->get('search_menuitems', 1) == 1)
		{
			$helper = new Dynamic404HelperMatchMenu($this->params);
			$this->addToMatches($helper->findNumericMatches($id));
		}

		// Call all dynamic404-plugins
		JPluginHelper::importPlugin('dynamic404');
		$application = JFactory::getApplication();
		$matches     = $application->triggerEvent('getNumericMatches', array($id));

		if (isset($matches[0]))
		{
			$this->addToMatches($matches[0]);
		}

		return true;
	}

	/**
	 * @param $string
	 *
	 * @return mixed|string
	 */
	protected function sanitizeString($string)
	{
		// Construct the first text
		$string = strtolower($string);
		$string = str_replace(' ', '-', $string);
		$string = str_replace('%20', '-', $string);
		$string = preg_replace('/([\-\_\.]+)$/', '', $string);
		$string = preg_replace('/^([\-\_\.]+)/', '', $string);
		$string = str_replace('.', '-', $string);
		$string = str_replace('_', '-', $string);

		return $string;
	}

	/**
	 * Method to collect all the text matches
	 *
	 * @return bool
	 */
	public function findTextMatches()
	{
		if ($this->params->get('search_text', 1) == 0)
		{
			return false;
		}

		// Try to find text matches
		if (!is_string($this->request['uri_last']))
		{
			return false;
		}

		// Construct the first text
		$text1 = $this->sanitizeString($this->request['uri_last']);

		if (empty($text1))
		{
			return false;
		}

		// Construct the second text
		$text2 = str_replace('_', '-', $text1);

		// Construct text parts
		$textParts = explode('-', $text2);

		// Call the article-helper
		if ($this->params->get('search_articles', 1) == 1)
		{
			$helper = new Dynamic404HelperMatchArticle($this->params);
			$this->addToMatches($helper->findTextMatches($textParts));
		}

		// Call the category-helper
		if ($this->params->get('search_categories', 1) == 1)
		{
			$helper = new Dynamic404HelperMatchCategory($this->params);
			$this->addToMatches($helper->findTextMatches($text1, $text2));
		}

		// Call the menuitem-helper
		if ($this->params->get('search_menuitems', 1) == 1)
		{
			$helper = new Dynamic404HelperMatchMenu($this->params);
			$this->addToMatches($helper->findTextMatches($text1, $text2, $this->request['uri'], $this->request['uri_parts']));
		}

		$this->findDynamic404PluginMatches($text1, $text2);

		return true;
	}

	/**
	 * @param $text1
	 * @param $text2
	 *
	 * @return array
	 */
	public function findDynamic404PluginMatches($text1, $text2)
	{
		$helper  = new Dynamic404HelperMatchPlugin($this->params, $this->request);
		$matches = $helper->getMatches($text1, $text2);
		$this->addToMatches($matches);

		return $matches;
	}

	/**
	 * Method to find matches using search plugins
	 *
	 * @return array
	 */
	public function findSearchPluginMatches()
	{
		$helper  = new Dynamic404HelperMatchSearch($this->params, $this->request);
		$matches = $helper->getMatches();
		$this->addToMatches($matches);

		return $matches;
	}

	/**
	 * Method to find matches within custom redirects
	 *
	 * @return array
	 */
	public function findRedirectMatches()
	{
		$helper  = new Dynamic404HelperMatchManual($this->params, $this->request);
		$matches = $helper->getMatches($this->staticRulesOnly);
		$this->addToMatches($matches);

		return $matches;
	}

	/**
	 * Method to add a valid list of matches to the existing matches
	 *
	 * @param   array $matches List of matches to add
	 *
	 * @return null
	 */
	private function addToMatches($matches)
	{
		if (is_array($matches) && !empty($matches))
		{
			$this->matches = array_merge($this->matches, $matches);
		}
	}

	/**
	 * Method to parse all the matches
	 *
	 * @return null
	 */
	private function parseMatches()
	{
		$uri = JUri::getInstance();

		if (!empty($this->matches))
		{
			foreach ($this->matches as $index => $match)
			{
				// Cast this match to the right class
				$match = Dynamic404ModelMatch::getInstance($match);

				// Parse the current match
				$match->parse();

				if ($uri->toString(array('path')) == $match->url)
				{
					unset($this->matches[$index]);
					continue;
				}

				$this->matches[$index] = $match;
			}
		}
	}

	/**
	 * Method to sort all the matches by their rating
	 *
	 * @return null
	 */
	private function sortMatches()
	{
		if (!empty($this->matches))
		{
			$foundMatches = array();
			$sort         = array();

			foreach ($this->matches as $match)
			{
				$matchSum = md5($match->url);

				if (array_key_exists($matchSum, $foundMatches))
				{
					$foundMatch = $foundMatches[$matchSum];

					if ($match->rating < $foundMatch->rating)
					{
						continue;
					}
				}

				$foundMatches[$matchSum] = $match;

				$index        = urlencode($match->rating . '-' . $match->url);
				$sort[$index] = array('rating' => $match->rating, 'match' => $match);
			}

			ksort($sort);
			$matches = array();

			if (!empty($sort))
			{
				foreach ($sort as $s)
				{
					$matches[] = $s['match'];
				}
			}

			$this->matches = array_reverse($matches);
		}
	}

	/**
	 * Method to match one string with another
	 *
	 * @param   string $text1 String to match
	 * @param   string $text2 String to compare with
	 *
	 * @return bool
	 * @deprecated Use \Yireo\Dynamic404\Utility\Rating::hasSimpleMatch() instead
	 */
	static public function matchTextString($text1, $text2)
	{
		$rating = new \Yireo\Dynamic404\Utility\Rating($text1, $text2);

		return $rating->hasSimpleMatch();
	}

	/**
	 * Method to match certain text parts, chopping a string into parts using a dash (-)
	 *
	 * @param   string $text1 String to match
	 * @param   string $text2 String to compare with
	 *
	 * @return bool
	 * @deprecated Use \Yireo\Dynamic404\Utility\Rating::getMatchedParts() instead
	 */
	static public function matchTextParts($text1, $text2)
	{
		$rating = new \Yireo\Dynamic404\Utility\Rating($text1, $text2);

		return $rating->getMatchedParts();
	}

	/**
	 * Method alias for debugging
	 *
	 * @param   string $msg      Debugging message
	 * @param   null   $variable Optional variable to dump
	 */
	public function debug($msg, $variable = null)
	{
		Dynamic404HelperDebug::debug($msg, $variable);
	}
}
