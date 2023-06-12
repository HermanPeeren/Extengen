<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Controller;

use Akeeba\Component\ATS\Site\Controller\Mixin\TicketStateFilterAware;

defined('_JEXEC') or die;

class CategoryController extends CategoriesController
{
	use TicketStateFilterAware;

	public function display($cachable = false, $urlparams = [])
	{
		$this->fixMissingStatusFilterInPost();

		// Migration from old menu items
		$catId = $this->app->input->getInt('id', $this->app->input->getInt('category'));
		$this->app->input->set('id', $catId);

		return parent::display($cachable, $urlparams);
	}
}