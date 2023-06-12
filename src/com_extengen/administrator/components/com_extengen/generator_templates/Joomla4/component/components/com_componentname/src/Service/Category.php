<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Categories\Categories;

class Category extends Categories
{
	public function __construct($options)
	{
		$options = array_merge($options, [
			'extension'  => 'com_ats',
			'table'      => '#__ats_tickets',
			'field'      => 'catid',
			'key'        => 'id',
			'statefield' => 'enabled',
		]);

		parent::__construct($options);
	}

}