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
 * A concrete generator to create the general admin files of a J4-component with templates
 * generated files: access.xml, config.xml, version.php, license file, manifest-file
 *
 * @package     Extension Generator
 */
class AdminGeneral extends Generator
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

		// The name of the component (without 'com_' prefix and capital first character)
		$componentName = ucfirst($this->componentName);
		
		$manifest = $project->extensions->component->manifest;

		// What kind of output do you want to generate? For instance: 'Joomla4'
		$outputType = $this->outputType;

		$baseTemplateFilePath = 'component/administrator/components/com_componentname/';
		$templateFilePath = $baseTemplateFilePath;
		$baseGeneratedFilePath = 'administrator/components/com_'.strtolower($componentName).'/';
		$generatedFilePath = $baseGeneratedFilePath;
		$templateVariables = ['componentName' => $componentName];

		// --- create access.xml ---
		$templateFileName = 'access.xml';
		$generatedFileName = 'access.xml';

		$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedFilePath, $generatedFileName, $templateVariables));

		// --- create config.xml ---
		$templateFileName = 'config.xml';
		$generatedFileName = 'config.xml';

		$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedFilePath, $generatedFileName, $templateVariables));

		/*// --- create version.php ---
		$templateFileName = 'version.php';
		$generatedFileName = 'version.php';

		$templateVariables['creation_date'] = $manifest->creation_date;

		$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedFilePath, $generatedFileName, $templateVariables));*/

		// todo: this should be in package top, not in admin side
		// --- create license text --- todo: choose between different licenses (from the AST) AND: put this in package top level, not in admin.
		$templateFileName = 'license_GPL3.txt';
		$generatedFileName = 'LICENSE.txt'; // todo: name license_GPL3.txt etc., but then also put that in manifest!

		$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedFilePath, $generatedFileName));

/*
		$templateVariables['author_name'] = $manifest->author_name;
		$templateVariables['author_email'] = $manifest->author_email;
		$templateVariables['author_url'] = $manifest->author_url;*/

		$templateVariables['version'] = $manifest->version;
		$templateVariables['projectName'] = $project->name;
		$templateVariables['company_namespace'] = $manifest->company_namespace;
		$templateVariables['copyright'] = $manifest->copyright;
		$templateVariables['license'] = $manifest->license;

		// --- create services/provider file of component ---
		$templateFilePath = $baseTemplateFilePath . 'services/';
		$templateFileName = 'provider.php.twig';
		$generatedFilePath = $baseGeneratedFilePath . 'services/';
		$generatedFileName = 'provider.php';

		$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedFilePath, $generatedFileName, $templateVariables));

		// --- create Extension file of component ---
		$templateFilePath = $baseTemplateFilePath . 'src/Extension/';
		$templateFileName = 'ComponentnameComponent.php.twig';
		$generatedFilePath = $baseGeneratedFilePath . 'src/Extension/';
		$generatedFileName = $componentName . 'Component.php';

		$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedFilePath, $generatedFileName, $templateVariables));

		// --- add some general language strings ---

		// back-end
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', '',
			$componentName);
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'CONFIGURATION',
			$templateVariables['projectName'] . ' options');
		/*
		    todo: items must be specified per type and not added in this general part of language strings
		    todo: only (un)published, (un)featured etc.  if that is an option for this type of item
			COM_FOOS_N_ITEMS_PUBLISHED="%d foos published."
			COM_FOOS_N_ITEMS_PUBLISHED_1="%d foo published."
			COM_FOOS_N_ITEMS_UNPUBLISHED="%d foos unpublished."
			COM_FOOS_N_ITEMS_UNPUBLISHED_1="%d foo unpublished."
			COM_FOOS_N_ITEMS_CHECKED_IN_1="%d foo checked in."
			COM_FOOS_N_ITEMS_CHECKED_IN_MORE="%d foos checked in."
			COM_FOOS_N_ITEMS_FEATURED="%d foos featured."
			COM_FOOS_N_ITEMS_FEATURED_1="Foo featured."
			COM_FOOS_N_ITEMS_UNFEATURED="%d foos unfeatured."
			COM_FOOS_N_ITEMS_UNFEATURED_1="Foo unfeatured."
			COM_FOOS_N_ITEMS_ARCHIVED="%d foos archived."
			COM_FOOS_N_ITEMS_ARCHIVED_1="%d foo archived."
			COM_FOOS_N_ITEMS_DELETED="%d foos deleted."
			COM_FOOS_N_ITEMS_DELETED_1="%d foo deleted."
			COM_FOOS_N_ITEMS_TRASHED="%d foos trashed."
			COM_FOOS_N_ITEMS_TRASHED_1="%d foo trashed."*/
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_PUBLISHED',
			'%d items published.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_PUBLISHED_1',
			'%d item published.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_UNPUBLISHED',
			'%d items unpublished.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_UNPUBLISHED_1',
			'%d item unpublished.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_CHECKED_IN_1',
			'%d item checked in.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_CHECKED_IN_MORE',
			'%d items checked in.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_FEATURED',
			'%d items featured.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_FEATURED_1',
			'%d item featured.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_UNFEATURED',
			'%d items unfeatured.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_UNFEATURED_1',
			'%d item unfeatured.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_ARCHIVED',
			'%d items archived.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_ARCHIVED_1',
			'%d item archived.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_DELETED',
			'%d items deleted.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_DELETED_1',
			'%d item deleted.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_TRASHED',
			'%d items trashed.');
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', 'N_ITEMS_TRASHED_1',
			'%d item trashed.');

		// sys
		$this->languageStringUtil->addLanguageStringFromView($componentName, '', '',
			$componentName, 'Administrator', true);

		// front-end
		/*$this->languageStringUtil->addLanguageStringFromView($componentName, '', '',
			$componentName,'Site');*/

		return $log;
	}
}