<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\View\ProjectForms;

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
class HtmlViewOLD extends BaseHtmlView
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

		ExtengenHelper::addSubmenu('projectforms');
		$this->addToolbar();
		$this->sidebar = \JHtmlSidebar::render();

		echo "<h2>Project Forms will be listed here</h2>";
		echo '<p>A project form is the nested set of forms (with 1 root form) to define the project. It is so to say the DSL in which the model is written. The current (static) project form is the project form. We will make a project <b>type</b>, that indicates what subform will be used in this project. In that way we can define different subforms and hence different "modelling languages".</p>';
		echo '<p>TODO! Project forms can be copied and adjusted. New project forms can be defined and... generated.</p>';
		echo '<p>Planned for version 0.9</p>';
		echo '<p>&nbsp</p>';
		echo '<p>At the moment, version 0.8, only one project form: <b>ER1</b>.</p>';
		echo '<p>This is a nested set of forms, with momentarily  as root: <b>project.xml</b>, and specifically the fieldset "entities" in that (which should be ported to a subform), with the three subforms: datamodel, pages & extensions.</p>';

		echo "<h2>The current ER1 project forms:</h2>";
		$dir = \JUri::root() . "/administrator/components/com_extengen/src/View/ProjectForms/";
		echo '<p><img src="' . $dir .'projectforms_ER1.png" /></p>';
		echo '<p>&nbsp</p>';
		echo '<p>To migrate from the current static project form to dynamic project forms, the following steps will be taken:
				<ul>
				<li>keep the current forms under project.xml (so I can for now keep the same project data)</li>
				<li>copy the current project forms to a subfolder (er1) of forms and add a new root-form (er1)</li>
				<li>if everything works: add the data of the current example project to the new forms and kill the old forms</li>
				<li>make a test-project-form in which this new project subform can be tested</li>
				<li>get the AST from the project = from the new root subtemplate down. Adjust all generators, js, etc to be sure the right variables are selected</li>
				<li>generate new subforms, with 1 project root, in a subfolder of forms (or in db?)</li>
				<li>first project to generate will be the same as the current root-project (to test the results)</li>
				</ul></p>';


		echo '<p>&nbsp</p>';

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
