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
    <legend><?php echo JText::_( 'System Plugin' ); ?></legend>
    <table class="table table-striped">
        <tr>
            <td width="200" align="left">
                Dynamic404 System Plugin
            </td>
            <td>
                <?php if ($this->plugin_check_d404 == 'enabled') { ?>
                    <?php echo $this->getMessageText('Enabled', 'Plugin is enabled.', 0); ?>

                <?php } else if ($this->plugin_check_d404 == 'disabled') { ?>
                    <?php echo $this->getMessageText('Disabled', 'Plugin is not enabled yet.', -1); ?>
                    <a href="index.php?option=com_dynamic404&task=pluginD404">Click here to enable</a>

                <?php } else { ?>
                    <?php echo $this->getMessageText('Not installed', 'Plugin is not installed yet.', -2); ?>
                    <a href="http://www.yireo.com/software/joomla-extensions/dynamic404/downloads">Download from Yireo site</a>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td width="200" align="left">
                Redirect System Plugin
            </td>
            <td>
                <?php if ($this->plugin_check_redirect == 'enabled') { ?>
                    <?php echo $this->getMessageText('Enabled', 'Plugin is conflicting.', -2); ?>
                    <a href="index.php?option=com_dynamic404&task=pluginRedirect">Click here to disable</a>

                <?php } else if ($this->plugin_check_redirect == 'disabled') { ?>
                    <?php echo $this->getMessageText('Disabled', 'Plugin is not conflicting.', 0); ?>
                <?php } ?>
            </td>
        </tr>
    </table>
</fieldset>

<input type="hidden" name="option" value="com_dynamic404" />
<input type="hidden" name="view" value="setup" />
<input type="hidden" name="task" value="" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
