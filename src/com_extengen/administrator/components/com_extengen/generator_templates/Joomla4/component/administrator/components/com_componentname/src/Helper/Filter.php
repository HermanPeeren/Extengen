<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Helper;

defined('_JEXEC') or die;

use HTMLPurifier;
use HTMLPurifier_Config;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\User\User;

class Filter
{
	// Array with bad words (ie wget, curl) that would trigger security warnings
	static public $badWords = ['wget', 'curl', '^'];

	static public $transliterationTable = null;

	public static function filterText($text, User $user = null)
	{
		static $filterMethod = null;

		if (is_null($filterMethod))
		{
			$cParams      = ComponentHelper::getParams('com_ats');
			$filterMethod = $cParams->get('filtermethod', 'htmlpurifier');

			if (!in_array($filterMethod, ['joomla', 'htmlpurifier', 'hackme']))
			{
				$filterMethod = 'htmlpurifier';
			}
		}

		if ($filterMethod == 'hackme')
		{
			return $text;
		}
		elseif ($filterMethod == 'htmlpurifier')
		{
			return self::filterTextHtmlpurifier($text, $user);
		}
		else
		{
			return self::filterTextJoomla($text, $user);
		}
	}

	public static function filterTextHtmlpurifier($text, User $user = null)
	{
		if (!is_object($user))
		{
			$user = Permissions::getUser();
		}

		$userGroups = Access::getGroupsByUser($user ? $user->id : 0);

		$config  = ComponentHelper::getParams('com_config');
		$filters = $config->get('filters');

		$blackListTags       = [];
		$blackListAttributes = [];

		$customListTags       = [];
		$customListAttributes = [];

		$whiteListTags       = [];
		$whiteListAttributes = [];

		$noHtml     = false;
		$whiteList  = false;
		$blackList  = false;
		$customList = false;
		$unfiltered = false;

		$countNoHtml = 0;
		$countRaw    = 0;
		$countOther  = 0;

		// Cycle through each of the user groups the user is in.
		// Remember they are included in the Public group as well.
		foreach ($userGroups as $groupId)
		{
			// May have added a group but not saved the filters.
			if (!isset($filters->$groupId))
			{
				continue;
			}

			// Each group the user is in could have different filtering properties.
			$filterData = $filters->$groupId;
			$filterType = strtoupper($filterData->filter_type);

			switch ($filterType)
			{
				case 'NH':
					$countNoHtml++;
					break;

				case 'NONE':
					$countRaw++;
					break;

				default:
					$countOther++;
					break;
			}
		}

		// If any group defines no filtering, disable filtering
		if ($countRaw)
		{
			$unfiltered = true;
		}
		// If any group defines No HTML and the other groups do not define a more lax filtering, strip all tags
		elseif ($countNoHtml && !$countOther)
		{
			$noHtml = true;
		}
		// Otherwise we will just sanitize the HTML

		// Remove duplicates before processing (because the black list uses both sets of arrays).
		$blackListTags        = array_unique($blackListTags);
		$blackListAttributes  = array_unique($blackListAttributes);
		$customListTags       = array_unique($customListTags);
		$customListAttributes = array_unique($customListAttributes);
		$whiteListTags        = array_unique($whiteListTags);
		$whiteListAttributes  = array_unique($whiteListAttributes);

		// Unfiltered assumes first priority.
		if ($unfiltered)
		{
			return $text;
		}

		// No HTML - strip tags and get done with it
		if ($noHtml)
		{
			return strip_tags($text);
		}

		// Set up HTML Purifier
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Core.Encoding', 'UTF-8');
		$config->set('HTML.Doctype', 'HTML 4.01 Transitional');

		if (is_writable(JPATH_CACHE))
		{
			$config->set('Cache.SerializerPath', JPATH_CACHE);
		}
		else
		{
			$config->set('Core.SerializerPath', null);
		}

		$cParams = ComponentHelper::getParams('com_ats');
		$string  = $cParams->get('htmlpurifier_configstring', '');
		$string  = trim($string);

		if (empty($string))
		{
			$string = 'p,b,a[href],i,u,strong,em,small,big,span[style],font[size],font[color],ul,ol,li,br,img[src],img[width],img[height],code,pre,blockquote';
		}

		$config->set('HTML.Allowed', $string);

		// Clean the HTML
		$purifier   = new HTMLPurifier($config);
		$clean_html = $purifier->purify($text);

		return $clean_html;
	}

	public static function filterTextJoomla($text, User $user = null)
	{
		if (!is_object($user))
		{
			$user = Permissions::getUser();
		}

		$userGroups = Access::getGroupsByUser($user->get('id'));

		$config  = ComponentHelper::getParams('com_config');
		$filters = $config->get('filters');

		$blackListTags       = [];
		$blackListAttributes = [];

		$customListTags       = [];
		$customListAttributes = [];

		$whiteListTags       = [];
		$whiteListAttributes = [];

		$noHtml     = false;
		$whiteList  = false;
		$blackList  = false;
		$customList = false;
		$unfiltered = false;

		$countNoHtml = 0;
		$countRaw    = 0;
		$countOther  = 0;

		// Cycle through each of the user groups the user is in.
		// Remember they are included in the Public group as well.
		foreach ($userGroups as $groupId)
		{
			// May have added a group but not saved the filters.
			if (!isset($filters->$groupId))
			{
				continue;
			}

			// Each group the user is in could have different filtering properties.
			$filterData = $filters->$groupId;
			$filterType = strtoupper($filterData->filter_type);

			if ($filterType == 'NH')
			{
				// Maximum HTML filtering.
				$countNoHtml++;
			}
			elseif ($filterType == 'NONE')
			{
				// No HTML filtering.
				$countRaw++;
			}
			else
			{
				// Black, white or custom list.
				$countOther++;
				// Preprocess the tags and attributes.
				$tags           = explode(',', $filterData->filter_tags);
				$attributes     = explode(',', $filterData->filter_attributes);
				$tempTags       = [];
				$tempAttributes = [];

				foreach ($tags as $tag)
				{
					$tag = trim($tag);

					if ($tag)
					{
						$tempTags[] = $tag;
					}
				}

				foreach ($attributes as $attribute)
				{
					$attribute = trim($attribute);

					if ($attribute)
					{
						$tempAttributes[] = $attribute;
					}
				}

				// Collect the black or white list tags and attributes.
				// Each lists is cummulative.
				if ($filterType == 'BL')
				{
					$blackList           = true;
					$blackListTags       = array_merge($blackListTags, $tempTags);
					$blackListAttributes = array_merge($blackListAttributes, $tempAttributes);
				}
				elseif ($filterType == 'CBL')
				{
					// Only set to true if Tags or Attributes were added
					if ($tempTags || $tempAttributes)
					{
						$customList           = true;
						$customListTags       = array_merge($customListTags, $tempTags);
						$customListAttributes = array_merge($customListAttributes, $tempAttributes);
					}
				}
				elseif ($filterType == 'WL')
				{
					$whiteList           = true;
					$whiteListTags       = array_merge($whiteListTags, $tempTags);
					$whiteListAttributes = array_merge($whiteListAttributes, $tempAttributes);
				}
			}
		}

		// If any group defines no filtering, disable filtering
		if ($countRaw)
		{
			$unfiltered = true;
		}
		// If any group defines No HTML and the other groups do not define a more lax filtering, strip all tags
		elseif ($countNoHtml && !$countOther)
		{
			$noHtml = true;
		}
		// Otherwise we will just sanitize the HTML

		// Remove duplicates before processing (because the black list uses both sets of arrays).
		$blackListTags        = array_unique($blackListTags);
		$blackListAttributes  = array_unique($blackListAttributes);
		$customListTags       = array_unique($customListTags);
		$customListAttributes = array_unique($customListAttributes);
		$whiteListTags        = array_unique($whiteListTags);
		$whiteListAttributes  = array_unique($whiteListAttributes);

		// Unfiltered assumes first priority.
		if ($unfiltered)
		{
			// Don't apply filtering.
		}
		elseif ($noHtml)
		{
			return strip_tags($text);
		}
		else
		{
			// Custom blacklist precedes Default blacklist
			if ($customList)
			{
				$filter = InputFilter::getInstance([], [], 1, 1);

				// Override filter's default blacklist tags and attributes
				if ($customListTags)
				{
					if (property_exists($filter, 'tagBlacklist'))
					{
						$filter->tagBlacklist = $customListTags;
					}
					else
					{
						$filter->blockedAttributes = $customListTags;
					}
				}
				if ($customListAttributes)
				{
					if (property_exists($filter, 'attrBlacklist'))
					{
						$filter->attrBlacklist = $customListAttributes;
					}
					else
					{
						$filter->blockedAttributes = $customListAttributes;
					}
				}
			}
			// Black lists take third precedence.
			elseif ($blackList)
			{
				// Remove the white-listed attributes from the black-list.
				$filter = InputFilter::getInstance(
					array_diff($blackListTags, $whiteListTags), // blacklisted tags
					array_diff($blackListAttributes, $whiteListAttributes), // blacklisted attributes
					1, // blacklist tags
					1 // blacklist attributes
				);

				// Remove white listed tags from filter's default blacklist
				if ($whiteListTags)
				{
					$filter->tagBlacklist = array_diff($filter->tagBlacklist ?? $filter->blockedTags, $whiteListTags);
				}

				// Remove white listed attributes from filter's default blacklist
				if ($whiteListAttributes)
				{
					$filter->attrBlacklist = array_diff($filter->attrBlacklist ?? $filter->blockedAttributes, $whiteListAttributes);
				}
			}
			// White lists take fourth precedence.
			elseif ($whiteList)
			{
				$filter = InputFilter::getInstance($whiteListTags, $whiteListAttributes, 0, 0, 0); // turn off xss auto clean
			}
			// No HTML takes last place.
			else
			{
				$filter = InputFilter::getInstance();
			}

			// JFilterInput throws a gazillion strict standards warnings when it
			// encounters slightly screwed up HTML. Let's prevent an information
			// disclosure, shall we?
			$error_reporting = error_reporting(0);
			$text            = $filter->clean($text, 'html');
			error_reporting($error_reporting);
		}

		return $text;
	}

	public static function toSlug(?string $value, string $language = '')
	{
		if (empty($value))
		{
			return '';
		}

		$value = ApplicationHelper::stringURLSafe(trim($value), $language);

		// We always use our function, since if the user choose the option "Unicode Aliases" we will have aliases with wrong
		// characters (eg ^) and no char transliteration. Moreover we want to remove some word that could cause
		// security issues, like WGET or CURL

//		if (function_exists('mb_strtolower'))
//		{
//			$value = mb_strtolower($value, 'UTF-8');
//		}
//		else
//		{
//			$value = trim(strtolower($value));
//		}

		// Remove "bad words"
		$value = str_replace(self::$badWords, ' ', $value);

		// Whitespace to dashes
		$value = preg_replace('/\s+/u', '-', $value);

		// Limit length
		if (strlen($value) > 100)
		{
			$value = substr($value, 0, 100);
		}

		return $value;
	}
}
