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
use Akeeba\Component\ATS\Administrator\Table\AttachmentTable;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;

/**
 * @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView $this
 * @var \Akeeba\Component\ATS\Administrator\Table\PostTable      $post
 */

// Am I a manager of this category?
$ticket           = $post->getTicket();
$ticketCategoryId = $ticket->catid;
$isManager        = Permissions::isManager($ticketCategoryId);

// Do not show unpublished posts to non–managers
if (!$isManager && !$post->enabled)
{
	return;
}

// Get references to the users involved in this post
$creator      = Permissions::getUser($post->created_by);
$modifier     = Permissions::getUser($post->modified_by ?? $post->created_by);
$me           = Permissions::getUser();
$thisIsMe     = $me->id === $creator->id;
$creatorCanDo = Permissions::getTicketPrivileges($this->item, $creator);
$postCanDo    = Permissions::getPostPrivileges($post);

// Have I been edited / modified?
$modifiedIsNull = empty($post->modified) || ($post->modified == $this->getModel()->getDbo()->getNullDate());
$bogusModified  = $post->modified === $post->created;
$isModified     = !$modifiedIsNull && !$bogusModified;

// Button permissions
$hasPublishButton           = $isManager || $postCanDo['edit.state'];
$hasDeleteButton            = $isManager || $postCanDo['delete'];
$hasEditButton              = $isManager || $postCanDo['edit'] || (Permissions::editGraceTime($post) && $thisIsMe);
$hasAttachmentPublishButton = $hasPublishButton || $postCanDo['attachment.edit.state'];
$hasAttachmentDeleteButton  = $hasDeleteButton || $postCanDo['attachment.delete'];

// The background and border color conveys information about who replied and whether the post is published
$bgColor          = Permissions::isManager($ticketCategoryId, $post->created_by) ? 'bg-success' : 'bg-secondary';
$bgColor          = $post->enabled ? $bgColor : 'bg-warning';
$borderColor      = 'border-' . substr($bgColor, 3);

// Post container classes
$postClasses = implode(' ', array_merge([
		'post-status-' . ($post->enabled ? 'published' : 'unpublished')
], array_map(function ($group_id) {
	return 'ats-post-group-' . $group_id;
}, $creator->getAuthorisedGroups())));

// Get a suitable origin icon
$originIcon = ($post->origin === 'email') ? 'fa fa-envelope' : 'fa fa-globe';

// Get information for user display
$avatarSize    = 64;
$avatarUrl     = Avatar::getUserAvatar($creator->id, $avatarSize);
$userCountry   = CountryHelper::getUserCountry($creator->id);
$userSignature = Permissions::getSignature($creator->id);

// Get the attachments
$attachments = array_filter($post->getAttachments(), function (AttachmentTable $attachment) {
	return Permissions::attachmentVisible($attachment);
});

// Set up the return URL
$returnURL = base64_encode(Uri::getInstance()->toString(['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment']));

// Set up necessary Joomla stuff
HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');
HTMLHelper::_('bootstrap.collapse');

?>
<div class="card mb-3 <?= $borderColor ?> ats-post ats-threaded-item <?= $postClasses ?>" id="p<?= $post->id ?>">
	<h3 class="card-header h5 text-white <?= $bgColor ?> <?= $borderColor ?> ats-post-header">
		<span title="<?= Text::_('COM_ATS_TICKETS_ORIGIN_' . $post->origin, true) ?>" class="hasTooltip ats-post-origin">
			<span class="<?= $originIcon ?>" aria-hidden="true"></span>
		</span>

		<?php // Post date and permalink ?>
		<a href="<?= Uri::current() ?>#p<?= $post->id ?>"
		   class="ms-2 text-white ats-post-date"
		>
		<?= HTMLHelper::_('ats.date', $post->created, Text::_('DATE_FORMAT_LC2')) ?>
		</a>
	</h3>
	<div class="card-body p-0">
		<div class="ats-post-columns-container ats-threaded-item-container row col-12 mx-0 ats-post-columns-container">
			<div class="order-md-1 col-12 col-md-4 col-xl-3 d-flex flex-column justify-content-start p-1 bg-light ats-post-userinfo ats-threaded-item-userinfo">
				<?php // Avatar ?>
				<?php if ($avatarUrl): ?>
				<div class="text-center m-1 ats-post-userinfo-avatar ats-threaded-item-userinfo-avatar">
					<img src="<?= $avatarUrl ?>" alt="" width="<?= $avatarSize ?>" class="img-fluid rounded rounded-3">
				</div>
				<?php endif; ?>

				<?php if ($isManager || $thisIsMe || ($creator->id == -1)): ?>
				<div class="mb-1 fw-bold text-center ats-post-header-fullname ats-threaded-item-header-fullname">
					<?= $this->escape($creator->name) ?>
				</div>
				<?php endif; ?>

				<div class="mb-1 d-flex justify-content-center align-items-center ats-post-userinfo-username ats-threaded-item-userinfo-username">
					<div class="font-monospace flex-shrink-1 ats-post-userinfo-username-label ats-threaded-item-username-label">
						<?php if ($isManager && ($creator->id == $ticket->created_by)): ?>
							<a href="<?= Route::_('index.php?option=com_ats&view=my&user_id=' . $creator->id) ?>"
							>
								<?= $this->escape($creator->username) ?>
							</a>
						<?php else: ?>
							<?= $this->escape($creator->username) ?>
						<?php endif; ?>
					</div>
					<?php if (($isManager || $thisIsMe) && $userCountry): ?>
					<div class="px-2 py-1 flex-shrink-1 ats-post-userinfo-username-flag">
						<span title="<?= HTMLHelper::_('ats.countryName', $userCountry) ?>" class="hasTooltip">
							<?= HTMLHelper::_('ats.countryFlag', $userCountry) ?>
						</span>
					</div>
					<?php endif; ?>
				</div>

				<?php if ($isManager): ?>
					<div class="mb-1 text-center text-muted user-select-all ats-post-header-email">
						<?= $this->escape($creator->email) ?>
					</div>
				<?php endif; ?>

				<?php if ($isManager && ($creator->id > 0)): ?>
				<div class="d-flex justify-content-center ats-post-userinfo-stats">
					<div class="badge bg-dark p-2 m-1 hasTooltip" title="<?= Text::_('COM_ATS_POSTS_LBL_TICKETS', true) ?>">
						<span class="fa fa-ticket-alt" aria-hidden="true"></span>
						<?= Permissions::getTicketsCount($creator->id) ?>
					</div>
					<div class="badge bg-info p-2 m-1 hasTooltip" title="<?= htmlentities(Text::_('COM_ATS_POSTS_LBL_TIMESPENT')) ?>">
						<span class="fa fa-clock" aria-hidden="true"></span>
						<span>
							<?= round(Permissions::getTimeSpentPerUser($creator->id), 2) ?>'
						</span>
					</div>
				</div>
				<?php endif; ?>

				<?php if ($isManager && !($creatorCanDo['admin']) && (ComponentHelper::getParams('com_ats')->get('userGroupsDisplay', 1) == 1)): ?>
				<div class="ats-user-groups my-2 px-2">
					<div class="d-flex flex-wrap justify-content-center gap-2">
						<?php foreach (Permissions::getGroupsByUser($creator->id) as $group): ?>
							<div>
								<span class="badge bg-info rounded-pill px-2"><?= $this->escape($group) ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

				<?php if ($isManager)
				{
					// Let plugins display their own stuff
					$event  = new Event('onATSUserInformationDisplay', [$creator]);
					$result = Factory::getApplication()->getDispatcher()->dispatch($event->getName(), $event);

					foreach (!isset($result['result']) || \is_null($result['result']) ? [] : $result['result'] as $result)
					{
						echo $result ?: '';
					}
				}
				else
				{
					// Let plugins display their own stuff
					$event  = new Event('onATSUserPublicInformationDisplay', [$creator]);
					$result = Factory::getApplication()->getDispatcher()->dispatch($event->getName(), $event);

					foreach (!isset($result['result']) || \is_null($result['result']) ? [] : $result['result'] as $result)
					{
						echo $result ?: '';
					}
				}
				?>
			</div>
			<?php // Post column ?>
			<div class="order-md-0 col-12 col-md-8 col-xl-9 py-2 d-flex flex-column ats-post-body-container ats-threaded-item-body-container">
				<div class="flex-grow-1 mb-2 ats-post-content">
					<div class="mb-2 ats-post-content-html ats-threaded-item-content-html">
						<?= BBCode::parseBBCodeConditional($post->content_html) ?>
					</div>

					<?php if (count($attachments)): ?>
					<div class="border-top border-1 pt-2 mb-3 d-flex flex-column gap-2 ats-post-attachments">
						<h4 class="h5">
							<?= Text::_('COM_ATS_TICKETS_HEADING_ATTACHMENTS') ?>
						</h4>
						<?php foreach($attachments as $attachment): ?>
						<div class="d-flex gap-3 ats-post-attachment">
							<a href="<?= Route::_(sprintf("index.php?option=com_ats&task=attachments.download&cid[]=%s&format=raw&%s=1", $attachment->getId(), Factory::getApplication()->getFormToken())) ?>"
								class="flex-grow-1 ats-post-attachments-filename<?= ($attachment->enabled == 1) ? '' : ' fst-italic text-reset text-muted' ?>"
							>
								<?= $this->escape($attachment->original_filename) ?>
							</a>
							<?php if ($hasAttachmentPublishButton): ?>
								<?php if ($attachment->enabled): ?>
									<a class="btn btn-sm btn-warning"
									   href="<?= Route::_(sprintf('index.php?option=com_ats&task=attachments.unpublish&cid[]=%d&returnurl=%s&%s=1', $attachment->id, $returnURL, Factory::getApplication()->getFormToken())) ?>"
									   >
										<span class="fa fa-lock" aria-hidden="true"></span>
										<?= Text::_('UNPUBLISH') ?>
									</a>
								<?php else: ?>
									<a class="btn btn-sm btn-success"
									   href="<?= Route::_(sprintf('index.php?option=com_ats&task=attachments.publish&cid[]=%d&returnurl=%s&%s=1', $attachment->id, $returnURL, Factory::getApplication()->getFormToken())) ?>"
									>
										<span class="fa fa-unlock" aria-hidden="true"></span>
										<?= Text::_('PUBLISH') ?>
									</a>
								<?php endif; ?>
							<?php endif; ?>
							<?php if ($hasAttachmentDeleteButton): ?>
								<a class="btn btn-sm btn-danger"
								   href="<?= Route::_(sprintf('index.php?option=com_ats&task=attachments.delete&cid[]=%d&returnurl=%s&%s=1', $attachment->id, $returnURL, Factory::getApplication()->getFormToken())) ?>"
								>
									<span class="fa fa-trash" aria-hidden="true"></span>
									<?= Text::_('JACTION_DELETE') ?>
								</a>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>

					<?php if (!empty($userSignature) && !empty(trim(strip_tags($userSignature), '  \n\r\t\0'))): ?>
						<div class="border-top border-2 ats-post-content-signature">
							<?= $userSignature ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="ats-post-footer ats-threaded-item-footer">
					<?php if ($isModified): ?>
						<div class="text-muted ats-post-edits ats-threaded-item-edits my-2">
							<?= Text::sprintf('COM_ATS_TICKETS_MSG_EDITEDBYON', $modifier->username, HTMLHelper::_('ats.date', $post->modified)) ?>
						</div>
					<?php endif; ?>

					<?php if (!$post->enabled): ?>
						<div class="alert alert-warning ats-post-header-unpublished">
							<?= Text::_('COM_ATS_TICKETS_MSG_UNPUBLISHEDPOSTNOTICE') ?>
						</div>
					<?php endif; ?>

					<?php if ($hasPublishButton || $hasEditButton || $hasDeleteButton): ?>
						<div class="d-flex gap-3 ats-post-buttons ats-threaded-item-buttons">
							<?php if ($hasEditButton): ?>
								<a class="btn btn-sm btn-primary"
								   href="<?= Route::_(sprintf('index.php?option=com_ats&task=post.edit&cid[]=%d&returnurl=%s&%s=1', $post->id, $returnURL, Factory::getApplication()->getFormToken())) ?>"
								>
									<span class="fa fa-edit" aria-hidden="true"></span>
									<?= Text::_('JACTION_EDIT') ?>
								</a>
							<?php endif ?>

							<?php if ($hasPublishButton): ?>
								<?php if ($post->enabled): ?>
									<a class="btn btn-sm btn-outline-warning"
									   href="<?= Route::_(sprintf('index.php?option=com_ats&task=posts.unpublish&cid[]=%d&returnurl=%s&%s=1', $post->id, $returnURL, Factory::getApplication()->getFormToken())) ?>"
									>
										<span class="fa fa-lock" aria-hidden="true"></span>
										<?= Text::_('UNPUBLISH') ?>
									</a>
								<?php else: ?>
									<a class="btn btn-sm btn-outline-success"
									   href="<?= Route::_(sprintf('index.php?option=com_ats&task=posts.publish&cid[]=%d&returnurl=%s&%s=1', $post->id, $returnURL, Factory::getApplication()->getFormToken())) ?>"
									>
										<span class="fa fa-unlock" aria-hidden="true"></span>
										<?= Text::_('PUBLISH') ?>
									</a>
								<?php endif; ?>
							<?php endif; ?>

							<?php if ($hasDeleteButton): ?>
								<a class="btn btn-sm btn-outline-danger"
								   href="<?= Route::_(sprintf('index.php?option=com_ats&task=posts.delete&cid[]=%d&returnurl=%s&%s=1', $post->id, $returnURL, Factory::getApplication()->getFormToken())) ?>"
								>
									<span class="fa fa-trash" aria-hidden="true"></span>
									<?= Text::_('JACTION_DELETE') ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>