<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\View\Users;

defined('_JEXEC') or die;

/**
 * User picker form: View
 *
 * @since  5.0.6
 */
class HtmlView extends \Joomla\Component\Users\Administrator\View\Users\HtmlView
{
	/**
	 * Override the toolbar manipulation; no such thing in the frontend
	 *
	 * @since 5.0.6
	 */
	protected function addToolbar()
	{
	}

}