<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\View\ProjectForm;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

use Joomla\CMS\Form\Form;

/**
 * View to edit a project.
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The Form object
	 *
	 * @var  Form
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * Display the view.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		$this->item = $this->get('Item');

		// If we are forcing a language in modal (used for associations).
		if ($this->getLayout() === 'modal' && $forcedLanguage = Factory::getApplication()->input->get('forcedLanguage', '', 'cmd'))
		{
			// Set the language field to the forcedLanguage and disable changing it.
			$this->form->setValue('language', null, $forcedLanguage);
			$this->form->setFieldAttribute('language', 'readonly', 'true');

			// Only allow to select categories with All language or with the forced language.
			$this->form->setFieldAttribute('catid', 'language', '*,' . $forcedLanguage);
		}

		$this->addToolbar();

		return parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user = Factory::getUser();
		$userId = $user->id;
		$isNew = ($this->item->id == 0);

		ToolbarHelper::title($isNew ? Text::_('COM_EXTENGEN_MANAGER_PROJECTFORM_NEW') : Text::_('COM_EXTENGEN_MANAGER_PROJECTFORM_EDIT'), 'project');

		// Since we don't track these assets at the item level, use the category id.
		$canDo = ContentHelper::getActions('com_extengen'); //, 'category', $this->item->catid

		// Build the actions for new and existing records.
		if ($isNew)
		{
			// For new records, check the create permission.
			//if ($isNew && (count($user->getAuthorisedCategories('com_extengen', 'core.create')) > 0))
			//{
				ToolbarHelper::apply('project.apply');

				ToolbarHelper::saveGroup(
					[
						['save', 'project.save'],
						['save2new', 'project.save2new']
					],
					'btn-success'
				);
			//}

			ToolbarHelper::cancel('project.cancel'); // TODO: I want 'Close' on the button, not 'Cancel'
		}
		else
		{
			// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
			//$itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

			$toolbarButtons = [];

			// Can't save the record if it's not editable
			//if ($itemEditable)
			//{
				ToolbarHelper::apply('project.apply');

				$toolbarButtons[] = ['save', 'project.save'];

				// We can save this record, but check the create permission to see if we can return to make a new one.
				if ($canDo->get('core.create'))
				{
					$toolbarButtons[] = ['save2new', 'project.save2new'];
				}
			//}

			// If checked out, we can still save
			if ($canDo->get('core.create'))
			{
				$toolbarButtons[] = ['save2copy', 'project.save2copy'];
			}

			ToolbarHelper::saveGroup(
				$toolbarButtons,
				'btn-success'
			);

			/*if (Associations::isEnabled() && ComponentHelper::isEnabled('com_associations'))
			{
				ToolbarHelper::custom('project.editAssociations', 'contract', 'contract', 'JTOOLBAR_ASSOCIATIONS', false, false);
			}*/

			ToolbarHelper::cancel('project.cancel', 'JTOOLBAR_CLOSE'); // TODO: I want 'Close' on the button, not 'Cancel'
		}

		ToolbarHelper::divider();
		ToolbarHelper::help('', false, 'https://yepr.nl');
	}
}
