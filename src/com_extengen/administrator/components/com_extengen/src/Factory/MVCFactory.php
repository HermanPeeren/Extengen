<?php

/**
 * @package     Extension Generator
 *
 * An MVCFactory that uses $container->buildSharedObject() constructor-injection
 * instead of standard Joomla\CMS\MVC\Factory\MVCFactory setter-injection
 *
 * Reason: If an object cannot function correctly without some dependencies, then those dependencies should be
 * injected with constructor-injection. Setter-injection could otherwise create incomplete objects. When using
 * constructor-injection, if a necessary dependency is missing, an exception will be thrown. You also don't need to
 * provide separate setters for the MVC-objects, nor for this MVC-factory, as they are resolved automatically by the
 * DI-container considering the constructor parameters. In the factory you don't have to specify the parameters for the
 * MVC-class: that is automatically done with reflection. So, a variable number of parameters are resolved automatically
 * and we not even need to specify whether it is a Model, View, Controller or Table.
 *
 * Also different from standard Joomla\CMS\MVC\Factory\MVCFactory: the Model, View, Controller or Table is placed in the
 * DI-container after creation, instead of being newly created at every call of the create...()-methods.
 *
 * BUT: now the DI-container as a whole is put into the factory. More pure, avoiding any Service Locator anti-pattern,
 * would be a sub-container that only has the dependencies that are needed for instantiating MVC- and Table-classes.
 * $config, $app, $input and $viewType have to be set into the DI-container before using them to create the model,
 * controller, view and table.
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
*/


namespace Yepr\Component\Extengen\Administrator\Service;

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\ControllerInterface;
use Joomla\CMS\MVC\Model\ModelInterface;
use Joomla\CMS\MVC\View\ViewInterface;
use Joomla\CMS\Table\Table;
use Joomla\DI\Container;
use Joomla\DI\Exception\DependencyResolutionException;
use Joomla\Input\Input;

// phpcs:disable PSR1.Files.SideEffects
\defined('JPATH_PLATFORM') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Factory to create MVC objects based on a namespace, using constructor-injection.
 *
 * @since  3.10.0
 */
class MVCFactory implements MVCFactoryInterface
{
    /**
     * The namespace to create the objects from.
     *
     * @var    string
     */
    private string $namespace;

	/**
	 * Container
	 *
	 * @var    Container
	 */
	private Container $container;

    /**
     * Constructor of this MVCFactory: injecting the container
     * The namespace must be like: Yepr\Component\Extengen
     *
     * @param   string  $namespace  The namespace
     */
    public function __construct(
		string $namespace,
		Container $container
    )
    {
        $this->namespace = $namespace;
		$this->container = $container;
    }

    /**
     * Method to load and return a controller object.
     *
     * @param   string                   $name    The name of the controller
     * @param   string                   $prefix  The controller prefix
     * @param   array                    $config  The configuration array for the controller
     * @param   CMSApplicationInterface  $app     The app
     * @param   Input                    $input   The input
     *
     * @return  object | false ControllerInterface
     * @throws  DependencyResolutionException if the object could not be built (due to missing information)
     */
    public function createController($name, $prefix, array $config, CMSApplicationInterface $app, Input $input)
    {
        // Clean the parameters
        $name   = preg_replace('/[^A-Z0-9_]/i', '', $name);
        $prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

        $className = $this->getClassName('Controller\\' . ucfirst($name) . 'Controller', $prefix);

        if (!$className) {
            return null;
        }

		// Set $config, $app and $input into the container
	    $this->container->set('ControllerConfig', $config);
	    $this->container->set('CMSApplicationInterface', $app);
	    $this->container->set('Input', $input);

	    // return the controller from the container
        return $this->container->buildSharedObject($className);
    }

    /**
     * Method to load and return a model object.
     *
     * @param   string  $name    The name of the model.
     * @param   string  $prefix  Optional model prefix.
     * @param   array   $config  Optional configuration array for the model.
     *
     * @return  object | false  ModelInterface
     * @throws  DependencyResolutionException if the object could not be built (due to missing information)
     */
    public function createModel($name, $prefix = '', array $config = [])
    {
        // Clean the parameters
        $name   = preg_replace('/[^A-Z0-9_]/i', '', $name);
        $prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

        if (!$prefix) {
            @trigger_error(
                sprintf(
                    'Calling %s() without a prefix is deprecated.',
                    __METHOD__
                ),
                E_USER_DEPRECATED
            );

            $prefix = Factory::getApplication()->getName();
        }

        $className = $this->getClassName('Model\\' . ucfirst($name) . 'Model', $prefix);

        if (!$className) {
            return null;
        }

	    // Set $config into the container
	    $this->container->set('ModelConfig', $config);

	    // return the model from the container
	    return $this->container->buildSharedObject($className);
    }

    /**
     * Method to load and return a view object.
     *
     * @param   string  $name     The name of the view.
     * @param   string  $prefix   Optional view prefix.
     * @param   string  $viewType Optional type of view.
     * @param   array   $config   Optional configuration array for the view.
     *
     * @return  object | false  ViewInterface
     * @throws  DependencyResolutionException if the object could not be built (due to missing information)
     */
    public function createView($name, $prefix = '', $viewType = '', array $config = [])
    {
        // Clean the parameters
        $name     = preg_replace('/[^A-Z0-9_]/i', '', $name);
        $prefix   = preg_replace('/[^A-Z0-9_]/i', '', $prefix);
        $viewType = preg_replace('/[^A-Z0-9_]/i', '', $viewType);

        if (!$prefix) {
            @trigger_error(
                sprintf(
                    'Calling %s() without a prefix is deprecated.',
                    __METHOD__
                ),
                E_USER_DEPRECATED
            );

            $prefix = Factory::getApplication()->getName();
        }

        $className = $this->getClassName('View\\' . ucfirst($name) . '\\' . ucfirst($viewType) . 'View', $prefix);

        if (!$className) {
            return null;
        }

	    // Set $config and $viewType into the container
	    $this->container->set('ViewConfig', $config);
	    $this->container->set('ViewType', $viewType);

	    // return the view from the container
	    return $this->container->buildSharedObject($className);
    }

    /**
     * Method to load and return a table object.
     *
     * @param   string  $name    The name of the table.
     * @param   string  $prefix  Optional table prefix.
     * @param   array   $config  Optional configuration array for the table.
     *
     * @return  object | false  Table
     * @throws  DependencyResolutionException if the object could not be built (due to missing information)
     */
    public function createTable($name, $prefix = '', array $config = [])
    {
        // Clean the parameters
        $name   = preg_replace('/[^A-Z0-9_]/i', '', $name);
        $prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

        if (!$prefix) {
            @trigger_error(
                sprintf(
                    'Calling %s() without a prefix is deprecated.',
                    __METHOD__
                ),
                E_USER_DEPRECATED
            );

            $prefix = Factory::getApplication()->getName();
        }

        $className = $this->getClassName('Table\\' . ucfirst($name) . 'Table', $prefix)
            ?: $this->getClassName('Table\\' . ucfirst($name) . 'Table', 'Administrator');

        if (!$className) {
            return null;
        }

	    // Set $config into the container
	    $this->container->set('TableConfig', $config);

	    // return the table from the container
	    return $this->container->buildSharedObject($className);
    }

    /**
     * Returns a standard classname, if the class doesn't exist null is returned.
     *
     * @param   string  $suffix  The suffix
     * @param   string  $prefix  The prefix
     *
     * @return  string|null  The class name
     */
    protected function getClassName(string $suffix, string $prefix)
    {
        if (!$prefix) {
            $prefix = Factory::getApplication();
        }

        $className = trim($this->namespace, '\\') . '\\' . ucfirst($prefix) . '\\' . $suffix;

        if (!class_exists($className)) {
            return null;
        }

        return $className;
    }
}
