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
namespace Yireo\Dynamic404\Utility;

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Requirements
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/utility/rating/text.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/utility/rating/text/source.php';
require_once JPATH_ADMINISTRATOR . '/components/com_dynamic404/utility/rating/text/target.php';

/**
 * Class Yireo\Dynamic404\Utility\Rating
 */
class Rating
{
	/**
	 * @var \Yireo\Dynamic404\Utility\Rating\Text\Source
	 */
	private $source;

	/**
	 * @var \Yireo\Dynamic404\Utility\Rating\Text\Target
	 */
	private $target;

	/**
	 * Yireo\Dynamic404\Utility\Rating constructor.
	 *
	 * @param string $sourceString
	 * @param array  $targetString
	 */
	public function __construct($sourceString = null, $targetString = null)
	{
		$this->setSourceByString($sourceString);
		$this->setTargetByString($targetString);
	}

	/**
	 * @return \Yireo\Dynamic404\Utility\Rating\Text\Source
	 */
	public function getSource()
	{
		return $this->source;
	}

	/**
	 * @param \Yireo\Dynamic404\Utility\Rating\Text\Source $source
	 */
	public function setSource($source)
	{
		$this->source = $source;
	}

	/**
	 * @param string $sourceString
	 */
	public function setSourceByString($sourceString)
	{
		$sourceString = $this->sanitizeString($sourceString);
		$this->source = new Rating\Text\Source($sourceString);
	}

	/**
	 * @param string $targetString
	 */
	public function setTargetByString($targetString)
	{
		$targetString = $this->sanitizeString($targetString);
		$this->target = new Rating\Text\Target($targetString);
	}

	/**
	 * @param $string
	 *
	 * @return string
	 */
	public function sanitizeString($string)
	{
		$string = trim($string);

		return $string;
	}

	/**
	 * @return \Yireo\Dynamic404\Utility\Rating\Text\Target
	 */
	public function getTarget()
	{
		$target = $this->target;

		if ($this->isValidTarget($target) == false)
		{
			return false;
		}

		return $target;
	}

	/**
	 * @param \Yireo\Dynamic404\Utility\Rating\Text\Target $target
	 */
	public function setTarget($target)
	{
		if (!empty($target) && is_string($target))
		{
			$this->setTargetByString($target);

			return;
		}

		if (!empty($target))
		{
			$this->target = $target;
		}
	}

	/**
	 * @param $target
	 *
	 * @return bool
	 */
	public function isValidTarget($target)
	{
		if (empty($target))
		{
			return false;
		}

		return true;
	}

	/**
	 * Method to match a target with this source
	 *
	 * @param   null|string|Rating\Text\Target $target
	 *
	 * @return bool
	 */
	public function hasSimpleMatch($target = null)
	{
		$this->setTarget($target);

		if ($this->source->getString() == $this->target->getString())
		{
			return true;
		}

		if (strstr($this->source->getString(), $this->target->getString()))
		{
			return true;
		}

		if (strstr($this->target->getString(), $this->source->getString()))
		{
			return true;
		}

		return false;
	}

	/**
	 * Method to match certain text parts, chopping a string into parts using a dash (-)
	 *
	 * @param   null|string|Rating\Text\Target $target
	 *
	 * @return array
	 */
	public function getMatchedParts($target = null)
	{
		$this->setTarget($target);

		$sourceParts = $this->source->getParts();
		$targetParts = $this->target->getParts();

		$matchedParts = array();

		if (!empty($sourceParts))
		{
			foreach ($sourceParts as $sourcePart)
			{
				if (in_array($sourcePart, $targetParts))
				{
					$matchedParts[] = $sourcePart;
				}
			}
		}

		return $matchedParts;
	}

	/**
	 * Method to get the matching percentage between two strings
	 *
	 * @param   null|string|Rating\Text\Target $target
	 *
	 * @return double
	 */
	public function getMatchPercentage($target = null)
	{
		if (!empty($target))
		{
			$this->setTarget($target);
		}

		$sourceString = $this->source->getString();
		$targetString = $this->target->getString();

		$sourceLength = strlen($sourceString);
		$targetLength = strlen($targetString);

		$matchingChars = similar_text($sourceString, $targetString);

		$matchPercentage = round((100 / $sourceLength * $matchingChars) / 100, 2);

		if ($targetLength > $sourceLength)
		{
			$lengthPenalty = round($targetLength / 100 * ($targetLength - $sourceLength) / 100, 2);
		}
		else
		{
			$lengthPenalty = 0;
		}

		$matchPercentage = $matchPercentage - $lengthPenalty;

		return $matchPercentage;
	}
}
