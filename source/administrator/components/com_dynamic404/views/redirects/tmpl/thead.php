<?php
/**
 * Joomla! Yireo Library
 *
 * @author Yireo
 * @package YireoLib
 * @copyright Copyright 2015
 * @license GNU Public License
 * @link http://www.yireo.com/
 * @version 0.6.0
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
?>
<th class="title">
    <?php echo JHTML::_('grid.sort', 'COM_DYNAMIC404_REDIRECT_FIELD_MATCH', 'redirect.match', $this->lists['order_Dir'], $this->lists['order'] ); ?>
</th>
<th width="200" class="title">
    <?php echo JHTML::_('grid.sort', 'COM_DYNAMIC404_REDIRECT_FIELD_TYPE', 'redirect.type', $this->lists['order_Dir'], $this->lists['order'] ); ?>
</th>
<th class="title">
    <?php echo JHTML::_('grid.sort', 'COM_DYNAMIC404_REDIRECT_FIELD_URL', 'redirect.url', $this->lists['order_Dir'], $this->lists['order'] ); ?>
</th>
<th class="title">
    <?php echo JHTML::_('grid.sort', 'COM_DYNAMIC404_REDIRECT_FIELD_DESCRIPTION', 'redirect.description', $this->lists['order_Dir'], $this->lists['order'] ); ?>
</th>
