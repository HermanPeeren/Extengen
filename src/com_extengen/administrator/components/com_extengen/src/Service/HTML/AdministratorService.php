<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\Service\HTML;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/**
 * Extengen HTML class.
 */
class AdministratorService
{
	/**
	 * Get the associated language flags
	 *
	 * @param   integer  $projectid  The item id to search associations
	 *
	 * @return  string  The language HTML
	 *
	 * @throws  \Exception
	 */
	public function association($projectid)
	{
		// Defaults
		$html = '';

		// Get the associations
		if ($associations = Associations::getAssociations('com_extengen', '#__extengen_projects', 'com_extengen.item', $projectid, 'id', null))
		{
			foreach ($associations as $tag => $associated)
			{
				$associations[$tag] = (int) $associated->id;
			}

			// Get the associated project items
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('c.id, c.name as title')
				->select('l.sef as lang_sef, lang_code')
				->from('#__extengen_projects as c')
				->select('cat.title as category_title')
				->join('LEFT', '#__categories as cat ON cat.id=c.catid')
				->where('c.id IN (' . implode(',', array_values($associations)) . ')')
				->where('c.id != ' . $projectid)
				->join('LEFT', '#__languages as l ON c.language=l.lang_code')
				->select('l.image')
				->select('l.title as language_title');
			$db->setQuery($query);

			try
			{
				$items = $db->loadObjectList('id');
			}
			catch (\RuntimeException $e)
			{
				throw new \Exception($e->getMessage(), 500, $e);
			}

			if ($items)
			{
				foreach ($items as &$item)
				{
					$text = strtoupper($item->lang_sef);
					$url = Route::_('index.php?option=com_extengen&task=project.edit&id=' . (int) $item->id);
					$tooltip = '<strong>' . htmlspecialchars($item->language_title, ENT_QUOTES, 'UTF-8') . '</strong><br>'
						. htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8') . '<br>' . Text::sprintf('JCATEGORY_SPRINTF', $item->category_title);
					$classes = 'badge badge-secondary';

					$item->link = '<a href="' . $url . '" title="' . $item->language_title . '" class="' . $classes . '">' . $text . '</a>'
						. '<div role="tooltip" id="tip' . (int) $item->id . '">' . $tooltip . '</div>';
				}
			}

			$html = LayoutHelper::render('joomla.content.associations', $items);
		}

		return $html;
	}


    /**
     * Show the featured/not-featured icon.
     *
     * @param   integer  $value      The featured value.
     * @param   integer  $i          Id of the item.
     * @param   boolean  $canChange  Whether the value can be changed or not.
     *
     * @return  string	The anchor tag to toggle featured/unfeatured projects.
     */
    public function featured($value, $i, $canChange = true)
    {
        // Array of image, task, title, action
        $states = [
            0 => ['unfeatured', 'projects.featured', 'COM_CONTACT_UNFEATURED', 'JGLOBAL_ITEM_FEATURE'],
            1 => ['featured', 'projects.unfeatured', 'JFEATURED', 'JGLOBAL_ITEM_UNFEATURE'],
        ];
        $state = ArrayHelper::getValue($states, (int) $value, $states[1]);
        $icon = $state[0] === 'featured' ? 'star featured' : 'star';

        if ($canChange) {
            $html = '<a href="#" onclick="return Joomla.listItemTask(\'cb' . $i . '\',\'' . $state[1] . '\')" class="tbody-icon'
                . ($value == 1 ? ' active' : '') . '" aria-labelledby="cb' . $i . '-desc">'
                . '<span class="fas fa-' . $icon . '" aria-hidden="true"></span></a>'
                . '<div role="tooltip" id="cb' . $i . '-desc">' . Text::_($state[3]);
        } else {
            $html = '<a class="tbody-icon disabled' . ($value == 1 ? ' active' : '')
                . '" title="' . Text::_($state[2]) . '"><span class="fas fa-' . $icon . '" aria-hidden="true"></span></a>';
        }

        return $html;
    }
}
