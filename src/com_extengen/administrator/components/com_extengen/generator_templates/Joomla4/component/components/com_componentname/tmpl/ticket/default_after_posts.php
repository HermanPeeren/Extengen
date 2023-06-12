<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView $this */

?>
<?php if (!empty($this->afterContent)): ?>
	<div class="ats-after-content card card-body mb-3">
		<?php foreach ($this->afterContent as $result) {
			echo $result;
		} ?>
	</div>
<?php endif; ?>

<?php if (!empty($this->afterConversationResults)): ?>
<div class="ats-after-ticketconversation mb-3">
	<?php foreach ($this->afterConversationResults as $result) {
		echo $result;
	} ?>
</div>
<?php endif; ?>