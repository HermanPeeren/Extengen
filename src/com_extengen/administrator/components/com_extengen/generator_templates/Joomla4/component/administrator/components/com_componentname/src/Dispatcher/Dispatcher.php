<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Dispatcher;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Controller\Mixin\TriggerEvent;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Document\HtmlDocument;
use Throwable;

/**
 * Component Dispatcher.
 *
 * Pre–processes the request data sent to us by the Joomla application and handles the part of the request pertaining
 * to the component.
 *
 * @since  5.0.0
 */
class Dispatcher extends ComponentDispatcher
{
	use TriggerEvent;

	/**
	 * Keys of common media files to load.
	 *
	 * The prefixes of each string can be preset, style or script.
	 *
	 * @var   string[]
	 * @since 5.0.0
	 */
	protected $commonMediaKeys = ['preset:com_ats.backend'];

	/**
	 * The default controller (and view), if none is specified in the request.
	 *
	 * @var   string
	 * @since 5.0.0
	 */
	protected $defaultController = 'controlpanel';

	/**
	 * Maps old versions' view names to the current view names.
	 *
	 * IMPORTANT! The keys must be in ALL LOWERCASE.
	 *
	 * @var   array
	 * @since 5.0.0
	 */
	protected $viewMap = [];

	/** @inheritdoc */
	public function dispatch()
	{
		// Check the minimum supported PHP version
		$minPHPVersion = '7.2.0';
		$softwareName  = 'Akeeba Ticket System';
		$silentResults = $this->app->isClient('site');

		if (!@include_once JPATH_ADMINISTRATOR . '/components/com_ats/tmpl/common/wrongphp.php')
		{
			return;
		}

		try
		{
			$this->triggerEvent('onBeforeDispatch');

			parent::dispatch();

			// This will only execute if there is no redirection set by the Controller
			$this->triggerEvent('onAfterDispatch');
		}
		catch (Throwable $e)
		{
			$title = 'Akeeba Ticket System';
			$isPro = false;

			// Frontend: forwards errors 401, 403 and 404 to Joomla
			if (in_array($e->getCode(), [401, 403, 404]) && $this->app->isClient('site'))
			{
				throw $e;
			}

			if (!(include_once JPATH_ADMINISTRATOR . '/components/com_ats/tmpl/common/errorhandler.php'))
			{
				throw $e;
			}
		}
	}

	/**
	 * Applies the view and controller to the input object communicated to the MVC objects.
	 *
	 * If we have a controller without view or just a task=controllerName.taskName we populate the view to make things
	 * easier and more consistent for us to handle.
	 *
	 * @return  void
	 * @since   5.0.0
	 */
	protected function applyViewAndController(): void
	{
		$controller = $this->input->getCmd('controller', null);
		$view       = $this->input->getCmd('view', null);
		$task       = $this->input->getCmd('task', 'main');

		if (strpos($task, '.') !== false)
		{
			// Explode the controller.task command.
			[$controller, $task] = explode('.', $task);
		}

		if (empty($controller) && empty($view))
		{
			$controller = $this->defaultController;
			$view       = $this->defaultController;
		}
		elseif (empty($controller) && !empty($view))
		{
			$view       = $this->mapView($view);
			$controller = $view;
		}
		elseif (!empty($controller) && empty($view))
		{
			$view = $controller;
		}

		$controller = strtolower($controller);
		$view       = strtolower($view);

		$this->input->set('view', $view);
		$this->input->set('controller', $controller);
		$this->input->set('task', $task);
	}

	/**
	 * Preload common static media files (CSS, JS) used throughout this side of the application.
	 *
	 * @return  void
	 * @since   5.0.0
	 * @internal
	 */
	final protected function loadCommonStaticMedia(): void
	{
		// Make sure we run under a CMS application
		if (!($this->app instanceof CMSApplication))
		{
			return;
		}

		// Make sure the document is HTML
		$document = $this->app->getDocument();

		if (!($document instanceof HtmlDocument))
		{
			return;
		}

		// Finally, load our 'common' backend preset
		$webAssetManager = $document->getWebAssetManager();

		foreach ($this->commonMediaKeys as $keyString)
		{
			[$prefix, $key] = explode(':', $keyString, 2);

			switch ($prefix)
			{
				case 'preset':
					$webAssetManager->usePreset($key);
					break;

				case 'style':
					$webAssetManager->useStyle($key);
					break;

				case 'script':
					$webAssetManager->useScript($key);
					break;
			}
		}
	}

	/**
	 * Loads the language files for this component.
	 *
	 * Always loads the backend translation file. In the site, CLI and API applications it also loads the frontend
	 * language file and the current application's language file.
	 *
	 * @return  void
	 * @since   5.0.0
	 * @internal
	 */
	final protected function loadLanguage(): void
	{
		$jLang = $this->app->getLanguage();

		// Always load the admin language files
		$jLang->load($this->option, JPATH_ADMINISTRATOR);

		$isAdmin = $this->app->isClient('administrator');
		$isSite  = $this->app->isClient('site');

		// Load the language file specific to the current application. Only applies to site, CLI and API applications.
		if (!$isAdmin)
		{
			$jLang->load($this->option, JPATH_BASE);
		}

		// Load the frontend language files in the CLI and API applications.
		if (!$isAdmin && !$isSite)
		{
			$jLang->load($this->option, JPATH_SITE);
		}
	}

	/**
	 * Loads the version.php file. If it doesn't exist, fakes the version constants to simulate a dev release.
	 *
	 * @return  void
	 * @since   5.0.0
	 * @internal
	 */
	final protected function loadVersion(): void
	{
		$filePath = JPATH_ADMINISTRATOR . '/components/com_ats/version.php';

		if (@file_exists($filePath) && is_file($filePath))
		{
			include_once $filePath;
		}

		if (!defined('ATS_VERSION'))
		{
			define('ATS_VERSION', 'dev');
		}

		if (!defined('ATS_DATE'))
		{
			define('ATS_DATE', gmdate('Y-m-d'));
		}

		if (!defined('ATS_PRO'))
		{
			$isPro = @is_dir(JPATH_ADMINISTRATOR . '/components/com_ats/src/CliCommand');

			define('ATS_PRO', $isPro ? '1' : '0');
		}
	}

	/**
	 * Maps an old view name to a new view name
	 *
	 * @param   string  $view
	 *
	 * @return  string
	 *
	 * @since   5.0.0
	 * @internal
	 */
	protected function mapView(string $view): string
	{
		$view = strtolower($view);

		return $this->viewMap[$view] ?? $view;
	}

	/**
	 * Executes before dispatching a request made to this component
	 *
	 * @return  void
	 * @since   5.0.0
	 */
	protected function onBeforeDispatch(): void
	{
		$this->loadLanguage();

		$this->applyViewAndController();

		$this->loadVersion();

		$this->loadCommonStaticMedia();
	}
}