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

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

// todo: make translation optional
// todo: a proper translation API (and use composer). This is just for testing...
// todo: translation is now string by string. It should be in context. Maybe have the whole file translated by ChatGPT at once, to keep strings in context. You can then also give a description of the domain for a better understanding of the semantics.
require_once(__DIR__ . '/GoogleTranslate.php');


/**
 * Twig extension: Language string utilities
 *
 * @package     Yepr\Component\Extengen\Administrator\Model
 */
class LanguageStringUtil extends AbstractExtension
{
	/**
	 * The Abstract Syntax Tree (AST) = the form-data of the project as 1 object with hierarchical properties
	 *
	 * @var object
	 */
	protected object $AST;

	/**
	 * The tree that will be built during generation, from which all language files are generated in the end
	 *
	 * @var object
	 */
	protected object $langTree;

	/**
	 * A translation object, using free Google translate ... temporary, for testing
	 *
	 * @var object
	 */
	protected object $translate;

	/**
	 * LanguageStringUtil Constructor
	 *
	 * @param   object        $AST        The Abstract Syntax Tree (AST) = the form-data of the project
	 *
	 * @return  void
	 */
	public function __construct(object $AST)
	{
		$this->AST = $AST;

		// todo: as the $AST is only needed for this, it can better be called from the outside, so the whole AST is not carried around
		$this->initLangTree();

		// test translation todo: choose different translate options
		$this->translate = new \Statickidz\GoogleTranslate();
	}

	public function getFunctions()
	{
		return [
			new TwigFunction('addLanguageString', [$this, 'addLanguageString']),
			new TwigFunction('addLanguageStringFromView', [$this, 'addLanguageStringFromView']),
			new TwigFunction('addLanguageStringFromForm', [$this, 'addLanguageStringFromForm'])
		];
	}

	/**
	 * Return a language string + (side effect) put in language files
	 *
	 * @param   string  $componentName
	 * @param   string  $pageName
	 * @param   string  $templateValue
	 * @param   string  $english          Preferred English translation.
	 * @param   string  $applicationType  Administrator | Site; default = Administrator. A.k.a. Section: backend and frontend
	 * @param   bool    $sys              If this is backend, will it be added to sys-language file? Default = false.
	 *
	 * @return  string  $langstring
	 */
	public function addLanguageStringFromView(
		string $componentName,
		string $pageName,
		string $templateValue,
		string $english,
		string $applicationType = "Administrator",
		bool   $sys = false
	)
	{
		// Make the language string
		$optional_underscore = "_";
		if (empty($templateValue)) $optional_underscore = "";
		$langstring = "COM_" . strtoupper($componentName) . $optional_underscore
			. str_replace("pageName",strtoupper($pageName),$templateValue);

		// Side effect:add the languagestring to the language tree for all languages in this component's section
		// Add to backend, sys or frontend?
		$fileType = $this->langTree->backend;
		if ($sys) $fileType = $this->langTree->sys;
		if ($applicationType == "Site") $fileType = $this->langTree->frontend;

		foreach ($fileType->languages as $language)
		{
			$keyValuePair = new \stdClass();
			$keyValuePair->language_string = $langstring;

			// todo: only translate if no translated string exists (you can give a preferred translation)
			// TEST automatic translation for other languages todo: proper translation API
			// todo: in general addLanguageString() method also replace componentName (and maybe also projectName) in templateValue
			$locale_string = ucfirst(str_replace("%pageName%",strtolower($pageName), $english));
			$locale_string_translated = $locale_string;

			$source = 'en';
			$target = $language->language_code;

			// even geen translation, was maar even om uit te proberen. todo: implement translation properly
/*
			if ($target != 'en')
			{
				$locale_string_translated = $this->translate->translate($source, $target, $locale_string);
			}*/

			$keyValuePair->locale_string = $locale_string_translated;

			// todo: only add if key not yet exist!
			$language->key_value_pairs[] = $keyValuePair;
		}

		return $langstring;
	}

	/**
	 * Return a language string + (side effect) put in language files
	 * todo: make 1 general method for adding language strings (from view, from form, whatever...)
	 * todo: the difference between addLanguageStringFromView and addLanguageStringFromForm is 1 parameter fieldName and its use.
	 *
	 * @param   string  $componentName
	 * @param   string  $pageName
	 * @param   string  $fieldName
	 * @param   string  $templateValue
	 * @param   string  $english          Preferred English translation.
	 * @param   string  $applicationType  Administrator | Site; default = Administrator. A.k.a. Section: backend and frontend
	 * @param   bool    $sys              If this is backend, will it be added to sys-language file? Default = false.
	 *
	 * @return  string  $langstring
	 */
	public function addLanguageStringFromForm(
		string $componentName,
		string $formName,
		string $fieldName,
		string $templateValue,
		string $english,
		string $applicationType = "Administrator",
		bool   $sys = false
	)
	{
		// Make the language string
		$langstring = "COM_" . strtoupper($componentName) . "_"
			. str_replace(["formName", "fieldName"],[strtoupper($formName),strtoupper($fieldName)],$templateValue);

		// Side effect:add the languagestring to the language tree for all languages in this component's section
		// Add to backend, sys or frontend?
		$fileType = $this->langTree->backend;
		if ($sys) $fileType = $this->langTree->sys;
		if ($applicationType == "Site") $fileType = $this->langTree->frontend;

		foreach ($fileType->languages as $language)
		{
			$keyValuePair = new \stdClass();
			$keyValuePair->language_string = $langstring;

			// TEST automatic translation for other languages todo: proper translation API
			$locale_string = ucfirst(str_replace(["%formName%", "%fieldName%"],[strtolower($formName),strtolower($fieldName)], $english));

			$locale_string_translated = $locale_string;

			$source = 'en';
			$target = $language->language_code;

			// even geen translation, was maar even om uit te proberen. todo: implement translation properly
/*
			if ($target != 'en')
			{
				$locale_string_translated = $this->translate->translate($source, $target, $locale_string);
			}*/

			$keyValuePair->locale_string = $locale_string_translated;

			// todo: only add if key not yet exist!
			$language->key_value_pairs[] = $keyValuePair;
		}

		return $langstring;
	}

	/**
	 * Return a language string + (side effect) put in language files
	 * todo: calling parameters in right order!!!
	 *
	 * @param   string  $templateValue
	 * @param   string  $english          Preferred English translation.
	 * @param   string  $componentName
	 * @param   string  $pageName         The name of the page OR the form
	 * @param   string  $fieldName
	 * @param   string  $applicationType  Administrator | Site; default = Administrator. A.k.a. Section: backend and frontend
	 * @param   bool    $sys              If this is backend, will it be added to sys-language file? Default = false.
	 *
	 * In $templateValue the strings componentName, pageName and fieldName will be replaced with their respective (uppercase) values.
	 * In $english the strings %componentName%, %pageName% and %fieldName% will be replaced with their respective (lowercase) values.
	 * The completed $english string will start with an uppercase letter. Todo: make possibility to start with lowercase.
	 *
	 * @return  string  $langstring
	 */
	public function addLanguageString(
		string $templateValue,
		string $english,
		string $componentName,
		string $pageName = "",
		string $fieldName = "",
		string $applicationType = "Administrator",
		bool   $sys = false
	)
	{
		// Make the language string
		$langstring = "COM_" . strtoupper($componentName) . "_"
			. str_replace(["pageName", "fieldName"],[strtoupper($pageName),strtoupper($fieldName)],$templateValue);

		// Side effect:add the languagestring to the language tree for all languages in this component's section
		// Add to backend, sys or frontend?
		$fileType = $this->langTree->backend;
		if ($sys) $fileType = $this->langTree->sys;
		if ($applicationType == "Site") $fileType = $this->langTree->frontend;

		foreach ($fileType->languages as $language)
		{
			$keyValuePair = new \stdClass();
			$keyValuePair->language_string = $langstring;

			// TEST automatic translation for other languages todo: proper translation API
			$locale_string = ucfirst(str_replace(["%pageName%", "%fieldName%"],[strtolower($pageName),strtolower($fieldName)], $english));

			$locale_string_translated = $locale_string;

			$source = 'en';
			$target = $language->language_code;

			// even geen translation, was maar even om uit te proberen. todo: implement translation properly
/*
			if ($target != 'en')
			{
				$locale_string_translated = $this->translate->translate($source, $target, $locale_string);
			}*/

			$keyValuePair->locale_string = $locale_string_translated;

			// todo: only add if key not yet exist! Because already given strings have priority.
			$language->key_value_pairs[] = $keyValuePair;
		}

		return $langstring;
	}

	/**
	 * Initialise the language tree: for all sections (Administrator | Site), for all languages; in backend also sys-file.
	 *
	 * @return  void
	 */
	private function initLangTree()
	{
		$langTree = new \stdClass();
		$extensions = $this->AST->extensions;
		if (!empty($extensions))
		{
			foreach ($extensions as $type => $extension)
			{
				// I now assume that there is max 1 component in the project
				// todo: adjust this for modules and plugins...
				if ($type=='component')
				{
					$languages = new \stdClass;
					$languages->languages = $extension->languages;

					// just cloning is not enough: nested objects are still references. Hence: unserialize(serialize($object))
					$languages_serialised = serialize($languages);

					// Backend languages including sys
					$langTree->backend = unserialize($languages_serialised);
					$langTree->sys     = unserialize($languages_serialised);

					// Frontend languages
					if (!empty($extension->Sections->frontendsection))
					{
						$langTree->frontend = unserialize($languages_serialised);
					}
				}
			}
		}

		$this->langTree = $langTree;
	}

	/**
	 * Get the completed language tree, to generate all language files from it.
	 *
	 * @return object $langTree
	 */
	public function getLangTree()
	{
		return $this->langTree;
	}

}
