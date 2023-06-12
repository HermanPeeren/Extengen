<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView $this */

if (!$this->canDo['admin'] && !$this->canDo['edit'] && !$this->canDo['ticket.assign'] && !($this->canDo['close'] && $this->item->status !== 'C'))
{
	return;
}
?>
<div class="border rounded-2 p-2 my-3 bg-light d-flex justify-content-between ats-pseudotoolbar">
	<?php if ($this->canDo['admin']): ?>
		<?php // === ADMINISTRATOR CLUSTER === ?>
		<div id="atsTicketToolbarAdminCluster">
			<?php // ~~> VISIBILITY TOGGLE <~~ ?>
			<?php if(!$this->item->public): ?>
				<a id="ats-makepublic-ticket" class="btn btn-sm btn-secondary atsAutoCollapse"
				   href="<?= $this->actionUrl('tickets.makepublic') ?>"
				   data-autocollapse-target="ats-makepublic-ticket-label"
				>
					<span class="fa fa-eye" aria-hidden="true"></span>
					<span id="ats-makepublic-ticket-label" class="visually-hidden"><?= Text::_('COM_ATS_TICKET_LBL_MAKEPUBLIC') ?></span>
				</a>
			<?php else: ?>
				<a id="ats-makeprivate-ticket" class="btn btn-sm btn-secondary atsAutoCollapse"
				   href="<?= $this->actionUrl('tickets.makeprivate') ?>"
				   data-autocollapse-target="ats-makeprivate-ticket-label">
					<span class="fa fa-eye-slash" aria-hidden="true"></span>
					<span id="ats-makeprivate-ticket-label" class="visually-hidden"><?= Text::_('COM_ATS_TICKET_LBL_MAKEPRIVATE') ?></span>
				</a>
			<?php endif ?>

			<?php // ~~> PUBLISH TOGGLE <~~ ?>
			<?php if(!$this->item->enabled): ?>
				<a id="ats-publish-ticket" class="btn btn-sm btn-success atsAutoCollapse"
				   href="<?= $this->actionUrl('tickets.publish') ?>"
				   data-autocollapse-target="ats-publish-ticket-label"
				>
					<span class="fa fa-unlock" aria-hidden="true"></span>
					<span id="ats-makepublic-ticket-label" class="visually-hidden"><?= Text::_('COM_ATS_TICKET_LBL_PUBLISH') ?></span>
				</a>
			<?php else: ?>
				<a id="ats-unpublish-ticket" class="btn btn-sm btn-warning atsAutoCollapse"
				   href="<?= $this->actionUrl('tickets.unpublish') ?>"
				   data-autocollapse-target="ats-unpublish-ticket-label"
				>
					<span class="fa fa-lock" aria-hidden="true"></span>
					<span id="ats-unpublish-ticket-label" class="visually-hidden"><?= Text::_('COM_ATS_TICKET_LBL_UNPUBLISH') ?></span>
				</a>
			<?php endif ?>

			<?php // ~~> EDIT <~~ ?>
			<a id="ats-edit-ticket" class="btn btn-sm btn-primary atsAutoCollapse"
			   href="<?= $this->actionUrl('edit', [
				   'view'   => 'ticket',
				   'catid'  => $this->item->catid,
				   'Itemid' => Factory::getApplication()->input->getInt('Itemid', -1),
			   ]) ?>"
			   data-autocollapse-target="ats-edit-ticket-label"
			>
				<span class="fa fa-edit" aria-hidden="true"></span>
				<span id="ats-edit-ticket-label" class="visually-hidden"><?= Text::_('COM_ATS_TICKET_LBL_EDIT') ?></span>
			</a>
		</div>
		<div id="atsTicketToolbarAdminSpacer" class="flex-grow-1 px-2"></div>
		<div id="atsTicketToolbarAdminAssigned">
			<?php // === ASSIGNED MANAGER === ?>
			<?= LayoutHelper::render('akeeba.ats.common.assigned_dropdown', [
				'item' => $this->item,
				'class' => 'm-1'
			]) ?>
		</div>
		<div id="atsTicketToolbarAdminVisibility" class="m-1">
			<?php if ($this->item->public): ?>
				<span class="badge bg-warning p-2">
				<?= Text::_('COM_ATS_TICKETS_PUBLIC_PUBLIC') ?>
			</span>
			<?php else: ?>
				<span class="badge bg-success p-2">
				<?= Text::_('COM_ATS_TICKETS_PUBLIC_PRIVATE') ?>
			</span>
			<?php endif; ?>
		</div>
		<div id="atsTicketToolbarAdminStatus">
			<?php // === STATUS (MANAGERS) === ?>
			<?= LayoutHelper::render('akeeba.ats.common.status_dropdown', [
				'item' => $this->item,
				'class' => 'm-1'
			]) ?>
		</div>
	<?php elseif($this->canDo['edit'] || ($this->canDo['close'] && ($this->item->status !== 'C'))): ?>
		<?php // === USER CLUSTER === ?>
		<div id="atsTicketToolbarUserCluster">
			<a id="ats-user-close" class="btn btn-sm btn-danger"
			   href="<?= Route::_($this->actionUrl('tickets.close')) ?>"
			>
				<span class="fa fa-power-off" aria-hidden="true"></span>
				<span><?= Text::_('COM_ATS_TICKET_LBL_CLOSE') ?></span>
			</a>

			<?php // ~~> EDIT <~~ ?>
			<?php if ($this->canDo['edit']): ?>
			<a id="ats-edit-ticket" class="btn btn-sm btn-primary"
			   href="<?= $this->actionUrl('ticket.edit') ?>"
			>
				<span class="fa fa-edit" aria-hidden="true"></span>
				<span id="ats-edit-ticket-label"><?= Text::_('COM_ATS_TICKET_LBL_EDIT') ?></span>
			</a>
			<?php endif ?>
		</div>
	<?php endif; ?>

	<?php if (!$this->canDo['admin']): ?>
		<div id="atsTicketToolbarUserSpacer" class="flex-grow-1"></div>

		<?php if ($this->canDo['ticket.assign']): ?>
			<div>
				<?= LayoutHelper::render('akeeba.ats.common.assigned_dropdown', [
					'item' => $this->item,
					'class' => 'm-1'
				]) ?>
			</div>
		<?php endif; ?>

		<div id="atsTicketToolbarUserVisibility" class="m-1">
			<?php if ($this->item->public): ?>
				<span class="badge bg-warning p-2">
				<?= Text::_('COM_ATS_TICKETS_PUBLIC_PUBLIC') ?>
			</span>
			<?php else: ?>
				<span class="badge bg-success p-2">
				<?= Text::_('COM_ATS_TICKETS_PUBLIC_PRIVATE') ?>
			</span>
			<?php endif; ?>
		</div>

		<?php // STATUS (NON–MANAGERS) === ?>
		<div id="atsTicketToolbarUserStatus" class="m-1">
			<?= LayoutHelper::render('akeeba.ats.common.status', [
				'item' => $this->item,
			]) ?>
		</div>
	<?php endif; ?>
</div>
