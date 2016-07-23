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
 * HTML View class 
 */
class Dynamic404ViewSetup extends YireoView
{
    public function __construct($config = array())
    {
        $this->loadToolbar = false;
        parent::__construct($config);
    }

    /*
     * Method to prepare for HTML output
     *
     * @access public
     * @param string $tpl
     * @return null
     */
    public function display($tpl = null)
    {
        $checks = $this->getChecks();
        $this->assignRef('checks', $checks);

        parent::display();
    }

    public function getChecks()
    {
        require_once JPATH_COMPONENT.'/helpers/check.php';
        $checks = array();
        $checks[] = Dynamic404HelperCheck::checkDynamic404SystemPlugin();
        $checks[] = Dynamic404HelperCheck::checkRedirectSystemPlugin();
        $checks[] = Dynamic404HelperCheck::checkSefEnabled();
        $checks[] = Dynamic404HelperCheck::checkSefRewritesEnabled();
        $checks[] = Dynamic404HelperCheck::checkAutoRedirectEnabled();
        return $checks;
    }

    /*
     * Method to get an analysis of the template-files and the Dynamic404 patch
     *
     * @access public
     * @param null
     * @return array
     */
    private function getTemplates()
    {
        $result = array();

        require_once(JPATH_ADMINISTRATOR.'/components/com_templates/helpers/templates.php');
        $templates = TemplatesHelper::getTemplateOptions(0);
        foreach ($templates as $template) {

            if (TemplatesHelper::isTemplateDefault($template->directory, 0) 
                || TemplatesHelper::isTemplateAssigned($template->directory)) {

                $errorfile = JPATH_SITE.'/templates/'.$template->directory.'/error.php';

                if (is_file($errorfile) && md5_file($errorfile) == md5_file(DYNAMIC404_ERROR_PATCH)) {
                    $template->message = $this->getMessageText('Patch applied', 
                        'Your template already contains the Dynamic404 error.php-file.', 0);

                } else if (is_file($errorfile)) {
                    $errorcontent = @file_get_contents($errorfile);
                    if (!empty($errorcontent) && stristr($errorcontent, 'dynamic404')) {
                        $template->message = $this->getMessageText('No patch needed',
                            'Your template contains an error.php file, which contains Dynamic404-code. Note that you need to maintain this file yourself.', 0);
                    } else {
                        $template->message = $this->getMessageText('Warning: Existing error.php file',
                            'Your template already contains an error.php file (/templates/%s/error.php). You need to delete that file or patch it yourself.',
                            -1,
                            $template->directory);
                    }
                } else {

                    $template->message = $this->getMessageText('No patch needed', 
                        'Your template does not contain an error.php file, which means that the System Template will be used instead.', 0);
                }

                $result[] = $template;
            }
        }


        return $result;
    }

    /*
     * Helper-method to get a tip-text
     *
     * @access public
     * @param string $title
     * @param string $description
     * @return array
     */
    public function getMessageText($title = null, $description = null, $type = 0, $argument = null)
    {
        $description = JText::sprintf($description, $argument);
        if ($type == -1) {
            $img = '../media/com_dynamic404/images/check-warning.png';
        } else if ($type == -2) {
            $img = '../media/com_dynamic404/images/check-error.png';
        } else {
            $img = '../media/com_dynamic404/images/check-ok.png';
        }

        $html = null;
        $html .= '<img src="'.$img.'" /> &nbsp;';
        $html .= '<span style="line-height:26px;"><strong>'.JText::_($title).'</strong>: ';
        $html .= $description.'</span>';
        return $html;
    }

    /*
     * Helper-method to get a tip-text
     *
     * @access public
     * @param string $status
     * @return array
     */
    public function getStatusIcon($status)
    {
        if ($status == 'warning')
        {
            $img = '../media/com_dynamic404/images/check-warning.png';
        }
        else if ($status == 'error')
        {
            $img = '../media/com_dynamic404/images/check-error.png';

        }
        else
        {
            $img = '../media/com_dynamic404/images/check-ok.png';
        }

        $html = '<img src="'.$img.'" /> &nbsp;';
        return $html;
    }
}
