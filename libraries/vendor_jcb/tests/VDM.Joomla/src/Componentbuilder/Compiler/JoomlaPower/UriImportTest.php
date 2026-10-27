<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    27th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\JoomlaPower;


use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\ComHelperClass\UserPermissionCheckAccess;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Extension\FileContent;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaFive\Header as JoomlaFiveHeader;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaFour\Header as JoomlaFourHeader;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaSix\Header as JoomlaSixHeader;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector;
use VDM\Joomla\Componentbuilder\Compiler\Power\Extractor as PowerExtractor;
use VDM\Joomla\Componentbuilder\Compiler\Power\Injector as PowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * URI imports survive generated-code assembly for Joomla 4, 5, and 6.
 *
 * Issue #1123 is guarded at the Power resolution boundary and at the generated
 * controller and access-denied redirect paths that consume that import.
 *
 * @since  6.2.1
 */
#[CoversClass(Injector::class)]
#[CoversClass(FileContent::class)]
#[UsesClass(Extractor::class)]
#[UsesClass(PowerExtractor::class)]
#[UsesClass(PowerInjector::class)]
#[UsesClass(Parser::class)]
#[UsesClass(UserPermissionCheckAccess::class)]
final class UriImportTest extends ArchitectureTestCase
{
	/**
	 * Stable Joomla Power identity maintained in the Joomla Powers catalogue.
	 *
	 * @var    string
	 * @since  6.2.1
	 */
	private const URI_GUID = 'eecc143e-b5cf-4c33-ba4d-97da1df61422';

	/**
	 * Generated token retained until the file import pass.
	 *
	 * @var    string
	 * @since  6.2.1
	 */
	private const URI_TOKEN = 'Joomla___eecc143e_b5cf_4c33_ba4d_97da1df61422___Power';

	/**
	 * Imports are added, reused, and aliased without changing the resolved class.
	 *
	 * @param   int     $target    Joomla compile target.
	 * @param   string  $imports   Existing import statements.
	 * @param   string  $name      Expected URI reference after injection.
	 * @param   string  $expected  Expected URI import statement.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('importCases')]
	public function testUriImportsResolveAndExecute(int $target, string $imports, string $name, string $expected): void
	{
		$this->config()->set('joomla_version', $target);
		$namespace = 'Acme\\UriContract\\J' . $target . '\\Case' . substr(md5($imports), 0, 8);
		$code = implode(PHP_EOL, [
			'<?php',
			'namespace ' . $namespace . ';',
			'',
			$imports,
			'',
			'final class Example',
			'{',
			"\tpublic static function query(): string",
			"\t{",
			"\t\treturn " . self::URI_TOKEN . "::buildQuery(['view' => 'items']);",
			"\t}",
			'',
			"\tpublic static function anotherQuery(): string",
			"\t{",
			"\t\treturn " . self::URI_TOKEN . "::buildQuery(['view' => 'item']);",
			"\t}",
			'}',
			'',
		]);
		$injector = $this->injector($target);

		$output = $injector->power($code);

		$this->assertSame(1, substr_count($output, $expected));
		$this->assertSame(2, substr_count($output, $name . '::buildQuery('));
		$this->assertStringNotContainsString(self::URI_TOKEN, $output);
		$this->assertSame($output, $injector->power($output), 'A second import pass must be idempotent.');
		$this->assertNotEmpty(token_get_all($output, TOKEN_PARSE));
		$this->assertLessThan(strpos($output, 'final class'), strpos($output, $expected), $output);
		eval(substr($output, strlen('<?php')));
		$class = $namespace . '\\Example';
		$this->assertSame('view=items', $class::query());
		$this->assertSame('view=item', $class::anotherQuery());

		// The compiler shares its injector across files; aliases are file-local.
		$fresh = $injector->power(str_replace($imports, '', $code));
		$this->assertStringContainsString('use Joomla\\CMS\\Uri\\Uri;', $fresh);
		$this->assertStringContainsString('return Uri::buildQuery(', $fresh);
		$this->assertStringNotContainsString(' as ', $fresh);
	}

	/**
	 * Real site controller templates retain their URI checks and one import.
	 *
	 * @param   int           $target       Joomla compile target.
	 * @param   class-string  $headerClass  Target header generator.
	 * @param   string        $directory    Target template directory.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('targets')]
	public function testSiteControllerTemplateRetainsUriImport(int $target, string $headerClass, string $directory): void
	{
		$this->prepareTarget($target);
		$source = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . '/compiler/' . $directory . '/SITE_ADMIN_VIEW_CONTROLLER.php');
		$this->assertIsString($source);
		$header = $this->renderer($headerClass)->get('site.admin.view.controller', 'item');
		$content = new ContentOne();

		foreach ([
			'NAMESPACEPREFIX' => 'Acme',
			'ComponentNamespace' => 'Demo',
			'COMPONENT' => 'DEMO',
			'component' => 'demo',
			'VIEW' => 'ITEM',
			'View' => 'Item',
			'view' => 'item',
			'SITE_DEFAULT_VIEW' => 'catalogue',
		] as $key => $value)
		{
			$content->set($key, $value);
		}

		$views = new ContentMulti();
		$views->set('item|SITE_ADMIN_VIEW_CONTROLLER_HEADER', $header);

		foreach ([
			'ADMIN_CUSTOM_BUTTONS_CONTROLLER',
			'JCONTROLLERFORM_ALLOWADD',
			'JCONTROLLERFORM_ALLOWEDIT',
			'JCONTROLLERFORM_BEFORECANCEL',
			'JCONTROLLERFORM_AFTERCANCEL',
			'POSTSAVEHOOK',
		] as $key)
		{
			$views->set('item|' . $key, '');
		}

		$output = $this->assemble($source, $target, $content, $views);

		$this->assertSame(1, substr_count($output, 'use Joomla\\CMS\\Uri\\Uri;'));
		$this->assertSame(2, substr_count($output, 'Uri::isInternal(base64_decode($return))'));
		$this->assertSame(2, substr_count($output, '$redirect = base64_decode($return);'));
		$this->assertSame(2, preg_match_all('/Route::_\(\s*\$redirect, false\s*\)/', $output));
		$this->assertStringNotContainsString('Joomla___', $output);
		$this->assertStringNotContainsString('###', $output);
		$this->assertNotEmpty(token_get_all($output, TOKEN_PARSE));
	}

	/**
	 * The access-denied emitter introduces its URI dependency after header assembly.
	 *
	 * @param   int           $target       Joomla compile target.
	 * @param   class-string  $headerClass  Target header generator.
	 * @param   string        $directory    Target template directory.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('targets')]
	public function testAccessDeniedRedirectAddsUriImport(int $target, string $headerClass, string $directory): void
	{
		$this->prepareTarget($target);
		$header = $this->renderer($headerClass)->get('site.view.html', 'item');
		$content = new ContentOne();
		$content->set('SITE_DEFAULT_VIEW', 'item');
		$emitter = new UserPermissionCheckAccess($content, $this->config(), $this->language());
		$redirect = $emitter->get(['access' => 1, 'settings' => (object) ['code' => 'item']], 1);
		$views = new ContentMulti();
		$views->set('item|HEADER', $header);
		$views->set('item|ACCESS_CHECK', $redirect);
		$source = implode(PHP_EOL, [
			'###BOM###',
			'namespace Acme\\Component\\Demo\\Site\\View\\Item;',
			'',
			'###HEADER###',
			'',
			'class HtmlView extends BaseHtmlView',
			'{',
			"\tpublic function accessCheck()",
			"\t{###ACCESS_CHECK###",
			"\t}",
			'}',
			'',
		]);

		$this->assertStringContainsString(self::URI_TOKEN . '::root()', $redirect);
		$output = $this->assemble($source, $target, new ContentOne(), $views);

		$this->assertSame(1, substr_count($output, 'use Joomla\\CMS\\Uri\\Uri;'));
		$this->assertStringContainsString('$app->redirect(Uri::root());', $output);
		$this->assertStringContainsString("->authorise('site.item.access', 'com_demo')", $output);
		$this->assertStringNotContainsString('Joomla___', $output);
		$this->assertStringNotContainsString('###', $output);
		$this->assertNotEmpty(token_get_all($output, TOKEN_PARSE));
	}

	/**
	 * Supported target identities and their maintained template families.
	 *
	 * @return  iterable<string, array{int, class-string, string}>
	 * @since   6.2.1
	 */
	public static function targets(): iterable
	{
		yield 'Joomla 4' => [4, JoomlaFourHeader::class, 'joomla_4'];
		yield 'Joomla 5' => [5, JoomlaFiveHeader::class, 'joomla_4'];
		yield 'Joomla 6' => [6, JoomlaSixHeader::class, 'joomla_6'];
	}

	/**
	 * URI imports coexist with user-defined aliases for every supported target.
	 *
	 * @return  iterable<string, array{int, string, string, string}>
	 * @since   6.2.1
	 */
	public static function importCases(): iterable
	{
		$cases = [
			'missing' => ['', 'Uri', 'use Joomla\\CMS\\Uri\\Uri;'],
			'existing' => ['use Joomla\\CMS\\Uri\\Uri;', 'Uri', 'use Joomla\\CMS\\Uri\\Uri;'],
			'aliased' => ['use Joomla\\CMS\\Uri\\Uri as CoreUri;', 'CoreUri', 'use Joomla\\CMS\\Uri\\Uri as CoreUri;'],
			'collision' => ['use Acme\\Network\\Uri;', 'UriUri', 'use Joomla\\CMS\\Uri\\Uri as UriUri;'],
		];

		foreach ([4, 5, 6] as $target)
		{
			foreach ($cases as $name => $arguments)
			{
				yield 'Joomla ' . $target . ' ' . $name => [$target, ...$arguments];
			}
		}
	}

	/**
	 * Prepare names and version settings consumed by real header generators.
	 *
	 * @param   int  $target  Joomla compile target.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	private function prepareTarget(int $target): void
	{
		$this->config()->set('joomla_version', $target);
		$this->config()->set('build_target', 'site');
		$this->config()->set('uikit', 0);
		$this->placeholder()->set('Component', 'Demo');
		$this->placeholder()->set('ComponentNamespace', 'Demo');
	}

	/**
	 * Resolve recorded catalogue values through the real extractor and import pipeline.
	 *
	 * @param   int  $target  Joomla compile target.
	 *
	 * @return  Injector
	 * @since   6.2.1
	 */
	private function injector(int $target): Injector
	{
		$catalogue = $this->createStub(PowerInterface::class);
		$catalogue->method('get')->willReturnCallback(static function (string $guid): object
		{
			$namespace = match ($guid)
			{
				self::URI_GUID => 'Joomla\\CMS\\Uri\\Uri',
				'39403062-84fb-46e0-bac4-0023f766e827' => 'Joomla\\CMS\\Factory',
				'd4c76099-4c32-408a-8701-d0a724484dfd' => 'Joomla\\CMS\\Router\\Route',
			};
			$separator = strrpos($namespace, '\\');

			return (object) [
				'_namespace' => substr($namespace, 0, $separator),
				'class_name' => substr($namespace, $separator + 1),
				'type' => 'class',
			];
		});

		return new Injector(
			$catalogue,
			new Extractor($this->createStub(DatabaseInterface::class), $target),
			new Parser(),
			$this->placeholder()
		);
	}

	/**
	 * Pass generated fragments through the production file assembly operation.
	 *
	 * @param   string        $source   Prepared template source.
	 * @param   int           $target   Joomla compile target.
	 * @param   ContentOne    $content  Global generated placeholders.
	 * @param   ContentMulti  $views    Contextual generated placeholders.
	 *
	 * @return  string  The exact content handed to the file writer.
	 * @since   6.2.1
	 */
	private function assemble(string $source, int $target, ContentOne $content, ContentMulti $views): string
	{
		$path = $this->writeTemporaryFile('generated.php', $source);
		$output = null;
		$file = $this->createMock(File::class);
		$file->expects($this->once())->method('write')->with($path, $this->isString())
			->willReturnCallback(static function (string $path, string $code) use (&$output): bool
			{
				$output = $code;
				return true;
			});
		$superPowers = $this->createMock(PowerInterface::class);
		$superPowers->expects($this->never())->method('get');
		$customcode = $this->createMock(Customcode::class);
		$customcode->expects($this->never())->method('update');
		$counter = $this->renderer(Counter::class);
		$subject = new FileContent(
			new Registry(),
			$this->placeholder(),
			$customcode,
			$this->createStub(EventInterface::class),
			new PowerInjector(
				$superPowers,
				new PowerExtractor($this->createStub(DatabaseInterface::class)),
				new Parser(),
				$this->placeholder()
			),
			$this->injector($target),
			$content,
			$views,
			$file,
			$counter
		);
		$subject->set('generated.php', $path, '', 'item');
		$this->assertIsString($output);
		$this->assertSame(substr_count($output, PHP_EOL), $counter->line);

		return $output;
	}
}
