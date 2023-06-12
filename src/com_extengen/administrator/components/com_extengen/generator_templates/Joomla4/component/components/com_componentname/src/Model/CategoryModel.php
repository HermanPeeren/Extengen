<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Model;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Helper\ComponentParams;
use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Akeeba\Component\ATS\Administrator\Table\TicketTable;
use Exception;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Tree\NodeInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class CategoryModel extends ListModel
{
	/**
	 * The list of other ticket categories.
	 *
	 * @var   array
	 * @since 5.0.0
	 */
	protected $categories = null;

	/**
	 * The category we are in.
	 *
	 * @var   object
	 * @since 5.0.0
	 */
	protected $category = null;

	/**
	 * Category item data
	 *
	 * @var   CategoryNode|null
	 * @since 5.0.0
	 */
	protected $item = null;

	/**
	 * Array of tickets in the category
	 *
	 * @var   TicketTable
	 * @since 5.0.0
	 */
	protected $tickets = null;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 't.id',
				'title', 't.title',
				'created', 't.created',
			];
		}

		parent::__construct($config);
	}

	/**
	 * Method to get category data for the current category
	 *
	 * @return  CategoryNode|null  The category object
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public function getCategory(): ?CategoryNode
	{
		if (is_object($this->item))
		{
			return $this->item;
		}

		$app    = Factory::getApplication();
		$menu   = $app->getMenu();
		$active = $menu->getActive();
		$params = $active ? $active->getParams() : new Registry;

		$catId      = $this->getState('category.id', 'root');
		$options    = [
			'countItems' => false,
		];
		$categories = Categories::getInstance('Ats', $options);
		$this->item = $categories->get($catId);

		return $this->item;
	}

	/**
	 * Get the parent category.
	 *
	 * @return  NodeInterface|null  An array of categories or false if an error occurs.
	 */
	public function getParent()
	{
		$category = $this->getCategory();

		return is_object($category) ? $category->getParent() : null;
	}


	/**
	 * Method to get a list of items.
	 *
	 * @return  TicketTable[]|null  An array of objects on success, null on failure.
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public function getItems(): ?array
	{
		// Invoke the parent getItems method to get the main list
		$items = parent::getItems();

		if (empty($items))
		{
			return null;
		}

		/** @var DatabaseDriver $db */
		$db     = $this->getDbo();
		$app    = Factory::getApplication();
		$ticket = new TicketTable($db, $app->getDispatcher());

		// Convert the params field into an object, saving original in _params
		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$thisTicket = clone $ticket;
			$thisTicket->bind($items[$i]);
			$items[$i] = $thisTicket;

			// Some contexts may not use tags data at all, so we allow callers to disable loading tag data
			if ($this->getState('load_tags', true))
			{
				$this->tags = new TagsHelper();
				$this->tags->getItemTags('com_ats.ticket', $items[$i]->id);
			}
		}

		return $items;
	}

	/**
	 * Method to build a SQL query to load the list data.
	 *
	 * @return  string    A SQL query
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	protected function getListQuery()
	{
		$app    = Factory::getApplication();
		$user   = $app->getIdentity();
		$groups = array_unique($user->getAuthorisedViewLevels());

		// Create a new query object.
		$db = $this->getDbo();
		/** @var DatabaseQuery $query */
		$query = $db->getQuery(true);
		$query
			->select([
				$db->quoteName('t') . '.*',
			])
			->from($db->quoteName('#__ats_tickets', 't'))
			->leftJoin(
				$db->quoteName('#__categories', 'c'),
				$db->quoteName('c.id') . ' = ' . $db->quoteName('t.catid')
			)
			->whereIn($db->quoteName('c.access'), $groups);

		// Filter by category.
		$categoryId = $this->getState('category.id');

		if ($categoryId)
		{
			$query
				->where($db->quoteName('t.catid') . ' = :ticket_catid')
				->bind(':ticket_catid', $categoryId, ParameterType::INTEGER);
		}

		// Filter by publish state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query
				->where($db->quoteName('t.enabled') . ' = :published')
				->bind(':published', $published, ParameterType::INTEGER);
		}

		/**
		 * Workaround for Latest Open Tickets view
		 *
		 * When we have a user who's only a manager or has private read privileges on some categories we can
		 * only show them tickets which are EITHER public OR in one of the categories they are managing. To
		 * do that we set the state flag `filter.latestWorkaround` and calculate the allowed categories
		 * below.
		 *
		 */
		$workaroundCategories = [];
		$latestWorkaround = $this->getState('filter.latestWorkaround', 0);

		if ($latestWorkaround && !Permissions::isManager(null, $user->id))
		{
			$categories   = Categories::getInstance('Ats', [
				'countItems' => false,
			]);
			$parentCat = $categories->get('root');

			foreach ($parentCat->getChildren(true) as $cat)
			{
				if (Permissions::isManager($cat->id, $user->id) || Permissions::canReadPrivate($cat->id, $user->id))
				{
					$workaroundCategories[] = $cat->id;
				}
			}
		}

		// Filter by public
		$public = $this->getState('filter.public');

		if (is_numeric($public) && $public)
		{
			$public = 1;
			if ($user->guest)
			{
				// Guest users only see the public tickets
				$query->where($db->quoteName('t.public') . ' = :public')
					->bind(':public', $public, ParameterType::INTEGER);
			}
			elseif (!empty($workaroundCategories))
			{
				/**
				 * Logged-in users with per category manager or view private permissions can see the public
				 * tickets in all categories, any ticket they have submitted themselves, and any ticket in a
				 * category they have manage or view private permissions.
				 */
				$myUserId = $user->id;
				$query->extendWhere('AND', [
					$db->quoteName('t.public') . ' = :public',
					$db->quoteName('t.created_by') . ' = :myUserId',
					$db->quoteName('c.id') . ' IN (' . implode(',', ArrayHelper::toInteger($workaroundCategories)) . ')',
				], 'OR')
				      ->bind(':public', $public, ParameterType::INTEGER)
				      ->bind(':myUserId', $myUserId, ParameterType::INTEGER);
			}
			else
			{
				// Logged-in users see the public tickets OR the tickets they have submitted themselves
				$myUserId = $user->id;
				$query->extendWhere('AND', [
					$db->quoteName('t.public') . ' = :public',
					$db->quoteName('t.created_by') . ' = :myUserId',
				], 'OR')
					->bind(':public', $public, ParameterType::INTEGER)
					->bind(':myUserId', $myUserId, ParameterType::INTEGER);
			}
		}

		// Filter by ownership
		$created_by = $this->getState('filter.created_by');

		if (is_numeric($created_by))
		{
			$query->where($db->quoteName('created_by') . ' = :created_by')
				->bind(':created_by', $created_by, ParameterType::INTEGER);
		}

		// Filter by status
		$status = $this->getState('filter.status') ?: [];
		$status = is_array($status) ? $status : explode(',', $status);
		$status = array_intersect($status, array_keys(ComponentParams::getStatuses()));

		if (!empty($status))
		{
			$query->whereIn($db->quoteName('t.status'), $status, ParameterType::STRING);
		}

		// Filter by assigned
		$assigned = $this->getState('filter.assigned_to') ?: [];
		$assigned = is_array($assigned) ? $assigned : explode(',', $assigned);
		$assigned = array_filter($assigned, function ($uid) use ($categoryId, $user) {
			return
				// It's allowed to see tickets assigned to me, without checking if I am a manager
				(!empty($user->id) && $user->id == $uid)
				// I can see someone else's assigned tickets if BOTH of us are managers of the category
				|| (
					!empty($user->id)
					&& Permissions::isManager($categoryId ?: null, $user->id)
					&& Permissions::isManager($categoryId ?: null, $uid)
				);
		});

		if (!empty($assigned))
		{
			$query->whereIn($db->quoteName('t.assigned_to'), $assigned, ParameterType::INTEGER);
		}

		// Filter by search in title
		$search = $this->getState('list.filter');

		if (!empty($search))
		{
			$search = '%' . trim($search) . '%';
			$query->where($db->quoteName('t.title') . ' LIKE :title');
			$query->bind(':title', $search);
		}

		// Filter on the language.
		if ($this->getState('filter.language'))
		{
			$lang = $app->getLanguage();
			$query->whereIn($db->quoteName('c.language'), [
				$lang->getTag(), '*',
			], ParameterType::STRING);
		}

		// Set sortname ordering if selected
		$orderColumn    = $this->getState('list.ordering', 'a.ordering');
		$orderDirection = $this->getState('list.direction', 'ASC');
		$query->order($db->quoteName($orderColumn) . ' ' . $db->escape($orderDirection));

		return $query;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app    = Factory::getApplication();
		$params = ComponentHelper::getParams('com_ats');

		// Get list ordering default from the parameters
		$menuParams   = ($menu = $app->getMenu()->getActive()) ? $menu->getParams() : new Registry;
		$mergedParams = clone $params;
		$mergedParams->merge($menuParams);

		$Itemid = $app->input->get('Itemid', 0, 'int');

		// Number of items to display (list limit)
		$limit = $app->getUserStateFromRequest(
			'com_ats.category.list.' . $Itemid . '.limit',
			'limit',
			$mergedParams->get('tickets_display_num', $app->get('list_limit')),
			'uint'
		);
		$this->setState('list.limit', $limit);

		// Number of items to skip (list start / limitstart)
		$this->setState('list.start', $app->input->get('limitstart', 0, 'uint'));

		// Optional filter text
		$search = $app->getUserStateFromRequest('com_ats.category.list.' . $Itemid . '.filter-search', 'filter-search', '', 'string');
		$this->setState('list.filter', $search);

		// Order column
		$initialSort = $mergedParams->get('initial_sort', 'created DESC') ?: 'created DESC';
		[$initialCol, $initialOrder] = explode(' ', $initialSort, 2);

		$orderCol = $app->input->get('filter_order', $initialCol);
		$this->setState('list.ordering', in_array($orderCol, $this->filter_fields) ? $orderCol : 'created');

		// Order direction
		$listOrder = $app->input->get('filter_order_Dir', strtoupper($initialOrder));
		$this->setState('list.direction', in_array($listOrder, ['ASC', 'DESC', '']) ? $listOrder : 'DESC');

		// Category ID
		$id = $app->input->get('id', 0, 'int');
		$this->setState('category.id', $id);

		// Language filter
		$this->setState('filter.language', Multilanguage::isEnabled());

		// Published
		$published = $app->getUserStateFromRequest(
			'com_ats.category.list.' . $Itemid . '.published',
			'published', '', 'string'
		);
		$this->setState('filter.published', is_numeric($published) ? (int) $published : '');

		// Public
		$public = $app->getUserStateFromRequest(
			'com_ats.category.list.' . $Itemid . '.public',
			'public', '', 'string'
		);
		$this->setState('filter.public', is_numeric($public) ? (int) $public : '');

		// Status
		$status = $app->getUserStateFromRequest(
			'com_ats.category.list.' . $Itemid . '.status',
			'status', '', 'array'
		);
		$this->setState('filter.status', is_array($status) ? $status : '');

		// Assigned
		$assigned = $app->getUserStateFromRequest(
			'com_ats.category.list.' . $Itemid . '.assigned_to',
			'assigned_to', '', 'string'
		);
		$this->setState('filter.assigned_to', is_numeric($assigned) ? (int) $assigned : '');


		// Apply restrictions to non–manager users
		$user      = $app->getIdentity();
		$isManager = Permissions::isManager($id, $user->id);

		if (!$isManager)
		{
			$this->setState('filter.published', 1);
			$this->setState('filter.public', Permissions::canReadPrivate($id, $user->id) ? 0 : 1);
			$this->setState('filter.assigned', null);
			$this->setState('list.filter', null);
		}

		// Load the parameters.
		$this->setState('params', $params);
	}
}