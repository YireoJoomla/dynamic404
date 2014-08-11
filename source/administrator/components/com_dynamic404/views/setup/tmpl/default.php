<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright (C) 2014 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
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
        </tbody>
    </table>
</fieldset>

<input type="hidden" name="option" value="com_dynamic404" />
<input type="hidden" name="view" value="setup" />
<input type="hidden" name="task" value="" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
