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
use VDM\Joomla\Utilities\ArrayHelper;


/**
 * Model Validation Fix Class.
 * 
 * Builds server-side conditional requirements from the same normalized
 * definitions used by the administrator form script.
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
			$fix .= $this->conditions($view);
			$fix .= PHP_EOL . Indent::_(2)
				. "return parent::validate(\$form, \$data, \$group);";
			$fix .= PHP_EOL . Indent::_(1) . "}";
		}

		return $fix;
	}

	/**
	 * Render conditional requirements without trusting browser-supplied field names.
	 *
	 * @param   string  $view  The single view name.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	protected function conditions(string $view): string
	{
		$groups = $this->validationfix->getConditions($view);
		if ($groups === [])
		{
			return '';
		}

		$code = PHP_EOL . Indent::_(2) . '$conditionGroups = ' . $this->export($groups) . ';';
		$code .= PHP_EOL . <<<'PHP'
		// The browser's not_required list is informational, never an authority.
		$conditionData = $data;
		$conditionStored = [];
		$recordId = (int) ($data['id'] ?? $this->getState($this->getName() . '.id', 0));
		if ($recordId > 0)
		{
			$stored = $this->getItem($recordId);
			if ($stored === false || $stored === null)
			{
				return false;
			}
			$conditionStored = (array) $stored;
			$conditionData = array_replace($conditionStored, $conditionData);
		}
		$conditionPresent = static function ($value): bool
		{
			return $value !== null && $value !== '' && $value !== [];
		};
		$conditionEquals = static function ($value, $option): bool
		{
			// Selection values arrive as DOM strings; numeric/boolean options use JS equality.
			if (is_numeric($option) || $option === 'true' || $option === 'false')
			{
				if ($value === null)
				{
					return false;
				}
				$number = $option === 'true' ? 1 : ($option === 'false' ? 0 : (float) $option);
				if (is_bool($value) || (is_string($value) && trim($value) === ''))
				{
					return (float) $value === (float) $number;
				}
				return is_numeric($value) && (float) $value === (float) $number;
			}
			return is_scalar($value) && (string) $value === (string) $option;
		};
		$conditionMatch = static function ($value, array $rule) use ($conditionPresent, $conditionEquals): bool
		{
			$behavior = $rule['behavior'];
			$options = $rule['options'];
			if ($behavior >= 1 && $behavior <= 3)
			{
				if ($options !== [])
				{
					foreach ($options as $option)
					{
						$equal = $conditionEquals($value, $option);
						// Preserve the browser's OR across options, including Is Not.
						if ($behavior === 2 ? !$equal : $equal)
						{
							return true;
						}
					}
					return false;
				}
				$present = $conditionPresent($value);
				if ($behavior === 2)
				{
					return !$present;
				}
				return $present && !($behavior === 3 && $rule['user'] && $conditionEquals($value, '0'));
			}
			if ($behavior === 4 || $behavior === 5)
			{
				return $behavior === 4 ? $conditionPresent($value) : !$conditionPresent($value);
			}
			if (!is_scalar($value) && $value !== null)
			{
				return false;
			}
			$value = (string) $value;
			if ($behavior >= 6 && $behavior <= 9)
			{
				$keywords = $options['keywords'] ?? [];
				if ($keywords === [])
				{
					return $value === 'error';
				}
				$all = $behavior === 6 || $behavior === 8;
				if ($behavior === 8 || $behavior === 9)
				{
					$value = StringHelper::strtolower($value);
				}
				foreach ($keywords as $keyword)
				{
					$found = strpos($value, $keyword) !== false;
					if ($all ? !$found : $found)
					{
						return !$all;
					}
				}
				return $all;
			}
			// JavaScript length counts UTF-16 code units, including surrogate pairs.
			$length = StringHelper::strlen($value) + preg_match_all('/[\x{10000}-\x{10FFFF}]/u', $value);
			$expected = (int) (($options['length'] ?? 0) ?: 5);
			switch ($behavior)
			{
				case 10:
					return $length >= $expected;
				case 11:
					return $length <= $expected;
				case 12:
					return $length == $expected;
			}
			return false;
		};
		$conditionalRequired = [];
		foreach ($conditionGroups as $conditionGroup)
		{
			foreach ($conditionGroup['targets'] as $target)
			{
				$conditionalRequired[$target] = true;
			}
		}
		foreach ($conditionGroups as $conditionGroup)
		{
			$matched = true;
			foreach ($conditionGroup['matches'] as $rule)
			{
				// Unsupported definitions cannot relax a required field.
				if (!$rule['supported'])
				{
					continue 2;
				}
				// ACL-denied selectors cannot change applicability through discarded input.
				$disabled = strtolower((string) $form->getFieldAttribute($rule['name'], 'disabled', '', $group));
				$filter = strtolower((string) $form->getFieldAttribute($rule['name'], 'filter', '', $group));
				$available = $form->getFieldAttribute($rule['name'], 'name', null, $group) !== null;
				$values = !$available || in_array($disabled, ['true', '1', 'disabled'], true) || $filter === 'unset'
					? $conditionStored : $conditionData;
				$value = array_key_exists($rule['name'], $values)
					? $values[$rule['name']]
					: $form->getFieldAttribute($rule['name'], 'default', null, $group);
				if ($rule['checkbox'])
				{
					$value = (bool) $value;
				}
				if ($rule['array'])
				{
					$values = $conditionPresent($value) ? (array) $value : [];
					$oneMatches = false;
					foreach ($values as $entry)
					{
						if ($conditionMatch($entry, $rule))
						{
							$oneMatches = true;
							break;
						}
					}
				}
				else
				{
					$oneMatches = $conditionMatch($value, $rule);
				}
				$matched = $matched && $oneMatches;
			}
			if ($matched || $conditionGroup['toggle'])
			{
				$required = $matched ? $conditionGroup['show'] : !$conditionGroup['show'];
				foreach ($conditionGroup['targets'] as $target)
				{
					$conditionalRequired[$target] = $required;
				}
			}
		}
		foreach ($conditionalRequired as $field => $required)
		{
			$form->setFieldAttribute($field, 'required', $required ? 'true' : 'false', $group);
		}
		// Inactive fields keep their values; ordinary filtering and validation still apply.
PHP;

		return $code;
	}

	/**
	 * Export normalized condition data as a compact PHP array literal.
	 *
	 * @param   mixed  $value  A scalar or array from the compiler definition.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	protected function export($value): string
	{
		if (!is_array($value))
		{
			return var_export($value, true);
		}

		$items = [];
		foreach ($value as $key => $item)
		{
			$items[] = var_export($key, true) . ' => ' . $this->export($item);
		}

		return '[' . implode(', ', $items) . ']';
	}

}

