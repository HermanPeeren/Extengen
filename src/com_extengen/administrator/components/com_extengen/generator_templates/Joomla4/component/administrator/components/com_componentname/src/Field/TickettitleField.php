<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Field;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Site\Service\Category;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class TickettitleField extends TextField
{
	/** @inheritdoc */
	protected $layout = 'akeeba.ats.field.ticket_title';

	/** @inheritdoc */
	protected function getLayoutData()
	{
		$params  = $this->getCategoryParams();
		$cParams = ComponentHelper::getParams('com_ats');

		$searchMethod = $params->get('instantsearch_method') ?: $cParams->get('instantsearch_method', 'finder');
		$hasSearch    = $searchMethod != 'none';

		if ($hasSearch)
		{
			$filter    = $params->get('instantsearch_filter') ?: $cParams->get('instantsearch_filter', '');
			$Itemid    = $params->get('instantsearch_menu') ?: $cParams->get('instantsearch_menu', '0');
			$searchUrl = 'index.php?option=com_finder&view=search&q={search}&tmpl=component' . ($Itemid ? sprintf('&Itemid=%u', (int) $Itemid) : '');
			if (is_numeric($filter))
			{
				$searchUrl .= '&f=' . (int) $filter;
			}
			elseif (is_array($filter))
			{
				$searchUrl .= array_map(function ($f) {
					if (empty($f) || ($f < 0))
					{
						return '';
					}

					return '&f[]=' . (int) $f;
				}, ArrayHelper::toInteger($filter));
			}

			$searchEngineUrl = $params->get('instantsearch_url') ?: $cParams->get('instantsearch_url', 'https://duckduckgo.com/search.html?site={site}&q={search}');

			Factory::getApplication()->getDocument()->addScriptOptions('ats.instantsearch.options', [
				'method' => $searchMethod,
				'url'    => ($searchMethod == 'url') ? $searchEngineUrl : Route::_($searchUrl, false),
				'host'   => Uri::getInstance()->toString(['host']),
			]);
		}

		return array_merge([
			'hasSearch' => $hasSearch,
		], parent::getLayoutData());
	}

	/**
	 * Get the parameters of the currently selected category
	 *
	 * @return  Registry
	 *
	 * @since   5.0.0
	 */
	private function getCategoryParams(): Registry
	{
		$catid = $this->form->getValue('catid', null);

		if (empty($catid))
		{
			return new Registry();
		}

		$catService = new Category([]);
		$category   = $catService->get($catid);

		if (!is_object($category) || !($category instanceof CategoryNode))
		{
			return new Registry();
		}

		$params = $category->params;

		if ($params instanceof Registry)
		{
			return $params;
		}

		return new Registry($params);
	}
}