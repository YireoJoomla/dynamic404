<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
?>
<style>
	input.browse {
		font-size: 120%;
		padding: 2px;
	}
	div.description {
		padding-left: 20px;
		padding-bottom: 20px;
		width: 700px;
	}
	div.description pre {
		padding-left: 20px;
	}
</style>

<fieldset class="adminform">
<legend><?php echo JText::_('COM_DYNAMIC404_BROWSE_TEST'); ?></legend>
<div class="description">
<p>
On this page, you can see the result of Joomla trying to fetch data from Joomla internally. The result given below should be free of errors.
</p>
</div>
<table class="admintable" width="100%">
<tr>
    <td class="key">
        <?php echo JText::_('COM_DYNAMIC404_URL'); ?>
    </td>
    <td>
        <form method="post" name="adminForm" id="adminForm">
            <input class="browse" type="text" name="url" value="<?php echo $this->url; ?>" size="60" disabled />
            <input class="submit" type="submit" name="type" value="<?php echo JText::_('COM_DYNAMIC404_BROWSE'); ?>" />
            <input type="hidden" name="option" value="com_dynamic404" />
            <input type="hidden" name="view" value="browse" />
            <input type="hidden" name="task" value="data" />
            <?php echo JHTML::_( 'form.token' ); ?>
        </form>
    </td>
</tr>
<tr>
    <td class="key">
        <?php echo JText::_('COM_DYNAMIC404_HOSTNAME'); ?>
    </td>
    <td>
        <?php echo $this->host; ?> (<?php echo JText::_('COM_DYNAMIC404_IPADDRESS'); ?>: <?php echo gethostbyname($this->host); ?>)
    </td>
</tr>
<tr>
    <td class="key">
        <?php echo JText::_('COM_DYNAMIC404_RESULT'); ?>
    </td>
    <td>
        <iframe src="index.php?option=com_dynamic404&view=browse&task=ajax" width="100%" height="300"></iframe>
    </td>
</tr>
</table>
</fieldset>