<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\ATS\Site\View\Latest\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

?>
<div class="ats ats-assigned ats-latest">
	<h2>
		<?= Text::_('COM_ATS_LATEST_LBL_PAGE_TITLE') ?>
	</h2>

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