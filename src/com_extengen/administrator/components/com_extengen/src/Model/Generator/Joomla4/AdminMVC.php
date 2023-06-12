<?php
/**
 * @package     Extension Generator
 * @subpackage  Joomla4 Generator
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\Model\Generator\Joomla4;

use Yepr\Component\Extengen\Administrator\Model\Generator\Generator;

/**
 * A concrete generator to create the admin MVC-files of a J4-component with templates.
 * Generated files: models views and controllers in the backend.
 *
 * @package     Extension Generator
 */
class AdminMVC extends Generator
{
	/**
	 * Generate the files. This method is called from the model.
	 *
	 * @return array the log of this concrete generator; logs which files were generated
	 */
	public function generate(): array
	{
		// Initialise variables
		$project = $this->AST;
		$log = [];
		$logAppend = function ($append) use(&$log) {$log = array_merge($log, $append);};

		// The name of the component (without 'com_' prefix and possibly with capitals)
		$componentName = $this->componentName;

		// What kind of output do you want to generate? For instance: 'Joomla4'
		$outputType = $this->outputType;

		$templateFilePathRoot = 'component/administrator/components/com_componentname/';
		$generatedFilePathRoot = 'administrator/components/com_'.strtolower($componentName).'/';

		$templateVariables = ['componentName' => $componentName];
		$manifest = $project->extensions->component->manifest;
		$templateVariables['version'] = $manifest->version;
		$templateVariables['copyright'] = $manifest->copyright;
		$templateVariables['license'] = $manifest->license;
		$templateVariables['company_namespace'] = $manifest->company_namespace;
		$templateVariables['projectName'] = $project->name;

		// Loop over the entities to make a map of entity_id to entity
		// Per entity: loop over the fields to make a map of field_id to field
		// todo: make this more general to use it when building generators in Extengen
		$entityMap = [];
		$fieldMap  = [];
		foreach ($project->datamodel as $entity)
		{
			$entityMap[$entity->entity_id] = $entity;
			foreach ($entity->field as $field)
			{
				$fieldMap[$field->field_id] = $field;
			}
		}

		// Loop over the pages to make a map of page_id to page-definition
		$pageMap = [];
		foreach ($project->pages as $page)
		{
			$pageMap[$page->page_id] = $page;
		}

		// In a component we can have a Frontend- and a Backend-section. Each with page-references.
		// Here we only look at Backend-section

		// DefaultView = first index page in backend. Todo: add a defaultview to the project/model
		$templateVariables['defaultView'] = "";

		// Each page-reference will be used to generate a Model, a View and a Controller
		// A page is either an index-page or a details-page. todo: other page-types
		$MVCtypes = ['Controller', 'Model', 'View'];

		// loop over the page-references for the backend
		foreach ($project->extensions->component->Sections->backendsection as $ref)
		{
			// some properties of the page
			$page = $pageMap[$ref->page_reference];
			$pageName = ucfirst($page->page_name);
			$pageType = (($page->page_type)=='indexpage')?'Index':'Details';// todo: switch, for there will be more page types

			// DefaultView = first index page in backend. Todo: add a defaultview to the project/model
			if (empty($templateVariables['defaultView']) && ($pageType=='Index'))
			{
				$templateVariables['defaultView'] = $pageName; // to be used in DisplayController
			}

			$templateVariables['pageName'] = $pageName;

			// Get links. Now only used in index view and tmpl to link to the detailspage
			// Todo: put in detailsControler to be able to redirect to its indexpage; also see Nic's ReturnURLAware-mixin
			// Todo: multiple links possible. Now only one (on field with default_ref_display) and here we give the detailspage
			// Todo: what if links is (still) empty? ==> check if links not empty! Otherwise: get another linkPageName: current pageName;
			$linkPageRef = $page->links->links0->target_page->page_reference;
			$linkPage    = $pageMap[$linkPageRef];
			$linkPageName = ucfirst($linkPage->page_name);
			$templateVariables['linkPageName'] = $linkPageName;

			// Main entity on this page
			// todo: get editFields/presentationFields from page to get the exact fields you want to use on this page
			// todo: this only uses 1 entity.... what if multiple references??? How is this $entity used?
			//N.B.: index- and detailspages should have at least 1 entity! But now possibly not => make it null
			$entityRef = property_exists($page,'entity_ref')?$page->entity_ref->entity_ref0->reference:null;
			$entity = $entityRef?$entityMap[$entityRef]:null;

			// deep casted to array, because Twig cann't loop over object attributes
			$templateVariables['entity'] = json_decode(json_encode($entity), true);

			$entityName = $entity?$entity->entity_name:"";
			$templateVariables['entityName'] = $entityName;

			// todo: editFields for the detailspage and representationcolumns for the indexpage: they can overwrite the default fields from the entity.

			// Per page-reference: make a Model, View and Controller
			foreach ($MVCtypes as $MVCtype)
			{
				// Create Model, View or Controller  // todo: reorganise for Views: in subfolder with different names...
				if ($MVCtype!='View')
				{
					// todo: add filters to templateVariables when making an index model

					$templateFileName = 'Admin' . $pageType . $MVCtype . '.php.twig';
					$generatedFileName = $pageName . $MVCtype . '.php';
					$templateFilePath = $templateFilePathRoot . 'src/' . $MVCtype . '/';
					$generatedFilePath = $generatedFilePathRoot  . 'src/' . $MVCtype . '/';
				}

				if ($MVCtype=='View')
				{
					// todo: filters for index view

					$templateFileName = 'HtmlView.php.twig';
					$generatedFileName = 'HtmlView.php';
					$templateFilePath = $templateFilePathRoot . 'src/View/'. $pageType . '/';
					$generatedFilePath = $generatedFilePathRoot  . 'src/View/'. $pageName . '/';
				}

				// And generate the MVC-file
				$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedFilePath, $generatedFileName, $templateVariables));
			}

			// Create tmpl-file for the Index-View
			if ($pageType=='Index')
			{
				// Filters
				// Per filter make an associative array for the template: [fieldName, columnName]
				// Where
				//      * fieldName  = the local field name of the entity
				//      * columnName = the name of the column in the table, which can be  different from fieldName in case of a foreign key
				$filtersInTemplate = [];

				// Loop over the filters in the AST and make variables for the AdminIndexModel-template
				// Todo: make this more general for similar cases in generators, when building the generator in Extengen
				foreach ($page->filters as $filter)
				{
					$entity_id = $filter->entity_reference;
					$entity    = $entityMap[$entity_id];

					$field_id = $filter->field_reference;
					$field    = $fieldMap[$field_id];

					$fieldName = $field->field_name;

					// ColumnName depends on this being a property of this entity or a reference (foreign key)

					//    - Property: the fieldName and columnName are the same
					if (($field->field_type) == "property")
					{
						$filtersInTemplate[] = [
							'fieldName'  => $fieldName,
							'columnName' => $fieldName
						];

					}

					//    - Reference (n:1): the columnName is the foreign key
					if (($field->field_type) == "reference")
					{
						$reference = $field->reference;

						$refEntity_id = $reference->reference_id;
						$refEntity    = $entityMap[$refEntity_id];

						$columnName = strtolower($refEntity->entity_name) . '_id';

						$filtersInTemplate[] = [
							'fieldName'  => $fieldName,
							'columnName' => $columnName
						];
					}

					$templateVariables['filters'] = $filtersInTemplate;
				}

				$templateFileName = 'default.php.twig';
				$generatedFileName = 'default.php';
				$templateFilePath = $templateFilePathRoot . 'tmpl/index/';
				$generatedFilePath = $generatedFilePathRoot  . 'tmpl/' . strtolower($pageName) . '/';

				// And generate the file
				$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedFilePath, $generatedFileName, $templateVariables));
			}

			// Create tmpl-file for the Details-View
			if ($pageType=='Details')
			{
				$templateFileName = 'edit.php.twig';
				$generatedFileName = 'edit.php';
				$templateFilePath = $templateFilePathRoot . '/tmpl/details/';
				$generatedFilePath = $generatedFilePathRoot  . '/tmpl/' . strtolower($pageName) . '/';

				// And generate the file
				$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedFilePath, $generatedFileName, $templateVariables));
			}

		}

		// Add a DisplayController
		$templateFileName = 'DisplayController.php.twig';
		$generatedFileName = 'DisplayController.php';
		$templateFilePath = $templateFilePathRoot . 'src/Controller/';
		$generatedFilePath = $generatedFilePathRoot  . 'src/Controller/';

		// And generate the file
		$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedFilePath, $generatedFileName, $templateVariables));

		return $log;
	}
}