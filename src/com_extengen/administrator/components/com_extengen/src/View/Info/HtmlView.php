<?php
/**
 <li>@package     Extengen

 <li>@subpackage  Extengen component
 <li>@version     0.8.0
 *
 <li>@copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 <li>@license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\View\Info;

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
 <li>View class togive information about the Extension Generator project.
 */
class HtmlView extends BaseHtmlView
{

	/**
	 <li>Method to display the view.
	 *
	 <li>@param   string  $tpl  A template file to load. [optional]
	 *
	 <li>@return  void
     <li>@throws Genericdataexception
	 */
	public function display($tpl = null): void
	{

		ExtengenHelper::addSubmenu('info');
		$this->addToolbar();
		$this->sidebar = \JHtmlSidebar::render();
        
        $info = <<<INFO
        <h2>Extengen</h2>
        <p>Extension generator as Joomla extension, model based on eJSL (JooMDD), with projectional editor</p>.

    <p>First the JooMDD model was ported to Jetbrain's MPS (eJSL-MPS) and based on that structure this was turned into a 
    Joomla extension, with HTML forms as input of the AST.</p>

<h2>Information about the Extension Generator project</h2>
<p>19-4-2025</p>

<p>At the moment mainly working on getting the meta-level complete, using forms:</p>
<ul>
<li>Generator-generator: define and adjust generators.</li>
<li>Project forms generator: define and adjust project forms.</li>

<p>Still in the phase to get the first official release out. So be warned: the Extension Generator is still not production-ready.</p>

<h3>version 0.9.0</h3>
<p>If the model and the generator can both be created/edited, using forms, then the model and generator will be much easier
to adjust. That's why I postponed all kinds of changes in model and generator until after this version.</p>


<h3>version 1.0.0</h3>
    <p>With this version basic Joomla components will be easy to make.</p>

    <p>Features of model and generator, that will be added via the meta-level:</p>
<ul>
<li>toggle Joomla core features in generated extension: categories, tags, versioning, workflow, pagination, 
custom fields, ordering, access control, language associations, routing/alias, action logs, finder</li>
    <li>automatic junction table for n:n relations</li>
    <li>submenu for this component</li>
    <li>toggle translations and choose translation service; override translations</li>
    <li>add dashboard page type</li>
    <li>update-site for extengen; update from github</li>
</ul>
<h3>future version features</h3>
<ul>
    <li>import & export projects</li>
    <li>migrate older version projects</li>
    <li>save versions of the model and generator on Git(hub)</li>
    <li>add page-type detail with indices (for the multiple files)</li>
<li>update extengen info page(s) from github instead of shipping with component</li>
        <li>generate Joomla modules</li>
        <li>generate Joomla plugins</li>
        <li>Joomla CLI and API applications</li>
        <li>Initial WordPress generator</li>
        <li>Joomla + Doctrine generator</li>
        <li>possibility to use Event Sourcing in projects</li>
        <li>Joomla + Prooph Event Sourcing generator</li>
</ul>

INFO;

        echo $info;


		//parent::display($tpl);
	}

	/**
	 <li>Add the page title and toolbar.
	 *
	 <li>@return  void
	 */
	protected function addToolbar()
	{
		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(Text::_('COM_EXTENGEN_MANAGER_INFO'), 'generators');

		HTMLHelper::_('sidebar.setAction', 'index.php?option=com_extengen');
	}
}
