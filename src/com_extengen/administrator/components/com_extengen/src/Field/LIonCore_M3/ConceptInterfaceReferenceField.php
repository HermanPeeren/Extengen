<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.9.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\Field\LIonCore_M3;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;


// The class name must always be the same as the filename (in camel case)
// extend the list field type
class ConceptInterfaceReferenceField extends ListField
{
	//The field class must know its own type through the variable $type.
	protected $type = 'ConceptInterfaceReference';

	/**
	 * Get the options for the list field: all defined interfaces that can be implemented.
	 * To be used in a "implements" multiple subfield
	 *
	 */
	public function getOptions()
	{
		$classifierNameMap = [];
		$classifierNameMap[0] = '&nbsp;';

		$AST = $this->initiateAST();
		if (!is_null($AST))
		{
			// Get the entities that are currently in the model.
			// todo: cache this (in the AST) and in the project-model
			foreach ($AST->languageEntities as $languageEntity)
			{
				// Only use ConnceptInterfaces
				if (($languageEntity->languageEntity_type == 'Classifier')
					&& ($languageEntity->classifier->classifier_type == 'ConceptInterface'))
				{
					$classifierNameMap[$languageEntity->key] = ucfirst($languageEntity->name);
				}
			}
		}

		// Todo: sort
		// Todo: also get the ConceptInterfaces of the dependent languages, if applicable

        // use a for-each to iterate over the $classifierNameMap
		$classifierOptions = [];
        foreach($classifierNameMap as $key => $classifierName)
        {
	        // Set an array with the  value / text items.
	        $classifierOptions[] = array("value" => $key, "text" => $classifierName);
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $classifierOptions);
        return $options;
    }


	/**
	 * Get the (json-encoded) form-data of the projectform that form the AST.
	 *
	 * @return object|null
	 */
	private function initiateAST(): ?object
	{
		// Which project?
		// todo: better use a hidden variable in the form, but do you need to set it in every subform?
		//$projectFormId    = $this->form->getValue('id'); // or "project_id", if you have to put it in all subforms

		// Get project_id from get-query string input
		$input = Factory::getApplication()->input;
		$projectFormId = $input->getInt('id');

		if ($projectFormId)
		{
			$db = $this->getDatabase();
			$getASTquery = $db->getQuery(true)
				->select($db->quoteName('form_data'))
				->from($db->quoteName('#__extengen_projectforms'))
				->where($db->quoteName('id') . ' = :id')
				->bind(':id', $projectFormId, ParameterType::INTEGER);
			$db->setQuery($getASTquery);

			return json_decode($db->loadResult());
		}

		return null;
	}

}
