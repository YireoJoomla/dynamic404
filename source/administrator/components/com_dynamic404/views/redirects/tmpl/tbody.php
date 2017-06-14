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

/** @var object $item */
/** @var int $i */
/** @var Dynamic404ViewRedirects $this */

$popupImage = '<img src="../media/com_dynamic404/images/external_link.gif" />';
$itemMatch  = $this->guiHelper->getItemMatchLink($item->match, $item->type);
$itemUrl    = $this->guiHelper->getItemUrlLink($item->url);
?>
<td class="break">
	<?php if ($this->isCheckedOut($item)): ?>
		<?php echo $this->checkedout($item, $i); ?>
        <span class="checked_out"><?php echo $item->match; ?></span>
	<?php else: ?>
        <a href="<?php echo $item->edit_link; ?>" title="<?php echo JText::_('Edit Tag'); ?>">
            <?php echo html_entity_decode($item->match); ?>
        </a>
	<?php endif; ?>

	<?php if (!empty($itemMatch)) : ?>
        <a href="<?php echo $itemMatch; ?>" target="_new">
            <?php echo $popupImage; ?>
        </a>
	<?php endif; ?>
</td>
<td>
	<?php echo $this->guiHelper->getTypeTitle($item->type); ?>
</td>
<td class="break">
	<?php echo $item->url; ?>
	<?php if (!empty($itemUrl)) : ?>
        <a href="<?php echo $itemUrl; ?>" target="_new"><?php echo $popupImage; ?></a>
	<?php endif; ?>
</td>
<td>
	<?php echo $item->description; ?>
</td>
