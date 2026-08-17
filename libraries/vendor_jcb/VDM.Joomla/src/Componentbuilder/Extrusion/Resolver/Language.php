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


use VDM\Joomla\Componentbuilder\Extrusion\Registry\Language as Catalogue;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;


/**
 * Turns a language constant into the actual English string.
 *
 * JCB stores real text, not placeholders, so a label of
 * COM_EXAMPLE_ITEM_NAME_LABEL must become "Name" before it is written. A
 * constant that cannot be resolved is kept verbatim and recorded, because
 * silently inventing a label would be worse than reporting the gap.
 *
 * @since 6.1.6
 */
final class Language
{
	/**
	 * The Language catalogue registry.
	 *
	 * @var    Catalogue
	 * @since  6.1.6
	 */
	protected Catalogue $catalogue;

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
	 * @param   Catalogue  $catalogue  The language constant catalogue.
	 * @param   Report     $report     The run report registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(Catalogue $catalogue, Report $report)
	{
		$this->catalogue = $catalogue;
		$this->report = $report;
	}

	/**
	 * Whether a value looks like a language constant rather than text.
	 *
	 * @param   mixed  $value  The candidate value.
	 *
	 * @return  bool  True when the value is a constant.
	 * @since   6.1.6
	 */
	public function isConstant($value): bool
	{
		if (!is_string($value) || $value === '')
		{
			return false;
		}

		return preg_match('/^[A-Z][A-Z0-9_]*$/', $value) === 1
			&& str_contains($value, '_');
	}

	/**
	 * Resolve one value through the catalogue.
	 *
	 * @param   mixed   $value     The candidate value.
	 * @param   string  $fallback  A value to use when the constant is unknown.
	 *
	 * @return  string  The English string, the fallback, or the constant verbatim.
	 * @since   6.1.6
	 */
	public function resolve($value, string $fallback = ''): string
	{
		if (!is_string($value))
		{
			return $fallback;
		}

		if (!$this->isConstant($value))
		{
			return $value;
		}

		$resolved = $this->catalogue->get('constant.' . $value);

		if (is_string($resolved) && $resolved !== '')
		{
			return $resolved;
		}

		$this->report->set('unresolved.language.' . $value, true);

		return $fallback !== '' ? $fallback : $value;
	}

	/**
	 * Resolve every value of an attribute bag that carries display text.
	 *
	 * @param   array<string, mixed>  $attributes  The raw attribute bag.
	 * @param   array<string>         $keys        The attribute names to resolve.
	 *
	 * @return  array<string, mixed>  The bag with those attributes resolved.
	 * @since   6.1.6
	 */
	public function bag(array $attributes, array $keys): array
	{
		foreach ($keys as $key)
		{
			if (isset($attributes[$key]) && $this->isConstant($attributes[$key]))
			{
				$attributes[$key] = $this->resolve($attributes[$key]);
			}
		}

		return $attributes;
	}
}
