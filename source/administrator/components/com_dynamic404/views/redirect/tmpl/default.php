<?php 
/**
 * Joomla! Yireo Lib
 *
 * @author Yireo
 * @package YireoLib
 * @copyright Copyright (C) 2014
 * @license GNU Public License
 * @link http://www.yireo.com/
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Set the right image directory for JavaScipt
jimport('joomla.utilities.utility');
?>
<?php echo $this->loadTemplate('script'); ?>

<form method="post" name="adminForm" id="adminForm">

<?php if(JRequest::getInt('modal') == 1): ?>
<jdoc:include type="message" />
<button onclick="Joomla.submitbutton('save');" class="btn btn-small btn-success"><?php echo JText::_('JSUBMIT'); ?></button>
<input type="hidden" name="modal" value="1" />
<?php endif; ?>

<div class="row-fluid">
    <div class="span6">
        <?php echo $this->loadTemplate('fieldset', array('fieldset' => 'source')); ?>
        <?php echo $this->loadTemplate('fieldset', array('fieldset' => 'destination')); ?>
    </div>
    <div class="span6">
        <?php echo $this->loadTemplate('fieldset', array('fieldset' => 'other')); ?>
        <?php echo $this->loadTemplate('fieldset', array('fieldset' => 'params')); ?>
    </div>
</div>
<?php echo $this->loadTemplate('formend'); ?>
</form>
