<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\Users\Administrator\Model\UsersModel as JoomlaUsersModel;

/**
 * User picker form: Model
 *
 * @since  5.0.6
 */
class UsersModel extends JoomlaUsersModel
{
	public function __construct($config = [], MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		// Load the necessary languages
		Factory::getApplication()->getLanguage()->load('com_users', JPATH_ADMINISTRATOR);

		// Make sure we can load Joomla's Search Tools form from com_users' backend
		Form::addFormPath(JPATH_ADMINISTRATOR . '/components/com_users/forms');
	}
}