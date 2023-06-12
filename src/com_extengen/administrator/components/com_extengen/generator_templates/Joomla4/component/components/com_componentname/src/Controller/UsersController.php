<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;

/**
 * User picker form: Controller
 *
 * @since  5.0.6
 */
class UsersController extends \Joomla\Component\Users\Administrator\Controller\UsersController
{
	/**
	 * Constructor.
	 *
	 * @param   array                    $config   An optional associative array of configuration settings.
	 * @param   MVCFactoryInterface      $factory  The factory.
	 * @param   CMSApplication           $app      The CMSApplication for the dispatcher
	 * @param   \Joomla\CMS\Input\Input  $input    Input
	 *
	 * @throws \Exception
	 * @see    BaseController
	 * @since  5.0.6
	 */
	public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
		// Load the necessary language files
		$app->getLanguage()->load('com_users', JPATH_ADMINISTRATOR);
		$app->getLanguage()->load('joomla', JPATH_ADMINISTRATOR);

		parent::__construct($config, $factory, $app, $input);
	}

	/**
	 * Execute a task by triggering a method in the derived class.
	 *
	 * @param   string  $task  The task to perform. If no matching task is found, the '__default' task is executed, if
	 *                         defined.
	 *
	 * @return  mixed   The value returned by the called method.
	 *
	 * @throws  \Exception
	 * @since   5.0.6
	 */
	public function execute($task)
	{
		// Make sure the user is authorised to display the user selection interface
		$validAuthCode   = $this->app->getSession()->get('com_ats.users.authorisation_code', null);
		$currentAuthCode = $this->input->get('authorisation', null);

		if ($validAuthCode === null || $validAuthCode !== $currentAuthCode)
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		return parent::execute($task);
	}
}