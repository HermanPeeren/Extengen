<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Helper;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use ReflectionClass;

class ComponentParams
{
	/**
	 * All possible ticket statuses and their description
	 *
	 * @var   array
	 * @since 5.0.0
	 */
	private static $ticketStatuses;

	/**
	 * Actually Save the params into the db
	 *
	 * @param   Registry     $params
	 * @param   string       $element
	 * @param   string       $type
	 * @param   string|null  $folder
	 * @param   bool         $throwException
	 *
	 * @since   5.0.0
	 */
	public static function save(Registry $params, string $element = 'com_ats', string $type = 'component', ?string $folder = null, bool $throwException = false): void
	{
		/** @var DatabaseDriver $db */
		$db   = JoomlaFactory::getContainer()->get('DatabaseDriver');
		$data = $params->toString('JSON');

		$sql = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params') . ' = ' . $db->q($data))
			->where($db->qn('element') . ' = :element')
			->where($db->qn('type') . ' = :type')
			->bind(':element', $element)
			->bind(':type', $type);

		if (!empty($folder))
		{
			$sql->where($db->quoteName('folder') . ' = :folder')
				->bind(':folder', $folder);
		}

		$db->setQuery($sql);

		try
		{
			$db->execute();

			// The extension parameters are cached. We just changed them. Therefore we MUST reset the system cache which holds them.
			CacheCleaner::clearCacheGroups(['_system'], [0, 1]);
		}
		catch (Exception $e)
		{
			// Don't sweat if it fails unless told otherwise
			if ($throwException)
			{
				throw $e;
			}
		}

		// Reset ComponentHelper's cache
		if ($type === 'component')
		{
			$refClass = new ReflectionClass(ComponentHelper::class);
			$refProp  = $refClass->getProperty('components');
			$refProp->setAccessible(true);
			$components                    = $refProp->getValue();
			$components[$element]->params = $params;
			$refProp->setValue($components);
		}
		elseif ($type === 'plugin')
		{
			$refClass = new ReflectionClass(PluginHelper::class);
			$refProp  = $refClass->getProperty('plugins');
			$refProp->setAccessible(true);
			$plugins = $refProp->getValue();

			foreach ($plugins as $plugin)
			{
				if ($plugin->type === $folder && $plugin->name == $element)
				{
					$plugin->params = $params->toString('JSON');
				}
			}

			$refProp->setValue($plugins);
		}
	}

	/**
	 * Get all possible ticket statuses
	 *
	 * @return  array
	 *
	 * @since   5.0.0
	 */
	public static function getStatuses(): array
	{
		if (!is_null(self::$ticketStatuses))
		{
			return self::$ticketStatuses;
		}

		self::$ticketStatuses = [
			'O' => Text::_('COM_ATS_TICKETS_STATUS_O'),
			'P' => Text::_('COM_ATS_TICKETS_STATUS_P'),
		];

		$custom = ComponentHelper::getParams('com_ats')->get('customStatuses', '');

		if (is_string($custom))
		{
			$custom = str_replace("\\n", "\n", $custom);
			$custom = str_replace("\r", "\n", $custom);
			$custom = str_replace("\n\n", "\n", $custom);
			$lines  = explode("\n", $custom);

			foreach ($lines as $line)
			{
				$parts = explode('=', $line);

				if (count($parts) != 2)
				{
					continue;
				}

				$parts[0] = trim($parts[0]);
				$parts[1] = trim($parts[1]);

				if (!is_numeric($parts[0]))
				{
					continue;
				}

				$statusId = (int) $parts[0];

				if (($statusId <= 0) || ($statusId > 99))
				{
					continue;
				}

				$description = $parts[1];

				if (empty($description))
				{
					continue;
				}

				self::$ticketStatuses[$statusId] = Text::_($description);
			}
		}
		elseif (is_object($custom))
		{
			foreach ((array)$custom as $customStatus)
			{
				$id = $customStatus->id ?? null;
				$label = $customStatus->label ?? null;

				if (empty($id) || (int)$id < 1 || (int)$id > 99 || empty($label))
				{
					continue;
				}

				self::$ticketStatuses[$id] = Text::_($label);
			}
		}

		// The Closed status must always be AFTER any custom status
		self::$ticketStatuses['C'] = Text::_('COM_ATS_TICKETS_STATUS_C');

		return self::$ticketStatuses;
	}

}