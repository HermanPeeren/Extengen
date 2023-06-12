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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

/**
 * @var array         $displayData
 * @var CategoryNode  $category
 * @var Registry|null $params
 */
// Get the category and make sure its params property is a Registry object
$category         = $displayData['category'];
$category->params = is_object($category->params) ? $category->params : new Registry($category->params);
$params           = $displayData['params'] ?? call_user_func(function () {
		// Merge the global component and menu parameters
		$app          = Factory::getApplication();
		$menu         = $app->getMenu()->getActive();
		$menuParams   = is_object($menu) ? $menu->getParams() : new Registry();
		$params = clone ComponentHelper::getParams('com_ats');

		return $params->merge($menuParams);
	});

// Get the available user actions
$actions    = Permissions::getAclPrivileges($category->id);
$showCreate = $actions['core.create'];
$showLink   = in_array($category->access, Permissions::getUser()->getAuthorisedViewLevels());

// Get the tags data
$tagsData = (isset($category->tags) && isset($category->tags->itemTags)) ? $category->tags->itemTags : null;

// Get the display parameters
$showTitle       = $params->get('cats_show_title', 1) == 1;
$linkTitle       = $params->get('cats_link_title', 1) == 1;
$showImage       = $params->get('cats_show_description_image', 1) == 1;
$showDescription = $params->get('cats_show_description', 1) == 1;
$showButtons     = ($params->get('cats_show_buttons', 1) == 1) && ($showLink || $showCreate);
$showTags        = ($params->get('cats_show_tags', 1) == 1) && !empty($tagsData);
$showSubcats     = $params->get('cats_show_subcats', 0) == 1;
$maxSubcatLevel  = $showSubcats ? (int) $params->get('cats_show_subcats_maxLevel', -1) : 0;

// Process the category image parameters
$image         = $category->params->get('image', '');
$imageAlt      = $category->params->get('image_alt', '');
$imageAltEmpty = $category->params->get('image_alt_empty', '');
$altAttribute  = (empty($imageAlt) && empty($imageAltEmpty)) ? '' : sprintf("alt=\"%s\"", htmlspecialchars($imageAlt, ENT_COMPAT));
$showImage     = $showImage && !empty($image);

// Get the subcategories — DO NOT REMOVE. Required for $category->hasChildren() to work properly.
$subCats = $category->getChildren();

// Helper variables to figure out which sections to display
$hasBeforeContent = !empty($category->beforeContent ?? null);
$hasAfterContent  = !empty($category->afterContent ?? null);
$isTwoColumns     = $hasBeforeContent || $showDescription || $hasAfterContent || $showButtons;
?>

<div class="card mb-2 ats-category-summary-<?= $category->id ?> ats-category-<?= $category->id ?>">
	<?php if ($showTitle): ?>
	<h3 class="card-header bg-dark">
		<?php if ($linkTitle && $showLink): ?>
		<a href="<?= Route::_(RouteHelper::getCategoryRoute($category->id)) ?>" class="text-decoration-none text-white">
			<?= $this->escape($category->title) ?>
		</a>
		<?php else: ?>
		<?= $this->escape($category->title) ?>
		<?php endif ?>
	</h3>
	<?php endif; ?>

	<div class="card-body">
		<?php if (!empty($category->afterTitle ?? null)): ?>
		<div class="ats-category-aftertitle">
			<?= implode("\n", $category->afterTitle) ?>
		</div>
		<?php endif ?>

		<?php if (!empty($tagsData) && $showTags) : ?>
		<div class="ats-category-tags">
			<?php echo LayoutHelper::render('joomla.content.tags', $category->tags->itemTags); ?>
		</div>
		<?php endif; ?>

		<div class="row">
			<?php if ($showImage): ?>
			<div class="col-12 <?php if ($isTwoColumns): ?>col-md-2<?php endif ?> ats-category-image">
				<img src="<?= $image ?>" <?= $altAttribute ?> class="ats-category-image">
			</div>
			<?php endif; ?>

			<?php if ($hasBeforeContent || $showDescription || $hasAfterContent || $showButtons): ?>
			<div class="col">
				<?php if ($hasBeforeContent): ?>
					<div class="ats-category-beforecontent">
						<?= implode("\n", $category->beforeContent) ?>
					</div>
				<?php endif ?>

				<?php if ($showDescription): ?>
					<div class="ats-category-desc">
						<?php echo HTMLHelper::_('content.prepare', $category->description, '', 'com_ats.category'); ?>
					</div>
				<?php endif; ?>

				<?php if ($hasAfterContent): ?>
					<div class="ats-category-aftercontent">
						<?= implode("\n", $category->afterContent) ?>
					</div>
				<?php endif ?>

				<div class="col d-flex flex-row ats-category-quickbuttons">
					<div class="flex-grow-1"></div>

					<?php if ($showLink): ?>
					<a class="btn btn-primary btn-sm m-1"
					   href="<?= @Route::_(RouteHelper::getCategoryRoute($category)) ?>">
						<span class="fa fa-folder" aria-hidden="true"></span>
						<?= Text::_('COM_ATS_CATEGORIES_VIEWTICKETS') ?>
					</a>
					<?php endif; ?>

					<?php if ($showCreate): ?>
					<a class="btn btn-success btn-sm m-1"
					   href="<?= Route::_('index.php?option=com_ats&view=new&catid=' . $category->id) ?>">
						<span class="fa fa-file" aria-hidden="true"></span>
						<?= Text::_('COM_ATS_TICKETS_BUTTON_NEWTICKET') ?>
					</a>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<?php if (($maxSubcatLevel != 0) && $category->hasChildren()): ?>
		<div class="ats-category-subcategories my-2">
			<h5 class="border-bottom py-1">
				<?php echo Text::_('JGLOBAL_SUBCATEGORIES'); ?>
			</h5>
			<?= LayoutHelper::render('akeeba.ats.category.list_subcategories', [
				'category' => $category,
				'maxLevel' => $maxSubcatLevel,
				'params'   => $params,
			]) ?>
		</div>
		<?php endif; ?>
	</div>
</div>