<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string  $autocomplete   Autocomplete attribute for the field.
 * @var   boolean $autofocus      Is autofocus enabled?
 * @var   string  $class          Classes for the input.
 * @var   string  $description    Description of the field.
 * @var   boolean $disabled       Is this field disabled?
 * @var   string  $group          Group the field belongs to. <fields> section in form XML.
 * @var   boolean $hidden         Is this field hidden in the form?
 * @var   string  $hint           Placeholder for the field.
 * @var   string  $id             DOM id of the field.
 * @var   string  $label          Label of the field.
 * @var   string  $labelclass     Classes to apply to the label.
 * @var   boolean $multiple       Does this field support multiple values?
 * @var   string  $name           Name of the input field.
 * @var   string  $onchange       Onchange attribute for the field.
 * @var   string  $onclick        Onclick attribute for the field.
 * @var   string  $pattern        Pattern (Reg Ex) of value of the form field.
 * @var   boolean $readonly       Is this field read only?
 * @var   boolean $repeat         Allows extensions to duplicate elements.
 * @var   boolean $required       Is this field required?
 * @var   boolean $spellcheck     Spellcheck state for the form field.
 * @var   string  $validate       Validation rules to apply.
 * @var   string  $value          Value attribute of the field.
 * @var   array   $checkedOptions Options that will be set as checked.
 * @var   boolean $hasValue       Has this field a value assigned?
 * @var   array   $options        Options available for this field.
 * @var   array   $inputType      Options available for this field.
 * @var   string  $accept         File types that are accepted.
 * @var   string  $dataAttribute  Miscellaneous data attributes preprocessed for HTML output
 * @var   array   $dataAttributes Miscellaneous data attribute for eg, data-*.
 */

$attributes = [
	!empty($class) ? 'class="form-control ' . $class . '"' : 'class="form-control"',
	!empty($description) ? 'aria-describedby="' . $name . '-desc"' : '',
	$disabled ? 'disabled' : '',
	$readonly ? 'readonly' : '',
	strlen($hint) ? 'placeholder="' . htmlspecialchars($hint, ENT_COMPAT, 'UTF-8') . '"' : '',
	!empty($onchange) ? 'onchange="' . $onchange . '"' : '',
	isset($max) ? 'max="' . $max . '"' : '',
	!empty($step) ? 'step="' . $step . '"' : '',
	isset($min) ? 'min="' . $min . '"' : '',
	$required ? 'required' : '',
	!empty($autocomplete) ? 'autocomplete="' . $autocomplete . '"' : '',
	$autofocus ? 'autofocus' : '',
	$dataAttribute,
];

if (is_numeric($value))
{
	$value = (float) $value;
}
else
{
	$value = '';
	$value = ($required && isset($min)) ? $min : $value;
}

$id        = $id ?? 'timespent';
$btnId     = $id . '-startstop-button';
$autoStart = ComponentHelper::getParams('com_ats')->get('timespent_mandatory', 0) == 1;

// Load the necessary JavaScript for the timer
Factory::getApplication()->getDocument()->getWebAssetManager()
	->useScript('com_ats.timespent');

// Push language files to client–side
Text::script('COM_ATS_POSTS_TIMESPENT_FIELD_START');
Text::script('COM_ATS_POSTS_TIMESPENT_FIELD_STOP');

?>
<div class="input-group mb-3">
	<input
			type="number"
			inputmode="decimal"
			name="<?= $name; ?>"
			id="<?= $id; ?>"
			value="<?= htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>"
		<?= implode(' ', $attributes); ?> >
	<span class="input-group-text">
		<?= Text::_('COM_ATS_POSTS_TIMESPENT_LBL_MINUTES') ?>
	</span>
	<button type="button" id="<?= $btnId ?>"
			class="btn btn-outline-secondary atsTimespentFieldStartStop"
			data-parent="<?= $id ?>"
			data-autostart="<?= $autoStart ? 1 : 0 ?>"
	>
		<?= Text::_('COM_ATS_POSTS_TIMESPENT_FIELD_START') ?>
	</button>
</div>