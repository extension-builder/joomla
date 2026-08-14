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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Extension;


use RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Extension\FileContent;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Power\ExtractorInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector as JoomlaPowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Power\Injector as PowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Extension file transformation and persistence contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(FileContent::class)]
#[UsesClass(ContentOne::class)]
#[UsesClass(ContentMulti::class)]
#[UsesClass(Placeholder::class)]
#[UsesClass(PowerInjector::class)]
#[UsesClass(JoomlaPowerInjector::class)]
final class FileContentTest extends CompilerDomainTestCase
{
	/**
	 * File content passes through events, placeholders, custom code, injectors, and persistence.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRunsTheTransformationPipelineInOrder(): void
	{
		$path = null;
		$events = [];
		$written = null;

		try
		{
			$temporaryFile = tempnam(sys_get_temp_dir(), 'jcb-extension-');

			if ($temporaryFile === false)
			{
				throw new RuntimeException('Unable to create the extension-content fixture.');
			}

			$path = $temporaryFile;

			if (file_put_contents(
				$path,
				"###BOM###\nfinal class ###FILENAME###\n{\n\tprivate const NAME = '###TITLE###';\n}"
			) === false)
			{
				throw new RuntimeException('Unable to write the extension-content fixture: ' . $path);
			}

			$registry = new Registry();
			$registry->set('update.file.content.' . $path, true);
			$placeholder = new Placeholder($this->compilerConfig());
			$customcode = $this->createMock(Customcode::class);
			$customcode->expects($this->once())
				->method('update')
				->willReturnCallback(static fn(string $code): string => $code . "\n// customized");
			$event = $this->createMock(EventInterface::class);
			$event->expects($this->exactly(3))
				->method('trigger')
				->willReturnCallback(static function (string $name) use (&$events): void
				{
					$events[] = $name;
				});
			$powerInjector = $this->createMock(PowerInjector::class);
			$powerInjector->expects($this->once())
				->method('power')
				->willReturnCallback(static fn(string $code): string => $code . "\n// power");
			$extractor = $this->createStub(ExtractorInterface::class);
			$extractor->method('get')->willReturn(null);
			$joomlaInjector = new JoomlaPowerInjector(
				$this->createStub(PowerInterface::class),
				$extractor,
				$this->inertCompilerCollaborator(Parser::class),
				$placeholder
			);
			$contentOne = new ContentOne();
			$contentMulti = new ContentMulti();
			$contentMulti->set('article|TITLE', 'Example title');
			$file = $this->createMock(File::class);
			$file->expects($this->once())
				->method('write')
				->with($path, $this->isString())
				->willReturnCallback(static function (string $target, string $data) use (&$written): bool
				{
					$written = $data;
					return true;
				});
			$counter = $this->inertCompilerCollaborator(Counter::class);
			$subject = new FileContent(
				$registry,
				$placeholder,
				$customcode,
				$event,
				$powerInjector,
				$joomlaInjector,
				$contentOne,
				$contentMulti,
				$file,
				$counter
			);

			$subject->set('Example.php', $path, '// generated header', 'article');

			$this->assertSame([
				'jcb_ce_onBeforeSetFileContent',
				'jcb_ce_onGetFileContents',
				'jcb_ce_onBeforeWriteFileContent'
			], $events);
			$this->assertIsString($written);
			$this->assertStringStartsWith("<?php\n// generated header\n", $written);
			$this->assertStringContainsString('final class Example.php', $written);
			$this->assertStringContainsString("private const NAME = 'Example title';", $written);
			$this->assertStringEndsWith("// customized\n// power", $written);
			$this->assertSame(substr_count($written, PHP_EOL), $counter->line);
		}
		finally
		{
			$this->removeTemporaryFile($path);
		}
	}

	/**
	 * Non-array view content fails before custom-code injection or persistence.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRejectsInvalidViewPlaceholderState(): void
	{
		$path = null;

		try
		{
			$temporaryFile = tempnam(sys_get_temp_dir(), 'jcb-extension-');

			if ($temporaryFile === false)
			{
				throw new RuntimeException('Unable to create the invalid-view fixture.');
			}

			$path = $temporaryFile;

			if (file_put_contents($path, 'plain content') === false)
			{
				throw new RuntimeException('Unable to write the invalid-view fixture: ' . $path);
			}

			$contentMulti = new ContentMulti();
			$contentMulti->set('broken', 'not-an-array');
			$customcode = $this->createMock(Customcode::class);
			$customcode->expects($this->never())->method('update');
			$file = $this->createMock(File::class);
			$file->expects($this->never())->method('write');
			$extractor = $this->createStub(ExtractorInterface::class);
			$extractor->method('get')->willReturn(null);
			$placeholder = new Placeholder($this->compilerConfig());
			$subject = new FileContent(
				new Registry(),
				$placeholder,
				$customcode,
				$this->createStub(EventInterface::class),
				$this->createStub(PowerInjector::class),
				new JoomlaPowerInjector(
					$this->createStub(PowerInterface::class),
					$extractor,
					$this->inertCompilerCollaborator(Parser::class),
					$placeholder
				),
				new ContentOne(),
				$contentMulti,
				$file,
				$this->inertCompilerCollaborator(Counter::class)
			);

			$this->expectException(RuntimeException::class);
			$this->expectExceptionMessage('Invalid placeholder data for view [broken]');
			$subject->set('plain.txt', $path, '', 'broken');
		}
		finally
		{
			$this->removeTemporaryFile($path);
		}
	}

	/**
	 * Remove one exact temporary file owned by the current test.
	 *
	 * @param   string|null  $path  Temporary file path.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function removeTemporaryFile(?string $path): void
	{
		if ($path !== null && (is_link($path) || is_file($path)) && !unlink($path))
		{
			throw new RuntimeException('Unable to remove the extension-content fixture: ' . $path);
		}

		if ($path !== null && file_exists($path))
		{
			throw new RuntimeException('The extension-content fixture is not a file: ' . $path);
		}
	}
}
