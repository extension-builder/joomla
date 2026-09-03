<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    3rd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Resolver;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Proposal;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Delta;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Diff;
use VDM\Joomla\Componentbuilder\Table as JcbTable;
use VDM\Tests\Support\ExtrusionItemFixture;
use VDM\Tests\Support\TestCase;


/**
 * What a write would change about the record that stands.
 *
 * @since 6.2.0
 */
#[CoversClass(Delta::class)]
#[CoversClass(Proposal::class)]
final class DeltaTest extends TestCase
{
	/**
	 * The identity every test weighs.
	 *
	 * @var    string
	 * @since  6.2.0
	 */
	private const GUID = 'aaaaaaaa-1111-4111-8111-aaaaaaaaaaaa';

	/**
	 * The data item the standing record is read from.
	 *
	 * @var    ExtrusionItemFixture
	 * @since  6.2.0
	 */
	private ExtrusionItemFixture $item;

	/**
	 * The proposal registry under test.
	 *
	 * @var    Proposal
	 * @since  6.2.0
	 */
	private Proposal $proposal;

	/**
	 * The weigher under test.
	 *
	 * @var    Delta
	 * @since  6.2.0
	 */
	private Delta $delta;

	/**
	 * Start every test from a fresh weigher.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->item = new ExtrusionItemFixture();
		$this->proposal = new Proposal();
		$this->delta = new Delta($this->item, new JcbTable(), new Diff(), $this->proposal);
	}

	/**
	 * A record nothing stands under is created, and all of it is an addition.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testARecordNothingStandsUnderIsAllAddition(): void
	{
		$delta = $this->weigh((object) [
			'guid' => self::GUID,
			'name' => 'Line One',
			'xml' => "<field\n\tname=\"line_one\"\n/>"
		], false);

		$this->assertSame('create', $delta['action']);
		$this->assertTrue($delta['changed']);
		$this->assertSame(0, $delta['deletions'], 'Nothing stood, so nothing is taken away.');
		$this->assertSame(4, $delta['additions'], 'The name is one line and the xml is three.');
		$this->assertSame(['name', 'xml'], array_keys($delta['columns']));
		$this->assertSame('', $delta['columns']['name']['before']);
		$this->assertSame('Line One', $delta['columns']['name']['after']);
	}

	/**
	 * A record that already says what the write says is left alone.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testARecordThatAlreadySaysItIsUnchanged(): void
	{
		$this->stands(['name' => 'Line One', 'xml' => '<field name="line_one" />']);

		$delta = $this->weigh((object) [
			'guid' => self::GUID,
			'name' => 'Line One',
			'xml' => '<field name="line_one" />'
		]);

		$this->assertSame('update', $delta['action']);
		$this->assertFalse($delta['changed'], 'Nothing about the record would move.');
		$this->assertSame([], $delta['columns']);
		$this->assertSame(0, $delta['additions']);
		$this->assertSame(0, $delta['deletions']);
	}

	/**
	 * Only the columns a write would carry are weighed, and only those that move.
	 *
	 * A column the record holds and the write never names is untouched by that
	 * write, so it is not a difference: the question is what this write would
	 * change, never how two records differ.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testOnlyTheColumnsTheWriteCarriesAreWeighed(): void
	{
		$this->stands([
			'name' => 'Line One',
			'xml' => '<field name="line_one" />',
			'description' => 'A description nobody is touching'
		]);

		$delta = $this->weigh((object) [
			'guid' => self::GUID,
			'name' => 'Line One',
			'xml' => '<field name="line_one" label="Line One" />'
		]);

		$this->assertSame(['xml'], array_keys($delta['columns']), 'The name did not move and the description is not named.');
		$this->assertSame(1, $delta['additions']);
		$this->assertSame(1, $delta['deletions'], 'One line replaced is one gained and one lost.');
	}

	/**
	 * The identity is how a record is found, never something a write changes.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTheIdentityIsNeverAChange(): void
	{
		$this->stands(['name' => 'Line One']);

		$delta = $this->weigh((object) ['guid' => self::GUID, 'name' => 'Line One']);

		$this->assertArrayNotHasKey('guid', $delta['columns']);
		$this->assertFalse($delta['changed']);
	}

	/**
	 * A value is weighed as the column would hold it, and shown as a person reads it.
	 *
	 * The tabs of a view are stored as JSON and read back as a structure. What
	 * decides whether they moved is what would land in the column; what a
	 * person is shown is the structure laid out to read.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAValueIsWeighedAsStoredAndShownAsRead(): void
	{
		$this->item->serve('admin_view', self::GUID, (object) [
			'guid' => self::GUID,
			'addtabs' => (object) ['addtabs0' => (object) ['name' => 'Details']]
		]);
		$this->item->identity('admin_view', self::GUID, 7);

		$same = $this->delta->weigh('admin_view', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'addtabs' => ['addtabs0' => ['name' => 'Details']]
		], true);

		$this->assertFalse(
			$same['changed'],
			'The same tabs on their way in and read back out are the same tabs.'
		);

		$moved = $this->delta->weigh('admin_view', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'addtabs' => ['addtabs0' => ['name' => 'Details'], 'addtabs1' => ['name' => 'Metrics']]
		], true);

		$this->assertTrue($moved['changed']);
		$this->assertSame('text', $moved['columns']['addtabs']['shape']);
		$this->assertStringContainsString(
			"\n",
			$moved['columns']['addtabs']['after'],
			'A subform read as one long line is a subform nobody can read.'
		);
		$this->assertStringContainsString('"Metrics"', $moved['columns']['addtabs']['after']);
	}

	/**
	 * A stored text is weighed on the text, not on the encoding it is kept in.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAStoredTextIsWeighedOnItsText(): void
	{
		$this->item->serve('power', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\tpublic function run(): void\n\t{\n\t}"
		]);
		$this->item->identity('power', self::GUID, 3);

		$same = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\tpublic function run(): void\n\t{\n\t}"
		], true);

		$this->assertFalse($same['changed'], 'The body reads the same, so nothing is written.');

		$moved = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\tpublic function run(): void\n\t{\n\t\t\$this->go();\n\t}"
		], true);

		$this->assertTrue($moved['changed']);
		$this->assertSame(1, $moved['additions']);
		$this->assertSame(0, $moved['deletions'], 'A line added to a body takes nothing away.');
		$this->assertSame('text', $moved['columns']['main_class_code']['shape']);
	}

	/**
	 * A column that was never set and a column set to nothing are the same thing.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAnUnsetColumnAndAnEmptyOneAreTheSame(): void
	{
		$this->stands(['name' => 'Line One']);

		$delta = $this->weigh((object) ['guid' => self::GUID, 'name' => 'Line One', 'description' => '']);

		$this->assertFalse(
			$delta['changed'],
			'Writing nothing where nothing stands changes nothing.'
		);
	}

	/**
	 * A column that was never set and one holding an empty list say the same nothing.
	 *
	 * A record read back out of the database carries nothing in several
	 * shapes -- null, an empty string, an empty list, an empty object -- and a
	 * write that puts one of those where another stands changes nothing a
	 * person would ever see. Reporting it would put "[]" on the board as
	 * though it were work.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testEveryShapeOfNothingIsTheSameNothing(): void
	{
		$this->item->serve('power', self::GUID, (object) [
			'guid' => self::GUID,
			'implements' => '',
			'extendsinterfaces' => null,
			'use_selection' => (object) [],
			'description' => 'A power that stands'
		]);
		$this->item->identity('power', self::GUID, 5);

		$delta = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'implements' => [],
			'extendsinterfaces' => [],
			'use_selection' => [],
			'description' => 'A power that stands'
		], true);

		$this->assertFalse(
			$delta['changed'],
			'Writing nothing where nothing stands is not a change, whatever shape the nothing takes.'
		);
		$this->assertSame([], $delta['columns']);
	}

	/**
	 * Filling something in where nothing stood is still a change.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFillingNothingInIsStillAChange(): void
	{
		$this->item->serve('power', self::GUID, (object) ['guid' => self::GUID, 'implements' => '']);
		$this->item->identity('power', self::GUID, 5);

		$delta = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'implements' => ['0' => 'some-interface-guid']
		], true);

		$this->assertTrue($delta['changed']);
		$this->assertSame(['implements'], array_keys($delta['columns']));
		$this->assertSame('', $delta['columns']['implements']['before']);
		$this->assertStringContainsString('some-interface-guid', $delta['columns']['implements']['after']);
	}

	/**
	 * Every record weighed is proposed, and each proposal names its board row.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testEveryRecordWeighedIsProposedUnderItsRow(): void
	{
		$this->weigh((object) ['guid' => self::GUID, 'name' => 'Line One'], false);
		$this->delta->weigh('admin_fields', 'admin_view', self::GUID, (object) [
			'admin_view' => self::GUID,
			'addfields' => ['addfields0' => ['field' => 'x']]
		], false, 'admin_view|address');

		$proposed = $this->proposal->record('field', self::GUID);

		$this->assertSame('field|address.line_one', $proposed['origin']);
		$this->assertSame('create', $proposed['action']);
		$this->assertCount(2, $this->proposal->records());

		$summary = $this->proposal->summary();

		$this->assertSame(['field|address.line_one', 'admin_view|address'], array_keys($summary));
		$this->assertTrue($summary['admin_view|address']['changed']);
		$this->assertSame(1, $summary['admin_view|address']['records']);
	}

	/**
	 * A board row is worth what everything written under it is worth.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testARowIsWorthEverythingWrittenUnderIt(): void
	{
		$this->item->serve('admin_view', self::GUID, (object) ['guid' => self::GUID, 'name_single' => 'Address']);
		$this->item->identity('admin_view', self::GUID, 7);

		$this->delta->weigh('admin_view', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'name_single' => 'Address Type'
		], true, 'admin_view|address');
		$this->delta->weigh('admin_fields', 'admin_view', self::GUID, (object) [
			'admin_view' => self::GUID,
			'addfields' => ['addfields0' => ['field' => 'x']]
		], false, 'admin_view|address');

		$summary = $this->proposal->summary();

		$this->assertSame('update', $summary['admin_view|address']['action'], 'A row that updates anything is an update.');
		$this->assertSame(2, $summary['admin_view|address']['records']);
		$this->assertSame(1, $summary['admin_view|address']['deletions']);
		$this->assertGreaterThan(1, $summary['admin_view|address']['additions']);
	}

	/**
	 * Weigh one field definition under the standard row.
	 *
	 * @param   object  $definition  The definition a write would carry.
	 * @param   bool    $exists      Whether a record already stands.
	 *
	 * @return  array<string, mixed>  What the write would change.
	 * @since   6.2.0
	 */
	private function weigh(object $definition, bool $exists = true): array
	{
		return $this->delta->weigh(
			'field', 'guid', self::GUID, $definition, $exists, 'field|address.line_one'
		);
	}

	/**
	 * Declare the field record that stands.
	 *
	 * @param   array<string, mixed>  $columns  The columns it holds.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	private function stands(array $columns): void
	{
		$this->item->serve('field', self::GUID, (object) (['guid' => self::GUID] + $columns));
		$this->item->identity('field', self::GUID, 11);
	}
}
