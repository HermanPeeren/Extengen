<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * @var \Akeeba\Component\ATS\Site\View\Category\HtmlView $this
 * @var TicketTable                                       $ticket
 * @var bool                                              $showAgo
 * @var bool                                              $showAssigned
 * @var bool                                              $showCategory
 * @var bool                                              $showMy
 * @var bool                                              $showStatus
 * @var bool                                              $showStatusDD
 * @var string                                            $extraRowAttr
 * @var string                                            $extraLinkAttr
 */

use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Akeeba\Component\ATS\Administrator\Table\TicketTable;
use Akeeba\Component\ATS\Site\Helper\RouteHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseDriver;

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

// Set up view template parameters
$showAgo       = $showAgo ?? false;
$showAssigned  = $showAssigned ?? true;
$showCategory  = $showCategory ?? false;
$showMy        = $showMy ?? true;
$showStatus    = $showStatus ?? true;
$showStatusDD  = $showStatusDD ?? true;
$extraRowAttr  = $extraRowAttr ?? '';
$extraLinkAttr = $extraLinkAttr ?? '';

// Get the information used for displaying the ticket
/** @var DatabaseDriver $db */
$db             = Factory::getContainer()->get('DatabaseDriver');
$modifiedBy     = $ticket->modified_by ? $ticket->modified_by : $ticket->created_by;
$creator        = Permissions::getUser($ticket->created_by);
$modifier       = Permissions::getUser($modifiedBy);
$unmodified     = empty($ticket->modified) || ($ticket->modified == $db->getNullDate());
$createdOn      = HTMLHelper::_('ats.date', $ticket->created);
$createdBy      = $creator->username;
$lastOnRaw      = $unmodified ? $ticket->created : $ticket->modified;
$lastOn         = HTMLHelper::_('ats.date', $lastOnRaw);
$lastBy         = $unmodified ? $creator->username : $modifier->username;
$me             = Permissions::getUser();
$mine           = $me->id == $ticket->created_by;
$visibilityIcon = $ticket->public ? 'fa fa-eye' : 'fa fa-eye-slash';
$assigned_to    = $ticket->assigned_to ? Permissions::getUser($ticket->assigned_to)->name : Text::_('COM_ATS_TICKETS_UNASSIGNED');
$visibilityKey  = $ticket->public ? 'COM_ATS_NEWTICKET_LBL_PUBLIC' : 'COM_ATS_NEWTICKET_LBL_PRIVATE';
$isManager      = Permissions::isManager($ticket->catid, $me->id);
$canAssign      = Permissions::canAssignTickets($ticket->catid, $me->id);
$timeSpentLbl   = Text::sprintf('COM_ATS_TICKET_LBL_HAVESPENTXMINUTESTICKET', $ticket->timespent);

$tagsData = isset($ticket->tagsHelper) && ($ticket->tagsHelper instanceof TagsHelper) ?
	$ticket->tagsHelper->getItemTags('com_ats.ticket', $ticket->getId(), true) : null;
?>
<tr id="ats-ticket-<?= (int) $ticket->id ?>" <?= $extraRowAttr ?>>
	<?php if($ticket->priority > 5): ?>
	<td class="low-priority bg-info text-white" style="width: 1em">
		<div class="d-flex flex-column justify-content-center">
			<span class="fa fa-arrow-down hasTooltip"
				  aria-hidden="true"
				  title="<?= Text::_('COM_ATS_LOW_PRIORITY') ?>"></span>
			<span class="visually-hidden"><?= Text::_('COM_ATS_LOW_PRIORITY') ?></span>
		</div>
	</td>
	<?php elseif (($ticket->priority > 0) && ($ticket->priority < 5)): ?>
	<td class="high-priority bg-danger text-white" style="width: 1em">
		<div class="d-flex flex-column justify-content-center">
			<span class="fa fa-arrow-up hasTooltip"
				  aria-hidden="true"
				  title="<?= Text::_('COM_ATS_HIGH_PRIORITY') ?>"></span>
			<span class="visually-hidden"><?= Text::_('COM_ATS_HIGH_PRIORITY') ?></span>
		</div>
	</td>
	<?php else: ?>
	<td style="width: 1em"></td>
	<?php endif ?>

	<td class="ats-badges">
		<div class="d-flex flex-column justify-content-center">
			<?php if($showMy && $mine): ?>
			<div>
				<span class="ats-my-ticket badge bg-secondary hasTooltip">
					<span class="fa fa-user hasTooltip"
						  title="<?= Text::_('COM_ATS_CREATED_BY_ME') ?>"
						  aria-hidden="true"></span>
					<span class="visually-hidden"><?= Text::_('COM_ATS_CREATED_BY_ME') ?></span>
				</span>
			</div>
			<?php endif ?>

			<div>
				<span class="ats-visibility hasTooltip badge bg-<?= $ticket->public ? 'warning' : 'success' ?>">
					<span class="<?= $visibilityIcon ?> hasTooltip"
						  aria-hidden="true"
						  title="<?= Text::_($visibilityKey) ?>"></span>
					<span class="visually-hidden"><?= Text::_($visibilityKey) ?></span>
				</span>
			</div>

			<?php if($showAssigned && $isManager && ($ticket->timespent > 0)): ?>
			<div>
				<span class="badge bg-info hasTooltip time-spent"
					  title="<?= $this->escape($timeSpentLbl) ?>">
					<span aria-hidden="true"><?= $ticket->timespent ?>'</span>
					<span class="visually-hidden"><?= $timeSpentLbl ?></span>
				</span>
			</div>
			<?php endif ?>
		</div>
	</td>

	<td>
		<div class="d-block d-lg-flex flex-lg-row justify-content-between ats-ticket-in-list">
			<div>
				<h3 class="ats-ticket-title h6">
					<?php if ($showAgo): ?>
						<span class="ats-opened-ago badge bg-transparent border border-warning text-warning small">
							<?= HTMLHelper::_('ats.timeago', (new Date($lastOnRaw))->toUnix()) ?>
						</span>
					<?php endif ?>
					<a href="<?= Route::_(RouteHelper::getTicketRoute($ticket->id, $ticket->catid)) ?>"
					   class="text-decoration-none link-primary"
						<?= $extraLinkAttr ?>>
						#<?= (int) $ticket->id ?>: <?= $this->escape($ticket->title) ?>
					</a>
				</h3>

				<?php if (!empty($tagsData)): ?>
					<?php echo LayoutHelper::render('joomla.content.tags', $tagsData); ?>
				<?php endif ?>

				<div class="d-none d-lg-flex flex-row justify-content-between ats-ticket-info-and-button-container">
					<span class="small p-2">
						<?= Text::sprintf('COM_ATS_TICKETS_MSG_CREATED', $createdBy, $createdOn) ?>
					</span>
					<span class="small p-2 text-muted">
						<?= Text::sprintf('COM_ATS_TICKETS_MSG_LASTPOST', $lastBy, $lastOn) ?>
					</span>
				</div>
			</div>

			<?php if ( ($isManager && $showStatusDD) || $showStatus || ($showAssigned && $canAssign) ): ?>
			<div class="flex-shrink ps-3 d-flex flex-row flex-lg-column justify-content-end justify-content-lg-start align-items-lg-end ats-ticket-management-buttons">
				<?php if ($isManager && $showStatusDD): ?>
					<?= LayoutHelper::render('akeeba.ats.common.status_dropdown', [
						'item' => $ticket,
						'class' => 'mb-1 '
					]) ?>
				<?php elseif ($showStatus): ?>
					<?= LayoutHelper::render('akeeba.ats.common.status', [
						'item' => $ticket,
						'class' => 'mb-1 '
					]) ?>
				<?php endif; ?>

				<?php if ($showAssigned && $canAssign): ?>
					<?= LayoutHelper::render('akeeba.ats.common.assigned_dropdown', [
						'item' => $ticket,
						'class' => 'mb-1 ms-2 ms-lg-0 w-100'
					]) ?>
				<?php endif; ?>
			</div>
			<?php endif ?>
		</div>

		<?php if ($showCategory): ?>
			<div class="ats-latest-category small text-muted">
				<span class="fw-bold"><?= Text::_('JCATEGORY') ?>:</span>
				<a href="<?= Route::_(RouteHelper::getCategoryRoute($ticket->catid)) ?>"
				   class="link-secondary"
				>
					<?= $this->escape($ticket->getCategoryName()) ?>
				</a>
			</div>
			<div class="ats-clear"></div>
		<?php endif ?>

		<div class="d-flex flex-row flex-wrap d-lg-none justify-content-between ats-ticket-info-small-sizes">
			<span class="small p-2">
				<?= Text::sprintf('COM_ATS_TICKETS_MSG_CREATED', $createdBy, $createdOn) ?>
			</span>
			<span class="small p-2 text-muted">
				<?= Text::sprintf('COM_ATS_TICKETS_MSG_LASTPOST', $lastBy, $lastOn) ?>
			</span>
		</div>
	</td>
</tr>
