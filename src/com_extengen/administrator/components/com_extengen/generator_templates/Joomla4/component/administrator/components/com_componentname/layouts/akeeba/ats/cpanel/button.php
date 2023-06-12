<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * @var array  $displayData The array passed from the layout renderer
 * @var string $color       The Bootstrap background color of the link e.g. primary, success etc
 * @var string $link        The button's link
 * @var string $icon        The button's icon class
 * @var string $label       The button's label (will be passed through Text::_())
 * @var string $width       The width of the button
 */

extract($displayData);

$color      = $color ?? 'primary';
$width      = $width ?? '10em';

switch ($color)
{
	default:
		$textColor = 'white';
		break;


	case 'light':
		$textColor = 'dark';
		break;

	case 'warning':
		$textColor  = 'dark';
		break;
}
?>
<a class="text-center align-self-stretch btn btn-outline-<?= $color ?> border-0" style="width: <?= $width ?>"
   href="<?= Route::_($link) ?>">
	<div class="bg-<?= $color ?> text-<?= $textColor ?> d-block text-center p-3 h2">
		<span class="<?= $icon ?>"></span>
	</div>
	<div>
		<?= Text::_($label) ?>
	</div>
</a>
