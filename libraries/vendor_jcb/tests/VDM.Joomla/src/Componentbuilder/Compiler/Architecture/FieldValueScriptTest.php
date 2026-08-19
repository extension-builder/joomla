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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\ValueScript;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ScriptUserSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;


/**
 * Watched field value read contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class FieldValueScriptTest extends ArchitectureTestCase
{
	/**
	 * Read one field the way a form condition watches it.
	 *
	 * @param   string  $type     The field type.
	 * @param   string  $extends  The type a custom field extends.
	 *
	 * @return  array  The read statement and its array flag.
	 * @since   6.1.7
	 */
	private function read(string $type, string $extends = ''): array
	{
		Indent::_(1);

		return $this->renderer(ValueScript::class)
			->get($type, 'colours', $extends, 'abc1234');
	}

	/**
	 * The types whose value is read by one jQuery call.
	 *
	 * @return  array<string, array{string,string,bool}>
	 * @since   6.1.7
	 */
	public static function singleReads(): array
	{
		return [
			'checkbox' => ['checkbox',
				'var colours_abc1234 = jQuery("#jform_colours").prop(\'checked\');', false],
			'radio' => ['radio',
				'var colours_abc1234 = jQuery("#jform_colours input[type=\'radio\']:checked").val();', false],
			// a list, a dynamic type and a type in no group all read the same way
			'list' => ['list',
				'var colours_abc1234 = jQuery("#jform_colours").val();', true],
			'dynamic' => ['category',
				'var colours_abc1234 = jQuery("#jform_colours").val();', true],
			'in no group at all' => ['acme_custom',
				'var colours_abc1234 = jQuery("#jform_colours").val();', true],
			// a text field reads the same way but never yields an array
			'text' => ['text',
				'var colours_abc1234 = jQuery("#jform_colours").val();', false],
		];
	}

	/**
	 * Each field type is read by the call that suits it.
	 *
	 * @param   string  $type      The field type.
	 * @param   string  $expected  The statement it must build.
	 * @param   bool    $isArray   Whether the value arrives as an array.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('singleReads')]
	public function testEachFieldTypeIsReadByTheCallThatSuitsIt(string $type,
		string $expected, bool $isArray): void
	{
		$read = $this->read($type);

		$this->assertSame($expected, $read['get']);
		$this->assertSame($isArray, $read['isArray']);
	}

	/**
	 * Checkboxes are collected one by one into an array.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCheckboxesAreCollectedIntoAnArray(): void
	{
		$read = $this->read('checkboxes');

		$this->assertSame(
			"var colours_abc1234 = [];" . PHP_EOL
			. "\tjQuery('#jform_colours input[type=checkbox]').each(function()" . PHP_EOL
			. "\t{" . PHP_EOL
			. "\t\tif (jQuery(this).is(':checked'))" . PHP_EOL
			. "\t\t{" . PHP_EOL
			. "\t\t\tcolours_abc1234.push(jQuery(this).prop('value'));" . PHP_EOL
			. "\t\t}" . PHP_EOL
			. "\t});",
			$read['get']
		);
		$this->assertTrue($read['isArray']);
	}

	/**
	 * A custom field is read as the checkboxes it extends, not as its own type.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomFieldIsReadAsTheTypeItExtends(): void
	{
		$this->assertSame(
			$this->read('checkboxes'),
			$this->read('acme_custom', 'checkboxes')
		);
	}

	/**
	 * A user field is read from the identifier input beside it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAUserFieldIsReadFromTheIdentifierInput(): void
	{
		// the switch holds the field types whose value lives in a _id input
		$switch = new ScriptUserSwitch();
		$switch->set('a_field', 'user');

		$this->assertSame(
			[
				'get' => 'var colours_abc1234 = jQuery("#jform_colours_id").val();',
				'isArray' => false,
			],
			$this->renderer(ValueScript::class, ['scriptuserswitch' => $switch])
				->get('user', 'colours', '', 'abc1234')
		);
	}

	/**
	 * A known type that is neither a selection nor text is read by nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAKnownTypeThatIsNeitherSelectionNorTextIsReadByNothing(): void
	{
		// a note is in the default group, so it is neither unknown nor readable
		$this->assertSame(['get' => '', 'isArray' => false], $this->read('note'));
	}
}
