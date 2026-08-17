<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Resolver;


use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;


/**
 * Composes the form XML attribute string JCB stores on a field.
 *
 * JCB keeps a field's form definition as a single self-closing field element, so
 * this builds that element from the resolved attribute bag. The field type's own
 * declared property names decide what is allowed through, which is how an
 * attribute JCB would not understand is dropped rather than written blindly.
 *
 * @since 6.1.6
 */
final class FieldXml
{
	/**
	 * Attributes that are never carried across.
	 *
	 * The type is implied by the field type itself, and showon becomes a JCB
	 * condition rather than a stored attribute.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const DROP = ['type', 'showon'];

	/**
	 * The attribute order JCB's own generated fields use.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const ORDER = [
		'name', 'label', 'size', 'maxlength', 'default', 'description',
		'class', 'required', 'readonly', 'disabled', 'multiple', 'filter',
		'validate', 'message', 'hint'
	];

	/**
	 * The Fieldtype Resolver.
	 *
	 * @var    Fieldtype
	 * @since  6.1.6
	 */
	protected Fieldtype $fieldtype;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	protected Report $report;

	/**
	 * Constructor.
	 *
	 * @param   Fieldtype  $fieldtype  The field type resolver.
	 * @param   Report     $report     The run report registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(Fieldtype $fieldtype, Report $report)
	{
		$this->fieldtype = $fieldtype;
		$this->report = $report;
	}

	/**
	 * Build the stored field element for one resolved field.
	 *
	 * @param   string                                            $column      The source column name.
	 * @param   array<string, array{value: mixed, origin: string}>  $properties  Resolved properties.
	 *
	 * @return  string  The field element source.
	 * @since   6.1.6
	 */
	public function build(string $column, array $properties): string
	{
		$attributes = $this->attributes($column, $properties);
		$xml = '<field';

		foreach ($attributes as $name => $value)
		{
			$xml .= PHP_EOL . "\t" . $name . '="' . $this->escape((string) $value) . '"';
		}

		$options = $this->options($properties);

		if ($options === [])
		{
			return $xml . PHP_EOL . '/>';
		}

		$xml .= PHP_EOL . '>';

		foreach ($options as $option)
		{
			$xml .= PHP_EOL . "\t" . '<option value="'
				. $this->escape((string) ($option['value'] ?? '')) . '">'
				. $this->escape((string) ($option['text'] ?? ''))
				. '</option>';
		}

		return $xml . PHP_EOL . '</field>';
	}

	/**
	 * The ordered, filtered attribute set for one resolved field.
	 *
	 * @param   string                                            $column      The source column name.
	 * @param   array<string, array{value: mixed, origin: string}>  $properties  Resolved properties.
	 *
	 * @return  array<string, string>  The attributes to write.
	 * @since   6.1.6
	 */
	public function attributes(string $column, array $properties): array
	{
		$bag = $this->bag($column, $properties);
		$type = (string) ($properties['xml_type']['value'] ?? '');
		$allowed = $this->fieldtype->properties($type);
		$attributes = [];

		foreach (self::ORDER as $name)
		{
			if (isset($bag[$name]) && $this->allowed($name, $allowed))
			{
				$attributes[$name] = $bag[$name];
				unset($bag[$name]);
			}
		}

		foreach ($bag as $name => $value)
		{
			if ($this->allowed($name, $allowed))
			{
				$attributes[$name] = $value;
			}
		}

		return $attributes;
	}

	/**
	 * Flatten the resolved properties into a raw attribute bag.
	 *
	 * @param   string                                            $column      The source column name.
	 * @param   array<string, array{value: mixed, origin: string}>  $properties  Resolved properties.
	 *
	 * @return  array<string, string>  The raw attribute bag.
	 * @since   6.1.6
	 */
	protected function bag(string $column, array $properties): array
	{
		$bag = [];
		$carried = $properties['attributes']['value'] ?? null;

		if (is_array($carried))
		{
			foreach ($carried as $name => $value)
			{
				if (is_string($name) && (is_scalar($value) || $value === null))
				{
					$bag[$name] = (string) $value;
				}
			}
		}

		foreach (['label', 'description', 'hint', 'message', 'default', 'class', 'required', 'filter', 'validate', 'multiple', 'readonly', 'disabled'] as $name)
		{
			$value = $properties[$name]['value'] ?? null;

			if (is_scalar($value) && (string) $value !== '')
			{
				$bag[$name] = (string) $value;
			}
		}

		$bag['name'] = $column;

		foreach (self::DROP as $name)
		{
			unset($bag[$name]);
		}

		return $bag;
	}

	/**
	 * The option list of one resolved field.
	 *
	 * @param   array<string, array{value: mixed, origin: string}>  $properties  Resolved properties.
	 *
	 * @return  array<int, array{value: mixed, text: mixed}>  The options.
	 * @since   6.1.6
	 */
	protected function options(array $properties): array
	{
		$options = $properties['options']['value'] ?? null;

		if (!is_array($options))
		{
			return [];
		}

		$list = [];

		foreach ($options as $option)
		{
			$option = (array) $option;

			if (!isset($option['value']) && !isset($option['text']))
			{
				continue;
			}

			$list[] = ['value' => $option['value'] ?? '', 'text' => $option['text'] ?? ''];
		}

		return $list;
	}

	/**
	 * Whether one attribute is declared by the field type.
	 *
	 * An empty declared set means the field type could not be resolved, in which
	 * case nothing is filtered out rather than everything.
	 *
	 * @param   string         $name     The attribute name.
	 * @param   array<string>  $allowed  The declared property names.
	 *
	 * @return  bool  True when the attribute may be written.
	 * @since   6.1.6
	 */
	protected function allowed(string $name, array $allowed): bool
	{
		if (in_array($name, self::DROP, true))
		{
			return false;
		}

		if ($allowed === [])
		{
			return true;
		}

		return in_array($name, $allowed, true);
	}

	/**
	 * Escape one attribute value for XML.
	 *
	 * @param   string  $value  The raw value.
	 *
	 * @return  string  The escaped value.
	 * @since   6.1.6
	 */
	protected function escape(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
	}
}
