<?php
/**
 * @package     Extengen

 * @subpackage  Extengen component
 * @version     0.8.0
 *
 * @copyright   Copyright (C) Yepr, Herman Peeren, 2023. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Yepr\Component\Extengen\Administrator\Rule;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormRule;

/**
 * Form Rule class for the Joomla Platform.
 */
class LetterRule extends FormRule
{
	/**
	 * The regular expression to use in testing a form field value.
	 *
	 * @var    string
	 */
	protected $regex = '^([a-z]+)$';

	/**
	 * The regular expression modifiers to use when testing a form field value.
	 *
	 * @var    string
	 */
	protected $modifiers = 'i';
}
