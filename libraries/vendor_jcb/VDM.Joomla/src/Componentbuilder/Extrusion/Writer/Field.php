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

namespace VDM\Joomla\Componentbuilder\Extrusion\Writer;


use VDM\Joomla\Componentbuilder\Extrusion\Abstraction\Writer;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\FieldXml;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Fieldtype;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Pairing;
use VDM\Joomla\Interfaces\TableInterface;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Writes one JCB field definition per resolved column.
 *
 * The stored values are raw: the storage encoding each column declares is applied
 * by the Data pipeline, so applying it here would encode twice. The identity is
 * the GUID the source supplied where it had one, which is what lets a component
 * that came out of JCB line back up with its own definitions.
 *
 * @since 6.1.6
 */
final class Field extends Writer
{
	/**
	 * The identity of every field this run has written, by its shape.
	 *
	 * @var    array<string, string>
	 * @since  6.1.8
	 */
	protected array $shared = [];

	/**
	 * Whether the caller chose the identity of the field being written.
	 *
	 * @var    bool
	 * @since  6.1.8
	 */
	protected bool $chosen = false;

	/**
	 * Default lengths JCB offers directly rather than as an other value.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const SIZES = ['1', '7', '10', '11', '50', '64', '100', '255', '1024', '2048'];

	/**
	 * Default values JCB offers directly rather than as an other value.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const DEFAULTS = ['', '0', '1', 'CURRENT_TIMESTAMP', 'DATETIME'];

	/**
	 * The Fieldtype Resolver.
	 *
	 * @var    Fieldtype
	 * @since  6.1.6
	 */
	protected Fieldtype $fieldtype;

	/**
	 * The FieldXml Resolver.
	 *
	 * @var    FieldXml
	 * @since  6.1.6
	 */
	protected FieldXml $fieldxml;

	/**
	 * The Guid Resolver.
	 *
	 * @var    Guid
	 * @since  6.1.6
	 */
	protected Guid $guid;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.6
	 */
	protected Source $source;

	/**
	 * The Pairing Resolver.
	 *
	 * @var    Pairing
	 * @since  6.1.7
	 */
	protected Pairing $pairing;

	/**
	 * The JCB table definitions, the source of truth for every column.
	 *
	 * @var    TableInterface
	 * @since  6.1.7
	 */
	protected TableInterface $tables;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config     The extrusion configuration.
	 * @param   Resolved       $resolved   The resolved definition registry.
	 * @param   ItemInterface  $item       The JCB data item writer.
	 * @param   Report         $report     The run report registry.
	 * @param   Fieldtype      $fieldtype  The field type resolver.
	 * @param   FieldXml       $fieldxml   The field XML composer.
	 * @param   Guid           $guid       The identity resolver.
	 * @param   Source         $source     The source identity registry.
	 * @param   Pairing        $pairing   The pairing resolver.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report,
		Fieldtype $fieldtype,
		FieldXml $fieldxml,
		Guid $guid,
		Source $source,
		Pairing $pairing,
		TableInterface $tables
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->fieldtype = $fieldtype;
		$this->fieldxml = $fieldxml;
		$this->guid = $guid;
		$this->source = $source;
		$this->pairing = $pairing;
		$this->tables = $tables;
	}

	/**
	 * The JCB table this writer persists into.
	 *
	 * @return  string  The table name without its prefix.
	 * @since   6.1.6
	 */
	protected function table(): string
	{
		return 'field';
	}

	/**
	 * Write every resolved field.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.6
	 */
	public function write(): int
	{
		$written = 0;

		foreach ($this->views() as $view)
		{
			$fields = $this->resolved->get($this->path($view) . '.field');

			foreach ((array) $fields as $key => $properties)
			{
				$properties = (array) $properties;
				$column = (string) $this->value($properties, 'name', (string) $key);

				if ($column === '')
				{
					continue;
				}

				if ($this->one($view, $column, $properties))
				{
					$written++;
				}
			}
		}

		$this->report->set('counts.field', $written);

		return $written;
	}

	/**
	 * Write one resolved field.
	 *
	 * @param   string                $view        The view name.
	 * @param   string                $column      The source column name.
	 * @param   array<string, mixed>  $properties  The resolved properties.
	 *
	 * @return  bool  True when the definition was written.
	 * @since   6.1.6
	 */
	protected function one(string $view, string $column, array $properties): bool
	{
		$type = (string) $this->value($properties, 'xml_type', 'text');
		$link = $this->fieldxml->link($properties, $type);

		// A field that stores a key from another table has to be a custom field type,
		// because that is the only JCB field type whose generated code queries the
		// linked view. Leaving it as the plain type the column looked like would keep
		// the attributes and throw away the relationship they describe.
		$fieldtype = $link === []
			? $this->fieldtype->resolve($type)
			: $this->fieldtype->fallback($type);

		if ($link === [])
		{
			// Two field types can answer to one XML type because one is a narrower kind
			// of the other, and the field's own validation says which was meant.
			$narrower = $this->fieldtype->discriminate($type, $this->attributes($properties));
			$fieldtype = $narrower ?? $fieldtype;
		}

		if ($fieldtype === null)
		{
			$this->report->set('failed.field.unresolved_type.' . $this->key($column), $type);

			return false;
		}

		if ($link !== [])
		{
			$this->report->set(
				'relations.written.' . $this->key($view) . '.' . $this->key($column),
				$link['type'] . ' selecting ' . $link['value_field'] . ' from ' . $link['table']
			);
		}

		$guid = $this->guid->prefer(
			$this->value($properties, 'guid'),
			[$this->option(), 'field', $view, $column]
		);

		// the caller's pairing verdict outranks the derived identity
		$derived = $guid;
		$guid = $this->pairing->guid(
			'field',
			$this->key($view) . '.' . $this->key($column),
			$guid
		);

		if ($guid === null)
		{
			return false;
		}

		// a person who named the field this column belongs to has spoken, and
		// nothing here may quietly relate the column to another field instead
		$this->chosen = $guid !== $derived;

		$label = (string) $this->value($properties, 'label', $column);
		$size = (string) $this->value($properties, 'size', '');
		$default = (string) $this->value($properties, 'default', '');

		$identity = trim((string) ($fieldtype['guid'] ?? ''));

		if ($identity === '')
		{
			// field.fieldtype is a VARCHAR(36) holding the field type's own guid, which is
			// the only identity that survives being moved between installs. A numeric id
			// written there compiles to nothing, so a catalogue row with no guid is a
			// failure to resolve rather than something to write around.
			$this->report->set(
				'failed.field.unidentified_type.' . $this->key($column),
				$fieldtype['name'] ?? $type
			);

			return false;
		}

		$definition = new \stdClass();
		$definition->guid = $guid;
		$definition->name = $this->readable($label, $column);
		$definition->fieldtype = $identity;
		$definition->datatype = (string) $this->value($properties, 'datatype', 'TEXT');
		$definition->indexes = $this->indexes(
			(int) $this->value($properties, 'key', 0),
			$column
		);
		$definition->null_switch = (string) $this->value($properties, 'null', 'NULL');
		$definition->store = $this->storeCode((string) $this->value($properties, 'store', ''));
		$definition->xml = $this->fieldxml->build($column, $properties);
		$definition->published = 1;

		$definition->datalenght = in_array($size, self::SIZES, true) || $size === ''
			? $size
			: 'Other';
		$definition->datalenght_other = $definition->datalenght === 'Other' ? $size : '';
		// the column's default is what the schema states for the column; the
		// form's default attribute is a different thing entirely, and reading
		// one as the other writes the form's placeholder into the table
		$columnDefault = (string) $this->value($properties, 'db_default', '');
		$stated = $this->value($properties, 'db_default_stated', null);

		if ($stated === false)
		{
			// a column carrying no DEFAULT clause is not a column defaulting to
			// nothing, and JCB spells that difference EMPTY: without it the
			// compiler gives every such column a default the source never had
			$definition->datadefault = 'Other';
			$definition->datadefault_other = 'EMPTY';
		}
		elseif ($columnDefault === 'EMPTY')
		{
			// EMPTY is the word JCB reserves for "this column carries no
			// DEFAULT clause", so a column whose default is literally that
			// word cannot be stored as itself. Dropping the clause would leave
			// a NOT NULL column with no default at all, so the nearest thing
			// is kept and the loss is named rather than passed over
			$definition->datadefault = '';
			$definition->datadefault_other = '';
			$this->report->set(
				'skipped.default.reserved_word.' . $this->key($column),
				'the column defaults to the word EMPTY, which JCB reserves for '
				. 'a column carrying no default at all'
			);
		}
		else
		{
			$definition->datadefault = in_array($columnDefault, self::DEFAULTS, true) || $columnDefault === ''
				? $columnDefault
				: 'Other';
			$definition->datadefault_other = $definition->datadefault === 'Other' ? $columnDefault : '';
		}

		// a column default can only be as long as the Table class says the
		// datadefault_other column holds; a longer harvested default is a
		// form default, it lives on in the field's xml, and carrying it
		// here would be refused by any live database
		if (strlen($definition->datadefault_other) > $this->capacity('datadefault_other'))
		{
			$definition->datadefault = '';
			$definition->datadefault_other = '';
			$this->report->set(
				'skipped.default.too_long.' . $this->key($column),
				strlen($columnDefault)
			);
		}

		// two views stating the same field are stating one field: JCB holds
		// fields on their own and every view that needs one links it, so a
		// definition already written this run is linked again rather than
		// written a second time under another identity
		$shape = $this->shape($definition);
		$standing = $this->shared[$shape] ?? null;

		if ($standing !== null && $standing !== $guid && !$this->chosen)
		{
			$this->relate($view, $column, $standing, $fieldtype['name']);
			$this->report->set(
				'field.shared.' . $this->key($view) . '.' . $this->key($column),
				$standing
			);

			return true;
		}

		// a field's type, datatype and xml are what the source states, so a
		// re-run refreshes them; only the record's own bookkeeping is
		// scaffolding a new field needs
		if (!$this->store($definition, ['published']))
		{
			return false;
		}

		$this->shared[$shape] ??= $guid;
		$this->relate($view, $column, $guid, $fieldtype['name']);

		return true;
	}

	/**
	 * Record which field one view's column relates to.
	 *
	 * @param   string  $view       The view name.
	 * @param   string  $column     The column name.
	 * @param   string  $guid       The field's identity.
	 * @param   string  $fieldtype  The field type's name.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	protected function relate(string $view, string $column, string $guid, string $fieldtype): void
	{
		$this->resolved->set(
			$this->path($view) . '.written.' . $this->key($column) . '.guid',
			$guid
		);
		$this->resolved->set(
			$this->path($view) . '.written.' . $this->key($column) . '.fieldtype',
			$fieldtype
		);
	}

	/**
	 * What makes one field the field it is.
	 *
	 * Two definitions of the same shape are one field: the same name, the
	 * same type, the same column behind it and the same xml. Anything that
	 * differs in any of those is a field of its own.
	 *
	 * @param   object  $definition  The field definition.
	 *
	 * @return  string  The shape.
	 * @since   6.1.8
	 */
	protected function shape(object $definition): string
	{
		$shape = [];

		foreach ([
			'name', 'fieldtype', 'datatype', 'indexes', 'null_switch', 'store',
			'datalenght', 'datalenght_other', 'datadefault', 'datadefault_other', 'xml'
		] as $property)
		{
			$shape[$property] = $definition->{$property} ?? null;
		}

		return md5((string) json_encode($shape));
	}

	/**
	 * The attributes a resolved field states, flattened for comparison.
	 *
	 * @param   array<string, mixed>  $properties  The resolved properties.
	 *
	 * @return  array<string, mixed>  Attribute name keyed to its value.
	 * @since   6.1.6
	 */
	protected function attributes(array $properties): array
	{
		$attributes = (array) ($this->value($properties, 'attributes', []) ?? []);

		foreach (['validate', 'filter'] as $name)
		{
			$value = $this->value($properties, $name);

			if ($value !== null)
			{
				$attributes[$name] = $value;
			}
		}

		return $attributes;
	}

	/**
	 * The index names JCB claims for the columns it manages itself.
	 *
	 * Compiler\Architecture\Component\InstallSql writes each of these after a
	 * view's own field indexes, guarding each on the name of the column it
	 * indexes rather than on the name of the index. A field named for one of
	 * these indexes therefore slips past the guard and the table is written
	 * with the same key name twice, which MySQL refuses outright (1061) -- so
	 * the component builds and then installs nothing at all.
	 *
	 * @var    array<string, string>
	 * @since  6.1.8
	 */
	private const CLAIMED = [
		'idx_access' => 'access',
		'idx_checkout' => 'checked_out',
		'idx_createdby' => 'created_by',
		'idx_modifiedby' => 'modified_by',
		'idx_state' => 'published'
	];

	/**
	 * The index a field carries, on the scale JCB's own form offers.
	 *
	 * The schema and the field record rank keys differently, and the two used
	 * to be passed straight between each other: a source column's ranks run
	 * none, index, unique, primary, while the field form offers unique as 1 and
	 * a plain index as 2. Read one as the other and a unique key survives only
	 * by coincidence, a primary key becomes a plain index, and every plain
	 * index the component asked for is lost entirely.
	 *
	 * The primary key is not among them, because it is not a field: JCB writes
	 * the id column and its primary key itself.
	 *
	 * @param   int  $rank  The source column's key rank.
	 *
	 * @return  int  The value the field record stores.
	 * @since   6.1.8
	 */
	protected function indexes(int $rank, string $column): int
	{
		$index = match ($rank)
		{
			2 => 1,
			1 => 2,
			default => 0
		};

		if ($index === 0)
		{
			return 0;
		}

		// JCB names a field's index after its column and its own five after
		// the columns it manages, and MySQL refuses a table carrying one name
		// twice. A source column named "state" therefore asks for the very
		// name JCB gives the published column, and the whole install fails
		// with nothing built -- so the column and its field stand, and only
		// the index it asked for is given up, which a person can restore.
		if (isset(self::CLAIMED['idx_' . strtolower(trim($column))]))
		{
			$this->report->set(
				'skipped.index.claimed.' . $this->key($column),
				'JCB names its own index for the ' . self::CLAIMED['idx_' . strtolower(trim($column))]
				. ' column idx_' . strtolower(trim($column))
				. ', and one table cannot carry that name twice'
			);

			return 0;
		}

		return $index;
	}

	/**
	 * The JCB store code for a declared storage encoding.
	 *
	 * @param   string  $store  The declared encoding.
	 *
	 * @return  int  The JCB store code.
	 * @since   6.1.6
	 */
	protected function storeCode(string $store): int
	{
		switch (strtolower(trim($store)))
		{
			case 'base64':
				return 1;
			case 'json':
				return 2;
			case 'basic_encryption':
				return 3;
			case 'whmcs_encryption':
				return 4;
			case 'expert_mode_encryption':
				return 5;
			default:
				return 0;
		}
	}

	/**
	 * The name a field is listed under.
	 *
	 * A label may carry markup -- a note under the label, a link to a
	 * reference -- because a label is rendered as HTML. A field's name is
	 * read in lists, pickers and the field's own record, so it takes the
	 * words of the label without the markup, while the label itself is
	 * carried through to the field's xml exactly as the source states it.
	 *
	 * @param   string  $label   The field's stated label.
	 * @param   string  $column  The column the field stands for.
	 *
	 * @return  string  The readable name.
	 * @since   6.1.8
	 */
	protected function readable(string $label, string $column): string
	{
		if (!str_contains($label, '<'))
		{
			return trim($label);
		}

		$text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $label));
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

		return $text === '' ? $column : $text;
	}

	/**
	 * The component option, when it is known.
	 *
	 * @return  string  The com_ prefixed option, or an empty string.
	 * @since   6.1.6
	 */
	protected function option(): string
	{
		return (string) $this->source->get('code_name', '');
	}

	/**
	 * How many characters one column of this writer's table can hold.
	 *
	 * The Table class states the column's db type; a CHAR or VARCHAR names
	 * its capacity, and the text types hold more than any value this writer
	 * carries.
	 *
	 * @param   string  $column  The column name.
	 *
	 * @return  int  The capacity in characters.
	 * @since   6.1.7
	 */
	protected function capacity(string $column): int
	{
		$type = (string) ($this->tables->get($this->table(), $column, 'db')['type'] ?? '');

		if (preg_match('/^(?:VAR)?CHAR\((\d+)\)/i', $type, $size))
		{
			return (int) $size[1];
		}

		return PHP_INT_MAX;
	}
}
