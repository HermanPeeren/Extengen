<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Controller\TicketsController as AdminTicketsController;

class TicketsController extends AdminTicketsController
{
	/**
	 * Respect the return URL after making a ticket private.
	 *
	 * @return  void
	 * @since   5.0.0
	 */
	protected function onAfterMakeprivate()
	{
		$this->applyReturnUrl();
	}

	/**
	 * Respect the return URL after making a ticket public.
	 *
	 * @return  void
	 * @since   5.0.0
	 */
	protected function onAfterMakepublic()
	{
		$this->applyReturnUrl();
	}

	/**
	 * Respect the return URL after publishing a ticket.
	 *
	 * @return  void
	 * @since   5.0.0
	 */
	protected function onAfterPublish()
	{
		$this->applyReturnUrl();
	}

	/**
	 * Respect the return URL after unpublishing a ticket.
	 *
	 * @return  void
	 * @since   5.0.0
	 */
	protected function onAfterUnpublish()
	{
		$this->applyReturnUrl();
	}

	/**
	 * Respect the return URL after closing a ticket.
	 *
	 * @return  void
	 * @since   5.0.0
	 */
	protected function onAfterClose()
	{
		$this->applyReturnUrl();
	}

	/**
	 * Respect the return URL after reopening a ticket.
	 *
	 * @return  void
	 * @since   5.0.0
	 */
	protected function onAfterReopen()
	{
		$this->applyReturnUrl();
	}
}