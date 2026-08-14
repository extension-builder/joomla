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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Unique;
use VDM\Tests\Support\TestCase;


/**
 * Compiler-local identifier uniqueness contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Unique::class)]
final class UniqueTest extends TestCase
{
	/**
	 * Unique-key state active before the current test.
	 *
	 * @var    array<mixed>
	 * @since  6.1.6
	 */
	private array $originalUniqueState = [];

	/**
	 * Area state active before the current test.
	 *
	 * @var    array<mixed>
	 * @since  6.1.6
	 */
	private array $originalAreaState = [];

	/**
	 * Reset static identifier history around every test.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->originalUniqueState = (new ReflectionProperty(Unique::class, 'unique'))->getValue();
		$this->originalAreaState = (new ReflectionProperty(Unique::class, 'areas'))->getValue();
		$this->resetUniqueState();
	}

	/**
	 * Clear state so random test order cannot alter future suffixes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		(new ReflectionProperty(Unique::class, 'unique'))
			->setValue(null, $this->originalUniqueState);
		(new ReflectionProperty(Unique::class, 'areas'))
			->setValue(null, $this->originalAreaState);
		$this->originalUniqueState = [];
		$this->originalAreaState = [];

		parent::tearDown();
	}

	/**
	 * Increment alphabetic keys independently for each requested width.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetProducesIndependentAlphabeticSequencesBySize(): void
	{
		$this->assertSame('v', Unique::get(1));
		$this->assertSame('w', Unique::get(1));
		$this->assertSame('vv', Unique::get(2));
		$this->assertSame('vw', Unique::get(2));
		$this->assertSame('x', Unique::get(1));
	}

	/**
	 * Preserve the first code and suffix collisions within the same target.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCodeSuffixesRepeatedValuesWithinOneTarget(): void
	{
		$this->assertSame('Article', Unique::code('Article', 'admin'));
		$this->assertSame('Articlev', Unique::code('Article', 'admin'));
		$this->assertSame('Articlew', Unique::code('Article', 'admin'));
	}

	/**
	 * Allow the same unsuffixed value in independent uniqueness areas.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCodeSeparatesFirstUseAcrossTargets(): void
	{
		$this->assertSame('Article', Unique::code('Article', 'admin'));
		$this->assertSame('Article', Unique::code('Article', 'site'));
		$this->assertSame('Article', Unique::code('Article', 'api'));
	}

	/**
	 * Reset the two process-static uniqueness registries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function resetUniqueState(): void
	{
		(new ReflectionProperty(Unique::class, 'unique'))->setValue(null, []);
		(new ReflectionProperty(Unique::class, 'areas'))->setValue(null, []);
	}
}
