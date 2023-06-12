<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Site\Helper\ModuleRenderHelper;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Registry\Registry;

/**
 * @var array          $displayData
 * @var CategoryNode[] $categories
 * @var null|Registry       $params
 */
$categories = $displayData['categories'];
$params     = $displayData['params'] ?? call_user_func(function () {
		// Merge the global component and menu parameters
		$app          = Factory::getApplication();
		$menu         = $app->getMenu()->getActive();
		$menuParams   = is_object($menu) ? $menu->getParams() : new Registry();
		$mergedParams = clone ComponentHelper::getParams('com_ats');

		return $mergedParams->merge($menuParams);
	});

$showEmptyMessage = $params->get('cats_show_empty_message', 1);

?>
<?php if (!count($categories)): ?>
	<?= ModuleRenderHelper::loadPosition('ats-categories-none-top') ?>

	<?php if ($showEmptyMessage): ?>
		<p class="fa fa-info-circle">
			<span class="icon-info-circle" aria-hidden="true"></span>
			<?= Text::_('COM_ATS_CATEGORIES_MSG_NOCATEGORIES') ?>
		</p>
	<?php endif; ?>

	<?= ModuleRenderHelper::loadPosition('ats-categories-none-bottom') ?>
<?php else: ?>
	<?php foreach ($categories as $cat): ?>
		<?= LayoutHelper::render('akeeba.ats.category.list_item', [
			'category' => $cat,
			'params'   => $params,
		]) ?>
	<?php endforeach; ?>
<?php endif; ?>
