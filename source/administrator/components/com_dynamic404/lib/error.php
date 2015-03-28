<?php
/**
 * Joomla! component Dynamic404
 *
 * @author      Yireo (http://www.yireo.com/)
 * @package     Dynamic404
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     GNU Public License (GPL) version 3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @link        http://www.yireo.com/
 */

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Include the 404 Helper
require_once JPATH_ADMINISTRATOR.'/components/com_dynamic404/helpers/helper.php';

// Instantiate the helper with the argument of how many matches to show
if($this instanceof Dynamic404Helper) {
    $helper = $this;
} else {
    $helper = new Dynamic404Helper($this->error);
}

// Parse empty variables and/or objects
if(empty($this->error)) $this->error = $helper->getErrorObject();
if(empty($this->title)) $this->title = JText::_('COM_DYNAMIC404_NOT_FOUND');
$errorCode = $helper->getErrorCode($this->error);

// Get the possible matches
$matches = $helper->getMatches();

// Get the last segment - nice for searching
$search = $helper->getLast();

// Load the article
$article = $helper->getArticle($errorCode);
if(!empty($article)) {
    $this->title = $article->title;
}

// Fetch additional properties
$errorMsg = (is_object($this->error) && isset($this->error->message)) ? $this->error->message : $this->title; 
$additionalErrors = $this->getAdditionalErrors();

// If no redirect is available or performed, show the page below
?>
<!DOCTYPE HTML>
<html>
<head>
	<title><?php echo $errorCode ?> - <?php echo $this->title; ?></title>
	<link rel="stylesheet" href="<?php echo JURI::base(); ?>/templates/system/css/error.css" type="text/css" />
</head>
<body>
	<div align="center">
		<div id="outline">
		<div id="errorboxoutline">
			<div id="errorboxheader"><?php echo $errorCode ?> - <?php echo $errorMsg ?></div>
			<div id="errorboxbody">
            <?php if(!empty($article)) : ?>
                <?php echo $article->text; ?>
            <?php endif; ?>
            <p>
                <?php if(!empty($matches)): ?>
                <?php echo JText::_('COM_DYNAMIC404_MATCHES_FOUND'); ?>:
                <ul>
                    <?php foreach($matches as $item) { ?>
                    <?php if(!empty($item->match_note)): ?><!-- Match note: "<?php echo $item->match_note ?>" --><?php endif; ?>
                    <li><a href="<?php echo $item->url; ?>"><?php echo $item->name; ?></a> (<?php echo $item->rating; ?>%)</li>
                    <?php } ?>
                </ul>
                <?php else: ?>
                    <?php echo JText::_('COM_DYNAMIC404_NO_MATCHES_FOUND'); ?>
                <?php endif; ?>
            </p>
			<p>
                <?php echo JText::_('COM_DYNAMIC404_ALTERNATIVES'); ?>:
				<ul>
					<li><a href="<?php echo JURI::base(); ?>" title="<?php echo JText::_('COM_DYNAMIC404_HOME'); ?>"><?php echo JText::_('COM_DYNAMIC404_HOME'); ?></a></li>
					<li><a href="<?php echo JRoute::_( 'index.php?option=com_search&searchword='.$search); ?>" title="<?php echo JText::_('COM_DYNAMIC404_SEARCH');
					?>"><?php echo JText::_('COM_DYNAMIC404_SEARCH_FOR'); ?>: "<?php echo $search; ?>"</a></li>
				</ul>
			</p>

            <?php if (!empty($additionalErrors)) : ?>
			    <p><?php echo JText::_('COM_DYNAMIC404_ADDITIONAL_ERRORS'); ?></p>
                <ul>
                    <?php foreach($additionalErrors as $additionalError) : ?>
                    <li><?php echo $additionalError; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

			<p><?php echo JText::_('COM_DYNAMIC404_PROBLEMS'); ?></p>
			<div id="techinfo">
			<p><?php echo $errorMsg; ?></p>
			<p>
				<?php if(isset($this->debug) && $this->debug == true) : ?>
					<?php echo $this->renderBacktrace(); ?>
				<?php endif; ?>
			</p>
			</div>
			</div>
		</div>
		</div>
	</div>
</body>
</html>
