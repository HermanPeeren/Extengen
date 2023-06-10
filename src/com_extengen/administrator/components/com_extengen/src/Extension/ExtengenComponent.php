<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\Extension;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Yepr\Component\Extengen\Administrator\Service\HTML\AdministratorService;
use Yepr\Component\Extengen\Administrator\Service\HTML\Icon;
use Psr\Container\ContainerInterface;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;

/**
 * Component class for com_extengen.
 */
class ExtengenComponent extends MVCComponent
implements BootableExtensionInterface, CategoryServiceInterface, AssociationServiceInterface, RouterServiceInterface
{
	use CategoryServiceTrait;
	use AssociationServiceTrait;
	use HTMLRegistryAwareTrait;
    use RouterServiceTrait;

	/**
	 * Booting the extension. This is the function to set up the environment of the extension like
	 * registering new class loaders, etc.
	 *
	 * If required, some initial set up can be done from services of the container, eg.
	 * registering HTML services.
	 *
	 * @param   ContainerInterface  $container  The container
	 *
	 * @return  void
	 */
	public function boot(ContainerInterface $container)
	{
		$this->getRegistry()->register('extengenadministrator', new AdministratorService);
        $this->getRegistry()->register('extengenicon', new Icon($container->get(SiteApplication::class)));
	}

    /**
     * Adds Count Items for Category Manager.
     *
     * @param   \stdClass[]  $items    The category objects
     * @param   string       $section  The section
     *
     * @return  void
     */
    public function countItems(array $items, string $section)
    {
        try {
            $config = (object) [
                'related_tbl'   => $this->getTableNameForSection($section),
                'state_col'     => 'published',
                'group_col'     => 'catid',
                'relation_type' => 'category_or_group',
            ];

            ContentHelper::countRelations($items, $config);
        } catch (\Exception $e) {
            // Ignore it
        }
    }


    /**
	 * Returns the table for the count items functions for the given section.
	 *
	 * @param   string  $section  The section
	 *
	 * @return  string|null
	 */
	protected function getTableNameForSection(string $section = null)
	{
		return ($section === 'category' ? 'categories' : 'extengen_projects');

	}

    /**
     * Returns the state column for the count items functions for the given section.
     *
     * @param   string  $section  The section
     *
     * @return  string|null
     */
    protected function getStateColumnForSection(string $section = null)
    {
        return 'published';
    }

}

