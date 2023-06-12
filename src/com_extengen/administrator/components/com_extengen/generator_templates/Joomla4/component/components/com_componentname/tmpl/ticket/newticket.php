<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView $this */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$knownFieldsets = ['basic', 'post'];
$customFieldSets = array_diff(array_keys($this->form->getFieldsets()), $knownFieldsets);
$customFieldSets = array_filter($customFieldSets, function ($fieldSet) {
	return count($this->form->getFieldset($fieldSet)) > 0;
});

$postUrlParams = [
	'option' => 'com_ats',
	'view'   => 'ticket',
	'layout' => 'newticket',
	'catid'  => $this->form->getValue('catid'),
	'format' => 'html',
	'Itemid' => Factory::getApplication()->input->getInt('Itemid', null),
];

if (empty($postUrlParams['catid']))
{
	unset ($postUrlParams['catid']);
}

if (empty($postUrlParams['Itemid']))
{
	unset ($postUrlParams['Itemid']);
}

if (isset($postUrlParams['catid']) && $this->getModel()->isFrontendNewTicketForCategory())
{
	$returnurl               = 'index.php?option=com_ats&view=category&id=' . $postUrlParams['catid'];
	$postUrlParams['return'] = base64_encode($returnurl);
}

$postUrl = 'index.php?' . http_build_query($postUrlParams);

if (isset($postUrlParams['catid']))
{
	unset ($postUrlParams['catid']);
}

if (isset($postUrlParams['return']))
{
	unset ($postUrlParams['return']);
}

$reloadUrl = 'index.php?' . http_build_query($postUrlParams);
$this->document->addScriptOptions('com_ats_newticket_reloadurl', Route::_($reloadUrl, false, Route::TLS_IGNORE, true));
?>
<div class="ats ats-ticket-new">
	<?= $this->loadPosition('ats-top') ?>
	<?= $this->loadPosition('ats-newticket-top') ?>

	<form action="<?= Route::_($postUrl) ?>"
		  method="post"
		  name="adminForm" id="adminForm"
		  class="form-validate my-2"
		  enctype="multipart/form-data"
		  aria-label="<?= Text::_('COM_ATS_TITLE_TICKETS_ADD', true) ?>"
	>
		<input type="hidden" name="id" value="">
		<input type="hidden" name="view" value="ticket">
		<input type="hidden" name="task" value="save">
		<?= HTMLHelper::_('form.token') ?>

		<div class="card mb-2 h-100">
			<h3 class="card-header bg-info text-white">
				<?= Text::_($this->form->getFieldsets()['basic']->label) ?>
			</h3>
			<div class="card-body">
				<?php echo $this->form->renderFieldset('basic'); ?>
			</div>
		</div>

		<?php if (!empty($customFieldSets)): ?>
		<div class="row row-cols-1 <?= (count($customFieldSets) === 1) ? '' : 'row-cols-md-2 g-1' ?> mb-2">
			<?php foreach ($customFieldSets as $fieldSet): ?>
				<div class="col">
					<div class="card mb-2 h-100">
						<h3 class="card-header bg-dark text-white">
							<?= Text::_($this->form->getFieldsets()[$fieldSet]->label) ?>
						</h3>
						<div class="card-body">
							<?php echo $this->form->renderFieldset($fieldSet); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ($this->form->getValue('catid')): ?>
		<div class="card mb-2 h-100">
			<h3 class="card-header bg-secondary text-white visually-hidden">
				<?= Text::_('COM_ATS_TITLE_POSTS_ADD') ?>
			</h3>
			<div class="card-body">
				<div class="card-body">
					<?php echo $this->form->renderFieldset('post'); ?>
				</div>
			</div>

			<?php if ($this->canDo['attachment'] && !empty($allowedExtensions)): ?>
				<div class="control-group">
					<div class="control-label d-none d-md-block">
						&nbsp;
					</div>
					<div class="controls">
						<div class="alert alert-info">
							<?= Text::sprintf('COM_ATS_NEWTICKET_LBL_ATTACHMENT_ALLOWED_EXTENSIONS', implode(', ', $allowedExtensions)) ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="control-group">
				<div class="control-label d-none d-md-block">
				</div>
				<div class="controls p-2">
					<button type="submit"
							class="btn btn-lg btn-primary w-100">
						<span class="fa fa-paper-plane" aria-hidden="true"></span>
						<?= Text::_('COM_ATS_TICKET_LBL_SEND_TICKET') ?>
					</button>
				</div>
			</div>
		</div>
		<?php else: ?>
		<div class="alert alert-info">
			<h4 class="alert-heading">
				<?= Text::_('COM_ATS_TICKETS_LBL_NEWTICKET_NOCATEGORY_HEAD') ?>
			</h4>
			<p>
				<?= Text::_('COM_ATS_TICKETS_LBL_NEWTICKET_NOCATEGORY_BODY') ?>
			</p>
		</div>
		<?php endif; ?>
	</form>

	<?= $this->loadPosition('ats-newticket-bottom') ?>
	<?= $this->loadPosition('ats-bottom') ?>
</div>