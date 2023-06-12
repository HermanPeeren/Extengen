<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView $this */

$allowedExtensions = Permissions::getAllowedExtensions();

// Only show overlaid modules to non–managers
$overlays = $this->canDo['admin'] ? null : $this->loadPosition('ats-replyarea-overlay', ['style' => 'card']);

?>
<form action="<?= Route::_('index.php?option=com_ats&task=post.save&id=0') ?>"
      method="post"
	  enctype="multipart/form-data"
      name="replyForm"
      id="replyForm"
      class="form-validate"
      aria-label="<?= Text::_('COM_ATS_POSTS_HEADING_REPLYAREA', true) ?>"
>
	<input type="hidden" name="returnurl" value="<?= base64_encode(Uri::getInstance()->toString(['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'])) ?>">
	<?= HTMLHelper::_('form.token') ?>

	<div class="mt-5 pt-3 mb-2 border-top border-2 border-dark">
		<h3 class="h1 my-3">
			<?= Text::_('COM_ATS_POSTS_HEADING_REPLYAREA') ?>
		</h3>

		<?php if ($this->item->public): ?>
			<div class="alert alert-warning">
				<h4 class="alert-heading">
					<span class="fa fa-eye me-1" aria-hidden="true"></span>
					<?= Text::_('COM_ATS_POST_LBL_PUBNOTE_PUBLIC_HEAD') ?>
				</h4>
				<p class="mb-0">
					<?= Text::_('COM_ATS_POST_LBL_PUBNOTE_PUBLIC') ?>
				</p>
			</div>
		<?php else: ?>
			<div class="alert alert-success">
				<h4 class="alert-heading">
					<span class="fa fa-eye-slash me-1" aria-hidden="true"></span>
					<?= Text::_('COM_ATS_POST_LBL_PUBNOTE_PRIVATE_HEAD') ?>
				</h4>
				<p class="mb-0">
					<?= Text::_('COM_ATS_POST_LBL_PUBNOTE_PRIVATE') ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ($this->canDo['admin'] && ($this->item->status === 'C')): ?>
		<div class="alert alert-danger">
			<h4 class="alert-heading">
				<?= Text::_('COM_ATS_POST_LBL_CLOSEDNOTICE_HEAD') ?>
			</h4>
			<p class="mb-0">
				<?= Text::_('COM_ATS_POST_LBL_CLOSEDNOTICE_ADMIN') ?>
			</p>
		</div>
		<?php elseif($this->canDo['admin'] && !empty($this->item->assigned_to) && ($this->item->assigned_to != Permissions::getUser()->id)): ?>
		<div class="alert alert-warning">
			<h4 class="alert-heading">
				<span class="fa fa-exclamation-triangle me-1" aria-hidden="true"></span>
				<?= Text::_('WARNING') ?>
			</h4>
			<p class="mb-0">
				<?= Text::sprintf('COM_ATS_TICKET_ALREADY_ASSIGNED_WARN', Permissions::getUser($this->item->assigned_to)->name) ?>
			</p>
		</div>
		<?php endif ?>

		<?php if (!empty($overlays)): ?>
		<div id="atsReplyOverlays" class="p-2 container">
			<?= $overlays ?>
			<div class="row mx-2 my-3">
				<button type="button"
						class="col-12 col-md-6 offset-md-3 btn btn-primary atsReplyOverlayAcknowledge collapsed"
						data-target="atsReplyArea" data-container="atsReplyOverlays"
						aria-expanded="true" aria-controls="atsReplyArea"
				>
					<span class="fa fa-chevron-right" aria-hidden="true"></span>
					<?= Text::_('COM_ATS_TICKET_LBL_OVERLAY_ACKNOWLEDGE') ?>
				</button>
			</div>
		</div>
		<?php endif; ?>

		<div id="atsReplyArea" class="<?= empty($overlays) ? '' : 'd-none' ?>">
			<?php foreach (array_keys($this->replyForm->getFieldsets()) as $fieldSet)
			{
				echo $this->replyForm->renderFieldset($fieldSet);
			} ?>

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
					&nbsp;
				</div>
				<div class="controls">
					<button type="submit"
							class="btn btn-lg btn-primary w-100">
						<span class="fa fa-comment-dots" aria-hidden="true"></span>
						<?= Text::_('COM_ATS_POSTS_MSG_POST') ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</form>
