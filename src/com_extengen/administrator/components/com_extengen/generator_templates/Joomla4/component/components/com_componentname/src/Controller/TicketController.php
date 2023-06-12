<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\Controller\Mixin\ReturnURLAware;
use Akeeba\Component\ATS\Administrator\Controller\TicketController as AdminTicketController;
use Akeeba\Component\ATS\Administrator\Helper\ComponentParams;
use Akeeba\Component\ATS\Administrator\Helper\Permissions;
use Akeeba\Component\ATS\Administrator\Table\TicketTable;
use Akeeba\Component\ATS\Administrator\View\Post\HtmlView;
use Akeeba\Component\ATS\Site\Model\CategoryModel;
use Akeeba\Component\ATS\Site\Service\Category;
use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use RuntimeException;

class TicketController extends AdminTicketController
{
	use ReturnURLAware
	{
		ReturnURLAware::getRedirectToItemAppend as applyReturnURLOnItemAppend;
	}

	protected $view_item = 'ticket';

	/**
	 * Change the assigned user on a ticket.
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public function ajax_set_assigned(): void
	{
		if (!$this->checkToken('post', false))
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);

			return;
		}

		$id       = $this->input->getInt('id');
		$assigned = $this->input->getInt('assigned');

		if (!$id)
		{
			echo new JsonResponse(null, Text::_('COM_ATS_TICKETS_ERR_INVALID_TICKET_ID'), true);

			return;
		}

		/** @var CategoryModel $model */
		$model = $this->getModel();
		/** @var TicketTable $ticket */
		$ticket = $model->getTable('Ticket', 'Administrator');

		if (!$ticket->load($id))
		{
			echo new JsonResponse(null, Text::_('COM_ATS_TICKETS_ERR_INVALID_TICKET_ID'), true);

			return;
		}

		if (!Permissions::canAssignTickets($ticket->catid) || (($assigned != 0) && !Permissions::canBeAssignedTickets($ticket->catid, $assigned)))
		{
			echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);

			return;
		}

		$ticket->assigned_to = $assigned;

		if (!$ticket->store())
		{
			echo new JsonResponse(null, $ticket->getError(), true);

			return;
		}

		echo new JsonResponse();
	}

	/**
	 * Change the status of a ticket.
	 *
	 * @return  void
	 * @throws  Exception
	 * @since   5.0.0
	 */
	public function ajax_set_status(): void
	{
		if (!$this->checkToken('post', false))
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);

			return;
		}

		$id          = $this->input->getInt('id');
		$status      = $this->input->getCmd('status');
		$allStatuses = ComponentParams::getStatuses();

		if (!$id)
		{
			echo new JsonResponse(null, Text::_('COM_ATS_TICKETS_ERR_INVALID_TICKET_ID'), true);

			return;
		}

		if (!array_key_exists($status, $allStatuses))
		{
			echo new JsonResponse(null, Text::_('COM_ATS_TICKETS_ERR_INVALID_STATE'), true);

			return;
		}

		/** @var CategoryModel $model */
		$model = $this->getModel();
		/** @var TicketTable $ticket */
		$ticket = $model->getTable('Ticket', 'Administrator');

		if (!$ticket->load($id))
		{
			echo new JsonResponse(null, Text::_('COM_ATS_TICKETS_ERR_INVALID_TICKET_ID'), true);

			return;
		}

		if (!Permissions::isManager($ticket->catid))
		{
			echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);

			return;
		}

		$ticket->status = $status;

		if (!$ticket->store())
		{
			echo new JsonResponse(null, $ticket->getError(), true);

			return;
		}

		echo new JsonResponse();
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   5.0.1
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		$ret    = $this->applyReturnURLOnItemAppend($recordId, $urlVar);
		$Itemid = $this->input->get('Itemid', null);

		/** @var TicketTable $ticket */
		$ticket = $this->getModel()->getTable('Ticket', 'Administrator');
		$ticket->load($recordId);
		$catid = $ticket->catid;

		if ($catid && is_numeric($catid))
		{
			$ret .= '&catid=' . $catid;
		}

		if (is_numeric($Itemid) && ($Itemid > 0))
		{
			$ret .= '&Itemid=' . urlencode((int) $Itemid);
		}

		return $ret;
	}

	protected function onBeforeSave(): void
	{
		/**
		 * When submitting a ticket from a New Ticket page with a specific category ID we have to remove the catid field
		 * from the form. If we try to hide it, or make it disabled or make it readonly Joomla for whatever reason DOES
		 * NOT submit it with the form data. So, all of the above result in the same problem: we are here trying to do
		 * a save but the form does not have the bloody category ID at all!
		 *
		 * However, since the menu item does have a category ID we are indeed given a catid value in the request query
		 * parameters which, however, is not used by JForm. So we have to explicitly catch that case and edit the POSTed
		 * `jform` array, adding the `catid` key to it populated by the query request parameter. Because somehow that
		 * makes sense for this pile of dog poop that's Joomla's form handling :@
		 */
		$catid = $this->input->getInt('catid');
		$jForm = $this->input->get('jform', [], 'raw');

		if (is_array($jForm) && is_numeric($catid) && !empty($catid) && !isset($jForm['catid']))
		{
			$jForm['catid'] = $catid;
			$this->input->post->set('jform', $jForm);
		}
	}

	/**
	 * Runs after the save task (Save & Close button).
	 *
	 * Used to apply the return URL.
	 *
	 * @return  void
	 * @since   5.0.0
	 */
	protected function onAfterSave(): void
	{
		$this->applyReturnUrl();
	}

	/** @inheritdoc */
	protected function onBeforeMain()
	{
		// If there is no ticket ID it's the new ticket page.
		$id    = max(0, $this->input->getInt('id', 0));
		$catid = null;

		// Direct menu item links for New Ticket pass the category ID as a the id variable
		$layout = $this->input->get('layout', 'default');

		if ($layout === 'newticket')
		{
			$catid = $id;
			$id    = null;
		}

		if (empty($id) && $layout !== 'newticket')
		{
			$layout = 'edit';

			$this->input->set('layout', 'edit');
		}

		/**
		 * Menu items of the New Ticket type use `id` to communicate the category ID for backwards compatibility with
		 * sites upgraded from ATS 4 and earlier. The form, however, uses `catid` for the category ID and `id` for the
		 * ticket ID. So we need to do a magic conversion of the input to avoid errors.
		 */
		if ($layout === 'newticket')
		{
			$this->input->set('id', $id);
			$this->input->set('catid', $catid);

			$this->checkAddEditPermissions($id);
		}

		// If it's an edit or new ticket page let's check if we are allowed to do that.
		if ($layout === 'edit')
		{
			$this->checkAddEditPermissions($id);

			// If we have a catid let's use it
			$catid          = $this->input->getInt('catid', null);
			$view           = $this->getView();
			$view->category = $catid ? (new Category([]))->get($catid) : null;
			$model          = $this->getModel();
			$model->setState('ticket.catid', $catid);
		}

		parent::onBeforeMain();

		// Apply the return URL on edit views
		/** @var HtmlView $view */
		$view            = $this->getView();
		$view->returnUrl = $this->getReturnUrl();

		/** @var TicketTable $ticket */
		$ticket = $this->getModel()->getItem();

		$permissions = Permissions::getTicketPrivileges($ticket);

		if ($ticket->id && !($permissions['view'] ?? false))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	/**
	 * Check if the current user is allowed to edit the specified ticket
	 *
	 * @param   int|null  $id  The ticket ID. Null to check the category permissions instead.
	 *
	 * @throws  Exception
	 * @since   5.0.0
	 */
	private function checkAddEditPermissions(?int $id): void
	{
		// We are editing an existing ticket. Are we allowed to?
		if (!empty($id))
		{
			/** @var TicketTable $ticket */
			$ticket = $this->getModel()->getTable();

			if (!$ticket->load($id))
			{
				throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			$permissions = Permissions::getTicketPrivileges($ticket);

			if (!$permissions['edit'])
			{
				throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
			}

			return;
		}

		// This is a new ticket. Let's check if this is possible.
		$catId = max($this->input->getInt('catid', 0), 0);

		if (!$catId)
		{
			// No catid? Set the newticket layout.
			$this->input->set('layout', 'newticket');

			return;
		}

		// We have a category ID. Are we allowed to create tickets in it?
		$permissions = Permissions::getAclPrivileges($catId);

		if (!$permissions['core.create'])
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}
}