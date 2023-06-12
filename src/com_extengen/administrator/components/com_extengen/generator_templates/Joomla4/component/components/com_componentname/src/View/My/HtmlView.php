<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\View\My;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Akeeba\Component\ATS\Administrator\View\Mixin\LoadAnyTemplate;
use Akeeba\Component\ATS\Site\Model\CategoryModel;
use Akeeba\Component\ATS\Site\View\Mixin\ModuleRenderAware;
use Akeeba\Component\ATS\Site\View\Mixin\PageMetaAware;
use Exception;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

class HtmlView extends BaseHtmlView
{
	use ModuleRenderAware;
	use LoadAnyTemplate;
	use PageMetaAware;

	/**
	 * Can the user file new tickets?
	 *
	 * @var   bool
	 * @since 5.0.0
	 */
	protected $canFileTickets = false;

	/**
	 * Default translation key for the page title
	 *
	 * @var   string
	 * @since 5.0.0
	 */
	protected $defaultPageTitle = 'COM_ATS_MY_LBL_PAGE_TITLE';

	/**
	 * The tickets to display
	 *
	 * @var   array|null
	 * @since 5.0.0
	 */
	protected $items;

	/**
	 * Are these my tickets? FALSE if it's a different user's tickets.
	 *
	 * @var   bool
	 * @since 5.0.0
	 */
	protected $myTickets;

	/**
	 * Pagination object
	 *
	 * @var    Pagination
	 * @since  5.0.0
	 */
	protected $pagination;

	/**
	 * Page parameters
	 *
	 * @var   Registry
	 * @since 5.0.0
	 */
	protected $params;

	/**
	 * State data
	 *
	 * @var    Registry
	 * @since  5.0.0
	 */
	protected $state;

	/**
	 * User tickets are displayed for
	 *
	 * @var   User|null
	 * @since 5.0.0
	 */
	protected $user;

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

		$userId               = $model->getState('filter.created_by', $user->id);
		$this->state          = $this->get('State');
		$this->items          = $this->get('Items');
		$this->params         = $params;
		$this->pagination     = $this->get('Pagination');
		$this->user           = Permissions::getUser($userId);
		$this->myTickets      = Permissions::getUser()->id == $this->user->id;
		$this->canFileTickets = $this->canFileTickets();

		// Flag indicates to not add limitstart=0 to URL
		$this->pagination->hideEmptyLimitstart = true;

		$this->prepareDocument();

		parent::display($tpl);
	}

	protected function canFileTickets()
	{
		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');
		$me = Permissions::getUser();

		$query      = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__categories'))
			->where([
				$db->quoteName('extension') . '=' . $db->quote('com_ats'),
				$db->quoteName('published') . '=' . $db->quote('1'),
			])
			->whereIn($db->quoteName('access'), $me->getAuthorisedGroups());
		$categories = $db->setQuery($query)->loadColumn() ?: [];

		foreach ($categories as $catid)
		{
			$permissions = Permissions::getAclPrivileges($catid);

			if ($permissions['core.create'])
			{
				return true;
			}
		}

		return false;
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
		/** @var SiteApplication $app */
		$app = Factory::getApplication();
		$this->pathway = $app->getPathway();

		// Because the application sets a default page title, we need to get it from the menu item itself
		$this->menu = $app->getMenu()->getActive();

		$this->params->def('page_heading', $this->menu ? $this->params->get('page_title', $this->menu->title) : Text::_($this->defaultPageTitle));

		$this->setPageMeta();
	}
}