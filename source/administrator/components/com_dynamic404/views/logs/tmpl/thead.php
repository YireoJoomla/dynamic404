<?php
/**
 * Joomla! Yireo Library
 *
 * @author    Yireo
 * @package   YireoLib
 * @copyright Copyright 2016
 * @license   GNU Public License
 * @link      https://www.yireo.com/
 * @version   0.6.0
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
?>
<th width="250" class="title">
	<?php echo JHTML::_('grid.sort', 'COM_DYNAMIC404_VIEW_LOGS_REQUEST', 'log.request', $this->lists['order_Dir'], $this->lists['order']); ?>
</th>
<th width="200" class="title">
	<?php echo JHTML::_('grid.sort', 'COM_DYNAMIC404_VIEW_LOGS_HTTP_STATUS', 'log.http_status', $this->lists['order_Dir'], $this->lists['order']); ?>
</th>
<th class="title">
	<?php echo JHTML::_('grid.sort', 'COM_DYNAMIC404_VIEW_LOGS_MESSAGE', 'log.message', $this->lists['order_Dir'], $this->lists['order']); ?>
</th>
<th class="title">
	<?php echo JHTML::_('grid.sort', 'COM_DYNAMIC404_VIEW_LOGS_TIMESTAMP', 'log.timestamp', $this->lists['order_Dir'], $this->lists['order']); ?>
</th>
<th class="title">
	<?php echo JHTML::_('grid.sort', 'COM_DYNAMIC404_VIEW_LOGS_HITS', 'log.hits', $this->lists['order_Dir'], $this->lists['order']); ?>
</th>
<th class="title">
	<?php echo JText::_('JACTION'); ?>
</th>
