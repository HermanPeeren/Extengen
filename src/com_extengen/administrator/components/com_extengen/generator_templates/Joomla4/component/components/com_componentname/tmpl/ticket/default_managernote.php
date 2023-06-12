<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Helper\Avatar;
use Akeeba\Component\ATS\Administrator\Helper\BBCode;
use Akeeba\Component\ATS\Administrator\Helper\CountryHelper;
use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView   $this
 * @var \Akeeba\Component\ATS\Administrator\Table\ManagernoteTable $managerNote
 */

// Am I a manager of this category?
$ticketCategoryId = $managerNote->getTicket()->catid;

// Get references to the users involved in this managerNote
$creator  = Permissions::getUser($managerNote->created_by);
$modifier = Permissions::getUser($managerNote->modified_by ?? $managerNote->created_by);
$me       = Permissions::getUser();
$thisIsMe = $me->id === $creator->id;

// What am I allowed to do?
$canEdit   = $this->canDo['admin'] || $this->canDo['notes.edit'] || ($thisIsMe && $this->canDo['notes.edit.own']);
$canDelete = $this->canDo['admin'] || $this->canDo['delete'] || $this->canDo['notes.delete'];

// Have I been edited / modified?
$modifiedIsNull = empty($managerNote->modified) || ($managerNote->modified == $this->getModel()->getDbo()->getNullDate());
$bogusModified  = $managerNote->modified === $managerNote->created;
$isModified     = !$modifiedIsNull && !$bogusModified;

// The background and border color conveys information about who replied and whether the post is published
$bgColor          = $thisIsMe ? 'bg-success' : 'bg-secondary';
$borderColor      = 'border-' . substr($bgColor, 3);

// Post container classes
$postClasses = implode(' ', array_map(function ($group_id) {
	return 'ats-managernote-group-' . $group_id;
}, $creator->getAuthorisedGroups()));

// Get information for user display
$avatarSize    = 64;
$avatarUrl     = Avatar::getUserAvatar($creator->id, $avatarSize);
$userCountry   = CountryHelper::getUserCountry($creator->id);
$userSignature = Permissions::getSignature($creator->id);

// Set up necessary Joomla stuff
$returnURL = base64_encode(Uri::getInstance()->toString());

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
HTMLHelper::_('bootstrap.collapse');

?>
<div class="card mb-3 <?= $borderColor ?> ats-managernote ats-threaded-item <?= $postClasses ?>" id="n<?= $managerNote->id ?>">
	<h3 class="card-header h5 text-white <?= $bgColor ?> <?= $borderColor ?> ats-managernote-header">
		<?php // Post date and permalink ?>
		<a href="<?= Uri::current() ?>#n<?= $managerNote->id ?>"
		   class="text-white ats-managernote-date"
		>
			<?= HTMLHelper::_('ats.date', $managerNote->created, Text::_('DATE_FORMAT_LC2')) ?>
		</a>
	</h3>
	<div class="card-body p-0">
		<div class="ats-managernote-columns-container ats-threaded-item-container row col-12 mx-0 ats-managernote-columns-container">
			<div class="order-md-1 col-12 col-md-4 col-xl-3 d-flex flex-column justify-content-start p-1 bg-light ats-managernote-userinfo ats-threaded-item-userinfo">
				<?php // Avatar ?>
				<?php if ($avatarUrl): ?>
					<div class="text-center m-1 ats-managernote-userinfo-avatar ats-threaded-item-userinfo-avatar">
						<img src="<?= $avatarUrl ?>" alt="" width="<?= $avatarSize ?>" class="img-fluid rounded rounded-3">
					</div>
				<?php endif; ?>

				<div class="mb-1 fw-bold text-center ats-managernote-header-fullname ats-threaded-item-header-fullname">
					<?= $this->escape($creator->name) ?>
				</div>

				<div class="mb-1 d-flex justify-content-center ats-managernote-userinfo-username ats-threaded-item-userinfo-username">
					<div class="font-monospace flex-shrink-1 ats-threaded-item-username-label">
						<?= $this->escape($creator->username) ?>
					</div>
				</div>
			</div>
			<?php // Manager note column ?>
			<div class="order-md-0 col-12 col-md-8 col-xl-9 py-2 d-flex flex-column ats-managernote-body-container ats-threaded-item-body-container">
				<div class="flex-grow-1 mb-2 ats-managernote-content">
					<div class="mb-2 ats-managernote-content-html ats-threaded-item-content-html">
						<?= BBCode::parseBBCodeConditional($managerNote->note_html) ?>
					</div>
				</div>

				<div class="ats-managernote-footer ats-threaded-item-footer">
					<?php if ($isModified): ?>
						<div class="text-muted ats-managernote-edits ats-threaded-item-edits">
							<?= Text::sprintf('COM_ATS_TICKETS_MSG_EDITEDBYON', $modifier->username, HTMLHelper::_('ats.date', $managerNote->modified)) ?>
						</div>
					<?php endif; ?>

					<?php if ($canEdit || $canDelete): ?>
					<div class="d-flex gap-3 ats-managernote-buttons ats-threaded-item-buttons">
						<?php if ($canEdit): ?>
						<a class="btn btn-sm btn-primary"
						   href="<?= Route::_(sprintf('index.php?option=com_ats&task=managernote.edit&cid[]=%d&returnurl=%s&%s=1', $managerNote->id, $returnURL, Factory::getApplication()->getFormToken())) ?>"
						>
							<span class="fa fa-edit" aria-hidden="true"></span>
							<?= Text::_('JACTION_EDIT') ?>
						</a>
						<?php endif ?>

						<?php if ($canDelete): ?>
						<a class="btn btn-sm btn-outline-danger"
						   href="<?= Route::_(sprintf('index.php?option=com_ats&task=managernotes.delete&cid[]=%d&returnurl=%s&%s=1', $managerNote->id, $returnURL, Factory::getApplication()->getFormToken())) ?>"
						>
							<span class="fa fa-trash" aria-hidden="true"></span>
							<?= Text::_('JACTION_DELETE') ?>
						</a>
						<?php endif ?>
					</div>
					<?php endif ?>
				</div>
			</div>
		</div>
	</div>
</div>