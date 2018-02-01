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
 * Class Dynamic404HelperMatchArticle
 */
class Dynamic404HelperMatchArticle
{
	/*
	 * Component parameters
	 */
	private $params = null;

	/**
	 * @var array
	 */
	protected $articleFields = array(
		'id',
		'title',
		'alias',
		'introtext',
		'fulltext',
		'state',
		'catid',
		'publish_up',
		'publish_down',
		'attribs',
		'language'
	);

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->params = JComponentHelper::getParams('com_dynamic404');
	}

	/**
	 * Method to find matches when the last segment seems to be an ID
	 *
	 * @param   int $id Numerical value to match
	 *
	 * @return mixed|null
	 */
	public function findNumericMatches($id)
	{
		$row = $this->getArticleById($id);
		$row = $this->prepareArticle($row);

		if (empty($row))
		{
			return null;
		}

		$row->handler = 'article';

		return array($row);
	}

	/**
	 * Method to find matches within Joomla! articles
	 *
	 * @param   array $strings Strings to search for
	 *
	 * @return array
	 */
	public function findTextMatches($strings)
	{
		if (empty($strings))
		{
			return array();
		}

		$matches = array();

		// Match the number only
		$firstString = $strings[0];

		if (preg_match('/^([0-9]+)\-/', $firstString, $match))
		{
			$article = $this->getArticleById($match[0]);
			$article = $this->prepareArticle($article);

			if (!empty($article))
			{
				$article->rating     = $this->params->get('rating_articles', 85);
				$article->match_note = 'article ID';
				$matches[]           = $article;
			}
		}

		// Match the alias
		$articles = $this->getArticleListByStrings($strings);

		if (empty($articles))
		{
			return $matches;
		}

		foreach ($articles as $article)
		{
			if (!isset($article->alias) || empty($article->alias))
			{
				continue;
			}

			/** @var Dynamic404ModelMatch $match */
			$match = $this->prepareArticle($article);

			if (empty($match))
			{
				continue;
			}

			$additionalRating = $match->getAdditionalRatingFromMatchedParts($match->alias, $strings);

			if (empty($additionalRating) && $this->params->get('load_all_articles', 0) == 1)
			{
				continue;
			}

			$match->search_parts = $strings;
			$match->match_note = 'article alias "' . $match->alias . '"';

			if ($this->params->get('apply_character_rating', 1) == 1)
			{
				$match->rating = $match->rating + $additionalRating;
			}

			$matches[] = $match;
		}

		return $matches;
	}

	/**
	 * Method to redirect to a specific match
	 *
	 * @param   string $article_slug Article ID + alias
	 * @param   int    $category_id  Category ID
	 * @param   int    $section_id   Section ID (deprecated)
	 * @param   string $language     Language identifier
	 *
	 * @return string
	 */
	public function getArticleLink($article_slug, $category_id = null, $section_id = null, $language = null)
	{
        $authorised = JAccess::getAuthorisedViewLevels(JFactory::getUser()->get('id'));
		$article = $this->getArticleSlugDetailsById($article_slug);

		if (empty($article_slug))
		{
			return null;
		}

		require_once JPATH_SITE . '/components/com_content/helpers/route.php';

		if (empty($category_id) || is_numeric($article_slug))
		{

            if (!in_array($article->access, $authorised))
            {
                return null;
            }

			if (!empty($article))
			{
				$article_slug = $article->id . ':' . $article->alias;
				$category_id  = $article->catid . ':' . $article->catalias;
			}
		}

        if (!empty($category_id) && !empty($article->cataccess) && !in_array($article->cataccess, $authorised))
        {
            return null;
        }

		if ($section_id > 0)
		{
			$link = ContentHelperRoute::getArticleRoute($article_slug, $category_id, $section_id);
		}
		else
		{
			$link = ContentHelperRoute::getArticleRoute($article_slug, $category_id);
		}

		$currentLanguage = JFactory::getLanguage();

		if (!empty($language) && $language != '*' && $language != $currentLanguage->getTag())
		{
			$link .= '&lang=' . $language;
		}

		return JRoute::_($link);
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	private function getArticleSlugDetailsById($id)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('a.id', 'a.alias', 'a.catid', 'a.access')))
			->select($db->quoteName('c.alias', 'catalias'))
			->select($db->quoteName('c.access', 'cataccess'))
			->from($db->quoteName('#__content', 'a'))
			->join('INNER', $db->quoteName('#__categories', 'c') . ' ON (' . $db->quoteName('a.catid') . ' = ' . $db->quoteName('c.id') . ')')
			->where($db->quoteName('a.id') . '=' . (int) $id)
			->setLimit(1);
		$db->setQuery($query);
		$article = $db->loadObject();

		return $article;
	}

	/**
	 * Method to get an article by ID
	 *
	 * @param null
	 *
	 * @return object
	 */
	private function getArticleById($id)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName($this->articleFields))
			->from($db->quoteName('#__content'))
			->where($db->quoteName('state') . '= 1')
			->where($db->quoteName('id') . '=' . (int) $id)
			->order($db->quoteName('ordering') . ' ASC')
			->setLimit(1);
		$db->setQuery($query, 0, 1);

		if ($this->params->get('debug', 0) == 1)
		{
			$this->debug('MatchArticle::getArticleById', $db->getQuery());
		}

		return $db->loadObject();
	}

	/**
	 * Method to get a list of articles
	 *
	 * @param   array $strings Array of strings to search for
	 *
	 * @return array
	 */
	private function getArticleListByStrings($strings)
	{
		if (empty($strings))
		{
			return array();
		}

		static $rows = null;

		if (!empty($rows))
		{
			return $rows;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$nullDate = $db->getNullDate();
		$date     = JFactory::getDate();
		$now      = $date->toSql();

		$query->select($db->quoteName($this->articleFields))
			->from($db->quoteName('#__content'))
			->where($db->quoteName('state') . '= 1')
			->where('(publish_up = ' . $db->quote($nullDate) . ' OR publish_up <= ' . $db->quote($now) . ')')
			->where('(publish_down = ' . $db->quote($nullDate) . ' OR publish_down >= ' . $db->quote($now) . ')')
			->order($db->quoteName('ordering'));

		if ($this->params->get('load_all_articles', 0) == 0)
		{
			$whereParts = array();

			foreach ($strings as $string)
			{
				$whereParts[] = $db->quoteName('alias') . ' LIKE ' . $db->quote('%' . $string . '%');
			}

			$query->where('(' . implode(' OR ', $whereParts) . ')');
		}

		$db->setQuery($query);

		if ($this->params->get('debug') == 1)
		{
			$this->debug('MatchArticle::getArticleListByStrings', $db->getQuery());
		}

		$rows = $db->loadObjectList();

		return $rows;
	}

	/**
	 * Method to prepare an article
	 *
	 * @param   object $item Content item object
	 *
	 * @return array|object
	 */
	private function prepareArticle($item)
	{
		// Sanity checks
		if (empty($item) || !is_object($item))
		{
			return null;
		}

		// Cast this match to the right class
		$item = Dynamic404ModelMatch::getInstance($item);

		$item->type    = 'component';
		$item->handler = 'article';
		$item->name    = $item->title;
		$item->rating  = $this->params->get('rating_articles', 85);

		// Parse the language of this item
		$item->parseLanguage();
		$item->url = $this->getArticleUrl($item);

		return $item;
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	private function getArticleUrl($item)
	{
		if (isset($item->sectionid))
		{
			return $this->getArticleLink($item->id . ':' . $item->alias, $item->catid, $item->sectionid, $item->language);
		}

		return $this->getArticleLink($item->id . ':' . $item->alias, $item->catid, null, $item->language);
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
