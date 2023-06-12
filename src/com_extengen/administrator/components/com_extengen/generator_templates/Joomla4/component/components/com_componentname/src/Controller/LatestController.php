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
use Akeeba\Component\ATS\Site\Controller\Mixin\TicketStateFilterAware;
use Akeeba\Component\ATS\Site\Model\CategoryModel;
use Joomla\CMS\MVC\Controller\BaseController;

class LatestController extends BaseController
{
	use ReusableModels
	{
		ReusableModels::getModel as reusableGetModel;
	}
	use ControllerEvents;
	use TicketStateFilterAware;

	public function display($cachable = false, $urlparams = [])
	{
		$this->fixMissingStatusFilterInPost();

		/** @var CategoryModel $model */
		$model = $this->getModel();
		$view  = $this->getView();

		/**
		 * IMPORTANT! DO NOT REMOVE getState().
		 *
		 * This is required to initialise the state in the model before we start modifying it. Otherwise all of our
		 * modifications will be overwritten by the model's populateState() method. This would end up displaying all
		 * tickets, not just the latest open ones...
		 */
		$model->getState();
		$model->setState('filter.published', 1);
		$model->setState('filter.status', ['O']);
		$model->setState('filter.latestWorkaround', 1);
		$model->setState('list.ordering', 'modified');
		$model->setState('list.direction', 'ASC');

		$view->setModel($model, true);

		return parent::display($cachable, $urlparams);
	}

	public function getModel($name = 'Category', $prefix = '', $config = [])
	{
		if ($name == 'latest')
		{
			$name = 'category';
		}

		return $this->reusableGetModel($name, $prefix, $config);
	}


}