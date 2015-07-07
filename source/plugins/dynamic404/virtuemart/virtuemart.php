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
	 * Determine if this is VirtueMart 1 or not
	 *
	 * @return JRegistry
	 */
	private function isVm1()
	{
		if ($this->params->get('vm_version') == 1)
		{
			return true;
		}

		return false;
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
		$product = $this->findProduct($urilast, $app->input->getInt('product_id'));

		if (!empty($product))
		{
			$matches[] = $product;
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
	 * @param object $item
	 *
	 * @return string
	 */
	private function findCategory($urilast, $category_id)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		if ($this->isVm1())
		{
			$query->select($db->quoteName(array('c.category_name')));

			$query->from($db->quoteName('#__vm_category', 'c'));
			$query->where($db->quoteName('c.category_id') . '=' . (int) $category_id);
			$query->setLimit(1);
		}
		else
		{
			$query->select($db->quoteName(array('l.category_name')));

			$query->from($db->quoteName('#__virtuemart_categories_' . $this->getLanguageCode(), 'l'));
			$query->join('LEFT', $db->quoteName('#__virtuemart_categories', 'c')
				. ' ON (' . $db->quoteName('c.virtuemart_category_id') . ' = ' . $db->quoteName('l.virtuemart_category_id') . ')');

			$wheres = array();
			$wheres[] = $db->quoteName('l.virtuemart_category_id') . '=' . (int) $category_id;
			$wheres[] = $db->quoteName('l.slug') . ' LIKE ' . $db->quote('%' . $urilast . '%');
			$query->where(implode(' OR ', $wheres));

			$query->where($db->quoteName('c.published') . '=1');
			$query->setLimit(1);
		}

		$db->setQuery($query);
		$category = $db->loadObject();
		$category->match_note = 'virtuemart category';

		if (empty($category))
		{
			return null;
		}

		$category->type = 'component';
		$category->name = $category->category_name;
		$category->rating = $this->params->get('rating', 85) - 1;

		if ($this->isVm1())
		{
			$category->url = JRoute::_('index.php?page=shop.browse&category_id=' . $category_id);
		}
		else
		{
			$category->url = JRoute::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category_id);
		}

		return $category;
	}

	/**
	 * Method to match possible products
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	private function findProduct($urilast, $product_id)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		if ($this->isVm1())
		{
			$query->select($db->quoteName(array('p.product_name', 'x.category_id')));

			$query->from($db->quoteName('#__vm_product', 'p'));
			$query->join('LEFT', $db->quoteName('#__vm_product_category_xref', 'x')
				. ' ON (' . $db->quoteName('x.product_id') . ' = ' . $db->quoteName('p.product_id') . ')');

			$query->where($db->quoteName('p.product_id') . '=' . (int) $product_id);
			$query->where($db->quoteName('p.product_publish') . '=' . $db->quote('Y'));
			$query->setLimit(1);
		}
		else
		{
			$query->select($db->quoteName('l.product_name'));
			$query->select($db->quoteName('c.virtuemart_category_id', 'category_id'));

			$query->from($db->quoteName('#__virtuemart_products_' . $this->getLanguageCode(), 'l'));
			$query->join('LEFT', $db->quoteName('#__virtuemart_product_categories', 'c')
				. ' ON (' . $db->quoteName('c.virtuemart_product_id') . ' = ' . $db->quoteName('l.virtuemart_product_id') . ')');
			$query->join('LEFT', $db->quoteName('#__virtuemart_product', 'p')
				. ' ON (' . $db->quoteName('p.virtuemart_product_id') . ' = ' . $db->quoteName('l.virtuemart_product_id') . ')');

			$wheres = array();
			$wheres[] = $db->quoteName('l.virtuemart_product_id') . '=' . (int) $product_id;
			$wheres[] = $db->quoteName('l.slug') . ' LIKE ' . $db->quote('%' . $urilast . '%');
			$query->where(implode(' OR ', $wheres));

			$query->where($db->quoteName('p.published') . '=1');
			$query->setLimit(1);
		}

		$db->setQuery($query);
		$product = $db->loadObject();

		if (empty($product))
		{
			return null;
		}

		$category_id = $product->category_id;
		$product->type = 'component';
		$product->name = $product->product_name;
		$product->match_note = 'virtuemart product';
		$product->rating = $this->params->get('rating', 85);

		if ($this->isVm1())
		{
			$url = 'index.php?page=shop.product_details&flypage=flypage.tpl&product_id=' . $product_id . '&category_id=' . $category_id;
		}
		else
		{
			$url = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $product_id . '&virtuemart_category_id=' . $category_id;
		}

		$product->url = JRoute::_($url);

		return $product;
	}
}
