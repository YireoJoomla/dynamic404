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

// GUI elements
JHTML::_('behavior.tooltip');
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
			    <?php echo JText::_( 'ID' ); ?>
			</th>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ); ?>);" />
			</th>
			<th width="250" class="title">
				<?php echo JHTML::_('grid.sort',  'Request', 'log.request', $this->lists['order_Dir'], $this->lists['order'] ); ?>
			</th>
			<th class="title">
				<?php echo JHTML::_('grid.sort',  'Timestamp', 'log.timestamp', $this->lists['order_Dir'], $this->lists['order'] ); ?>
			</th>
			<th class="title">
				<?php echo JHTML::_('grid.sort',  'Hits', 'log.hits', $this->lists['order_Dir'], $this->lists['order'] ); ?>
			</th>
			<th class="title">
				<?php echo JText::_('Action'); ?>
			</th>
			<th width="5">
				<?php echo JHTML::_('grid.sort',  'ID', 'log.log_id', $this->lists['order_Dir'], $this->lists['order'] ); ?>
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
        foreach ($this->items as $item) {

            $add_link = JRoute::_( 'index.php?option=com_dynamic404&view=redirect&task=add&match='. base64_encode($item->request));
            $checkbox = $this->checkbox($item, $i);

            if(strlen($item->request) > 150) $item->request = preg_replace('/([^\ ]{100})/', '\1 ', $item->request);
            ?>
            <tr class="<?php echo "row".($i%2); ?>">
                <td>
                    <?php echo $i+1; ?>
                </td>
                <td>
                    <?php echo $checkbox; ?>
                </td>
                <td>
                    <?php echo $item->request; ?>
                </td>
                <td>
                    <?php echo ($item->timestamp > 0) ? date('d-M-Y H:i:s', $item->timestamp) : ''; ?>
                </td>
                <td>
                    <?php echo $item->hits; ?>
                </td>
                <td>
                    <a href="<?php echo $add_link; ?>" title="<?php echo JText::_( 'Add Rule' ); ?>"><?php echo JText::_( 'Add rule'); ?></a>
                </td>
                <td>
                    <?php echo $item->log_id; ?>
                </td>
            </tr>
            <?php
            $i++;
        }
    } else {
        ?>
        <tr>
            <td colspan="10">
                <?php echo JText::_( 'No logs found' ) ; ?>
            </td>
        </tr>
        <?php
    }
	?>
	</tbody>
	</table>
</div>

<input type="hidden" name="option" value="com_dynamic404" />
<input type="hidden" name="view" value="logs" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="filter_order" value="<?php echo $this->getFilter('order'); ?>" />
<input type="hidden" name="filter_order_Dir" value="<?php echo $this->getFilter('order_Dir'); ?>" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>
