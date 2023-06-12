<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\Field;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;


// The class name must always be the same as the filename (in camel case)
// extend the list field type
class EntityReferenceField extends ListField
{
	//The field class must know its own type through the variable $type.
	protected $type = 'EntityReference';

	/**
	 * Get the options for the list field: all entities currently in the project
	 *
	 */
	public function getOptions()
	{
		// Get the entities that are currently in the model.
		// todo: cache this (in the AST) and in the project-model
		$AST = $this->initiateAST();
		$entityNameMap = [];
		$entityNameMap[0] = '&nbsp;';
		foreach ($AST->datamodel as $entity)
		{
			$entityNameMap[$entity->entity_id] = ucfirst($entity->entity_name);
		}

        // use a for each to iterate over the $entityNameMap
		$entityOptions = [];
        foreach($entityNameMap as $uuid => $entityName)
        {
	        // Set an array with the  value / text items.
	        $entityOptions[] = array("value" => $uuid, "text" => $entityName);
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $entityOptions);
        return $options;
    }


	/**
	 * Get the (json-encoded) form-data of the project that form the AST.
	 *
	 * @return object
	 */
	private function initiateAST(): object
	{
		// Which project?
		// todo: better use a hidden variable in the form, but do you need to set it in every subform?
		//$projectId    = $this->form->getValue('id'); // or "project_id", if you have to put it in all subforms

		// Get project_id from get-query string input
		$input = Factory::getApplication()->input;
		$projectId = $input->getInt('id');

		$db = $this->getDatabase();
		$getASTquery = $db->getQuery(true)
			->select($db->quoteName('form_data'))
			->from($db->quoteName('#__extengen_projects'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $projectId, ParameterType::INTEGER);
		$db->setQuery($getASTquery);

		return json_decode($db->loadResult());
	}

}
