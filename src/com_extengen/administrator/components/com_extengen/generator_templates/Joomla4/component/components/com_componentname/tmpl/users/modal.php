<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * User picker form: view template for modal dialog
 *
 * @since  5.0.6
 */

@ob_start();

require_once JPATH_ADMINISTRATOR . '/components/com_users/tmpl/users/modal.php';

$html = @ob_get_clean();
$replacement   = sprintf('action="%s"', \Joomla\CMS\Uri\Uri::getInstance()->toString());
$actionPattern = '#action\\s?=\\s?"([^"]*)"#i';

echo preg_replace($actionPattern, $replacement, $html, 1) ?? '';