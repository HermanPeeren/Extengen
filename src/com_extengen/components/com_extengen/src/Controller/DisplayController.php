<?php
/**
 * @package     Extengen
 * @subpackage  Extengen component
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2022. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Extengen\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

/**
 * extengen Component Controller
 *
 * @since  1.0.0
 */
class DisplayController extends BaseController
{
	/**
	 * Constructor.
	 *
	 * @param   array                         $config   An optional associative array of configuration settings.
     *                                                  Recognized key values include 'name', 'default_task', 'model_path', and
     *                                                  'view_path' (this list is not meant to be comprehensive).
	 * @param   MVCFactoryInterface|null      $factory  The factory.
	 * @param   CMSApplication|null           $app      The JApplication for the dispatcher
	 * @param   \Joomla\CMS\Input\Input|null  $input    Input
	 *
	 * @since   1.0
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
	{
		parent::__construct($config, $factory, $app, $input);
	}

	/**
	 * Method to display a view.
	 *
     * @param   boolean  $cachable   If true, the view output will be cached.
     * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return  DisplayController    This object to support chaining.
	 *
	 * @since   1.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
		parent::display($cachable);

		return $this;
	}
}
