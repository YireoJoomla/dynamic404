<?php
/*
 * Joomla! Editor Button Plugin - Dynamic404
 *
 * @author Yireo (info@yireo.com)
 * @copyright Copyright 2014
 * @license GNU Public License
 * @link http://www.yireo.com
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 * Dynamic404 Editor Button Plugin
 */
class plgButtonDynamic404 extends JPlugin
{
    /*
     * Load the language files
     */
    protected $autoloadLanguage = true;

    /**
     * Method to display the button
     *
     * @param string $name
     */
    public function onDisplay($name, $asset, $author)
    {
        // Load the parameters
        $params = JComponentHelper::getParams('com_dynamic404');

        // Construct the button
        $link = 'index.php?option=com_dynamic404&amp;view=redirect&amp;task=add&amp;modal=1&amp;tmpl=component&amp;formname='.$name.'&amp;asset='.$asset;
		JHtml::_('behavior.modal');
		$button = new JObject();
		$button->set('modal', true);
		$button->set('link', $link);
        $button->set('class', 'btn');
		$button->set('text', JText::_('PLG_EDITORS-XTD_DYNAMIC404_BUTTON'));
		$button->set('name', 'image');
		$button->set('options', "{handler: 'iframe', size: {x: 800, y: 600}}");

		return $button;
    }
}
