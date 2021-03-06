<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        https://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// GUI elements
JHTML::_('behavior.tooltip');
?>
<form method="post" name="adminForm" id="adminForm" action="index.php">
<fieldset class="adminform">
    <legend><?php echo JText::_('COM_DYNAMIC404_CHECK_FIELDSET'); ?></legend>
    <table class="adminlist table table-striped">
        <thead>
            <tr>
                <th width="25%"><?php echo JText::_('COM_DYNAMIC404_CHECK_COLUMN_LABEL'); ?></th>
                <th width="9%"><?php echo JText::_('COM_DYNAMIC404_CHECK_COLUMN_STATUS'); ?></th>
                <th><?php echo JText::_('COM_DYNAMIC404_CHECK_COLUMN_MESSAGE'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($this->checks as $check) : ?>
        <tr>
            <td><?php echo JText::_($check['label']); ?></td>
            <td><?php echo $this->getStatusIcon($check['status']); ?></td>
            <td><?php echo $check['message']; ?></td>
        </tr>
        <?php endforeach; ?>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td><a href="index.php?option=com_dynamic404&view=browse"><?php echo JText::_('COM_DYNAMIC404_BROWSE_TEST'); ?></a></td>
		</tr>
        </tbody>
    </table>
</fieldset>

<input type="hidden" name="option" value="com_dynamic404" />
<input type="hidden" name="view" value="setup" />
<input type="hidden" name="task" value="" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
