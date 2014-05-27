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
defined('_JEXEC') or die( 'Restricted access' );

?>
<?php if(!empty($this->article)) : ?>
    <h2><?php echo $this->article->title; ?></h2>
    <?php echo $this->article->text; ?>
<?php else: ?>
    <h2><?php echo JText::_('COM_DYNAMIC404_NOT_FOUND'); ?></h2>
<?php endif; ?>

<p>
    <?php if (!empty($this->matches)) : ?>
    <?php echo JText::_('COM_DYNAMIC404_MATCHES_FOUND'); ?>:
    <ul>
        <?php foreach ($this->matches as $item) : ?>
        <li><a href="<?php echo $item->url; ?>"><?php echo $item->name; ?></a> (<?php echo $item->rating; ?>%)</li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
        <?php echo JText::_('COM_DYNAMIC404_NO_MATCHES_FOUND'); ?>
    <?php endif; ?>
</p>

<p>
    <?php echo JText::_('COM_DYNAMIC404_ALTERNATIVES'); ?>:
	<ul>
		<li><a href="<?php echo JURI::base(); ?>" title="<?php echo JText::_('COM_DYNAMIC404_HOME'); ?>"><?php echo JText::_('COM_DYNAMIC404_HOME'); ?></a></li>
		<li><a href="<?php echo JRoute::_( 'index.php?option=com_search&searchword='.$this->urilast ); ?>" title="<?php echo JText::_('COM_DYNAMIC404_SEARCH'); ?>"><?php echo JText::_('COM_DYNAMIC404_SEARCH_FOR'); ?>: <?php echo $this->urilast; ?></a></li>
	</ul>
</p>
<p>
    <?php echo JText::_('COM_DYNAMIC404_PROBLEMS'); ?>
</p>
