<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var array $displayData */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

if (!array_key_exists('field', $displayData))
{
	return;
}

$field      = $displayData['field'];
$isPrivate  = $field->fieldparams->get('atsPrivate', '0') == 1;
$label      = Text::_($field->label);
$value      = $field->value;
$showLabel  = $field->params->get('showlabel');
$prefix     = Text::plural($field->params->get('prefix'), $value);
$suffix     = Text::plural($field->params->get('suffix'), $value);
$labelClass = $field->params->get('label_render_class');
$valueClass = $field->params->get('value_render_class');
$class      = $field->name . ' ' . $field->params->get('render_class');

if ($value == '')
{
	return;
}

?>
<div class="row mb-1 <?= $class ?>">
	<?php if ($showLabel == 1) : ?>
		<div class="col-12 col-sm-6 col-md-4 fw-bold field-label <?= $labelClass ?>">
			<?php if ($isPrivate):
				HTMLHelper::_('bootstrap.tooltip', '.atsTooltip');
				?>
			<span class="badge bg-warning atsTooltip" data-bs-placement="top" title="<?= htmlentities(Text::_('COM_ATS_TICKET_FIELD_IS_PRIVATE'), ENT_QUOTES | ENT_IGNORE, 'UTF-8') ?>">
				<span class="visually-hidden"><?= Text::_('COM_ATS_TICKET_FIELD_IS_PRIVATE') ?></span>
				<span class="fa fa-eye-slash" aria-hidden="true"></span>
			</span>
			<?php endif ?>
			<?= htmlentities($label, ENT_QUOTES | ENT_IGNORE, 'UTF-8') ?>
		</div>
	<?php endif ?>

	<div class="col">
		<?php if ($prefix) : ?>
			<span class="field-prefix"><?= htmlentities($prefix, ENT_QUOTES | ENT_IGNORE, 'UTF-8'); ?></span>
		<?php endif; ?>
		<span class="field-value <?= $valueClass; ?>"><?= $value; ?></span>
		<?php if ($suffix) : ?>
			<span class="field-suffix"><?= htmlentities($suffix, ENT_QUOTES | ENT_IGNORE, 'UTF-8'); ?></span>
		<?php endif; ?>
	</div>
</div>