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
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Decision;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\FieldXml;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Fieldtype;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Pairing;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Sharing;
use VDM\Tests\Support\ExtrusionCatalogueFixture;
use VDM\Tests\Support\TestCase;


/**
 * One field per stated identity, linked into every view that states it.
 *
 * The rule under test is the author's own: a stated Global Unique ID is the
 * same field always; otherwise the code name, the label, the field type, the
 * database shape and every stated XML property must match exactly -- required
 * true and required false are two different fields. Nothing outside the
 * component being extruded is consulted.
 *
 * @since  6.1.9
 */
#[CoversClass(Sharing::class)]
#[UsesClass(FieldXml::class)]
#[UsesClass(Fieldtype::class)]
#[UsesClass(Guid::class)]
#[UsesClass(Pairing::class)]
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
		$properties = ['name' => ['value' => $column, 'origin' => 'derived']];

		foreach ($values as $property => $value)
		{
			$properties[$property] = ['value' => $value, 'origin' => 'xml'];
		}

		$views = (array) $this->resolved->get('views', []);

		if (!in_array($view, $views, true))
		{
			$views[] = $view;
			$this->resolved->set('views', $views);
		}

		$this->resolved->set('view.' . $view . '.field.' . $column, $properties);
	}

	/**
	 * The resolver under test, over the current registries.
	 *
	 * @return  Sharing  The sharing resolver.
	 * @since   6.1.9
	 */
	private function sharing(): Sharing
	{
		return new Sharing(
			$this->resolved,
			$this->source,
			$this->pairing,
			$this->guid,
			new FieldXml(
				new Fieldtype(
					new ExtrusionCatalogueFixture(),
					$this->source,
					$this->report
				),
				$this->report
			),
			$this->report
		);
	}
}
