<?php
/**
 * @package     Extesion Generator

 * @subpackage  Extengen component
 * @version     0.9.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\View\GenerateForm;

defined('_JEXEC') or die;

// Get Twig: use the Composer autoloader todo: use the DIC and add this service
require_once JPATH_LIBRARIES . '/yepr/vendor/autoload.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Yepr\Component\Extengen\Administrator\Helper\ExtengenHelper;
use Joomla\CMS\Pagination\Pagination;


/**
 * View class for a list of projects.
 */
class HtmlView extends BaseHtmlView
{

	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  void
	 */
	public function display($tpl = null): void
	{
        $model = $this->getModel();

		// Get the project_id and put it in the model
		// Todo: this must be done in the (display)controller and probably best via UserState
		$projectId = Factory::getApplication()->input->getInt('projectform_id');
		$model->setProjectFormId($projectId);

				$model->generate();
		$log = $model->log;
		$text = implode("<br />\n", $log);
		echo '<h2>Generation log of these project forms</h2>';
		echo '<p>' . $text . '</p>';

		//parent::display($tpl);
	}

}
