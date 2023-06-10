<?php

/**
 * @package     Joomla.Site
 * @subpackage  COM_EXTENGEN
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Yepr\Extengen\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Extengen model for the Joomla extengen component.
 *
 * @since  1.0.0
 */
class ProjectModel extends BaseDatabaseModel
{
	/**
	 * @var string item
	 */
	protected $_item = null;

	/**
	 * Gets a extengen
	 *
	 * @param   integer  $pk  Id for the extengen
	 *
	 * @return  mixed Object or null
	 *
	 * @since   1.0
	 */
	public function getItem($pk = null)
	{
		$app = Factory::getApplication();
		$pk = $app->input->getInt('id');

		if ($this->_item === null)
		{
			$this->_item = array();
		}

		if (!isset($this->_item[$pk]))
		{
			try
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true);

				$query->select('*')
					->from($db->quoteName('#__extengen_projects', 'a'))
					->where('a.id = ' . (int) $pk);

				$db->setQuery($query);
				$data = $db->loadObject();

				if (empty($data))
				{
					throw new \Exception(Text::_('COM_EXTENGEN_ERROR_PROJECT_NOT_FOUND'), 404);
				}

				$this->_item[$pk] = $data;
			}
			catch (\Exception $e)
			{
				$this->setError($e);
				$this->_item[$pk] = false;
			}
		}

		return $this->_item[$pk];
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

		$this->setState('extengen.id', $app->input->getInt('id'));
		$this->setState('params', $app->getParams());
	}
}
