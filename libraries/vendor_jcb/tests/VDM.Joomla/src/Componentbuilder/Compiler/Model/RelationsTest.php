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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Model;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldRelations;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListHeadOverride;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListJoin;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Model\Relations;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\GuidHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Admin-view relation normalization and builder-state contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Relations::class)]
#[UsesClass(Config::class)]
#[UsesClass(Language::class)]
#[UsesClass(FieldRelations::class)]
#[UsesClass(ListHeadOverride::class)]
#[UsesClass(ListJoin::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(GuidHelper::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(StringHelper::class)]
final class RelationsTest extends CompilerDomainTestCase
{
	/**
	 * Normalize valid relations into custom code, joins, language, and header state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetCoordinatesValidRelationsAndConsumesSourceJson(): void
	{
		$firstField = '57d5f6fa-7b1c-4d36-b382-93281c3ef020';
		$secondField = '9bf7fc62-6f25-44b6-82a8-3f053019a16e';
		$config = $this->compilerConfig([
			'lang_prefix' => 'COM_DEMO',
			'remove_line_breaks' => false
		]);
		$language = new Language($config);
		$customCalls = [];
		$customcode = $this->createMock(Customcode::class);
		$customcode->expects($this->once())
			->method('update')
			->with('[CUSTOM]')
			->willReturnCallback(
				static function (string $code) use (&$customCalls): string
				{
					$customCalls[] = $code;

					return 'expanded:' . $code;
				}
			);
		$listJoin = new ListJoin();
		$headOverride = new ListHeadOverride();
		$fieldRelations = new FieldRelations();
		$item = (object) [
			'name_list_code' => 'articles',
			'addrelations' => json_encode([
				[
					'listfield' => $firstField,
					'area' => 2,
					'set' => '[CUSTOM]',
					'joinfields' => ['author', 'category'],
					'column_name' => 'Related Author'
				],
				[
					'listfield' => $secondField,
					'area' => '1',
					'column_name' => ' default '
				],
				[
					'listfield' => $firstField,
					'area' => 0,
					'set' => 'must not run'
				]
			], JSON_THROW_ON_ERROR)
		];

		(new Relations(
			$config,
			$language,
			$customcode,
			$listJoin,
			$headOverride,
			$fieldRelations
		))->set($item);

		$this->assertSame(['[CUSTOM]'], $customCalls);
		$this->assertSame(
			'expanded:[CUSTOM]',
			$fieldRelations->get('articles.' . $firstField . '.2.set')
		);
		$this->assertSame(
			' default ',
			$fieldRelations->get('articles.' . $secondField . '.1.column_name')
		);
		$this->assertSame('author', $listJoin->get('articles.author'));
		$this->assertSame('category', $listJoin->get('articles.category'));
		$this->assertSame(
			'COM_DEMO_ARTICLES_RELATED_AUTHOR',
			$headOverride->get('articles.' . $firstField)
		);
		$this->assertSame(
			'Related Author',
			$language->get('admin', 'COM_DEMO_ARTICLES_RELATED_AUTHOR')
		);
		$this->assertFalse($headOverride->exists('articles.' . $secondField));
		$this->assertObjectNotHasProperty('addrelations', $item);
	}

	/**
	 * Normalize malformed source data to no builder state and consume it.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRejectsMalformedRelationJson(): void
	{
		$config = $this->compilerConfig([
			'lang_prefix' => 'COM_DEMO',
			'remove_line_breaks' => false
		]);
		$customcode = $this->createMock(Customcode::class);
		$customcode->expects($this->never())->method('update');
		$listJoin = new ListJoin();
		$headOverride = new ListHeadOverride();
		$fieldRelations = new FieldRelations();
		$item = (object) ['addrelations' => '{invalid'];

		(new Relations(
			$config,
			new Language($config),
			$customcode,
			$listJoin,
			$headOverride,
			$fieldRelations
		))->set($item);

		$this->assertSame([], $listJoin->allActive());
		$this->assertSame([], $headOverride->allActive());
		$this->assertSame([], $fieldRelations->allActive());
		$this->assertObjectNotHasProperty('addrelations', $item);
	}
}
