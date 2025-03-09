<?php
/**
 * @package     Extension Generator

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\Model\Generator;

// Get Twig: use the Composer autoloader
// todo: use the DIC and add this Twig-service
// todo: inject in constructor!
require_once JPATH_LIBRARIES . '/yepr/vendor/autoload.php';

use Joomla\Database\DatabaseInterface;
use Joomla\CMS\MVC\View\GenericDataException;
use	Twig\Loader\FilesystemLoader;
use	Twig\Environment;
use Twig\Extension\ExtensionInterface;


/**
 * Abstract Generator Class: all concrete generators are extended from this.
 * A concrete  Generator has an AST as input and outputs (a part of) the files in the output type.
 * For instance: a Table generator (= concrete generator) for Joomla4 (= output type) generates the Table files
 * todo: make it more general than only for a component; also other extensions or non-Joomla output.
 *
 * @package     Yepr\Component\Extengen\Generator
 */
abstract class Generator
{
	/**
	 * The type of output we generate files for, for instance "Joomla4"
	 * There must be a subdirectory with this name with the concrete generators
	 * and when templates are used, they must be in a subdirectory under /generator_templates with that same name
	 *
	 * @var string
	 */
	protected string $outputType;

	/**
	 * The Abstract Syntax Tree (AST) = the form-data of the project as 1 object with hierarchical properties
	 *
	 * @var object
	 */
	protected object $AST;

	/**
	 * The Language String Utilities (a Twig extension) to make language strings and put them in the language files
	 *
	 * @var ExtensionInterface
	 */
	protected ExtensionInterface $languageStringUtil;

	/**
	 * The path to the administrator-side of com_extengen
	 *
	 * @var string
	 */
	protected string $extengenAdminPath;

	/**
	 * The cache for Twig
	 *
	 * @var string
	 */
	protected string $twigCache;

	/**
	 * The component name
	 *
	 * @var string
	 */
	protected string $componentName;

	/**
	 * Generator Class Constructor
	 *
	 * @param   string        $outputType The name of the type of output (e.g. "Joomla4"); must exist
	 * @param   object        $AST        The Abstract Syntax Tree (AST) = the form-data of the project
	 */
	public function __construct(string $outputType, object $AST, ExtensionInterface $languageStringUtil)
	{
		$this->outputType = $outputType;
		$this->AST = $AST;
		$this->languageStringUtil = $languageStringUtil;

		// Component name
		$component = $AST->extensions->component;
		$this->componentName = $component->component_name;

		// General paths to admin part of extengen-component and Twig-cache
		$this->extengenAdminPath = JPATH_ROOT . '/administrator/components/com_extengen/';
		$this->twigCache         = $this->extengenAdminPath . 'compilation_cache';
	}

	/**
	 * Method to be implemented in concrete generator. Implements the actual generation of files.
	 *
	 * @return array the log of the concrete generator; logs which files were generated
	 */
	abstract public function generate(): array;

	/**
	 * Generate a file with a template, using values from the AST.
	 * To be called from the concrete generator generate()-method when a template is used to create the file.
	 *
	 * @param   string  $templateFilePath   Path in the template-directory to the template file  (ending with /)
	 * @param   string  $templateFileName   Filename of the template
	 * @param   string  $generatedFilePath  Path in the generated-files-directory to the generated file (ending with /)
	 * @param   string  $generatedFileName  Filename of the generated file
	 * @param   array   $templateVariables  Array of variables to be used in template
	 *
	 * @return array $log a log of what file has been created
	 *
	 * @throws \Twig\Error\RuntimeError
	 * @throws \Twig\Error\SyntaxError
	 * @throws \Twig\Error\LoaderError
	 */
	protected function generateFileWithTemplate
	(
		string $templateFilePath,
		string $templateFileName,
		string $generatedFilePath,
		string $generatedFileName,
		array  $templateVariables = []
	) : array
	{
		// Initialise variables
		$log = [];

		// The name of the component (without 'com_' prefix and possibly with capitals)
		$componentName = $this->componentName;

		// What kind of output do you want to generate? For instance: 'Joomla4'
		$outputType = $this->outputType;

		// Path to administrator-side of com_extengen
		$extengenAdminPath = $this->extengenAdminPath;

		// Path to generated files of component todo: more general to package and to other modules or plugins...
		$generatedFilesPathComponent = $extengenAdminPath . '/generated/' . $componentName .'/'
			. $outputType . '/com_'.strtolower($componentName) . '/';

		// Render the template
		$generatedContent= $this->renderTemplateFragment($templateFilePath, $templateFileName, $templateVariables);

		// Create the directory for the generated file if it doesn't exist
		$generatedDirectory = $generatedFilesPathComponent . $generatedFilePath;
		if (!file_exists($generatedDirectory)) {
			mkdir($generatedDirectory, 0755, true);
		}

		// Write the file
		$myfile = fopen( $generatedDirectory .$generatedFileName, "w") or die("Unable to open file!");
		fwrite($myfile, $generatedContent);
		fclose($myfile);
		$log[] = $generatedFileName . ' generated';

		return $log;
	}

	/**
	 * Render template fragment, using template variables.
	 * To be called from the concrete generator generate()-method when a sub-template must be rendered.
	 * And is called from $this->generateFileWithTemplate()
	 *
	 * @param   string  $templateFilePath   Path in the template-directory to the template file  (ending with /)
	 * @param   string  $templateFileName   Filename of the template
	 *
	 * @return string The generated (sub-)template
	 *
	 * @throws \Twig\Error\RuntimeError
	 * @throws \Twig\Error\SyntaxError
	 * @throws \Twig\Error\LoaderError
	 */
	protected function renderTemplateFragment
	(
		string $templateFilePath,
		string $templateFileName,
		array  $templateVariables = []
	) : string
	{
		// What kind of output do you want to generate? For instance: 'Joomla4'
		$outputType = $this->outputType;

		// Path to administrator-side of com_extengen
		$extengenAdminPath = $this->extengenAdminPath;

		// Path to generator-templates of a specific output type
		$generatorTemplatePath = $extengenAdminPath . 'generator_templates/'. $outputType . '/';

		// Path to template
		$templatePath = $generatorTemplatePath . $templateFilePath;

		// Get Twig to render the template todo: do you have to instantiate a new twig or can you change the templatePath?
		$loader = new FilesystemLoader($templatePath);
		$twig = new Environment($loader, ['cache' => $this->twigCache]);

		// Add extension to Twig to render the language strings and put them in files
		$twig->addExtension($this->languageStringUtil);

		// Render the template
		$generatedContent= $twig->render($templateFileName, $templateVariables);

		return $generatedContent;
	}

}