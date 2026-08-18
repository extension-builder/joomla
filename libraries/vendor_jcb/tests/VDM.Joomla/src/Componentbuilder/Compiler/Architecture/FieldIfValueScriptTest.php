<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    18th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\IfValueScript;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ScriptUserSwitch;


/**
 * Generated form condition test contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class FieldIfValueScriptTest extends ArchitectureTestCase
{
	/**
	 * What each behaviour builds over a field that offers options.
	 *
	 * @return  array<string, array{int,string,string}>
	 * @since   6.1.7
	 */
	public static function selections(): array
	{
		return [
			'is' => [1, 'list', "value == 'one' || value == 2 || value == true"],
			'is not' => [2, 'list', "value != 'one' || value != 2 || value != true"],
			'any selection' => [3, 'list', "value == 'one' || value == 2 || value == true"],
			// a list behaviour over a text field tests nothing
			'is, over text' => [1, 'text', '0'],
			'is not, over text' => [2, 'text', '0'],
		];
	}

	/**
	 * A selection behaviour compares the value against every option.
	 *
	 * @param   int     $behavior  The declared behaviour.
	 * @param   string  $type      The field type.
	 * @param   string  $expected  The test it must build.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('selections')]
	public function testASelectionBehaviourComparesEveryOption(int $behavior,
		string $type, string $expected): void
	{
		$this->assertSame($expected, (string) $this->build(
			$behavior, $type, ['one', '2', 'true']
		));
	}

	/**
	 * Only an option that is neither numeric nor a boolean word is quoted.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOnlyAnOptionThatNeedsQuotingGetsIt(): void
	{
		$this->assertSame(
			"value == 'red' || value == 7 || value == 1.5"
			. " || value == true || value == false",
			$this->build(1, 'list', ['red', '7', '1.5', 'true', 'false'])
		);
	}

	/**
	 * What each behaviour builds over a field that offers no options.
	 *
	 * @return  array<string, array{int,string,string}>
	 * @since   6.1.7
	 */
	public static function presences(): array
	{
		return [
			'is' => [1, 'list', 'isSet(value)'],
			'is not' => [2, 'list', '!isSet(value)'],
			'any selection' => [3, 'list', 'isSet(value)'],
			'active' => [4, 'text', 'isSet(value)'],
			'unactive' => [5, 'text', '!isSet(value)'],
			// active and unactive only speak about a text field
			'active, over list' => [4, 'list', '0'],
			'unactive, over list' => [5, 'list', '0'],
		];
	}

	/**
	 * With nothing to compare against, a behaviour tests presence instead.
	 *
	 * @param   int     $behavior  The declared behaviour.
	 * @param   string  $type      The field type.
	 * @param   string  $expected  The test it must build.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('presences')]
	public function testWithNothingToCompareAgainstPresenceIsTested(int $behavior,
		string $type, string $expected): void
	{
		$this->assertSame($expected, (string) $this->build($behavior, $type, ''));
	}

	/**
	 * A user field carries the extra guard the user switch asks for.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAUserFieldCarriesTheGuardTheSwitchAsksFor(): void
	{
		// the switch holds the field types whose zero means unset
		$switch = new ScriptUserSwitch();
		$switch->set('a_field', 'user');

		$this->assertSame(
			'isSet(value) && value != 0',
			$this->renderer(IfValueScript::class, ['scriptuserswitch' => $switch])
				->get('value', 3, 'user', '')
		);
	}

	/**
	 * What each keyword behaviour builds.
	 *
	 * @return  array<string, array{int,string}>
	 * @since   6.1.7
	 */
	public static function keywords(): array
	{
		return [
			'all, case sensitive' => [6,
				'value.indexOf("red") >= 0 && value.indexOf("blue") >= 0'],
			'any, case sensitive' => [7,
				'value.indexOf("red") >= 0 || value.indexOf("blue") >= 0'],
			'all, case insensitive' => [8,
				'value.toLowerCase().indexOf("red") >= 0'
				. ' && value.toLowerCase().indexOf("blue") >= 0'],
			'any, case insensitive' => [9,
				'value.toLowerCase().indexOf("red") >= 0'
				. ' || value.toLowerCase().indexOf("blue") >= 0'],
		];
	}

	/**
	 * A keyword behaviour searches the value for every keyword.
	 *
	 * @param   int     $behavior  The declared behaviour.
	 * @param   string  $expected  The test it must build.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('keywords')]
	public function testAKeywordBehaviourSearchesForEveryKeyword(int $behavior,
		string $expected): void
	{
		$this->assertSame($expected, $this->build(
			$behavior, 'text', ['keywords' => ['red', 'blue']]
		));
	}

	/**
	 * A keyword behaviour only speaks about a text field.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAKeywordBehaviourOnlySpeaksAboutATextField(): void
	{
		$this->assertSame(0, $this->build(
			6, 'list', ['keywords' => ['red', 'blue']]
		));
	}

	/**
	 * What each length behaviour builds, and what it falls back to.
	 *
	 * @return  array<string, array{int,string,string}>
	 * @since   6.1.7
	 */
	public static function lengths(): array
	{
		return [
			'min' => [10, 'value.length >= 7', 'value.length >= 5'],
			'max' => [11, 'value.length <= 7', 'value.length <= 5'],
			'exact' => [12, 'value.length == 7', 'value.length == 5'],
		];
	}

	/**
	 * A length behaviour measures the value, and has a length of its own.
	 *
	 * @param   int     $behavior  The declared behaviour.
	 * @param   string  $given     The test built from the declared length.
	 * @param   string  $fallback  The test built when no length is declared.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('lengths')]
	public function testALengthBehaviourMeasuresTheValue(int $behavior,
		string $given, string $fallback): void
	{
		$this->assertSame($given, $this->build($behavior, 'text', ['length' => 7]));
		$this->assertSame($fallback, $this->build($behavior, 'text', ''));
	}

	/**
	 * A condition that tests nothing is the integer zero, not an empty string.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAConditionThatTestsNothingIsTheIntegerZero(): void
	{
		$this->assertSame(0, $this->build(1, 'text', ['one']));
		$this->assertSame(0, $this->build(99, 'list', ['one']));
	}

	/**
	 * Build one condition test.
	 *
	 * @param   int     $behavior  The declared behaviour.
	 * @param   string  $type      The field type.
	 * @param   mixed   $options   The options the field offers.
	 *
	 * @return  string|int
	 * @since   6.1.7
	 */
	private function build(int $behavior, string $type, $options)
	{
		return $this->renderer(IfValueScript::class)
			->get('value', $behavior, $type, $options);
	}
}
