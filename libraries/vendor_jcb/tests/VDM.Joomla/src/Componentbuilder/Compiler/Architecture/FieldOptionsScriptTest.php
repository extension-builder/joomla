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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\OptionsScript;


/**
 * Watched field option reading contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class FieldOptionsScriptTest extends ArchitectureTestCase
{
	/**
	 * Read the options one field declares.
	 *
	 * @param   mixed   $type     The field type.
	 * @param   mixed   $options  The options the field declares.
	 *
	 * @return  array  The bucket the condition test is built from.
	 * @since   6.1.7
	 */
	private function read($type, $options): array
	{
		return $this->renderer(OptionsScript::class)->get($type, $options);
	}

	/**
	 * A selection field yields the value half of every option line.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASelectionFieldYieldsTheValueHalfOfEveryOption(): void
	{
		$this->assertSame(
			['one', 'two'],
			$this->read('list', "one" . PHP_EOL . "two|Two")
		);
	}

	/**
	 * The dynamic list marker is a placeholder, not an option to compare against.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheDynamicListMarkerIsNotAnOption(): void
	{
		$this->assertSame(
			['one'],
			$this->read('list', "one" . PHP_EOL . "dynamic_list")
		);
	}

	/**
	 * The types read as a selection.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function selectionTypes(): array
	{
		return [
			'list' => ['list'],
			'radio' => ['radio'],
			'dynamic' => ['category'],
			'in no group at all' => ['acme_custom'],
		];
	}

	/**
	 * Every selection shape reads its options the same way.
	 *
	 * @param   string  $type  The field type.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('selectionTypes')]
	public function testEverySelectionShapeReadsItsOptionsTheSameWay(string $type): void
	{
		$this->assertSame(
			['1', '0'],
			$this->read($type, "1|Yes" . PHP_EOL . "0|No")
		);
	}

	/**
	 * A text field yields the keywords and the length its options name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATextFieldYieldsItsKeywordsAndLength(): void
	{
		$this->assertSame(
			['keywords' => ['red', 'blue'], 'length' => '7'],
			$this->read('text', 'keywords="red,blue" length="7"')
		);
	}

	/**
	 * One keyword is still a keyword list.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOneKeywordIsStillAKeywordList(): void
	{
		$this->assertSame(
			['keywords' => ['red'], 'length' => false],
			$this->read('text', 'keywords="red"')
		);
	}

	/**
	 * A text field that names no length reports that, rather than omitting it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATextFieldThatNamesNoLengthReportsIt(): void
	{
		$this->assertSame(
			['length' => false],
			$this->read('text', 'something="else"')
		);
	}

	/**
	 * A length may be named without any keywords.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testALengthMayBeNamedWithoutKeywords(): void
	{
		$this->assertSame(
			['length' => '12'],
			$this->read('text', 'length="12"')
		);
	}

	/**
	 * The shapes that declare nothing to read.
	 *
	 * @return  array<string, array{string,mixed}>
	 * @since   6.1.7
	 */
	public static function quietShapes(): array
	{
		return [
			'no options' => ['list', ''],
			'null options' => ['list', null],
			// a note is neither a selection nor a text field
			'a type that reads neither way' => ['note', "one" . PHP_EOL . "two"],
		];
	}

	/**
	 * Nothing to read yields an empty bucket rather than a complaint.
	 *
	 * @param   string  $type     The field type.
	 * @param   mixed   $options  The options the field declares.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('quietShapes')]
	public function testNothingToReadYieldsAnEmptyBucket(string $type, $options): void
	{
		$this->assertSame([], $this->read($type, $options));
	}
}
