<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * @var array       $displayData Incoming display data. These set the following variables.
 * @var TicketTable $item        The ticket object.
 * @var string      $class		 Additional class for the dropdown button.
 */

use Akeeba\Component\ATS\Administrator\Helper\ComponentParams;
use Akeeba\Component\ATS\Administrator\Table\TicketTable;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

extract(array_merge([
	'item'  => null,
	'class' => '',
], $displayData));

// Bail out on no or invalid data
if (empty($item) || !is_object($item) || !property_exists($item, 'priority'))
{
	return;
}

// Get all known ticket statuses
$allStatuses = ComponentParams::getStatuses();

// Make sure we actually have drop–downs enabled
HTMLHelper::_('bootstrap.dropdown');

// Require the JS
/** @var HtmlDocument $document */
$document = Factory::getApplication()->getDocument();
$document->getWebAssetManager()
	->useScript('com_ats.tickets_frontend');

/**
 * Map a status short code to a color
 * @param   string  $status  The status: O, P, C, 1..99
 * @return  string  The color code
 */
$colorMapper = function (string $status): string {
	switch ($status)
	{
		case 'O':
			return 'danger';
		case 'P':
			return 'info';
		case 'C':
			return 'success';
		default:
			return 'dark';
	}
};

// Pass the labels and colors of the statuses to the frontend
$frontendStatuses = [];
foreach ($allStatuses as $value => $label)
{
	$frontendStatuses[$value] = [
		'label' => $label,
		'color' => 'btn-' . $colorMapper($value),
	];
}
$allColors = array_values(array_unique(array_map(function ($x) {
	return $x['color'];
}, $frontendStatuses)));
$document->addScriptOptions('com_ats.statuses', $frontendStatuses);
$document->addScriptOptions('com_ats.allStatusColors', $allColors);

$btnColor    = 'btn-' . $colorMapper($item->status);
$id          = 'atsStatusDD_' . md5(random_bytes(16) . microtime());
?>
<div class="dropdown">
	<button type="button" data-bs-toggle="dropdown" aria-expanded="false"
			class="btn btn-sm <?= $btnColor ?> <?= $class ?> dropdown-toggle" id="<?= $id ?>"
	>
		<?= $allStatuses[$item->status] ?? '' ?>
	</button>

	<ul class="dropdown-menu" aria-labelledby="<?= $id ?>">
	<?php foreach ($allStatuses as $value => $label): ?>
	<li>
		<a class="dropdown-item atsStatusDropdown m-0 p-1"
		   href="#" data-status="<?= $value ?>" data-ticketid="<?= $item->id ?>" data-dropdownid="<?= $id ?>">
			<span class="badge bg-<?= $colorMapper($value) ?> text-start w-100 m-0 p-2">
				<?= $label ?>
			</span>
		</a>
	</li>
	<?php endforeach ?>
	</ul>
</div>