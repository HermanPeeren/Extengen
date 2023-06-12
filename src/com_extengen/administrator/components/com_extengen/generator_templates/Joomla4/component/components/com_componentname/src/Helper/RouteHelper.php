<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\Helper;

use Akeeba\Component\ATS\Site\Service\Category;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Language\Multilanguage;

defined('_JEXEC') or die;

abstract class RouteHelper
{
	private static $categories = [];

	public static function getCategoryRoute($catid, $language = 0)
	{
		$id    = ($catid instanceof CategoryNode) ? $catid->id : (int) $catid;
		$catid = ($catid instanceof CategoryNode) ? $catid : self::getCategory($id);

		if ($id < 1)
		{
			return '';
		}

		$qsp = [
			'option' => 'com_ats',
			'view'   => 'category',
			'id'     => $id,
			'catid'  => $catid->parent_id,
		];

		if (is_null($qsp['catid']))
		{
			unset($qsp['catid']);
		}

		if (empty($language) && ($catid instanceof CategoryNode))
		{
			$language = $catid->language;
		}

		if ($language && $language !== '*' && Multilanguage::isEnabled())
		{
			$qsp['lang'] = $language;
		}

		return 'index.php?' . http_build_query($qsp);
	}

	public static function getTicketRoute(int $id, ?int $catid = 0, $language = 0)
	{
		$qsp = [
			'option' => 'com_ats',
			'view'   => 'ticket',
			'id'     => $id,
		];

		if ($catid > 1)
		{
			$qsp['catid'] = $catid;
		}

		if ($language && $language !== '*' && Multilanguage::isEnabled())
		{
			$qsp['lang'] = $language;
		}

		return 'index.php?' . http_build_query($qsp);
	}

	private static function getCategory($id)
	{
		if (isset(self::$categories[$id]))
		{
			return self::$categories[$id];
		}

		$catService = new Category([]);

		self::$categories[$id] = $catService->get($id);

		return self::$categories[$id];
	}
}