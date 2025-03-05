<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('script', 'com_extengen/admin-extengen-letter.js', array('version' => 'auto', 'relative' => true));
HTMLHelper::_('script', 'com_extengen/admin-project.js', array('version' => 'auto', 'relative' => true));

$app = Factory::getApplication();
$input = $app->input;

$assoc = Associations::isEnabled();

$this->ignore_fieldsets = array('item_associations');
$this->useCoreUI = true;

// In case of modal
$isModal = $input->get('layout') == 'modal' ? true : false;
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
?>
<form action="<?php echo Route::_('index.php?option=com_extengen&view=projectform&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="projectform-form" class="form-validate">

    <?php echo $this->getForm()->renderField('name'); ?>

	<div>

		<?php if (($this->item->id)>0): ?>
            <div class="row">
                <div class="col-md-12 btns">
                    <a class="btn btn-info"  data-bs-toggle="modal"  href="#ProjectFormModal">
						<?php echo Text::_('COM_EXTENGEN_BUTTON_PROJECTFORM_DIAGRAM'); ?>
                    </a>
                    <p>&nbsp;</p>
                </div>
            </div>
		<?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-12">
						<?php echo $this->getForm()->renderField('languageEntities'); ?>
                    </div>
                </div>
            </div>
        </div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php
if (($this->item->id)>0)
{
    // Make a forms-diagram
	echo HTMLHelper::_(
		'bootstrap.renderModal',
		'ProjectFormModal',
		array(
			'title'  => Text::_('COM_EXTENGEN_BUTTON_PROJECTFORM_DIAGRAM'),
			'url' => JUri::root() . "administrator/index.php?option=com_extengen&view=FormsDiagram&tmpl=component&projectform_id=" .  $this->item->id,
			'height' => "700",
			'width' => "700"
		)
    ); // todo: adjust height and width to screen
}
 ?>
