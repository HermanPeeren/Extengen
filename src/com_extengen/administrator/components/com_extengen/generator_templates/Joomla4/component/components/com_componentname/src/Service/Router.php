<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Service;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Categories\CategoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Menu\MenuItem;
use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\MVC\Model\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class Router extends RouterView
{
	use MVCFactoryAwareTrait;
	use DatabaseAwareTrait;

	/**
	 * Maps ATS 1.x—4.x views to their ATS 5+ counterparts.
	 *
	 * @const array
	 * @since 5.0.0
	 */
	private const OLD_VIEW_MAP = [
		'categories'      => 'categories',
		'tickets'         => 'category',
		'latests'         => 'latest',
		'mies'            => 'my',
		'newticket'       => 'new',
		'newtickets'      => 'new',
		'assignedtickets' => 'assigned',
		'assignedticket'  => 'assigned',
	];

	/**
	 * The category cache
	 *
	 * @var   array
	 * @since 5.0.0
	 */
	private $categoryCache = [];

	/**
	 * The category factory
	 *
	 * @var   CategoryFactoryInterface
	 * @since 5.0.0
	 */
	private $categoryFactory;

	public function __construct(SiteApplication $app, AbstractMenu $menu, DatabaseInterface $db, MVCFactory $factory, CategoryFactoryInterface $categoryFactory)
	{
		$this->categoryFactory = $categoryFactory;
		$this->setDbo($db);
		$this->setMVCFactory($factory);

		$categories = new RouterViewConfiguration('categories');
		$categories->setKey('id');
		$this->registerView($categories);

		$category = new RouterViewConfiguration('category');
		$category
			->setKey('id')
			->setParent($categories, 'catid')
			->setNestable();
		$this->registerView($category);

		$ticket = new RouterViewConfiguration('ticket');
		$ticket
			->setKey('id')
			->setParent($category, 'catid')
			->addLayout('edit');
		$this->registerView($ticket);

		$new = new RouterViewConfiguration('new');
		$new
			->setParent($category, 'catid');
		$this->registerView($new);

		$this->registerView(new RouterViewConfiguration('users'));
		$this->registerView(new RouterViewConfiguration('latest'));
		$this->registerView(new RouterViewConfiguration('my'));
		$this->registerView(new RouterViewConfiguration('my'));
		$this->registerView(new RouterViewConfiguration('assigned'));
		$this->registerView(new RouterViewConfiguration('post'));
		$this->registerView(new RouterViewConfiguration('posts'));
		$this->registerView(new RouterViewConfiguration('managernote'));
		$this->registerView(new RouterViewConfiguration('managernotes'));

		// Migrate the view names of all menu items. Required by StandardRules which tries to get menu items directly.
		foreach ($menu->getItems(['component'], ['com_ats']) as $itemToConvert)
		{
			$this->migrateMenuItem($itemToConvert);
		}

		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	/** @inheritdoc */
	public function build(&$query)
	{
		// Don't let the controller be set in a SEF URL.
		if (isset($query['controller']))
		{
			unset ($query['controller']);
		}

		// Convert the view name, taking into account the ones used in older versions of the component.
		if (isset($query['view']))
		{
			$query['view'] = $this->translateOldViewName($query['view']);
		}

		return parent::build($query);
	}

	/** @inheritdoc  */
	public function parse(&$segments)
	{
		$query = parent::parse($segments);

		if (isset($query['view']))
		{
			$query['view'] = $this->translateOldViewName($query['view']);
		}

		return $query;
	}

	/**
	 * Method to get the segment(s) for a ticket
	 *
	 * @param   string  $id     ID of the ticket to retrieve the segments for
	 * @param   array   $query  The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 */
	public function getTicketSegment($id, $query)
	{
		if (strpos($id, ':'))
		{
			return [(int) $id => $id];
		}

		$id        = (int) $id;
		$numericId = $id;
		$db        = $this->getDbo();
		$query     = $db->getQuery(true);
		$query->select($db->quoteName('alias'))
			->from($db->quoteName('#__ats_tickets'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$db->setQuery($query);

		$id .= ':' . $db->loadResult();

		return [$numericId => $id];
	}

	/**
	 * Method to get the segment(s) for a category
	 *
	 * @param   string  $segment  Segment to retrieve the ID for
	 * @param   array   $query    The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 */
	public function getCategoriesId($segment, $query)
	{
		return $this->getCategoryId($segment, $query);
	}

	/**
	 * Method to get the segment(s) for a category
	 *
	 * @param   string  $id     ID of the category to retrieve the segments for
	 * @param   array   $query  The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 */
	public function getCategoriesSegment($id, $query)
	{
		return $this->getCategorySegment($id, $query);
	}

	/**
	 * Method to get the id for a category
	 *
	 * @param   string  $segment  Segment to retrieve the ID for
	 * @param   array   $query    The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 */
	public function getCategoryId($segment, $query)
	{
		$id = $query['id'] ?? 'root';

		$category = $this->getCategories(['access' => false])->get($id);

		if (!$category)
		{
			return false;
		}

		foreach ($category->getChildren() as $child)
		{
			if ($child->alias == $segment)
			{
				return $child->id;
			}
		}

		return false;
	}

	/**
	 * Method to get the segment(s) for a category
	 *
	 * @param   string  $id     ID of the category to retrieve the segments for
	 * @param   array   $query  The request that is built right now
	 *
	 * @return  array|string  The segments of this item
	 */
	public function getCategorySegment($id, $query)
	{
		$category = $this->getCategories(['access' => true])->get($id);

		if ($category)
		{
			$path    = array_reverse($category->getPath(), true);
			$path[0] = '1:root';

			foreach ($path as &$segment)
			{
				[$id, $segment] = explode(':', $segment, 2);
			}

			return $path;
		}

		return [];
	}

	/**
	 * Method to get the segment(s) for a ticket
	 *
	 * @param   string  $segment  Segment of the ticket to retrieve the ID for
	 * @param   array   $query    The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 */
	public function getTicketId($segment, $query)
	{
		return (int) $segment;
	}

	/**
	 * Method to get categories from cache
	 *
	 * @param   array  $options  The options for retrieving categories
	 *
	 * @return  CategoryInterface  The object containing categories
	 *
	 * @since   4.0.0
	 */
	private function getCategories(array $options = []): CategoryInterface
	{
		$key = serialize($options);

		if (!isset($this->categoryCache[$key]))
		{
			$this->categoryCache[$key] = $this->categoryFactory->createCategory($options);
		}

		return $this->categoryCache[$key];
	}

	/**
	 * Translates view names from older versions of the component to the ones currently in use.
	 *
	 * @param   string  $oldViewName
	 *
	 * @return  string
	 * @since   5.0.0
	 */
	private function translateOldViewName(string $oldViewName): string
	{
		$oldViewName = strtolower($oldViewName);

		return self::OLD_VIEW_MAP[$oldViewName] ?? $oldViewName;
	}

	/**
	 * Backwards compatibility for older versions of the component.
	 *
	 * 1. Older versions had Formal case views (e.g. Categories instead of categories) which cause the Joomla View
	 *    Router to choke and die when building and parsing routes. This fixes that problem.
	 *
	 * 2. Much older versions have used the view names category (instead of releases), release (instead of items) etc.
	 *    This will transparently convert the view name of existing menu items to the new view names.
	 *
	 * This method must be used TWICE in a router:
	 * a. Building a route, if there is a detected menu item; and
	 * b. Parsing a route, if there is an active menu item.
	 *
	 * @param   MenuItem|null  $item  The menu item to address or null if there's no menu item.
	 *
	 * @return  void
	 * @since   5.0.0
	 *
	 * @throws  Exception
	 */
	private function migrateMenuItem(?MenuItem &$item): void
	{
		// Convert the view name
		$oldView = $item->query['view'];
		$item->query['view'] = $this->translateOldViewName($item->query['view']);
		$item->link = str_replace('view=' . $oldView, 'view=' . $item->query['view'], $item->link);

		// Migration: "Tickets" (now: category) view used to set `category` instead of `id`
		if ($item->query['view'] == 'category')
		{
			$item->query['id'] = $item->query['category'] ?? $item->query['id'] ?? 0;
		}
	}
}