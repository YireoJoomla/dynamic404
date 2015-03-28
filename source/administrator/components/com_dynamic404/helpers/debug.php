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

class Dynamic404HelperDebug
{
    public function debug($msg, $variable = null)
    {
		$this->params = JComponentHelper::getParams('com_dynamic404');

		if ($this->params->get('debug', 0) == 0)
        {
            return;
        }

        if(!empty($variable))
        {
            $msg .= ' = '.var_export($variable, true);
        }

        $msg .= "\n";
        echo $msg.'<br/>';
    }
}
