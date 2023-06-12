<?php
/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ATS\Site\View\Mixin;

defined('_JEXEC') or die;

use Akeeba\Component\ATS\Site\Helper\ModuleRenderHelper;
use Joomla\CMS\Document\Renderer\Html\ModuleRenderer;

trait ModuleRenderAware
{
	/**
	 * Render a module by name and returns the HTMLM content
	 *
	 * @param   string       $moduleName  The module to render, e.g. mod_example
	 * @param   array        $attribs     The attributes to use for module rendering.
	 * @param   string|null  $content     Optional module content (e.g. for the Custom HTML module)
	 *
	 * @return  string  The rendered module
	 *
	 * @throws \Exception
	 * @see     ModuleRenderer::render()  To understand how $attribs works.
	 * @since   5.0.0
	 */
	public function loadModule(string $moduleName, array $attribs = [], ?string $content = null): string
	{
		return ModuleRenderHelper::loadModule($moduleName, $attribs, $content);
	}

	/**
	 * Renders a module position and returns the HTML content
	 *
	 * @param   string  $position  The position name, e.g. "position-1"
	 * @param   array   $attribs   The attributes to use for module rendering.
	 *
	 * @return  string  The rendered module position
	 *
	 * @throws  \Exception
	 * @see     ModuleRenderer::render()  To understand how $attribs works.
	 * @since   5.0.0
	 */
	public function loadPosition(string $position, array $attribs = []): string
	{
		return ModuleRenderHelper::loadPosition($position, $attribs);
	}
}