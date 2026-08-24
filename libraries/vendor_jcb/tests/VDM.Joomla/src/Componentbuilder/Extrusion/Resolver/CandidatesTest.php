<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    23rd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Resolver;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Decision;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Candidates;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Pairing;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Reuse;
use VDM\Tests\Support\ExtrusionDatabaseFixture;
use VDM\Tests\Support\TestCase;


/**
 * The resolver that turns one harvest into the approval candidate list.
 *
 * The pairing step lives or dies on two properties held here: every candidate
 * key must be exactly the key the writers will file its verdict under, and the
 * proposed pairings must come from what the target component actually links --
 * so a re-import of a known component arrives pre-paired as updates.
 *
 * @since  6.1.7
 */
#[CoversClass(Candidates::class)]
#[CoversClass(Reuse::class)]
#[UsesClass(Config::class)]
#[UsesClass(Guid::class)]
#[UsesClass(Report::class)]
final class CandidatesTest extends TestCase
{
	/**
	 * The existing admin view identity in the catalogue.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const VIEW = 'aaaaaaaa-1111-4111-8111-111111111111';

	/**
	 * The existing field identity in the catalogue.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const FIELD = 'bbbbbbbb-2222-4222-8222-222222222222';

	/**
	 * The existing site view identity in the catalogue.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const SITE = 'eeeeeeee-5555-4555-8555-555555555555';

	/**
	 * The served database boundary.
	 *
	 * @var    ExtrusionDatabaseFixture
	 * @since  6.1.7
	 */
	private ExtrusionDatabaseFixture $load;

	/**
	 * The resolved definition registry.
	 *
	 * @var    Resolved
	 * @since  6.1.7
	 */
	private Resolved $resolved;

	/**
	 * The source identity registry.
	 *
	 * @var    Source
	 * @since  6.1.7
	 */
	private Source $source;

	/**
	 * The classified view registry.
	 *
	 * @var    View
	 * @since  6.1.7
	 */
	private View $view;

	/**
	 * The resolver under test.
	 *
	 * @var    Candidates
	 * @since  6.1.7
	 */
	private Candidates $candidates;

	/**
	 * Compose the resolver over a served catalogue and one resolved view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->load = new ExtrusionDatabaseFixture();
		$this->load
			->table('joomla_component', [
				['id' => 3, 'guid' => 'comp-guid', 'system_name' => 'Demo', 'name_code' => 'demo', 'published' => 1, 'modified' => '2026-01-01']
			])
			->table('component_admin_views', [
				['joomla_component' => 'comp-guid',
					'addadmin_views' => json_encode([
						'addadmin_views0' => ['adminview' => self::VIEW],
						'addadmin_views1' => ['adminview' => 'dddddddd-4444-4444-8444-dddddddddddd']
					])]
			])
			->table('component_site_views', [
				['joomla_component' => 'comp-guid',
					'addsite_views' => json_encode(['addsite_views0' => ['siteview' => self::SITE]])]
			])
			->table('admin_view', [
				['guid' => self::VIEW, 'name_single' => 'Item', 'name_list' => 'Items',
					'system_name' => 'Demo Item'],
				['guid' => 'dddddddd-4444-4444-8444-dddddddddddd', 'name_single' => 'Venue',
					'name_list' => 'Venues', 'system_name' => 'Demo Venue']
			])
			->table('admin_fields', [
				['admin_view' => self::VIEW,
					'addfields' => json_encode(['addfields0' => ['field' => self::FIELD]])]
			])
			->table('field', [
				['guid' => self::FIELD, 'name' => 'Title']
			])
			->table('site_view', [
				['guid' => self::SITE, 'name' => 'Itemcard Page', 'system_name' => 'Demo Itemcard Page']
			])
			->table('layout', [
				['guid' => 'cccccccc-3333-4333-8333-333333333333', 'name' => 'itemcard']
			])
			->table('template', [])
			->table('custom_admin_view', [])
			->table('component_custom_admin_views', [])
			->table('power', []);

		$this->resolved = new Resolved();
		$this->resolved->set('views', ['item']);
		$this->resolved->set('view.item.name_single', 'Item');
		$this->resolved->set('view.item.system_name', 'Demo Item');
		$this->resolved->set('view.item.field.title', [
			'name' => ['value' => 'title', 'origin' => 'derived'],
			'label' => ['value' => 'Title', 'origin' => 'xml']
		]);
		$this->resolved->set('view.item.field.legacy_flag', [
			'name' => ['value' => 'legacy_flag', 'origin' => 'derived'],
			'label' => ['value' => 'Legacy Flag', 'origin' => 'derived']
		]);

		$this->source = new Source();
		$this->source->set('code_name', 'com_demo');

		$this->view = new View();
		$this->view->set('layout', ['itemcard' => ['name' => 'itemcard']]);
		$this->view->set('site_view', ['itemcard_page' => ['name' => 'Itemcard Page']]);

		$this->candidates = new Candidates(
			new Config(),
			$this->resolved,
			$this->source,
			$this->view,
			$this->load,
			new Guid(),
			new Report()
		);
	}

	/**
	 * A known component pre-pairs its views and fields as updates.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAKnownComponentArrivesPrePaired(): void
	{
		$candidates = $this->candidates->candidates(3);
		$view = $candidates['admin_view'][0];

		$this->assertSame('item', $view['key']);
		$this->assertSame('Item', $view['label']);
		$this->assertSame(self::VIEW, $view['match']['guid'] ?? null);

		$fields = array_column($view['fields'], null, 'key');

		$this->assertSame(
			self::FIELD,
			$fields['item.title']['match']['guid'] ?? null,
			'A field pairs by name against the fields its paired view already links.'
		);
		$this->assertSame(
			'scoped',
			$fields['item.title']['match']['by'] ?? null,
			'A field the paired view already links is its own wiring rediscovered, '
			. 'and weighs like an identity.'
		);
		$this->assertNull(
			$fields['item.legacy_flag']['match'],
			'A field the component does not know proposes itself as a creation.'
		);

		$this->assertSame(
			'cccccccc-3333-4333-8333-333333333333',
			$candidates['layout'][0]['match']['guid'] ?? null
		);
		$this->assertSame(
			self::SITE,
			$candidates['site_view'][0]['match']['guid'] ?? null,
			'A site view pairs by name against the site views the component links.'
		);
	}

	/**
	 * Candidate keys are exactly the keys verdicts are filed under.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCandidateKeysMatchTheVerdictKeys(): void
	{
		$candidates = $this->candidates->candidates(3);

		$this->assertSame('item', $candidates['admin_view'][0]['key']);
		$this->assertSame(
			['item.title', 'item.legacy_flag'],
			array_column($candidates['admin_view'][0]['fields'], 'key')
		);
		$this->assertSame('itemcard', $candidates['layout'][0]['key']);
	}

	/**
	 * Without a component the views propose creation, but fields still match.
	 *
	 * A component-scoped pool needs a component: no views pair, no site views
	 * pair. Fields are different -- JCB shares them across every component,
	 * so a field that already stands anywhere in the system is a match even
	 * when no target component was chosen. That is what keeps a second name
	 * field from ever being created.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWithoutAComponentViewsProposeCreationButFieldsStillMatch(): void
	{
		$candidates = $this->candidates->candidates(0);

		$this->assertNull($candidates['admin_view'][0]['match']);
		$this->assertSame(
			'bbbbbbbb-2222-4222-8222-222222222222',
			$candidates['admin_view'][0]['fields'][0]['match']['guid'] ?? null,
			'A field that already stands in JCB matches whatever the component target.'
		);
	}

	/**
	 * A guid or the paired view's own wiring reuses; a bare name never does.
	 *
	 * A field whose stated guid already stands in JCB IS that field, and a
	 * field the paired view already links is that view's own wiring
	 * rediscovered -- both update what stands, and the identity is recorded
	 * so the view links it. A field that merely shares a name with something
	 * elsewhere gets no default at all. An explicit verdict is never
	 * overruled either way.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testOnlyAnIdentityOrTheViewsOwnWiringDefaultsToReuse(): void
	{
		// legacy_flag states the very identity JCB already holds, and title
		// answers to a field the paired view already links -- while the
		// existing component's real view names are recorded for the writers
		$this->resolved->set('view.item.field.legacy_flag.guid', [
			'value' => self::FIELD,
			'origin' => 'table'
		]);

		$decision = new Decision();
		$report = new Report();
		$pairing = new Pairing($decision, new Guid(), $report);
		$config = new Config();
		$config->set('component', 3);

		$pairing->load(['admin_view' => ['item' => ['action' => 'create']]]);

		$reuse = new Reuse($this->candidates, $pairing, $this->resolved, $report, $config);

		$this->assertSame(2, $reuse->apply());
		$this->assertSame(
			['action' => 'create', 'target' => ''],
			$decision->get('admin_view.item'),
			'An explicit verdict outranks the reuse default.'
		);
		$this->assertSame(
			['action' => 'update', 'target' => self::FIELD],
			$decision->get('field.item_legacy_flag'),
			'A field whose stated guid already stands IS that field, and updates it.'
		);
		$this->assertSame(
			['action' => 'update', 'target' => self::FIELD],
			$decision->get('field.item_title'),
			'A field the paired view already links is its wiring, and is reused.'
		);
		$this->assertSame(
			self::FIELD,
			$this->resolved->get('view.item.linked.title.guid'),
			'The identity is recorded so the view links the standing field.'
		);
		$this->assertSame(self::FIELD, $report->get('reuse.field.item_legacy_flag'));
		$this->assertContains(
			'item',
			(array) $this->resolved->get('existing.admin_view_names', []),
			'The component\'s own view names are recorded for the writers to consult.'
		);
	}

	/**
	 * A stated identity pairs by guid, and says so.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testAStatedIdentityPairsByGuid(): void
	{
		$this->resolved->set('view.item.field.legacy_flag.guid', [
			'value' => self::FIELD,
			'origin' => 'table'
		]);

		$candidates = $this->candidates->candidates(3);
		$fields = array_column($candidates['admin_view'][0]['fields'], null, 'key');

		$this->assertSame(self::FIELD, $fields['item.legacy_flag']['match']['guid'] ?? null);
		$this->assertSame(
			'guid',
			$fields['item.legacy_flag']['match']['by'] ?? null,
			'Everything in JCB is linked by guid, so a guid in common IS the same field.'
		);
	}

	/**
	 * A table view's own template never appears as a custom admin view.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testATableViewsOwnTemplateIsNeverACustomAdminViewCandidate(): void
	{
		$this->view->set('custom_admin_view.item.name', 'item');
		$this->view->set('custom_admin_view.item.default', '<p>generated</p>');
		$this->view->set('custom_admin_view.editor.name', 'editor');
		$this->view->set('custom_admin_view.editor.default', '<p>edited</p>');
		$this->view->set('custom_admin_view.editor.crud', 1);
		$this->view->set('custom_admin_view.wizard.name', 'wizard');
		$this->view->set('custom_admin_view.wizard.default', '<p>wizard</p>');
		$this->view->set('custom_admin_view.items.name', 'items');
		$this->view->set('custom_admin_view.items.default', '<p>list</p>');
		// venues answers to a view the component links in the DATABASE alone:
		// this run never resolved it, so only the ground truth can refuse it
		$this->view->set('custom_admin_view.venues.name', 'venues');
		$this->view->set('custom_admin_view.venues.default', '<p>venues</p>');

		$candidates = $this->candidates->candidates(3);

		$this->assertSame(
			['wizard'],
			array_column($candidates['custom_admin_view'], 'label'),
			'A folder a resolved view answers for, one an editor marked as a table '
			. 'view\'s own, or one of the component\'s own admin views by its real '
			. 'list name from the database, must never be offered as a custom admin view.'
		);
	}

	/**
	 * The harvested source detects the component it appears to be.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheSourceDetectsItsOwnComponent(): void
	{
		$detected = $this->candidates->detect();

		$this->assertSame('comp-guid', $detected->guid ?? null);

		$this->source->set('code_name', 'com_unknown');

		$this->assertNull($this->candidates->detect());
	}

	/**
	 * The published components stand ready as targets.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testThePublishedComponentsStandReadyAsTargets(): void
	{
		$components = $this->candidates->components();

		$this->assertCount(1, $components);
		$this->assertSame('Demo', $components[0]->name ?? null);
	}
}
