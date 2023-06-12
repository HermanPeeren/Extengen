<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\NumberField;

class TimespentField extends NumberField
{
	protected $layout = 'akeeba.ats.fields.timespent';

	/** @inheritdoc  */
	protected function getLayoutPaths()
	{
		return array_merge(parent::getLayoutPaths(), [
			(Factory::getApplication()->isClient('site') ? JPATH_SITE : JPATH_ADMINISTRATOR) .
				'/components/com_ats/layouts',
			(Factory::getApplication()->isClient('site') ? JPATH_ADMINISTRATOR : JPATH_SITE) .
				'/components/com_ats/layouts',
		]);
	}


}