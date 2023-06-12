<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
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
 * View class togive information about the Extension Generator project.
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

		ExtengenHelper::addSubmenu('info');
		$this->addToolbar();
		$this->sidebar = \JHtmlSidebar::render();

		echo "<h2>Information about the Extension Generator project</h2>";
		echo '<p>Planned featureswill be adjusted in the process.</p>';
		echo '<h3>version 0.8.0</h3>';
		echo '<p>Current version - under construction.</p>';
		echo '<p>Plain component backend generated.</p>';
		echo '<p>Todo:
					<ul>
							<li>form filters</li>
							<li>editFields</li>
					</ul>
			  </p>';
		echo '<h3>version 0.8.1</h3>';
		echo '<p>Minimal Viable Product</p>';
		echo '<p>features:
					<ul>
							<li>plain component frontend generated</li>
							<li>fill entities & attributes dropdowns in extengen backend</li>
							<li>install generated component in current Joomla 4 site (for testing)</li>
					</ul>
			  </p>';
		echo '<h3>version 0.9.0</h3>';
		echo '<p>features:
					<ul>
							<li>generator-generator: define and adjust generators</li>
							<li>project forms generator: define and adjust project forms</li>
					</ul>
			  </p>';
		echo '<h3>version 1.0.0</h3>';
		echo '<p>features:
					<ul>
							<li>toggle Joomla core features in generated extension: categories, tags, versioning, workflow, pagination, custom fields, ordering, access control, language associations, routing/alias, action logs, finder</li>
							<li>submenu for this component</li>
							<li>toggle translations and choose translation service</li>
							<li>override translations</li>
							<li>add categories to projects and generators</li>
							<li>add dashboard page type</li>
							<li>update-site for extengen; update from github</li>
					</ul>
			  </p>';
		echo '<h3>version 1.1.0</h3>';
		echo '<p>features:
					<ul>
							<li>one-to-many relations</li>
							<li>add page-type detail with indices (for the multiple files)</li>
					</ul>
			  </p>';
		echo '<h3>version 1.2.0</h3>';
		echo '<p>features:
					<ul>
							<li>save versions on Git(hub)</li>
							<li>update extengen info page(s) from github instead of shipping with component</li>
					</ul>
			  </p>';
		echo '<h3>version 1.3.0</h3>';
		echo '<p>features:
					<ul>
							<li>generate modules</li>
							<li>generate plugins</li>
							<li>CLI and API applications</li>
					</ul>
			  </p>';
		echo '<h3>version 1.4.0</h3>';
		echo '<p>features:
					<ul>
							<li>import & export projects</li>
							<li>migrate older version projects</li>
					</ul>
			  </p>';
		echo '<h3>version 2.0.0</h3>';
		echo '<p>features:
					<ul>
							<li>Initial WordPress generator</li>
					</ul>
			  </p>';
		echo '<h3>version 2.1.0</h3>';
		echo '<p>features:
					<ul>
							<li>Joomla4 + Doctrine generator</li>
					</ul>
			  </p>';
		echo '<h3>version 2.2.0</h3>';
		echo '<p>features:
					<ul>
							<li>possibility to use Event Sourcing in projects</li>
							<li>Joomla4 + Prooph Event Sourcing generator</li>
					</ul>
			  </p>';
		echo '<p>&nbsp</p>';
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
		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(Text::_('COM_EXTENGEN_MANAGER_INFO'), 'generators');

		HTMLHelper::_('sidebar.setAction', 'index.php?option=com_extengen');
	}
}
