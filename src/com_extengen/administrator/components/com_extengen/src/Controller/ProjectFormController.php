<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

/**
 * Controller for a single project form
 */
class ProjectFormController extends FormController
{
	/**
	 * Override constructor to indicate the right list-view
	 *
	 * Alternatieve: Add $this->applyReturnUrl(); See Nic's PostController + ReturnURLAware mixin
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
	{
		$this->view_list = 'projectforms';
		parent::__construct($config, $factory, $app, $input);
	}

	/**
	 * Method to run batch operations.
	 *
	 * @param   object|null  $model  The model.
	 *
	 * @return  boolean   True on success
	 */
	public function batch($model = null)
	{
		$this->checkToken();

		$model = $this->getModel('ProjectForm', '', array());

		// Preset the redirect
		$this->setRedirect(Route::_('index.php?option=com_extengen&view=projectforms' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}
}
