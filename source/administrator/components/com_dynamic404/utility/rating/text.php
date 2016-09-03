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

// Namespace
namespace Yireo\Dynamic404\Utility\Rating;

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Class Yireo\Dynamic404\Utility\Rating\Text
 */
class Text
{
	/**
	 * @var string
	 */
	private $string;

	/**
	 * Text constructor.
	 *
	 * @param $string
	 */
	public function __construct($string)
	{
		$string = strtolower($string);
		$this->string = $string;
	}

	/**
	 * @return string
	 */
	public function getString()
	{
		return $this->string;
	}

	/**
	 * @return array
	 */
	public function getParts()
	{
		return array_unique(explode('-', $this->string));
	}
}