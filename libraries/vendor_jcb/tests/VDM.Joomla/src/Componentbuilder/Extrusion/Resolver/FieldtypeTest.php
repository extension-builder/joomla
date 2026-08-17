<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Resolver;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Fieldtype;
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Tests\Support\TestCase;


/**
 * The Joomla form field type to JCB field type mapping.
 *
 * The mapping is data rather than a hardcoded table: each seeded field type row
 * carries a properties payload whose type entry states the Joomla XML type. These
 * tests pin the three policies that payload forces -- collision, version scope,
 * and the custom fallback -- because each one silently produces the wrong field
 * type rather than an error when it regresses.
 *
 * @since  6.1.6
 */
#[CoversClass(Fieldtype::class)]
final class FieldtypeTest extends TestCase
{
	/**
	 * Build a catalogue loader over the supplied rows.
	 *
	 * @param   array<int, array<string, mixed>>  $rows  The field type rows.
	 *
	 * @return  LoadInterface  A loader serving exactly those rows.
	 * @since   6.1.6
	 */
	private function loader(array $rows): LoadInterface
	{
		return new class ($rows) implements LoadInterface
		{
			/**
			 * The rows this loader serves.
			 *
			 * @var    array<int, object>
			 * @since  6.1.6
			 */
			private array $rows;

			/**
			 * Constructor.
			 *
			 * @param   array<int, array<string, mixed>>  $rows  The field type rows.
			 *
			 * @since   6.1.6
			 */
			public function __construct(array $rows)
			{
				$this->rows = array_map(
					static fn (array $row): object => (object) $row,
					$rows
				);
			}

			/**
			 * Serve the field type rows.
			 *
			 * @param   array       $select  The select map.
			 * @param   array       $tables  The table map.
			 * @param   array|null  $where   The where map.
			 * @param   array|null  $order   The order map.
			 * @param   int|null    $limit   The row limit.
			 *
			 * @return  array|null  The rows, or null for another table.
			 * @since   6.1.6
			 */
			public function items(array $select, array $tables, ?array $where = null,
				?array $order = null, ?int $limit = null): ?array
			{
				return $tables === ['a' => 'fieldtype'] ? $this->rows : null;
			}

			/**
			 * Unused loader method.
			 *
			 * @param   array       $select  The select map.
			 * @param   array       $tables  The table map.
			 * @param   array|null  $where   The where map.
			 * @param   array|null  $order   The order map.
			 * @param   int|null    $limit   The row limit.
			 *
			 * @return  array|null  Always null.
			 * @since   6.1.6
			 */
			public function rows(array $select, array $tables, ?array $where = null,
				?array $order = null, ?int $limit = null): ?array
			{
				return null;
			}

			/**
			 * Unused loader method.
			 *
			 * @param   array       $select  The select map.
			 * @param   array       $tables  The table map.
			 * @param   array|null  $where   The where map.
			 * @param   array|null  $order   The order map.
			 *
			 * @return  array|null  Always null.
			 * @since   6.1.6
			 */
			public function row(array $select, array $tables, ?array $where = null,
				?array $order = null): ?array
			{
				return null;
			}

			/**
			 * Unused loader method.
			 *
			 * @param   array       $select  The select map.
			 * @param   array       $tables  The table map.
			 * @param   array|null  $where   The where map.
			 * @param   array|null  $order   The order map.
			 *
			 * @return  object|null  Always null.
			 * @since   6.1.6
			 */
			public function item(array $select, array $tables, ?array $where = null,
				?array $order = null): ?object
			{
				return null;
			}

			/**
			 * Unused loader method.
			 *
			 * @param   mixed  $field   The field.
			 * @param   array  $tables  The table map.
			 * @param   array  $filter  The filter map.
			 *
			 * @return  int|null  Always null.
			 * @since   6.1.6
			 */
			public function max($field, array $tables, array $filter): ?int
			{
				return null;
			}

			/**
			 * Unused loader method.
			 *
			 * @param   array  $tables  The table map.
			 * @param   array  $filter  The filter map.
			 *
			 * @return  int|null  Always null.
			 * @since   6.1.6
			 */
			public function count(array $tables, array $filter): ?int
			{
				return null;
			}

			/**
			 * Unused loader method.
			 *
			 * @param   array       $select  The select map.
			 * @param   array       $tables  The table map.
			 * @param   array|null  $where   The where map.
			 * @param   array|null  $order   The order map.
			 *
			 * @return  mixed  Always null.
			 * @since   6.1.6
			 */
			public function value(array $select, array $tables, ?array $where = null,
				?array $order = null)
			{
				return null;
			}

			/**
			 * Unused loader method.
			 *
			 * @param   array       $select  The select map.
			 * @param   array       $tables  The table map.
			 * @param   array|null  $where   The where map.
			 * @param   array|null  $order   The order map.
			 * @param   int|null    $limit   The row limit.
			 *
			 * @return  array|null  Always null.
			 * @since   6.1.6
			 */
			public function values(array $select, array $tables, ?array $where = null,
				?array $order = null, ?int $limit = null): ?array
			{
				return null;
			}
		};
	}

	/**
	 * Build a field type row whose properties declare an XML type.
	 *
	 * @param   int            $id          The field type id.
	 * @param   string         $name        The JCB field type name.
	 * @param   string         $xmlType     The Joomla XML type it advertises.
	 * @param   array<string>  $properties  Extra declared property names.
	 *
	 * @return  array<string, mixed>  The row.
	 * @since   6.1.6
	 */
	private function row(int $id, string $name, string $xmlType, array $properties = []): array
	{
		$payload = [['name' => 'type', 'example' => $xmlType]];

		foreach ($properties as $property => $example)
		{
			$payload[] = is_string($property)
				? ['name' => $property, 'example' => (string) $example]
				: ['name' => (string) $example, 'example' => ''];
		}

		return [
			'id' => $id,
			'guid' => $this->guid($id),
			'name' => $name,
			'properties' => json_encode($payload)
		];
	}

	/**
	 * A stable well formed guid for one served row.
	 *
	 * @param   int  $id  The row id.
	 *
	 * @return  string  The guid.
	 * @since   6.1.6
	 */
	private function guid(int $id): string
	{
		return sprintf('%08d-0000-4000-8000-000000000000', $id);
	}

	/**
	 * Build a resolver over the supplied rows.
	 *
	 * @param   array<int, array<string, mixed>>  $rows    The field type rows.
	 * @param   string                            $layout  The detected layout family.
	 *
	 * @return  Fieldtype  The resolver under test.
	 * @since   6.1.6
	 */
	private function resolver(array $rows, string $layout = ''): Fieldtype
	{
		$source = new Source();

		if ($layout !== '')
		{
			$source->set('layout', $layout);
		}

		return new Fieldtype($this->loader($rows), $source, new Report());
	}

	/**
	 * The catalogue is keyed by the XML type each row advertises.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCatalogueIsKeyedByTheAdvertisedXmlType(): void
	{
		$resolver = $this->resolver([
			$this->row(1, 'Calendar', 'calendar'),
			$this->row(2, 'Editor', 'editor')
		]);

		$catalogue = $resolver->catalogue();

		$this->assertArrayHasKey('calendar', $catalogue);
		$this->assertArrayHasKey('editor', $catalogue);
		$this->assertSame('Calendar', $catalogue['calendar']['name']);
		$this->assertSame(2, $catalogue['editor']['id']);
		$this->assertSame(1, $resolver->id('calendar'));
		$this->assertSame('Editor', $resolver->name('editor'));
	}

	/**
	 * A row with an unreadable properties payload contributes no XML type.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUnreadablePropertiesContributeNoXmlType(): void
	{
		$resolver = $this->resolver([
			['id' => 7, 'name' => 'Broken', 'properties' => 'not json'],
			['id' => 8, 'name' => 'Missing', 'properties' => ''],
			$this->row(9, 'Text', 'text')
		]);

		$catalogue = $resolver->catalogue();

		$this->assertArrayNotHasKey('broken', $catalogue);
		$this->assertCount(1, $catalogue);
		$this->assertSame('Text', $resolver->name('text'));
	}

	/**
	 * A field type is still reachable by its own name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAFieldTypeIsReachableByItsOwnName(): void
	{
		$resolver = $this->resolver([$this->row(3, 'Subform', 'subform')], 'J4');

		$this->assertSame('Subform', $resolver->name('SUBFORM'));
		$this->assertSame('Subform', $resolver->name('Subform'));
	}

	/**
	 * Two field types claiming one XML type resolve deliberately.
	 *
	 * Both Text and Tel advertise the text XML type in the seeded catalogue. The
	 * override must settle it rather than leaving the winner to row order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testACollisionIsSettledByTheOverride(): void
	{
		$telFirst = $this->resolver([
			$this->row(4, 'Tel', 'text'),
			$this->row(5, 'Text', 'text')
		]);
		$textFirst = $this->resolver([
			$this->row(5, 'Text', 'text'),
			$this->row(4, 'Tel', 'text')
		]);

		$this->assertSame('Text', $telFirst->name('text'));
		$this->assertSame('Text', $textFirst->name('text'));
	}

	/**
	 * A collision is recorded so the choice is visible in the report.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testACollisionIsRecorded(): void
	{
		$report = new Report();
		$resolver = new Fieldtype(
			$this->loader([
				$this->row(4, 'Tel', 'text'),
				$this->row(5, 'Text', 'text')
			]),
			new Source(),
			$report
		);

		$resolver->catalogue();

		$this->assertSame('Text', $report->get('collision.fieldtype.text'));
	}

	/**
	 * A version scoped type is only used for the matching target.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepeatableIsScopedToJoomlaThree(): void
	{
		$rows = [$this->row(6, 'Repeatable', 'repeatable'), $this->row(7, 'Custom', 'subjects')];

		$this->assertSame('Repeatable', $this->resolver($rows, 'J3')->name('repeatable'));
		$this->assertSame('Custom', $this->resolver($rows, 'J4')->name('repeatable'));
	}

	/**
	 * Subform is scoped away from Joomla 3.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSubformIsScopedAwayFromJoomlaThree(): void
	{
		$rows = [$this->row(8, 'Subform', 'subform'), $this->row(9, 'Custom', 'subjects')];

		$this->assertSame('Subform', $this->resolver($rows, 'J6')->name('subform'));
		$this->assertSame('Custom', $this->resolver($rows, 'J3')->name('subform'));
	}

	/**
	 * An unknown type becomes the component's own custom field type.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAnUnknownTypeFallsBackToACustomFieldType(): void
	{
		$resolver = $this->resolver([
			$this->row(10, 'Custom', 'subjects'),
			$this->row(11, 'CustomUser', 'staffusers')
		]);

		$this->assertSame('Custom', $resolver->name('mywidget'));
		$this->assertSame('CustomUser', $resolver->name('staffuserpicker'));
		$this->assertSame('Custom', $resolver->name(''));
	}

	/**
	 * An unmapped type is recorded rather than silently absorbed.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAnUnmappedTypeIsRecorded(): void
	{
		$report = new Report();
		$resolver = new Fieldtype(
			$this->loader([$this->row(12, 'Custom', 'subjects')]),
			new Source(),
			$report
		);

		$resolver->resolve('my-widget');

		$this->assertSame('my-widget', $report->get('unmapped.fieldtype.my_widget'));
	}

	/**
	 * Resolution can refuse to fall back when a caller needs certainty.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFallbackCanBeRefused(): void
	{
		$resolver = $this->resolver([$this->row(13, 'Custom', 'subjects')]);

		$this->assertNull($resolver->resolve('mywidget', false));
		$this->assertNotNull($resolver->resolve('mywidget'));
	}

	/**
	 * The declared property names of a field type are exposed.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDeclaredPropertyNamesAreExposed(): void
	{
		$resolver = $this->resolver([
			$this->row(14, 'Text', 'text', ['name', 'label', 'size'])
		]);

		$this->assertSame(['type', 'name', 'label', 'size'], $resolver->properties('text'));
		$this->assertSame([], $resolver->properties('nothing-here'));
	}

	/**
	 * The catalogue is loaded once and reused.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheCatalogueIsLoadedOnce(): void
	{
		$resolver = $this->resolver([$this->row(15, 'Text', 'text')]);

		$this->assertSame($resolver->catalogue(), $resolver->catalogue());
		$this->assertCount(1, $resolver->catalogue());
	}
	/**
	 * A field type is identified by its guid, not by its install-specific id.
	 *
	 * field.fieldtype is a VARCHAR(36) holding the field type's own guid. A numeric
	 * id written there means nothing to a compile and nothing to another install, so
	 * the guid is the only identity the resolver may hand out.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEveryResolvedTypeCarriesItsGuid(): void
	{
		$resolved = $this->resolver([$this->row(3, 'Text', 'text')])->resolve('text');

		$this->assertIsArray($resolved);
		$this->assertSame($this->guid(3), $resolved['guid']);
		$this->assertMatchesRegularExpression(
			'/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/',
			$resolved['guid'],
			'The identity handed out has to be a well formed guid.'
		);
		$this->assertSame('Text', $resolved['name']);
	}

	/**
	 * A field type JCB generates never claims a real component's type string.
	 *
	 * A generated field type declares what it extends, and its own type is a sample
	 * name the author picks -- Custom examples subjects. Indexing that as a literal
	 * would have a component's own subjects field claimed by the wrong record.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAGeneratedFieldTypeDoesNotClaimItsExampleTypeString(): void
	{
		$report = new Report();
		$resolver = new Fieldtype(
			$this->loader([
				$this->row(1, 'List', 'list'),
				$this->row(6, 'Custom', 'subjects', ['extends' => 'list'])
			]),
			new Source(),
			$report
		);

		$this->assertTrue(
			$resolver->generated(json_encode([
				['name' => 'type', 'example' => 'subjects'],
				['name' => 'extends', 'example' => 'list']
			]))
		);
		$this->assertFalse(
			$resolver->generated(json_encode([['name' => 'type', 'example' => 'text']]))
		);
		$this->assertFalse($resolver->generated(''));
		$this->assertFalse($resolver->generated('not json'));

		$resolver->catalogue();

		$this->assertSame(
			'subjects',
			$report->get('fieldtype.generated.Custom'),
			'A generated type is recorded as such rather than silently dropped.'
		);
		$this->assertSame(
			'Custom',
			$resolver->resolve('subjects')['name'],
			'It still answers, but through the fallback rather than as a literal match.'
		);
		$this->assertSame(
			'subjects',
			$report->get('unmapped.fieldtype.subjects'),
			'Reaching the fallback is reported, so nothing looks like a real match.'
		);
	}

	/**
	 * Two claimants of one XML type are told apart by the field's own validation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheNarrowerClaimantWinsWhenTheFieldSaysSo(): void
	{
		$report = new Report();
		$resolver = new Fieldtype(
			$this->loader([
				$this->row(1, 'Text', 'text', ['validate' => '', 'filter' => 'STRING']),
				$this->row(41, 'Tel', 'text', ['validate' => 'tel', 'filter' => 'tel'])
			]),
			new Source(),
			$report
		);

		$this->assertSame(
			'Text',
			$resolver->resolve('text')['name'],
			'With nothing to go on the broader type wins, as it always did.'
		);
		$this->assertNull(
			$resolver->discriminate('text', []),
			'No stated attribute means nothing to discriminate on.'
		);
		$this->assertNull($resolver->discriminate('text', ['validate' => 'guid']));
		$this->assertSame(
			'Tel',
			$resolver->discriminate('text', ['validate' => 'tel'])['name'],
			'A text field validated as tel is a telephone number.'
		);
		$this->assertSame(
			'Tel by validate="tel"',
			$report->get('fieldtype.discriminated.text')
		);
		$this->assertNull(
			$resolver->discriminate('list', ['validate' => 'tel']),
			'A type only one field type claims has nothing to discriminate.'
		);
	}
}
