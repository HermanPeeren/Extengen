<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\View\FormsDiagram;

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
 * View class to make a JSON-Diagram from the defined forms with PlantUML.
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
		$projectFormId = Factory::getApplication()->input->getInt('projectform_id');
		$model->setProjectFormId($projectFormId);

		// Get the AST from the model as JSON-string
		$AST = $model->getAST();

		// Get a JSON-diagram of the AST
		// $projectForm = json_encode($AST, JSON_PRETTY_PRINT); //This gives the M2 JSON; surround it with @startjson and @endjson
		// $umlCreate = [];
		// $umlCreate[] = "@startjson";
		// $umlCreate[] = 	$projectForm;
		// $umlCreate[] = '@endjson';


		// Get the concepts + conceptInterfaces and the root-concept (assume 1 root for now)
		// Make a key-concept-map + key-conceptInterface-map
		$keyConceptMap = [];
		$keyConceptInterfaceMap = [];
		$root = null;
		foreach ($AST->languageEntities as $languageEntity)
		{
			if ($languageEntity->languageEntity_type=='Classifier')
				{
					// Concepts
					if ($languageEntity->classifier->classifier_type=='Concept')
					{
						$keyConceptMap[$languageEntity->key] = $languageEntity;// ? alleen de naam nodig???

						// is this the root? Property partition only exists if it == 1.
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

		//echo '<pre>';
//print_r($keyConceptMap);
		//echo '</pre>';

		// Build up the Object Diagram of the form, starting at the root and going down.
		// This is how a Package containing entities, pages and extensions childen, all with a name, look like:
		//
		//@startuml
		//object Package {
		//    name
		//}
		//object Entity {
		//    name
		//}
		//
		//object Page{
		//    name
		//}
		//
		//object Extension{
		//    name
		//}
		//
		//Package *-- Entity: entities
		//Package *-- Page: pages
		//Package *-- Extension: extensions
		//@enduml



		// todo: make the PlantUML-diagram in the model and only query that here

		// Open the test-uml-file for writing
		$umlfile = fopen( 'testform.puml', "w") or die("Unable to open file!"); // todo: cache this file

		// the whole PlantUML definition of children, references and extensions
		$umlRef = [];

		// Initiate the uml-file.
		$umlCreate = [];
		$umlCreate[] = "@startuml";

		// Loop over all CONCEPTINTERFACES
		foreach ($keyConceptInterfaceMap as $languageEntity)
		{
			$umlCreate[]  = '			
				interface ' . $languageEntity->name . ' {
	  			';

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

		fwrite($umlfile, $uml);
		// Close the uml-file
		fclose($umlfile);

		// Get the picture from PlantUML
		$encode = $this->encodep($uml);
		$formDiagram = "http://www.plantuml.com/plantuml/png/{$encode}";
		// todo: caching when Project Form is unchanged
		echo "<h2>" . Text::_('COM_EXTENGEN_BUTTON_PROJECTFORM_DIAGRAM')
		    . Text::_('COM_EXTENGEN_FOR')
			. $AST->name ."</h2>";
		echo '<p><img src="' . $formDiagram . '" /></p>';

		//parent::display($tpl);
	}

// functions to call plantuml
	private function encodep($text) {
		//$data = utf8_encode($text); // utf8_encode() deprecated since PHP 8.2
		$data = mb_convert_encoding($text, 'UTF-8');
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
