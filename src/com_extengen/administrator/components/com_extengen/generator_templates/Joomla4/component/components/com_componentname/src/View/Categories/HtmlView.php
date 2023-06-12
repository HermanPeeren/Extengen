<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\View\Categories;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Administrator\View\Mixin\LoadAnyTemplate;
use Akeeba\Component\ATS\Site\View\Mixin\ModuleRenderAware;
use Akeeba\Component\ATS\Site\View\Mixin\CategoryFieldsAware;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\CategoriesView;
use Joomla\Registry\Registry;

class HtmlView extends CategoriesView
{
	use ModuleRenderAware;
	use LoadAnyTemplate;
	use CategoryFieldsAware;

	/**
	 * Language key for default page heading
	 *
	 * @var    string
	 * @since  5.0.0
	 */
	protected $pageHeading = 'COM_ATS_CATEGORIES_TITLE';

	/**
	 * Page parameters
	 *
	 * @var   Registry
	 * @since 5.0.0
	 */
	protected $params;

	/**
	 * Parent category
	 *
	 * @var   CategoryNode|null
	 * @since 5.0.0
	 */
	protected $parent;

	/**
	 * Prepares the document
	 *
	 * @return  void
	 *
	 * @since   5.0.0
	 */
	protected function prepareDocument()
	{
		$this->items = array_pop($this->items);
		$this->items = array_map([$this, 'processCategoryFieldsDisplay'], $this->items);

		// Set the document meta
		parent::prepareDocument();
	}
}