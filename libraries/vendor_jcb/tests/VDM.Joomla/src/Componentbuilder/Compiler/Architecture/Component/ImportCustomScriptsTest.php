<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Component;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\ImportCustomScripts;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HeaderInterface;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The custom import files a list view is given.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\Component')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ImportCustomScriptsTest extends ArchitectureTestCase
{
	/**
	 * What the compiler wrote for the import files of one list view.
	 *
	 * @var    array<string, string>
	 * @since  6.1.7
	 */
	private const EXPECTED = [
		'###IMPORT_EXT_METHOD###' => '// php_import_ext of import_demos',
		'###IMPORT_DISPLAY_METHOD_CUSTOM###' => '// php_import_display of import_demos',
		'###IMPORT_SETDATA_METHOD###' => '// php_import_setdata of import_demos',
		'###IMPORT_METHOD_CUSTOM###' => '// php_import of import_demos',
		'###IMPORT_SAVE_METHOD###' => '// php_import_save of import_demos',
		'###IMPORT_DEFAULT_VIEW_CUSTOM###' => '// html_import_view of import_demos',
		'###VIEW###' => 'IMPORT_DEMOS',
		'###View###' => 'Import_demos',
		'###view###' => 'import_demos',
		'###VIEWS###' => 'IMPORT_DEMOS',
		'###Views###' => 'Import_demos',
		'###views###' => 'import_demos',
		'###IMPORT_CUSTOM_CONTROLLER_HEADER###' => '// header import.custom.controller demos',
		'###IMPORT_CUSTOM_MODEL_HEADER###' => '// header import.custom.model demos'
	];

	/**
	 * The files of one list view are asked for, and filled in.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheImportFilesOfAListViewAreWritten(): void
	{
		$built = [];
		$content = new ContentMulti();
		$subject = $this->renderer(ImportCustomScripts::class, [
			'contentmulti' => $content,
			'dispenser' => $this->customCode(),
			'header' => $this->headers(),
			'structure' => $this->files($built)
		]);
		$this->namedViews();

		$subject->set('demos');

		$this->assertSame(['customimport|import_demos'], $built);
		$this->assertSame(
			['import_demos' => self::EXPECTED], $content->allActive()
		);
	}

	/**
	 * A second list view is written beside the first, not over it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASecondListViewIsWrittenBesideTheFirst(): void
	{
		$built = [];
		$content = new ContentMulti();
		$subject = $this->renderer(ImportCustomScripts::class, [
			'contentmulti' => $content,
			'dispenser' => $this->customCode(),
			'header' => $this->headers(),
			'structure' => $this->files($built)
		]);
		$this->namedViews();

		$subject->set('demos');
		$subject->set('lookers');

		$this->assertSame(
			['customimport|import_demos', 'customimport|import_lookers'], $built
		);
		$this->assertSame(
			['import_demos', 'import_lookers'],
			array_keys($content->allActive())
		);
	}

	/**
	 * Name the views the placeholders stand for.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function namedViews(): void
	{
		$this->placeholder()->set('VIEWS', 'DEMOS');
		$this->placeholder()->set('views', 'demos');
	}

	/**
	 * A dispenser that names the custom code it was asked for.
	 *
	 * @return  Dispenser
	 * @since   6.1.7
	 */
	private function customCode(): Dispenser
	{
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->method('get')->willReturnCallback(
			static fn($type, $key, $prefix = null, $suffix = null, $unset = false):
				string => '// ' . $type . ' of ' . $key
		);

		return $dispenser;
	}

	/**
	 * A header writer that names the header it was asked for.
	 *
	 * @return  HeaderInterface
	 * @since   6.1.7
	 */
	private function headers(): HeaderInterface
	{
		$header = $this->createStub(HeaderInterface::class);
		$header->method('get')->willReturnCallback(
			static fn($type, $name = null): string => '// header ' . $type . ' ' . $name
		);

		return $header;
	}

	/**
	 * A file builder that records the targets it was asked to build.
	 *
	 * @param   array  $built  The targets asked for, in order.
	 *
	 * @return  Structure
	 * @since   6.1.7
	 */
	private function files(array &$built): Structure
	{
		$structure = $this->createStub(Structure::class);
		$structure->method('build')->willReturnCallback(
			static function (array $target, string $type, $fileName = null, $config = null)
				use (&$built): bool
			{
				$built[] = $type . '|' . implode(',', $target);

				return true;
			}
		);

		return $structure;
	}
}
