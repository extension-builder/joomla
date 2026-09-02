<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    2nd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Dynamic;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\AllowView;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\Expectations;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\GetModel;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\Meta;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\PrepareItem;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\Resource;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Resources;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\JoinStructure;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HeaderInterface;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The API resource placeholders of a site view or custom admin view.
 *
 * @since 6.1.7
 */
#[CoversClass(Resource::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Abstraction')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ResourceTest extends ArchitectureTestCase
{
	/**
	 * The content registry the placeholders land in.
	 *
	 * @var    ContentMulti
	 * @since  6.1.7
	 */
	private ContentMulti $multi;

	/**
	 * An item site view whose code collides gets its placeholders under the prefixed name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnItemSiteViewGetsItsPlaceholdersUnderItsApiName(): void
	{
		$subject = $this->subject();
		$subject->set($this->site, 'site');

		$this->assertSame('Site_truck', $this->multi->get('site_truck|ApiName'));
		$this->assertSame('site_truck', $this->multi->get('site_truck|apiname'));
		$this->assertSame('Truck', $this->multi->get('site_truck|SView'));
		$this->assertSame('truck', $this->multi->get('site_truck|sview'));
		$this->assertSame('header:api.dynamic.view.controller:Site_truck', $this->multi->get('site_truck|API_DYNAMIC_VIEW_CONTROLLER_HEADER'));
		$this->assertSame('header:api.dynamic.view.json:Site_truck', $this->multi->get('site_truck|API_DYNAMIC_VIEW_JSON_HEADER'));
		$this->assertStringContainsString("parent::getModel('Truck', 'Site'", $this->multi->get('site_truck|API_DYNAMIC_VIEW_CONTROLLER_GETMODEL'));
		$this->assertStringContainsString("'site.truck.access'", $this->multi->get('site_truck|API_DYNAMIC_VIEW_CONTROLLER_ALLOWVIEW'));
		$this->assertStringContainsString('the :id route segment', $this->multi->get('site_truck|API_DYNAMIC_VIEW_CONTROLLER_EXPECTATIONS'));
		$this->assertStringContainsString("getState('truck.id')", $this->multi->get('site_truck|API_DYNAMIC_VIEW_JSON_PREPAREITEM'));
		$this->assertFalse($this->multi->exists('site_truck|API_DYNAMIC_VIEWS_JSON_META'));
		$this->assertFalse($this->multi->exists('truck|ApiName'));
	}

	/**
	 * A list custom admin view gets the list placeholders and its meta.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAListCustomAdminViewGetsTheListPlaceholdersAndItsMeta(): void
	{
		$subject = $this->subject();
		$subject->set($this->report, 'custom_admin');

		$this->assertSame('Report', $this->multi->get('report|ApiName'));
		$this->assertSame('header:api.dynamic.views.controller:Report', $this->multi->get('report|API_DYNAMIC_VIEWS_CONTROLLER_HEADER'));
		$this->assertSame('header:api.dynamic.views.json:Report', $this->multi->get('report|API_DYNAMIC_VIEWS_JSON_HEADER'));
		$this->assertStringContainsString("parent::getModel('Report', 'Administrator'", $this->multi->get('report|API_DYNAMIC_VIEWS_CONTROLLER_GETMODEL'));
		$this->assertStringContainsString("'core.manage'", $this->multi->get('report|API_DYNAMIC_VIEWS_CONTROLLER_ALLOWVIEW'));
		$this->assertStringContainsString('Every record is returned', $this->multi->get('report|API_DYNAMIC_VIEWS_CONTROLLER_EXPECTATIONS'));
		$this->assertStringContainsString('++$this->position', $this->multi->get('report|API_DYNAMIC_VIEWS_JSON_PREPAREITEM'));
		$this->assertStringContainsString("addMeta('owner'", $this->multi->get('report|API_DYNAMIC_VIEWS_JSON_META'));
		$this->assertFalse($this->multi->exists('report|API_DYNAMIC_VIEW_CONTROLLER_HEADER'));
	}

	/**
	 * A view without a resource, or without a code, sets nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutAResourceSetsNothing(): void
	{
		$subject = $this->subject();

		$subject->set(['settings' => (object) ['code' => 'unknown']], 'site');
		$subject->set(['settings' => (object) []], 'site');
		$subject->set($this->report, 'site');

		$this->assertSame([], $this->multi->allActive());
	}

	/**
	 * The resources are mapped from the component when nothing mapped them yet.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheResourcesAreMappedFromTheComponentWhenNeeded(): void
	{
		$resources = new Resources($this->config());
		$subject = $this->subject($resources);

		$this->assertFalse($resources->mapped());

		$subject->set($this->site, 'site');

		$this->assertTrue($resources->mapped());
		$this->assertSame('site_truck', $resources->name('site', 'truck'));
		$this->assertSame('report', $resources->name('custom_admin', 'report'));
	}

	/**
	 * The item site view link, colliding with the admin view of the same code.
	 *
	 * @var    array
	 * @since  6.1.7
	 */
	private array $site;

	/**
	 * The list custom admin view link.
	 *
	 * @var    array
	 * @since  6.1.7
	 */
	private array $report;

	/**
	 * The subject over a component with an admin API, a custom admin view and a site view.
	 *
	 * @param   Resources|null  $resources  The resources map, a fresh one when null.
	 *
	 * @return  Resource
	 * @since   6.1.7
	 */
	private function subject(?Resources $resources = null): Resource
	{
		$this->multi = new ContentMulti();

		$this->site = ['access' => 1, 'public_access' => 1, 'settings' => (object) [
			'code' => 'truck', 'Code' => 'Truck',
			'main_get' => (object) ['gettype' => 1, 'main_source' => 1, 'custom_get' => [],
				'filter' => [['table_key' => 'a.id', 'operator' => '=', 'filter_type' => 1]]],
			'custom_get' => [],
		]];
		$this->report = ['access' => 1, 'settings' => (object) [
			'code' => 'report', 'Code' => 'Report',
			'main_get' => (object) ['gettype' => 2, 'main_source' => 1, 'pagination' => 0, 'custom_get' => []],
			'custom_get' => [(object) ['gettype' => 3, 'getcustom' => 'getOwner']],
		]];

		$component = new Component(
			(new ReflectionClass(Data::class))->newInstanceWithoutConstructor(),
			$this->createStub(EventInterface::class)
		);
		$component->set('admin_views', [['add_api' => 2, 'settings' => (object) [
			'name_single' => 'Truck', 'name_list' => 'Trucks', 'name_single_code' => 'truck', 'name_list_code' => 'trucks',
		]]]);
		$component->set('custom_admin_views', [$this->report]);
		$component->set('site_views', [$this->site]);

		$header = $this->createStub(HeaderInterface::class);
		$header->method('get')->willReturnCallback(
			static fn (string $context, string $name): string => 'header:' . $context . ':' . $name
		);

		return new Resource(
			$resources ?? new Resources($this->config()),
			$component,
			$header,
			$this->multi,
			new GetModel(),
			new AllowView($this->config()),
			new Expectations(),
			new PrepareItem(new JoinStructure()),
			new Meta()
		);
	}
}
