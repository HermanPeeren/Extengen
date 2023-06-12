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
 * Generate Model
 */
class GenerateModel extends AdminModel
{
	/**
	 * A log of all files that were created with the various generators
	 *
	 * @var array
	 */
	public array $log = [];

	/**
	 * The (internal) id of the project for which we generate files
	 *
	 * @var   int|Integer
	 */
	protected int $projectId;

	/**
	 * The type of output we generate files for, for instance "Joomla4".
	 * There must be a subdirectory with this name with the concrete generators.
	 * When templates are used, they must be in a subdirectory under /generator_templates with that same name. todo: templates in db
	 *
	 * @var   string
	 */
	protected array $outputTypes = ['Joomla4']; //todo: set other output types

	/**
	 * Set the project id.
	 *
	 * @param   int  $projectId
	 */
	public function setProjectId(int $projectId): void
	{
		$this->projectId = $projectId;
	}

	/**
	 * Generate the files using various generators.
	 * This is the central place from where all concrete generators are called.
	 *
	 */
	public function generate()
	{
		// Initialise variables
		$outputType = "Joomla4"; // todo: get the outputType from the generator... multiple generators...
		$AST = $this->initiateAST();
		$languageStringUtil = new LanguageStringUtil($AST);

		// Component name
		$component = $AST->extensions->component;
		$componentName = $component->component_name;

		// file paths for generated (language)files
		$extengenAdminPath = JPATH_ROOT . '/administrator/components/com_extengen/';
		$generatedFilesPathComponent = $extengenAdminPath . '/generated/' . $componentName .'/'
			. $outputType . '/com_'.strtolower($componentName) . '/';

		foreach ($this->outputTypes as $outputType)
		{
			$generatorNamespace = 'Yepr\\Component\\Extengen\\Administrator\\Model\\Generator\\' . $outputType . '\\';

			// --- Component ---
			$this->log[] = "<b>=== COMPONENT GENERAL ===</b>";
			$this->useConcreteGenerator($generatorNamespace . "ComponentGeneral", $outputType, $AST, $languageStringUtil);

			// --- Backend ---
			$this->log[] = "<b>=== BACK-END ===</b>";
			// Call the various generators
			$this->useConcreteGenerator($generatorNamespace . "AdminGeneral", $outputType, $AST, $languageStringUtil);
			$this->useConcreteGenerator($generatorNamespace . "AdminEntities", $outputType, $AST, $languageStringUtil);
			$this->useConcreteGenerator($generatorNamespace . "AdminMVC", $outputType, $AST, $languageStringUtil);
			$this->useConcreteGenerator($generatorNamespace . "Forms", $outputType, $AST, $languageStringUtil);
			// ...more generators here
		}

		// Generate the language files; assume for now there is only a component
		$this->log[] = "&nbsp;";
		$this->log[] = "<b>=== LANGUAGE FILES ===</b>";

		$languageTree = $languageStringUtil->getLangTree();
		$baseGeneratedFilePath = 'administrator/components/com_'.strtolower($componentName).'/';
		foreach ($languageTree as $section_name => $section)
		{
			switch ($section_name)
			{
				case 'backend':
				case 'sys':
					$generatedFilePath = 'administrator/components/com_'.strtolower($componentName) .'/language/';
					break;
				case 'frontend':
					$generatedFilePath = 'components/com_'.strtolower($componentName) .'/language/';
					break;
			}
			foreach ($section->languages as $language)
			{
				$languageFolderName = $language->language_code . '-' . $language->country_code;

				// Create the directory for the generated files if it doesn't exist
				$generatedDirectory = $generatedFilesPathComponent . $generatedFilePath . $languageFolderName;
				if (!file_exists($generatedDirectory)) {
					mkdir($generatedDirectory, 0755, true);
				}

				// Create the content of the language file
				$languageContent = [];
				foreach ($language->key_value_pairs as $keyValuePair)
				{
					$languageContent[] = $keyValuePair->language_string . '="' . $keyValuePair->locale_string . '"';
				}

				// Sort language strings alphabetically
				sort($languageContent);

				// todo: Add a heading to language string files with project, copyright, license and version

				// File name
				$sys = "";
				if ($section_name == 'sys')
				{
					$sys = ".sys";
				}
				$generatedFileName ='com_' . strtolower($componentName) . $sys . '.ini';

				// Write the file
				$languageFile = fopen( $generatedDirectory . "/" . $generatedFileName, "w") or die("Unable to open file!");
				fwrite($languageFile, implode("\n",$languageContent));
				fclose($languageFile);
				$this->log[] = $generatedFilePath . $languageFolderName . '/' . $generatedFileName . ' generated';
			}
		}
	}

	/**
	 * Use a specific concrete generator to generate files and add the result to the log.
	 *
	 * @param   string              $generatorFQN         The Full Qualified Name of the concrete Generator
	 * @param   string              $outputType           The type of output we generate files for, for instance "Joomla4"
	 * @param   object              $AST                  The Abstract Syntax Tree (= all properties of the project)
	 * @param   LanguageStringUtil  $languageStringUtil   A Twig extension with language string utilities
	 */
	private function useConcreteGenerator(string $generatorFQN, string $outputType, object $AST, LanguageStringUtil $languageStringUtil)
	{
		$generator = new $generatorFQN($outputType, $AST, $languageStringUtil);
		$this->log = array_merge($this->log, $generator->generate());
	}

	/**
	 * Get the (json-encoded) form-data of the project that form the AST.
	 *
	 * @return object
	 */
	private function initiateAST(): object
	{
		$db = $this->getDatabase();
		$getASTquery = $db->getQuery(true)
			->select($db->quoteName('form_data'))
			->from($db->quoteName('#__extengen_projects'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $this->projectId, ParameterType::INTEGER);
		$db->setQuery($getASTquery);

		return json_decode($db->loadResult());
	}

	/**
	 * NOT IN USE NOW (instead: directly query via initiateAST()).
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
	 * NOT USED ATM. BUT MUST BE IMPLEMENTED. MIGHT USE IN FUTURE TO CHOOSE GENERATORS.
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
