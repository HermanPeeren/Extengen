<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\ATS\Site\View\Post\HtmlView $this */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

?>
<div class="ats ats-edit-post">
	<form action="<?= Route::_('index.php?option=com_ats&task=post.save&id=' . $this->item->id) ?>"
		  method="post"
		  name="adminForm"
		  id="adminForm"
		  class="form-validate"
		  aria-label="<?= Text::_('COM_ATS_TITLE_POSTS_EDIT', true) ?>"
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

		<?php foreach (array_keys($this->form->getFieldsets()) as $fieldSet): ?>
			<div class="card mb-2 h-100">
				<?php if ($this->form->getFieldsets()[$fieldSet]->label): ?>
					<h3 class="card-header bg-info text-white">
						<?= Text::_($this->form->getFieldsets()[$fieldSet]->label) ?>
					</h3>
				<?php endif; ?>
				<div class="card-body">
					<?php echo $this->form->renderFieldset($fieldSet); ?>
				</div>
			</div>
		<?php endforeach; ?>
	</form>
</div>