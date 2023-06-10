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
<form action="<?php echo Route::_('index.php?option=com_extengen&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="project-form" class="form-validate">

    <?php echo $this->getForm()->renderField('name'); ?>

	<div>
		<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'details')); ?>

		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'entities', Text::_('COM_EXTENGEN_HEADING_ENTITIES')); ?>

        <?php if (($this->item->id)>0): ?>
        <div class="row">
            <div class="col-md-12 btns">
                <a class="btn btn-info"  data-bs-toggle="modal"  href="#ERDModal">
				<?php echo Text::_('COM_EXTENGEN_BUTTON_ERD'); ?>
                </a>
                <p>&nbsp;</p>
            </div>
        </div>
        <?php endif; ?>

		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="col-md-12">
						<?php echo $this->getForm()->renderField('datamodel'); ?>
					</div>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>


        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'pages', Text::_('COM_EXTENGEN_HEADING_PAGES')); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-12">
                        <?php echo $this->getForm()->renderField('pages'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>


        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'pages', Text::_('COM_EXTENGEN_HEADING_EXTENSIONS')); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-12">
                        <?php echo $this->getForm()->renderField('extensions'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>


		<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php
if (($this->item->id)>0)
{
	echo HTMLHelper::_(
		'bootstrap.renderModal',
		'ERDModal',
		array(
			'title'  => Text::_('COM_EXTENGEN_BUTTON_ERD'),
			'url' => JUri::root() . "administrator/index.php?option=com_extengen&view=ERD&tmpl=component&project_id=" .  $this->item->id,
			'height' => "700",
			'width' => "700"
		)
    ); // todo: adjust height and width to screen
}
 ?>