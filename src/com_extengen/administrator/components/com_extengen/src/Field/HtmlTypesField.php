<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\Field;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;


// The class name must always be the same as the filename (in camel case)
// extend the list field type
class HtmlTypesField extends ListField
{
	//The field class must know its own type through the variable $type.
	protected $type = 'HtmlTypes';

	// get the options for the list field


	/**
	 * Get the options for the list field: all possible field-types
	 * Todo: attributes for those types...
	 * Todo: some fields can only be used for specific data-types; other options should not be available
	 *
	 * ALL STANDARD JOOMLA FORM FIELD TYPES:
	 *     - accessiblemedia: provides modal access to the media manager for insertion of images with upload for users with appropriate permissions and a text field for adding a alternative text.
	 *     - accesslevel: provides a drop down list of viewing access levels.
	 *     - cachehandler: provides a list of available cache handling options.
	 *     - calendar: provides a text box for entry of a date. An icon next to the text box provides a link to a pop-up calendar, which can also be used to enter the date value.
	 *     - captcha: provides the use of a captcha plugin.
	 *     - category: provides a drop down list of categories for an extension.
	 *     - checkbox: provides a single checkbox to be checked or unchecked
	 *     - checkboxes: provides unlimited checkboxes that can be used for multi-select.
	 *     - Chrome Style: provides a list of template chrome style options grouped by template.
	 *     - color: provides a color picker when clicking the input box.
	 *     - Content Language: Provides a list of content languages.
	 *     - Content Type: Provides a list of content types.
	 *     - combo: provides a combo box field.
	 *     - componentlayout: provides a grouped list of core and template alternative layouts for a component item.
	 *     - contentlanguage: provides a list of installed content languages for use in conjunction with the language switcher plugin.
	 *     - Database Connection: Provides a list of available database connections, optionally limiting to a given list.
	 *     - editor: provides an editor area field.
	 *     - editors: Provides a drop down list of the available WYSIWYG editors. Since Joomla 2.5 use plugins form field instead.
	 *     - email: provides an email field.
	 *     - file:Provides an input field for files
	 *     - filelist: provides a drop down list of files from a specified directory.
	 *     - folderlist: provides a drop down list of folders from a specified directory.
	 *     - groupedlist: provides a drop down list of items organized into groups.
	 *     - header tag:provides a drop down list of the header tags (h1-h6).
	 *     - helpsite: provides a drop down list of the help sites for your Joomla installation.
	 *     - hidden: provides a hidden field for saving a form field whose value cannot be altered directly by a user in the Administrator (it can be altered in code or by editing the params.ini file).
	 *     - imagelist: provides a drop down list of image files in a specified directory.
	 *     - integer: provides a drop down list of integers between a minimum and maximum.
	 *     - language: provides a drop down list of the installed languages for the Front-end or Back-end.
	 *     - list: provides a drop down list of custom-defined entries.
	 *     - media: provides modal access to the media manager for insertion of images with upload for users with appropriate permissions.
	 *     - menu: provides a drop down list of the available menus from your Joomla site.
	 *     - Menu Item: provides a drop down list of the available menu items from your Joomla site.
	 *     - meter: Provides a meter to show value in a range.
	 *     - Module Layout: provides a list of alternative layout for a module grouped by core and template.
	 *     - Module Order: Provides a drop down to set the ordering of module in a given position
	 *     - Module Position: provides a text input to set the position of a module.
	 *     - Module Tag: provides a list of html5 elements (used to wrap a module in).
	 *     - note: supports a one line text field.
	 *     - number: Provides a one line text box with up-down handles to set a number in the field.
	 *     - password: provides a text box for entry of a password.  The password characters will be obscured as they are entered.
	 *     - plugins: provides a list of plugins from a given folder.
	 *     - predefinedlist: Form Field to load a list of predefined values.
	 *     - radio: provides radio buttons to select different options.
	 *     - range: Provides a horizontal scroll bar to specify a value in a range.
	 *     - repeatable: Allows form fields which can have as many options as the user desires.
	 *     - rules: provides a matrix of group by action options for managing access control. Display depends on context.
	 *     - sessionhandler: provides a drop down list of session handler options.
	 *     - spacer: provides a visual separator between form fields.  It is purely a visual aid and no value is stored.
	 *     - sql: provides a drop down list of entries obtained by running a query on the Joomla Database.  The first results column returned by the query provides the values for the drop down box.
	 *     - subform: provides a way to use XML forms inside each other or reuse your existing forms inside your current form.
	 *     - tag: provides an entry point for tags (either AJAX or Nested).
	 *     - tel: provides an input field for a telephone number.
	 *     - templatestyle: provides a drop down list of template styles.
	 *     - text: provides a text box for data entry.
	 *     - textarea: provides a text area for entry of multi-line text.
	 *     - timezone: provides a drop down list of time zones.
	 *     - URL: provides a URL text input field.
	 *     - user: Field to select a user from a modal list. Displays User Name and stores User ID
	 *     - useractive: Field to show a list of available user active statuses.
	 *     - usergroup: provides a drop down list of user groups. Since Joomla 3.2 use usergrouplist instead.
	 *     - usergrouplist: Field to load a drop down list of available user groups. Replaces usergroup form field type.
	 *     - userstate: Field to load a list of available users statuses.
	 *
	 *
	 *     - yes_no_buttons and show_hide_buttons are fields of type list with 2 options (JNO/JYES and JHIDE/JSHOW
	 *     - Todo: custom form fields for decimals, currency, floating point values etc.
	 *
	 */
	public function getOptions()
	{
		// insert your JSON here or else call an API to get all possible fields
		// todo: besides Joomla core fields, Xtra fields and other custom fields must be dynamically added
		$fieldsJson = '{
			 "accessiblemedia":"Accessible Media",
			 "accesslevel":"Access level drop down",
			 "cachehandler":"Cache handling options dropdown",
			 "calendar":"Calendar date picker",
			 "captcha":"Captcha",
			 "category":"Categories drop down",
			 "checkbox":"Checkbox",
			 "checkboxes":"Checkboxes",
			 "Chrome Style":"Chrome styles drop down",
			 "color":"Color picker",
			 "Content Language":"Content languages drop down",
			 "Content Type":"Content types drop down",
			 "combo":"Combo box",
			 "componentlayout":"Component layouts",
			 "contentlanguage":"Content languages drop down",
			 "Database Connection":"Database connections drop down",
			 "editor":"Editor area field",
			 "email":"Email field.",
			 "file":"Files input field",
			 "filelist":"File list drop down",
			 "folderlist":"Folder list drop down",
			 "groupedlist":"Grouped list drop down",
			 "header tag":"Header tags (h1-h6) drop down",
			 "helpsite":"Help sites drop down",
			 "hidden":"Hidden field",
			 "imagelist":"Image files list drop down",
			 "integer":"Integer drop down (min/max)",
			 "language":"Languages drop down",
			 "list":"List drop down",
			 "media":"Media manager modal",
			 "menu":"Menus drop down",
			 "Menu Item":"Menu item drop down",
			 "meter":"Meter",
			 "Module Layout":"Module layouts drop down",
			 "Module Order":"Module order drop down",
			 "Module Position":"Module position text input",
			 "Module Tag":"Module tags html5 elements drop down",
			 "note":"Note field",
			 "number":"Number text box with up-down handles",
			 "password":"Password text box",
			 "plugins":"Plugins drop down",
			 "predefinedlist":"Predefined values list",
			 "radio":"Radio buttons",
			 "range":"Range horizontal scroll bar",
			 "rules":"Rules for managing access control",
			 "sessionhandler":"Session handler options drop down",
			 "spacer":"Spacer",
			 "sql":"SQL filled drop down list",
			 "subform":"Subform",
			 "tag":"Tags entry point",
			 "tel":"telephone number input field",
			 "templatestyle":"Template styles drop down list",
			 "text":"Text box",
			 "textarea":"Text area",
			 "timezone":"Time zones drop down list",
			 "URL":"URL text input field.",
			 "user":"User select from a modal list.",
			 "useractive":"Users that are active list",
			 "usergrouplist":"User groups drop down list",
			 "userstate":"User statuses list",
			 
			 "yes_no_button":"Yes/No-button",
			 "show_hide_button":"Show/Hide-button"
		}'; // yes_no_buttons and show_hide_buttons are fields of type list with 2 options (JNO/JYES and JHIDE/JSHOW

        // decode the JSON
        $htmlTypes = json_decode($fieldsJson, true);

        // use a for each to iterate over the JSON
		$htmlTypeOptions = [];
        foreach($htmlTypes as $htmlType => $text)
        {
	        // Set an array with the  value / text items.
	        $htmlTypeOptions[] = array("value" => $htmlType, "text" => $text);
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $htmlTypeOptions);
        return $options;
    }
}
