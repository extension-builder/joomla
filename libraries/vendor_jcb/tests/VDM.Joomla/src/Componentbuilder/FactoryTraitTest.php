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

namespace VDM\Joomla\Tests\Componentbuilder;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Factory;
use VDM\Joomla\Componentbuilder\FactoryTrait;
use VDM\Joomla\Componentbuilder\Fieldtype\Factory as FieldtypeFactory;
use VDM\Joomla\Componentbuilder\Power\Factory as PowerFactory;
use VDM\Tests\Support\FactoryTraitFixture;
use VDM\Tests\Support\TestCase;


/**
 * Entity-factory trait selection, caching, and error-contract tests.
 *
 * @since  6.1.6
 */
#[CoversTrait(FactoryTrait::class)]
#[UsesClass(Factory::class)]
final class FactoryTraitTest extends TestCase
{
	/**
	 * Select a primary entity and cache each resolved factory by entity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEntityFactoriesAreResolvedAndCached(): void
	{
		$subject = new FactoryTraitFixture();

		$this->assertSame($subject, $subject->select('power'));
		$this->assertSame('power', $subject->selected());
		$this->assertSame(PowerFactory::class, $subject->factory());
		$this->assertSame(PowerFactory::class, $subject->factory());
		$this->assertSame(FieldtypeFactory::class, $subject->factoryFor('fieldtype'));
		$this->assertSame(
			[
				'power' => PowerFactory::class,
				'fieldtype' => FieldtypeFactory::class
			],
			$subject->cache()
		);
		$this->assertSame('Power', $subject->areaFor('power'));
		$this->assertNull($subject->areaFor('unknown'));
	}

	/**
	 * Reject an entity that is absent from the central routing catalog.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUnknownEntityFactoryIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('No factory is registered for entity "unknown".');

		(new FactoryTraitFixture())->factoryFor('unknown');
	}

	/**
	 * Report the public validation exception when no primary entity was selected.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testMissingPrimaryEntityIsRejectedWithInvalidArgumentException(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('no entity was provided');

		(new FactoryTraitFixture())->factory();
	}
}
