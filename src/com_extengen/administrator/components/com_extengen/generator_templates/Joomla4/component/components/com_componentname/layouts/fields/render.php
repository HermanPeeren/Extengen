<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var array $displayData The display data for this layout */

use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

// Check if we have all the data
$item    = $displayData['item'] ?? null;
$context = $displayData['context'] ?? null;

if (empty($item) || empty($context))
{
	return;
}

// Get the fields and remove the ones without any display data
$parts     = explode('.', $context);
$component = $parts[0];
$fields    = $displayData['fields'] ?? $item->jcfields ?: FieldsHelper::getFields($context, $item, true);
$fields    = array_filter($fields ?: [], function ($field) {
	return is_object($field) && isset($field->value) && ($field->value !== '');
});

if (empty($fields))
{
	return;
}

// Get field rendering information and group information
$groups = [];

$rendered = array_map(function (object $field) use ($context, &$groups) {
	$layout  = $field->params->get('layout', 'render');
	$content = FieldsHelper::render($context, 'field.' . $layout, ['field' => $field]);

	if (!empty($content) && !isset($groups[$field->group_id]))
	{
		$groups[$field->group_id] = (object) [
			'id'     => $field->group_id,
			'title'  => $field->group_title,
			'access' => $field->group_access,
			'state'  => $field->group_state,
			'note'   => $field->group_note,
		];
	}

	return (object) [
		'group'   => $field->group_id,
		'content' => $content,
	];
}, $fields);

// Remove fields which have not rendered anything
$rendered = array_filter($rendered, function ($x) {
	return !empty($x);
});

if (empty($rendered))
{
	return;
}

?>
<div class="ats-ticket-fields-container">
	<?php foreach ($groups as $group): ?>
		<div class="container mb-3">
			<h4 class="h5 border-bottom"><?= $group->title ?></h4>
			<?php foreach ($rendered as $item)
			{
				if ($item->group != $group->id)
				{
					continue;
				}

				echo $item->content;
			}
			?>
		</div>
	<?php endforeach; ?>
</div>