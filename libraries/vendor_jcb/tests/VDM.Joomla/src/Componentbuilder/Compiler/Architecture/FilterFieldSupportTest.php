<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\FilterFieldFile;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\CustomFieldCode;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;


/**
 * Custom field code and filter field file contracts.
 *
 * Neither differs between Joomla targets, so each is one class with no
 * target variants at all.
 *
 * @since  6.1.7
 */
#[CoversClass(CustomFieldCode::class)]
#[CoversClass(FilterFieldFile::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class FilterFieldSupportTest extends ArchitectureTestCase
{
	/**
	 * A field carrying no PHP yields two empty buckets.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFieldWithoutPhpYieldsEmptyBuckets(): void
	{
		$code = $this->customFieldCode()->get([]);

		$this->assertSame(
			['JFORM_TYPE_HEADER' => '', 'JFORM_TYPE_PHP' => ''],
			$code
		);
	}

	/**
	 * Header PHP lands in the header bucket, everything else in the class one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testHeaderPhpIsKeptApartFromClassPhp(): void
	{
		$code = $this->customFieldCode()->get([
			'phpHEADER' => ['use Joomla\CMS\Factory;'],
			'phpa' => ['return [];'],
		]);

		$this->assertStringContainsString('use Joomla\CMS\Factory;', $code['JFORM_TYPE_HEADER']);
		$this->assertStringNotContainsString('return [];', $code['JFORM_TYPE_HEADER']);
		$this->assertStringContainsString('return [];', $code['JFORM_TYPE_PHP']);
	}

	/**
	 * Escaped tabs and breaks become real indentation and line breaks.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEscapedTabsAndBreaksAreExpanded(): void
	{
		$code = $this->customFieldCode()->get([
			'phpa' => ['if (true)\n\treturn [];'],
		]);

		$this->assertStringContainsString(PHP_EOL, $code['JFORM_TYPE_PHP']);
		$this->assertStringNotContainsString('\n', $code['JFORM_TYPE_PHP']);
		$this->assertStringNotContainsString('\t', $code['JFORM_TYPE_PHP']);
	}

	/**
	 * Empty lines contribute nothing to either bucket.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEmptyLinesContributeNothing(): void
	{
		$code = $this->customFieldCode()->get(['phpa' => ['', '   ']]);

		$this->assertStringNotContainsString('   ', $code['JFORM_TYPE_PHP']);
	}

	/**
	 * A filter field type is registered once with its own get options code.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFilterFieldTypeIsRegisteredWithItsOptions(): void
	{
		$contentmulti = new ContentMulti();

		$this->filterFieldFile($contentmulti)
			->set('return [];', ['filter_type' => 'article_status']);

		$this->assertSame('J', $contentmulti->get('customfilterfield_article_status|JPREFIX'));
		$this->assertSame(
			'Article_status',
			$contentmulti->get('customfilterfield_article_status|Type')
		);
		$this->assertSame(
			'article_status',
			$contentmulti->get('customfilterfield_article_status|type')
		);
		$this->assertSame(
			'return [];',
			$contentmulti->get('customfilterfield_article_status|JFORM_GETOPTIONS_PHP')
		);
		$this->assertSame(
			'',
			$contentmulti->get('customfilterfield_article_status|ADD_BUTTON')
		);
	}

	/**
	 * A filter field type that already exists is not rebuilt.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnExistingFilterFieldTypeIsNotRebuilt(): void
	{
		$contentmulti = new ContentMulti();
		$contentmulti->set('customfilterfield_article_status|JFORM_GETOPTIONS_PHP', 'first');

		$structure = $this->createMock(Structure::class);
		$structure->expects($this->never())->method('build');

		$this->filterFieldFile($contentmulti, $structure)
			->set('second', ['filter_type' => 'article_status']);

		$this->assertSame(
			'first',
			$contentmulti->get('customfilterfield_article_status|JFORM_GETOPTIONS_PHP')
		);
	}

	/**
	 * A new filter field type has its field list file built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testANewFilterFieldTypeHasItsFileBuilt(): void
	{
		$structure = $this->createMock(Structure::class);
		$structure->expects($this->once())
			->method('build')
			->with(['admin' => 'customfilterfield'], 'fieldlist', 'article_status');

		$this->filterFieldFile(new ContentMulti(), $structure)
			->set('return [];', ['filter_type' => 'article_status']);
	}

	/**
	 * Create the custom field code collector.
	 *
	 * @return  CustomFieldCode
	 * @since   6.1.7
	 */
	private function customFieldCode(): CustomFieldCode
	{
		return new CustomFieldCode($this->placeholder());
	}

	/**
	 * Create the filter field file builder.
	 *
	 * @param   ContentMulti     $contentmulti  The multi content registry.
	 * @param   Structure|null   $structure     The structure builder.
	 *
	 * @return  FilterFieldFile
	 * @since   6.1.7
	 */
	private function filterFieldFile(ContentMulti $contentmulti,
		?Structure $structure = null): FilterFieldFile
	{
		return new FilterFieldFile(
			$contentmulti,
			$structure ?? $this->createStub(Structure::class)
		);
	}
}
