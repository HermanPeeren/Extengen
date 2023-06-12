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
 * A concrete generator to create the entity-related files of a J4-component administrator-side
 * generated files: sql/install.mysql.utf8 (SQL to create the db-tables) and the Table files
 *
 * @package     Yepr\Component\Extengen\Administrator\Model\Generator\Joomla4
 */
class AdminEntities extends Generator
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
		$componentName = ucfirst($this->componentName);

		// What kind of output do you want to generate? For instance: 'Joomla4'
		$outputType = $this->outputType;

		// Path to administrator-side of com_extengen
		$extengenAdminPath = $this->extengenAdminPath;

		// Path to generated files of component
		$generatedFilesPathComponent = $extengenAdminPath . '/generated/' . $componentName .'/'
			. $outputType . '/com_'.strtolower($componentName) . '/';

		// Path of generated file IN the directory for generated files of component
		$generatedFilePath = 'administrator/components/com_'.strtolower($componentName).'/';

		// Create the install.mysql.utf8 sql file and open for writing
		$sqlDirectory = $generatedFilesPathComponent . $generatedFilePath . 'sql/';

		// Create the directory for the sql files if it doen't exist
		if (!file_exists($sqlDirectory)) {
			mkdir($sqlDirectory, 0755, true);
		}

		// Open the sql-install-file for writing
		$sqlfile = fopen( $sqlDirectory . 'install.mysql.utf8.sql', "w") or die("Unable to open file!");
		$logAppend(['generated install.mysql.utf8.sql sql-file']);

		// Open the sql-UNinstall-file for writing
		$sqlUNfile = fopen( $sqlDirectory . 'uninstall.mysql.utf8.sql', "w") or die("Unable to open file!");
		$logAppend(['generated uninstall.mysql.utf8.sql sql-file']);

		// Table class template within the Joomla4 templates
		$templateFilePath = 'component/administrator/components/com_componentname/src/Table/';

		// Path to generated Table-files in component
		$generatedTablesPath = $generatedFilePath . 'src/Table/';

		// General template variables
		$templateVariables = ['componentName' => $componentName];
		$templateVariables['projectName'] = ucfirst($project->name);

		$manifest = $project->extensions->component->manifest;
		$templateVariables['copyright'] = $manifest->copyright;
		$templateVariables['license'] = $manifest->license;
		$templateVariables['companyNamepace'] = $manifest->company_namespace;

		// Loop over the entities to make a map of entity_id to name
		$entityNameMap = [];
		foreach ($project->datamodel as $entity)
		{
			$entityNameMap[$entity->entity_id] = ucfirst($entity->entity_name);
		}

		// Loop over the entities to create the tables in the sql-file and the Table-files for Joomla
		$sqlCreateTable = [];
		$sqlDropTable = [];
		foreach ($project->datamodel as $entity)
		{
			$entityName = ucfirst($entity->entity_name);
			$templateVariables['entityName'] = $entityName;

			// --- CREATE TABLE sql statement for this entity and write to sql-file ---
			// N.B.: I now name the table singular. It might be nicer to do it in plural (but inflector only works for English names)
			// Maybe stick to English names for the entities. But for now: use entityName for the tableName
			$tableName = '#__' . strtolower($componentName) . "_" . strtolower($entityName);
			$tableRows = [];
			$tableRows[] = "CREATE TABLE IF NOT EXISTS `$tableName` (";

			// Add a Drop Table to the uninstall sql file
			$sqlDropTable[] = "DROP TABLE IF EXISTS `$tableName`;";

			$attributeRows = [];
			// By default all tables have an auto increment id.
			$attributeRows[] = "`id` bigint UNSIGNED NOT NULL AUTO_INCREMENT";

			// Add fields
			foreach ($entity->field as $field)
			{
				switch($field->field_type)
				{
					case "property":
						$attributeRows[]= '`'
							. $field->field_name . '` '
							. $this->standard2SqlTypes($field->property->type);
						break;

					case "reference":
						// For references: add the foreign key
						$attributeRows[]= '`'
							. strtolower($entityNameMap[$field->reference->reference]) . '_id` '
							. "bigint(20) UNSIGNED";
						break;
				}
			}

			// The id also is the primary key.
			$attributeRows[] = "PRIMARY KEY (`id`)";

			// Add the attributes to the table
			$tableRows[] =  implode(",\n", $attributeRows);
			$tableRows[] = ")  ENGINE=InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;";

			// Generate the Create Table sql
			$sqlCreateTable[] = implode("\n", $tableRows);
			$logAppend(['generated CREATE TABLE sql statement for ' . $tableName . ' in sql-file']);

			// --- create Table file for this entity ---
			$templateFileName = 'Table.php.twig';
			$generatedFileName = $entityName . 'Table.php';

			$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedTablesPath, $generatedFileName, $templateVariables));
			// also add a method to retrieve entity with FK in the table
			// todo: this must be a "stub" that will be a variable in the template;
			// todo: also for many2one relations... (choose: eager or lazy loading)
		}

		// Write the sql install file
		fwrite($sqlfile, implode("\n\n", $sqlCreateTable));
		// Close the sql-file
		fclose($sqlfile);

		// Write the sql UNinstall file
		fwrite($sqlUNfile, implode("\n", $sqlDropTable));
		// Close the sql-uninstall-file
		fclose($sqlUNfile);

		return $log;
	}

	/**
	 * Convert the standard data type from the model to the proper MySql data type.
	 * todo: other types, like JSON. And: attributes & defaults
	 * N.B.: Bool and Boolean are not native MySql types: https://dev.mysql.com/doc/refman/8.0/en/other-vendor-data-types.html
	 *
	 * All MySql types:
	 *      Numeric Data Types
	 *          - Integer Types (Exact Value) - INTEGER, INT, SMALLINT, TINYINT, MEDIUMINT, BIGINT
	 *          - Fixed-Point Types (Exact Value) - DECIMAL, NUMERIC
	 *          - Floating-Point Types (Approximate Value) - FLOAT, DOUBLE
	 *          - Bit-Value Type - BIT
	 *      Date and Time Data Types
	 *          - The DATE, DATETIME, and TIMESTAMP Types
	 *          - The TIME Type
	 *          - The YEAR Type
	 *      String Data Types
	 *          - The CHAR and VARCHAR Types
	 *          - The BINARY and VARBINARY Types
	 *          - The BLOB and TEXT Types
	 *          - The ENUM Type
	 *          - The SET Type
	 *      Spatial Data Types
	 *          - single geometry values: GEOMETRY, POINT, LINESTRING, POLYGON
	 *          - collections of values: MULTIPOINT, MULTILINESTRING, MULTIPOLYGON, GEOMETRYCOLLECTION
	 *      The JSON Data Type
	 *
	 *
	 * @param $standardType
	 *
	 * @return string the sql type
	 */
	private function standard2SqlTypes($standardType)
	{
		switch ($standardType)
		{
			case ('Integer'):
				$sqlDef = "int NOT NULL DEFAULT 0";
				break;
			case ('Boolean'):
				$sqlDef = "tinyint unsigned NOT NULL DEFAULT 0";
				break;
			case ('Text'):
				$sqlDef = "text";
				break;
			case ('Decimal'):
				$sqlDef = "decimal(10,2)";
				break;
			case ('Currency'):
				$sqlDef = "decimal(10,2)";
				break;
			case ('Float'):
				$sqlDef = "float";
				break;
			case ('Short_Text'):
				$sqlDef = "varchar(255)";
				break;
			case ('Time'):
				$sqlDef = "time";
				break;
			case ('Date'):
				$sqlDef = "date";
				break;
			case ('DateTime'):
				$sqlDef = "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'";
				break;
			case ('File'):
				$sqlDef = "varchar(255)";
				break;
			case ('Link'):
				$sqlDef = "varchar(255)";
				break;
			case ('Image'):
				$sqlDef = "varchar(255)";
				break;
			default:
				$sqlDef = "text";
		}

		return $sqlDef;

	}
}