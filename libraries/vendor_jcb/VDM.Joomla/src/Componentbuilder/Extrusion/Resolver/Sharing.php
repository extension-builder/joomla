<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    28th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Resolver;


use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;


/**
 * One field per stated identity, linked into every view that states it.
 *
 * JCB holds a field once and every view that needs it links it by guid. A
 * component whose ten views each state the same name field therefore owes one
 * field record and ten links -- not ten records. What makes two columns the
 * same field is decided here, before anything is written, so the harvest a
 * person approves already shows one field and the views it serves.
 *
 * Identity is settled in the order the rule runs:
 *
 * - A stated Global Unique ID outranks everything. The same guid is the same
 *   field always, and two columns stating it are one field whatever else they
 *   say.
 * - Otherwise the sources' own statements decide: the code name, the label,
 *   the field type, the database shape, and every stated XML property must
 *   match exactly. Required true and required false are two different fields.
 *   Nothing is judged more or less important -- a statement is a statement.
 *
 * The first view in table order owns the field's record; every later view
 * carries a share note the writer turns into a link. A pairing verdict on any
 * single column outranks its share note, so a person who points one view's
 * column at another field detaches exactly that view and nothing else.
 *
 * Nothing here looks outside the component being extruded. What already
 * stands in the wider system is a pairing decision a person makes on the
 * board, never a resemblance this run acts on by itself.
 *
 * @since 6.1.9
 */
final class Sharing
{
	/**
	 * The Resolved Registry.
	 *
	 * @var    Resolved
	 * @since  6.1.9
	 */
	protected Resolved $resolved;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.9
	 */
	protected Source $source;

	/**
	 * The Pairing Resolver.
	 *
	 * @var    Pairing
	 * @since  6.1.9
	 */
	protected Pairing $pairing;

	/**
	 * The Guid Resolver.
	 *
	 * @var    Guid
	 * @since  6.1.9
	 */
	protected Guid $guid;

	/**
	 * The Field Xml Resolver.
	 *
	 * @var    FieldXml
	 * @since  6.1.9
	 */
	protected FieldXml $fieldxml;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.9
	 */
	protected Report $report;

	/**
	 * Constructor.
	 *
	 * @param   Resolved  $resolved  The resolved definition registry.
	 * @param   Source    $source    The source identity registry.
	 * @param   Pairing   $pairing   The pairing resolver.
	 * @param   Guid      $guid      The identity resolver.
	 * @param   FieldXml  $fieldxml  The field xml resolver.
	 * @param   Report    $report    The run report registry.
	 *
	 * @since   6.1.9
	 */
	public function __construct(
		Resolved $resolved,
		Source $source,
		Pairing $pairing,
		Guid $guid,
		FieldXml $fieldxml,
		Report $report
	)
	{
		$this->resolved = $resolved;
		$this->source = $source;
		$this->pairing = $pairing;
		$this->guid = $guid;
		$this->fieldxml = $fieldxml;
		$this->report = $report;
	}

	/**
	 * Settle which columns share one field, across the whole component.
	 *
	 * @return  int  The number of columns that link a field another view owns.
	 * @since   6.1.9
	 */
	public function settle(): int
	{
		$members = $this->members();

		if ($members === [])
		{
			return 0;
		}

		$groups = $this->group($members);
		$shared = 0;

		foreach ($groups as $group)
		{
			if (count($group['members']) < 2)
			{
				continue;
			}

			$owner = $group['members'][0];

			foreach (array_slice($group['members'], 1) as $member)
			{
				$this->resolved->set(
					'view.' . $member['view'] . '.field.' . $member['column'] . '.share',
					[
						'guid' => $group['guid'],
						'owner' => $owner['view'] . '.' . $owner['column'],
						'by' => $group['by']
					]
				);
				$this->report->set(
					'field.shared.' . $member['view'] . '.' . $member['column'],
					$owner['view'] . '.' . $owner['column'] . ' (' . $group['by'] . ')'
				);
				$shared++;
			}
		}

		if ($shared > 0)
		{
			$this->report->set('counts.fields_shared', $shared);
		}

		return $shared;
	}

	/**
	 * Every resolved column that may take part in sharing, in table order.
	 *
	 * A column a person has already decided by verdict stands outside the
	 * grouping entirely: pointed at another field it is that field's, and
	 * ignored it is nobody's -- either way no share note may speak for it.
	 *
	 * @return  array<int, array{view: string, column: string, stated: string, identity: string, derived: string}>  The members.
	 * @since   6.1.9
	 */
	protected function members(): array
	{
		$members = [];

		foreach ((array) $this->resolved->get('views', []) as $view)
		{
			if (!is_string($view) || $view === '')
			{
				continue;
			}

			$key = $this->key($view);
			$fields = (array) $this->resolved->get('view.' . $key . '.field', []);

			foreach ($fields as $name => $properties)
			{
				$properties = (array) $properties;
				$column = (string) ($properties['name']['value'] ?? $name);

				if ($column === '')
				{
					continue;
				}

				$stated = trim((string) ($properties['guid']['value'] ?? ''));

				// the seed is the writer's own, term for term, so the identity
				// settled here is the identity the writer would derive alone
				$derived = $this->guid->prefer(
					$stated === '' ? null : $stated,
					[$this->option(), 'field', $view, $column]
				);
				$decided = $this->pairing->guid(
					'field',
					$this->key($view) . '.' . $this->key($column),
					$derived
				);

				if ($decided !== $derived)
				{
					// decided null is an ignore, and any other answer is a
					// person naming the field this column belongs to
					continue;
				}

				$members[] = [
					'view' => $key,
					'column' => $this->key($column),
					'stated' => $stated,
					'identity' => $this->identity($column, $properties),
					'derived' => $derived
				];
			}
		}

		return $members;
	}

	/**
	 * Group the members into the fields they jointly state.
	 *
	 * The stated guid claims first: every column stating one guid is one
	 * group, whatever else it says. The rest group by their full stated
	 * identity -- and a statement group one guid-stating column also belongs
	 * to is that guid's group, because a matching statement is the rule's own
	 * proof that the guid speaks for all of them.
	 *
	 * @param   array<int, array{view: string, column: string, stated: string, identity: string, derived: string}>  $members  The members in table order.
	 *
	 * @return  array<int, array{guid: string, by: string, members: array}>  The groups, each in table order.
	 * @since   6.1.9
	 */
	protected function group(array $members): array
	{
		$byGuid = [];
		$byIdentity = [];

		foreach ($members as $member)
		{
			if ($member['stated'] !== '')
			{
				$byGuid[$member['stated']][] = $member;

				continue;
			}

			$byIdentity[$member['identity']][] = $member;
		}

		$groups = [];

		foreach ($byGuid as $stated => $grouped)
		{
			// a column stating no guid but stating the same everything else
			// belongs with the columns that do state it
			$adopted = $byIdentity[$grouped[0]['identity']] ?? null;

			if ($adopted !== null)
			{
				$grouped = $this->merge($grouped, $adopted);
				unset($byIdentity[$grouped[0]['identity']]);
			}

			$groups[] = [
				'guid' => $stated,
				'by' => 'guid',
				'members' => $grouped
			];
		}

		foreach ($byIdentity as $grouped)
		{
			$groups[] = [
				'guid' => $grouped[0]['derived'],
				'by' => 'xml',
				'members' => $grouped
			];
		}

		return $groups;
	}

	/**
	 * Merge two member lists back into table order.
	 *
	 * @param   array  $left   Members in table order.
	 * @param   array  $right  Members in table order.
	 *
	 * @return  array  One list, in table order.
	 * @since   6.1.9
	 */
	protected function merge(array $left, array $right): array
	{
		$merged = array_merge($left, $right);

		usort(
			$merged,
			static fn (array $one, array $two): int =>
				[$one['view'], $one['column']] <=> [$two['view'], $two['column']]
		);

		return $merged;
	}

	/**
	 * The whole stated identity of one column, as one canonical string.
	 *
	 * The XML statement carries the code name, the label, the type and every
	 * stated property; the database shape carries what the column is in the
	 * table. Together they are everything the source said, and everything the
	 * source said has to match for two columns to be one field.
	 *
	 * @param   string                $column      The source column name.
	 * @param   array<string, mixed>  $properties  The resolved properties.
	 *
	 * @return  string  The canonical identity.
	 * @since   6.1.9
	 */
	protected function identity(string $column, array $properties): string
	{
		$shape = [];

		foreach (['datatype', 'size', 'db_default', 'null', 'key', 'store'] as $property)
		{
			$value = $properties[$property]['value'] ?? null;
			$shape[] = $property . '=' . (is_scalar($value) ? (string) $value : '');
		}

		return $this->fieldxml->essence($column, $properties)
			. ' :: ' . implode(' ', $shape);
	}

	/**
	 * The component's code name, which seeds every derived identity.
	 *
	 * @return  string  The code name.
	 * @since   6.1.9
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
	 * @since   6.1.9
	 */
	protected function key(string $segment): string
	{
		return preg_replace('/[^A-Za-z0-9_]/', '_', $segment) ?? $segment;
	}
}
