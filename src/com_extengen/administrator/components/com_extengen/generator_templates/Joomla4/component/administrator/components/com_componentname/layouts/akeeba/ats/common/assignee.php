<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Akeeba\Component\ATS\Administrator\Table\TicketTable;
use Joomla\CMS\Language\Text;

/**
 * @var array              $displayData    Incoming display data. These set the following variables.
 * @var object|TicketTable $item           The ticket object
 * @var string             $class_me       Class to add if it's assigned to me
 * @var string             $class_other    Class to add if it's assigned to someone else
 * @var bool               $prefix         Prefix with "Assigned to:"?
 * @var bool               $onlyToManagers Only show to category managers?
 */

extract(array_merge([
	'item'           => null,
	'class_me'       => 'fw-bolder',
	'class_other'    => '',
	'prefix'         => true,
	'onlyToManagers' => true,
], $displayData));

// Bail out on no or invalid data
if (empty($item) || !is_object($item) || !property_exists($item, 'priority'))
{
	return;
}

if (($item->assigned_to ?? 0) <= 0)
{
	return;
}

if ($onlyToManagers && !Permissions::isManager($item->catid))
{
	return;
}

$assignee = Permissions::getUser($item->assigned_to);
$class    = $assignee->id === Permissions::getUser()->id ? $class_me : $class_other;
?>
<?php if ($prefix): ?>
<strong><?= Text::_('COM_ATS_TICKETS_ASSIGNED_TO') ?></strong>:
<?php endif; ?>
<span class="<?= $class ?>">
	<?= $this->escape($assignee->name) ?> (<?= $this->escape($assignee->username) ?>)
</span>