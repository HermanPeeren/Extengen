<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Field;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;

class TicketManagersField extends ListField
{
	protected $type = 'TicketManagers';

	protected function getInput()
	{
		$catField = $this->element['catfield'] ?? 'id';
		$catid    = $this->form->getValue((string) $catField);

		if (!isset($this->element['hideall']))
		{
			$this->addOption('COM_ATS_CATEGORY_ALL_MANAGERS', [
				'value' => 'all',
			]);
		}

		array_map(function ($o) {
			$this->addOption($o->name, [
				'value' => $o->id,
			]);
		}, Permissions::getManagers($catid));

		$html = parent::getInput();

		if (!$catid && !isset($this->element['hidetip']))
		{
			$html .= sprintf("<div class=\"mt-2 alert alert-info\">%s</div>", Text::_('COM_ATS_CATEGORY_NOTIFY_MANAGERS_SAVEBEFORE'));
		}

		return $html;
	}


}