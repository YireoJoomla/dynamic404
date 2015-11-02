<?php
/**
 * Joomla! plugin for Dynamic404 - VirtueMart
 *
 * @author      Yireo (https://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright (c) 2015 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        https://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Import the parent class
jimport('joomla.plugin.plugin');

/**
 * Dynamic404 Plugin for VirtueMart
 */
class PlgDynamic404VirtueMart extends JPlugin
{
	protected $matchedUrls = array();

	/**
	 * Determine whether this plugin could be used
	 *
	 * @return boolean
	 */
	private function isEnabled()
	{
		if (!is_dir(JPATH_SITE . '/components/com_virtuemart'))
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the current locale
	 *
	 * @return string
	 */
	private function getLanguageCode()
	{
		$language = JFactory::getLanguage();

		if (isset($language->lang_code))
		{
			return str_replace('-', '_', $language->lang_code);
		}

		return 'en_gb';
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
		$app = JFactory::getApplication();
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
				$matches[] = $product;
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
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('l.category_name')));

		$query->from($db->quoteName('#__virtuemart_categories_' . $this->getLanguageCode(), 'l'));

		$categoryTable = $db->quoteName('#__virtuemart_categories', 'c');
		$categoryTableId = $db->quoteName('c.virtuemart_category_id');
		$productTableCategoryId = $db->quoteName('l.virtuemart_category_id');
		$query->join('LEFT', $categoryTable . ' ON (' . $categoryTableId . ' = ' . $productTableCategoryId . ')');

		$wheres = array();
		$wheres[] = $db->quoteName('l.virtuemart_category_id') . '=' . (int) $category_id;
		$wheres[] = $db->quoteName('l.slug') . ' LIKE ' . $db->quote('%' . $urilast . '%');
		$query->where(implode(' OR ', $wheres));

		$query->where($db->quoteName('c.published') . '=1');
		$query->setLimit(1);

		$db->setQuery($query);
		$category = $db->loadObject();

		if (empty($category))
		{
			return null;
		}

		$category->match_note = 'virtuemart category';
		$category->type = 'component';
		$category->name = $category->category_name;
		$category->rating = $this->params->get('rating', 85) - 1;
		$category->url = JRoute::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category_id);

		return $category;
	}

	/**
	 * @param $product_id
	 * @param $urilast
	 *
	 * @return JDatabaseQuery
	 */
	private function getVmProductQuery($product_id, $urilast)
	{
		$strings = $this->getSearchPartsFromString($urilast);

		if (empty($strings) && empty($product_id))
        {
            return false;
        }

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select($db->quoteName('p.virtuemart_product_id', 'product_id'));
		$query->select($db->quoteName('l.product_name'));
		$query->select($db->quoteName('l.slug'));
		$query->select($db->quoteName('c.virtuemart_category_id', 'category_id'));

		$query->from($db->quoteName('#__virtuemart_products_' . $this->getLanguageCode(), 'l'));

		$categoryTable = $db->quoteName('#__virtuemart_product_categories', 'c');
		$categoryTableProductId = $db->quoteName('c.virtuemart_product_id');
		$languageTableProductId = $db->quoteName('l.virtuemart_product_id');
		$query->join('LEFT', $categoryTable . ' ON (' . $categoryTableProductId . ' = ' . $languageTableProductId . ')');

		$productsTable = $db->quoteName('#__virtuemart_products', 'p');
		$productsTableProductId = $db->quoteName('p.virtuemart_product_id');
		$query->join('LEFT', $productsTable . ' ON (' . $productsTableProductId . ' = ' . $languageTableProductId . ')');

		$wheres = array();

		if (!empty($product_id))
		{
			$wheres[] = $db->quoteName('l.virtuemart_product_id') . '=' . (int) $product_id;
		}

		$strings = $this->getSearchPartsFromString($urilast);
		$firstString = $strings[0];
		$wheres[] = $db->quoteName('l.slug') . ' LIKE ' . $db->quote(implode('%', $strings));
		$wheres[] = $db->quoteName('l.slug') . ' LIKE ' . $db->quote($firstString . '%');

		$query->where('(' . implode(' OR ', $wheres) . ')');
		$query->where($db->quoteName('p.published') . '=1');

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
		$string = str_replace('_', '-', $string);
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
		$strings = $this->getArrayFromString($string);
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
		$db = JFactory::getDBO();

		$query = $this->getVmProductQuery($product_id, $urilast);

        if (empty($query))
        {
            return false;
        }

		$db->setQuery($query);
		$this->debug('VirtueMart query', $db->getQuery());

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

			$match->type = 'component';
			$match->name = $product->product_name;
			$match->match_note = 'virtuemart product';

			$match->rating = $this->params->get('rating', 85);
			$match->rating = $match->rating + $match->getAdditionalRatingFromMatchedParts($product->slug, $urilast);

			if (in_array($product->category_id, $this->getNumbersFromString($urilast)))
			{
				$match->rating = $match->rating + 1;
			}

			$match->url = $this->getProductUrl($product->product_id, $product->category_id);

			if (in_array($match->url, $this->matchedUrls))
			{
				continue;
			}

			$this->matchedUrls[] = $match->url;
			$matches[] = $match;
		}

		return $matches;
	}

	/**
	 * @param $product_id
	 * @param $category_id
	 *
	 * @return string
	 */
	private function getProductUrl($product_id, $category_id)
	{
		$url = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $product_id . '&virtuemart_category_id=' . $category_id;
		$url = JRoute::_($url);

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
