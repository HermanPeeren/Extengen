<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\ATS\Site\View\My\HtmlView $this */

use Akeeba\Component\ATS\Administrator\Helper\ComponentParams;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

?>
<div class="ats ats-my-tickets">
	<h2>
		<?= $this->myTickets ? Text::_($this->defaultPageTitle) : Text::sprintf('COM_ATS_MY_LBL_PAGE_TITLE_ALT', $this->user->username) ?>
	</h2>

	<div class="border rounded-2 p-2 my-3 bg-light d-flex justify-content-between ats-pseudotoolbar">
		<?php if ($this->canFileTickets): ?>
		<a href="<?= Route::_('index.php?option=com_ats&view=ticket&layout=edit') ?>"
		   class="btn btn-sm btn-success">
			<span class="fa fa-file" aria-hidden="true"></span>
			<?= Text::_('COM_ATS_TICKETS_BUTTON_NEWTICKET') ?>
		</a>
		<?php endif; ?>

		<form id="ats-category-filters" name="adminForm"
			  action="<?= Uri::current() ?>"
			  method="post"
			  class="row gx-2 align-items-center"
		>
			<div class="col-auto">
				<label class="visually-hidden" for="filterStatus">
					<?= Text::_('COM_ATS_TICKETS_HEADING_STATUS') ?>
				</label>
				<?= LayoutHelper::render('joomla.form.field.list-fancy-select', [
					'autofocus'     => false,
					'name'          => 'status[]',
					'id'            => 'filterStatus',
					'class'         => '',
					'multiple'      => true,
					'value'         => $this->getModel()->getState('filter.status', ''),
					'options'       => array_merge([
						'' => Text::_('COM_ATS_TICKETS_STATUS_SELECT'),
					], ComponentParams::getStatuses()),
					'hint'          => '',
					'onchange'      => '',
					'onclick'       => '',
					'dataAttribute' => '',
					'readonly'      => false,
					'required'      => false,
					'disabled'      => false,
				]) ?>
			</div>
			<div class="col-auto">
				<button type="submit" class="btn btn-sm btn-outline-primary">
					<span class="fa fa-search"></span>
					<?= Text::_('JSEARCH_FILTER') ?>
				</button>
			</div>
		</form>
	</div>

	<?php if (empty($this->items)): ?>
		<?= $this->loadPosition('ats-tickets-none-top') ?>
		<p class="alert alert-info">
			<span class="icon-info-circle" aria-hidden="true"></span>
			<?= Text::_('COM_ATS_TICKETS_MSG_NOTICKETS_ALT') ?>
		</p>
		<?= $this->loadPosition('ats-tickets-none-bottom') ?>
	<?php else: ?>
		<?php echo $this->loadAnyTemplate('category/default_tickets', false, [
			'tickets' => $this->items,
			'ticketOptions' => [
				'showAgo'      => true,
				'showCategory' => true,
				'showMy'       => false,
			]
		])?>
	<?php endif ?>

	<form id="ats-pagination" name="atspagination"
		  action="<?= Route::_('index.php?option=com_ats&view=latest') ?>"
		  method="post">
		<?php
		$filterStatus = $this->getModel()->getState('filter.status');
		if (is_array($filterStatus) && !empty($filterStatus)): ?>
			<input type="hidden" name="status" value="<?= implode(',', $this->getModel()->getState('filter.status')) ?>" id="ats_filter_status" />
		<?php endif; ?>
		<?= HTMLHelper::_('form.token') ?>

		<div class="pagination d-flex flex-column flex-md-row justify-content-between align-items-center">
			<div class="counter order-md-1">
				<?= $this->pagination->getPagesCounter() ?>
			</div>
			<div>
				<?= $this->pagination->getPagesLinks() ?>
			</div>
		</div>
	</form>
</div>