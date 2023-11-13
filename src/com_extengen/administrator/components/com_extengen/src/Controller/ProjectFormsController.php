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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Project forms list controller class.
 */
class ProjectFormsController extends AdminController
{
	/**
     * Constructor.
     *
     * @param   array                         $config   An optional associative array of configuration settings.
     *                                                  Recognized key values include 'name', 'default_task', 'model_path', and
     *                                                  'view_path' (this list is not meant to be comprehensive).
     * @param   MVCFactoryInterface|null      $factory  The factory.
     * @param   CMSApplication|null           $app      The JApplication for the dispatcher
     * @param   Input|null                    $input    Input
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The name of the model.
	 * @param   string  $prefix  The prefix for the PHP class name.
	 * @param   array   $config  Array of configuration parameters.
	 *
	 * @return  BaseDatabaseModel
	 */
	public function getModel($name = 'ProjectForms', $prefix = '', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}
