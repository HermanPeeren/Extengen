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
 * A concrete generator to create the general component files of a J4-component with templates
 * generated files: manifest-file
 *
 * @package     Extension Generator
 */
class ComponentGeneral extends Generator
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
		$baseGeneratedFilePath = '';
		$generatedFilePath = $baseGeneratedFilePath;
		$templateVariables = ['componentName' => $componentName];

		// --- create component manifest file ---
		$templateFileName = 'componentManifest.xml';
		$generatedFileName = strtolower($componentName).'.xml';

		$templateVariables['author_name'] = $manifest->author_name;
		$templateVariables['author_email'] = $manifest->author_email;
		$templateVariables['author_url'] = $manifest->author_url;
		$templateVariables['copyright'] = $manifest->copyright;
		$templateVariables['license'] = $manifest->license;
		$templateVariables['company_namespace'] = $manifest->company_namespace;
		$templateVariables['projectName'] = $project->name;

		$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedFilePath, $generatedFileName, $templateVariables));

		return $log;
	}
}