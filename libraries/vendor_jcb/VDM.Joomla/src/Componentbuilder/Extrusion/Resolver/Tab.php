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


use VDM\Joomla\Componentbuilder\Extrusion\Registry\Form;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;


/**
 * Turns a form's fieldsets into JCB tabs.
 *
 * A table definition class states the intended tab outright in tab_name, which is
 * the better answer. Otherwise the form's fieldsets supply the grouping, which is
 * still far better than putting every field on one tab.
 *
 * @since 6.1.6
 */
final class Tab
{
	/**
	 * The tab every field falls back to.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	public const DEFAULT_TAB = 'Details';

	/**
	 * The tab number JCB keeps for its own publishing section.
	 *
	 * The compiler sets it unconditionally and the documentation states it
	 * plainly, so a field the source shows in a section the compiler generates
	 * belongs there rather than on a tab of its own.
	 *
	 * @var    int
	 * @since  6.1.8
	 */
	public const PUBLISHING_TAB = 15;

	/**
	 * The Form Registry.
	 *
	 * @var    Form
	 * @since  6.1.6
	 */
	protected Form $form;

	/**
	 * The Language Resolver.
	 *
	 * @var    Language
	 * @since  6.1.6
	 */
	protected Language $language;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	protected Report $report;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.8
	 */
	protected Source $source;

	/**
	 * Constructor.
	 *
	 * @param   Form      $form      The parsed form registry.
	 * @param   Language  $language  The language resolver.
	 * @param   Report    $report    The run report registry.
	 * @param   Source    $source    The source identity registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(Form $form, Language $language, Report $report, Source $source)
	{
		$this->form = $form;
		$this->language = $language;
		$this->report = $report;
		$this->source = $source;
	}

	/**
	 * The ordered, de-duplicated tab names for one view.
	 *
	 * @param   string                                                  $view    The JCB view name.
	 * @param   array<string, array<string, array{value: mixed, origin: string}>>  $fields  Resolved fields.
	 *
	 * @return  array<int, string>  The tab names, one-based order preserved.
	 * @since   6.1.6
	 */
	public function names(string $view, array $fields): array
	{
		// the component's own edit screen names its tabs and their order; a
		// form's fieldsets are only consulted where no screen was recovered
		$stated = $this->stated($view);

		if ($stated !== [])
		{
			$this->report->set('tabs.' . $this->key($view), $stated);

			return $stated;
		}

		$names = [];

		foreach ($fields as $properties)
		{
			$name = $this->nameFor($view, $properties);

			if ($name !== '' && !in_array($name, $names, true))
			{
				$names[] = $name;
			}
		}

		if ($names === [])
		{
			$names[] = self::DEFAULT_TAB;
		}

		$this->report->set('tabs.' . $this->key($view), $names);

		return $names;
	}

	/**
	 * The tab one resolved field belongs on.
	 *
	 * @param   string                                            $view        The JCB view name.
	 * @param   array<string, array{value: mixed, origin: string}>  $properties  Resolved properties.
	 *
	 * @return  string  The tab name.
	 * @since   6.1.6
	 */
	public function nameFor(string $view, array $properties): string
	{
		$stated = $properties['tab']['value'] ?? null;

		if (is_string($stated) && trim($stated) !== '')
		{
			return $this->clean($stated);
		}

		$fieldset = $properties['fieldset']['value'] ?? null;

		if (is_string($fieldset) && trim($fieldset) !== '')
		{
			return $this->clean($this->label($view, $fieldset));
		}

		return self::DEFAULT_TAB;
	}

	/**
	 * The one-based tab index a field belongs to.
	 *
	 * @param   string             $name  The tab name.
	 * @param   array<int, string>  $tabs  The ordered tab names.
	 *
	 * @return  int  The one-based tab index.
	 * @since   6.1.6
	 */
	public function index(string $name, array $tabs): int
	{
		$position = array_search($name, $tabs, true);

		return $position === false ? 1 : ((int) $position) + 1;
	}

	/**
	 * The display label of one fieldset.
	 *
	 * @param   string  $view      The JCB view name.
	 * @param   string  $fieldset  The fieldset name.
	 *
	 * @return  string  The resolved label, or the fieldset name.
	 * @since   6.1.6
	 */
	protected function label(string $view, string $fieldset): string
	{
		$path = 'view.' . $this->key($view) . '.fieldset.' . $this->key($fieldset) . '.label';
		$label = $this->form->get($path);

		if (is_string($label) && $label !== '')
		{
			return $this->language->resolve($label, $fieldset);
		}

		return $fieldset;
	}

	/**
	 * Normalise a tab name into a readable label.
	 *
	 * @param   string  $name  The raw tab name.
	 *
	 * @return  string  The cleaned tab name.
	 * @since   6.1.6
	 */
	protected function clean(string $name): string
	{
		$name = trim(str_replace(['_', '-'], ' ', $name));
		$name = preg_replace('/\s+/', ' ', $name) ?? $name;

		return $name === '' ? self::DEFAULT_TAB : ucwords($name);
	}

	/**
	 * The tabs the component's own edit screen states for one view.
	 *
	 * @param   string  $view  The JCB view name.
	 *
	 * @return  array<int, string>  The tab names in their stated order.
	 * @since   6.1.8
	 */
	public function stated(string $view): array
	{
		$names = [];

		foreach ((array) $this->source->get('screen.' . strtolower($view) . '.tabs', []) as $tab)
		{
			$tab = (array) $tab;
			$key = trim((string) ($tab['key'] ?? ''));

			if ($key === '')
			{
				continue;
			}

			$label = trim((string) ($tab['label'] ?? ''));
			$name = $this->clean(
				$label === '' ? $key : $this->language->resolve($label, $key)
			);

			if (!in_array($name, $names, true))
			{
				$names[] = $name;
			}
		}

		return $names;
	}

	/**
	 * Where the component's own edit screen places one field.
	 *
	 * @param   string  $view    The JCB view name.
	 * @param   string  $column  The field's column name.
	 *
	 * @return  array{tab: int, alignment: int, order: int}|null  The placement, or null.
	 * @since   6.1.8
	 */
	public function placement(string $view, string $column): ?array
	{
		$view = strtolower($view);
		$place = $this->source->get(
			'screen.' . $view . '.place.' . strtolower($column),
			null
		);

		if (!is_array($place) || ($place['tab'] ?? '') === '')
		{
			return null;
		}

		$tab = strtolower((string) $place['tab']);
		$alignment = (int) ($place['alignment'] ?? 1);
		$order = (int) ($place['order'] ?? 1);

		if (!empty($place['generated']))
		{
			return [
				'tab' => self::PUBLISHING_TAB,
				'alignment' => $alignment > 0 ? $alignment : 1,
				'order' => $order
			];
		}

		$number = 0;

		foreach ((array) $this->source->get('screen.' . $view . '.tabs', []) as $entry)
		{
			$number++;

			if (strtolower(trim((string) (((array) $entry)['key'] ?? ''))) === $tab)
			{
				return [
					'tab' => $number,
					'alignment' => $alignment > 0 ? $alignment : 1,
					'order' => $order
				];
			}
		}

		return null;
	}

	/**
	 * Sanitise one registry path segment.
	 *
	 * @param   string  $segment  The raw segment.
	 *
	 * @return  string  A segment safe to use in a dotted registry path.
	 * @since   6.1.6
	 */
	public function key(string $segment): string
	{
		return preg_replace('/[^A-Za-z0-9_]/', '_', $segment) ?? $segment;
	}
}
