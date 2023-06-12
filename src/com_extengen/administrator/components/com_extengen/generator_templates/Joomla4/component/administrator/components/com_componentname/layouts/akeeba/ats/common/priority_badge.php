<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\ATS\Administrator\Table\TicketTable;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

/**
 * @var array              $displayData Incoming display data. These set the following variables.
 * @var object|TicketTable $item        The ticket object
 * @var bool               $showNormal  Show the normal priority badge?
 */

extract(array_merge([
	'item'       => null,
	'showNormal' => true,
], $displayData));

// Bail out on no or invalid data
if (empty($item) || !is_object($item) || !property_exists($item, 'priority'))
{
	return;
}

// Bail out if priorities are not supported
if (ComponentHelper::getParams('com_ats')->get('ticketPriorities', 0) != 1)
{
	return;
}

$background = 'bg-dark';
$icon       = 'fa fa-equals';
$langCode   = 'COM_ATS_PRIORITIES_NORMAL';

if ($item->priority > 5)
{
	$background = 'bg-info';
	$icon       = 'fa fa-chevron-down';
	$langCode   = 'COM_ATS_PRIORITIES_LOW';
}
elseif (($item->priority > 0) && ($item->priority < 5))
{
	$background = 'bg-danger';
	$icon       = 'fa fa-chevron-up';
	$langCode   = 'COM_ATS_PRIORITIES_HIGH';
}
elseif(!$showNormal)
{
	return;
}

?>
<div class="text-center">
	<div class="badge fs-4 p-2 <?= $background ?>"
	     title="<?= Text::_('COM_ATS_TICKET_PRIORITY') ?>: <?= Text::_($langCode) ?>">
		<span class="<?= $icon ?> mx-0" aria-hidden="true"></span>
	</div>
</div>
