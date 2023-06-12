<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var \Akeeba\Component\ATS\Site\View\Ticket\HtmlView $this */
?>
<div class="ats ats-nonewtickets">
	<?= $this->loadPosition('ats-top') ?>
	<?= $this->loadPosition('ats-newticket-top') ?>

	<?= $this->loadPosition('ats-replyarea-overlay') ?>
	<?= $this->loadPosition('ats-nonewtickets') ?>

	<?= $this->loadPosition('ats-offline') ?>

	<?= $this->loadPosition('ats-newticket-bottom') ?>
	<?= $this->loadPosition('ats-bottom') ?>
</div>