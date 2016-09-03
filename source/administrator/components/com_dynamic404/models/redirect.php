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
 * Class Dynamic404ModelRedirect
 */
class Dynamic404ModelRedirect extends YireoModel
{
	/**
	 * Indicator if this is a model for multiple or single entries
	 */
	protected $_single = true;

	/**
	 * @var bool
	 */
	protected $_debug = false;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_orderby_title = 'description';

		return parent::__construct('redirect');
	}

	/**
	 * Method to store the model
	 *
	 * @param mixed $data
	 *
	 * @return bool
	 */
	public function store($data)
	{
		if (isset($data['url']))
		{
			$data['url'] = trim($data['url']);
		}

		if (isset($data['match']) && $data['match'] != '/')
		{
			$data['match'] = preg_replace('/^\//', '', $data['match']);
		}

		return parent::store($data);
	}

	/**
	 * Method to get data
	 *
	 * @param bool $forceNew
	 *
	 * @return array
	 */
	public function getData($forceNew = false)
	{
		// Get the parent data
		$data = parent::getData();
		$jinput = JFactory::getApplication()->input;

		if ($jinput->getInt('modal') == 1 && empty($data->redirect_id))
		{
			$asset_id = $jinput->getInt('asset');
			$asset_data = $this->loadDataByAssetId($asset_id);

			if (!empty($asset_data) && is_array($asset_data))
			{
				$data = (object) array_merge((array) $data, $asset_data);
			}
		}

		return $data;
	}

	/**
	 * Method to prepare for HTML output
	 *
	 * @param string $tpl
	 * @return null
	 */
	public function loadDataByAssetId($asset_id)
	{
		$asset_id = (int) $asset_id;

		if (empty($asset_id))
		{
			return array();
		}

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('name'));
		$query->from($db->quoteName('#__assets'));
		$query->where($db->quoteName('id') . '=' . $asset_id);
		$db->setQuery($query);

		// Load the generic asset data
		$assetName = $db->loadResult();
		$asset = explode('.', $assetName);

		if (!isset($asset[2]))
		{
			return array();
		}

		$data = array(
			'match' => 'test',
			'type' => 'full_url',
			'http_status' => '303',
			'url' => 'index.php?option=' . $asset[0] . '&view=' . $asset[1] . '&id=' . $asset[2],
			'params' => array(
				'redirect' => 1,
				'rating' => 99,
			),
		);

		// Complete with article data
		if ($asset[0] == 'com_content')
		{
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('id', 'alias', 'catid')));
			$query->from($db->quoteName('#__content'));
			$query->where($db->quoteName('id') . '=' . $asset[2]);
			$db->setQuery($query);

			$article = $db->loadObject();

			require_once JPATH_SITE . '/components/com_content/helpers/route.php';
			$data['url'] = ContentHelperRoute::getArticleRoute($article->id . ':' . $article->alias, $article->catid);
			$data['match'] = $article->id . '-' . $article->alias;
		}

		return $data;
	}

	/**
	 * Method to initialise the data
	 *
	 * @return bool
	 */
	protected function getEmpty()
	{
		$rt = parent::getEmpty();

        if (!empty($this->params)) {
		$this->data->type = $this->params->get('type');
		$this->data->http_status = $this->params->get('http_status');

		$params = YireoHelper::toParameter($this->data->params);
		$params->set('redirect', $this->params->get('redirect'));
		$params->set('match_case', $this->params->get('match_case'));
		$params->set('show_description', $this->params->get('show_description'));
		$this->data->params = json_encode($params);
        }

		return $rt;
	}
}
