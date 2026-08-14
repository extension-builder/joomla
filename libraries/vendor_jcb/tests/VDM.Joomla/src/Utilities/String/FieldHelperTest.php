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
use VDM\Joomla\Utilities\String\FieldHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Database-field name normalization contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(FieldHelper::class)]
#[UsesClass(Helper::class)]
#[UsesClass(StringHelper::class)]
final class FieldHelperTest extends JoomlaTestCase
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
	 * Original resolved field-name builder.
	 *
	 * @var    mixed
	 * @since  6.1.6
	 */
	private mixed $originalBuilder;

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
		$this->originalBuilder = (new ReflectionProperty(FieldHelper::class, 'builder'))->getValue();
		$this->originalLanguageTag = StringHelper::$langTag;
		StringHelper::$langTag = 'en-GB';
		$container = new Container();
		$container->share(LanguageFactoryInterface::class, new LanguageFactory(), true);
		$this->setJoomlaContainer($container);
		$this->installBuilder(2);
	}

	/**
	 * Restore all static state after the test.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		(new ReflectionProperty(FieldHelper::class, 'builder'))
			->setValue(null, $this->originalBuilder);
		(new ReflectionProperty(Helper::class, 'params'))
			->setValue(null, $this->originalParams);
		Helper::$option = $this->originalOption;
		StringHelper::$langTag = $this->originalLanguageTag;
		$this->originalParams = [];

		parent::tearDown();
	}

	/**
	 * Normalize field names through whitespace, symbols, Unicode, and numeric prefixes.
	 *
	 * @param   mixed   $input     Candidate field name.
	 * @param   string  $spacer    Word separator.
	 * @param   string  $expected  Expected normalized value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('modernFieldProvider')]
	public function testModernBuilderProducesSafeLowercaseFields(
		mixed $input,
		string $spacer,
		string $expected
	): void
	{
		$this->assertSame($expected, FieldHelper::safe($input, false, $spacer));
	}

	/**
	 * Provide representative modern field-name cases.
	 *
	 * @return  iterable<string, array{mixed, string, string}>
	 * @since   6.1.6
	 */
	public static function modernFieldProvider(): iterable
	{
		yield 'words' => ['Article Title', '_', 'article_title'];
		yield 'numeric prefix' => ['6 Field', '_', 'six_field'];
		yield 'punctuation' => ['Article.Title!', '_', 'articletitle'];
		yield 'unicode' => ['München Straße', '_', 'muenchen_strasse'];
		yield 'custom separator' => ['Article---Title', '-', 'article-title'];
		yield 'empty' => ['', '_', ''];
		yield 'null' => [null, '_', ''];
	}

	/**
	 * Return uppercase output only when explicitly requested.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAllCapsSwitchPreservesTheSameSanitizedWords(): void
	{
		$this->assertSame('ARTICLE_TITLE', FieldHelper::safe('Article Title', true));
		$this->assertSame('SIX_FIELD', FieldHelper::safe('6 Field', true));
	}

	/**
	 * Delegate the legacy mode to the established StringHelper conventions.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLegacyBuilderUsesLegacyLowerAndUpperConventions(): void
	{
		$this->installBuilder(1);

		$this->assertSame('article_title', FieldHelper::safe('Article Title'));
		$this->assertSame('ARTICLE_TITLE', FieldHelper::safe('Article Title', true));
	}

	/**
	 * Install the requested field-name builder into the component parameter cache.
	 *
	 * @param   int  $builder  Builder mode.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function installBuilder(int $builder): void
	{
		Helper::$option = 'com_componentbuilder';
		(new ReflectionProperty(Helper::class, 'params'))->setValue(
			null,
			['com_componentbuilder' => new Registry(['field_name_builder' => $builder])]
		);
		(new ReflectionProperty(FieldHelper::class, 'builder'))->setValue(null, false);
	}
}
