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
 * Class Dynamic404HelperMatchArticle
 */
class Dynamic404HelperMatchArticle
{
	/*
	 * Component parameters
	 */
	private $params = null;

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
	 * @param   int  $id  Numerical value to match
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
	 * @param   array  $strings Strings to search for
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
			$row = $this->getArticleById($match[0]);
			$row = $this->prepareArticle($row);

			if (!empty($row))
			{
				$row->rating = 95;
				$matches[] = $row;
			}
		}

		// Sanitize the strings
		$newStrings = array();

		foreach ($strings as $string)
		{
			if (strlen($string) < 2)
			{
				continue;
			}

			if (is_numeric($string))
			{
				continue;
			}

			$newStrings[] = $string;
		}

		$strings = $newStrings;

		if (empty($strings))
		{
			return array();
		}

		// Match the alias
		$rows = $this->getArticleListByStrings($strings);

		if (!empty($rows))
		{
			foreach ($rows as $row)
			{
				if (!isset($row->alias) || empty($row->alias))
				{
					continue;
				}

				$matchTextString = false;

				foreach ($strings as $string)
				{
					if (Dynamic404HelperMatch::matchTextString($row->alias, $string))
					{
						$matchTextString = true;
					}
				}

				$row->match_parts = array();

				foreach ($strings as $string)
				{
					$row->match_parts = array_merge($row->match_parts, Dynamic404HelperMatch::matchTextParts($row->alias, $string));
				}

				if (!empty($row->match_parts))
				{
					$row = $this->prepareArticle($row);

					if (!empty($row))
					{
						$row->match_note = 'article alias';
						$row->rating = $row->rating - count($strings) + count($row->match_parts);
						$matches[] = $row;
					}
				}
			}
		}

		return $matches;
	}

	/**
	 * Method to redirect to a specific match
	 *
	 * @param   string  $article_slug  Article ID + alias
	 * @param   int     $category_id   Category ID
	 * @param   int     $section_id    Section ID (deprecated)
	 * @param   string  $language      Language identifier
	 *
	 * @return string
	 */
	public function getArticleLink($article_slug, $category_id = null, $section_id = null, $language = null)
	{
		if (empty($article_slug))
		{
			return null;
		}

		require_once JPATH_SITE . '/components/com_content/helpers/route.php';

		if (empty($category_id) || is_numeric($article_slug))
		{
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('a.id', 'a.alias', 'a.catid')))
				->select($db->quoteName('c.alias', 'catalias'))
				->from($db->quoteName('#__content', 'a'))
				->join('INNER', $db->quoteName('#__categories', 'c') . ' ON (' . $db->quoteName('a.catid') . ' = ' . $db->quoteName('c.id') . ')')
				->where($db->quoteName('a.id') . '=' . (int) $article_slug);
			$db->setQuery($query);
			$article = $db->loadObject();

			if (!empty($article))
			{
				$article_slug = $article->id . ':' . $article->alias;
				$category_id = $article->catid . ':' . $article->catalias;
			}
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

		return $link;
	}

	/**
	 * Method to get an article by ID
	 *
	 * @param null
	 * @return array
	 */
	private function getArticleById($id)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName('#__content'))
			->where($db->quoteName('state') . '= 1')
			->where($db->quoteName('id') . '=' . (int) $id)
			->order($db->quoteName('ordering') . ' ASC');
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
	 * @param   array  $strings  Array of strings to search for
	 *
	 * @return array
	 */
	private function getArticleListByStrings($strings)
	{
		if (empty($strings))
		{
			return false;
		}

		static $rows = null;

		if (empty($rows))
		{
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select('*')
				->from('#__content')
				->where($db->quoteName('state') . '= 1')
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
		}

		return $rows;
	}

	/**
	 * Method to prepare an article
	 *
	 * @param   object  $item  Content item object
	 *
	 * @return object
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

		$item->type = 'component';
		$item->handler = 'article';
		$item->name = $item->title;
		$item->rating = $this->params->get('rating_articles', 85);

		// Parse the language of this item
		$item->parseLanguage();

		if (isset($item->sectionid))
		{
			$item->url = $this->getArticleLink($item->id . ':' . $item->alias, $item->catid, $item->sectionid, $item->language);
		}
		else
		{
			$item->url = $this->getArticleLink($item->id . ':' . $item->alias, $item->catid, null, $item->language);
		}

		return $item;
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
