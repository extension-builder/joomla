<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    1st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller;


use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;


/**
 * Api Controller Get Model Class.
 *
 * Builds the getModel method of both API controllers of a view. The model
 * names of a view are explicit, so the content type is mapped onto them and
 * never inflected into a model name Joomla has to guess.
 *
 * @since 6.1.7
 */
final class GetModel
{
	/**
	 * Get the model mapping code of the API controllers.
	 *
	 * @param   string  $nameSingleCode  The single code name of the view.
	 * @param   string  $nameListCode    The list code name of the view.
	 *
	 * @return  string  The get model method body.
	 * @since   6.1.7
	 */
	public function get(string $nameSingleCode, string $nameListCode): string
	{
		$code = [];

		$code[] = PHP_EOL . Indent::_(2) . "//" . Line::_(__LINE__, __CLASS__)
			. " The model names of this view are explicit, the content type is never inflected.";
		$code[] = Indent::_(2)
			. "if (\$name !== '' && strtolower((string) \$name) === \$this->contentType)";
		$code[] = Indent::_(2) . "{";
		$code[] = Indent::_(3) . "\$name = '" . $nameListCode . "';";
		$code[] = Indent::_(2) . "}";
		$code[] = Indent::_(2) . "else";
		$code[] = Indent::_(2) . "{";
		$code[] = Indent::_(3) . "\$name = '" . $nameSingleCode . "';";
		$code[] = Indent::_(2) . "}";
		$code[] = PHP_EOL . Indent::_(2)
			. "return parent::getModel(\$name, \$prefix, \$config);";

		return implode(PHP_EOL, $code);
	}
}
