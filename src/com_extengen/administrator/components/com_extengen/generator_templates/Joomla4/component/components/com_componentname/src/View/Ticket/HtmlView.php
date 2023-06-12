<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\View\Ticket;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Akeeba\Component\ATS\Administrator\View\Ticket\HtmlView as AdminHtmlView;
use Akeeba\Component\ATS\Site\Helper\RouteHelper;
use Akeeba\Component\ATS\Site\Service\Category;
use Akeeba\Component\ATS\Site\View\Mixin\CategoryBreadcrumbsAware;
use Akeeba\Component\ATS\Site\View\Mixin\CategoryFieldsAware;
use Akeeba\Component\ATS\Site\View\Mixin\ModuleRenderAware;
use Akeeba\Component\ATS\Site\View\Mixin\PageMetaAware;
use Exception;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

class HtmlView extends AdminHtmlView
{
	use ModuleRenderAware;
	use CategoryBreadcrumbsAware;
	use CategoryFieldsAware;
	use PageMetaAware;

	/**
	 * The node object of the category the current ticket belongs in.
	 *
	 * @var   CategoryNode|null
	 * @since 5.0.0
	 */
	public $category;

	/**
	 * The URL to return to after editing
	 *
	 * @var   string|null
	 * @since 5.0.0
	 */
	public $returnUrl;

	/**
	 * Results of plugins responding to the onContentAfterDisplay event
	 *
	 * @var   array
	 * @since 5.0.0
	 */
	protected $afterContent;

	/**
	 * Results of plugins responding to the onAfterTicketConversationDisplay event
	 *
	 * @var   array
	 * @since 3.0.0
	 */
	protected $afterConversationResults;

	/**
	 * Results of plugins responding to the onContentAfterTitle event
	 *
	 * @var   array
	 * @since 5.0.0
	 */
	protected $afterTitle;

	/**
	 * Results of plugins responding to the onContentBeforeDisplay event
	 *
	 * @var   string[]
	 * @since 5.0.0
	 */
	protected $beforeContent;

	/**
	 * Results of plugins responding to the onBeforeTicketConversationDisplay event
	 *
	 * @var   string[]
	 * @since 3.0.0
	 */
	protected $beforeConversationResults;

	/**
	 * Returns a URL for an action which returns back to the current page
	 *
	 * @param   string  $task    The task to perform: either bare task or viewName.taskName
	 * @param   array   $params  Additional query string parameters to include
	 *
	 * @return  string  A non-SEF URL; pass it through Joomla's CMS Route to use in the frontend
	 *
	 * @since   5.0.0
	 */
	protected function actionUrl(string $task, array $params = []): string
	{
		/** @var SiteApplication $app */
		$app       = Factory::getApplication();
		$returnUrl = base64_encode(Uri::getInstance());
		$token     = $app->getFormToken();


		$taskParts = explode('.', $task);

		if (count($taskParts) > 1)
		{
			$view = $taskParts[0];
			$task = $taskParts[1];
			$urlTask = sprintf("%s.%s", $view, $task);
		}
		else
		{
			$view = $params['view'] ?? $this->getName();
			$urlTask = $task;
		}

		$idKey = in_array($task, ['edit']) ? 'id' : 'cid';
		$id    = (isset($this->item) && is_object($this->item)) ? ($this->item->id ?? null) : null;

		$Itemid = Factory::getApplication()->input->getInt('Itemid', -1) ?: -1;

		$params = array_merge([
			'option'    => 'com_ats',
			'task'      => $urlTask,
			$idKey      => $id,
			'returnurl' => urlencode($returnUrl),
			'Itemid'    => $Itemid,
			$token      => 1,
		], $params);

		$params = array_filter($params, function ($x) {
			return !is_null($x);
		});

		return 'index.php?' . http_build_query($params);
	}

	/**
	 * Overridden to add front–end specific behaviour
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	protected function addToolbar(): void
	{
		/** @var SiteApplication $app */
		$app = Factory::getApplication();

		// Load Joomla's backend and frontend language (so that some form field labels are not left untranslated).
		$lang = $app->getLanguage();
		$lang->load('joomla', JPATH_ADMINISTRATOR, null, true);
		$lang->load('joomla', JPATH_SITE, null, true);

		// Get the category of the ticket
		$catid = empty($this->category) ? $this->item->catid : $this->category->id;

		if ($this->getLayout() != 'default')
		{
			$catid = $this->form ? $this->form->getValue('catid', null, $catid) : $catid;
		}

		$this->category = $this->category ?: (new Category([]))->get($catid);
		// Load plugins
		PluginHelper::importPlugin('ats');
		PluginHelper::importPlugin('content');

		// Prepare the custom fields
		$pseudoObject       = (object) $this->item->getProperties();
		$pseudoObject->text = '';
		try
		{
			$this->runPlugins('onContentPrepare', 'com_ats.ticket', $pseudoObject, null, 0);
		}
		catch (\Throwable $e)
		{
			// Ignore third party plugins failing.
			Log::add(sprintf(
				"Third party onContentPrepare plugin failed to run: #%d %s\nTrace:\n%s(%d)\n%s",
				$e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()
			), Log::DEBUG, 'com_ats');
		}

		if (isset($pseudoObject->jcfields))
		{
			$this->item->jcfields = $pseudoObject->jcfields;
		}

		// Run the plugin events.
		$this->beforeConversationResults =
			$this->trimStringArray($this->runPlugins('onBeforeTicketConversationDisplay', $this->item));

		$this->afterConversationResults =
			$this->trimStringArray($this->runPlugins('onAfterTicketConversationDisplay', $this->item));

		// Handle custom fields display
		$this->afterTitle    = $this->trimStringArray([$this->getCustomFieldsDisplay(1)]);
		$this->beforeContent = $this->trimStringArray([$this->getCustomFieldsDisplay(2)]);
		$this->afterContent  = $this->trimStringArray([$this->getCustomFieldsDisplay(3)]);

		// Add the intermediate category breadcrumbs
		if ($this->category)
		{
			$this->addCategoryPathToBreadcrumbs($this->category);
		}

		// Add a breadcrumb for the ticket
		if (!empty($this->item->id))
		{
			$app->getPathway()->addItem('#' . $this->item->id, Route::_(RouteHelper::getTicketRoute($this->item->id, $catid)));
		}
		else
		{
			$app->getPathway()->addItem(Text::_('COM_ATS_TITLE_TICKETS_ADD'), '');
		}

		// Load the necessary JavaScript
		$app->getDocument()->getWebAssetManager()
			->useScript('com_ats.tickets_frontend');

		// If this is a new ticket and nonewtickets has been enabled we will switch to the corresponding layout
		$isNewTicket  = empty($this->item->id) || ($this->item->id < 0);
		$cParams      = ComponentHelper::getParams('com_ats');
		$noNewTickets = $cParams->get('nonewtickets', 0) == 1;

		if ($isNewTicket && $noNewTickets)
		{
			$this->setLayout('nonewtickets');
		}

		$this->setPageMeta();
	}

	/**
	 * Get custom fields for display.
	 *
	 * Unlike Joomla's built–in Content - Fields plugin this method will also filter by ATS' custom Private Display
	 * field property.
	 *
	 * @param   int  $displayType  1: After title. 2: Before content. 3: After content.
	 *
	 * @return  string
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	private function getCustomFieldsDisplay(int $displayType): string
	{
		// Get the fields
		$fields = $this->item->jcfields;

		if (empty($fields))
		{
			return '';
		}

		// Filter fields by category language
		$catLanguage = $this->category->language;
		$app         = Factory::getApplication();

		if (Multilanguage::isEnabled() && ($catLanguage === '*'))
		{
			$lang = $app->getLanguage()->getTag();

			foreach ($fields as $key => $field)
			{
				if (!in_array($field->language, ['*', $lang]))
				{
					unset($fields[$key]);
				}
			}
		}

		if (empty($fields))
		{
			return '';
		}

		// Filter fields by display type
		foreach ($fields as $key => $field)
		{
			if ($field->params->get('display', '2') != $displayType)
			{
				unset($fields[$key]);
			}
		}

		if (empty($fields))
		{
			return '';
		}

		// Filter fields by ATS Private Display
		$me                = Permissions::getUser();
		$canDisplayPrivate = !$me->guest && (($me->id == $this->item->created_by) || Permissions::isManager($this->item->catid, $me->id));

		if (!$canDisplayPrivate)
		{
			foreach ($fields as $key => $field)
			{
				if ($field->fieldparams->get('atsPrivate', '0') == 1)
				{
					unset ($fields[$key]);
				}
			}
		}

		if (empty($fields))
		{
			return '';
		}

		// Render the fields
		$context = 'com_ats.ticket';

		LayoutHelper::$defaultBasePath = JPATH_SITE . '/components/com_ats/layouts';

		return FieldsHelper::render(
			$context,
			'fields.render',
			[
				'item'    => $this->item,
				'context' => $context,
				'fields'  => $fields,
			]
		);
	}
}