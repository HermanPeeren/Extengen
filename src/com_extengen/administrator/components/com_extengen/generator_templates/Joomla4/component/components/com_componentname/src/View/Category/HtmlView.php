<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\View\Category;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Table\TicketTable;
use Akeeba\Component\ATS\Administrator\View\Mixin\LoadAnyTemplate;
use Akeeba\Component\ATS\Site\View\Mixin\CategoryBreadcrumbsAware;
use Akeeba\Component\ATS\Site\View\Mixin\CategoryFieldsAware;
use Akeeba\Component\ATS\Site\View\Mixin\ModuleRenderAware;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\CategoryView;
use Joomla\Registry\Registry;

class HtmlView extends CategoryView
{
	use ModuleRenderAware;
	use LoadAnyTemplate;
	use CategoryBreadcrumbsAware;
	use CategoryFieldsAware;

	/**
	 * Display parameters
	 *
	 * @var   Registry
	 * @since 5.0.0
	 */
	public $params;

	/** @inheritdoc */
	protected $extension = 'com_ats';

	/**
	 * Tickets in the category
	 *
	 * @var    TicketTable[]
	 * @since  5.0.0
	 */
	protected $items;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public function display($tpl = null)
	{
		$state       = $this->get('State');

		parent::commonCategoryDisplay();

		// Custom fields
		$this->category = $this->processCategoryFieldsDisplay($this->category, 2);
		$this->items    = array_map(function (TicketTable $item) {
			try
			{
				$this->runPlugins('onContentPrepare', 'com_ats.ticket', $item, null, 0);
			}
			catch (\Throwable $e)
			{
				// Ignore third party plugins failing.
				Log::add(sprintf(
					"Third party onContentPrepare plugin failed to run: #%d %s\nTrace:\n%s(%d)\n%s",
					$e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()
				), Log::DEBUG, 'com_ats');
			}

			return $item;
		}, $this->items ?? []);

		// Flag indicates to not add limitstart=0 to URL
		$this->pagination->hideEmptyLimitstart = true;

		parent::display($tpl);
	}

	/**
	 * Method to prepares the document
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   5.0.0
	 */
	protected function prepareDocument()
	{
		// Add breadcrumbs from the menu item's category to this category, INCLUDING the current category.
		$this->addCategoryPathToBreadcrumbs($this->category);

		// Set the document meta
		parent::prepareDocument();
	}
}