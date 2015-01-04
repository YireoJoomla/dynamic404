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

$popup_image = '<img src="../media/com_dynamic404/images/external_link.gif" />';
$item_match = Dynamic404HelperGUI::getItemMatchLink($item->match, $item->type);
$item_url = Dynamic404HelperGUI::getItemUrlLink($item->url);
?>
<td>
    <?php if($this->isCheckedOut($item)) : ?>
        <?php echo $this->checkedout($item, $i); ?>
        <span class="checked_out"><?php echo $item->match; ?></span>
    <?php else: ?>
        <a href="<?php echo $item->edit_link; ?>" title="<?php echo JText::_( 'Edit Tag' ); ?>"><?php echo $item->match; ?></a>
    <?php endif; ?>
    <?php if(!empty($item_match)) : ?>
        <a href="<?php echo $item_match; ?>" target="_new"><?php echo $popup_image; ?></a>
    <?php endif; ?>
</td>
<td>
    <?php echo Dynamic404HelperGUI::getTypeTitle($item->type); ?>
</td>
<td>
    <?php echo $item->url; ?>
    <?php if (!empty($item_url)) : ?>
        <a href="<?php echo $item_url; ?>" target="_new"><?php echo $popup_image; ?></a>
    <?php endif; ?>
</td>
<td>
    <?php echo $item->description; ?>
</td>
