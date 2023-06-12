<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Akeeba\Component\ATS\Site\Helper\RouteHelper;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

/**
 * @var array        $displayData
 * @var CategoryNode $category
 * @var int          $maxLevel
 * @var Registry     $params
 */

$category        = $displayData['category'];
$maxLevel        = $displayData['maxLevel'] - 1;
$params          = $displayData['params'] ?? call_user_func(function () {
		// Merge the global component and menu parameters
		$app        = Factory::getApplication();
		$menu       = $app->getMenu()->getActive();
		$menuParams = is_object($menu) ? $menu->getParams() : new Registry();
		$params     = clone ComponentHelper::getParams('com_ats');

		return $params->merge($menuParams);
	});
$showLink        = in_array($category->access, Permissions::getUser()->getAuthorisedViewLevels());
$showDescription = $params->get('cats_show_subcats_desc', 0) == 1;

?>
<ul class="ats-category-subcategories ats-category-<?= $category->id ?>-subcategories">
	<?php foreach ($category->getChildren() as $id => $child): ?>
	<li>
		<h6 class="ats-subcategory-title">
			<?php if ($showLink): ?>
				<a href="<?= Route::_(RouteHelper::getCategoryRoute($child->id)) ?>" class="text-decoration-none">
					<?= $this->escape($child->title) ?>
				</a>
			<?php else: ?>
				<?= $this->escape($child->title) ?>
			<?php endif ?>
		</h6>

		<?php if ($showDescription && $child->description): ?>
		<div class="ats-subcategory-description">
			<?= HTMLHelper::_('content.prepare', $child->description, '', 'com_ats.category'); ?>
		</div>
		<?php endif; ?>

		<?php if (count($child->getChildren()) && ($maxLevel != 0)): ?>
		<?= LayoutHelper::render('akeeba.ats.category.list_subcategories', [
			'category' => $child,
			'maxLevel' => $maxLevel,
			'params'   => $params,
			]) ?>
		<?php endif; ?>
	</li>
	<?php endforeach; ?>
</ul>