<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\ATS\Site\View\Category\HtmlView $this */

use Akeeba\Component\ATS\Administrator\Helper\ComponentParams;
use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Akeeba\Component\ATS\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$canDo = Permissions::getAclPrivileges($this->category->id);

$this->document->getWebAssetManager()->useScript('com_ats.commonevents');

$showTitle            = $this->params->get('cat_show_title', 1) == 1;
$showParent           = $this->params->get('cat_show_parent', 0) == 1;
$showTags             = $this->params->get('cat_show_tags', 0) == 1;
$showDescription      = $this->params->get('cat_show_description', 0) == 1;
$showDescriptionImage = $this->params->get('cat_show_description_image', 0) == 1;

$image                = $this->category->params->get('image', '');
$imageAlt             = $this->category->params->get('image_alt', '');
$imageAltEmpty        = $this->category->params->get('image_alt_empty', '');
$altAttribute         = (empty($imageAlt) && empty($imageAltEmpty)) ? '' : sprintf("alt=\"%s\"", htmlspecialchars($imageAlt, ENT_COMPAT));
$showDescriptionImage = $showDescriptionImage && !empty($image);

$showSubcategories            = $this->params->get('cat_show_subcats', 1) == 1;
$subcategoriesMaxLevel        = $this->params->get('cat_show_subcats_maxLevel', 1);
$showSubcategoriesDescription = $this->params->get('cat_show_subcats_desc', 0) == 1;

$showTickets          = $this->params->get('cat_show_tickets', 1) == 1;
$showNoTicketsMessage = $this->params->get('cat_show_notickets', 1) == 1;
$showStatusFilter     = !Factory::getApplication()->getIdentity()->guest;
$showNewTicketButton  = $canDo['core.create'] && ($this->params->get('cat_show_newticket_button', 1) == 1);

$showPagination        = $this->params->get('show_pagination', 1);
$showPaginationResults = $this->params->get('show_pagination_results', 1);
$showPaginationLimit   = $this->params->get('show_pagination_limit', 0);

$hasParent  = $this->category->hasParent() && ($this->category->getParent()->id != $this->category->getRoot()->id);
$tagsData = (isset($this->category->tags) && isset($this->category->tags->itemTags)) ? $this->category->tags->itemTags : null;

$hasBeforeContent = !empty($this->category->beforeContent ?? null);
$hasAfterContent  = !empty($this->category->afterContent ?? null);

?>

<div class="ats ats-category-details-<?= $this->category->id ?> ats-category-<?= $this->category->id ?>">
	<?= $this->loadPosition('ats-top'); ?>

	<?php if ($showTitle): ?>
	<h2>
		<?= $this->escape($this->category->title) ?>
	</h2>

	<?php if (!empty($this->category->afterTitle ?? null)): ?>
			<div class="ats-category-aftertitle">
				<?= implode("\n", $this->category->afterTitle) ?>
			</div>
		<?php endif ?>
	<?php endif ?>

	<?php if ($showParent && $hasParent): ?>
	<div class="ats-category-parent">
		<span class="fw-bold">
			<?= Text::_('JCATEGORY') ?>:
		</span>
		<?= $this->escape($this->category->getParent()->title) ?>
	</div>
	<?php endif; ?>

	<?php if ($showTags): ?>
	<?php if (!empty($tagsData) && $showTags) : ?>
		<div class="ats-category-tags">
			<?php echo LayoutHelper::render('joomla.content.tags', $this->category->tags->itemTags); ?>
		</div>
	<?php endif; ?>
	<?php endif; ?>

	<?php if (!empty($this->category->beforeContent ?? null)): ?>
		<div class="ats-category-beforecontent">
			<?= implode("\n", $this->category->beforeContent) ?>
		</div>
	<?php endif ?>

	<?php if ($showDescription || $showDescriptionImage): ?>
	<div class="row">
		<?php if ($showDescriptionImage): ?>
		<div class="col-12 <?php if ($showDescription): ?>col-md-2<?php endif ?> ats-category-image">
			<img src="<?= $image ?>" <?= $altAttribute ?> class="ats-category-image">
		</div>
		<?php endif; ?>
		<?php if ($showDescription): ?>
		<div class="col">
			<div class="ats-category-desc">
				<?php echo HTMLHelper::_('content.prepare', $this->category->description, '', 'com_ats.category'); ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php endif ?>

	<?php if ($showSubcategories && $this->category->hasChildren()):
		?>
		<div class="border-top border-2 mt-3 p-2 ats-category-subcategories-<?= $this->category->id ?>">
			<h4>
				<?php echo Text::_('JGLOBAL_SUBCATEGORIES'); ?>
			</h4>
			<?= LayoutHelper::render('akeeba.ats.category.list', [
				'categories' => array_map(function ($cat) {
					// Otherwise some core and third party plugins which only expect articles throw warnings...
					$cat->text = '';

					return ($this->processCategoryFieldsDisplay($cat, 2));
				}, $this->category->getChildren() ?? []),
				'params'     => $this->params,
			]); ?>
		</div>
	<?php endif; ?>

	<?= $this->loadPosition('ats-tickets-top'); ?>

	<?php if ($showTickets): ?>
	<?php if ($showStatusFilter || $showNewTicketButton): ?>
	<div class="border rounded-2 p-2 my-3 bg-light d-flex justify-content-between ats-pseudotoolbar">
		<?php if ($showNewTicketButton): ?>
			<a class="btn btn-success" id="toolbar-newticket"
			   href="<?= Route::_('index.php?option=com_ats&view=new&catid=' . $this->category->id) ?>">
				<span class="fa fa-plus" aria-hidden="true"></span>
				<?= Text::_('COM_ATS_TITLE_TICKETS_ADD') ?>
			</a>
		<?php endif; ?>

		<?php if ($showStatusFilter): ?>
			<form id="ats-category-filters" name="adminForm"
				  action="<?= Uri::current() ?>"
				  method="post"
				  class="row gx-2 align-items-center"
			>
				<div class="col-auto">
					<label class="visually-hidden" for="filterStatus">
						<?= Text::_('COM_ATS_TICKETS_HEADING_STATUS') ?>
					</label>
					<?= LayoutHelper::render('joomla.form.field.list-fancy-select', [
						'autofocus'     => false,
						'name'          => 'status[]',
						'id'            => 'filterStatus',
						'class'         => '',
						'multiple'      => true,
						'value'         => $this->getModel()->getState('filter.status', ''),
						'options'       => array_merge([
							'' => Text::_('COM_ATS_TICKETS_STATUS_SELECT'),
						], ComponentParams::getStatuses()),
						'hint'          => '',
						'onchange'      => '',
						'onclick'       => '',
						'dataAttribute' => '',
						'readonly'      => false,
						'required'      => false,
						'disabled'      => false,
					]) ?>
				</div>
				<div class="col-auto">
					<button type="submit" class="btn btn-sm btn-outline-primary">
						<span class="fa fa-search"></span>
						<?= Text::_('JSEARCH_FILTER') ?>
					</button>
				</div>
			</form>
		<?php endif ?>
	</div>
	<?php endif ?>

	<?php if (empty($this->items)): ?>
		<?= $this->loadPosition('ats-tickets-none-top') ?>
		<?php if ($showNoTicketsMessage): ?>
		<p class="alert alert-info">
			<span class="icon-info-circle" aria-hidden="true"></span>
			<?= Text::_('COM_ATS_TICKETS_MSG_NOTICKETS') ?>
		</p>
		<?php endif ?>
		<?= $this->loadPosition('ats-tickets-none-bottom') ?>
	<?php else: ?>
		<?php echo $this->loadAnyTemplate('category/default_tickets', false, [
			'tickets' => $this->items,
		])?>
	<?php endif; // empty($this->items) ?>
	<?php endif; // $showTickets ?>

	<?php if (!empty($this->category->afterContent ?? null)): ?>
	<div class="ats-category-aftercontent">
		<?= implode("\n", $this->category->afterContent) ?>
	</div>
	<?php endif ?>

	<?php if ($showPagination): ?>
	<form id="ats-pagination" name="atspagination"
		  action="<?= Route::_(RouteHelper::getCategoryRoute($this->category->id)) ?>"
		  method="post">
		<?php
		$filterStatus = $this->getModel()->getState('filter.status');
		if (is_array($filterStatus) && !empty($filterStatus)): ?>
			<input type="hidden" name="status" value="<?= implode(',', $this->getModel()->getState('filter.status')) ?>" id="ats_filter_status" />
		<?php endif; ?>
		<?= HTMLHelper::_('form.token') ?>

		<div class="pagination d-flex flex-column flex-md-row justify-content-between align-items-center">
			<?php if ($showPaginationResults || $showPaginationLimit): ?>
			<div class="counter order-md-1">
				<?php if ($showPaginationLimit): ?>
				<?= $this->pagination->getLimitBox() ?>
				<?php endif; ?>
				<?php if ($showPaginationResults): ?>
				<?= $this->pagination->getPagesCounter() ?>
				<?php endif; ?>
			</div>
			<?php endif ?>
			<div>
				<?= $this->pagination->getPagesLinks() ?>
			</div>
		</div>
	</form>
	<?php endif; ?>

	<?= $this->loadPosition('ats-tickets-bottom'); ?>
	<?= $this->loadPosition('ats-bottom'); ?>
</div>