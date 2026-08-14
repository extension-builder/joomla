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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Language;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Abstraction\Registry\Traits\IsArray;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitchList;
use VDM\Joomla\Componentbuilder\Compiler\Builder\MetaData;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Language\Fieldset;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * View fieldset metadata, access, and generated language-catalog contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Fieldset::class)]
#[UsesClass(Language::class)]
#[UsesClass(Config::class)]
#[UsesClass(MetaData::class)]
#[UsesClass(AccessSwitch::class)]
#[UsesClass(AccessSwitchList::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesTrait(IsArray::class)]
final class FieldsetTest extends CompilerDomainTestCase
{
	/**
	 * Enable metadata and both single/list access registries while building defaults.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetBuildsMetadataAccessAndCompleteViewLanguageDefaults(): void
	{
		[$subject, $language, $metadata, $access, $accessList] = $this->subject();

		$subject->set(
			true,
			true,
			'admin',
			'COM_EXAMPLE_ARTICLE',
			'COM_EXAMPLE_ARTICLES',
			'Article',
			'Articles',
			'article',
			'articles'
		);

		$this->assertSame('articles', $metadata->get('article'));
		$this->assertTrue($access->get('article'));
		$this->assertTrue($accessList->get('articles'));
		$this->assertSame('Article', $language->get('admin', 'COM_EXAMPLE_ARTICLE'));
		$this->assertSame('Articles', $language->get('admin', 'COM_EXAMPLE_ARTICLES'));
		$this->assertSame(
			'%s Articles published.',
			$language->get('admin', 'COM_EXAMPLE_ARTICLES_N_ITEMS_PUBLISHED')
		);
		$this->assertSame(
			'Another Article has the same alias.',
			$language->get('admin', 'COM_EXAMPLE_ARTICLE_ERROR_UNIQUE_ALIAS')
		);
		$this->assertSame(
			'Created By',
			$language->get('admin', 'COM_EXAMPLE_ARTICLE_CREATED_BY_LABEL')
		);
		$this->assertGreaterThanOrEqual(30, count($language->getTarget('admin')));
	}

	/**
	 * Leave optional registries untouched while still installing language defaults.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetHonorsDisabledMetadataAndAccessSwitches(): void
	{
		[$subject, $language, $metadata, $access, $accessList] = $this->subject();

		$subject->set(
			false,
			false,
			'site',
			'COM_EXAMPLE_ITEM',
			'COM_EXAMPLE_ITEMS',
			'Item',
			'Items',
			'item',
			'items'
		);

		$this->assertFalse($metadata->isActive());
		$this->assertFalse($access->isActive());
		$this->assertFalse($accessList->isActive());
		$this->assertSame('Item', $language->get('site', 'COM_EXAMPLE_ITEM'));
		$this->assertSame(
			'All changes will be applied to all selected Items',
			$language->get('site', 'COM_EXAMPLE_ITEMS_BATCH_TIP')
		);
	}

	/**
	 * Create fieldset collaborators with independently observable state.
	 *
	 * @return  array{Fieldset, Language, MetaData, AccessSwitch, AccessSwitchList}
	 * @since   6.1.6
	 */
	private function subject(): array
	{
		$language = new Language($this->compilerConfig([
			'lang_prefix' => 'COM_EXAMPLE',
			'remove_line_breaks' => false
		]));
		$metadata = new MetaData();
		$access = new AccessSwitch();
		$accessList = new AccessSwitchList();

		return [
			new Fieldset($language, $metadata, $access, $accessList),
			$language,
			$metadata,
			$access,
			$accessList
		];
	}
}
