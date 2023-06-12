<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Controller\Mixin;

defined('_JEXEC') or die;

trait TicketStateFilterAware
{
	/**
	 * Fix the missing status[] filter in POST requests.
	 *
	 * We ar using a multiselect joomla-fancy-select field. When you clear all of its options (therefore telling it to
	 * display all records, unfiltered by status) it does NOT send any data in the POST request. This causes Joomla to
	 * revert to the last ticket status filter state set in the user session. Therefore you can never remove this
	 * filter.
	 *
	 * This method detects the POST requests with no ticket status filter and sets an empty array for the status query
	 * parameter in the POST data to fix this issue.
	 *
	 * @return  void
	 * @since   5.0.0
	 */
	protected function fixMissingStatusFilterInPost(): void
	{
		if ($this->input->getMethod() !== 'POST')
		{
			return;
		}

		$postData = $this->input->post->getArray();

		if (!empty($postData['status'] ?? null))
		{
			return;
		}

		$this->input->set('status', []);
	}
}