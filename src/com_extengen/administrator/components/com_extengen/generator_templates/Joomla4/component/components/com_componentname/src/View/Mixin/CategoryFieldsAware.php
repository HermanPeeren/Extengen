<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\View\Mixin;

use Exception;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Event\Event;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die;

/**
 * Trait to add support for category custom fields
 */
trait CategoryFieldsAware
{
	/**
	 * Custom fields helper method for template overrides.
	 *
	 * Returns the Joomla custom fields keyed by name (instead of ID) -or- returns a single field by name.
	 *
	 * @param   mixed        $item  The object whose jcfields we will process.
	 * @param   string|null  $name  The name of the field to get. NULL to get all fields.
	 *
	 * @return  array|object|null  Array of fields if name is NULL. Field object if name is found, NULL otherwise.
	 * @since   5.0.0
	 */
	public function getFieldsByName($item, ?string $name = null)
	{
		$fields = is_object($item) ? ($item->jcfields ?? []) : [];

		if (!is_array($fields))
		{
			$fields = [];
		}

		$fields = ArrayHelper::pivot($fields, 'name');

		if (empty($name))
		{
			return $fields;
		}

		return $fields[$name] ?? null;
	}

	/**
	 * Process the display of custom fields in a category object.
	 *
	 * Populates the jcfields, afterTitle, beforeContent and afterContent properties of the object.
	 *
	 * @param   CategoryNode  $cat       The category where custom fields display is going to be processed
	 * @param   int           $showWhen  Type of display: 1=summary (categories view), 2=ticket view
	 *
	 * @return  CategoryNode The processed note
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public function processCategoryFieldsDisplay(CategoryNode $cat, int $showWhen = 1): CategoryNode
	{
		$cat->params = is_object($cat->params) ? $cat->params : new Registry($cat->params);

		// Add the display data for fields which get automatically displayed
		$showFields         = in_array($cat->params->get('show_custom_fields', 3), [$showWhen, 3]);
		$cat->afterTitle    = $showFields
			? $this->trimStringArray($this->runPlugins('onContentAfterTitle', 'com_ats.categories', $cat, null))
			: [];
		$cat->beforeContent = $showFields
			? $this->trimStringArray($this->runPlugins('onContentBeforeDisplay', 'com_ats.categories', $cat, null))
			: [];
		$cat->afterContent  = $showFields
			? $this->trimStringArray($this->runPlugins('onContentAfterDisplay', 'com_ats.categories', $cat, null))
			: [];

		// Populate the jcfields property of the category object for custom handling of fields in view template overrides
		try
		{
			$this->runPlugins('onContentPrepare', 'com_ats.categories', $cat, null, 0);
		}
		catch (\Throwable $e)
		{
			// Ignore third party plugins failing.
			Log::add(sprintf(
				"Third party onContentPrepare plugin failed to run: #%d %s\nTrace:\n%s(%d)\n%s",
				$e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()
			), Log::DEBUG, 'com_ats');
		}

		return $cat;
	}

	/**
	 * Run a plugin event and return the plugins' replies as an array.
	 *
	 * @param   string  $eventName  The event name, e.g. 'onFooBar'
	 * @param   mixed   ...$args    The arguments to the event
	 *
	 * @return  array  The plugin results
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	private function runPlugins($eventName, ...$args)
	{
		/** @var SiteApplication $app */
		$app        = Factory::getApplication();
		$dispatcher = $app->getDispatcher();

		$event  = new Event($eventName, $args);
		$result = $dispatcher->dispatch($eventName, $event);

		return !isset($result['result']) || \is_null($result['result']) ? [] : $result['result'];
	}

	/**
	 * Removes non–string and empty elements from an array ostensibly consisting of strings.
	 *
	 * @param   array  $array  The array to filter
	 *
	 * @return  array  The filtered array
	 *
	 * @since   5.0.0
	 */
	private function trimStringArray(array $array)
	{
		return array_filter($array, function ($x) {
			return is_string($x) && !empty($x);
		});
	}
}