<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Model;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;

class CategoriesModel extends ListModel
{
	/**
	 * Array of child-categories
	 *
	 * @var    CategoryNode[]|null
	 */
	private $items = null;

	/**
	 * Parent category of the current one
	 *
	 * @var    CategoryNode|null
	 */
	private $parent = null;

	/**
	 * Redefine the function and add some properties to make the styling easier
	 *
	 * @return  array|null
	 * @since   5.0.0
	 */
	public function getItems()
	{
		if ($this->items !== null)
		{
			return $this->items;
		}

		$app    = Factory::getApplication();
		$active = $app->getMenu()->getActive();
		$params = $active ? $active->getParams() : new Registry;

		$options      = [
			'countItems' => false,
		];
		$categories   = Categories::getInstance('Ats', $options);

		$this->parent = $categories->get($this->getState('filter.parentId', 'root'));
		$this->items  = is_object($this->parent) ? $this->parent->getChildren() : null;

		return $this->items;
	}

	/**
	 * Gets the id of the parent category for the selected list of categories
	 *
	 * @return   CategoryNode|null  The id of the parent category
	 *
	 * @since    5.0.0
	 */
	public function getParent()
	{
		if (!is_object($this->parent))
		{
			$this->getItems();
		}

		return $this->parent;
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 * @since   5.0.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.extension');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . $this->getState('filter.parentId');

		return parent::getStoreId($id);
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
		$app = Factory::getApplication();
		$this->setState('filter.extension', 'com_ats');

		// Get the parent id if defined.
		$parentId = $app->input->getInt('id');
		$this->setState('filter.parentId', $parentId);

		$params = $app->getParams();
		$this->setState('params', $params);

		$this->setState('filter.published', 1);
		$this->setState('filter.access', true);
	}
}