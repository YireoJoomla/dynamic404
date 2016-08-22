<?php
/**
 * Joomla! plugin for Dynamic404 - Hikashop
 *
 * @author      Yireo (https://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        https://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Import the parent class
jimport('joomla.plugin.plugin');

/**
 * Dynamic404 Plugin for Hikashop
 */
class PlgDynamic404Hikashop extends JPlugin
{
	protected $matchedUrls = array();

	/**
	 * Determine whether this plugin could be used
	 *
	 * @return boolean
	 */
	private function isEnabled()
	{
		if (!is_dir(JPATH_SITE . '/components/com_hikashop'))
		{
			return false;
		}

		return true;
	}

	/**
	 * Return on all matches
	 *
	 * @param string $urilast
	 *
	 * @return array
	 */
	public function getMatches($urilast = null)
	{
		$app     = JFactory::getApplication();
		$matches = array();

		if ($this->isEnabled() == false)
		{
			return $matches;
		}

		// Find a matching product
		$products = $this->findProducts($urilast, $app->input->getInt('product_id'));

		if (!empty($products))
		{
			foreach ($products as $product)
			{
				if (!empty($product))
				{
					$matches[] = $product;
				}
			}
		}

		// Find a matching category
		$category = $this->findCategory($urilast, $app->input->getInt('category_id'));

		if (!empty($category))
		{
			$matches[] = $category;
		}

		return $matches;
	}

	/**
	 * Method to match possible products
	 *
	 * @param string $urilast
	 * @param int    $category_id
	 *
	 * @return null|object
	 */
	private function findCategory($urilast, $category_id)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('c.category_id', 'c.category_name')));
		$query->from($db->quoteName('#__hikashop_category', 'c'));

		$wheres   = array();
		$wheres[] = $db->quoteName('c.category_id') . '=' . (int) $category_id;
		$wheres[] = $db->quoteName('c.category_alias') . ' LIKE ' . $db->quote('%' . $urilast . '%');

		$strings = $this->getSearchPartsFromString($urilast);

		foreach ($strings as $string)
		{
			$wheres[] = $db->quoteName('c.category_alias') . ' LIKE ' . $db->quote('%' . $string . '%');
		}

		$query->where(implode(' OR ', $wheres));

		$query->where($db->quoteName('c.category_published') . '=1');
		$query->setLimit(1);

		$db->setQuery($query);
		$category = $db->loadObject();

		$this->debug('Hikashop category query', $db->getQuery());

		if (empty($category) || empty($category->category_id))
		{
			return null;
		}

		$match = Dynamic404ModelMatch::getInstance($category);

		if (empty($match))
		{
			return false;
		}

		$match->match_note = 'hikashop category';
		$match->type       = 'component';
		$match->name       = $category->category_name;
		$match->rating     = $this->params->get('rating', 85) - 1;
		$match->url        = JRoute::_('index.php?option=com_hikashop&view=category&category_id=' . $category->category_id);

		return $match;
	}

	/**
	 * @param $product_id
	 * @param $urilast
	 *
	 * @return false|JDatabaseQuery
	 */
	private function getProductQuery($product_id, $urilast)
	{
		$strings = $this->getSearchPartsFromString($urilast);

		if (empty($strings) && empty($product_id))
		{
			return false;
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName('p.product_id'));
		$query->select($db->quoteName('p.product_name'));
		$query->select($db->quoteName('p.product_alias'));
		$query->select($db->quoteName('p.product_canonical'));
		$query->select($db->quoteName('c.category_id'));

		$query->from($db->quoteName('#__hikashop_product', 'p'));

		$categoryTable          = $db->quoteName('#__hikashop_product_category', 'c');
		$categoryTableProductId = $db->quoteName('c.product_id');
		$productId              = $db->quoteName('p.product_id');
		$query->join('LEFT', $categoryTable . ' ON (' . $categoryTableProductId . ' = ' . $productId . ')');

		$wheres = array();

		if (!empty($product_id))
		{
			$wheres[] = $db->quoteName('p.product_id') . '=' . (int) $product_id;
		}

		foreach ($strings as $string)
		{
			$wheres[] = $db->quoteName('p.product_alias') . ' LIKE ' . $db->quote('%' . $string . '%');
		}

		$query->where('(' . implode(' OR ', $wheres) . ')');
		$query->where($db->quoteName('p.product_published') . '=1');

		$query->setLimit(15);

		return $query;
	}

	/**
	 * @param $string
	 *
	 * @return array
	 */
	private function getArrayFromString($string)
	{
		$string  = str_replace('_', '-', $string);
		$strings = explode('-', $string);

		return $strings;
	}

	/**
	 * @param $string
	 *
	 * @return array
	 */
	private function getNumbersFromString($string)
	{
		$strings = $this->getArrayFromString($string);
		$numbers = array();

		foreach ($strings as $string)
		{
			if (is_numeric($string))
			{
				$numbers[] = $string;;
			}
		}

		return $numbers;
	}

	/**
	 * @param $string
	 *
	 * @return array
	 */
	private function getSearchPartsFromString($string)
	{
		$strings     = $this->getArrayFromString($string);
		$searchParts = array();

		foreach ($strings as $string)
		{
			if (is_numeric($string))
			{
				continue;
			}

			if (strlen($string) < 3)
			{
				continue;
			}

			$searchParts[] = $string;
		}

		return $searchParts;
	}

	/**
	 * Method to match possible products
	 *
	 * @param string $urilast
	 * @param int    $product_id
	 *
	 * @return array
	 */
	private function findProducts($urilast, $product_id)
	{
		$db = JFactory::getDbo();

		$query = $this->getProductQuery($product_id, $urilast);

		if (empty($query))
		{
			return false;
		}

		$db->setQuery($query);
		$this->debug('Hikashop product query', $db->getQuery());

		$products = $db->loadObjectList();

		if (empty($products))
		{
			return null;
		}

		$matches[] = array();

		foreach ($products as $product)
		{
			$match = Dynamic404ModelMatch::getInstance($product);

			if (empty($match))
			{
				continue;
			}

			$match->type       = 'component';
			$match->name       = $product->product_name;
			$match->match_note = 'hikashop product';

			$match->rating = $this->params->get('rating', 85);
			$match->rating = $match->rating + $match->getAdditionalRatingFromMatchedParts($product->product_alias, $urilast);

			if (in_array($product->category_id, $this->getNumbersFromString($urilast)))
			{
				$match->rating = $match->rating + 1;
			}

			$match->url = $this->getProductUrl($product);

			if (in_array($match->url, $this->matchedUrls))
			{
				continue;
			}

			$this->matchedUrls[] = $match->url;
			$matches[]           = $match;
		}

		return $matches;
	}

	/**
	 * @param $product_id
	 * @param $category_id
	 *
	 * @return string
	 */
	private function getProductUrl($product)
	{
		require_once JPATH_SITE . '/components/com_hikashop/helpers/route.php';

		$productSlug = $product->product_id . ':' . $product->product_alias;
		$url         = hikashopTagRouteHelper::getProductRoute($productSlug, $product->category_id, '*');
		$url         = JRoute::_($url);

		return $url;
	}

	/**
	 * Method alias for debugging
	 *
	 * @param   string $msg      Debugging message
	 * @param   null   $variable Optional variable to dump
	 */
	private function debug($msg, $variable = null)
	{
		Dynamic404HelperDebug::debug($msg, $variable);
	}
}
