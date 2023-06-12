<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

$displayData = [
	'textPrefix' => 'COM_ATS_TICKETS',
	'formURL'    => 'index.php?option=com_ats&view=tickets',
	'icon'       => 'fa fa-ticket-alt',
];

$user = Factory::getApplication()->getIdentity();

if ($user->authorise('core.create', 'com_ats') || count($user->getAuthorisedCategories('com_ats', 'core.create')) > 0)
{
	$displayData['createURL'] = 'index.php?option=com_ats&task=ticket.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
