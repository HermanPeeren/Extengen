<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Controller\Mixin\ControllerEvents;
use Akeeba\Component\ATS\Administrator\Controller\Mixin\ReusableModels;
use Joomla\CMS\MVC\Controller\BaseController;

class CategoriesController extends BaseController
{
	use ControllerEvents;
	use ReusableModels;

	public function display($cachable = false, $urlparams = [])
	{
		$user     = $this->app->getIdentity();
		$cachable = (bool) $user->guest;

		$urlparams = array_merge([
			'catid'            => 'INT',
			'id'               => 'INT',
			'cid'              => 'ARRAY',
			'limit'            => 'UINT',
			'limitstart'       => 'UINT',
			'return'           => 'BASE64',
			'filter'           => 'STRING',
			'filter_order'     => 'CMD',
			'filter_order_Dir' => 'CMD',
			'filter-search'    => 'STRING',
			'status'           => 'ARRAY',
			'print'            => 'BOOLEAN',
			'lang'             => 'CMD',
			'Itemid'           => 'INT',
		], $urlparams);

		return parent::display($cachable, $urlparams);
	}

}