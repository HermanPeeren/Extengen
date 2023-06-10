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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Yepr\Component\Extengen\Site\Helper\RouteHelper;
use Joomla\Registry\Registry;

/**
 * Content Component HTML Helper
 */
class Icon
{
    /**
     * The application
     *
     * @var    CMSApplication
     */
    private $application;

    /**
     * Service constructor
     *
     * @param   CMSApplication  $application  The application
     */
    public function __construct(CMSApplication $application)
    {
        $this->application = $application;
    }

    /**
     * Method to generate a link to the create item page for the given category
     *
     * @param   object    $category  The category information
     * @param   Registry  $params    The item parameters
     * @param   array     $attribs   Optional attributes for the link
     *
     * @return  string  The HTML markup for the create item link
     */
    public static function create($category, $params, $attribs = [])
    {
        $uri = Uri::getInstance();

        $url = 'index.php?option=com_extengen&task=project.add&return=' . base64_encode($uri) . '&id=0&catid=' . $category->id;

        $text = LayoutHelper::render('joomla.content.icons.create', ['params' => $params, 'legacy' => false]);

        // Add the button classes to the attribs array
        if (isset($attribs['class'])) {
            $attribs['class'] .= ' btn btn-primary';
        } else {
            $attribs['class'] = 'btn btn-primary';
        }

        $button = HTMLHelper::_('link', Route::_($url), $text, $attribs);

        $output = '<span class="hasTooltip" title="' . HTMLHelper::_('tooltipText', 'COM_EXTENGEN_CREATE_PROJECT') . '">' . $button . '</span>';

        return $output;
    }

    /**
     * Display an edit icon for the project.
     *
     * This icon will not display in a popup window, nor if the project is trashed.
     * Edit access checks must be performed in the calling code.
     *
     * @param   object    $project  The project information
     * @param   Registry  $params   The item parameters
     * @param   array     $attribs  Optional attributes for the link
     * @param   boolean   $legacy   True to use legacy images, false to use icomoon based graphic
     *
     * @return  string   The HTML for the project edit icon.
     */
    public static function edit($project, $params, $attribs = [], $legacy = false)
    {
        $user = Factory::getUser();
        $uri  = Uri::getInstance();

        // Ignore if in a popup window.
        if ($params && $params->get('popup')) {
            return '';
        }

        // Ignore if the state is negative (trashed).
        if ($project->published < 0) {
            return '';
        }

        // Set the link class
        $attribs['class'] = 'dropdown-item';

        // Show checked_out icon if the project is checked out by a different user
        if (property_exists($project, 'checked_out')
            && property_exists($project, 'checked_out_time')
            && $project->checked_out > 0
            && $project->checked_out != $user->get('id')) {
            $checkoutUser = Factory::getUser($project->checked_out);
            $date         = HTMLHelper::_('date', $project->checked_out_time);
            $tooltip      = Text::_('JLIB_HTML_CHECKED_OUT') . ' :: ' . Text::sprintf('COM_EXTENGEN_CHECKED_OUT_BY', $checkoutUser->name)
                . ' <br /> ' . $date;

            $text = LayoutHelper::render('joomla.content.icons.edit_lock', ['tooltip' => $tooltip, 'legacy' => $legacy]);

            $output = HTMLHelper::_('link', '#', $text, $attribs);

            return $output;
        }

        if (!isset($project->slug)) {
            $project->slug = "";
        }

        $projectUrl = RouteHelper::getProjectRoute($project->slug, $project->catid, $project->language);
        $url        = $projectUrl . '&task=project.edit&id=' . $project->id . '&return=' . base64_encode($uri);

        if ($project->published == 0) {
            $overlib = Text::_('JUNPUBLISHED');
        } else {
            $overlib = Text::_('JPUBLISHED');
        }

        if (!isset($project->created)) {
            $date = HTMLHelper::_('date', 'now');
        } else {
            $date = HTMLHelper::_('date', $project->created);
        }

        if (!isset($created_by_alias) && !isset($project->created_by)) {
            $author = '';
        } else {
            $author = $project->created_by_alias ?: Factory::getUser($project->created_by)->name;
        }

        $overlib .= '&lt;br /&gt;';
        $overlib .= $date;
        $overlib .= '&lt;br /&gt;';
        $overlib .= Text::sprintf('COM_EXTENGEN_WRITTEN_BY', htmlspecialchars($author, ENT_COMPAT, 'UTF-8'));

        $icon = $project->published ? 'edit' : 'eye-slash';

        if (strtotime($project->publish_up) > strtotime(Factory::getDate())
            || ((strtotime($project->publish_down) < strtotime(Factory::getDate())) && $project->publish_down != Factory::getDbo()->getNullDate())) {
            $icon = 'eye-slash';
        }

        $text = '<span class="hasTooltip fa fa-' . $icon . '" title="'
            . HTMLHelper::tooltipText(Text::_('COM_EXTENGEN_EDIT_PROJECT'), $overlib, 0, 0) . '"></span> ';
        $text .= Text::_('JGLOBAL_EDIT');

        $attribs['title'] = Text::_('COM_EXTENGEN_EDIT_PROJECT');
        $output           = HTMLHelper::_('link', Route::_($url), $text, $attribs);

        return $output;
    }
}