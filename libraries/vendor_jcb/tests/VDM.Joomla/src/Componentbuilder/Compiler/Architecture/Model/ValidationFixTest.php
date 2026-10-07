<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    7th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Model;


use Closure;
use RuntimeException;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Language;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\ValidationFix;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ValidationFix as ValidationFixRegistry;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Execute emitted conditional requirements against Joomla's real Form lifecycle.
 *
 * @since  6.2.0
 */
#[CoversClass(ValidationFix::class)]
#[CoversClass(ValidationFixRegistry::class)]
final class ValidationFixTest extends ArchitectureTestCase
{
	/**
	 * The language instance restored after each fixture.
	 *
	 * @var    mixed
	 * @since  6.2.0
	 */
	private $language;

	/**
	 * Install a local language without booting an application or database.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->language = Factory::$language;
		Factory::$language = new Language('en-GB');
	}

	/**
	 * Restore the process language.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function tearDown(): void
	{
		Factory::$language = $this->language;
		parent::tearDown();
	}

	/**
	 * Name-only updates evaluate stored selectors and preserve inactive code.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testPartialUpdatesUseStoredSettingsAndPreserveInactiveValues(): void
	{
		$validate = $this->validator([self::group()], ['id' => 9, 'mode' => 'plain']);
		$form = $this->form();
		$data = ['id' => 9, 'name' => 'Renamed', 'details' => 'original decoded code'];

		$this->assertSame($data, $validate($form, $data));
		$this->assertSame('false', $form->getFieldAttribute('details', 'required'));
		$this->assertSame($data, $validate($form, $data));
	}

	/**
	 * A forged browser list cannot suppress an active requirement.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testClientCannotBypassActiveRequirements(): void
	{
		$validate = $this->validator([self::group()], ['id' => 9, 'mode' => 'expert']);
		$form = $this->form();

		$this->assertFalse($validate($form, ['id' => 9, 'name' => 'Renamed', 'not_required' => 'details,name']));
		$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
		$this->assertSame('true', $form->getFieldAttribute('name', 'required'));
	}

	/**
	 * Protected selectors cannot bypass requirements with values Joomla will discard.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testDeniedAndRemovedSelectorsUseStoredValues(): void
	{
		$validate = $this->validator([self::group()], ['id' => 9, 'mode' => 'expert']);
		foreach (['disabled', 'unset', 'removed'] as $protection)
		{
			$form = $this->form();
			if ($protection === 'removed')
			{
				$form->removeField('mode');
			}
			else
			{
				$form->setFieldAttribute('mode', $protection === 'disabled' ? 'disabled' : 'filter', $protection === 'disabled' ? 'true' : 'unset');
			}
			try
			{
				$this->assertFalse($validate($form, ['id' => 9, 'name' => 'Attempt', 'mode' => 'plain']));
			}
			catch (RuntimeException $error)
			{
				$this->assertSame('disabled', $protection);
				$this->assertSame('JLIB_FORM_VALIDATE_FIELD_INVALID', $error->getMessage());
			}
			$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
		}
	}

	/**
	 * Unknown conditions keep required fields strict rather than allowing a bypass.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testUnsupportedConditionRetainsRequiredValidation(): void
	{
		$group = self::group();
		$group['matches'] = [self::rule(['supported' => false])];
		$validate = $this->validator([$group], []);
		$this->assertFalse($validate($this->form(), ['name' => 'Attempt', 'mode' => 'plain']));
	}

	/**
	 * Incoming mode changes override stored mode without clearing inactive values.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testIncomingSettingsActivateAndDeactivateRequirements(): void
	{
		$validate = $this->validator([self::group()], ['id' => 9, 'mode' => 'plain']);
		$form = $this->form();

		$this->assertFalse($validate($form, ['id' => 9, 'name' => 'One', 'mode' => 'expert']));
		$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
		$data = ['id' => 9, 'name' => 'Two', 'mode' => 'plain', 'details' => 'keep this'];
		$this->assertSame($data, $validate($form, $data));
		$this->assertSame('false', $form->getFieldAttribute('details', 'required'));
	}

	/**
	 * Failed stored-record loading cannot turn required fields into optional ones.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFailedExistingRecordLoadFailsClosed(): void
	{
		$validate = $this->validator([self::group()], false);
		$form = $this->form();
		$this->assertFalse($validate($form, ['id' => 9, 'name' => 'Attempt', 'mode' => 'plain']));
		$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
	}

	/**
	 * Create defaults are evaluated without looking up an unrelated stored row.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testCreateUsesFormDefaultsAndStillValidatesOrdinaryFields(): void
	{
		$validate = $this->validator([self::group()], []);
		$form = $this->form();

		$this->assertSame(['name' => 'Created'], $validate($form, ['name' => 'Created']));
		$this->assertFalse($validate($form, ['not_required' => 'name']));
	}

	/**
	 * Related conditions use AND and multiselects use the browser's some semantics.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testRelationsAndMultipleSelections(): void
	{
		$group = self::group();
		$group['matches'][] = self::rule(['name' => 'level', 'options' => ['high']]);
		$validate = $this->validator([$group], []);
		$form = $this->form();

		$data = ['name' => 'One', 'mode' => ['plain', 'expert'], 'level' => ['low']];
		$this->assertSame($data, $validate($form, $data));
		$this->assertSame('false', $form->getFieldAttribute('details', 'required'));
		$this->assertFalse($validate($form, array_replace($data, ['level' => ['low', 'high']])));
	}

	/**
	 * All existing matcher behaviors retain their browser interpretation.
	 *
	 * @param   array  $rule      The normalized match rule.
	 * @param   mixed  $value     Submitted selector value.
	 * @param   bool   $required  Whether the target is required.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('matchCases')]
	public function testMatchBehavior(array $rule, $value, bool $required): void
	{
		$group = self::group();
		$group['matches'] = [self::rule($rule)];
		$validate = $this->validator([$group], []);
		$form = $this->form();
		$validate($form, ['name' => 'Record', 'mode' => $value, 'details' => 'saved']);

		$this->assertSame($required ? 'true' : 'false', $form->getFieldAttribute('details', 'required'));
	}

	/**
	 * Reviewed match cases, including historical Is Not OR behavior.
	 *
	 * @return  array
	 * @since   6.2.0
	 */
	public static function matchCases(): array
	{
		return [
			'numeric radio string' => [['options' => ['6'], 'array' => false], '6', true],
			'numeric radio integer' => [['options' => ['6'], 'array' => false], 6, true],
			'zero is present' => [['behavior' => 3, 'options' => []], '0', true],
			'empty selection' => [['behavior' => 3, 'options' => []], [], false],
			'unselected user' => [['behavior' => 3, 'options' => [], 'user' => true], '0', false],
			'is not one' => [['behavior' => 2, 'options' => ['expert']], 'plain', true],
			'is not multiple keeps OR' => [['behavior' => 2, 'options' => ['expert', 'plain']], 'expert', true],
			'checkbox true' => [['options' => ['true'], 'checkbox' => true, 'array' => false], 1, true],
			'checkbox false' => [['options' => ['false'], 'checkbox' => true, 'array' => false], 0, true],
			'text active' => [['behavior' => 4, 'options' => [], 'array' => false], '0', true],
			'text empty' => [['behavior' => 5, 'options' => [], 'array' => false], '', true],
			'all keywords' => [['behavior' => 6, 'options' => ['keywords' => ['red', 'blue']], 'array' => false], 'red blue', true],
			'all missing keyword' => [['behavior' => 6, 'options' => ['keywords' => ['red', 'blue']], 'array' => false], 'red', false],
			'any keyword' => [['behavior' => 7, 'options' => ['keywords' => ['red', 'blue']], 'array' => false], 'blue', true],
			'insensitive all' => [['behavior' => 8, 'options' => ['keywords' => ['red', 'blue']], 'array' => false], 'RED BLUE', true],
			'insensitive any' => [['behavior' => 9, 'options' => ['keywords' => ['red', 'blue']], 'array' => false], 'BLUE', true],
			'minimum length' => [['behavior' => 10, 'options' => ['length' => 3], 'array' => false], 'abc', true],
			'maximum length' => [['behavior' => 11, 'options' => ['length' => 3], 'array' => false], 'abcd', false],
			'exact unicode length' => [['behavior' => 12, 'options' => ['length' => 2], 'array' => false], '😀', true],
			'quoted option stays data' => [['options' => ["x'\\\\y"]], "x'\\\\y", true],
		];
	}

	/**
	 * Hide-only and show-only definitions keep their declared one-way behavior.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testHideAndOneWayGroups(): void
	{
		$group = self::group();
		$group['show'] = false;
		$group['toggle'] = false;
		$validate = $this->validator([$group], []);
		$form = $this->form();

		$this->assertSame(['name' => 'Hidden', 'mode' => 'expert'], $validate($form, ['name' => 'Hidden', 'mode' => 'expert']));
		$this->assertSame('false', $form->getFieldAttribute('details', 'required'));
		$this->assertFalse($validate($form, ['name' => 'Visible', 'mode' => 'plain']));
	}

	/**
	 * Build the emitted validate method and replace only the parent lifecycle boundary.
	 *
	 * @param   array  $groups  Normalized condition groups.
	 * @param   array|false  $stored  Decoded existing form values or a failed load.
	 *
	 * @return  Closure
	 * @since   6.2.0
	 */
	private function validator(array $groups, array|false $stored): Closure
	{
		$registry = new ValidationFixRegistry();
		$registry->set('record', ['details']);
		$registry->setConditions('record', $groups);
		$method = (new ValidationFix($registry))->get('record', 'Demo');
		$this->assertStringContainsString('return parent::validate($form, $data, $group);', $method);
		$this->assertStringNotContainsString('->filter(', $method);
		$method = substr($method, strpos($method, 'public function validate'));
		$method = str_replace('public function validate', 'function', $method);
		$method = str_replace('parent::validate($form, $data, $group)', '$form->process($data, $group)', $method);
		$validate = eval('use Joomla\\String\\StringHelper; return ' . $method . ';');
		$record = new class($stored)
		{
			/**
			 * Decoded model state supplied at the persistence boundary.
			 *
			 * @var    array
			 * @since  6.2.0
			 */
			private array|false $stored;

			/**
			 * @param   array  $stored  Decoded model state.
			 * @since   6.2.0
			 */
			public function __construct(array|false $stored)
			{
				$this->stored = $stored;
			}

			/**
			 * @param   int  $id  Requested record.
			 * @return  object
			 * @since   6.2.0
			 */
			public function getItem(int $id): object|false
			{
				return $this->stored === false ? false : (object) $this->stored;
			}

			/**
			 * @return  string
			 * @since   6.2.0
			 */
			public function getName(): string
			{
				return 'record';
			}

			/**
			 * @param   string  $name     State name.
			 * @param   mixed   $default  Default value.
			 * @return  mixed
			 * @since   6.2.0
			 */
			public function getState(string $name, $default)
			{
				return $default;
			}
		};

		return $validate->bindTo($record, $record);
	}

	/**
	 * Build a real Joomla form with persistence and user boundaries isolated.
	 *
	 * @return  Form
	 * @since   6.2.0
	 */
	private function form(): Form
	{
		$form = new Form('conditional');
		$form->setDatabase($this->createStub(DatabaseInterface::class));
		$form->setCurrentUser(new User());
		$form->load('<form>'
			. '<field name="id" type="text" filter="int" />'
			. '<field name="name" type="text" required="true" filter="raw" />'
			. '<field name="mode" type="text" default="plain" filter="raw" />'
			. '<field name="level" type="text" filter="raw" />'
			. '<field name="details" type="text" required="true" filter="raw" />'
			. '<field name="not_required" type="hidden" filter="raw" />'
			. '</form>');

		return $form;
	}

	/**
	 * @return  array
	 * @since   6.2.0
	 */
	private static function group(): array
	{
		return ['matches' => [self::rule()], 'targets' => ['details'], 'show' => true, 'toggle' => true];
	}

	/**
	 * @param   array  $overrides  Modified matcher properties.
	 * @return  array
	 * @since   6.2.0
	 */
	private static function rule(array $overrides = []): array
	{
		return $overrides + [
			'name' => 'mode', 'behavior' => 1, 'options' => ['expert'],
			'array' => true, 'user' => false, 'checkbox' => false, 'supported' => true,
		];
	}
}
