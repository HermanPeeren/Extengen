<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView $this */

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$posts        = $this->item->posts($this->item->id);
$managerNotes = $this->item->managerNotes($this->item->id);
$cParams      = ComponentHelper::getParams('com_ats');
$noReplies    = $cParams->get('noreplies', 0) == 1;
$isMine       = $this->item->created_by == Factory::getApplication()->getIdentity()->id;
$canViewNotes = $this->canDo['admin'] || $this->canDo['notes.read'];
$canMakeNotes = $this->canDo['admin'] || $this->canDo['notes.create'];

$tagsData = isset($this->item->tagsHelper) && ($this->item->tagsHelper instanceof TagsHelper) ?
	$this->item->tagsHelper->getItemTags('com_ats.ticket', $this->item->getId(), true) : null;
?>

<div class="ats ats-ticket">
	<?= $this->loadPosition('ats-top') ?>
	<?= $this->loadPosition('ats-posts-top') ?>

	<h2 class="h3">
	<span class="badge bg-light text-dark">
		#<?= $this->item->id ?>
	</span>
		<?= $this->escape($this->item->title) ?>
	</h2>

	<?php if (!empty($tagsData)): ?>
		<?php echo LayoutHelper::render('joomla.content.tags', $tagsData); ?>
	<?php endif ?>

	<div class="ats-ticket-view-postedin text-muted">
		<?= Text::sprintf('COM_ATS_TICKET_LBL_POSTEDIN', $this->escape($this->category->title)) ?>
	</div>

	<?= $this->loadTemplate('toolbar') ?>

	<?= $this->loadTemplate('after_title') ?>

	<?php if($this->canDo['admin']): ?>
		<div class="alert alert-info small">
			<div class="ats-total-timespent">
				<?= Text::sprintf('COM_ATS_TICKET_TIMESPENT_MSG', $this->item->timespent) ?>
			</div>
		</div>
	<?php endif ?>

	<?= $this->loadTemplate('visibility_warning') ?>
	<?= $this->loadTemplate('before_posts') ?>

	<?php
	if ($canViewNotes && defined('ATS_PRO') && ATS_PRO)
	{
		echo HTMLHelper::_('uitab.startTabSet', 'com_ats_site_ticket', [
			'recall' => true,
		]);
		echo HTMLHelper::_('uitab.addTab', 'com_ats_site_ticket', 'posts', Text::_('COM_ATS_TICKETS_LEGEND_CONVO'));
	}
	?>

	<section class="ats_ticket_frontend_conversation">
		<?php if (empty($posts)): ?>
			<div class="alert alert-info">
				<span class="icon-info-circle" aria-hidden="true"></span>
				<span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
				<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php endif; ?>
		<?php foreach($posts as $post): ?>
			<?= $this->loadAnyTemplate('default_post', false, [
				'post' => $post,
			]) ?>
		<?php endforeach; ?>
	</section>

	<?= $this->loadTemplate('after_posts') ?>

	<?php // Show a notice for closed tickets to the ticket owner (unless they are an admin) ?>
	<?php if ($isMine && !$this->canDo['admin'] && ($this->item->status === 'C')): ?>
		<div class="alert alert-danger">
			<h4 class="alert-heading">
				<?= Text::_('COM_ATS_POST_LBL_CLOSEDNOTICE_HEAD') ?>
			</h4>
			<p class="mb-0">
				<?= Text::_('COM_ATS_POST_LBL_CLOSEDNOTICE') ?>
			</p>
		</div>
		<?php // Show a notice to non–admins who COULD reply to the ticket when replies are globally disabled ?>
	<?php elseif ($noReplies && $this->canDo['admin']): ?>
		<?= $this->loadPosition('ats-replyarea-overlay') ?>
		<?= $this->loadPosition('ats-noreplies') ?>
		<?= $this->loadPosition('ats-offline') ?>
		<?php // Show the reply area if the user is allowed to post to the ticket and replies are allowed; or if admin ?>
	<?php elseif ($this->canDo['admin'] || ($this->canDo['post'] && !$noReplies)): ?>
		<?= $this->loadAnyTemplate('default_reply', false) ?>
	<?php endif ?>

	<?php
	if ($canViewNotes && defined('ATS_PRO') && ATS_PRO):
		echo HTMLHelper::_('uitab.endTab');
		echo HTMLHelper::_('uitab.addTab', 'com_ats_site_ticket', 'managernotes', Text::_('COM_ATS_TICKETS_LEGEND_MANAGERNOTES'));
		?>

		<section class="ats_ticket_backend_conversation">
			<?php if (empty($managerNotes)): ?>
				<div class="alert alert-info">
					<span class="icon-info-circle" aria-hidden="true"></span>
					<span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
					<?= Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
			<?php endif; ?>
			<?php foreach($managerNotes as $managerNote): ?>
				<?= $this->loadAnyTemplate('default_managernote', false, [
					'managerNote' => $managerNote,
				]) ?>
			<?php endforeach; ?>
		</section>

		<?php if ($canMakeNotes): ?>
		<?= $this->loadAnyTemplate('default_newnote', false) ?>
		<?php endif; ?>

		<?= HTMLHelper::_('uitab.endTab'); ?>
		<?= HTMLHelper::_('uitab.endTabSet'); ?>

	<?php endif; ?>

	<?= $this->loadPosition('ats-posts-bottom') ?>
	<?= $this->loadPosition('ats-bottom') ?>
</div>