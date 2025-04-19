<?php
/**
 * @package     Extension Generator
 * @subpackage  Joomla Generator
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2025. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\Model\Generator;

use	Yepr\Component\Extengen\Administrator\Model\LanguageStringUtil;
use DOMDocument;

/**
 * A concrete generator to create the project-form files
 * generated files: form xml-files
 *
 * @package     Yepr\Component\Extengen\Administrator\Model\Generator\
 *
 * @since       version 1.0
 */
class ProjectForms
{


	/**
	 * The Abstract Syntax Tree (AST) = the form-data of the project as 1 object with hierarchical properties
	 *
	 * @var object
	 */
	protected object $AST;

	/**
	 * The Language String Utilities to make language strings and put them in the language files.
	 * Not in use for projectforms at the moment
	 * (because the generated language strings should have to be added to the existing ones of this component).
	 *
	 * @var LanguageStringUtil
	 */
	protected LanguageStringUtil $languageStringUtil;

	/**
	 * The path to the administrator-side of com_extengen
	 *
	 * @var string
	 */
	protected string $extengenAdminPath;

	/**
	 * The name of these project forms
	 *
	 * @var string
	 */
	protected string $projectFormName;

	/**
	 * The namespace of these project forms (for Rules and Fields)
	 *
	 * @var string
	 */
	protected string $projectFormNamespace;

	/**
	 * Generator Class Constructor
	 *
	 * @param   string        $projectFormName    The name of the projectform
	 * @param   object        $AST                The Abstract Syntax Tree (AST) = the form-data of the project
	 * @param   object        $languageStringUtil The languagestring utility
	 */
	public function __construct(string $projectFormName, object $AST, LanguageStringUtil $languageStringUtil)
	{
		$this->projectFormName = $projectFormName;
		$this->AST = $AST;
		$this->languageStringUtil = $languageStringUtil;

		// General paths to admin part of extengen-component and Twig-cache
		$this->extengenAdminPath = JPATH_ROOT . '/administrator/components/com_extengen/';

		// The project form namespace
		$this->projectFormNamespace = 'Yepr\\Component\\Extengen\\Administrator\\Projectform\\';
	}

	/**
	 * Generate the PROJECTFORM xml-files. This method is called from the model.
	 *    - One xml-file for each concept.
	 *
	 * @return array the log of this concrete generator; logs which files were generated
	 */
	public function generate(): array
	{
		// Initialise variables
		$projectForm = $this->AST;

		// The path where the projectforms are made
		$generatedProjectFormFilesPath = $this->extengenAdminPath . 'forms/ProjectForms/' . $this->projectFormName .'/';

		// Create the directory for the projectForm files if it doen't exist
		if (!file_exists($generatedProjectFormFilesPath)) {
			mkdir($generatedProjectFormFilesPath, 0755, true);
		}

		// Get the concepts + conceptInterfaces and the root-concept (assume 1 root for now)
		// Make a key-concept-map + key-conceptInterface-map
		$keyConceptMap = [];
		$keyConceptInterfaceMap = [];
		$root = new \stdClass();
		foreach ($projectForm->languageEntities as $languageEntity)
		{
			if ($languageEntity->languageEntity_type=='Classifier')
			{
				// Concepts
				if ($languageEntity->classifier->classifier_type=='Concept')
				{
					$keyConceptMap[$languageEntity->key] = $languageEntity;// ? alleen de naam nodig???

					// is this the root? Property partition only exists if it == 1.
					// todo: multiple root nodes; we now only assume 1 (= the last one that is encountered in the concepts)
					if (property_exists($languageEntity->classifier->concept,'partition'))
					{
						$root = $languageEntity;
					}
				}

				// ConceptInterfaces
				if ($languageEntity->classifier->classifier_type=='ConceptInterface')
				{
					$keyConceptInterfaceMap[$languageEntity->key] = $languageEntity;// ? alleen de naam nodig???
				}

			}

		}

		// A type of a link can refer to any Classifier, so make a combined map
		// todo: add Annotations
		$keyClassifierMap = array_merge($keyConceptMap, $keyConceptInterfaceMap);

		$log = [];
		$logAppend = function ($append) use(&$log) {$log = array_merge($log, $append);};


		// Gather the extensions and implementations of concept-interfaces and concepts.
		$umlRef = [];

		// Initiate the uml-file.
		$umlCreate = [];
		$umlCreate[] = "@startuml";

		// Loop over all CONCEPTINTERFACES TODO: extract the repetition with "concepts"
		foreach ($keyConceptInterfaceMap as $languageEntity)
		{

			// Add fields to the fieldset

			// Get extended conceptInterface and add it as the parent
			$extends = $languageEntity->classifier->conceptInterface->extends;
			if (!empty($extends))
			{
				

				$umlRef[] =  $keyConceptInterfaceMap[$extends]->name . ' <|-- ' . $languageEntity->name;



			}

			foreach ($languageEntity->classifier->feature as $feature)
			{
				// Get other properties of this conceptInterface, if any
				if ($feature->feature_type=='Property')
				{
					$umlCreate[] = $feature->name; // todo: type
				}
				// Get children and references
				if ($feature->feature_type=='Link')
				{
					$featureName = ":" . $feature->name;

					if (( $feature->is_optional) && ( $feature->link->is_multiple)) $toCardinality = ' "0..*" ';
					if (( $feature->is_optional) && (!$feature->link->is_multiple)) $toCardinality = ' "0..1" ';
					if ((!$feature->is_optional) && ( $feature->link->is_multiple)) $toCardinality = ' "1..*" ';
					if ((!$feature->is_optional) && (!$feature->link->is_multiple)) $toCardinality = ' "1" ';

					$fromCardinality = ''; // we don't know anything of the cardinality on the "from-side"

					if ($feature->link->link_type=='Containment')
					{
						if (!empty($feature->link->type))
							$umlRef[] =  $languageEntity->name . $fromCardinality . ' *-- ' . $toCardinality
								. $keyClassifierMap[$feature->link->type]->name . $featureName;
					}
					if ($feature->link->link_type=='Reference')
					{
						if (!empty($feature->link->type))
							$umlRef[] =  $languageEntity->name . $fromCardinality . ' --> '  . $toCardinality
								. $keyClassifierMap[$feature->link->type]->name . $featureName;
					}
				}
			}

			$umlCreate[] = "}
				
				";

		}

		// Loop over all CONCEPTS
		foreach ($keyConceptMap as $languageEntity)
		{
			// Add fields to the fieldset





			$umlCreate[]  = '			
				object ' . $languageEntity->name . ' {
	  			';

			// Get extended concept and add it as the parent
			$extends = $languageEntity->classifier->concept->extends;
			if (!empty($extends))
			{

				$umlRef[] =  $keyConceptMap[$extends]->name . ' <|-- ' . $languageEntity->name;

			}

			// Get implemented conceptInterfaces and add an implements-line to them
			$implements = $languageEntity->classifier->concept->implements;
			if (!empty($implements))
			{
				foreach ($implements as $interface)
				{
					$umlRef[] =  $keyConceptInterfaceMap[$interface->conceptInterface]->name . ' <|.. ' . $languageEntity->name;
				}

			}

			foreach ($languageEntity->classifier->feature as $feature)
			{
				// Get other properties of this concept, if any
				if ($feature->feature_type=='Property')
				{
					$umlCreate[] = $feature->name; // todo: type
				}
				// Get children and references
				if ($feature->feature_type=='Link')
				{
					$featureName = ":" . $feature->name;

					// because $feature->is_optional and $feature->link->is_multiple are checkboxes,
					// those properties don't exist if the checkbox was empty.
					if (!property_exists($feature, 'is_optional')) $feature->is_optional = false;
					if (!property_exists($feature->link, 'is_multiple')) $feature->link->is_multiple = false;

					if (( $feature->is_optional) && ( $feature->link->is_multiple)) $toCardinality = ' "0..*" ';
					if (( $feature->is_optional) && (!$feature->link->is_multiple)) $toCardinality = ' "0..1" ';
					if ((!$feature->is_optional) && ( $feature->link->is_multiple)) $toCardinality = ' "1..*" ';
					if ((!$feature->is_optional) && (!$feature->link->is_multiple)) $toCardinality = ' "1" ';

					$fromCardinality = ''; // we don't know anything of the cardinality on the "from-side"

					if ($feature->link->link_type=='Containment')
					{
						if (!empty($feature->link->type))
							$umlRef[] =  $languageEntity->name . $fromCardinality . ' *-- ' . $toCardinality
								. $keyClassifierMap[$feature->link->type]->name . $featureName;
					}
					if ($feature->link->link_type=='Reference')
					{
						if (!empty($feature->link->type))
							$umlRef[] =  $languageEntity->name . $fromCardinality . ' --> '  . $toCardinality
								. $keyClassifierMap[$feature->link->type]->name . $featureName;
					}
				}
			}

			$umlCreate[] = "}
				
				";
		}

		// Add the relationships
		$umlCreate[] = implode("\n", $umlRef);

		$umlCreate[] = '@enduml';

		// Compact all lines to one file
		$uml = implode("\n", $umlCreate);

		// Create a form from a node
		$createForm = function ($node) use ($generatedProjectFormFilesPath, $logAppend)
		{
			// Start the form-creation
			$form               = new DOMDocument();
			$form->encoding     = 'utf-8';
			$form->xmlVersion   = '1.0';
			$form->formatOutput = true;
			$root               = $form->createElement('form');
			$form->appendChild($root);

			$formName =$node->name;

			// Add a fieldset
			$fieldset   = $form->createElement('fieldset');
			$ruleprefix = new \DOMAttr('addruleprefix',
				$this->projectFormNamespace . $formName . '\\Rule');
			$fieldset->setAttributeNode($ruleprefix);
			$fieldprefix = new \DOMAttr('addfieldprefix',
				$this->projectFormNamespace . $formName . '\\Field');
			$fieldset->setAttributeNode($fieldprefix);
			$root->appendChild($fieldset);

			// Add fields to the fieldset



			//........



			// Write to file
			$form->save($generatedProjectFormFilesPath . $formName . '.xml');
			$logAppend([$formName . '.xml generated']);

		};



		// Closure to loop through the tree (based on a json_decoded object) and apply a function
		// (can be recursively used by calling $treeIterate from the function)
		$treeIterate = function ($rootNode, $function)//, $done
		{
			foreach ($rootNode as $childNode)
			{
				$function($childNode);
			}
		};

		//$done = [];
		// Loop down the tree and create the forms
	/*	$treeIterate($root, 'createForm')
		{

		}*/



		// ---------------------- from Forms generator -----------------------------------
		// A type of a link can refer to any Classifier, so make a combined map
		// todo: add Annotations
		$keyClassifierMap = array_merge($keyConceptMap, $keyConceptInterfaceMap);


		// Loop over the concepts to make a map of entity_id to entity
		// Per entity: loop over the fields to make a map of field_id to field
		// todo: make this more general to use it when building generators in Extengen
		$entityMap = [];
		$fieldMap  = [];
		foreach ($projectForm->datamodel as $entity)
		{
			$entityMap[$entity->entity_id] = $entity;
			foreach ($entity->field as $field)
			{
				$fieldMap[$field->field_id] = $field;
			}
		}

		$log = [];
		$logAppend = function ($append) use(&$log) {$log = array_merge($log, $append);};
		// Path to administrator-side of com_extengen
		$extengenAdminPath = $this->extengenAdminPath;

		// Path to generated files of component
		$generatedFilesPathComponent = $extengenAdminPath . '/forms/projectForms/' . $this->projectFormName .'/';

		// Path of generated file IN the directory for generated files of component
		$generatedFilePath = 'administrator/components/com_'.strtolower($componentName).'/';


		// Create the directory for the form files if it doen't exist
		$formDirectory = $generatedFilesPathComponent . $generatedFilePath . 'forms/';
		if (!file_exists($formDirectory)) {
			mkdir($formDirectory, 0755, true);
		}

		$logAppend(["&nbsp;", "<b>=== FORM FILES ===</b>"]);

		// Create a form for each detailspage (editing is now only done in detailspages in backend)
		// todo: selection of fields, subforms, custom edit-fields



		foreach ($projectForm->pages as $page)
		{
			$formName = strtolower($page->page_name);

			// Generate the detail page forms + subforms
			if (($page->page_type)=="detailspage" || ($page->page_type)=="subform")
			{

// todo: SimpleXML doesn't format the xml, DOMdocument only puts new tags on new line (with identation),
				// todo: but I also want the attributes on new line, vertically stacked. Hence: paste my own xml...
				// todo: or can I extend DOMdocument to adjust output-format?
				// for now: stick to DOMdocument...


				// Start the form-creation
				$form               = new DOMDocument();
				$form->encoding     = 'utf-8';
				$form->xmlVersion   = '1.0';
				$form->formatOutput = true;
				$root               = $form->createElement('form');
				$form->appendChild($root);

				// Add a fieldset
				$fieldset   = $form->createElement('fieldset');
				$ruleprefix = new \DOMAttr('addruleprefix',
					$companyNamespace . '\\Component\\' . $this->componentName . '\\Administrator\\Rule');
				$fieldset->setAttributeNode($ruleprefix);
				$fieldprefix = new \DOMAttr('addfieldprefix',
					$companyNamespace . '\\Component\\' . $this->componentName . '\\Administrator\\Field');
				$fieldset->setAttributeNode($fieldprefix);
				$root->appendChild($fieldset);

				// Add fields to the fieldset

				// Find the entity referenced in that page todo: multiple entities?
				$entity_id = $page->entity_ref->entity_ref0->reference;
				$entity    = $entityMap[$entity_id];

				// Loop over the fields in that entity and map them to HtmlFields
				foreach ($entity->field as $field)
				{
					// todo: possibility (in editFields) to exclude fields from the form.

					// fieldname: field->field_name
					// label = ucfirst(fieldname) todo: add labels to editfields if you want other names

					// Add a FIELD to the fieldset
					$formField = $form->createElement('field');

					// Name
					$name = new \DOMAttr('name', $field->field_name);
					$formField->setAttributeNode($name);

					// Type

					//    - Property
					if (($field->field_type) == "property")
					{
						// By default use a standard HtmlType.
						$property = $field->property;
						$type     = $this->standard2HtmlTypes($property->type);

						// Is this field in the editFields?
						foreach ($page->editfields as $editfield)
						{
							// The current field is in the editfields
							if (($editfield->attribute->field_reference) == $field->field_id)
							{
								// If in editfields, then use the HtmlType defined there.
								$type = $editfield->htmltype;

								// Process parameters of editfield, if not empty
								if (!empty($editfield->parameters))
								{
									switch ($type)
									{
										case 'list':
										case 'checkboxes':
										case 'radio':

											// Default empty choice as first option
											$option = $form->createElement('option');
											$key    = new \DOMAttr('value', 0);
											$option->setAttributeNode($key);
											$option->textContent = '&nbsp;';
											$formField->appendChild($option);

											// Add options to the list-field
											foreach ($editfield->parameters as $parameter)
											{
												$option = $form->createElement('option');
												$key    = new \DOMAttr('value', $parameter->key);
												$option->setAttributeNode($key);
												$option->textContent = $parameter->value;
												$formField->appendChild($option);
											}
											break;
										default:
											// Add the parameters as attributes to the formField
											foreach ($editfield->parameters as $parameter)
											{
												$key_value = new \DOMAttr($parameter->key, $parameter->value);
												$formField->setAttributeNode($key_value);
											}


									}
								}
							}
						}

						// HtmlField-type: $this->standard2HtmlTypes($property->type);
						$type = new \DOMAttr('type', $type);
						$formField->setAttributeNode($type);

						// DateTime: add time to date.  format = "%Y-%m-%d %H:%M"
						// todo: if specified in editfields, then use that, not this!
						$type = new \DOMAttr('format', '%Y-%m-%d %H:%M');
						$formField->setAttributeNode($type);
					}

					//    - Reference
					if (($field->field_type) == "reference")
					{
						$reference = $field->reference;

						$refEntity_id = $reference->reference_id;
						$refEntity    = $entityMap[$refEntity_id];

						// In case of an embeddable: refer to a subform
						if (property_exists($refEntity, 'isvalueobject'))
						{

							// Type
							$type = new \DOMAttr('type', 'subform');
							$formField->setAttributeNode($type);

							// FormSource
							$formsource = new \DOMAttr(
								'formsource',
								'administrator/components/com_'.strtolower($componentName) .
								'/forms/' . strtolower($refEntity->entity_name) . '.xml'
							);
							$formField->setAttributeNode($formsource);

							// Attributes
							// Is this field in the editFields?
							foreach ($page->editfields as $editfield)
							{
								// The current field is in the editfields
								if (($editfield->attribute->field_reference) == $field->field_id)
								{
									// If in editfields, then use the HtmlType defined there.
									// $type = $editfield->htmltype; // can now only be subform

									// Add parameters of editfield, if not empty
									if (!empty($editfield->parameters))
									{
										// Add the parameters as attributes to the formField
										foreach ($editfield->parameters as $parameter)
										{
											$key_value = new \DOMAttr($parameter->key, $parameter->value);
											$formField->setAttributeNode($key_value);
										}
									}
								}
							}
						}
						else
						{
							// (n:1)
							// Find the default field to display this reference
							$refDisplayFieldName = '';
							foreach ($refEntity->field as $foreignField)
							{
								if ((($foreignField->field_type) == "property") && property_exists($foreignField->property, 'default_ref_display'))
								{
									$refDisplayFieldName = $foreignField->field_name;
									break;
								}
							}

							// display the id if no display-field available
							if (empty($refDisplayFieldName))
							{
								$refDisplayFieldName = 'id';
							}

							// Make the custom sql to get the values for the dropdown-list todo $db->quoteName i.s.o. directly backticks
							$table = '#__' . strtolower($componentName) . "_" . strtolower($refEntity->entity_name);
							$query = "SELECT id, `" . $refDisplayFieldName . "` FROM `" . $table . "`";

							// Type
							$type = new \DOMAttr('type', 'sql');
							$formField->setAttributeNode($type);

							// Query
							$query = new \DOMAttr('query', $query);
							$formField->setAttributeNode($query);

							// Empty choice on top of options
							$header = new \DOMAttr('header', '&nbsp;');
							$formField->setAttributeNode($header);

							// Key-field
							$keyField = new \DOMAttr('key_field', 'id');
							$formField->setAttributeNode($keyField);

							// Value-field
							$valueField = new \DOMAttr('value_field', $refDisplayFieldName);
							$formField->setAttributeNode($valueField);
						}
					}

					// Label
					$label = new \DOMAttr('label', $field->field_name);
					$formField->setAttributeNode($label);
					$fieldset->appendChild($formField);
				}

				if (($page->page_type)!="subform")
				{
					// HIDDEN field: id
					$formField = $form->createElement('field');

					// Name = id
					$name = new \DOMAttr('name', 'id');
					$formField->setAttributeNode($name);

					// Type = hidden
					$type = new \DOMAttr('type', 'hidden');
					$formField->setAttributeNode($type);

					$fieldset->appendChild($formField);
				}

				// Write to file
				$form->save($formDirectory . $formName . '.xml');
				$logAppend([$formName . '.xml generated']);

			}


			// Generate the index page FILTER-forms
			if (($page->page_type)=="indexpage")
			{
				// Do we have any filters on this page?
				if (!empty($page->filters))
				{
					// Start the form-creation
					$form = new DOMDocument();
					$form->encoding = 'utf-8';
					$form->xmlVersion = '1.0';
					$form->formatOutput = true;
					$root = $form->createElement('form');
					$form->appendChild($root);

					// Add a fields-tag
					$fields = $form->createElement('fields');

					// With name="filter"
					$fieldsname = new \DOMAttr('name', "filter");
					$fields->setAttributeNode($fieldsname);

					$root->appendChild($fields);

					// Add search-field to the fields-tag
					/*<field
						name="search"
						type="text"
						inputmode="search"
						label="COM_FOOS_FILTER_SEARCH_LABEL"
						description="COM_FOOS_FILTER_SEARCH_DESC"
						hint="JSEARCH_FILTER"
					/>*/
					$searchField = $form->createElement('field');
					// Name
					$name = new \DOMAttr('name', 'search');
					$searchField->setAttributeNode($name);
					// Type
					$type = new \DOMAttr('type', 'text');
					$searchField->setAttributeNode($type);
					// Inputmode
					$inputmode = new \DOMAttr('inputmode', 'search');
					$searchField->setAttributeNode($inputmode);
					// Label
					$label = new \DOMAttr('label',
						$addLanguageString(
							$componentName, $formName, '', "pageName_FIELD_SEARCH_LABEL", 'Search'));
					$searchField->setAttributeNode($label);
					// Description
					//todo: longer description how to use search-string; see com_content)
					$description = new \DOMAttr('description',
						$addLanguageString(
							$componentName, $formName, '', "pageName_FIELD_SEARCH_DESC", 'Search'));
					$searchField->setAttributeNode($description);
					// Hint
					$hint = new \DOMAttr('hint', 'JSEARCH_FILTER');
					$searchField->setAttributeNode($hint);

					$fields->appendChild($searchField);

					// todo: general joomla filter-possibilities like language, categories, tags, etc.

					// Add fields to the fields-tag: loop over the filters for this page
					foreach ($page->filters as $filter)
					{
						$entity_id = $filter->entity_reference;
						$entity = $entityMap[$entity_id];

						$field_id = $filter->field_reference;
						$field = $fieldMap[$field_id];


						// Add a FIELD to the fieldset
						$formField = $form->createElement('field');

						// Name
						$name = new \DOMAttr('name', $field->field_name);
						$formField->setAttributeNode($name);

						// Type
						// todo: Joomla specific filters like categories, tags, etc.
						// A filter on a field (not being for instance a Joomla category etc.) is momentarily implemented as an sql-field
						// Maybe it would better be implemented as a custom-made field type. Then name it: filter<FormName><FieldName>
						// $filterFieldName = 'filter' . ucfirst($formName) . ucfirst($field->field_name);

						$type = new \DOMAttr('type', 'sql');
						$formField->setAttributeNode($type);

						// Make the query todo: when another value is stored in the db, we need another value for the selected text
						//    - Property: query the table of this entity
						if (($field->field_type)=="property")
						{
							// Make the custom sql to get the values for the dropdown-list todo $db->quoteName i.s.o. directly backticks
							$table = '#__' . strtolower($componentName) . "_" . strtolower($entity->entity_name);
							$query="SELECT DISTINCT `" . $field->field_name . "` AS value, `" . $field->field_name . "` AS text FROM `" . $table . "`";
							$refDisplayFieldName = $field->field_name;

						}

						//    - Reference (n:1): query the foreign table
						if (($field->field_type)=="reference")
						{
							$reference = $field->reference;

							$refEntity_id = $reference->reference_id;
							$refEntity = $entityMap[$refEntity_id];

							// Find the default field to display this reference
							$refDisplayFieldName = '';
							foreach ($refEntity->field as $foreignField)
							{
								if ((($foreignField->field_type)=="property") && property_exists($foreignField->property,'default_ref_display'))
								{
									$refDisplayFieldName = $foreignField->field_name;
									break;
								}
							}

							// display the id if no display-field available
							if (empty($refDisplayFieldName))
							{
								$refDisplayFieldName = 'id';
							}

							// Make the custom sql to get the values for the dropdown-list todo $db->quoteName i.s.o. directly backticks
							$table = '#__' . strtolower($componentName) . "_" . strtolower($refEntity->entity_name);
							$query="SELECT `id` AS value, `" . $refDisplayFieldName . "` AS text FROM `" . $table . "`";
						}

						$query = new \DOMAttr('query', $query);
						$formField->setAttributeNode($query);

						// Empty choice with field name on top of options
						$header = new \DOMAttr('header', $addLanguageString(
							$componentName, $formName, $field->field_name, "pageName_FILTER_FIELD_fieldName_HEADER", '- Select %fieldName% -'));
						$formField->setAttributeNode($header);

						// Submit on change
						$keyField = new \DOMAttr('onchange', 'this.form.submit();');
						$formField->setAttributeNode($keyField);

						// Key-field
						$keyField = new \DOMAttr('key_field', 'value');
						$formField->setAttributeNode($keyField);

						// Value-field
						$valueField = new \DOMAttr('value_field', 'text');
						$formField->setAttributeNode($valueField);

						// Label language-string: COM_componentname_formName_FIELD_fieldname_LABEL
						$label = new \DOMAttr('label',
							$addLanguageString(
								$componentName, $formName, $field->field_name, "pageName_FILTER_FIELD_fieldName_LABEL", 'Filter %fieldName%'));
						$formField->setAttributeNode($label);

						// Description language-string: COM_componentname_formName_FILTER_FIELD_fieldname_DESC
						$description = new \DOMAttr('description',
							$addLanguageString($componentName, $formName, $field->field_name, "pageName_FILTER_FIELD_fieldName_DESC", 'Filter on %fieldName%.'
								));
						$formField->setAttributeNode($description);

						$fields->appendChild($formField);
					}

					// Write to file
					$form->save($formDirectory . "filter_" . $formName . '.xml');
					$logAppend(["filter_" . $formName . '.xml generated']);
				}
			}
		}


		// Open the sql-file for writing
		//$sqlfile = fopen( $formDirectory . 'install.mysql.utf8', "w") or die("Unable to open file!");
		//$logAppend(['generated install.mysql.utf8 sql-file']);

		// make a map of entity_id to name
		/*$entityNameMap = [];
		foreach ($projectForm->datamodel as $entity)
		{
			$entityNameMap[$entity->entity_id] = ucfirst($entity->entity_name);
		}*/

		// Loop over the detail pages







/*


		// Loop over the entities to create the tables in the sql-file and the Table-files for Joomla
		$sqlCreateTable = [];
		foreach ($projectForm->datamodel as $entity)
		{
			$entityName = ucfirst($entity->entity_name);
			$templateVariables['entityName'] = $entityName;

			// --- CREATE TABLE sql statement for this entity and write to sql-file ---
			// N.B.: I now name the table singular. It might be nicer to do it in plural (but inflector only works for English names)
			// Maybe stick to English names for the entities. But for now: use entityName for the tableName
			$tableName = '#__' . strtolower($componentName) . "_" . strtolower($entityName);
			$tableRows = [];
			$tableRows[] = "CREATE TABLE IF NOT EXISTS `$tableName` (\n"
				. "`id` bigint UNSIGNED NOT NULL AUTO_INCREMENT";// default all tables have an auto increment id
			foreach ($entity->property as $property)
			{
				$tableRows[]= '`' . $property->property_name . '` ' . $this->standard2SqlTypes($property->type);
			}
			// Add reference(s)
			foreach ($entity->reference as $reference)
			{
				// todo only when this class owns the reference, so many2one, but not one2many
				$tableRows[] = '`' . strtolower($entityNameMap[$reference->reference]) . '_id` ' . "bigint(20) UNSIGNED";
			}
			$tableRows[]= ")";

			// Generate the Create Table sql
			$sqlCreateTable[] = implode(",\n", $tableRows);
			$logAppend(['generated CREATE TABLE sql statement for ' . $tableName . ' in sql-file']);

			// --- create Table file for this entity ---
			$templateFileName = 'Table.php';
			$generatedFileName = $entityName . 'Table.php';

			$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedTablesPath, $generatedFileName, $templateVariables));
			// also add a method to retrieve entity with FK in the table
			// todo: this must be a "stub" that will be a variable in the template;
			// todo: also for many2one relations... (choose: eager or lazy loading)
		}

		fwrite($sqlfile, implode("\n", $sqlCreateTable));
		// Close the sql-file
		fclose($sqlfile);*/

		return $log;
	}

	/**
	 * Convert the standard data type from the model to default HTML input type.
	 * Todo: attributes for those types...
	 * Todo: use more standard Joomla fields
	 *
	 * All standard Joomla form field types:
	 *     - accessiblemedia: provides modal access to the media manager for insertion of images with upload for users with appropriate permissions and a text field for adding a alternative text.
	 *     - accesslevel: provides a drop down list of viewing access levels.
	 *     - cachehandler: provides a list of available cache handling options.
	 *     - calendar: provides a text box for entry of a date. An icon next to the text box provides a link to a pop-up calendar, which can also be used to enter the date value.
	 *     - captcha: provides the use of a captcha plugin.
	 *     - category: provides a drop down list of categories for an extension.
	 *     - checkbox: provides a single checkbox to be checked or unchecked
	 *     - checkboxes: provides unlimited checkboxes that can be used for multi-select.
	 *     - Chrome Style: provides a list of template chrome style options grouped by template.
	 *     - color: provides a color picker when clicking the input box.
	 *     - Content Language: Provides a list of content languages.
	 *     - Content Type: Provides a list of content types.
	 *     - combo: provides a combo box field.
	 *     - componentlayout: provides a grouped list of core and template alternative layouts for a component item.
	 *     - contentlanguage: provides a list of installed content languages for use in conjunction with the language switcher plugin.
	 *     - Database Connection: Provides a list of available database connections, optionally limiting to a given list.
	 *     - editor: provides an editor area field.
	 *     - editors: Provides a drop down list of the available WYSIWYG editors. Since Joomla 2.5 use plugins form field instead.
	 *     - email: provides an email field.
	 *     - file:Provides an input field for files
	 *     - filelist: provides a drop down list of files from a specified directory.
	 *     - folderlist: provides a drop down list of folders from a specified directory.
	 *     - groupedlist: provides a drop down list of items organized into groups.
	 *     - header tag:provides a drop down list of the header tags (h1-h6).
	 *     - helpsite: provides a drop down list of the help sites for your Joomla installation.
	 *     - hidden: provides a hidden field for saving a form field whose value cannot be altered directly by a user in the Administrator (it can be altered in code or by editing the params.ini file).
	 *     - imagelist: provides a drop down list of image files in a specified directory.
	 *     - integer: provides a drop down list of integers between a minimum and maximum.
	 *     - language: provides a drop down list of the installed languages for the Front-end or Back-end.
	 *     - list: provides a drop down list of custom-defined entries.
	 *     - media: provides modal access to the media manager for insertion of images with upload for users with appropriate permissions.
	 *     - menu: provides a drop down list of the available menus from your Joomla site.
	 *     - Menu Item: provides a drop down list of the available menu items from your Joomla site.
	 *     - meter: Provides a meter to show value in a range.
	 *     - Module Layout: provides a list of alternative layout for a module grouped by core and template.
	 *     - Module Order: Provides a drop down to set the ordering of module in a given position
	 *     - Module Position: provides a text input to set the position of a module.
	 *     - Module Tag: provides a list of html5 elements (used to wrap a module in).
	 *     - note: supports a one line text field.
	 *     - number: Provides a one line text box with up-down handles to set a number in the field.
	 *     - password: provides a text box for entry of a password.  The password characters will be obscured as they are entered.
	 *     - plugins: provides a list of plugins from a given folder.
	 *     - predefinedlist: Form Field to load a list of predefined values.
	 *     - radio: provides radio buttons to select different options.
	 *     - range: Provides a horizontal scroll bar to specify a value in a range.
	 *     - repeatable: Allows form fields which can have as many options as the user desires.
	 *     - rules: provides a matrix of group by action options for managing access control. Display depends on context.
	 *     - sessionhandler: provides a drop down list of session handler options.
	 *     - spacer: provides a visual separator between form fields.  It is purely a visual aid and no value is stored.
	 *     - sql: provides a drop down list of entries obtained by running a query on the Joomla Database.  The first results column returned by the query provides the values for the drop down box.
	 *     - subform: provides a way to use XML forms inside each other or reuse your existing forms inside your current form.
	 *     - tag: provides an entry point for tags (either AJAX or Nested).
	 *     - tel: provides an input field for a telephone number.
	 *     - templatestyle: provides a drop down list of template styles.
	 *     - text: provides a text box for data entry.
	 *     - textarea: provides a text area for entry of multi-line text.
	 *     - timezone: provides a drop down list of time zones.
	 *     - URL: provides a URL text input field.
	 *     - user: Field to select a user from a modal list. Displays User Name and stores User ID
	 *     - useractive: Field to show a list of available user active statuses.
	 *     - usergroup: provides a drop down list of user groups. Since Joomla 3.2 use usergrouplist instead.
	 *     - usergrouplist: Field to load a drop down list of available user groups. Replaces usergroup form field type.
	 *     - userstate: Field to load a list of available users statuses.
	 *
	 *
	 *     - yes_no_buttons and show_hide_buttons are fields of type list with 2 options (JNO/JYES and JHIDE/JSHOW
	 *     - Todo: custom form fields for decimals, currency, floating point values etc.
	 *
	 *
	 * @param $standardType
	 *
	 * @return string the Html type
	 *
	 */
	private function standard2HtmlTypes($standardType)
	{
		switch ($standardType)
		{
			case ('Integer'):
				$htmlDef = "number";
				break;
			case ('Boolean'):
				$htmlDef = "yes_no_buttons"; // but can also be other options, like show/hide
				break;
			case ('Text'):
				$htmlDef = "textarea";
				break;
			case ('Short_Text'):
				$htmlDef = "text";
				break;
			case ('Time'):
				$htmlDef = "calendar";
				break;
			case ('Date'):
				$htmlDef = "calendar";
				break;
			case ('DateTime'):
				$htmlDef = "calendar";
				break;
			case ('File'):
				$htmlDef = "file"; // or filelist
				break;
			case ('Link'):
				$htmlDef = "URL";
				break;
			case ('Image'):
				$htmlDef = "imagelist";
				break;
			default:
				$htmlDef = "text";
		}

		return $htmlDef;

	}
}