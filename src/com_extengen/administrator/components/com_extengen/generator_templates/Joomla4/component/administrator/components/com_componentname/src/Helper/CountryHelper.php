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
use Joomla\CMS\Factory;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Helper class for ISO-3166 alpha-2 country codes and their ISO-prescribed names in English.
 *
 * @package Akeeba\Component\Paddle\Administrator\Helper
 * @since   5.0.0
 */
class CountryHelper
{
	/**
	 * List of ISO-3166 alpha-2 codes and ISO-prescribed country names in English.
	 *
	 * Note that some country names may NOT be what the countries call themselves but what the U.N. calls them, e.g.
	 * “Taiwan, Province of China” instead of “Taiwan”.
	 *
	 * @see https://github.com/lukes/ISO-3166-Countries-with-Regional-Codes/tree/master/slim-2
	 */
	private const COUNTRIES = [
		'AF' => 'Afghanistan',
		'AX' => 'Åland Islands',
		'AL' => 'Albania',
		'DZ' => 'Algeria',
		'AS' => 'American Samoa',
		'AD' => 'Andorra',
		'AO' => 'Angola',
		'AI' => 'Anguilla',
		'AQ' => 'Antarctica',
		'AG' => 'Antigua and Barbuda',
		'AR' => 'Argentina',
		'AM' => 'Armenia',
		'AW' => 'Aruba',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BS' => 'Bahamas',
		'BH' => 'Bahrain',
		'BD' => 'Bangladesh',
		'BB' => 'Barbados',
		'BY' => 'Belarus',
		'BE' => 'Belgium',
		'BZ' => 'Belize',
		'BJ' => 'Benin',
		'BM' => 'Bermuda',
		'BT' => 'Bhutan',
		'BO' => 'Bolivia',
		'BQ' => 'Bonaire, Sint Eustatius and Saba',
		'BA' => 'Bosnia and Herzegovina',
		'BW' => 'Botswana',
		'BV' => 'Bouvet Island',
		'BR' => 'Brazil',
		'IO' => 'British Indian Ocean Territory',
		'BN' => 'Brunei Darussalam',
		'BG' => 'Bulgaria',
		'BF' => 'Burkina Faso',
		'BI' => 'Burundi',
		'CV' => 'Cabo Verde',
		'KH' => 'Cambodia',
		'CM' => 'Cameroon',
		'CA' => 'Canada',
		'KY' => 'Cayman Islands',
		'CF' => 'Central African Republic',
		'TD' => 'Chad',
		'CL' => 'Chile',
		'CN' => 'China',
		'CX' => 'Christmas Island',
		'CC' => 'Cocos (Keeling) Islands',
		'CO' => 'Colombia',
		'KM' => 'Comoros',
		'CG' => 'Congo',
		'CD' => 'Democratic Republic of the Congo',
		'CK' => 'Cook Islands',
		'CR' => 'Costa Rica',
		'CI' => 'Côte d\'Ivoire',
		'HR' => 'Croatia',
		'CU' => 'Cuba',
		'CW' => 'Curaçao',
		'CY' => 'Cyprus',
		'CZ' => 'Czechia',
		'DK' => 'Denmark',
		'DJ' => 'Djibouti',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'EC' => 'Ecuador',
		'EG' => 'Egypt',
		'SV' => 'El Salvador',
		'GQ' => 'Equatorial Guinea',
		'ER' => 'Eritrea',
		'EE' => 'Estonia',
		'SZ' => 'Eswatini',
		'ET' => 'Ethiopia',
		'FK' => 'Falkland Islands',
		'FO' => 'Faroe Islands',
		'FJ' => 'Fiji',
		'FI' => 'Finland',
		'FR' => 'France',
		'GF' => 'French Guiana',
		'PF' => 'French Polynesia',
		'TF' => 'French Southern Territories',
		'GA' => 'Gabon',
		'GM' => 'Gambia',
		'GE' => 'Georgia',
		'DE' => 'Germany',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GR' => 'Greece',
		'GL' => 'Greenland',
		'GD' => 'Grenada',
		'GP' => 'Guadeloupe',
		'GU' => 'Guam',
		'GT' => 'Guatemala',
		'GG' => 'Guernsey',
		'GN' => 'Guinea',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HT' => 'Haiti',
		'HM' => 'Heard Island and McDonald Islands',
		'VA' => 'Holy See',
		'HN' => 'Honduras',
		'HK' => 'Hong Kong',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		'IR' => 'Iran',
		'IQ' => 'Iraq',
		'IE' => 'Ireland',
		'IM' => 'Isle of Man',
		'IL' => 'Israel',
		'IT' => 'Italy',
		'JM' => 'Jamaica',
		'JP' => 'Japan',
		'JE' => 'Jersey',
		'JO' => 'Jordan',
		'KZ' => 'Kazakhstan',
		'KE' => 'Kenya',
		'KI' => 'Kiribati',
		'KP' => 'North Korea',
		'KR' => 'Korea',
		'KW' => 'Kuwait',
		'KG' => 'Kyrgyzstan',
		'LA' => 'Laos',
		'LV' => 'Latvia',
		'LB' => 'Lebanon',
		'LS' => 'Lesotho',
		'LR' => 'Liberia',
		'LY' => 'Libya',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'MO' => 'Macao',
		'MG' => 'Madagascar',
		'MW' => 'Malawi',
		'MY' => 'Malaysia',
		'MV' => 'Maldives',
		'ML' => 'Mali',
		'MT' => 'Malta',
		'MH' => 'Marshall Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MU' => 'Mauritius',
		'YT' => 'Mayotte',
		'MX' => 'Mexico',
		'FM' => 'Micronesia',
		'MD' => 'Moldova',
		'MC' => 'Monaco',
		'MN' => 'Mongolia',
		'ME' => 'Montenegro',
		'MS' => 'Montserrat',
		'MA' => 'Morocco',
		'MZ' => 'Mozambique',
		'MM' => 'Myanmar',
		'NA' => 'Namibia',
		'NR' => 'Nauru',
		'NP' => 'Nepal',
		'NL' => 'Netherlands',
		'NC' => 'New Caledonia',
		'NZ' => 'New Zealand',
		'NI' => 'Nicaragua',
		'NE' => 'Niger',
		'NG' => 'Nigeria',
		'NU' => 'Niue',
		'NF' => 'Norfolk Island',
		'MK' => 'North Macedonia',
		'MP' => 'Northern Mariana Islands',
		'NO' => 'Norway',
		'OM' => 'Oman',
		'PK' => 'Pakistan',
		'PW' => 'Palau',
		'PS' => 'Palestine, State of',
		'PA' => 'Panama',
		'PG' => 'Papua New Guinea',
		'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Philippines',
		'PN' => 'Pitcairn',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'PR' => 'Puerto Rico',
		'QA' => 'Qatar',
		'RE' => 'Réunion',
		'RO' => 'Romania',
		'RU' => 'Russian Federation',
		'RW' => 'Rwanda',
		'BL' => 'Saint Barthélemy',
		'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
		'KN' => 'Saint Kitts and Nevis',
		'LC' => 'Saint Lucia',
		'MF' => 'Saint Martin (French part)',
		'PM' => 'Saint Pierre and Miquelon',
		'VC' => 'Saint Vincent and the Grenadines',
		'WS' => 'Samoa',
		'SM' => 'San Marino',
		'ST' => 'Sao Tome and Principe',
		'SA' => 'Saudi Arabia',
		'SN' => 'Senegal',
		'RS' => 'Serbia',
		'SC' => 'Seychelles',
		'SL' => 'Sierra Leone',
		'SG' => 'Singapore',
		'SX' => 'Sint Maarten (Dutch part)',
		'SK' => 'Slovakia',
		'SI' => 'Slovenia',
		'SB' => 'Solomon Islands',
		'SO' => 'Somalia',
		'ZA' => 'South Africa',
		'GS' => 'South Georgia and the South Sandwich Islands',
		'SS' => 'South Sudan',
		'ES' => 'Spain',
		'LK' => 'Sri Lanka',
		'SD' => 'Sudan',
		'SR' => 'Suriname',
		'SJ' => 'Svalbard and Jan Mayen',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'SY' => 'Syrian Arab Republic',
		'TW' => 'Taiwan',
		'TJ' => 'Tajikistan',
		'TZ' => 'Tanzania',
		'TH' => 'Thailand',
		'TL' => 'Timor-Leste',
		'TG' => 'Togo',
		'TK' => 'Tokelau',
		'TO' => 'Tonga',
		'TT' => 'Trinidad and Tobago',
		'TN' => 'Tunisia',
		'TR' => 'Turkey',
		'TM' => 'Turkmenistan',
		'TC' => 'Turks and Caicos Islands',
		'TV' => 'Tuvalu',
		'UG' => 'Uganda',
		'UA' => 'Ukraine',
		'AE' => 'United Arab Emirates',
		'GB' => 'United Kingdom',
		'US' => 'United States of America',
		'UM' => 'United States Minor Outlying Islands',
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VU' => 'Vanuatu',
		'VE' => 'Venezuela',
		'VN' => 'Viet Nam',
		'VG' => 'Virgin Islands (British)',
		'VI' => 'Virgin Islands (U.S.)',
		'WF' => 'Wallis and Futuna',
		'EH' => 'Western Sahara',
		'YE' => 'Yemen',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe',
	];

	private static $userCountries = [];

	/**
	 * Converts an ISO country code to an emoji flag.
	 *
	 * This is stupidly easy. An emoji flag is the country code using Unicode Regional Indicator Symbol Letter glyphs
	 * instead of the regular ASCII characters. Thus US becomes \u1F1FA\u1F1F8 which is incidentally the emoji for the
	 * US flag :)
	 *
	 * On really old browsers (pre-2015) this still renders as the country code since the Regional Indicator Symbol
	 * Letter glyphs were added to Unicode in 2010. Now, if you have an even older browser -- what the heck, dude?!
	 *
	 * @param   string  $countryCode  The alpha-2 country code
	 *
	 * @return  string  The Emoji flag
	 *
	 * @since   5.0.0
	 */
	public static function countryToEmoji(?string $countryCode = ''): string
	{
		$countryCode = strtoupper(trim($countryCode ?? ''));

		if ($countryCode === 'XX')
		{
			$countryCode = '';
		}

		if (empty(self::COUNTRIES[$countryCode] ?? ''))
		{
			return '';
		}

		$countryCode = strtoupper($countryCode);

		// Uppercase letter to Unicode Regional Indicator Symbol Letter
		$letterToRISL = [
			'A' => "&#x1F1E6;",
			'B' => "&#x1F1E7;",
			'C' => "&#x1F1E8;",
			'D' => "&#x1F1E9;",
			'E' => "&#x1F1EA;",
			'F' => "&#x1F1EB;",
			'G' => "&#x1F1EC;",
			'H' => "&#x1F1ED;",
			'I' => "&#x1F1EE;",
			'J' => "&#x1F1EF;",
			'K' => "&#x1F1F0;",
			'L' => "&#x1F1F1;",
			'M' => "&#x1F1F2;",
			'N' => "&#x1F1F3;",
			'O' => "&#x1F1F4;",
			'P' => "&#x1F1F5;",
			'Q' => "&#x1F1F6;",
			'R' => "&#x1F1F7;",
			'S' => "&#x1F1F8;",
			'T' => "&#x1F1F9;",
			'U' => "&#x1F1FA;",
			'V' => "&#x1F1FB;",
			'W' => "&#x1F1FC;",
			'X' => "&#x1F1FD;",
			'Y' => "&#x1F1FE;",
			'Z' => "&#x1F1FF;",
		];

		return $letterToRISL[substr($countryCode, 0, 1)] . $letterToRISL[substr($countryCode, 1, 1)];
	}

	/**
	 * Convert an alpha-2 country code to the formal country name in English
	 *
	 * @param   string|null  $countryCode  The country code
	 *
	 * @return  string
	 * @since   5.0.0
	 */
	public static function decodeCountry(?string $countryCode): string
	{
		$countryCode = strtoupper(trim($countryCode ?? ''));

		if ($countryCode === 'XX')
		{
			$countryCode = '';
		}

		return self::COUNTRIES[$countryCode] ?? $countryCode;
	}

	/**
	 * Returns a list of all countries
	 *
	 * @return  array
	 * @since   5.0.0
	 */
	public static function getCountries(): array
	{
		return self::COUNTRIES;
	}

	public static function getUserCountry(?int $user_id): ?string
	{
		$user = Permissions::getUser($user_id);

		if ($user->guest)
		{
			return null;
		}

		$user_id = $user->id;

		if (array_key_exists($user_id, self::$userCountries))
		{
			return self::$userCountries[$user_id];
		}

		self::$userCountries[$user_id] = null;

		// Use a custom field in the user profile
		self::$userCountries[$user_id] = self::getCustomFieldCountry($user_id) ?: null;

		if (!empty(self::$userCountries[$user_id]))
		{
			return self::$userCountries[$user_id];
		}

		// Next up, try to figure out the free text entered by a user in their profile
		self::$userCountries[$user_id] = self::getJoomlaProfileCountry($user_id) ?: null;

		if (!empty(self::$userCountries[$user_id]))
		{
			return self::$userCountries[$user_id];
		}

		// Finally, try to get the country from Akeeba Subscriptions or Paddle Integration for Akeeba.com
		self::$userCountries[$user_id] = self::getGetAkeebaSubsCountry($user_id) ?: null;

		if (!empty(self::$userCountries[$user_id]))
		{
			return self::$userCountries[$user_id];
		}

		return self::$userCountries[$user_id];
	}

	/**
	 * Get the user's country from a custom field's value.
	 *
	 * @param   int|null  $user_id  The user ID to get the country for
	 *
	 * @return  string|null  The country code. NULL if we cannot get anything.
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	private static function getCustomFieldCountry(?int $user_id): ?string
	{
		$cParams       = ComponentHelper::getParams('com_ats');
		$customFieldId = $cParams->get('customfield_country', '');

		if (empty($customFieldId))
		{
			return null;
		}

		$user = Permissions::getUser($user_id);

		if ($user->guest)
		{
			return null;
		}

		$fields = FieldsHelper::getFields('com_users.user', $user, true);
		$fields = array_filter($fields, function (object $field) use ($customFieldId) {
			return $field->id == $customFieldId;
		});

		if (empty($fields))
		{
			return null;
		}

		$field = array_shift($fields);

		$rawvalue = $field->rawvalue;

		if (is_array($rawvalue))
		{
			$rawvalue = array_shift($rawvalue);
		}

		return ($rawvalue ?? null) ?: null;
	}

	/**
	 * Get the Joomla Database Object
	 *
	 * @return  DatabaseInterface|DatabaseDriver
	 * @since   5.0.0
	 */
	private static function getDbo(): DatabaseDriver
	{
		return Factory::getContainer()->get('DatabaseDriver');
	}

	/**
	 * Get the user's country stored by Akeeba Subscriptions or our Paddle Integration for Akeeba.com
	 *
	 * @return  string
	 *
	 * @since   5.0.0
	 */
	private static function getGetAkeebaSubsCountry(int $user_id): string
	{
		$db  = self::getDbo();
		$key = 'akeebasubs.country';

		$query = $db->getQuery(true)
			->select($db->quoteName('profile_value'))
			->from($db->quoteName('#__user_profiles'))
			->where($db->qn('user_id') . ' = :user_id')
			->where($db->qn('profile_key') . ' = :key')
			->bind(':user_id', $user_id, ParameterType::INTEGER)
			->bind(':key', $key, ParameterType::STRING);

		try
		{
			return $db->setQuery($query)->loadResult() ?: '';
		}
		catch (Exception $e)
		{
			return '';
		}
	}

	/**
	 * Returns a country defined in the user's Joomla profile.
	 *
	 * Do note that the Joomla profile stores free text. We try to make sure it contains EITHER a two letter country
	 * code OR the formal English name of a country per the ISO standard. This is obviously going to miss a LOT of
	 * countries which is why it's recommended using a Joomla custom field instead.
	 *
	 * @param   int  $user_id
	 *
	 * @return  string
	 *
	 * @since   5.0.0
	 */
	private static function getJoomlaProfileCountry(int $user_id): string
	{
		$db  = self::getDbo();
		$key = 'profile.country';

		$query = $db->getQuery(true)
			->select($db->quoteName('profile_value'))
			->from($db->quoteName('#__user_profiles'))
			->where($db->qn('user_id') . ' = :user_id')
			->where($db->qn('profile_key') . ' = :key')
			->bind(':user_id', $user_id, ParameterType::INTEGER)
			->bind(':key', $key, ParameterType::STRING);

		try
		{
			$countryHuman = $db->setQuery($query)->loadResult() ?: '';
		}
		catch (Exception $e)
		{
			return '';
		}

		$countryHuman = strtoupper(trim($countryHuman));

		if (array_key_exists($countryHuman, self::$userCountries))
		{
			return $countryHuman;
		}

		foreach (self::COUNTRIES as $key => $country)
		{
			if ($countryHuman == strtoupper($country ?? ''))
			{
				return $key;
			}
		}

		return '';
	}
}