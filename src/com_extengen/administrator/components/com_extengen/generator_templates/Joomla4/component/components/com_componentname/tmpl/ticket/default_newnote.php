<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView $this */

?>
<form action="<?= Route::_('index.php?option=com_ats&task=managernote.save&id=0') ?>"
      method="post"
      name="managernoteForm"
      id="managernoteForm"
      class="form-validate"
      aria-label="<?= Text::_('COM_ATS_POSTS_HEADING_MANAGERNOTEAREA', true) ?>"
>
	<input type="hidden" name="returnurl" value="<?= base64_encode(Uri::getInstance()->toString(['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'])) ?>">
	<?= HTMLHelper::_('form.token') ?>

	<div class="mt-5 pt-3 mb-2 border-top border-2 border-dark">
		<h3 class="h1 my-3">
			<?= Text::_('COM_ATS_POSTS_HEADING_MANAGERNOTEAREA') ?>
		</h3>
		<?php foreach (array_keys($this->managerNoteForm->getFieldsets()) as $fieldSet)
		{
			echo $this->managerNoteForm->renderFieldset($fieldSet);
		} ?>

		<div class="control-group">
			<div class="control-label d-none d-md-block">
				&nbsp;
			</div>
			<div class="controls">
				<button type="submit"
						class="btn btn-lg btn-primary w-100">
					<span class="fa fa-comment-dots" aria-hidden="true"></span>
					<?= Text::_('COM_ATS_MANAGERNOTES_MSG_POST') ?>
				</button>
			</div>
		</div>
	</div>
</form>
