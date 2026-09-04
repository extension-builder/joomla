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
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Placeholders;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Proposal;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Delta;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Diff;
use VDM\Joomla\Componentbuilder\Table as JcbTable;
use VDM\Tests\Support\ExtrusionItemFixture;
use VDM\Tests\Support\ExtrusionPowerLoadFixture;
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
	 * The served database boundary.
	 *
	 * @var    ExtrusionPowerLoadFixture
	 * @since  6.2.0
	 */
	private ExtrusionPowerLoadFixture $load;

	/**
	 * The run report registry.
	 *
	 * @var    Report
	 * @since  6.2.0
	 */
	private Report $report;

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
		$config = new Config();
		$config->set('component', 3);
		$this->load = new ExtrusionPowerLoadFixture();
		$this->load->component(3, 'comp-guid', 'demo', 1, 'VDM');
		$this->load->placeholder(25, '[[[upload_max_filesize]]]', '128M');
		$this->delta = new Delta(
			$this->item,
			new JcbTable(),
			new Diff(),
			$this->proposal,
			new Placeholders($config, $this->load, new Report(), new Source()),
			$this->report = new Report()
		);
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
	 * A line ends the same whichever way it was broken.
	 *
	 * The readers fold every line ending of a source file to one, and what a
	 * person saved through a form may carry the other. Nobody can see the
	 * difference, so a text that differs only there is not written again --
	 * and when it does change, the lines counted are the lines shown.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testALineEndsTheSameWhicheverWayItWasBroken(): void
	{
		$this->item->serve('power', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\tpublic function run(): void\r\n\t{\r\n\t}"
		]);
		$this->item->identity('power', self::GUID, 3);

		$same = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\tpublic function run(): void\n\t{\n\t}"
		], true);

		$this->assertFalse($same['changed'], 'The body reads the same, whichever way its lines were broken.');

		$moved = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\tpublic function run(): void\n\t{\n\t\t\$this->go();\n\t}"
		], true);

		$this->assertTrue($moved['changed']);
		$this->assertSame(1, $moved['additions'], 'One line was added, and that is the one line counted.');
		$this->assertSame(0, $moved['deletions']);
		$this->assertStringNotContainsString("\r", $moved['columns']['main_class_code']['before']);
	}

	/**
	 * A placeholder a person wrote is never unsaid.
	 *
	 * The source a record is weighed against was compiled from that very
	 * record, so where the record defers to a placeholder the source states
	 * what the compiler resolved it to. Weighing the two as text would call
	 * that a change and write the resolved value over the placeholder the
	 * person chose -- and the next compile would still produce the same file,
	 * having lost the only thing that made the record portable.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAPlaceholderAPersonWroteIsNeverUnsaid(): void
	{
		$this->item->serve('power', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\t\t\$limit = '[[[upload_max_filesize]]]';"
		]);
		$this->item->identity('power', self::GUID, 3);

		$same = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\t\t\$limit = '128M';"
		], true);

		$this->assertFalse(
			$same['changed'],
			'The record already says what the source states, only more carefully.'
		);

		$moved = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\t\t\$limit = '256M';"
		], true);

		$this->assertTrue(
			$moved['changed'],
			'What the placeholder stands for did move, and that is a change.'
		);
	}

	/**
	 * A record that spells the name out is restated through the placeholder.
	 *
	 * The two compile to the same file, so nothing a person reads in the
	 * component moves -- but the record stops being bound to the one
	 * component it was lifted out of, which is the whole point of writing it
	 * that way, so this one is worth the write.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testARecordSpellingTheNameOutIsRestatedThroughThePlaceholder(): void
	{
		$this->item->serve('power', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\t\t\$table = '#__demo_item';"
		]);
		$this->item->identity('power', self::GUID, 3);

		$restated = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\t\t\$table = '#__[[[component]]]_item';"
		], true);

		$this->assertTrue(
			$restated['changed'],
			'The write defers what the record spells out, and that is worth writing.'
		);
	}

	/**
	 * A deferral this run cannot resolve is never written over.
	 *
	 * A record may defer to something only the compiler can produce -- a whole
	 * generated array of a component's fields, say. Nothing here can stand for
	 * it, so nothing here can weigh it either, and writing what the compiler
	 * produced over the deferral that produced it is the one answer that is
	 * certainly wrong.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testADeferralThisRunCannotResolveIsNeverWrittenOver(): void
	{
		$generated = '#' . '#' . '#' . 'ALL_COMPONENT_FIELDS' . '#' . '#' . '#';
		$this->item->serve('power', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\t\tprotected array \$tables = " . $generated . ';'
		]);
		$this->item->identity('power', self::GUID, 3);

		$kept = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\t\tprotected array \$tables = ['item' => ['id']];"
		], true);

		$this->assertFalse(
			$kept['changed'],
			'Nothing here can stand for that, so nothing here may write over it.'
		);
		$this->assertSame(
			['ALL_COMPONENT_FIELDS'],
			$this->report->get(
				'kept.deferred.power.' . str_replace('-', '_', self::GUID) . '.main_class_code'
			)
		);
	}

	/**
	 * A placeholder says the same thing under either of its wrappers.
	 *
	 * The compiler registers every placeholder under both, and substitutes
	 * them with the same bare replacement. JCB writes both itself -- a person
	 * types the bracketed form into a form, the compiler's own custom code
	 * extractor stores the hashed one -- so a write that only swapped one for
	 * the other would rewrite what a person curated and change nothing.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAPlaceholderSaysTheSameThingUnderEitherWrapper(): void
	{
		// the record names the placeholder through the wrapper JCB's own
		// custom code extractor writes
		$hashed = '#' . '#' . '#' . 'component' . '#' . '#' . '#';
		$this->item->serve('power', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\t\t\$table = '#__" . $hashed . "_item';"
		]);
		$this->item->identity('power', self::GUID, 3);

		$same = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\t\t\$table = '#__[[[component]]]_item';"
		], true);

		$this->assertFalse(
			$same['changed'],
			'Both wrappers name the same placeholder, so neither is a change.'
		);

		$moved = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'main_class_code' => "\t\t\$table = '#__[[[Component]]]_item';"
		], true);

		$this->assertTrue(
			$moved['changed'],
			'A different placeholder is a different thing to say.'
		);
	}

	/**
	 * A subform says the same thing whichever way it was saved.
	 *
	 * A form saves its keys in the form's order and every value as text; a
	 * writer composes the same rows in its own order and with numbers where
	 * the form had text. A person reads both as the same subform, so neither
	 * the order nor the spelling of a number is a change -- and when a value
	 * does move, only that value is shown as moved.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testASubformSaysTheSameThingWhicheverWayItWasSaved(): void
	{
		$this->item->serve('admin_fields_conditions', self::GUID, (object) [
			'admin_view' => self::GUID,
			'addconditions' => (object) ['addconditions0' => (object) [
				'target_field' => 'field-a',
				'target_behavior' => '1',
				'target_relation' => '0',
				'match_field' => 'field-b',
				'match_behavior' => '1',
				'match_options' => 'yes'
			]]
		]);
		$this->item->identity('admin_fields_conditions', self::GUID, 9);

		$same = $this->delta->weigh('admin_fields_conditions', 'admin_view', self::GUID, (object) [
			'admin_view' => self::GUID,
			'addconditions' => ['addconditions0' => [
				'target_field' => 'field-a',
				'match_field' => 'field-b',
				'target_behavior' => 1,
				'target_relation' => 0,
				'match_behavior' => 1,
				'match_options' => 'yes'
			]]
		], true);

		$this->assertFalse(
			$same['changed'],
			'The rows say the same thing in another order and with numbers spelt as numbers.'
		);

		$moved = $this->delta->weigh('admin_fields_conditions', 'admin_view', self::GUID, (object) [
			'admin_view' => self::GUID,
			'addconditions' => ['addconditions0' => [
				'target_field' => 'field-a',
				'match_field' => 'field-b',
				'target_behavior' => 2,
				'target_relation' => 0,
				'match_behavior' => 1,
				'match_options' => 'yes'
			]]
		], true);

		$this->assertTrue($moved['changed']);
		$this->assertSame(1, $moved['additions'], 'One value moved, and that is the one line counted.');
		$this->assertSame(1, $moved['deletions']);
	}

	/**
	 * A list keeps its order, because there the position is the meaning.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAListInAnotherOrderIsAnotherList(): void
	{
		$this->item->serve('power', self::GUID, (object) [
			'guid' => self::GUID,
			'implements' => ['first-guid', 'second-guid']
		]);
		$this->item->identity('power', self::GUID, 5);

		$delta = $this->delta->weigh('power', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'implements' => ['second-guid', 'first-guid']
		], true);

		$this->assertTrue($delta['changed']);
	}

	/**
	 * A record coming into being shows only what it fills in.
	 *
	 * A column the creation leaves empty adds nothing a person could read, so
	 * nothing is shown for it; but the record still comes into being, which
	 * is a change even when everything it carries is empty.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testACreationShowsOnlyWhatItFillsIn(): void
	{
		$delta = $this->weigh((object) [
			'guid' => self::GUID,
			'name' => 'Line One',
			'description' => '',
			'datadefault' => null,
			'implements' => []
		], false);

		$this->assertTrue($delta['changed']);
		$this->assertSame(['name'], array_keys($delta['columns']), 'The empty columns are not shown as changes.');
		$this->assertSame(1, $delta['additions']);

		$empty = $this->weigh((object) ['guid' => self::GUID, 'description' => ''], false);

		$this->assertTrue($empty['changed'], 'A record that does not stand comes into being, and that is a change.');
		$this->assertSame([], $empty['columns']);
		$this->assertSame('create', $empty['action']);
	}

	/**
	 * The action follows whether the record stands, not what the read brought back.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTheActionFollowsWhetherTheRecordStands(): void
	{
		// the record stands, but nothing of it can be read back
		$this->item->identity('field', self::GUID, 11);

		$delta = $this->weigh((object) ['guid' => self::GUID, 'name' => 'Line One', 'description' => '']);

		$this->assertSame('update', $delta['action'], 'A record the writer found standing is updated, never created.');
		$this->assertTrue($delta['changed']);
		$this->assertSame(['name'], array_keys($delta['columns']), 'What cannot be read back is weighed against nothing.');
	}

	/**
	 * A record two rows both compose answers on both rows.
	 *
	 * Each row must answer for its own write; the write that would stand is
	 * the last one made, and that is the record's own answer.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testARecordTwoRowsComposeAnswersOnBothRows(): void
	{
		$this->delta->weigh('field', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'name' => 'Line One'
		], false, 'field|address.line_one');
		$this->delta->weigh('field', 'guid', self::GUID, (object) [
			'guid' => self::GUID,
			'name' => 'Line One',
			'description' => 'Stated by the other view'
		], false, 'field|contact.line_one');

		$summary = $this->proposal->summary();

		$this->assertSame(['field|address.line_one', 'field|contact.line_one'], array_keys($summary));
		$this->assertSame(1, $summary['field|address.line_one']['additions']);
		$this->assertSame(2, $summary['field|contact.line_one']['additions']);
		$this->assertCount(2, $this->proposal->records());
		$this->assertSame(
			'field|contact.line_one',
			$this->proposal->record('field', self::GUID)['origin'],
			'The write that would stand is the last one made.'
		);
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
