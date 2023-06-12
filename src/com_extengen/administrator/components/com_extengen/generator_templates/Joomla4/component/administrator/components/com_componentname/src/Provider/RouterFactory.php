<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Administrator\Provider;

defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class RouterFactory implements ServiceProviderInterface
{
	/**
	 * The module namespace
	 *
	 * @var     string
	 *
	 * @since   5.0.0
	 */
	private $namespace;

	/**
	 * Router factory constructor.
	 *
	 * @param   string  $namespace  The namespace
	 *
	 * @since   5.0.0
	 */
	public function __construct(string $namespace)
	{
		$this->namespace = $namespace;
	}

	/**
	 * @inheritDoc
	 */
	public function register(Container $container)
	{
		$container->set(
			RouterFactoryInterface::class,
			function (Container $container) {
				return new \Akeeba\Component\ATS\Administrator\Router\RouterFactory(
					$this->namespace,
					$container->get(DatabaseInterface::class),
					$container->get(MVCFactoryInterface::class),
					$container->get(CategoryFactoryInterface::class)
				);
			}
		);
	}
}