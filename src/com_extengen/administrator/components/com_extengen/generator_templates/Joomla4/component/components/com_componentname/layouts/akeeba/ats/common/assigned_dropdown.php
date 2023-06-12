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

use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Akeeba\Component\ATS\Administrator\Table\TicketTable;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

extract(array_merge([
	'item'  => null,
	'class' => '',
], $displayData));

// Bail out on no or invalid data
$isManager = Permissions::isManager($item->catid);
$canDo     = (!empty($item) && ($item instanceof TicketTable)) ? Permissions::getTicketPrivileges($item) : [];

if (empty($item) || !is_object($item) || !property_exists($item, 'priority') || (!$isManager && !($canDo['ticket.assign'] ?? false)))
{
	return;
}

$me       = Permissions::getUser();
$managers = ['0' => [
	'name'  => Text::_('COM_ATS_TICKETS_UNASSIGNED'),
	'color' => 'dark',
]];

foreach (Permissions::getAssignees($item->catid) as $userInfo)
{
	$id = $userInfo->id;
	$managers[$id] = [
		'name' => $userInfo->name,
		'color' => ($id == $me->id) ? 'success' : 'info'
	];
}

$allColors = ['btn-outline-success', 'btn-outline-info', 'btn-outline-dark'];

// Make sure we actually have drop–downs enabled
HTMLHelper::_('bootstrap.dropdown');

// Require the JS
/** @var HtmlDocument $document */
$document = Factory::getApplication()->getDocument();
$document->getWebAssetManager()
	->useScript('com_ats.tickets_frontend');

$document->addScriptOptions('com_ats.managers', array_map(function ($managerInfo) {
	$managerInfo['color'] = 'btn-outline-' . $managerInfo['color'];

	return $managerInfo;
}, $managers));

$document->addScriptOptions('com_ats.allManagerColors', $allColors);


$assignedTo = (int) $item->assigned_to;
$assignedTo = array_key_exists($assignedTo, $managers) ? $assignedTo : 0;
$btnColor   = 'btn-outline-' . $managers[$assignedTo]['color'];
$id         = 'atsAssignDD_' . md5(random_bytes(16) . microtime());
?>
<div class="dropdown">
	<button type="button" data-bs-toggle="dropdown" aria-expanded="false"
			class="btn btn-sm <?= $btnColor ?> <?= $class ?> dropdown-toggle" id="<?= $id ?>"
	>
		<span class="text-truncate w-25">
			<?= $managers[$assignedTo]['name'] ?>
		</span>
	</button>

	<ul class="dropdown-menu" aria-labelledby="<?= $id ?>">
	<?php foreach ($managers as $userId => $info): ?>
	<li>
		<a class="dropdown-item atsAssignedDropdown m-0 p-1"
		   href="#" data-assigned="<?= $userId ?>" data-ticketid="<?= $item->id ?>" data-dropdownid="<?= $id ?>">
			<span class="badge bg-<?= $info['color'] ?> text-start w-100 m-0 p-2">
				<?= $info['name'] ?>
			</span>
		</a>
	</li>
	<?php endforeach ?>
	</ul>
</div>