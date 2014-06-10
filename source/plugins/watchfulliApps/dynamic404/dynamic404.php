<?php
/**
 * Dynamic404 App for Watchful
 *
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright (c) 2014 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('JPATH_BASE') or die;

/**
 * App class
 *
 * @package Dynamic404
 */
require_once(JPATH_ADMINISTRATOR . '/components/com_watchfulli/classes/apps.php');

class Dynamic404Alert extends AppAlert
{
    public $parameter1;
    public $parameter2;
    public $parameter3;

    public function Dynamic404Alert($level, $message, $parameter1 = null, $parameter2 = null, $parameter3 = null)
    {
        if ($level != null && $message != null)
        {
            $this->level = $level;
            $this->message = $message;
            $this->parameter1 = $parameter1;
            $this->parameter2 = $parameter2;
            $this->parameter3 = $parameter3;
        }
    }
}

/**
 * Plugin class for reusing redirection of the Dynamic404 component
 *
 * @package Dynamic404
 */
class plgWatchfulliAppsDynamic404 extends watchfulliApps
{
    public function createAppAlert($level, $message, $parameter1 = null, $parameter2 = null, $parameter3 = null)
    {
        $alert = new Dynamic404Alert($level, $message, $parameter1, $parameter2, $parameter3);
        $this->addAlert($alert);
    }

    public function appMainProgram($oldValuesSerialized = null)
    {
        $this->setName('Dynamic404');
        $this->setDescription('Dynamic404 app to check if everything is configured properly.');
        $debug = $this->params->get('debug', 0);

        // Alert and return if Dynamic404 not installed
        if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_dynamic404/dynamic404.php'))
        {
            $this->createAppAlert(1, 'PLG_WATCHFULLIAPPS_DYNAMIC404_ALERT_COMPONENT_NOTFOUND');
            return $this;
        }

        // Check for autoredirect setting
        $params = JComponentHelper::getParams('com_dynamic404');
        if ($params->get('enable_redirect', 1) == 0)
        {
            $this->createAppAlert(1, 'PLG_WATCHFULLIAPPS_DYNAMIC404_ALERT_REDIRECT_DISABLED');
        }
        return $this;
    }
}
