<?php
/*
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright (C) 2014 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Dynamic404 Structure
 */
class HelperAbstract
{
    /**
     * Structural data of this component
     */
    static public function getStructure()
    {
        return array(
            'title' => 'Dynamic404',
            'menu' => array(
                'home' => 'Home',
                'setup' => 'Setup',
                'redirects' => 'Redirects',
                'logs' => 'Logs',
                'option=com_plugins&view=plugins&filter_folder=dynamic404' => 'Plugins',
            ),
            'views' => array(
                'home' => 'Home',
                'setup' => 'Setup',
                'redirect' => 'Redirect Rule',
                'redirects' => 'Redirect Rules',
                'log' => 'log',
                'logs' => 'logs',
            ),
            'obsolete_files' => array(
                JPATH_ADMINISTRATOR.'/components/com_dynamic404/views/redirects/tmpl/default.php',
                JPATH_ADMINISTRATOR.'/components/com_dynamic404/views/home/tmpl/default.php',
                JPATH_ADMINISTRATOR.'/components/com_dynamic404/views/home/tmpl/default_cpanel.php',
                JPATH_ADMINISTRATOR.'/components/com_dynamic404/views/home/tmpl/default_ads.php',
                JPATH_ADMINISTRATOR.'/components/com_dynamic404/views/home/tmpl/feeds.php',
                JPATH_ADMINISTRATOR.'/components/com_dynamic404/views/setup/tmpl/default_j16.php',
                JPATH_ADMINISTRATOR.'/components/com_dynamic404/helpers/acl.php',
            ),
        );
    }
}
