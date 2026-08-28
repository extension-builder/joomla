<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    28th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Resolver;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Decision;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View as ViewRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Candidates;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\FieldXml;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Fieldtype;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Pairing;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Record;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Sharing;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Standing;
use VDM\Joomla\Componentbuilder\Table;
use VDM\Tests\Support\ExtrusionCatalogueFixture;
use VDM\Tests\Support\ExtrusionDatabaseFixture;
use VDM\Tests\Support\TestCase;


/**
 * One field per stated identity, linked into every view that states it.
 *
 * The rule under test is the author's own: a stated Global Unique ID is the
 * same field always; otherwise the code name, the label, the field type, the
 * database shape and every stated XML property must match exactly -- required
 * true and required false are two different fields. A settled group takes one
 * written identity: the person's group verdict outranks all, then a record
 * already standing in the paired component -- recognised by its identity or
 * by its properties hash -- is reused, and only then does the first view in
 * table order own a fresh record. Nothing outside the component being
 * extruded and the component it is paired against is consulted.
 *
 * @since  6.1.9
 */
#[CoversClass(Sharing::class)]
#[CoversClass(Standing::class)]
#[UsesClass(Candidates::class)]
#[UsesClass(Config::class)]
#[UsesClass(FieldXml::class)]
#[UsesClass(Fieldtype::class)]
#[UsesClass(Guid::class)]
#[UsesClass(Pairing::class)]
#[UsesClass(Record::class)]
final class SharingTest extends TestCase
{
	/**
	 * The resolved definition registry.
	 *
	 * @var    Resolved
	 * @since  6.1.9
	 */
	private Resolved $resolved;

	/**
	 * The source identity registry.
	 *
	 * @var    Source
	 * @since  6.1.9
	 */
	private Source $source;

	/**
	 * The run report registry.
	 *
	 * @var    Report
	 * @since  6.1.9
	 */
	private Report $report;

	/**
	 * The pairing resolver over an empty decision board.
	 *
	 * @var    Pairing
	 * @since  6.1.9
	 */
	private Pairing $pairing;

	/**
	 * The identity resolver.
	 *
	 * @var    Guid
	 * @since  6.1.9
	 */
	private Guid $guid;

	/**
	 * The extrusion configuration.
	 *
	 * @var    Config
	 * @since  6.1.9
	 */
	private Config $config;

	/**
	 * The declarative database boundary.
	 *
	 * @var    ExtrusionDatabaseFixture
	 * @since  6.1.9
	 */
	private ExtrusionDatabaseFixture $database;

	/**
	 * Compose fresh state for every test.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->resolved = new Resolved();
		$this->source = new Source();
		$this->report = new Report();
		$this->guid = new Guid();
		$this->pairing = new Pairing(new Decision(), $this->guid, $this->report);
		$this->config = new Config();
		$this->database = new ExtrusionDatabaseFixture();

		$this->source->set('code_name', 'demo');
	}

	/**
	 * Ten views stating the same field state one field, linked ten times.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testViewsStatingTheSameFieldShareOneRecord(): void
	{
		$views = ['club', 'event', 'member'];

		foreach ($views as $view)
		{
			$this->seed($view, 'name', [
				'label' => 'Name',
				'xml_type' => 'text',
				'datatype' => 'VARCHAR',
				'size' => '255',
				'null' => 'NOT NULL',
				'required' => 'true'
			]);
		}

		$this->assertSame(2, $this->sharing()->settle());

		$this->assertNull(
			$this->resolved->get('view.club.field.name.share'),
			'The first view in table order owns the record.'
		);

		foreach (['event', 'member'] as $view)
		{
			$share = $this->resolved->get('view.' . $view . '.field.name.share');

			$this->assertIsArray($share);
			$this->assertSame('club.name', $share['owner']);
			$this->assertSame('xml', $share['by']);
			$this->assertSame(
				$this->guid->derive(['demo', 'field', 'club', 'name']),
				$share['guid'],
				'Every later view links the guid the owner\'s record is written under.'
			);
		}

		$this->assertSame(2, $this->report->get('counts.fields_shared'));
	}

	/**
	 * A single stated property splitting is the rule stated by the author.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testRequiredTrueAndRequiredFalseAreTwoDifferentFields(): void
	{
		$this->seed('club', 'name', [
			'label' => 'Name', 'xml_type' => 'text', 'datatype' => 'VARCHAR',
			'size' => '255', 'required' => 'true'
		]);
		$this->seed('event', 'name', [
			'label' => 'Name', 'xml_type' => 'text', 'datatype' => 'VARCHAR',
			'size' => '255', 'required' => 'false'
		]);

		$this->assertSame(
			0,
			$this->sharing()->settle(),
			'Required true and required false are two different fields.'
		);
		$this->assertNull($this->resolved->get('view.event.field.name.share'));
	}

	/**
	 * The label and the database shape are statements like any other.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testALabelOrShapeDifferenceSplitsTheField(): void
	{
		$this->seed('club', 'name', [
			'label' => 'Name', 'xml_type' => 'text', 'datatype' => 'VARCHAR', 'size' => '255'
		]);
		$this->seed('event', 'name', [
			'label' => 'Title', 'xml_type' => 'text', 'datatype' => 'VARCHAR', 'size' => '255'
		]);
		$this->seed('member', 'name', [
			'label' => 'Name', 'xml_type' => 'text', 'datatype' => 'VARCHAR', 'size' => '64'
		]);

		$this->assertSame(0, $this->sharing()->settle());
	}

	/**
	 * A stated Global Unique ID is the same field always.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAStatedGuidOutranksEveryOtherStatement(): void
	{
		$stated = 'aaaaaaaa-1111-4111-8111-111111111111';

		$this->seed('club', 'name', [
			'guid' => $stated, 'label' => 'Name', 'xml_type' => 'text',
			'datatype' => 'VARCHAR', 'size' => '255'
		]);
		$this->seed('event', 'name', [
			'guid' => $stated, 'label' => 'Full Name', 'xml_type' => 'text',
			'datatype' => 'VARCHAR', 'size' => '64'
		]);

		$this->assertSame(1, $this->sharing()->settle());

		$share = $this->resolved->get('view.event.field.name.share');

		$this->assertSame($stated, $share['guid'] ?? null);
		$this->assertSame(
			'guid',
			$share['by'] ?? null,
			'The guid will never be different for the same field, so it outranks '
			. 'a statement that differs.'
		);
	}

	/**
	 * A column stating no guid joins the guid group its statement matches.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAMatchingStatementJoinsTheGuidThatSpeaksForIt(): void
	{
		$stated = 'aaaaaaaa-1111-4111-8111-111111111111';
		$shape = [
			'label' => 'Name', 'xml_type' => 'text',
			'datatype' => 'VARCHAR', 'size' => '255'
		];

		$this->seed('club', 'name', ['guid' => $stated] + $shape);
		$this->seed('event', 'name', $shape);

		$this->assertSame(1, $this->sharing()->settle());
		$this->assertSame(
			$stated,
			$this->resolved->get('view.event.field.name.share.guid'),
			'A matching statement is the rule\'s own proof that the guid speaks '
			. 'for both.'
		);
	}

	/**
	 * A person's verdict detaches exactly one view, and nothing else.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAVerdictDetachesOnlyTheColumnItNames(): void
	{
		foreach (['club', 'event', 'member'] as $view)
		{
			$this->seed($view, 'name', [
				'label' => 'Name', 'xml_type' => 'text',
				'datatype' => 'VARCHAR', 'size' => '255'
			]);
		}

		$this->pairing->load([
			'field' => [
				'event.name' => [
					'action' => 'update',
					'target' => 'bbbbbbbb-2222-4222-8222-222222222222'
				]
			]
		]);

		$this->assertSame(1, $this->sharing()->settle());

		$this->assertNull(
			$this->resolved->get('view.event.field.name.share'),
			'The decided column belongs to the field the person named.'
		);
		$this->assertSame(
			'club.name',
			$this->resolved->get('view.member.field.name.share.owner'),
			'The rest still share the owner\'s record.'
		);
	}

	/**
	 * Per-view constants resolving to one English are one field.
	 *
	 * This is the sermon distributor case: category and sermon each state a
	 * guid field whose label, description and hint constants carry the view's
	 * own name -- and every one of them resolves to the same English. After
	 * the reversal the statements are identical, so the fields are identical.
	 * The essence never sees the constants, only what they resolved to.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testConstantsResolvingToOneEnglishAreOneField(): void
	{
		// the language reversal happens before the properties reach the
		// resolved registry, so both views arrive stating the same English
		foreach (['category', 'sermon'] as $view)
		{
			$this->seed($view, 'guid', [
				'label' => 'Guid',
				'description' => 'Globally Unique Identifier',
				'hint' => 'Auto Generated',
				'xml_type' => 'text',
				'datatype' => 'VARCHAR',
				'size' => '36',
				'readonly' => 'true',
				'filter' => 'CMD',
				'validate' => 'guid'
			]);
		}

		$this->assertSame(1, $this->sharing()->settle());
		$this->assertSame(
			'category.guid',
			$this->resolved->get('view.sermon.field.guid.share.owner'),
			'What differed was only the constants, and the constants only name '
			. 'the language -- the language itself matched.'
		);
	}

	/**
	 * A verdict on the group points every view at the chosen field.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAGroupVerdictPointsEveryViewAtTheChosenField(): void
	{
		$chosen = 'cccccccc-3333-4333-8333-333333333333';

		foreach (['club', 'event', 'member'] as $view)
		{
			$this->seed($view, 'name', [
				'label' => 'Name', 'xml_type' => 'text',
				'datatype' => 'VARCHAR', 'size' => '255'
			]);
		}

		$this->pairing->load([
			'field_group' => [
				'club.name' => ['action' => 'update', 'target' => $chosen]
			]
		]);

		$this->assertSame(2, $this->sharing()->settle());
		$this->assertSame(
			$chosen,
			$this->resolved->get('view.event.field.name.share.guid'),
			'Every member links the field the person chose for the group.'
		);
		$this->assertSame(
			'choice',
			$this->resolved->get('view.member.field.name.share.by')
		);
		$this->assertSame(
			$chosen,
			$this->pairing->guid(
				'field',
				'club.name',
				$this->guid->derive(['demo', 'field', 'club', 'name'])
			),
			'The owner is steered onto the very identity the members link.'
		);
	}

	/**
	 * A verdict setting the group aside leaves no member to write.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAGroupVerdictSetsTheWholeGroupAside(): void
	{
		foreach (['club', 'event'] as $view)
		{
			$this->seed($view, 'name', [
				'label' => 'Name', 'xml_type' => 'text',
				'datatype' => 'VARCHAR', 'size' => '255'
			]);
		}

		$this->pairing->load([
			'field_group' => ['club.name' => ['action' => 'ignore']]
		]);

		$this->assertSame(0, $this->sharing()->settle());
		$this->assertNull($this->resolved->get('view.event.field.name.share'));

		foreach (['club', 'event'] as $view)
		{
			$this->assertNull(
				$this->pairing->guid(
					'field',
					$view . '.name',
					$this->guid->derive(['demo', 'field', $view, 'name'])
				),
				'No member of a group the person set aside may write.'
			);
		}
	}

	/**
	 * A create verdict on the group yields one fresh field, shared by all.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAGroupVerdictCreatesOneFreshSharedField(): void
	{
		foreach (['club', 'event'] as $view)
		{
			$this->seed($view, 'name', [
				'label' => 'Name', 'xml_type' => 'text',
				'datatype' => 'VARCHAR', 'size' => '255'
			]);
		}

		$this->pairing->load([
			'field_group' => ['club.name' => ['action' => 'create']]
		]);

		$this->assertSame(1, $this->sharing()->settle());

		$derived = $this->guid->derive(['demo', 'field', 'club', 'name']);
		$fresh = $this->guid->derive(['field', 'forced-new', $derived]);

		$this->assertSame(
			$fresh,
			$this->resolved->get('view.event.field.name.share.guid'),
			'The members link the very identity the owner\'s create verdict salts.'
		);
		$this->assertSame(
			$fresh,
			$this->pairing->guid('field', 'club.name', $derived)
		);
	}

	/**
	 * A record standing under a member's own identity is reused, never written beside.
	 *
	 * This is the update run: an earlier extrusion left the event view its own
	 * per-view name field, and the paired component still links it. The group
	 * settles onto that standing record -- the owner updates it and every view
	 * links it -- because a record this engine wrote for this very column IS
	 * this field, already written.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAStandingRecordIsAdoptedNotWrittenBeside(): void
	{
		$standing = $this->guid->derive(['demo', 'field', 'event', 'name']);

		$this->aimAt(['club', 'event'], ['event' => $standing]);

		foreach (['club', 'event'] as $view)
		{
			$this->seed($view, 'name', [
				'label' => 'Name', 'xml_type' => 'text',
				'datatype' => 'VARCHAR', 'size' => '255'
			]);
		}

		$this->assertSame(1, $this->sharing()->settle());
		$this->assertSame(
			$standing,
			$this->resolved->get('view.event.field.name.share.guid'),
			'The standing record is the field, so it is the identity all share.'
		);
		$this->assertSame(
			'standing',
			$this->resolved->get('view.event.field.name.share.by')
		);
		$this->assertSame(
			$standing,
			$this->pairing->guid(
				'field',
				'club.name',
				$this->guid->derive(['demo', 'field', 'club', 'name'])
			),
			'The owner updates the standing record instead of writing a twin.'
		);
		$this->assertSame(
			$standing,
			$this->report->get('adopted.field.club.name')
		);
	}

	/**
	 * A second standing duplicate consolidates onto the settled field.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAStandingDuplicateIsConsolidatedOntoTheSettledField(): void
	{
		$clubStanding = $this->guid->derive(['demo', 'field', 'club', 'name']);
		$eventStanding = $this->guid->derive(['demo', 'field', 'event', 'name']);

		$this->aimAt(
			['club', 'event'],
			['club' => $clubStanding, 'event' => $eventStanding]
		);

		foreach (['club', 'event'] as $view)
		{
			$this->seed($view, 'name', [
				'label' => 'Name', 'xml_type' => 'text',
				'datatype' => 'VARCHAR', 'size' => '255'
			]);
		}

		$this->assertSame(1, $this->sharing()->settle());
		$this->assertSame(
			$clubStanding,
			$this->resolved->get('view.event.field.name.share.guid'),
			'The first recognition in table order is the record all reuse.'
		);
		$this->assertSame(
			$clubStanding,
			$this->resolved->get('view.event.superseded.' . $eventStanding),
			'The event view\'s old duplicate link is turned onto the one field.'
		);
		$this->assertSame(1, $this->report->get('counts.fields_consolidated'));
	}

	/**
	 * A lookalike whose stored properties hash differently is kept, not claimed.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testALookalikeWhoseHashDiffersIsKeptNotConsolidated(): void
	{
		$lookalike = 'dddddddd-4444-4444-8444-444444444444';

		// the standing row states another shape entirely, so its hash cannot
		// align -- it is somebody's own field that merely shares the name
		$this->aimAt(['club', 'event'], ['event' => $lookalike], [
			$lookalike => [
				'guid' => $lookalike,
				'name' => 'Name',
				'fieldtype' => 'eeeeeeee-5555-4555-8555-555555555555',
				'datatype' => 'TEXT',
				'datalenght' => '',
				'datalenght_other' => '',
				'datadefault' => '',
				'datadefault_other' => '',
				'indexes' => 0,
				'null_switch' => 'NULL',
				'store' => 0,
				'xml' => json_encode('<field name="name" label="Name" />')
			]
		]);

		foreach (['club', 'event'] as $view)
		{
			$this->seed($view, 'name', [
				'label' => 'Name', 'xml_type' => 'text',
				'datatype' => 'VARCHAR', 'size' => '255'
			]);
		}

		$this->assertSame(1, $this->sharing()->settle());
		$this->assertSame(
			$this->guid->derive(['demo', 'field', 'club', 'name']),
			$this->resolved->get('view.event.field.name.share.guid'),
			'The group keeps its own identity rather than claiming a lookalike.'
		);
		$this->assertNull(
			$this->resolved->get('view.event.superseded.' . $lookalike),
			'A link to a field that is not this field is never turned.'
		);
		$this->assertSame(
			$lookalike,
			$this->report->get('kept.similar.field.event.name'),
			'The resemblance is named for a person to decide.'
		);
	}

	/**
	 * A lookalike whose stored properties hash the same is this very field.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testALookalikeWithTheSameHashIsAdopted(): void
	{
		$properties = [
			'label' => 'Name', 'xml_type' => 'text',
			'datatype' => 'VARCHAR', 'size' => '255'
		];
		$lookalike = 'ffffffff-6666-4666-8666-666666666666';
		$columns = $this->record()->compose(
			'name',
			$this->properties('name', $properties)
		)['columns'];

		$this->assertIsArray($columns);

		$row = ['guid' => $lookalike, 'name' => 'Name'] + $columns;
		$row['xml'] = json_encode($row['xml']);

		$this->aimAt(['club', 'event'], ['event' => $lookalike], [$lookalike => $row]);

		foreach (['club', 'event'] as $view)
		{
			$this->seed($view, 'name', $properties);
		}

		$this->assertSame(1, $this->sharing()->settle());
		$this->assertSame(
			$lookalike,
			$this->resolved->get('view.event.field.name.share.guid'),
			'Aligned hashes are the proof: the standing record is this field, '
			. 'so it is reused.'
		);
		$this->assertSame(
			'standing',
			$this->resolved->get('view.event.field.name.share.by')
		);
	}

	/**
	 * A statement joins whichever guid-stating member it matches.
	 *
	 * A guid group may hold members whose statements differ -- the guid
	 * outranks the difference -- and a column stating no guid belongs with
	 * the group when its statement matches ANY of them, not only the first.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAStatementJoinsWhicheverGuidMemberItMatches(): void
	{
		$stated = 'aaaaaaaa-1111-4111-8111-111111111111';

		$this->seed('club', 'name', [
			'guid' => $stated, 'label' => 'Name', 'xml_type' => 'text',
			'datatype' => 'VARCHAR', 'size' => '255'
		]);
		$this->seed('event', 'name', [
			'guid' => $stated, 'label' => 'Full Name', 'xml_type' => 'text',
			'datatype' => 'VARCHAR', 'size' => '64'
		]);
		// member states no guid, and its statement matches EVENT's, not club's
		$this->seed('member', 'name', [
			'label' => 'Full Name', 'xml_type' => 'text',
			'datatype' => 'VARCHAR', 'size' => '64'
		]);

		$this->assertSame(2, $this->sharing()->settle());
		$this->assertSame(
			$stated,
			$this->resolved->get('view.member.field.name.share.guid'),
			'A matching statement joins the guid whichever member carries it.'
		);
	}

	/**
	 * A group verdict is honored even when other verdicts shrink the group.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAGroupVerdictSurvivesTheGroupShrinking(): void
	{
		foreach (['club', 'event'] as $view)
		{
			$this->seed($view, 'name', [
				'label' => 'Name', 'xml_type' => 'text',
				'datatype' => 'VARCHAR', 'size' => '255'
			]);
		}

		// the person detached the event view with its own verdict AND set
		// the group itself aside -- both must hold
		$this->pairing->load([
			'field' => ['event.name' => ['action' => 'create']],
			'field_group' => ['club.name' => ['action' => 'ignore']]
		]);

		$this->assertSame(0, $this->sharing()->settle());
		$this->assertNull(
			$this->pairing->guid(
				'field',
				'club.name',
				$this->guid->derive(['demo', 'field', 'club', 'name'])
			),
			'The group the person set aside writes nothing, however small it got.'
		);
		$this->assertNotNull(
			$this->pairing->guid(
				'field',
				'event.name',
				$this->guid->derive(['demo', 'field', 'event', 'name'])
			),
			'The detached view keeps its own verdict.'
		);
	}

	/**
	 * A stated guid that is not a guid states nothing.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAMalformedStatedGuidIsAStatementNotAnIdentity(): void
	{
		foreach (['club', 'event'] as $view)
		{
			$this->seed($view, 'name', [
				'guid' => 'not-a-guid-at-all',
				'label' => 'Name', 'xml_type' => 'text',
				'datatype' => 'VARCHAR', 'size' => '255'
			]);
		}

		$this->assertSame(1, $this->sharing()->settle());

		$share = $this->resolved->get('view.event.field.name.share');

		$this->assertSame(
			$this->guid->derive(['demo', 'field', 'club', 'name']),
			$share['guid'] ?? null,
			'The members share a real identity a writer can write, never the '
			. 'malformed string.'
		);
		$this->assertSame('xml', $share['by'] ?? null);
	}

	/**
	 * Seed one resolved column the way the assembler records it.
	 *
	 * @param   string                $view    The view name.
	 * @param   string                $column  The column name.
	 * @param   array<string, mixed>  $values  Property values, origin xml.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	private function seed(string $view, string $column, array $values): void
	{
		$views = (array) $this->resolved->get('views', []);

		if (!in_array($view, $views, true))
		{
			$views[] = $view;
			$this->resolved->set('views', $views);
		}

		$this->resolved->set(
			'view.' . $view . '.field.' . $column,
			$this->properties($column, $values)
		);
	}

	/**
	 * The resolved property shape one seeded column carries.
	 *
	 * @param   string                $column  The column name.
	 * @param   array<string, mixed>  $values  Property values, origin xml.
	 *
	 * @return  array<string, array{value: mixed, origin: string}>  The properties.
	 * @since   6.1.9
	 */
	private function properties(string $column, array $values): array
	{
		$properties = ['name' => ['value' => $column, 'origin' => 'derived']];

		foreach ($values as $property => $value)
		{
			$properties[$property] = ['value' => $value, 'origin' => 'xml'];
		}

		return $properties;
	}

	/**
	 * Stand a paired component in the database, with per-view name fields.
	 *
	 * Each named view gets a standing admin view the component links, and each
	 * entry in the fields map stands a field record that view links by name.
	 * A full row can be supplied for a field; without one, the row carries no
	 * stored properties and only its identity can recognise it.
	 *
	 * @param   array<string>          $views   The view names the component links.
	 * @param   array<string, string>  $fields  View name to standing field guid.
	 * @param   array<string, array>   $rows    Field guid to its full standing row.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	private function aimAt(array $views, array $fields, array $rows = []): void
	{
		$this->config->set('component', 7);

		$componentGuid = 'abcdefab-7777-4777-8777-777777777777';
		$viewRows = [];
		$links = [];
		$fieldRows = [];
		$adminFields = [];

		foreach ($views as $view)
		{
			$viewGuid = $this->guid->derive(['demo', 'admin_view', $view]);
			$viewRows[] = [
				'guid' => $viewGuid,
				'name_single' => $view,
				'name_list' => $view . 's',
				'system_name' => 'Demo ' . $view
			];
			$links[] = ['adminview' => $viewGuid];

			if (isset($fields[$view]))
			{
				$adminFields[] = [
					'admin_view' => $viewGuid,
					'addfields' => json_encode([['field' => $fields[$view]]])
				];
			}
		}

		foreach ($fields as $guid)
		{
			$fieldRows[$guid] = $rows[$guid] ?? ['guid' => $guid, 'name' => 'Name'];
		}

		$this->database
			->table('joomla_component', [
				['id' => 7, 'guid' => $componentGuid, 'system_name' => 'Demo', 'name_code' => 'demo']
			])
			->table('component_admin_views', [
				[
					'joomla_component' => $componentGuid,
					'addadmin_views' => json_encode($links)
				]
			])
			->table('component_site_views', [])
			->table('component_custom_admin_views', [])
			->table('admin_view', $viewRows)
			->table('admin_fields', $adminFields)
			->table('field', array_values($fieldRows))
			->table('power', []);
	}

	/**
	 * The record resolver over the shared catalogue fixture.
	 *
	 * @return  Record  The record resolver.
	 * @since   6.1.9
	 */
	private function record(): Record
	{
		$fieldtype = new Fieldtype(
			new ExtrusionCatalogueFixture(),
			$this->source,
			$this->report
		);

		return new Record(
			$fieldtype,
			new FieldXml($fieldtype, $this->report),
			new Table()
		);
	}

	/**
	 * The resolver under test, over the current registries.
	 *
	 * @return  Sharing  The sharing resolver.
	 * @since   6.1.9
	 */
	private function sharing(): Sharing
	{
		$fieldtype = new Fieldtype(
			new ExtrusionCatalogueFixture(),
			$this->source,
			$this->report
		);
		$fieldxml = new FieldXml($fieldtype, $this->report);

		return new Sharing(
			$this->resolved,
			$this->source,
			$this->pairing,
			$this->guid,
			$fieldxml,
			new Standing(
				$this->config,
				$this->resolved,
				$this->source,
				new Candidates(
					$this->config,
					$this->resolved,
					$this->source,
					new ViewRegistry(),
					$this->database,
					$this->guid,
					$this->report
				),
				new Record($fieldtype, $fieldxml, new Table()),
				$this->guid
			),
			$this->report
		);
	}
}
