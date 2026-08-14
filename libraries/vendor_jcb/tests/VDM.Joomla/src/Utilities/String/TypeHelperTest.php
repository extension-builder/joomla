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

namespace VDM\Joomla\Tests\Utilities\String;


use Joomla\CMS\Language\LanguageFactory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\DI\Container;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionProperty;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Joomla\Utilities\String\TypeHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Field-type identifier normalization and cache contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(TypeHelper::class)]
#[UsesClass(Helper::class)]
#[UsesClass(StringHelper::class)]
final class TypeHelperTest extends JoomlaTestCase
{
	/**
	 * Original component option.
	 *
	 * @var    mixed
	 * @since  6.1.6
	 */
	private mixed $originalOption;

	/**
	 * Original component parameter cache.
	 *
	 * @var    array<mixed>
	 * @since  6.1.6
	 */
	private array $originalParams = [];

	/**
	 * Original resolved type-name builder.
	 *
	 * @var    mixed
	 * @since  6.1.6
	 */
	private mixed $originalBuilder;

	/**
	 * Original normalized type-name cache.
	 *
	 * @var    array<mixed>
	 * @since  6.1.6
	 */
	private array $originalCache = [];

	/**
	 * Original language tag restored after every test.
	 *
	 * @var    mixed
	 * @since  6.1.6
	 */
	private mixed $originalLanguageTag;

	/**
	 * Install isolated component parameters and language services.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->originalOption = Helper::$option;
		$this->originalParams = (new ReflectionProperty(Helper::class, 'params'))->getValue();
		$this->originalBuilder = (new ReflectionProperty(TypeHelper::class, 'builder'))->getValue();
		$this->originalCache = (new ReflectionProperty(TypeHelper::class, 'cache'))->getValue();
		$this->originalLanguageTag = StringHelper::$langTag;
		StringHelper::$langTag = 'en-GB';

		$container = new Container();
		$container->share(LanguageFactoryInterface::class, new LanguageFactory(), true);
		$this->setJoomlaContainer($container);
		$this->installBuilder(2);
	}

	/**
	 * Reset process-static builder, cache, parameters, and language state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		(new ReflectionProperty(TypeHelper::class, 'builder'))
			->setValue(null, $this->originalBuilder);
		(new ReflectionProperty(TypeHelper::class, 'cache'))
			->setValue(null, $this->originalCache);
		(new ReflectionProperty(Helper::class, 'params'))
			->setValue(null, $this->originalParams);
		Helper::$option = $this->originalOption;
		StringHelper::$langTag = $this->originalLanguageTag;
		$this->originalParams = [];
		$this->originalCache = [];

		parent::tearDown();
	}

	/**
	 * Preserve camel case while enforcing the one-period type-name grammar.
	 *
	 * @param   mixed   $input     Candidate field type.
	 * @param   string  $expected  Expected safe identifier.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('modernTypeProvider')]
	public function testModernBuilderProducesStableTypeIdentifiers(mixed $input, string $expected): void
	{
		$this->assertSame($expected, TypeHelper::safe($input));
	}

	/**
	 * Provide camel case, numeric prefix, periods, symbols, Unicode, and empty input.
	 *
	 * @return  iterable<string, array{mixed, string}>
	 * @since   6.1.6
	 */
	public static function modernTypeProvider(): iterable
	{
		yield 'camel case' => ['repeatableSubform', 'repeatableSubform'];
		yield 'leading number' => ['6Field', 'sixField'];
		yield 'single period' => ['layout.variant', 'layout.variant'];
		yield 'subsequent periods removed' => ['layout.variant.extra', 'layout.variantextra'];
		yield 'symbols removed' => ['Some-Type Name!', 'sometypename'];
		yield 'unicode transliterated' => ['MünchenType', 'muenchentype'];
		yield 'empty input' => ['', ''];
		yield 'null input' => [null, ''];
	}

	/**
	 * Use the legacy lower-identifier convention when configured by the component.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLegacyBuilderDelegatesToTheLegacyStringConvention(): void
	{
		$this->installBuilder(1);

		$this->assertSame('repeatable_subform', TypeHelper::safe('Repeatable Subform'));
		$this->assertSame('six_field', TypeHelper::safe('6 Field'));
	}

	/**
	 * Return the cached result without re-reading mutated component configuration.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepeatedInputUsesTheEstablishedCacheEntry(): void
	{
		$this->assertSame('someType', TypeHelper::safe('someType'));

		$this->installBuilder(1, false);

		$this->assertSame('someType', TypeHelper::safe('someType'));
	}

	/**
	 * Install one naming-builder mode into the component parameter cache.
	 *
	 * @param   int   $builder       Builder mode.
	 * @param   bool  $resetStatics  Whether to reset the resolved mode and cache.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function installBuilder(int $builder, bool $resetStatics = true): void
	{
		Helper::$option = 'com_componentbuilder';
		(new ReflectionProperty(Helper::class, 'params'))->setValue(
			null,
			['com_componentbuilder' => new Registry(['type_name_builder' => $builder])]
		);

		if ($resetStatics)
		{
			(new ReflectionProperty(TypeHelper::class, 'builder'))->setValue(null, 0);
			(new ReflectionProperty(TypeHelper::class, 'cache'))->setValue(null, []);
		}
	}
}
