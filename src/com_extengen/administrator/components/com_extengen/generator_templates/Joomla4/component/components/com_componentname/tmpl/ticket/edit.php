<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView $this */

use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$knownFieldSets  = ['basic', 'details'];
$customFieldSets = array_diff(array_keys($this->form->getFieldsets()), $knownFieldSets);
$isManager       = Permissions::isManager($this->item->catid ?: null);

?>
<div class="ats ats-ticket-edit">
	<?= $this->loadPosition('ats-top') ?>
	<?= $this->loadPosition('ats-posts-top') ?>

	<form action="<?= Route::_('index.php?option=com_ats&task=ticket.save&id=' . $this->item->id) ?>"
		  method="post"
		  name="adminForm"
		  id="adminForm"
		  class="form-validate"
		  aria-label="<?= Text::_('COM_ATS_TITLE_TICKETS_EDIT', true) ?>"
	>
		<?php if (!empty($this->returnUrl)): ?>
			<input type="hidden" name="returnurl" value="<?= base64_encode($this->returnUrl) ?>">
		<?php endif; ?>
		<?= HTMLHelper::_('form.token') ?>

		<?php // Fake toolbar ?>
		<div class="border bg-light rounded-2 p-2 mb-2 ats-pseudotoolbar">
			<button type="submit"
					class="btn btn-success">
				<?= Text::_('JTOOLBAR_SAVE') ?>
			</button>
			<a href="<?= $this->returnUrl ?>"
			   class="btn btn-danger">
				<?= Text::_('JTOOLBAR_CANCEL') ?>
			</a>
		</div>

		<?php if (!empty($customFieldSets) && !$isManager): ?>
			<div class="alert alert-warning my-2">
				<h3 class="alert-header">
					<?= Text::_('COM_ATS_TICKET_LBL_EDITTICKET_STAFF_NOTIFIED_HEAD') ?>
				</h3>
				<p>
					<?= Text::_('COM_ATS_TICKET_LBL_EDITTICKET_STAFF_NOTIFIED_BODY') ?>
				</p>
			</div>
		<?php endif ?>

		<div class="row row-cols-1 row-cols-md-2 g-1">
			<?php foreach ($knownFieldSets as $fieldSet):
				$fieldsInSet = $this->form->getFieldset($fieldSet);
				if (empty($fieldsInSet)) continue;
				?>
				<div class="col">
					<div class="card mb-2 h-100">
						<h3 class="card-header bg-info text-white">
							<?= Text::_($this->form->getFieldsets()[$fieldSet]->label) ?>
						</h3>
						<div class="card-body">
							<?php echo $this->form->renderFieldset($fieldSet); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>

			<?php foreach($customFieldSets as $fieldSet):
				$fieldsInSet = $this->form->getFieldset($fieldSet);
				if (empty($fieldsInSet)) continue;
				?>
				<div class="col">
					<div class="card mb-2 h-100">
						<h3 class="card-header bg-dark text-white">
							<?= Text::_($this->form->getFieldsets()[$fieldSet]->label) ?>
						</h3>
						<div class="card-body">
							<?php if ($descriptionKey = $this->form->getFieldsets()[$fieldSet]->description): ?>
								<div class="alert alert-info">
									<?= Text::_($descriptionKey) ?>
								</div>
							<?php endif; ?>
							<?php echo $this->form->renderFieldset($fieldSet); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</form>

	<?= $this->loadPosition('ats-posts-bottom') ?>
	<?= $this->loadPosition('ats-bottom') ?>
</div>