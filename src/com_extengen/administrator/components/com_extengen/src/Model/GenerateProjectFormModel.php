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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject; // TODO!!!
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Yepr\Component\Extengen\Administrator\Model\Generator\ProjectForms;

use	Yepr\Component\Extengen\Administrator\Model\LanguageStringUtil;


/**
 * Generate Form Model
 */
class GenerateProjectFormModel extends AdminModel
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
	protected int $projectFormId;

	/**
	 * Set the project form id.
	 *
	 * @param   int  $projectFormId
	 */
	public function setProjectFormId(int $projectFormId): void
	{
		$this->projectFormId = $projectFormId;
	}

	/**
	 * Generate the form-files using various generators.
	 * This is the central place from where all concrete generators are called.
	 *
	 */
	public function generate()
	{
		// Initialise variables
		$AST = $this->initiateAST();
		$languageStringUtil = new LanguageStringUtil($AST);

		// Project Form Name
		$projectFormName = $AST->name;

		// Find the root(s) in the AST. For now: assume exactly 1 root. Todo: multiple roots.
		// walk through the AST and find the root; take that node and the tree under it


		// file paths for generated (language)files
		$extengenAdminPath = JPATH_ROOT . '/administrator/components/com_extengen/';
		$generatedFormFilesPath = $extengenAdminPath . 'forms/ProjectForms/' . $projectFormName .'/';

		$generatorNamespace = 'Yepr\\Component\\Extengen\\Administrator\\Model\\Generator\\';
		$this->log[] = "<b>=== XML-FORMS FOR " . $projectFormName . " GENERATED ===</b>";
		$generator = new ProjectForms($projectFormName, $AST, $languageStringUtil);
		$this->log = array_merge($this->log, $generator->generate());

		// todo: ? do we generate files or are we - more dynamically-  adding them to the db? How about version control then?

		// Add strings to the language files. TODO: can we add those language stings more dynamically to the db?
		$this->log[] = "&nbsp;";

		//$this->log[] = "<b>=== LANGUAGE STRINGS OF THE FORMS ===</b>";
		// todo: handle language strings

		/*
		 * Languagestrings not in use for projectforms at the moment
	     * (because the generated language strings should have to be added to the existing ones of this component).
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
		}*/
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
			->from($db->quoteName('#__extengen_projectforms'))
			->where($db->quoteName('id') . ' = :id')
			->bind(':id', $this->projectFormId, ParameterType::INTEGER);
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
