<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Service\Html;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Helper\CountryHelper;
use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use DateTimeZone;
use Exception;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\DatabaseAwareTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Utilities\ArrayHelper;

/**
 * HTMLHelper class for Akeeba Ticket System.
 *
 * All public methods are accessible through HTMLHelper('ats.methodName')
 *
 * @since  5.0.0
 */
class ATS
{
	use DatabaseAwareTrait;

	/**
	 * Public constructor.
	 *
	 * @param   DatabaseDriver  $db  The Joomla DB driver object for the site's database.
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->setDbo($db);
	}

	/**
	 * Returns the Emoji country flag given its ISO-3166 alpha-2 code.
	 *
	 * @param   string|null  $countryCode  The ISO-3166 alpha–2 country code
	 *
	 * @return  string  The country flag as an HTML entity encoded Emoji flag
	 *
	 * @since   5.0.0
	 */
	public function countryFlag(?string $countryCode = null): string
	{
		return CountryHelper::countryToEmoji($countryCode);
	}

	/**
	 * Returns the human–readable name of a country given its ISO-3166 alpha-2 code.
	 *
	 * @param   string|null  $countryCode  The ISO-3166 alpha–2 country code
	 *
	 * @return  string  The country name, in English
	 *
	 * @since   5.0.0
	 */
	public function countryName(?string $countryCode = null): string
	{
		return CountryHelper::decodeCountry($countryCode);
	}

	/**
	 * Format a date. Default is to format as a GMT date in DATE_FORMAT_LC5 format.
	 *
	 * @param   string       $date      The date to format.
	 * @param   string|null  $format    The format to use, default is DATE_FORMAT_LC5
	 * @param   bool         $local     Display the date in the user's local timezone? Default: true.
	 * @param   bool         $timezone  Include timezone if $format doesn't do so already? Default: true.
	 *
	 * @return  string  The formatted date
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public function date(?string $date, ?string $format = null, bool $local = true, bool $timezone = true): string
	{
		$date   = new Date($date ?? 'now', 'GMT');
		$format = $format ?: Text::_('DATE_FORMAT_LC5');

		if ($timezone && substr($format, -1) != 'T')
		{
			$format .= ' T';
		}

		if ($local)
		{
			$app  = Factory::getApplication();
			$zone = Permissions::getUser()->getParam('timezone', $app->get('offset', 'UTC'));
			$tz   = new DateTimeZone($zone);

			$date->setTimezone($tz);
		}

		return $date->format($format, $local);
	}

	/**
	 * Show the public/private icon.
	 *
	 * @param   integer  $value      The public value.
	 * @param   integer  $i          ID of the item.
	 * @param   boolean  $canChange  Whether the value can be changed or not.
	 *
	 * @return  string    The HTML
	 *
	 * @since   5.0.0
	 */
	public function publicToggle(int $value, int $i, bool $canChange = true): string
	{
		// Array of image, task, title, action
		$states      = [
			1 => [
				'public', 'tickets.makeprivate', 'COM_ATS_TICKETS_PUBLIC_MAKE_PRIVATE', 'COM_ATS_TICKETS_PUBLIC_PUBLIC',
			],
			0 => [
				'private', 'tickets.makepublic', 'COM_ATS_TICKETS_PUBLIC_MAKE_PUBLIC', 'COM_ATS_TICKETS_PUBLIC_PRIVATE',
			],
		];
		$state       = ArrayHelper::getValue($states, (int) $value, $states[1]);
		$icon        = $state[0] === 'public' ? 'fa fa-eye border-warning text-warning' : 'fa fa-eye-slash border-success text-success';
		$onclick     = 'onclick="return Joomla.listItemTask(\'cb' . $i . '\',\'' . $state[1] . '\')"';
		$tooltipText = Text::_($state[3]);

		if (!$canChange)
		{
			$onclick     = 'disabled';
			$tooltipText = Text::_($state[2]);
		}

		$html = '<button type="submit" class="tbody-icon' . ($value == 1 ? ' active' : '') . '"'
			. ' aria-labelledby="cb' . $i . '-desc" ' . $onclick . '>'
			. '<span class="icon-' . $icon . '" aria-hidden="true"></span>'
			. '</button>'
			. '<div role="tooltip" id="cb' . $i . '-desc">' . $tooltipText . '</div>';

		return $html;
	}

	/**
	 * Returns a fancy formatted time lapse code
	 *
	 * @param   int          $referenceTime  Timestamp of the reference date / time
	 * @param   string|null  $currentTime    Timestamp of the current date / time
	 * @param   string       $quantifyBy     Quantify by unit, one of s, m, h, d, y
	 * @param   bool         $autoText       Automatically append text
	 *
	 * @return  string  The HTML
	 * @since   5.0.0
	 */
	public function timeAgo(int $referenceTime = 0, ?string $currentTime = null, string $quantifyBy = '', bool $autoText = true): string
	{
		if (is_null($currentTime))
		{
			$currentTime = time();
		}

		// Raw time difference
		$rawTimeDifference      = $currentTime - $referenceTime;
		$absoluteTimeDifference = abs($rawTimeDifference);

		$uomMap = [
			['s', 60],
			['m', 60 * 60],
			['h', 60 * 60 * 24],
			['d', 60 * 60 * 24 * 365],
			['y', 0],
		];

		$textMap = [
			's' => [1, 'COM_ATS_TIME_SECOND'],
			'm' => [60, 'COM_ATS_TIME_MINUTE'],
			'h' => [60 * 60, 'COM_ATS_TIME_HOUR'],
			'd' => [60 * 60 * 24, 'COM_ATS_TIME_DAY'],
			'y' => [60 * 60 * 24 * 365, 'COM_ATS_TIME_YEAR'],
		];

		if ($quantifyBy == '')
		{
			$uom = 's';

			for ($i = 0; $i < count($uomMap); $i++)
			{
				if ($absoluteTimeDifference <= $uomMap[$i][1])
				{
					$uom = $uomMap[$i][0];

					break;
				}
			}

			if ($absoluteTimeDifference > 60 * 60 * 24 * 365)
			{
				$uom = 'y';
			}
		}
		else
		{
			$uom = $quantifyBy;
		}

		$dateDifference = floor($absoluteTimeDifference / $textMap[$uom][0]);

		$prefix = '';
		$suffix = '';

		if ($autoText == true && ($currentTime == time()))
		{
			if ($rawTimeDifference < 0)
			{
				$prefix = Text::_('COM_ATS_TIME_AFTER_PRE');
				$suffix = Text::_('COM_ATS_TIME_AFTER_POST');
			}
			else
			{
				$prefix = Text::_('COM_ATS_TIME_AGO_PRE');
				$suffix = Text::_('COM_ATS_TIME_AGO_POST');
			}
		}

		if ($prefix)
		{
			$prefix = trim($prefix) . ' ';
		}

		if ($suffix)
		{
			$suffix = ' ' . trim($suffix);
		}

		return $prefix . $dateDifference . ' ' . Text::_($textMap[$uom][1]) . $suffix;
	}

}