<?php
defined('_JEXEC') or die;
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */
use Joomla\CMS\Language\Text;

?>
<button type="button" class="btn btn-secondary" onclick="document.getElementById('batch-category-id').value='';document.getElementById('batch-access').value='';document.getElementById('batch-language-id').value='';document.getElementById('batch-user-id').value='';document.getElementById('batch-tag-id').value=''" data-dismiss="modal">
	<?php echo Text::_('JCANCEL'); ?>
</button>
<button type="submit" class="btn btn-success" onclick="Joomla.submitbutton('extengen.batch');">
	<?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
