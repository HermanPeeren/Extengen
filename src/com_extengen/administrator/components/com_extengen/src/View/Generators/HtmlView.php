<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\View\Generators;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Yepr\Component\Extengen\Administrator\Helper\ExtengenHelper;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\MVC\View\GenericDataException;


/**
 * View class to manage generators.
 */
class HtmlView extends BaseHtmlView
{

	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  void
     * @throws Genericdataexception
	 */
	public function display($tpl = null): void
	{

		ExtengenHelper::addSubmenu('generators');
		$this->addToolbar();
		$this->sidebar = \JHtmlSidebar::render();

		echo "<h2>Generators will be listed here</h2>";
		echo '<p>TODO! Generators can be copied and adjusted. New generators can be defined and... generated.</p>';
		echo '<p>Planned for version 0.9</p>';
		echo '<p>&nbsp</p>';
		echo '<p>At the moment, version 0.8, only one generator: <b>Joomla4</b>.</p>';
		echo '<p>This template is not yet easily adjustable.</p>';
		echo '<p>&nbsp</p>';
		echo '<p>To migrate from the current static generator to dynamic generators, the following steps will be taken:
				<ul>
				<li>add the necessary tables: generators, templates (with a generator_id and mapping-info)</li>
				<li>N.B.: a generator is linked to a specific project type. If you change the project type, the mapping might have to be adjusted. Current project type = ER1 (= based on eJSL).</li>
				<li>copy the current TWIG template files to the db templates table</li>
				<li>edit the templates for a project</li>
				<li>add mapping per template. Ideally the variable names are taken from the template and the possible values by showing the possibilities in the AST (some kind of AST viewer)</li>
				<li>create something general for the files that are directly generated from the AST, not with a template. As a first step, these PHP code files could be left as they are now and being incorporated in the project. But I would like to have some forms here...</li>
				<li>add choices in project for all available generators for this project type</li>
				<li>test if it works and switch to the new system</li>
				</ul></p>';

		//parent::display($tpl);
	}



	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */
	protected function addToolbar()
	{
		//$canDo = ContentHelper::getActions('com_extengen', 'category', $this->state->get('filter.category_id'));

		$user  = Factory::getUser();

		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(Text::_('COM_EXTENGEN_MANAGER_GENERATORS'), 'gnerators');

		//if ($canDo->get('core.create') || count($user->getAuthorisedCategories('com_extengen', 'core.create')) > 0)
		//{
			$toolbar->addNew('generator.add');
		//}

		//if ($canDo->get('core.edit.state'))
		//{
			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('fa fa-globe')
				->buttonClass('btn btn-info')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();

			//$childBar->publish('projects.publish')->listCheck(true);

			//$childBar->unpublish('projects.unpublish')->listCheck(true);

			//$childBar->archive('projects.archive')->listCheck(true);

			if ($user->authorise('core.admin'))
			{
				$childBar->checkin('generators.checkin')->listCheck(true);
			}

			//if ($this->state->get('filter.published') != -2)
			//{
				$childBar->trash('extengen.trash')->listCheck(true);
			//}
		//}

		$toolbar->popupButton('batch')
			->text('JTOOLBAR_BATCH')
			->selector('collapseModal')
			->listCheck(true);

		//if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		//{
			$toolbar->delete('projects.delete')
				->text('JTOOLBAR_EMPTY_TRASH')
				->message('JGLOBAL_CONFIRM_DELETE')
				->listCheck(true);
		//}

		if ($user->authorise('core.admin', 'com_extengen') || $user->authorise('core.options', 'com_extengen'))
		{
			$toolbar->preferences('com_extengen');
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('', false, 'https://yepr.nl');

		HTMLHelper::_('sidebar.setAction', 'index.php?option=com_extengen');
	}
}
