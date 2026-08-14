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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Field;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Field\ModalSelect;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Modal-select field metadata extraction contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(ModalSelect::class)]
#[UsesClass(ContentMulti::class)]
final class ModalSelectTest extends CompilerDomainTestCase
{
	/**
	 * URLs define the component and plural/singular views and custom keys request one override.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExtractPersistsResolvedMetadataAndBuildsCustomKeyOverrideOnce(): void
	{
		$structure = $this->createMock(Structure::class);
		$structure->expects($this->exactly(2))
			->method('build')
			->willReturn(true);
		$content = new ContentMulti();
		$subject = new ModalSelect($structure, $content);
		$attributes = [
			'urlSelect' => 'index.php?option=com_catalog&view=products',
			'urlEdit' => 'index.php?option=com_catalog&view=product',
			'urlNew' => 'index.php?option=com_catalog&view=product',
			'hint' => 'Choose a product',
			'titleSelect' => 'Select Product',
			'iconSelect' => 'box',
			'sql_title_table' => '#__catalog_product',
			'sql_title_key' => 'guid',
			'sql_title_column' => 'name'
		];

		$first = $subject->extract($attributes);
		$second = $subject->extract($attributes);

		$this->assertSame($first, $second);
		$this->assertSame('com_catalog', $first['component']);
		$this->assertSame('product', $first['view']);
		$this->assertSame('products', $first['views']);
		$this->assertSame('guid', $first['id']);
		$this->assertSame('name', $first['text']);
		$this->assertSame('guid', $content->get('product|SQL_TITLE_KEY'));
		$this->assertSame('name', $content->get('product|SQL_TITLE_COLUMN'));
		$this->assertSame('blabla', $content->get('fieldmodalselect_override|BLABLA'));
	}

	/**
	 * The table name supplies a singular view when edit/new URLs do not.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExtractFallsBackToTableViewAndSafeDefaults(): void
	{
		$structure = $this->createMock(Structure::class);
		$structure->expects($this->never())->method('build');
		$content = new ContentMulti();
		$subject = new ModalSelect($structure, $content);

		$result = $subject->extract(['sql_title_table' => '#__shop_order']);

		$this->assertSame('order', $result['view']);
		$this->assertSame('error', $result['component']);
		$this->assertSame('error', $result['views']);
		$this->assertSame('id', $result['id']);
		$this->assertSame('id', $result['text']);
		$this->assertSame('id', $content->get('order|SQL_TITLE_KEY'));
		$this->assertSame('id', $content->get('order|SQL_TITLE_COLUMN'));
	}
}
