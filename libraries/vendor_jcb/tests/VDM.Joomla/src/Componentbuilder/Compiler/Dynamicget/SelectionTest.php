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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Dynamicget;


use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\GetAsLookup;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFields;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\Selection;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Dynamic-get SQL selection and alias-index contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Selection::class)]
#[UsesClass(GetAsLookup::class)]
#[UsesClass(SiteFields::class)]
final class SelectionTest extends CompilerDomainTestCase
{
	/**
	 * Explicit database selections preserve source/alias pairs and populate lookup state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDatabaseSelectionBuildsQuoteNameArraysAndAliasLookup(): void
	{
		$db = $this->createStub(DatabaseInterface::class);
		$db->method('quote')->willReturnCallback(
			static fn($value): string => "'" . $value . "'"
		);
		$lookup = new GetAsLookup();
		$subject = new Selection(
			$this->compilerConfig(['component_code_name' => 'demo']),
			$lookup,
			new SiteFields(),
			$db
		);

		$result = $subject->get(
			'main',
			'articles',
			"a.id AS article_id\na.title AS title",
			'content',
			'a',
			'db'
		);

		$this->assertSame('#__content', $result['table']);
		$this->assertSame('content', $result['name']);
		$this->assertSame('db', $result['type']);
		$this->assertSame(["'a.id'", "'a.title'"], $result['select_gets']);
		$this->assertSame(["'article_id'", "'title'"], $result['select_keys']);
		$this->assertStringContainsString("array('a.id','a.title')", str_replace(PHP_EOL . "\t\t\t", '', $result['select']));
		$this->assertSame('article_id', $lookup->get('main.a.id'));
		$this->assertSame('title', $lookup->get('main.a.title'));
		$this->assertSame("\$db->quoteName('#__content', 'a')", $result['from']);
	}

	/**
	 * Empty selections and unsupported source types are rejected explicitly.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSelectionRejectsEmptyAndUnknownSourceTypes(): void
	{
		$subject = new Selection(
			$this->compilerConfig(),
			new GetAsLookup(),
			new SiteFields(),
			$this->createStub(DatabaseInterface::class)
		);

		$this->assertNull($subject->get('main', 'articles', '', 'content', 'a', 'db'));
		$this->assertNull($subject->get('main', 'articles', 'a.id', 'content', 'a', 'remote'));
	}

	/**
	 * A source column without an explicit alias must use its column name without a deprecation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testUnaliasedSelectionUsesColumnNameWithoutNullTrimDeprecation(): void
	{
		$db = $this->createStub(DatabaseInterface::class);
		$db->method('quote')->willReturnCallback(
			static fn($value): string => "'" . $value . "'"
		);
		$lookup = new GetAsLookup();
		$subject = new Selection($this->compilerConfig(), $lookup, new SiteFields(), $db);

		$result = $subject->get('main', 'articles', 'a.title', 'content', 'a', 'db');

		$this->assertSame(["'title'"], $result['select_keys']);
		$this->assertSame('title', $lookup->get('main.a.title'));
	}
}
