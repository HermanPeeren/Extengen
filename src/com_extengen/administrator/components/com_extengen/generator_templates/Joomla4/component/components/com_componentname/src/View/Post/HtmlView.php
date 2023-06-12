<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\View\Post;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\View\Post\HtmlView as AdminHtmlView;
use Akeeba\Component\ATS\Site\Helper\RouteHelper;
use Akeeba\Component\ATS\Site\Service\Category;
use Akeeba\Component\ATS\Site\View\Mixin\CategoryBreadcrumbsAware;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class HtmlView extends AdminHtmlView
{
	use CategoryBreadcrumbsAware;

	protected function addToolbar(): void
	{
		/** @var SiteApplication $app */
		$app = Factory::getApplication();

		// Load Joomla's backend and frontend language (so that some form field labels are not left untranslated).
		$lang = $app->getLanguage();
		$lang->load('joomla', JPATH_ADMINISTRATOR, null, true);
		$lang->load('joomla', JPATH_SITE, null, true);

		// Get the post's ticket. We need it for breadcrumbs.
		$ticket = $this->item->getTicket();

		// Get the category of the ticket
		$catService     = new Category([]);
		$this->category = $catService->get($ticket->catid);

		// Add the intermediate category breadcrumbs
		$this->addCategoryPathToBreadcrumbs($this->category);

		// Add a breadcrumb for the ticket
		$app->getPathway()->addItem('#' . $ticket->id, Route::_(RouteHelper::getTicketRoute($ticket->id, $ticket->catid)));

		// Add a breadcrumb to the post
		$app->getPathway()->addItem(Text::sprintf('COM_ATS_POST_LBL_BREADCRUMB', $this->item->id), '');
	}

}