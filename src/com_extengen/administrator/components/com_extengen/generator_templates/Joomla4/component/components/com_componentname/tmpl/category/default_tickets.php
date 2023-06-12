<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 *
 * @var \Akeeba\Component\ATS\Site\View\Category\HtmlView $this
 * @var TicketTable[]                                     $tickets
 * @var array                                             $ticketOptions
 */

use Akeeba\Component\ATS\Administrator\Table\TicketTable;
use Joomla\CMS\Language\Text;

$ticketOptions = $ticketOptions ?? [];

?>
<table class="table table-striped table-hover" role="presentation">
	<caption class="visually-hidden">
		<?= Text::sprintf('COM_ATS_TICKETS_LBL_TABLE_CAPTION', $this->escape($this->category->title)) ?>
	</caption>
	<tbody>
	<?php
	foreach ($tickets as $ticket)
	{
		echo $this->loadAnyTemplate('category/default_ticket', false, array_merge(
			$ticketOptions, ['ticket' => $ticket]
		));
	}
	?>
	</tbody>
</table>
