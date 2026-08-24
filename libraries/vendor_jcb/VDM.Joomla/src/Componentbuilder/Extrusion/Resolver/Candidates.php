<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    23rd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Resolver;


use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View;
use VDM\Joomla\Interfaces\Database\LoadInterface;


/**
 * Turns one harvest into the candidate list a person approves.
 *
 * Every resolved artifact becomes a candidate that carries exactly what the
 * pairing step needs: the key its verdict will be filed under, the identity
 * the writers would settle on when left alone, and the existing definition it
 * appears to match. The matching is by name against what the target component
 * already links, so a re-import of a known component arrives pre-paired as
 * updates and only what is genuinely new proposes itself as a creation.
 *
 * The catalogue of existing definitions stands on its own, because the
 * interface asks for it again whenever the person points the run at another
 * component -- every proposed pairing then re-lines against that component's
 * own links.
 *
 * @since 6.1.7
 */
final class Candidates
{
	/**
	 * The Config Class.
	 *
	 * @var    Config
	 * @since  6.1.7
	 */
	protected Config $config;

	/**
	 * The Resolved Registry.
	 *
	 * @var    Resolved
	 * @since  6.1.7
	 */
	protected Resolved $resolved;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.7
	 */
	protected Source $source;

	/**
	 * The View Registry.
	 *
	 * @var    View
	 * @since  6.1.7
	 */
	protected View $view;

	/**
	 * The Database Loader.
	 *
	 * @var    LoadInterface
	 * @since  6.1.7
	 */
	protected LoadInterface $load;

	/**
	 * The Guid Resolver.
	 *
	 * @var    Guid
	 * @since  6.1.7
	 */
	protected Guid $guid;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.7
	 */
	protected Report $report;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   Resolved       $resolved  The resolved definition registry.
	 * @param   Source         $source    The source identity registry.
	 * @param   View           $view      The classified view registry.
	 * @param   LoadInterface  $load      The database loader.
	 * @param   Guid           $guid      The identity resolver.
	 * @param   Report         $report    The run report registry.
	 *
	 * @since   6.1.7
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		Source $source,
		View $view,
		LoadInterface $load,
		Guid $guid,
		Report $report
	)
	{
		$this->config = $config;
		$this->resolved = $resolved;
		$this->source = $source;
		$this->view = $view;
		$this->load = $load;
		$this->guid = $guid;
		$this->report = $report;
	}

	/**
	 * Every candidate the harvest produced, paired against one component.
	 *
	 * @param   int  $componentId  The component whose links pair the candidates.
	 *
	 * @return  array<string, mixed>  Candidates by kind, each pre-paired by name.
	 * @since   6.1.7
	 */
	public function candidates(int $componentId): array
	{
		$catalogue = $this->catalogue($componentId);

		return [
			'admin_view' => $this->adminViews($catalogue),
			'site_view' => $this->classified('site_view', $catalogue['site_views']),
			'layout' => $this->classified('layout', $catalogue['layouts']),
			'template' => $this->classified('template', $catalogue['templates'])
		];
	}

	/**
	 * Everything one component already links, plus the global definition pools.
	 *
	 * @param   int  $componentId  The component id, or zero for none.
	 *
	 * @return  array<string, mixed>  The catalogue of existing definitions.
	 * @since   6.1.7
	 */
	public function catalogue(int $componentId): array
	{
		$component = $componentId > 0 ? $this->component($componentId) : null;
		$guid = trim((string) ($component->guid ?? ''));
		$views = $guid !== ''
			? $this->linked('component_admin_views', 'addadmin_views', 'adminview', $guid)
			: [];
		$siteViews = $guid !== ''
			? $this->linked('component_site_views', 'addsite_views', 'siteview', $guid)
			: [];

		return [
			'component' => $component,
			'admin_views' => $this->rows(
				'admin_view',
				['a.guid' => 'guid', 'a.name_single' => 'name', 'a.system_name' => 'system'],
				$views
			),
			'fields' => $this->fields($views),
			'site_views' => $this->rows(
				'site_view',
				['a.guid' => 'guid', 'a.name' => 'name', 'a.system_name' => 'system'],
				$siteViews
			),
			'layouts' => $this->rows('layout', ['a.guid' => 'guid', 'a.name' => 'name']),
			'templates' => $this->rows('template', ['a.guid' => 'guid', 'a.name' => 'name']),
			'powers' => $this->rows(
				'power',
				['a.guid' => 'guid', 'a.system_name' => 'name', 'a.namespace' => 'namespace']
			)
		];
	}

	/**
	 * The component the harvested source appears to be, by its code name.
	 *
	 * @return  object|null  The component row, or null when none answers.
	 * @since   6.1.7
	 */
	public function detect(): ?object
	{
		$code = trim((string) $this->source->get('code_name', ''));

		if ($code === '')
		{
			return null;
		}

		return $this->load->item(
			['a.id' => 'id', 'a.guid' => 'guid', 'a.system_name' => 'name', 'a.name_code' => 'code'],
			['a' => 'joomla_component'],
			['a.name_code' => str_replace('com_', '', strtolower($code))]
		);
	}

	/**
	 * Every published component, for the interface to offer as targets.
	 *
	 * @return  array<int, object>  The components, newest first.
	 * @since   6.1.7
	 */
	public function components(): array
	{
		$rows = $this->load->items(
			['a.id' => 'id', 'a.guid' => 'guid', 'a.system_name' => 'name', 'a.name_code' => 'code'],
			['a' => 'joomla_component'],
			['a.published' => 1],
			['a.modified' => 'DESC']
		);

		return is_array($rows) ? array_values($rows) : [];
	}

	/**
	 * One component row by id.
	 *
	 * @param   int  $componentId  The component id.
	 *
	 * @return  object|null  The component row, or null.
	 * @since   6.1.7
	 */
	protected function component(int $componentId): ?object
	{
		return $this->load->item(
			['a.id' => 'id', 'a.guid' => 'guid', 'a.system_name' => 'name', 'a.name_code' => 'code'],
			['a' => 'joomla_component'],
			['a.id' => $componentId]
		);
	}

	/**
	 * The admin view candidates, each carrying its field candidates.
	 *
	 * @param   array<string, mixed>  $catalogue  The existing definition catalogue.
	 *
	 * @return  array<int, array<string, mixed>>  The admin view candidates.
	 * @since   6.1.7
	 */
	protected function adminViews(array $catalogue): array
	{
		$candidates = [];
		$views = $this->resolved->get('views', []);

		foreach ((array) $views as $view)
		{
			if (!is_string($view) || $view === '')
			{
				continue;
			}

			$key = $this->key($view);
			$path = 'view.' . $key;
			$single = (string) $this->resolved->get($path . '.name_single', $view);
			$system = (string) $this->resolved->get($path . '.system_name', $single);
			$derived = (string) $this->resolved->get(
				$path . '.guid',
				$this->guid->derive([$this->option(), 'admin_view', $single])
			);
			$match = $this->matchByName(
				[$single, $system],
				(array) $catalogue['admin_views']
			);

			$candidates[] = [
				'kind' => 'admin_view',
				'key' => $key,
				'label' => $single,
				'detail' => $system,
				'guid' => $derived,
				'match' => $match,
				'fields' => $this->fieldCandidates($view, $key, $path, $match, $catalogue)
			];
		}

		return $candidates;
	}

	/**
	 * The field candidates of one resolved view.
	 *
	 * @param   string                     $view       The view name.
	 * @param   string                     $viewKey    The view's sanitised key.
	 * @param   string                     $path       The view's resolved registry path.
	 * @param   array<string, mixed>|null  $viewMatch  The view's own pairing, when found.
	 * @param   array<string, mixed>       $catalogue  The existing definition catalogue.
	 *
	 * @return  array<int, array<string, mixed>>  The field candidates.
	 * @since   6.1.7
	 */
	protected function fieldCandidates(
		string $view,
		string $viewKey,
		string $path,
		?array $viewMatch,
		array $catalogue
	): array
	{
		$candidates = [];
		$fields = (array) $this->resolved->get($path . '.field', []);

		// a field pairs first against the fields its paired view already links
		$pool = $viewMatch !== null
			? array_values(array_filter(
				(array) $catalogue['fields'],
				static fn ($row): bool => (object) $row instanceof \stdClass
					&& ((array) $row)['view'] === $viewMatch['guid']
			))
			: (array) $catalogue['fields'];

		foreach ($fields as $key => $properties)
		{
			$properties = (array) $properties;
			$column = (string) $this->value($properties, 'name', (string) $key);

			if ($column === '')
			{
				continue;
			}

			$label = (string) $this->value($properties, 'label', $column);
			$derived = $this->guid->prefer(
				$this->value($properties, 'guid'),
				[$this->option(), 'field', $view, $column]
			);

			$candidates[] = [
				'kind' => 'field',
				'key' => $viewKey . '.' . $this->key($column),
				'label' => $label,
				'detail' => $column,
				'guid' => $derived,
				'match' => $this->matchByName([$label, $column], $pool)
			];
		}

		return $candidates;
	}

	/**
	 * The candidates one classified view kind holds.
	 *
	 * @param   string                    $kind  The kind: site_view, layout, or template.
	 * @param   array<int, object|array>  $pool  The existing definitions to pair against.
	 *
	 * @return  array<int, array<string, mixed>>  The candidates.
	 * @since   6.1.7
	 */
	protected function classified(string $kind, array $pool): array
	{
		$candidates = [];
		$entries = (array) $this->view->get($kind, []);

		foreach ($entries as $key => $entry)
		{
			$entry = (array) $entry;
			$name = (string) ($entry['name'] ?? $key);

			if ($name === '')
			{
				continue;
			}

			$candidates[] = [
				'kind' => $kind,
				'key' => $this->key($name),
				'label' => $name,
				'detail' => (string) ($entry['view'] ?? ''),
				'guid' => $this->guid->derive([$this->option(), $kind, $name]),
				'match' => $this->matchByName([$name], $pool)
			];
		}

		return $candidates;
	}

	/**
	 * The first existing definition one of the given names answers to.
	 *
	 * @param   array<string>             $names  The candidate's names, best first.
	 * @param   array<int, object|array>  $pool   The existing definitions.
	 *
	 * @return  array{guid: string, label: string}|null  The pairing, or null.
	 * @since   6.1.7
	 */
	protected function matchByName(array $names, array $pool): ?array
	{
		$names = array_values(array_filter(array_map(
			static fn ($name): string => strtolower(trim((string) $name)),
			$names
		), 'strlen'));

		if ($names === [])
		{
			return null;
		}

		foreach ($pool as $row)
		{
			$row = (array) $row;

			foreach (['name', 'system'] as $field)
			{
				$value = strtolower(trim((string) ($row[$field] ?? '')));

				if ($value !== '' && in_array($value, $names, true))
				{
					return [
						'guid' => (string) ($row['guid'] ?? ''),
						'label' => (string) ($row['name'] ?? '')
					];
				}
			}
		}

		return null;
	}

	/**
	 * The guids one component-link subform holds.
	 *
	 * @param   string  $table   The linker table without its prefix.
	 * @param   string  $column  The subform column.
	 * @param   string  $field   The subform field carrying the guid.
	 * @param   string  $guid    The component identity.
	 *
	 * @return  array<string>  The linked guids.
	 * @since   6.1.7
	 */
	protected function linked(string $table, string $column, string $field, string $guid): array
	{
		$stored = $this->load->value(
			['a.' . $column => $column],
			['a' => $table],
			['a.joomla_component' => $guid]
		);

		if (!is_string($stored) || trim($stored) === '')
		{
			return [];
		}

		$rows = json_decode($stored, true);

		if (!is_array($rows))
		{
			return [];
		}

		$guids = [];

		foreach ($rows as $row)
		{
			$value = trim((string) (((array) $row)[$field] ?? ''));

			if ($this->guid->valid($value))
			{
				$guids[] = strtolower($value);
			}
		}

		return array_values(array_unique($guids));
	}

	/**
	 * The fields the given admin views already link, each carrying its view.
	 *
	 * @param   array<string>  $views  The admin view guids.
	 *
	 * @return  array<int, array{guid: string, name: string, view: string}>  The fields.
	 * @since   6.1.7
	 */
	protected function fields(array $views): array
	{
		if ($views === [])
		{
			return [];
		}

		$links = $this->load->items(
			['a.admin_view' => 'view', 'a.addfields' => 'fields'],
			['a' => 'admin_fields'],
			['a.admin_view' => ['operator' => 'IN', 'value' => $views]]
		);

		if (!is_array($links))
		{
			return [];
		}

		$byField = [];

		foreach ($links as $link)
		{
			$link = (array) $link;
			$view = strtolower(trim((string) ($link['view'] ?? '')));
			$rows = json_decode((string) ($link['fields'] ?? ''), true);

			if ($view === '' || !is_array($rows))
			{
				continue;
			}

			foreach ($rows as $row)
			{
				$field = trim((string) (((array) $row)['field'] ?? ''));

				if ($this->guid->valid($field))
				{
					$byField[strtolower($field)] = $view;
				}
			}
		}

		if ($byField === [])
		{
			return [];
		}

		$rows = $this->rows(
			'field',
			['a.guid' => 'guid', 'a.name' => 'name'],
			array_keys($byField)
		);

		foreach ($rows as &$row)
		{
			$row['view'] = $byField[strtolower((string) $row['guid'])] ?? '';
		}

		return $rows;
	}

	/**
	 * Rows of one definition table, optionally narrowed to given guids.
	 *
	 * @param   string                 $table   The table without its prefix.
	 * @param   array<string, string>  $select  The columns to select.
	 * @param   array<string>|null     $guids   The guids to narrow to, or null for all.
	 *
	 * @return  array<int, array<string, mixed>>  The rows.
	 * @since   6.1.7
	 */
	protected function rows(string $table, array $select, ?array $guids = null): array
	{
		if ($guids !== null && $guids === [])
		{
			return [];
		}

		$where = $guids === null
			? null
			: ['a.guid' => ['operator' => 'IN', 'value' => $guids]];
		$rows = $this->load->items($select, ['a' => $table], $where);

		if (!is_array($rows))
		{
			return [];
		}

		return array_map(
			static fn ($row): array => (array) $row,
			array_values($rows)
		);
	}

	/**
	 * The value of one resolved field property.
	 *
	 * @param   array<string, mixed>  $properties  The resolved properties.
	 * @param   string                $property    The property name.
	 * @param   mixed                 $default     A value to use when unresolved.
	 *
	 * @return  mixed  The resolved value, or the default.
	 * @since   6.1.7
	 */
	protected function value(array $properties, string $property, $default = null)
	{
		$entry = $properties[$property] ?? null;

		if (!is_array($entry) && !is_object($entry))
		{
			return $default;
		}

		$entry = (array) $entry;
		$value = $entry['value'] ?? null;

		return $value === null || $value === '' ? $default : $value;
	}

	/**
	 * The component option, when it is known.
	 *
	 * @return  string  The com_ prefixed option, or an empty string.
	 * @since   6.1.7
	 */
	protected function option(): string
	{
		return (string) $this->source->get('code_name', '');
	}

	/**
	 * Sanitise one registry path segment.
	 *
	 * @param   string  $segment  The raw segment.
	 *
	 * @return  string  A segment safe to use in a dotted registry path.
	 * @since   6.1.7
	 */
	protected function key(string $segment): string
	{
		return preg_replace('/[^A-Za-z0-9_]/', '_', $segment) ?? $segment;
	}
}
