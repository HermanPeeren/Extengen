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

		// Loop over the entities to make a map of entity_id to name and from entity_id to entity
		$entityNameMap = [];
		foreach ($project->datamodel as $entity)
		{
			$entityNameMap[$entity->entity_id] = ucfirst($entity->entity_name);
		}

		// Loop over the entities to create the tables in the sql-file and the Table-files for Joomla
		$sqlCreateTable = [];
		$sqlDropTable   = [];
		$junctionTables = [];
		foreach ($project->datamodel as $entity)
		{
			$entityName = ucfirst($entity->entity_name);
			$templateVariables['entityName'] = $entityName;
			$templateVariables['getFK'] = '';

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
						if (property_exists($field->reference, 'ismultiple'))
						{
							$fromEntityName = strtolower($entityName);
							$toEntityName   = strtolower($entityNameMap[$field->reference->reference]);

							// For references to multiple entities: create the junction table for this n:n-relation
							$junctionTables[] =
								[
									'fromEntityName' => $fromEntityName,
									'toEntityName'   => $toEntityName,
								];
							$templateVariables['getFK'] .= $this->getFK($fromEntityName, $field->field_name, $toEntityName);
						}
						else
						{
							// For references to a single entity: add the foreign key
							$attributeRows[]= '`'
								. strtolower($entityNameMap[$field->reference->reference]) . '_id` '
								. "bigint(20) UNSIGNED";
						}
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

			// --- create Joomla\CMS\Table file for this entity ---
			$templateFileName = 'Table.php.twig';
			$generatedFileName = $entityName . 'Table.php';

			$logAppend($this->generateFileWithTemplate($templateFilePath, $templateFileName, $generatedTablesPath, $generatedFileName, $templateVariables));
		}

		// Junction tables
		if (!empty($junctionTables))
		{
			// Get rid of duplicate junction tables (junction table fromEntity->toEntity == toEntity->fromEntity)
			$sortedJunctions       = array_map(function(array $entityNames)
										{
											// Sort the two entityNames alphabetcally
											$sortedJunction = $entityNames;
											if($entityNames['fromEntityName'] > $entityNames['toEntityName'])
											{

												$sortedJunction['fromEntityName'] = $entityNames['toEntityName'];
												$sortedJunction['toEntityName'] = $entityNames['fromEntityName'];
											}
											return $sortedJunction;
										}
				                    , $junctionTables);

			$uniqueJunctionStrings = array_unique(array_map(
				fn(array $junction): string => $junction['fromEntityName'] . $junction['toEntityName'],
				$sortedJunctions));
			$uniqueJunctions       = array_intersect_key($sortedJunctions, $uniqueJunctionStrings);

			// Create the junction tables from uniqueJunctions-array
			foreach ($uniqueJunctions as $uniqueJunction)
			{
				$tableName = '#__' . strtolower($componentName)
					. "_" . strtolower($uniqueJunction['fromEntityName'])
					. "_" . strtolower($uniqueJunction['toEntityName']);
				$tableRows = [];
				$tableRows[] = "CREATE TABLE IF NOT EXISTS `$tableName` (";

				// Add a Drop Table to the uninstall sql file
				$sqlDropTable[] = "DROP TABLE IF EXISTS `$tableName`;";

				// Add both foreign keys for the junction
				$id1 = '`' . strtolower($uniqueJunction['fromEntityName']) . '_id`';
				$id2 = '`' . strtolower($uniqueJunction['toEntityName']) . '_id`';
				$tableRows[]= $id1 . " bigint(20) UNSIGNED,";
				$tableRows[]= $id2 . " bigint(20) UNSIGNED,";

				// And make the combination the primary key of the junction table
				$tableRows[] = "PRIMARY KEY ($id1, $id2)";
				$tableRows[] = ")  ENGINE=InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;";

				// Generate the Create Table sql
				$sqlCreateTable[] = implode("\n", $tableRows);
				$logAppend(['generated CREATE TABLE sql statement for ' . $tableName . ' in sql-file']);
			}

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

	/**
	 * Add methods to the Table object of an entity to get the n:n entities that are referenced to
	 *
	 * @param string $fromEntityName  (lowercase)
	 * @param string $fieldName       The fieldName of the collection of foreign entities
	 * @param string $toEntityName    (lowercase)
	 *
	 * @return string 2 methods: to retrieve the foreign entities and to only retrieve their ids
	 *
	 */
	private function getFK(string $fromEntityName, string $fieldName, string $toEntityName)
	{
		$componentName  = strtolower($this->componentName);
		$u1ToEntityName = ucfirst($toEntityName);
		$combi          = $fromEntityName > $toEntityName ? $toEntityName . '_' . $fromEntityName : $fromEntityName . '_' . $toEntityName;
		$junctionTable  = '#__' . $componentName . '_' . $combi;
		$otherTable     = '#__' . $componentName . '_' . $toEntityName;

		$getFK = [];

		$getFK[] = '    ';
		$getFK[] = '    public function get' . ucfirst($fieldName) . '()';
		$getFK[] = '    {';
		$getFK[] = '        $db    = $this->getDbo();';
		$getFK[] = '        $query = $db->getQuery(true)';
		$getFK[] = '            ->select($db->quoteName(\''. $toEntityName . '\') . \'.*\')';
		$getFK[] = '            ->from($db->quoteName(\'' . $junctionTable . '\', \'junction\'))';
		$getFK[] = '            ->join(\'LEFT\', 
								    $db->quoteName(\'' . $otherTable . '\', \''. $toEntityName . '\'), 
									$db->quoteName(\'junction.'. $toEntityName . '_id\') . \' = \' . $db->quoteName(\''. $toEntityName . '.id\'))';
		$getFK[] = '            ->where($db->quoteName(\''. $fromEntityName . '_id\') . \' = :thisId\')';
		$getFK[] = '            ->order($db->quoteName(\'id\') . \' ASC\')';
		$getFK[] = '            ->bind(\':thisId\', $this->id, ParameterType::INTEGER);';
		$getFK[] = '        ';
		$getFK[] = '        $' . $fieldName . ' = $db->setQuery($query)->loadAssocList() ?: [];';
		$getFK[] = '        ';
		$getFK[] = '        return $' . $fieldName . ';';
		$getFK[] = '    }';















		return implode("\n", $getFK);
	}
}