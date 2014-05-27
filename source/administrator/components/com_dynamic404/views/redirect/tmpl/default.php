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

JHTML::_('behavior.tooltip');
?>

<style type="text/css">
	table.paramlist td.paramlist_key {
		width: 92px;
		text-align: left;
		height: 30px;
	}
</style>

<form method="post" name="adminForm" id="adminForm">
<div>
    <div class="width-60 fltlft">
	<fieldset class="adminform">
		<legend><?php echo JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELDSET_SOURCE'); ?></legend>
		<table class="admintable" width="100%">
		<tr>
			<td width="100" align="right" class="key">
				<label for="match">
					<?php echo JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELD_MATCH'); ?>:
				</label>
			</td>
			<td class="value">
				<input class="text_area" type="text" name="match" id="match" size="60" maxlength="250" value="<?php echo $this->item->match;?>" />
                <?php echo $this->getMessageText(null, JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELD_MATCH_DESC')); ?>
			</td>
		</tr>
		<tr>
			<td width="100" align="right" class="key">
				<label for="type">
				    <?php echo JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELD_MATCH_TYPE'); ?>:
				</label>
			</td>
			<td class="value">
				<?php echo $this->lists['type']; ?>
			</td>
		</tr>
        </table>
    </fieldset>
	<fieldset class="adminform">
		<legend><?php echo JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELDSET_DESTINATION'); ?></legend>
		<table class="admintable" width="100%">
		<tr>
			<td width="100" align="right" class="key">
				<label for="url">
					<?php echo JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELD_URL'); ?>:
				</label>
			</td>
			<td class="value">
				<input class="text_area" type="text" name="url" id="url" size="60" maxlength="250" value="<?php echo $this->item->url;?>" />
                <?php echo $this->getMessageText(null, JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELD_URL_DESC')); ?>
			</td>
		</tr>
		<tr>
			<td width="100" align="right" class="key">
				<label for="http_status">
				    <?php echo JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELD_HTTP_STATUS'); ?>:
				</label>
			</td>
			<td class="value">
				<?php echo $this->lists['http_status']; ?>
			</td>
		</tr>
        </table>
    </fieldset>
	<fieldset class="adminform">
		<legend><?php echo JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELDSET_OTHER'); ?></legend>
		<table class="admintable" width="100%">
		<tr>
			<td width="100" align="right" class="key">
				<label for="description">
					<?php echo JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELD_DESCRIPTION'); ?>:
				</label>
			</td>
			<td class="value">
				<input class="text_area" type="text" name="description" id="description" size="32" maxlength="250" value="<?php echo $this->item->description;?>" />
                <?php echo $this->getMessageText(null, JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELD_DESCRIPTION')); ?>
			</td>
		</tr>
        <tr>
            <td valign="top" align="right" class="key">
                <label for="published">
                    <?php echo JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELD_PUBLISHED'); ?>:
                </label>
            </td>
            <td class="value">
                <?php echo $this->lists['published']; ?>
            </td>
        </tr>
        <tr>
            <td valign="top" align="right" class="key">
                <label for="ordering">
                    <?php echo JText::_('COM_DYNAMIC_REDIRECT_VIEW_SOURCE_FIELD_ORDERING'); ?>:
                </label>
            </td>
            <td class="value">
                <?php echo $this->lists['ordering']; ?>
            </td>
        </tr>
	</table>
	</fieldset>
    </div>
    <div class="width-40 fltlft">
        <?php echo $this->loadTemplate('fieldset', array('fieldset' => 'params')); ?>
    </div>
</div>
<div class="clr"></div>

<input type="hidden" name="option" value="com_dynamic404" />
<input type="hidden" name="view" value="redirect" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="cid[]" value="<?php echo $this->item->redirect_id; ?>" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
