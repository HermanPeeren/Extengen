<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Field;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\UserField;

/**
 * User picker field for the frontend.
 *
 * Forked off Joomla's user field, uses a custom view in the ATS component instead of com_users' backend.
 *
 * @since  5.0.6
 */
class TicketUserField extends UserField
{
	public $type = 'TicketUser';

	/**
	 * Layout to render
	 *
	 * @var   string
	 * @since 3.5
	 */
	protected $layout = 'akeeba.ats.field.user';

	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$catId          = $this->form->getValue('catid') ?: null;
			$this->readonly = !Permissions::isManager($catId);
		}

		return $return;
	}


}