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

use Joomla\CMS\Association\AssociationExtensionInterface;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\ServiceProviderInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;

use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\HTML\Registry;
use Yepr\Component\Extengen\Administrator\Extension\ExtengenComponent;
//use Yepr\Component\Extengen\Administrator\Helper\AssociationsHelper;
use Joomla\DI\Container;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;

/**
 * The Extengen service provider.
 */
return new class implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 */
	public function register(Container $container)
	{
		//$container->set(AssociationExtensionInterface::class, new AssociationsHelper());

		$container->registerServiceProvider(new CategoryFactory('\\Yepr\\Component\\Extengen'));
		$container->registerServiceProvider(new MVCFactory('\\Yepr\\Component\\Extengen'));
		$container->registerServiceProvider(new ComponentDispatcherFactory('\\Yepr\\Component\\Extengen'));
        $container->registerServiceProvider(new RouterFactory('\\Yepr\\Component\\Extengen'));

		$container->set(
			ComponentInterface::class,
			function (Container $container)
			{
				$component = new ExtengenComponent($container->get(ComponentDispatcherFactoryInterface::class));

				$component->setRegistry($container->get(Registry::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				$component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
				//$component->setAssociationExtension($container->get(AssociationExtensionInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

				return $component;
			}
		);
	}
};
