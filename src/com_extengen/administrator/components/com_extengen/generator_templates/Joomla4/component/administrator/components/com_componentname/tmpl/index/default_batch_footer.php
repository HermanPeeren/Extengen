<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Akeeba\Component\ATS\Administrator\View\Tickets\HtmlView $this */

$this->document->getWebAssetManager()->useScript('com_ats.tickets_backend');

?>
<button class="btn btn-secondary" id="ats_batch_cancel"
		type="button"
		data-bs-dismiss="modal">
	<?php echo Text::_('JCANCEL'); ?>
</button>

<button class="btn btn-success" id="ats_batch_run"
		type="submit">
	<?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
