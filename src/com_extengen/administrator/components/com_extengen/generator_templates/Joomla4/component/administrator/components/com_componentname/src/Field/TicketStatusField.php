<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Field;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Helper\ComponentParams;
use Joomla\CMS\Form\Field\ListField;

class TicketStatusField extends ListField
{
	protected $type = 'TicketStatus';

	protected function getInput()
	{
		foreach (ComponentParams::getStatuses() as $value => $description)
		{
			$this->addOption($description, [
				'value' => $value,
			]);
		}

		return parent::getInput();
	}
}