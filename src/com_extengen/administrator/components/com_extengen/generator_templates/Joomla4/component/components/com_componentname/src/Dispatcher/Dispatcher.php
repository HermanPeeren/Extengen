<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Dispatcher;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Dispatcher\Dispatcher as AdminDispatcher;
use Joomla\CMS\Component\ComponentHelper;

class Dispatcher extends AdminDispatcher
{
	/** @inheritdoc */
	protected $commonMediaKeys = ['preset:com_ats.frontend'];

	/** @inheritdoc */
	protected $defaultController = 'categories';

	/** @inheritdoc */
	protected $viewMap = [
		'categories'      => 'categories',
		'tickets'         => 'category',
		'latests'         => 'latest',
		'mies'            => 'my',
		'newticket'       => 'new',
		'newtickets'      => 'new',
		'assignedtickets' => 'assigned',
		'assignedticket'  => 'assigned',
	];

	/** @inheritdoc  */
	protected function applyViewAndController(): void
	{
		parent::applyViewAndController();

		$view       = $this->input->get('view');

		// The new view is an alias to the ticket view
		if ($view == 'new')
		{
			$this->input->set('view', 'ticket');
			$this->input->set('controller', 'ticket');
			$this->input->set('layout', 'edit');
			$this->input->set('id', null);
		}
	}

	protected function onBeforeDispatch(): void
	{
		if (ComponentHelper::getParams('com_ats')->get('loadCustomCss', 0) == 1)
		{
			$this->commonMediaKeys = ['preset:com_ats.frontend.styled'];
		}

		parent::onBeforeDispatch();
	}


}