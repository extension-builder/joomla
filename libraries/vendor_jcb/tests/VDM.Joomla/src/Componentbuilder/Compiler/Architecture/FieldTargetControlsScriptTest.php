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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\TargetControlsScript;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ValidationFix;


/**
 * Generated form target control contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class FieldTargetControlsScriptTest extends ArchitectureTestCase
{
	/**
	 * The fixes recorded by the subject built for the current test.
	 *
	 * @var    ValidationFix
	 * @since  6.1.7
	 */
	private ValidationFix $fix;

	/**
	 * How each kind of target is addressed in the generated jQuery.
	 *
	 * @return  array<string, array{string,string,string}>
	 * @since   6.1.7
	 */
	public static function selectors(): array
	{
		return [
			// a field is addressed by its id
			'text' => ['plain', 'text', '#jform_plain'],
			// a note or spacer has no id, so its class is addressed
			'spacer' => ['note', 'spacer', '.note'],
			// an editor has no reachable id, so its label is addressed
			'editor' => ['body', 'editor', '#jform_body-lbl'],
			'subform' => ['rows', 'subform', '#jform_rows-lbl'],
		];
	}

	/**
	 * Each kind of target is addressed the way that kind can be reached.
	 *
	 * @param   string  $name      The target field name.
	 * @param   string  $type      The target field type.
	 * @param   string  $selector  The jQuery selector it must resolve to.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('selectors')]
	public function testEachKindOfTargetIsAddressedByWhatItHas(string $name,
		string $type, string $selector): void
	{
		$bucket = $this->build([
			['name' => $name, 'type' => $type, 'required' => 'no']
		]);

		$this->assertSame(
			PHP_EOL . "\t\tjQuery('" . $selector . "').closest('.control-group').show();",
			$bucket[$name]['behavior']
		);
		$this->assertSame(
			PHP_EOL . "\t\tjQuery('" . $selector . "').closest('.control-group').hide();",
			$bucket[$name]['default']
		);
	}

	/**
	 * A target that is not required carries no required handling at all.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATargetThatIsNotRequiredCarriesNoRequiredHandling(): void
	{
		$bucket = $this->build([
			['name' => 'plain', 'type' => 'text', 'required' => 'no']
		]);

		$this->assertSame('', $bucket['plain']['requiredVar']);
		$this->assertSame('', $bucket['plain']['hide']);
		$this->assertSame('', $bucket['plain']['show']);
		$this->assertSame([], $this->fix->allActive());
	}

	/**
	 * A required target declares its own flag and drops the attribute on hide.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testARequiredTargetDeclaresItsFlagAndDropsTheAttribute(): void
	{
		$bucket = $this->build([
			['name' => 'needed', 'type' => 'text', 'required' => 'yes']
		]);
		$flag = $this->flag($bucket['needed']['requiredVar']);

		$this->assertSame(
			'jform_' . $flag . '_required = false;' . PHP_EOL,
			$bucket['needed']['requiredVar']
		);
		$this->assertStringContainsString("updateFieldRequired('needed',1);", $bucket['needed']['hide']);
		$this->assertStringContainsString("jQuery('#jform_needed').removeAttr('required');", $bucket['needed']['hide']);
		$this->assertStringContainsString("jQuery('#jform_needed').removeAttr('aria-required');", $bucket['needed']['hide']);
		$this->assertStringContainsString("jQuery('#jform_needed').removeClass('required');", $bucket['needed']['hide']);
		$this->assertStringContainsString('jform_' . $flag . '_required = true;', $bucket['needed']['hide']);
	}

	/**
	 * The show side puts back exactly what the hide side took away.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheShowSidePutsBackWhatHideTookAway(): void
	{
		$bucket = $this->build([
			['name' => 'needed', 'type' => 'text', 'required' => 'yes']
		]);
		$flag = $this->flag($bucket['needed']['requiredVar']);

		$this->assertStringContainsString("updateFieldRequired('needed',0);", $bucket['needed']['show']);
		$this->assertStringContainsString("jQuery('#jform_needed').prop('required','required');", $bucket['needed']['show']);
		$this->assertStringContainsString("jQuery('#jform_needed').attr('aria-required',true);", $bucket['needed']['show']);
		$this->assertStringContainsString("jQuery('#jform_needed').addClass('required');", $bucket['needed']['show']);
		$this->assertStringContainsString('jform_' . $flag . '_required = false;', $bucket['needed']['show']);
	}

	/**
	 * A toggling condition guards the required handling on the flag.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATogglingConditionGuardsOnTheFlag(): void
	{
		$targets = [['name' => 'needed', 'type' => 'text', 'required' => 'yes']];
		$toggled = $this->build($targets, true);
		$flag = $this->flag($toggled['needed']['requiredVar']);

		$this->assertStringContainsString('if (!jform_' . $flag . '_required)', $toggled['needed']['hide']);
		$this->assertStringContainsString('if (jform_' . $flag . '_required)', $toggled['needed']['show']);

		$plain = $this->build($targets, false);
		$plainFlag = $this->flag($plain['needed']['requiredVar']);

		$this->assertStringNotContainsString('if (!jform_' . $plainFlag . '_required)', $plain['needed']['hide']);
		$this->assertStringNotContainsString('if (jform_' . $plainFlag . '_required)', $plain['needed']['show']);
	}

	/**
	 * Every required target is recorded for the form validation override.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryRequiredTargetIsRecordedForTheValidationOverride(): void
	{
		$this->build([
			['name' => 'needed', 'type' => 'text', 'required' => 'yes'],
			['name' => 'plain', 'type' => 'text', 'required' => 'no'],
			['name' => 'also', 'type' => 'text', 'required' => 'yes'],
		]);

		$this->assertSame(['article' => ['needed', 'also']], $this->fix->allActive());
	}

	/**
	 * A condition already built is not built a second time.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAConditionAlreadyBuiltIsNotBuiltAgain(): void
	{
		$targets = [['name' => 'needed', 'type' => 'text', 'required' => 'yes']];
		$subject = $this->subject();

		$this->assertNotSame([], $subject->get(true, $targets, 'show', 'hide', 'aaa', 'article'));
		$this->assertSame([], $subject->get(true, $targets, 'show', 'hide', 'aaa', 'article'));
		$this->assertNotSame([], $subject->get(true, $targets, 'show', 'hide', 'bbb', 'article'));
	}

	/**
	 * Targets the compiler cannot read produce nothing, quietly.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTargetsThatCannotBeReadProduceNothing(): void
	{
		$subject = $this->subject();

		$this->assertSame([], $subject->get(true, 'not an array', 'show', 'hide', 'aaa', 'article'));
		$this->assertSame([], $subject->get(true, [], 'show', 'hide', 'bbb', 'article'));
		$this->assertSame([], $subject->get(true, ['not an array'], 'show', 'hide', 'ccc', 'article'));
		$this->assertSame([], $this->fix->allActive());
	}

	/**
	 * Read the unique flag out of the generated declaration.
	 *
	 * @param   string  $requiredVar  The generated flag declaration.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function flag(string $requiredVar): string
	{
		$this->assertSame(1, preg_match('~^jform_(\w+)_required~', $requiredVar, $match));

		return $match[1];
	}

	/**
	 * Build a subject that records into this test's fix registry.
	 *
	 * @return  TargetControlsScript
	 * @since   6.1.7
	 */
	private function subject(): TargetControlsScript
	{
		$this->fix = new ValidationFix();

		return $this->renderer(TargetControlsScript::class, [
			'validationfix' => $this->fix,
		]);
	}

	/**
	 * Build the controls for one set of targets.
	 *
	 * @param   array  $targets       The target fields.
	 * @param   bool   $toggleSwitch  Whether the required attribute toggles.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function build(array $targets, bool $toggleSwitch = true): array
	{
		return $this->subject()->get(
			$toggleSwitch, $targets, 'show', 'hide', 'aaa', 'article'
		);
	}
}
