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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\SelectionTranslationMethod;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SelectionTranslation as SelectionTranslationData;


/**
 * Model selection translation method contracts.
 *
 * @since  6.1.7
 */
#[CoversClass(SelectionTranslationMethod::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ModelSelectionTranslationMethodTest extends ArchitectureTestCase
{
	/**
	 * A view with no translatable selections generates no method.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutSelectionsGeneratesNoMethod(): void
	{
		$subject = new SelectionTranslationMethod(new SelectionTranslationData());

		$this->assertSame('', $subject->get('articles'));
	}

	/**
	 * Each translatable field contributes its own guarded lookup array.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEachFieldGetsItsOwnGuardedLookupArray(): void
	{
		$code = $this->subject()->get('articles');

		$this->assertStringContainsString(
			'public function selectionTranslation($value,$name)',
			$code
		);
		$this->assertStringContainsString("if (\$name === 'status')", $code);
		$this->assertStringContainsString("if (\$name === 'kind')", $code);
		$this->assertStringContainsString('$statusArray = array(', $code);
		$this->assertStringContainsString('$kindArray = array(', $code);
		// an unmatched value falls through unchanged
		$this->assertStringContainsString('return $value;', $code);
	}

	/**
	 * String keys are quoted and numeric keys are not.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testStringKeysAreQuotedAndNumericKeysAreNot(): void
	{
		$data = new SelectionTranslationData();
		$data->set('articles', [
			'status' => [
				'draft' => 'Draft',
				1 => 'Published',
			],
		]);

		$code = (new SelectionTranslationMethod($data))->get('articles');

		$this->assertStringContainsString("'draft' => 'Draft'", $code);
		$this->assertStringContainsString("1 => 'Published'", $code);
		$this->assertStringNotContainsString("'1' => 'Published'", $code);
	}

	/**
	 * An empty key becomes zero rather than an empty lookup entry.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnEmptyKeyBecomesZero(): void
	{
		$data = new SelectionTranslationData();
		$data->set('articles', ['status' => ['' => 'None']]);

		$code = (new SelectionTranslationMethod($data))->get('articles');

		$this->assertStringContainsString("0 => 'None'", $code);
	}

	/**
	 * A field with no values contributes no branch at all.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFieldWithoutValuesContributesNoBranch(): void
	{
		$data = new SelectionTranslationData();
		$data->set('articles', ['status' => ['1' => 'Published'], 'empty' => []]);

		$code = (new SelectionTranslationMethod($data))->get('articles');

		$this->assertStringContainsString("if (\$name === 'status')", $code);
		$this->assertStringNotContainsString("if (\$name === 'empty')", $code);
	}

	/**
	 * Create the method builder with two translatable fields.
	 *
	 * @return  SelectionTranslationMethod
	 * @since   6.1.7
	 */
	private function subject(): SelectionTranslationMethod
	{
		$data = new SelectionTranslationData();
		$data->set('articles', [
			'status' => ['1' => 'Published'],
			'kind' => ['2' => 'Draft'],
		]);

		return new SelectionTranslationMethod($data);
	}
}
