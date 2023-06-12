<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Helper\ComponentParams;
use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \Akeeba\Component\ATS\Administrator\View\Tickets\HtmlView $this */

$published  = $this->state->get('filter.enabled');
$categories = $this->state->get('filter.catid', []);
$isManager  = Permissions::isManager();
$isManager  = $isManager && array_reduce($categories, function (bool $carry, $catId): bool {
		if (!is_integer($catId) || $catId <= 0)
		{
			return $carry;
		}

		return $carry && Permissions::isManager($catId);
	}, true);

$catsForManagerCheck = empty($categories)
	? Factory::getApplication()->getIdentity()->getAuthorisedCategories('com_ats', 'core.manage')
	: $categories;
$managers = [];
array_walk(
	$catsForManagerCheck,
	function ($catid) use (&$managers) {
		$managers = array_merge($managers, Permissions::getManagers($catid));
	}
);

$managers = array_filter(array_map(function ($user) {
	return HTMLHelper::_('select.option', $user->id, $user->name);
}, array_unique($managers, SORT_REGULAR)), function ($x) {
	return !empty($x);
});

$statusOptions = array_map(function ($key, $desc) {
	return HTMLHelper::_('select.option', $key, $desc);
}, array_keys(ComponentParams::getStatuses()), array_values(ComponentParams::getStatuses()));

$allEditStateCats = Factory::getApplication()->getIdentity()->getAuthorisedCategories('com_ats', 'core.edit.state');
$editStateCats    = empty($categories) ? $allEditStateCats : $categories;
$canEditState     = array_reduce($editStateCats, function ($carry, $catid) use ($allEditStateCats) {
	return $carry && (in_array($catid, $allEditStateCats) || Permissions::isManager($catid));
}, true);

$priorityOptions = [
		HTMLHelper::_('select.option', '', Text::_('COM_ATS_TICKET_PRIORITY_BATCH_SELECT')),
		HTMLHelper::_('select.option', 0, Text::_('COM_ATS_PRIORITIES_HIGH')),
		HTMLHelper::_('select.option', 5, Text::_('COM_ATS_PRIORITIES_NORMAL')),
		HTMLHelper::_('select.option', 10, Text::_('COM_ATS_PRIORITIES_LOW')),
];

$hasPriorities = ComponentHelper::getParams('com_ats')->get('ticketPriorities', 0) == 1;

?>
<div class="container">
	<div class="row">
		<?php // Assign To ?>
		<?php if ($isManager): ?>
		<div class="form-group col-md-6">
			<label id="batch-assigned-to-lbl" for="batch-assigned-to">
				<?= Text::_('COM_ATS_TICKETS_ASSIGN_TO'); ?>
			</label>
			<div class="control-group">
				<div class="controls">
					<select name="batch[assigned_to]" class="form-select" id="batch-assigned-to">
						<?= HTMLHelper::_('select.options', array_merge([
							HTMLHelper::_('select.option', '', Text::_('COM_ATS_TICKETS_ASSIGNED_TO_BATCH_SELECT')),
							HTMLHelper::_('select.option', '-1', Text::_('COM_ATS_TICKET_UNASSIGN')),
						], $managers)); ?>
					</select>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<?php // Status ?>
		<?php if ($canEditState): ?>
		<div class="form-group col-md-6">
			<div class="controls">
				<label id="batch-status-lbl" for="batch-status">
					<?= Text::_('COM_ATS_TICKETS_HEADING_STATUS'); ?>
				</label>
				<div class="control-group">
					<div class="controls">
						<select name="batch[status]" class="form-select" id="batch-status">
							<?= HTMLHelper::_('select.options', array_merge([
								HTMLHelper::_('select.option', '', Text::_('COM_ATS_TICKETS_STATUS_BATCH_SELECT'))
							], $statusOptions)); ?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>


		<?php // Ticket priority ?>
		<?php if ($canEditState && $hasPriorities): ?>
			<div class="form-group col-md-6">
				<div class="controls">
					<label id="batch-priority-lbl" for="batch-priority">
						<?= Text::_('COM_ATS_TICKET_PRIORITY'); ?>
					</label>
					<div class="control-group">
						<div class="controls">
							<select name="batch[priority]" class="form-select" id="batch-priority">
								<?= HTMLHelper::_('select.options', $priorityOptions); ?>
							</select>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>


		<?php // Copy or move ?>
		<div class="form-group col-md-6">
			<label id="batch-choose-action-lbl" for="batch-category-id">
				<?= Text::_('COM_ATS_TICKETS_BATCH_LBL_MOVE'); ?>
			</label>
			<div class="control-group">
				<select name="batch[category_id]" class="form-select" id="batch-category-id">
					<option value=""><?= Text::_('JLIB_HTML_BATCH_NO_CATEGORY'); ?></option>
					<?= HTMLHelper::_('select.options', HTMLHelper::_('category.options', 'com_ats')); ?>
				</select>
			</div>
		</div>


		<?php // Tag ?>
		<div class="form-group col-md-6">
			<div class="controls">
				<?= LayoutHelper::render('joomla.html.batch.tag', []); ?>
			</div>
		</div>
	</div>
</div>
