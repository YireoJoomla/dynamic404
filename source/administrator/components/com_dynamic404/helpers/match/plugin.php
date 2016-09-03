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
 * Class Dynamic404HelperMatchPlugin
 */
class Dynamic404HelperMatchPlugin
{
	/**
	 * @var array
	 */
	protected $request;

	/**
	 * @var \Joomla\Registry\Registry
	 */
	protected $params;

	/**
	 * Dynamic404HelperMatchSearch constructor.
	 *
	 * @param $params
	 * @param $request
	 */
	public function __construct($params, $request)
	{
		$this->params  = $params;
		$this->request = $request;
	}

	/**
	 * @param $text1
	 * @param $text2
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getMatches($text1, $text2)
	{
		JPluginHelper::importPlugin('dynamic404');
		$application = JFactory::getApplication();
		$matches     = $application->triggerEvent('getMatches', array($text1, $text2));
		$totalMatches = array();

		foreach ($matches as $submatches)
		{
			if (!empty($submatches))
			{
				$totalMatches = array_merge($totalMatches, $submatches);
			}
		}
		
		return $totalMatches;
	}
}