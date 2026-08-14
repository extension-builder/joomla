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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler;


use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Component;
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
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Component registry loading and failure-boundary contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Component::class)]
#[UsesClass(Data::class)]
final class ComponentTest extends CompilerDomainTestCase
{
	/**
	 * Preserve falsy registry values while returning null for missing paths.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMagicReadDistinguishesFalsyValuesFromMissingPaths(): void
	{
		$subject = new Component(
			$this->inertCompilerCollaborator(Data::class),
			$this->createStub(EventInterface::class)
		);
		$subject->set('flags.enabled', false);
		$subject->set('flags.count', 0);

		$this->assertFalse($subject->flags->enabled);
		$this->assertSame(0, $subject->flags->count);
		$this->assertNull($subject->missing);
	}

	/**
	 * A missing component fails after the before event and never emits the after event.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBuildFailsAtTheDataBoundaryWithoutMarkingTheRegistryInitialized(): void
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
		$event = $this->createStub(EventInterface::class);
		$event->method('trigger')->willReturnCallback(
			static function (string $name) use (&$events): void
			{
				$events[] = $name;
			}
		);
		$config = $this->compilerConfig(['component_id' => 73]);
		$data = new Data(
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
		$subject = new Component($data, $event);

		try
		{
			$subject->build();
			$this->fail('A missing component must stop the build.');
		}
		catch (RuntimeException $error)
		{
			$this->assertSame('Failed to load the component data.', $error->getMessage());
		}

		$this->assertSame([
			'jcb_ce_onBeforeGetComponentData',
			'jcb_ce_onBeforeQueryComponentData',
		], $events);
		$this->assertNull($subject->initialized);
	}
}
