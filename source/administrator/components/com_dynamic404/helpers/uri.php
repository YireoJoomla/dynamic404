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
 * Class Dynamic404HelperUri
 */
class Dynamic404HelperUri
{
	/**
	 * @var JInput
	 */
	protected $input;

	/**
	 * Dynamic404HelperUri constructor.
	 */
	public function __construct()
	{
		$this->input = JFactory::getApplication()->input;
	}
	
	/**
	 * @param $uri
	 * 
	 * @return array
	 */
	public function getArrayFromUri($uri)
	{
		$uri_parts = explode('/', $uri);

		if (!empty($uri_parts))
		{
			foreach ($uri_parts as $i => $part)
			{
				if (empty($part))
				{
					unset($uri_parts[$i]);
				}
			}
		}

		return array_values($uri_parts);
	}
}