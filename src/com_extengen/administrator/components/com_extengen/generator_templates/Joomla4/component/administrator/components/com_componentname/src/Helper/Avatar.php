<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Helper;

use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

defined('_JEXEC') or die;

class Avatar
{
	/**
	 * Cache of avatars per user ID
	 *
	 * @var   array
	 * @since 5.0.0
	 */
	private static $avatarImages = [];

	/**
	 * Get the user's avatar image for a specific size
	 *
	 * @param   int|null  $user_id  User ID to get the avatar for
	 * @param   int       $size     Image width in pixels
	 *
	 * @return  string
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public static function getUserAvatar(?int $user_id, int $size = 128): string
	{
		if ($user_id === -1)
		{
			return HTMLHelper::_('image', 'com_ats/system-task.png', '', [
				'aria-hidden' => 'true',
				'width' => 16,
				'height' => 16,
				'class' => 'm-2'
			], true, 1);
		}

		$user = Permissions::getUser($user_id);

		if ($user->guest)
		{
			return '';
		}

		if (array_key_exists($user_id, self::$avatarImages))
		{
			return self::$avatarImages[$user_id];
		}

		// Support custom fields
		self::$avatarImages[$user_id] = self::getAvatarFromCustomField($user_id);

		if (!empty(self::$avatarImages[$user_id]))
		{
			return self::$avatarImages[$user_id];
		}

		// TODO Support Joomla plugin events — if Joomla ever has such an event...

		// Fallback to Gravatar
		self::$avatarImages[$user_id] = sprintf('https://www.gravatar.com/avatar/%s?s=%s', md5(strtolower(trim($user->email))), $size);

		return self::$avatarImages[$user_id];
	}

	/**
	 * Get the user's avatar from a custom field.
	 *
	 * The custom field is expected to render EITHER an img element OR a URL to the avatar image.
	 *
	 * @param   int|null  $user_id  The user ID to get the avatar for
	 *
	 * @return  string|null  The avatar URL (best guess!) or NULL if it is not possible
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	private static function getAvatarFromCustomField(?int $user_id): ?string
	{
		$cParams       = ComponentHelper::getParams('com_ats');
		$customFieldId = $cParams->get('customfield_avatar', '');

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

		$field         = array_shift($fields);
		$renderedValue = $field->value;

		if (is_array($renderedValue))
		{
			$renderedValue = array_shift($renderedValue);
		}

		if (empty($renderedValue))
		{
			return null;
		}

		$hasMatch = preg_match('#src\s*=\s*"(.*)"#i', $renderedValue, $matches);

		if (!$hasMatch)
		{
			return null;
		}

		return $matches[1] ?: null;
	}
}