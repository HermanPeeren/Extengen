<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView $this */
?>
<?php if ($this->item->public): ?>
	<div class="alert alert-warning small">
		<h6>
			<span class="fa fa-eye me-1" aria-hidden="true"></span>
			<?= Text::_('COM_ATS_POST_LBL_PUBNOTE_PUBLIC_HEAD') ?>
		</h6>
		<p class="mb-0">
			<?= Text::_('COM_ATS_POST_LBL_PUBNOTE_PUBLIC') ?>
		</p>
	</div>
<?php else: ?>
	<div class="alert alert-success small">
		<h6>
			<span class="fa fa-eye-slash me-1" aria-hidden="true"></span>
			<?= Text::_('COM_ATS_POST_LBL_PUBNOTE_PRIVATE_HEAD') ?>
		</h6>
		<p class="mb-0">
			<?= Text::_('COM_ATS_POST_LBL_PUBNOTE_PRIVATE') ?>
		</p>
	</div>
<?php endif; ?>
