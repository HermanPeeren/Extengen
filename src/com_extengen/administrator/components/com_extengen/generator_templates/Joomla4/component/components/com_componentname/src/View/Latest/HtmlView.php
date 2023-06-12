<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\View\Latest;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\View\Mixin\LoadAnyTemplate;
use Akeeba\Component\ATS\Site\Model\CategoryModel;
use Akeeba\Component\ATS\Site\View\Mixin\ModuleRenderAware;
use Akeeba\Component\ATS\Site\View\Mixin\PageMetaAware;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;

class HtmlView extends BaseHtmlView
{
	use ModuleRenderAware;
	use LoadAnyTemplate;
	use PageMetaAware;

	/**
	 * Pagination object
	 *
	 * @var    Pagination
	 * @since  5.0.0
	 */
	protected $pagination;

	/**
	 * State data
	 *
	 * @var    Registry
	 * @since  5.0.0
	 */
	protected $state;

	/**
	 * @var
	 * @since version
	 */
	private $defaultPageTitle = 'COM_ATS_LATEST_LBL_PAGE_TITLE';

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
		$app    = Factory::getApplication();
		$user   = $app->getIdentity();
		$params = $app->getParams();

		// Get some data from the models
		/** @var CategoryModel $model */
		$model = $this->getModel();

		// Check for errors.
		if (\count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		// Escape strings for HTML output
		$this->pageclass_sfx = $this->escape($params->get('pageclass_sfx'));

		$this->state      = $this->get('State');
		$this->items      = $this->get('Items');
		$this->params     = $params;
		$this->pagination = $this->get('Pagination');
		$this->user       = $user;


		// Flag indicates to not add limitstart=0 to URL
		$this->pagination->hideEmptyLimitstart = true;

		$this->prepareDocument();

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
		$app           = Factory::getApplication();
		$this->pathway = $app->getPathway();

		// Because the application sets a default page title, we need to get it from the menu item itself
		$this->menu = $app->getMenu()->getActive();

		$this->params->def('page_heading', $this->menu ? $this->params->get('page_title', $this->menu->title) : Text::_($this->defaultPageTitle));

		$this->setPageMeta();
	}
}