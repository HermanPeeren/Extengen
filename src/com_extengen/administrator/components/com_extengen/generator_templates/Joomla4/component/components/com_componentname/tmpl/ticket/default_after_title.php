<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView $this */

if (empty($this->afterTitle))
{
	return;
}
?>
<div class="ats-after-title card card-body mb-3">
	<?php foreach ($this->afterTitle as $result) {
		echo $result;
	} ?>
</div>

