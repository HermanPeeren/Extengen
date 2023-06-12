<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\View\Mixin;

use Akeeba\Component\ATS\Site\Helper\RouteHelper;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\MenuItem;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

trait CategoryBreadcrumbsAware
{
	/**
	 * Adds the path from the menu item to the specified leaf category to the breadcrumbs.
	 *
	 * @param   CategoryNode  $cat  The leaf category: the category we display or the category of the ticket
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	private function addCategoryPathToBreadcrumbs(CategoryNode $cat): void
	{
		/** @var SiteApplication $app */
		$app     = Factory::getApplication();
		$pathway = $app->getPathway();

		foreach ($this->getIntermediateCategories($cat) as $parentCat)
		{
			$pathway->addItem($parentCat->title, Route::_(RouteHelper::getCategoryRoute($parentCat->id)));
		}

		if ($cat->id != $cat->getRoot()->id)
		{
			$pathway->addItem($cat->title, Route::_(RouteHelper::getCategoryRoute($cat->id)));
		}
	}

	/**
	 * Get the ATS category ID referenced by a menu item (if applicable)
	 *
	 * @param   MenuItem|null  $menu  The menu item. NULL if there's no menu item, i.e. we have bare access.
	 *
	 * @return  int|null  The ATS category ID, NULL if not applicable
	 *
	 * @since   5.0.0
	 */
	private function getCategoryRootFromMenuItem(?MenuItem $menu): ?int
	{
		if (empty($menu))
		{
			return null;
		}

		if ($menu->component != 'com_ats')
		{
			return null;
		}

		$view = trim(strtolower(($menu->query['view'] ?? null) ?: ''));

		if (!in_array($view, ['categories', 'category', 'tickets']))
		{
			return null;
		}

		$id = $view['id'] ?? $view['category'] ?? $view['catid'] ?? null;

		if (empty($id) || (int) $id == 0)
		{
			return null;
		}

		return (int) $id;
	}

	/**
	 * Get a list of all the intermediate categories between the one of the active menu item and the leaf category.
	 *
	 * If there is no active menu item or it has no category specified we assume the ATS Categories' root.
	 *
	 * All categories in the path between the root and the leaf EXCEPT FOR the root and the leaf are returned.
	 *
	 * If the leaf is a direct descendent of the root no categories are returned, of course (empty array).
	 *
	 * @param   CategoryNode  $leafCategory  The leaf category
	 *
	 * @return  CategoryNode[]  The intermediate categories
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	private function getIntermediateCategories(CategoryNode $leafCategory): array
	{
		/** @var SiteApplication $app */
		$app    = Factory::getApplication();
		$menu   = $app->getMenu()->getActive();
		$rootId = $this->getCategoryRootFromMenuItem($menu) ?? $leafCategory->getRoot()->id;

		$ret = [];
		$cat = clone $leafCategory;

		while ($cat->hasParent())
		{
			$cat = $cat->getParent();

			if ($cat->id === $rootId)
			{
				break;
			}

			$ret[] = clone $cat;
		}

		return array_reverse($ret);
	}
}