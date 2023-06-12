<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\ATS\Administrator\Helper\ComponentParams;
use Akeeba\Component\ATS\Administrator\Table\TicketTable;

/**
 * @var array              $displayData Incoming display data. These set the following variables.
 * @var object|TicketTable $item        The ticket object
 * @var string             $class       Additional class to the badge
 */

extract(array_merge([
	'item'  => null,
	'class' => 'p-2',
], $displayData));

// Bail out on no or invalid data
if (empty($item) || !is_object($item) || !property_exists($item, 'priority'))
{
	return;
}

$allStatuses = ComponentParams::getStatuses();
switch ($item->status)
{
	case 'O':
		$background = 'bg-danger';
		break;
	case 'P':
		$background = 'bg-info';
		break;
	case 'C':
		$background = 'bg-success';
		break;
	default:
		$background = 'bg-dark';
		break;
}

$description = $allStatuses[$item->status] ?? null;

if (!$description)
{
	return;
}
?>
<span class="badge <?= $background ?> <?= $class ?>"><?= $description ?></span>