<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\View\Mixin;

use Exception;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

trait PageMetaAware
{
	/**
	 * Set the page's HTML meta information
	 *
	 * @throws Exception
	 * @since  5.0.0
	 */
	private function setPageMeta()
	{
		/** @var Registry $params */
		$params         = Factory::getApplication()->getParams();

		$this->setDocumentTitle($params->get('page_title', ''));

		if ($params->get('menu-meta_description'))
		{
			$this->document->setDescription($params->get('menu-meta_description'));
		}

		if ($params->get('robots'))
		{
			$this->document->setMetaData('robots', $params->get('robots'));
		}
	}
}