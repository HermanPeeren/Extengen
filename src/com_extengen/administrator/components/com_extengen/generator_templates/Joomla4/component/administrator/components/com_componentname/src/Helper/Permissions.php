<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Helper;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Table\AttachmentTable;
use Akeeba\Component\ATS\Administrator\Table\PostTable;
use Akeeba\Component\ATS\Administrator\Table\TicketTable;
use DateInterval;
use Exception;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Access\Access as JAccess;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use RuntimeException;

class Permissions
{
	private static $cacheIdentities = true;

	private static $groupsByUserCache = [];

	private static $identityUserCache = null;

	private static $signatures = [];

	private static $ticketCountCache = [];

	private static $timePerUser = [];

	private static $usersCache = [];

	/**
	 * Is the post attachment visible / downloadable?
	 *
	 * @param   AttachmentTable|null  $attachment  Attachment record
	 * @param   User|null             $user        The user to check the attachment visibility for
	 *
	 * @return  bool
	 * @throws Exception
	 * @since   5.0.0
	 */
	public static function attachmentVisible(?AttachmentTable $attachment, ?User $user = null): bool
	{
		if (!defined('ATS_PRO') || !ATS_PRO)
		{
			return false;
		}

		// Do not allow invalid attachments
		if (empty($attachment) || !$attachment->id || !$attachment->original_filename)
		{
			return false;
		}

		$user       = $user ?? self::getUser();
		$post       = $attachment->getPost();
		$ticket     = $post->getTicket();
		$isManager  = self::isManager($ticket->catid, $user->id);
		$privileges = self::getAclPrivileges($ticket->catid, $user->id);

		// Managers see everything
		if ($isManager)
		{
			return true;
		}

		// Non–managers can only see unpublished attachments if they have the ats.attachment.edit.state privilege
		if (!$attachment->enabled && !$privileges['ats.attachment.edit.state'])
		{
			return false;
		}

		$privateAttachments = ComponentHelper::getParams('com_ats')->get('attachments_private', 0);

		// If we don't have private attachments they are visible by default
		if (!$privateAttachments)
		{
			return true;
		}

		// People with the ats.attachment.download privilege can see and download private attachments
		if ($privileges['ats.attachment.download'])
		{
			return true;
		}

		// Otherwise, only the owner of the attachments, post or ticket can view it.
		return in_array($user->id, [$attachment->created_by, $post->created_by, $ticket->created_by]);
	}

	/**
	 * Checks if a post is editable since we're inside the grace time
	 *
	 * @param   PostTable  $post  Post to check
	 * @param   User|null  $user  The user to perform the check for
	 *
	 * @return  bool    Are we within the grace time?
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public static function editGraceTime(PostTable $post, ?User $user = null): bool
	{
		$cParams   = ComponentHelper::getParams('com_ats');
		$graceTime = $cParams->get('editeableforxminutes', 15);
		$result    = false;
		$user      = $user ?? self::getUser();
		$userid    = $user->id;

		if ((($post->created_by == $userid) || ($post->modified_by == $userid)) && !$user->guest)
		{
			$editedOn  = new Date(($post->modified_by == $userid) ? $post->modified : $post->created);
			$now       = new Date();
			$editedAgo = abs($now->toUnix() - $editedOn->toUnix());
			$result    = $editedAgo < 60 * $graceTime;
		}

		return $result;
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param   int|null  $categoryId  The category ID.
	 * @param   int|null  $userId      User id
	 *
	 * @return  array{
	 *            "core.admin":bool,"core.manage":bool,"core.create":bool,"core.edit":bool,"core.edit.own":bool,
	 *            "core.edit.state":bool,"core.delete":bool,"ats.private":bool,"ats.attachment":bool,
	 *            "ats.edit.own.post":bool, "ats.edit.own.ticket":bool,"ats.private.read":bool,"ats.reply":bool,
	 *            "ats.attachment.download":bool, "ats.attachment.delete":bool,"ats.attachment.edit.state":bool,
	 *            "ats.notes.read":bool,"ats.notes.create":bool, "ats.notes.edit.own":bool,"ats.notes.edit":bool,
	 *            "ats.notes.delete":bool
	 *          }
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public static function getAclPrivileges(?int $categoryId = 0, ?int $userId = null): array
	{
		$user = self::getUser($userId);

		$result = [];

		$actions = [
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete',
			'ats.private', 'ats.attachment', 'ats.edit.own.post', 'ats.edit.own.ticket', 'ats.private.read',
			'ats.reply', 'ats.attachment.download', 'ats.attachment.delete', 'ats.attachment.edit.state',
			'ats.notes.read', 'ats.notes.create', 'ats.notes.edit.own', 'ats.notes.edit', 'ats.notes.delete',
			'ats.assignee', 'ats.assign',
		];

		foreach ($actions as $action)
		{
			if (empty($user))
			{
				$result[$action] = false;

				continue;
			}

			if ($categoryId)
			{
				$result[$action] = $user->authorise($action, 'com_ats') ||
					$user->authorise($action, 'com_ats.category.' . (int) $categoryId);
			}
			else
			{
				$result[$action] = $user->authorise($action, 'com_ats');
			}
		}

		if (!defined('ATS_PRO') || !ATS_PRO)
		{
			$result['ats.attachment'] = false;
		}

		return $result;
	}

	/**
	 * Returns the allowed file extensions for uploads. Empty if Joomla does not limit file extensions.
	 *
	 * @return  array
	 * @since   5.0.0
	 */
	public static function getAllowedExtensions(): array
	{
		$mediaParams = ComponentHelper::getParams('com_ats');

		$restrictUploads = $mediaParams->get('restrict_uploads', 1);

		if ($restrictUploads != 1)
		{
			return [];
		}

		$extensions = array_map('strtolower', explode(',', $mediaParams->get('restrict_uploads_extensions')));

		return array_unique($extensions);
	}

	public static function getGroupsByUser(?int $user_id = null): array
	{
		$user_id = $user_id ?? self::getUser()->id;

		if (!empty(self::$groupsByUserCache[$user_id]))
		{
			return self::$groupsByUserCache[$user_id];
		}

		self::$groupsByUserCache[$user_id] = array_map(function ($gid) {
			return Access::getGroupTitle($gid);
		}, Access::getGroupsByUser($user_id, false));

		return self::$groupsByUserCache[$user_id];
	}

	/**
	 * Returns the categories where the user is a manager
	 *
	 * @param   int  $userid
	 *
	 * @return  array
	 * @since   5.0.0
	 */
	public static function getManagerCategories($userid = null)
	{
		static $cache = [];

		$user   = self::getUser($userid);
		$userid = $user->id;

		if (!isset($cache[$userid]))
		{
			/** @var DatabaseDriver $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__categories'))
				->where($db->quoteName('extension') . ' = ' . $db->quote('com_ats'))
				->where($db->quoteName('published') . ' = ' . $db->quote(1));

			// Super Users and global support staff don't have their access level checked
			$fltIgnoreUser = $user->authorise('core.admin')
				|| $user->authorise('core.manage', 'com_ats');

			if (!$fltIgnoreUser)
			{
				$query->whereIn($db->quoteName('access'), $user->getAuthorisedViewLevels(), ParameterType::INTEGER);
			}

			$categories     = $db->setQuery($query)->loadColumn() ?: [];
			$cache[$userid] = array_filter($categories, function ($catId) use ($userid) {
				return self::isManager($catId, $userid);
			});
		}

		return $cache[$userid];
	}

	/**
	 * Fetches all the managers of the given category
	 *
	 * @param   int|null  $category  Ticket category
	 *
	 * @return  array   List of ids and names of managers (indexed by id) of the category
	 * @since   5.0.0
	 */
	public static function getManagers(?int $category = null)
	{
		static $cache = [];

		if (isset($cache[$category]))
		{
			return $cache[$category];
		}

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		// First, let's get the whole list of groups
		$query  = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__usergroups'))
			->order($db->quoteName('id') . ' DESC');
		$groups = $db->setQuery($query)->loadColumn();

		$validGroups = [];

		// Then check if they can admin tickets
		foreach ($groups as $group)
		{
			if (JAccess::checkGroup($group, 'core.admin', 'com_ats') ||
				JAccess::checkGroup($group, 'core.manage', 'com_ats') ||
				JAccess::checkGroup($group, 'core.manage', 'com_ats.category.' . $category))
			{
				$validGroups[] = $group;
			}
		}

		// Get all users in those groups
		$query            = $db->getQuery(true)
			->select([
				$db->quoteName('id'),
				$db->quoteName('name'),
				$db->quoteName('username'),
			])->from($db->quoteName('#__users', 'u'))
			->leftJoin($db->quoteName('#__user_usergroup_map', 'm'),
				'(' . $db->quoteName('u.id') . ' = ' . $db->quoteName('m.user_id') . ')'
			)->whereIn($db->quoteName('m.group_id'), $validGroups);
		$cache[$category] = $db->setQuery($query)->loadObjectList('id') ?: [];

		$cache[$category] = array_filter($cache[$category], function ($def) use ($category) {
			$juser = self::getUser($def->id);

			return $juser->authorise('core.admin', 'com_ats') ||
				$juser->authorise('core.manage', 'com_ats') ||
				$juser->authorise('core.manage', 'com_ats.category.' . $category);
		});

		return $cache[$category];
	}

	/**
	 * Fetches all the users which can be assigned tickets in the given category
	 *
	 * @param   int|null  $category  Ticket category
	 *
	 * @return  array   List of ids and names of ticket assignees (indexed by id) of the category
	 * @since   5.0.0
	 */
	public static function getAssignees(?int $category = null)
	{
		static $cache = [];

		if (isset($cache[$category]))
		{
			return $cache[$category];
		}

		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get('DatabaseDriver');

		// First, let's get the whole list of groups
		$query  = $db->getQuery(true)
		             ->select($db->quoteName('id'))
		             ->from($db->quoteName('#__usergroups'))
		             ->order($db->quoteName('id') . ' DESC');
		$groups = $db->setQuery($query)->loadColumn();

		$validGroups = [];

		// Then check if they can admin tickets
		foreach ($groups as $group)
		{
			if (JAccess::checkGroup($group, 'core.admin', 'com_ats') ||
				JAccess::checkGroup($group, 'core.manage', 'com_ats') ||
				JAccess::checkGroup($group, 'core.manage', 'com_ats.category.' . $category) ||
				JAccess::checkGroup($group, 'ats.assignee', 'com_ats') ||
				JAccess::checkGroup($group, 'ats.assignee', 'com_ats.category.' . $category)
			)
			{
				$validGroups[] = $group;
			}
		}

		// Get all users in those groups
		$query            = $db->getQuery(true)
		                       ->select([
			                       $db->quoteName('id'),
			                       $db->quoteName('name'),
			                       $db->quoteName('username'),
		                       ])->from($db->quoteName('#__users', 'u'))
		                       ->leftJoin($db->quoteName('#__user_usergroup_map', 'm'),
			                       '(' . $db->quoteName('u.id') . ' = ' . $db->quoteName('m.user_id') . ')'
		                       )->whereIn($db->quoteName('m.group_id'), $validGroups);
		$cache[$category] = $db->setQuery($query)->loadObjectList('id') ?: [];

		$cache[$category] = array_filter($cache[$category], function ($def) use ($category) {
			$juser = self::getUser($def->id);

			return $juser->authorise('core.admin', 'com_ats') ||
				$juser->authorise('core.manage', 'com_ats') ||
				$juser->authorise('core.manage', 'com_ats.category.' . $category) ||
				$juser->authorise('ats.assignee', 'com_ats') ||
				$juser->authorise('ats.assignee', 'com_ats.category.' . $category);
		});

		return $cache[$category];
	}

	/**
	 * Returns the category IDs a user can post to
	 *
	 * @param   null  $userid
	 *
	 * @return  array|mixed
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public static function getPostableCategories($userid = null)
	{
		static $cache = [];

		$user   = self::getUser($userid);
		$userid = $user->id;

		if (!isset($cache[$userid]))
		{
			/** @var DatabaseDriver $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__categories'))
				->where($db->quoteName('extension') . ' = ' . $db->quote('com_ats'))
				->where($db->quoteName('published') . ' = ' . $db->quote(1));

			// Super Users and global support staff don't have their access level checked
			$fltIgnoreUser = $user->authorise('core.admin')
				|| $user->authorise('core.manage', 'com_ats');

			if (!$fltIgnoreUser)
			{
				$query->whereIn($db->quoteName('access'), $user->getAuthorisedViewLevels(), ParameterType::INTEGER);
			}

			$categories     = $db->setQuery($query)->loadColumn() ?: [];
			$cache[$userid] = array_filter($categories, function ($catId) use ($user) {
				return $user->authorise('core.create', 'com_ats')
					|| $user->authorise('core.create', 'com_ats.category.' . $catId);
			});
		}

		return $cache[$userid];
	}

	/**
	 * Returns the all the privileges linked with a specific ticket
	 *
	 * @param   TicketTable  $ticket  The ticket to get privileges for
	 * @param   User|null    $user    The user to get privileges for, NULL for currently logged in user
	 *
	 * @return  array{
	 *            view:bool,post:bool,delete:bool,edit:bool,"edit.state":bool,admin:bool,close:bool,attachment:bool,
	 *            "notes.read":bool,"notes.create":bool,"notes.edit.own":bool,"notes.edit":bool,"notes.delete":bool
	 *          }
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public static function getTicketPrivileges(TicketTable $ticket, ?User $user = null): array
	{
		$user           = $user ?? self::getUser();
		$actions        = self::getAclPrivileges($ticket->catid, $user->id);
		$isManager      = self::isManager($ticket->catid, $user->id);
		$canReadPrivate = self::canReadPrivate($ticket->catid, $user->id);
		$isOwner        = $ticket->created_by == $user->id;

		/**
		 * Get the basic privileges state.
		 *
		 * Special note: you can only view a ticket if it's public, if you own the ticket or are a manager
		 */
		$ret = [
			'view'            => ($ticket->public == 1) || $isOwner || $canReadPrivate,
			'post'            => $actions['ats.reply'],
			'delete'          => $actions['core.delete'],
			'edit'            => $actions['core.edit'],
			'edit.state'      => $actions['core.edit.state'],
			'admin'           => $actions['core.manage'],
			'close'           => false,
			'private'         => $actions['ats.private'],
			'attachment'      => $actions['ats.attachment'],
			'notes.read'      => $actions['ats.notes.read'],
			'notes.create'    => $actions['ats.notes.create'],
			'notes.edit.own'  => $actions['ats.notes.edit.own'],
			'notes.edit'      => $actions['ats.notes.edit'],
			'notes.delete'    => $actions['ats.notes.delete'],
			'ticket.assignee' => $actions['ats.assignee'],
			'ticket.assign'   => $actions['ats.assign'],
		];

		if (!defined('ATS_PRO') || !ATS_PRO)
		{
			$ret['attachment'] = false;
		}

		// If I am the manager I can do everything without any restrictions; stop checking
		if ($isManager)
		{
			$ret = array_map(function ($x) {
				return true;
			}, $ret);

			if (!defined('ATS_PRO') || !ATS_PRO)
			{
				$ret['attachment'] = false;
			}

			return $ret;
		}

		// If the ticket is closed and I am not a manager I have very limited options.
		if (!$isManager && ($ticket->status === 'C'))
		{
			$exemptKeys = ['view'];

			foreach (array_keys($ret) as $k)
			{
				if (!in_array($k, $exemptKeys))
				{
					$ret[$k] = false;
				}
			}

			return $ret;
		}

		// If this is not the ticket owner I'm done checking.
		if (!$isOwner)
		{
			return $ret;
		}

		/**
		 * If I am here, I am the owner of this ticket and the ticket is Open, Pending or in a custom state. I have
		 * additional permissions regardless of ACLs.
		 *
		 * Note that being able to post a reply is contingent to the core.create ACL privilege. This is intentional.
		 * Let's say you have a ticket category Support where only members of the user group Clients can post tickets
		 * requesting your support. If a user had already filed a support ticket but they are removed from the Clients
		 * group in the meantime (e.g. they failed to make a payment for their support contract) you do not want them to
		 * be able to get free support through their already open ticket. The only way to address that is by linking
		 * their ability to post a reply to their ability to create new tickets in this category.
		 *
		 * The user will still be able to view their past tickets, regardless of the ticket state, so they can refer
		 * back to information you gave them in the past.
		 *
		 * This check, added in 5.0.0, solves a decade–long issue of users who are no longer clients being able to post
		 * replies to an already open issue with all the frustration that ensued.
		 */
		$editableMinutes = min((int) ComponentHelper::getParams('com_ats')->get('editeableforxminutes', 15), 0);
		$inEditingPeriod = false;

		if ($editableMinutes > 0)
		{
			try
			{
				$earliestTime    = (new Date())->sub(new DateInterval('PT' . $editableMinutes . 'S'));
				$ticketTime      = new Date($ticket->created);
				$inEditingPeriod = !$ticketTime->diff($earliestTime)->invert;
			}
			catch (Exception $e)
			{
				$inEditingPeriod = false;
			}
		}

		return array_merge($ret, [
			'post'  => $actions['core.create'],
			'close' => true,
			'edit'  => $inEditingPeriod || $actions['ats.edit.own.ticket'] || $actions['core.edit'],
		]);
	}

	/**
	 * Returns the all the privileges linked with a specific Post
	 *
	 * @param   PostTable  $post  The ticket to get privileges for
	 * @param   User|null  $user  The user to get privileges for, NULL for currently logged in user
	 *
	 * @return  array{delete:bool,edit:bool,"edit.state":bool,admin:bool,attachment:bool,"attachment.edit.state":bool,"attachment.delete":bool}
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public static function getPostPrivileges(PostTable $post, ?User $user = null): array
	{
		$user      = $user ?? self::getUser();

		try
		{
			$ticket = $post->getTicket();
		}
		catch (RuntimeException $e)
		{
			/** @var TicketTable $ticket */
			$ticket = new TicketTable($post->getDbo());
		}

		$actions   = self::getAclPrivileges($ticket->catid, $user->id);
		$isManager = self::isManager($ticket->catid, $user->id);
		$isOwner   = $post->created_by == $user->id;

		/**
		 * Get the basic privileges state.
		 *
		 * Special note: you can only view a ticket if it's public, if you own the ticket or are a manager
		 */
		$ret = [
			'delete'                => $actions['core.delete'],
			'edit'                  => ($isOwner ? $actions['core.edit.own'] : false) || $actions['core.edit'],
			'edit.state'            => $actions['core.edit.state'],
			'admin'                 => $actions['core.manage'],
			'attachment'            => $actions['ats.attachment'],
			'attachment.edit.state' => $actions['ats.attachment.edit.state'],
			'attachment.delete'     => $actions['ats.attachment.delete'],
		];

		if (!defined('ATS_PRO') || !ATS_PRO)
		{
			$ret['attachment'] = false;
		}

		// If I am the manager I can do everything without any restrictions; stop checking
		if ($isManager)
		{
			$ret = array_map(function ($x) {
				return true;
			}, $ret);

			if (!defined('ATS_PRO') || !ATS_PRO)
			{
				$ret['attachment'] = false;
			}

			return $ret;
		}

		// If the post's ticket is closed and I am not a manager I have very limited options.
		if (!$isManager && ($ticket->status === 'C'))
		{
			return array_map(function ($ignored) {
				return false;
			}, $ret);
		}

		if (!$isOwner)
		{
			return $ret;
		}

		/**
		 * If I am here, I am the owner of the post's ticket and the ticket is Open, Pending or in a custom state. I
		 * have additional permissions regardless of ACLs.
		 */
		$editableMinutes = max((int) ComponentHelper::getParams('com_ats')->get('editeableforxminutes', 15), 0);
		$inEditingPeriod = false;

		if ($editableMinutes > 0)
		{
			try
			{
				$earliestTime    = (new Date())->sub(new DateInterval('PT' . $editableMinutes . 'M'));
				$ticketTime      = new Date($post->created);
				$inEditingPeriod = $ticketTime->diff($earliestTime)->invert;
			}
			catch (Exception $e)
			{
				$inEditingPeriod = false;
			}
		}

		return array_merge($ret, [
			'close' => true,
			'edit'  => $inEditingPeriod || $actions['ats.edit.own.post'] || $actions['core.edit'],
		]);
	}


	/**
	 * Get the signature of a user. Empty if no signature is set.
	 *
	 * @param   int|null  $user_id  The user ID to get the signature for. NULL for current user.
	 *
	 * @return  string  The signature
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public static function getSignature(?int $user_id): string
	{
		$user_id = $user_id ?: self::getUser()->id;

		if ($user_id <= 0)
		{
			return '';
		}

		if (!isset(self::$signatures[$user_id]))
		{
			/** @var DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query     = $db->getQuery(true)
				->select($db->quoteName('profile_value'))
				->from($db->quoteName('#__user_profiles'))
				->where($db->quoteName('user_id') . ' = :user_id')
				->where($db->quoteName('profile_key') . ' = ' . $db->quote('ats.signature'))
				->bind(':user_id', $user_id, ParameterType::INTEGER);
			$signature = Filter::filterText($db->setQuery($query)->loadResult() ?: '', self::getUser($user_id));

			/**
			 * If there is no linebreak, paragraph or table tag we assume a plain text signature, therefore in need for
			 * nl2br(). This is backwards compatibility to older versions of ATS.
			 */
			if ((stripos($signature, '<br') === false) && (stripos($signature, '< br') === false) && (stripos($signature, '</p') === false) && (stripos($signature, '<td') === false))
			{
				$signature = nl2br($signature);
			}

			self::$signatures[$user_id] = empty($signature) || is_null($signature) ? '' : $signature;
		}

		return self::$signatures[$user_id];
	}

	/**
	 * Get the total number of tickets a user has submitted
	 *
	 * @param   int|null  $user_id  The user ID to get the information for; NULL for currently logged in
	 *
	 * @return  int  Number of tickets submitted, including unpublished (but NOT including any deleted)
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public static function getTicketsCount(?int $user_id): int
	{
		$user_id = $user_id ?: self::getUser()->id;

		if (!isset(self::$ticketCountCache[$user_id]))
		{
			/** @var DatabaseDriver $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName('#__ats_tickets'))
				->where($db->quoteName('created_by') . ' = :user_id')
				->bind(':user_id', $user_id, ParameterType::INTEGER);

			self::$ticketCountCache[$user_id] = $db->setQuery($query)->loadResult() ?: 0;
		}

		return self::$ticketCountCache[$user_id];
	}

	/**
	 * Gets the total amount of time spent supporting a specific user
	 *
	 * @param   int|null  $user_id  ID of the user
	 *
	 * @return  float  Total time spent supporting the user, in minutes
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public static function getTimeSpentPerUser(?int $user_id): float
	{
		$user_id = $user_id ?: self::getUser()->id;

		if (!isset(self::$timePerUser[$user_id]))
		{
			/** @var DatabaseDriver $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true)
				->select('SUM(' . $db->quoteName('timespent') . ')')
				->from($db->quoteName('#__ats_tickets'))
				->where($db->quoteName('created_by') . ' = :user_id')
				->bind(':user_id', $user_id, ParameterType::INTEGER);

			self::$timePerUser[$user_id] = $db->setQuery($query)->loadResult() ?: 0;
		}

		return self::$timePerUser[$user_id];
	}

	/**
	 * Get a Joomla user object
	 *
	 * @param   int|null  $id  The ID of the user; NULL for currently logged in user, -1 for fake ‘system‘ user.
	 *
	 * @return  User|null
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public static function getUser(?int $id = null): ?User
	{
		if (is_null($id))
		{
			if (!self::$cacheIdentities || empty(self::$identityUserCache))
			{
				$identityUser = Factory::getApplication()->getIdentity() ?? new User();
			}

			if (!self::$cacheIdentities)
			{
				return $identityUser;
			}

			if (empty(self::$identityUserCache))
			{
				self::$identityUserCache = $identityUser;
			}

			return self::$identityUserCache;
		}
		elseif ($id === -1)
		{
			$user           = new User();
			$user->id       = -1;
			$user->username = 'system';
			$user->email    = 'noreply@example.com';
			$user->name     = Text::_('COM_ATS_CLI_SYSTEMUSERLABEL');

			return $user;
		}

		if (!self::$cacheIdentities)
		{
			return Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($id);
		}

		if (isset(self::$usersCache[$id]))
		{
			return self::$usersCache[$id];
		}

		self::$usersCache[$id] = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($id);

		return self::$usersCache[$id];
	}

	/**
	 * Am I supposed to cache identities?
	 *
	 * While this is desirable in the web application I may want to disable this when running under CLI.
	 *
	 * @return  bool
	 * @since   5.0.0
	 */
	public static function isCacheIdentities(): bool
	{
		return self::$cacheIdentities;
	}

	/**
	 * Set the flag to cache identities (user accounts) in memory to reduce the number of DB queries.
	 *
	 * @param   bool  $cacheIdentities  Should I cache identities?
	 *
	 * @since   5.0.0
	 */
	public static function setCacheIdentities(bool $cacheIdentities): void
	{
		self::$cacheIdentities = $cacheIdentities;
	}

	/**
	 * Is the user a manger?
	 *
	 * @param   int|null  $category  Category id (opt.)
	 * @param   int|null  $userid    Userid (opt. current user if null)
	 *
	 * @return  bool  Is manager?
	 * @since   5.0.0
	 */
	public static function isManager(?int $category = null, ?int $userid = null): bool
	{
		// Automatically fetches the current user if the id is null
		$user = self::getUser($userid);

		return $user->authorise('core.admin', 'com_ats')
			|| $user->authorise('core.manage', 'com_ats')
			|| ($category && $user->authorise('core.manage', 'com_ats.category.' . $category));
	}

	/**
	 * Can this user assign tickets to other users?
	 *
	 * @param   int|null  $category  Category id (opt.)
	 * @param   int|null  $userid    Userid (opt. current user if null)
	 *
	 * @return  bool
	 *
	 * @throws  Exception
	 * @since   5.1.0
	 */
	public static function canAssignTickets(?int $category = null, ?int $userid = null): bool
	{
		// Automatically fetches the current user if the id is null
		$user = self::getUser($userid);

		return
			$user->authorise('core.admin', 'com_ats')
			|| $user->authorise('core.manage', 'com_ats')
			|| ($category && $user->authorise('core.manage', 'com_ats.category.' . $category))
			|| $user->authorise('ats.assign', 'com_ats')
			|| ($category && $user->authorise('ats.assign', 'com_ats.category.' . $category));
	}

	/**
	 * Can this user be assigned tickets?
	 *
	 * @param   int|null  $category  Category id (opt.)
	 * @param   int|null  $userid    Userid (opt. current user if null)
	 *
	 * @return  bool
	 *
	 * @throws  Exception
	 * @since   5.1.0
	 */
	public static function canBeAssignedTickets(?int $category = null, ?int $userid = null): bool
	{
		// Automatically fetches the current user if the id is null
		$user = self::getUser($userid);

		return
			$user->authorise('core.admin', 'com_ats')
			|| $user->authorise('core.manage', 'com_ats')
			|| ($category && $user->authorise('core.manage', 'com_ats.category.' . $category))
			|| $user->authorise('ats.assignee', 'com_ats')
			|| ($category && $user->authorise('ats.assignee', 'com_ats.category.' . $category));
	}

	/**
	 * Can this user read private tickets by any user?
	 *
	 * @param   int|null  $category  Category id (opt.)
	 * @param   int|null  $userid    Userid (opt. current user if null)
	 *
	 * @return  bool
	 *
	 * @throws  Exception
	 * @since   5.1.0
	 */
	public static function canReadPrivate(?int $category = null, ?int $userid = null): bool
	{
		// Automatically fetches the current user if the id is null
		$user = self::getUser($userid);

		return self::isManager($category, $userid)
			|| $user->authorise('ats.private.read', 'com_ats')
			|| ($category && $user->authorise('ats.private.read', 'com_ats.category.' . $category));
	}
}
