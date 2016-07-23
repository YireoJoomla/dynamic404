<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (https://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        https://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Class Dynamic404ModelMatch
 */
class Dynamic404ModelMatch
{
	/**
	 * The friendly name of this match displayed on screen
	 *
	 * @var string
	 */
	public $name = null;

	/**
	 * The type of this match (component|...)
	 *
	 * @var string
	 */
	public $type = null;

	/**
	 * The mechanism responsible for finding this match
	 *
	 * @var string
	 */
	public $handler = null;

	/**
	 * Percentage describing how much this match applies to the request
	 *
	 * @var int
	 */
	public $rating = 85;

	/**
	 * Direct URL of this match
	 *
	 * @var string
	 */
	public $url = null;

	/**
	 * Optional HTTP Status used when redirecting to this URL
	 *
	 * @var string
	 */
	public $http_status = null;

	/**
	 * Optional note for development
	 *
	 * @var string
	 */
	public $match_note = null;

	/**
	 * Method to get an instance
	 */
	static public function getInstance($data)
	{
		if ($data instanceof self)
		{
			return $data;
		}

		$model = new self;
		$model->cast($data);

		return $model;
	}

	/**
	 * Method to increase matching
	 */
	public function increaseRating($increase)
	{
		$this->rating += $increase;
	}

	/**
	 * Calculate an additional rating by matching two string patterns and seeing how much they match
	 *
	 * @param string $matchString
	 * @param array $searchParts
	 *
	 * @return int
	 */
	public function getAdditionalRatingFromMatchedParts($matchString, $searchParts)
	{
		$sourceString = implode('-', $searchParts);

		$rating = new \Yireo\Dynamic404\Utility\Rating($sourceString, $matchString);
		
		return $rating->getMatchPercentage();
		/*
		if (is_string($searchParts))
		{
			$searchParts = explode('-', $searchParts);
		}

		$rating = 0;
		$matchParts = array();

		foreach ($searchParts as $searchPart)
		{
			$matchParts = array_merge($matchParts, Dynamic404HelperMatch::matchTextParts($matchString, $searchPart));
		}

		if (!empty($matchParts))
		{
			$rating = count(array_intersect($matchParts, $searchParts));
		}

		$this->debug('Additional rating for "' . $matchString . '" [' . $rating . ']', $searchParts);

		return $rating;
		*/
	}

	/**
	 * Method to parse the properties of this match for output
	 */
	public function parse()
	{
		$this->parseLanguage();
		$this->parseUrl();

		$this->uri = $this->url;
		//$this->uri = str_replace(JURI::root(), '', $this->url);
	}

	/**
	 * Method to parse the language
	 */
	public function parseLanguage()
	{
		$currentLanguage = JFactory::getLanguage()->getTag();

		if (empty($this->language) || $this->language == '*')
		{
			$this->language = $currentLanguage;
		}

		if ($currentLanguage != $this->language)
		{
			$this->rating -= 1;
		}
	}

	/**
	 * Method to parse the URL of this match for output
	 */
	public function parseUrl()
	{
		$config = JFactory::getConfig();
		$sef_rewrite = (bool) $config->get('sef_rewrite');
		$app = JFactory::getApplication();

		if (strstr($this->url, 'index.php?option'))
		{
			if ($app->isAdmin())
			{
				JFactory::$application = JApplicationCms::getInstance('site');
                $siteApp = JFactory::getApplication();
			    $siteRouter = $siteApp::getRouter();
			    $siteUri = $siteRouter->build($this->url);
                $this->url = $siteUri->toString(array('path', 'query', 'fragment'));

                $this->url = preg_replace('/\/administrator\//', '', $this->url);
                $this->url = preg_replace('/^administrator\//', '', $this->url);
				$this->url = JURI::root() . $this->url;

				JFactory::$application = JApplicationCms::getInstance('administrator');
			}
			else
			{
				$this->url = JRoute::_($this->url);
			}
		}

		if (preg_match('/^(http|https):\/\//', $this->url) == false && preg_match('/^\//', $this->url) == false)
		{
			$base_uri = JURI::base();

			if ($sef_rewrite == false)
			{
				$base_uri .= 'index.php/';
			}

			if (preg_match('/^\//', $this->url))
			{
				$base_uri = preg_replace('/\/$/', '', $base_uri);
			}

			$this->url = $base_uri . $this->url;
		}
	}

	/**
	 * Method to cast a plain object to an object of this class
	 */
	public function cast($object)
	{
		if (is_array($object) || is_object($object))
		{
			foreach ($object as $key => $value)
			{
				$this->$key = $value;
			}
		}
	}

	/**
	 * Method alias for debugging
	 *
	 * @param   string  $msg       Debugging message
	 * @param   null    $variable  Optional variable to dump
	 */
	public function debug($msg, $variable = null)
	{
		Dynamic404HelperDebug::debug($msg, $variable);
	}
}
