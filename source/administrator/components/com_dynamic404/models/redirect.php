<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class Dynamic404ModelRedirect extends YireoModel
{
    /**
     * Indicator if this is a model for multiple or single entries
     */
    protected $_single = true;
    //protected $_debug = true;

    /**
     * Constructor
     *
     * @access public
     * @param null
     * @return null
     */
    public function __construct()
    {
        $this->_orderby_title = 'description';
        parent::__construct('redirect');
    }

    /**
     * Method to store the model
     *
     * @access public
     * @subpackage Yireo
     * @param mixed $data
     * @return bool
     */
    public function store($data)
    {
        if (isset($data['match']) && $data['match'] != '/') {
            $data['match'] = preg_replace( '/^\//', '', $data['match']);
        }

        return parent::store($data);
    }

    /**
     * Method to get data
     *
     * @access public
     * @subpackage Yireo
     * @param null
     * @return array
     */
    public function getData($forceNew = false)
    {
        // Get the parent data
        $data = parent::getData();
		$jinput = JFactory::getApplication()->input;

        if($jinput->getInt('modal') == 1 && empty($data->redirect_id))
		{
            $asset_id = $jinput->getInt('asset');
            $asset_data = $this->loadDataByAssetId($asset_id);

            if(!empty($asset_data) && is_array($asset_data))
			{
                $data = (object)array_merge((array)$data, $asset_data);
            }
        }

        return $data;
    }

    /*
     * Method to prepare for HTML output
     *
     * @access public
     * @param string $tpl
     * @return null
     */
    public function loadDataByAssetId($asset_id)
    {
        $asset_id = (int)$asset_id;
        if (empty($asset_id)) {
            return array();
        }

        $db = JFactory::getDBO();
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
            'url' => 'index.php?option='.$asset[0].'&view='.$asset[1].'&id='.$asset[2],
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
    
            require_once JPATH_SITE.'/components/com_content/helpers/route.php' ;
            $data['url'] = ContentHelperRoute::getArticleRoute($article->id.':'.$article->alias, $article->catid);
            $data['match'] = $article->id.'-'.$article->alias;
        }

        return $data;
    }
}
