<?php
/**
 * @package    {{ package_name }}
 *
 * @author     {{ author_name }} <{{ author_email }}>
 * @copyright  {{ copyright }}
 * @license    {{ license }}
 * @link       {{ author_url }}
 */

defined('_JEXEC') or die;

use Joomla\CMS\Installer\Adapter\PackageAdapter;

/**
 * {{ package_name }} script file.
 *
 * @package     {{ package_name }}
 * @see         https://docs.joomla.org/Manifest_files#Script_file
 */
class Pkg_{{ projectName }}InstallerScript extends \Joomla\CMS\Installer\InstallerScript
{
	/**
	 * Constructor
	 *
	 * @param   PackageAdapter  $adapter  The object responsible for running this script
	 */
	public function __construct(PackageAdapter $adapter) {}

	/**
	 * Called before any type of action
	 *
	 * @param   string          $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   PackageAdapter  $adapter  The object responsible for running this script
	 *
	 * @return  boolean         True on success
	 */
	public function preflight($route, PackageAdapter $adapter) {}

	/**
	 * Called after any type of action
	 *
	 * @param   string          $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   PackageAdapter  $adapter  The object responsible for running this script
	 *
	 * @return  boolean         True on success
	 */
	public function postflight($route, PackageAdapter $adapter) {}

	/**
	 * Called on installation
	 *
	 * @param   PackageAdapter  $adapter  The object responsible for running this script
	 *
	 * @return  boolean         True on success
	 */
	public function install(PackageAdapter $adapter) {}

	/**
	 * Called on update
	 *
	 * @param   PackageAdapter  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function update(PackageAdapter $adapter) {}

	/**
	 * Called on uninstallation
	 *
	 * @param   PackageAdapter  $adapter  The object responsible for running this script
	 */
	public function uninstall(PackageAdapter $adapter) {}
}
