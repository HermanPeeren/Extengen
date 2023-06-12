<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\View\ERD;

defined('_JEXEC') or die;

// Get Twig: use the Composer autoloader todo: use the DIC and add this service
//require_once JPATH_LIBRARIES . '/yepr/vendor/autoload.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Yepr\Component\Extengen\Administrator\Helper\ExtengenHelper;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\MVC\View\GenericDataException;


/**
 * View class to make an Entity-Relationship-Diagram (ERD) from the datamodel with PlantUML.
 */
class HtmlView extends BaseHtmlView
{

	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  void
     * @throws Genericdataexception
	 */
	public function display($tpl = null): void
	{
        // Temporary, test
		/*$filepath = JPATH_ROOT . '/administrator/components/com_extengen/test.json'; // GetFormdata - Dit uit db halen!
        $json_string = file_get_contents($filepath);
        $project = json_decode($json_string);*/


		$model = $this->getModel();

		// Get the project_id and put it in the model
		// Todo: this must be done in the (display)controller and probably best via UserState
		$projectId = Factory::getApplication()->input->getInt('project_id');
		$model->setProjectId($projectId);

		// Get the AST from the model
		$project = $model->getAST();

		// todo: make the PlantUML-diagram in the model and only query that here

		// Open the test-uml-file for writing
		$umlfile = fopen( 'test.puml', "w") or die("Unable to open file!"); // todo: cache this file

        $component = $project->extensions->component;
		
		// Loop over the entities to make a map of entity_id to name
		$entityNameMap = [];
		foreach ($project->datamodel as $entity)
		{
			$entityNameMap[$entity->entity_id] = ucfirst($entity->entity_name);
		}

		// Initiate the uml-file.
		$umlCreate = [];
		$umlCreate[] = "@startuml
' hide the spot
' hide circle

' avoid problems with angled crows feet
skinparam linetype ortho
";
		$umlRef = [];

		// Loop over the entities to create the uml-file
		foreach ($project->datamodel as $entity)
		{
			$entityName = $entity->entity_name;

			$umlCreate[] = '			
			entity "' . $entityName . '" {
  *id : number <<generated>>
  --
  ';
			// todo: more general for other things than entities, fields and relations???

			$references = [];
			// Add fields
			foreach ($entity->field as $field)
			{
				switch ($field->field_type)
				{
					case "property":
						$type=$field->property->type;
						break;
					case "reference":
						$type=$entityNameMap[$field->reference->reference] . '  <<FK>>';
						$references[] = $entityNameMap[$field->reference->reference];
						break;
					default:
						$type="";
				}
				$umlCreate[] = ' *' . strtolower($field->field_name) . ' : ' . $type;
			}
			// Add reference(s)
			foreach ($references as $reference)
			{
				// todo only when this class owns the reference, so many2one, but not one2many
				$umlRef[] =  $reference . ' |o..o{ ' . $entityName;
			}
			$umlCreate[] = "}
			
			";

		}

		// Add the relationships
		$umlCreate[] = implode("\n", $umlRef);
		$umlCreate[] = '@enduml';

		// Compact all lines to one file
		$uml = implode("\n", $umlCreate);

		fwrite($umlfile, $uml);
		// Close the uml-file
		fclose($umlfile);

		// Get the picture from PlantUML
		$encode = $this->encodep($uml);
		$erd = "http://www.plantuml.com/plantuml/png/{$encode}";
		// todo: caching when datamodel is unchanged
		// echo $erd ."\n";
		echo "<h2>Entity Relationship Diagram for " . $project->name ."</h2>";
		echo '<p><img src="' . $erd . '" /></p>';

		//parent::display($tpl);
	}

// functions to call plantuml
	private function encodep($text) {
		$data = utf8_encode($text);
		$compressed = gzdeflate($data, 9);
		return $this->encode64($compressed);
	}

	private function encode6bit($b) {
		if ($b < 10) {
			return chr(48 + $b);
		}
		$b -= 10;
		if ($b < 26) {
			return chr(65 + $b);
		}
		$b -= 26;
		if ($b < 26) {
			return chr(97 + $b);
		}
		$b -= 26;
		if ($b == 0) {
			return '-';
		}
		if ($b == 1) {
			return '_';
		}
		return '?';
	}

	private function append3bytes($b1, $b2, $b3) {
		$c1 = $b1 >> 2;
		$c2 = (($b1 & 0x3) << 4) | ($b2 >> 4);
		$c3 = (($b2 & 0xF) << 2) | ($b3 >> 6);
		$c4 = $b3 & 0x3F;
		$r = "";
		$r .= $this->encode6bit($c1 & 0x3F);
		$r .= $this->encode6bit($c2 & 0x3F);
		$r .= $this->encode6bit($c3 & 0x3F);
		$r .= $this->encode6bit($c4 & 0x3F);
		return $r;
	}

	private function encode64($c) {
		$str = "";
		$len = strlen($c);
		for ($i = 0; $i < $len; $i+=3) {
			if ($i+2==$len) {
				$str .= $this->append3bytes(ord(substr($c, $i, 1)), ord(substr($c, $i+1, 1)), 0);
			} else if ($i+1==$len) {
				$str .= $this->append3bytes(ord(substr($c, $i, 1)), 0, 0);
			} else {
				$str .= $this->append3bytes(ord(substr($c, $i, 1)), ord(substr($c, $i+1, 1)),
					ord(substr($c, $i+2, 1)));
			}
		}
		return $str;
	}

}
