<?php
/**
 * Joomla! Yireo Library
 *
 * @author Yireo
 * @package YireoLib
 * @copyright Copyright 2014
 * @license GNU Public License
 * @link http://www.yireo.com/
 * @version 0.6.0
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

$add_link = JRoute::_( 'index.php?option=com_dynamic404&view=redirect&task=add&match='. base64_encode($item->request));
?>
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
    <a href="<?php echo $add_link; ?>" title="<?php echo JText::_('COM_DYNAMIC404_VIEW_LOGS_ADD_RULE'); ?>"><?php echo JText::_('COM_DYNAMIC404_VIEW_LOGS_ADD_RULE'); ?></a>
</td>
