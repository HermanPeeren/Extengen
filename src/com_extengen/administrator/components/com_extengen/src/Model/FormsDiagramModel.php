<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Event\AbstractEvent;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\WorkflowBehaviorTrait;
use Joomla\CMS\MVC\Model\WorkflowModelInterface;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Table\TableInterface;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\UCM\UCMType;
use Joomla\CMS\Versioning\VersionableModelTrait;
use Joomla\CMS\Workflow\Workflow;
use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

use	Yepr\Component\Extengen\Administrator\Model\LanguageStringUtil;


/**
 * Forms Diagram Model: to get the project form data from the db
 */
class FormsDiagramModel extends AdminModel
{
	/**
	 * The (internal) id of the project forms definition from which we generate form files
	 *
	 * @var   int|Integer
	 */
	protected int $projectFormId;
	/**
	 * Set the project id.
	 *
	 * @param   int  $projectId
	 */
	public function setProjectFormId(int $projectFormId): void
	{
		$this->projectFormId = $projectFormId;
	}

	/**
	 * Get the (json-encoded) form-data of the project that form the AST.
	 * todo: use this as private method and query the PlantUML-stuff from this model
	 *
	 * @return object the AST
	 */
	public function getAST(): object
	{
		$db = $this->getDatabase();
		$getASTquery = $db->getQuery(true)
			->select($db->quoteName('form_data'))
			->from($db->quoteName('#__extengen_projectforms'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $this->projectFormId, ParameterType::INTEGER);
		$db->setQuery($getASTquery);

		return json_decode($db->loadResult());
	}

	/**
	 * NOT IN USE NOW (instead: directly query via getAST()).
	 * Method to get the project-data.
	 * Overriden to prevent initiating a non-existing Generator-Table.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed   Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
			$pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');

			// get the Project table
			$table = $this->getTable("Project");

			if ($pk > 0) {
				// Attempt to load the row.
				$return = $table->load($pk);

				// Check for a table object error.
				if ($return === false) {
					// If there was no underlying error, then the false means there simply was not a row in the db for this $pk.
					if (!$table->getError()) {
						$this->setError(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));
					} else {
						$this->setError($table->getError());
					}

					return false;
				}
			}

			// Convert to the CMSObject before adding other data.
			$properties = $table->getProperties(1);
			$item = ArrayHelper::toObject($properties, CMSObject::class);

			if (property_exists($item, 'params')) {
				$registry = new Registry($item->params);
				$item->params = $registry->toArray();
			}

			return $item;
	}

	/**
	 * NOT USED ATM. BUT MUST BE IMPLEMENTED. MIGHT USE IN FUTURE.
	 * Method to get the row form.
	 *
	 * @param   array    $data      Data for the form
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = true)
	{
		return false;
	}

}
