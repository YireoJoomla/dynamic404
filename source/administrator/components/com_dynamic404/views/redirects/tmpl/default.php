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

$popup_image = '<img src="../media/com_dynamic404/images/external_link.gif" />';
?>
<form method="post" name="adminForm" id="adminForm">
<table>
<tr>
    <td align="left" width="100%">
        <?php echo JText::_( 'Filter' ); ?>:
        <input type="text" name="<?php echo $this->lists['search_name']; ?>" id="search" value="<?php echo $this->lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
        <button onclick="this.form.submit();"><?php echo JText::_( 'Go' ); ?></button>
        <button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_( 'Reset' ); ?></button>
    </td>
</tr>
</table>
<div id="editcell">
	<table class="adminlist table table-striped" width="600">
	<thead>
		<tr>
			<th width="5">
			    <?php echo JText::_('NUM'); ?>
			</th>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ); ?>);" />
			</th>
			<th class="title">
				<?php echo JHTML::_('grid.sort',  'Match', 'redirect.match', $this->lists['order_Dir'], $this->lists['order'] ); ?>
			</th>
			<th width="200" class="title">
				<?php echo JHTML::_('grid.sort',  'Match Type', 'redirect.type', $this->lists['order_Dir'], $this->lists['order'] ); ?>
			</th>
			<th class="title">
				<?php echo JHTML::_('grid.sort',  'Destination URL', 'redirect.url', $this->lists['order_Dir'], $this->lists['order'] ); ?>
			</th>
			<th class="title">
				<?php echo JHTML::_('grid.sort',  'Description', 'redirect.description', $this->lists['order_Dir'], $this->lists['order'] ); ?>
			</th>
			<th width="5%" class="title">
				<?php echo JHTML::_('grid.sort',  'Published', 'redirect.published', $this->lists['order_Dir'], $this->lists['order'] ); ?>
			</th>
			<th nowrap="nowrap">
				<?php echo JHTML::_('grid.sort',  'Order', 'redirect.ordering', $this->lists['order_Dir'], $this->lists['order'] ); ?>
				<?php echo JHTML::_('grid.order',  $this->items ); ?>
			</th>
			<th width="5">
				<?php echo JHTML::_('grid.sort',  'ID', 'redirect.redirect_id', $this->lists['order_Dir'], $this->lists['order'] ); ?>
            </th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="10">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$i = 0;
    if (!empty($this->items)) {
        foreach ($this->items as $item)
        {
            $edit_link = JRoute::_( 'index.php?option=com_dynamic404&view=redirect&task=edit&cid[]='. $item->redirect_id );
            $item->id = $item->redirect_id;
            $checkbox = $this->checkbox($item, $i);
            $published = JHTML::_('grid.published', $item, $i );
            $ordering = ($this->lists['order'] == 'redirect.ordering');

            $item_match = Dynamic404HelperGUI::getItemMatchLink($item->match, $item->type);
            $item_url = Dynamic404HelperGUI::getItemUrlLink($item->url);
            ?>
            <tr class="<?php echo "row".($i%2); ?>">
                <td>
                    <?php echo $i+1; ?>
                </td>
                <td>
                    <?php echo $checkbox; ?>
                </td>
                <td>
                    <?php
                    if($this->isCheckedOut($item)) {
                        echo $item->match;
                    } else {
                    ?>
                        <a href="<?php echo $edit_link; ?>" title="<?php echo JText::_( 'Edit Tag' ); ?>"><?php echo $item->match; ?></a>
                    <?php } ?>
                    <?php if(!empty($item_match)) { ?><a href="<?php echo $item_match; ?>" target="_new"><?php echo $popup_image; ?></a><?php } ?>
                </td>
                <td>
                    <?php echo Dynamic404HelperGUI::getTypeTitle($item->type); ?>
                </td>
                <td>
                    <?php echo $item->url; ?>
                    <?php if (!empty($item_url)) { ?><a href="<?php echo $item_url; ?>" target="_new"><?php echo $popup_image; ?></a><?php } ?>
                </td>
                <td>
                    <?php echo $item->description; ?>
                </td>
                <td>
                    <?php echo $published; ?>
                </td>
                <td class="order">
                    <span><?php echo $this->pagination->orderUpIcon( $i, true,'orderup', 'Move Up', $ordering ); ?></span>
                    <span><?php echo $this->pagination->orderDownIcon( $i, 0, true, 'orderdown', 'Move Down', $ordering ); ?></span>
                    <?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
                    <input type="text" name="order[]" size="5" value="<?php echo $item->ordering;?>" <?php echo $disabled ?> class="ordering" style="text-align: center" />
                </td>
                <td>
                    <?php echo $item->redirect_id; ?>
                </td>
            </tr>
            <?php
            $i++;
        }
    } else {
        ?>
        <tr>
            <td colspan="10">
                <?php echo JText::_( 'No redirects found' ) ; ?>
            </td>
        </tr>
        <?php
    }
	?>
	</tbody>
	</table>
</div>

<input type="hidden" name="option" value="com_dynamic404" />
<input type="hidden" name="view" value="redirects" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="filter_order" value="<?php echo $this->getFilter('order'); ?>" />
<input type="hidden" name="filter_order_Dir" value="<?php echo $this->getFilter('order_Dir'); ?>" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
