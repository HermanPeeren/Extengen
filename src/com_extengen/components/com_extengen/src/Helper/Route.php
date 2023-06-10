<?php
/**
 * @package     Joomla.Site
 * @subpackage  COM_EXTENGEN
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Yepr\Extengen\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Language\Multilanguage;

/**
 * extengen Component Route Helper
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  COM_EXTENGEN
 * @since       1.5
 */
abstract class Route
{
	/**
	 * Get the URL route for a extengen from a extengen ID, extengen category ID and language
	 *
	 * @param   integer  $id        The id of the extengen
	 * @param   integer  $catid     The id of the extengen's category
	 * @param   mixed    $language  The id of the language being used.
	 *
	 * @return  string  The link to the extengen
	 *
	 * @since   1.5
	 */
	public static function getextengenRoute($id, $catid, $language = 0)
	{
		// Create the link
		$link = 'index.php?option=COM_EXTENGEN&view=extengen&id=' . $id;

		if ($catid > 1)
		{
			$link .= '&catid=' . $catid;
		}

		if ($language && $language !== '*' && Multilanguage::isEnabled())
		{
			$link .= '&lang=' . $language;
		}

		return $link;
	}

	/**
	 * Get the URL route for a extengen category from a extengen category ID and language
	 *
	 * @param   mixed  $catid     The id of the extengen's category either an integer id or an instance of CategoryNode
	 * @param   mixed  $language  The id of the language being used.
	 *
	 * @return  string  The link to the extengen
	 *
	 * @since   1.5
	 */
	public static function getCategoryRoute($catid, $language = 0)
	{
		if ($catid instanceof CategoryNode)
		{
			$id = $catid->id;
		}
		else
		{
			$id = (int) $catid;
		}

		if ($id < 1)
		{
			$link = '';
		}
		else
		{
			// Create the link
			$link = 'index.php?option=COM_EXTENGEN&view=category&id=' . $id;

			if ($language && $language !== '*' && Multilanguage::isEnabled())
			{
				$link .= '&lang=' . $language;
			}
		}

		return $link;
	}
}
