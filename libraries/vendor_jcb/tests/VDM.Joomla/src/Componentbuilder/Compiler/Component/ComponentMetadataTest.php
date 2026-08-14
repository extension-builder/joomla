<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Component;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Dashboard;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Component\Placeholder as ComponentPlaceholder;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Field;
use VDM\Joomla\Componentbuilder\Compiler\Field\Name as FieldName;
use VDM\Joomla\Componentbuilder\Compiler\Field\UniqueName;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Model\Adminviews;
use VDM\Joomla\Componentbuilder\Compiler\Model\Customadminviews;
use VDM\Joomla\Componentbuilder\Compiler\Model\Filesfolders;
use VDM\Joomla\Componentbuilder\Compiler\Model\Historycomponent;
use VDM\Joomla\Componentbuilder\Compiler\Model\Joomlamodules;
use VDM\Joomla\Componentbuilder\Compiler\Model\Joomlaplugins;
use VDM\Joomla\Componentbuilder\Compiler\Model\Router;
use VDM\Joomla\Componentbuilder\Compiler\Model\Siteviews;
use VDM\Joomla\Componentbuilder\Compiler\Model\Sqltweaking;
use VDM\Joomla\Componentbuilder\Compiler\Model\Updateserver;
use VDM\Joomla\Componentbuilder\Compiler\Model\Whmcs;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Component query, dashboard, and global-placeholder contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Data::class)]
#[CoversClass(Dashboard::class)]
#[CoversClass(ComponentPlaceholder::class)]
#[UsesClass(Component::class)]
#[UsesClass(Registry::class)]
final class ComponentMetadataTest extends CompilerDomainTestCase
{
	/**
	 * An empty component row returns null after building the joined query and before modelling.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDataReturnsNullAtTheDatabaseBoundaryForMissingComponent(): void
	{
		$query = $this->createStub(QueryInterface::class);
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$database->method('quoteName')->willReturnCallback(
			static function (string|array $name, string|array|null $as = null): string|array
			{
				if (is_array($name))
				{
					return array_map(
						static fn(string $item, string $alias): string => $item . ' AS ' . $alias,
						$name,
						is_array($as) ? $as : $name
					);
				}

				return $as === null ? $name : $name . ' AS ' . $as;
			}
		);
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('loadObject')->willReturn(null);
		$events = [];
		$event = $this->createMock(EventInterface::class);
		$event->expects($this->once())
			->method('trigger')
			->willReturnCallback(static function (string $name) use (&$events): void
			{
				$events[] = $name;
			});
		$config = $this->compilerConfig(['component_id' => 73]);
		$subject = new Data(
			$config,
			$event,
			new Placeholder($config),
			$this->inertCompilerCollaborator(ComponentPlaceholder::class),
			$this->inertCompilerCollaborator(Dispenser::class),
			$this->inertCompilerCollaborator(Customcode::class),
			$this->inertCompilerCollaborator(Gui::class),
			$this->inertCompilerCollaborator(Field::class),
			$this->inertCompilerCollaborator(FieldName::class),
			$this->inertCompilerCollaborator(UniqueName::class),
			$this->inertCompilerCollaborator(Filesfolders::class),
			$this->inertCompilerCollaborator(Historycomponent::class),
			$this->inertCompilerCollaborator(Whmcs::class),
			$this->inertCompilerCollaborator(Sqltweaking::class),
			$this->inertCompilerCollaborator(Adminviews::class),
			$this->inertCompilerCollaborator(Siteviews::class),
			$this->inertCompilerCollaborator(Customadminviews::class),
			$this->inertCompilerCollaborator(Updateserver::class),
			$this->inertCompilerCollaborator(Joomlamodules::class),
			$this->inertCompilerCollaborator(Joomlaplugins::class),
			$this->inertCompilerCollaborator(Router::class),
			$database
		);

		$this->assertNull($subject->get());
		$this->assertSame(['jcb_ce_onBeforeQueryComponentData'], $events);
	}

	/**
	 * A matching dynamic dashboard resolves to a normalized build target and removes legacy code.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDashboardResolvesAdminViewAndClearsLegacyDashboardCode(): void
	{
		$component = $this->component();
		$component->set('dashboard_type', 2);
		$component->set('dashboard', 'A_23');
		$component->set('dashboard_tab', 'legacy tab');
		$component->set('php_dashboard_methods', 'legacy methods');
		$component->set('admin_views', [[
			'adminview' => 23,
			'settings' => (object) ['name_list' => 'Product Orders']
		]]);
		$registry = new Registry();
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->never())->method('enqueueMessage');

		(new Dashboard($registry, $component, $app))->set();

		$this->assertSame('product_orders', $registry->get('build.dashboard'));
		$this->assertNull($component->get('dashboard_tab'));
		$this->assertNull($component->get('php_dashboard_methods'));
	}

	/**
	 * Dashboard target type must coexist with the scalar dashboard value in registry state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testDashboardPersistsResolvedTargetTypeAlongsideName(): void
	{
		$component = $this->component();
		$component->set('dashboard_type', 2);
		$component->set('dashboard', 'A_23');
		$component->set('admin_views', [[
			'adminview' => 23,
			'settings' => (object) ['name_list' => 'Product Orders']
		]]);
		$registry = new Registry();

		(new Dashboard(
			$registry,
			$component,
			$this->createStub(CMSApplicationInterface::class)
		))->set();

		$this->assertSame('product_orders', $registry->get('build.dashboard'));
		$this->assertSame('admin_views', $registry->get('build.dashboard.type'));
	}

	/**
	 * Invalid dynamic dashboard syntax reports both diagnostics without mutating build state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDashboardRejectsInvalidTargetSyntax(): void
	{
		$component = $this->component();
		$component->set('dashboard_type', 2);
		$component->set('dashboard', 'invalid');
		$registry = new Registry();
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->exactly(2))
			->method('enqueueMessage')
			->with($this->isString(), 'Error');

		(new Dashboard($registry, $component, $app))->set();

		$this->assertFalse($registry->exists('build.dashboard'));
	}

	/**
	 * Stored placeholders, core values, and component overrides merge in deterministic order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPlaceholderMergesStoredCoreAndOverrideValuesAndCachesResult(): void
	{
		$query = $this->createStub(QueryInterface::class);
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->exactly(2))->method('getQuery')->with(true)->willReturn($query);
		$database->method('quoteName')->willReturnCallback(
			static fn(string|array $name, string|array|null $as = null): string|array => $name
		);
		$database->method('quote')->willReturnCallback(
			static fn(mixed $value): string => "'" . (string) $value . "'"
		);
		$database->expects($this->exactly(2))->method('setQuery')->with($query);
		$database->expects($this->exactly(2))->method('execute');
		$database->expects($this->exactly(2))
			->method('getNumRows')
			->willReturnOnConsecutiveCalls(1, 1);
		$database->expects($this->once())
			->method('loadAssocList')
			->with('target', 'value')
			->willReturn(['[[[CUSTOM]]]' => base64_encode('stored value')]);
		$database->expects($this->once())
			->method('loadResult')
			->willReturn(json_encode([
				['target' => '###CUSTOM###', 'value' => '###component### override'],
				['target' => 'ignored']
			]));
		$config = $this->compilerConfig([
			'component_code_name' => 'example',
			'lang_prefix' => 'COM_EXAMPLE',
			'namespace_prefix' => 'Acme',
			'component_autoloader_path' => 'administrator/cache/autoload.php',
			'component_guid' => '123e4567-e89b-12d3-a456-426614174000'
		]);
		$subject = new ComponentPlaceholder($config, $database);

		$first = $subject->get();
		$second = $subject->get();

		$this->assertSame($first, $second);
		$this->assertSame('example', $first[Placefix::_('component')]);
		$this->assertSame('example', $first[Placefix::_h('component')]);
		$this->assertSame('Example', $first[Placefix::_('Component')]);
		$this->assertSame('EXAMPLE', $first[Placefix::_('COMPONENT')]);
		$this->assertSame('COM_EXAMPLE', $first[Placefix::_('LANG_PREFIX')]);
		$this->assertSame('Acme', $first[Placefix::_('NamespacePrefix')]);
		$this->assertSame('administrator/cache/autoload.php', $first[Placefix::_('POWERLOADERPATH')]);
		$this->assertSame('example override', $first[Placefix::_('CUSTOM')]);
		$this->assertSame('example override', $first[Placefix::_h('CUSTOM')]);
	}

	/**
	 * Build a mutable component registry without loading database component data.
	 *
	 * @return  Component
	 * @since   6.1.6
	 */
	private function component(): Component
	{
		return new Component(
			$this->inertCompilerCollaborator(Data::class),
			$this->createStub(EventInterface::class)
		);
	}
}
