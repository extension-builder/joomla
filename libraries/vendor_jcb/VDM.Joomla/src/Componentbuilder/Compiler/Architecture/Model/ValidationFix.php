<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\Model;


use VDM\Joomla\Componentbuilder\Compiler\Builder\ValidationFix as ValidationFixRegistry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Model Validation Fix Class.
 *
 * Builds the statements an admin model runs to make a value the form gave it
 * fit what the database will take.
 *
 * @since 6.1.7
 */
final class ValidationFix
{
	/**
	 * The Validation Fix Builder Class.
	 *
	 * @var   ValidationFixRegistry
	 * @since 6.1.7
	 */
	protected ValidationFixRegistry $validationfix;

	/**
	 * Constructor.
	 *
	 * @param ValidationFixRegistry  $validationfix  The Validation Fix Builder Class.
	 *
	 * @since 6.1.7
	 */
	public function __construct(ValidationFixRegistry $validationfix)
	{
		$this->validationfix = $validationfix;
	}

	/**
	 * Build the validation fix statements of a view.
	 *
	 * Only a view that was found to need one gets it.
	 *
	 * @param   string  $view       The single view name.
	 * @param   string  $Component  The component name.
	 *
	 * @return  string  The statements, or nothing when the view needs none.
	 *
	 * @since   6.1.7
	 */
	public function get($view, $Component): string
	{
		$fix = '';
		if (ArrayHelper::check(
				$this->validationfix->get($view)
			))
		{
			$fix .= PHP_EOL . PHP_EOL . Indent::_(1) . "/**";
			$fix .= PHP_EOL . Indent::_(1)
				. " * Method to validate the form data.";
			$fix .= PHP_EOL . Indent::_(1) . " *";
			$fix .= PHP_EOL . Indent::_(1)
				. " * @param   Form   \$form   The form to validate against.";
			$fix .= PHP_EOL . Indent::_(1)
				. " * @param   array   \$data   The data to validate.";
			$fix .= PHP_EOL . Indent::_(1)
				. " * @param   string  \$group  The name of the field group to validate.";
			$fix .= PHP_EOL . Indent::_(1) . " *";
			$fix .= PHP_EOL . Indent::_(1)
				. " * @return  mixed  Array of filtered data if valid, false otherwise.";
			$fix .= PHP_EOL . Indent::_(1) . " *";
			$fix .= PHP_EOL . Indent::_(1) . " * @see     JFormRule";
			$fix .= PHP_EOL . Indent::_(1) . " * @see     JFilterInput";
			$fix .= PHP_EOL . Indent::_(1) . " * @since   12.2";
			$fix .= PHP_EOL . Indent::_(1) . " */";
			$fix .= PHP_EOL . Indent::_(1)
				. "public function validate(\$form, \$data, \$group = null)";
			$fix .= PHP_EOL . Indent::_(1) . "{";
			$fix .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " check if the not_required field is set";
			$fix .= PHP_EOL . Indent::_(2)
				. "if (isset(\$data['not_required']) && "
				. "Super_" . "__1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check(\$data['not_required']))";
			$fix .= PHP_EOL . Indent::_(2) . "{";
			$fix .= PHP_EOL . Indent::_(3)
				. "\$requiredFields = (array) explode(',',(string) \$data['not_required']);";
			$fix .= PHP_EOL . Indent::_(3)
				. "\$requiredFields = array_unique(\$requiredFields);";
			$fix .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " now change the required field attributes value";
			$fix .= PHP_EOL . Indent::_(3)
				. "foreach (\$requiredFields as \$requiredField)";
			$fix .= PHP_EOL . Indent::_(3) . "{";
			$fix .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__)
				. " make sure there is a string value";
			$fix .= PHP_EOL . Indent::_(4) . "if ("
				. "Super_" . "__1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check(\$requiredField))";
			$fix .= PHP_EOL . Indent::_(4) . "{";
			$fix .= PHP_EOL . Indent::_(5) . "//" . Line::_(__Line__, __Class__)
				. " change to false";
			$fix .= PHP_EOL . Indent::_(5)
				. "\$form->setFieldAttribute(\$requiredField, 'required', 'false');";
			$fix .= PHP_EOL . Indent::_(5) . "//" . Line::_(__Line__, __Class__)
				. " also clear the data set";
			$fix .= PHP_EOL . Indent::_(5) . "unset(\$data[\$requiredField]);";
			$fix .= PHP_EOL . Indent::_(4) . "}";
			$fix .= PHP_EOL . Indent::_(3) . "}";
			$fix .= PHP_EOL . Indent::_(2) . "}";
			$fix .= PHP_EOL . Indent::_(2)
				. "return parent::validate(\$form, \$data, \$group);";
			$fix .= PHP_EOL . Indent::_(1) . "}";
		}

		return $fix;
	}
}
