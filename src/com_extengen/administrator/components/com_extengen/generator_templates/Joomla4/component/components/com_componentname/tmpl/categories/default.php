<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\ATS\Site\View\Categories\HtmlView $this */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

$subCategories = $subCategories ?? $this->items;

?>
<div class="ats ats-categories">
	<?= $this->loadPosition('ats-top') ?>
	<?= $this->loadPosition('ats-categories-top') ?>

	<?php if ($this->params->get('show_page_heading', 1) == 1): ?>
		<h2>
			<?= $this->escape($this->params->get('page_heading', Text::_('COM_ATS_CATEGORIES_TITLE'))) ?>
		</h2>
	<?php endif; ?>

	<?= LayoutHelper::render('akeeba.ats.category.list', [
		'categories' => $this->items,
		'params'     => $this->params,
	]); ?>

	<?= $this->loadPosition('ats-categories-bottom') ?>
	<?= $this->loadPosition('ats-bottom') ?>
</div>