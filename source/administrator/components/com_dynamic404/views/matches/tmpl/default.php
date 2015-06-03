<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
?>
<form action="index.php?option=com_dynamic404&view=matches" method="post" name="adminForm" id="adminForm">

	<div class="clearfix">
		<div class="js-stools-container-bar">
			<label for="filter_search" class="element-invisible" aria-invalid="false"><?php echo JText::_('LIB_YIREO_VIEW_FORM_FIELDSET_SOURCE'); ?></label>
			<div class="btn-wrapper input-append">
				<input type="text" name="url" id="filter_search" value="<?php echo $this->url; ?>" placeholder="<?php echo JText::_('LIB_YIREO_VIEW_FORM_FIELDSET_SOURCE'); ?>" class="input-xxlarge">
				<button type="submit" class="btn hasTooltip" title="" data-original-title="<?php echo JText::_('LIB_YIREO_VIEW_FORM_FIELDSET_SOURCE'); ?>">
					<i class="icon-search"></i>
				</button>
			</div>
		</div>
	</div>

	<table class="table table-striped" id="matchList">
		<thead>
			<th class="nowrap hidden-phone">
				<?php echo JText::_('COM_DYNAMIC404_REDIRECT_FIELD_TITLE'); ?>
			</th>
			<th class="nowrap hidden-phone">
				<?php echo JText::_('COM_DYNAMIC404_REDIRECT_FIELD_MATCH'); ?>
			</th>
			<th class="nowrap hidden-phone">
				<?php echo JText::_('COM_DYNAMIC404_REDIRECT_FIELD_TYPE'); ?>
			</th>
			<th class="nowrap hidden-phone">
				<?php echo JText::_('COM_DYNAMIC404_REDIRECT_FIELD_NOTE'); ?>
			</th>
			<th class="nowrap hidden-phone">
				<?php echo JText::_('COM_DYNAMIC404_REDIRECT_PARAM_RATING'); ?>
			</th>
		</thead>
		<tbody>
			<?php foreach($this->matches as $match) : ?>
				<tr>
					<td>
						<?php echo $match->title; ?>
					</td>
					<td>
						<a href="<?php echo $match->url; ?>"><?php echo $match->uri; ?></a>
					</td>
					<td>
						<?php echo $match->type; ?>
					</td>
					<td>
						<?php echo $match->match_note; ?>
					</td>
					<td>
						<?php echo $match->rating; ?>%
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</form>
