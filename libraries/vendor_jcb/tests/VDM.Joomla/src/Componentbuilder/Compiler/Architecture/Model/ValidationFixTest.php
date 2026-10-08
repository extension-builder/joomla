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
use ReflectionProperty;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Model\BeforeValidateDataEvent;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\Dispatcher;
use Joomla\Input\Input;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\ConditionalRule;
use VDM\Joomla\Componentbuilder\Compiler\Registry as CompilerRegistry;
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
#[CoversClass(ConditionalRule::class)]
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
	 * Process globals restored after native lifecycle tests.
	 *
	 * @var    array
	 * @since  6.2.0
	 */
	private array $globals = [];


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
		$this->globals['application'] = Factory::$application;
		foreach ([[PluginHelper::class, 'plugins'], [FormHelper::class, 'prefixes']] as [$class, $property])
		{
			$reflection = new ReflectionProperty($class, $property);
			$this->globals[$class . ':' . $property] = [$reflection, $reflection->getValue()];
		}
		(new ReflectionProperty(PluginHelper::class, 'plugins'))->setValue(null, []);
		FormHelper::addRulePrefix(__NAMESPACE__);
		if (!class_exists(__NAMESPACE__ . '\\JcbconditionalrequiredRule', false))
		{
			eval('namespace ' . __NAMESPACE__ . '; use Joomla\\CMS\\Form\\Form; use Joomla\\Registry\\Registry; '
				. 'class JcbconditionalrequiredRule extends \\Joomla\\CMS\\Form\\FormRule {' . (new ConditionalRule())->get() . '}');
		}
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
		Factory::$application = $this->globals['application'];
		unset($this->globals['application']);
		foreach ($this->globals as [$reflection, $value])
		{
			$reflection->setValue(null, $value);
		}
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
		$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
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
		$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
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
		$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
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
		$form->setFieldAttribute('mode', 'default', '');
		$data = ['name' => 'Record', 'mode' => $value];
		$result = $validate($form, $data);

		if ($required)
		{
			$this->assertFalse($result);
		}
		else
		{
			$this->assertSame($data, $result);
		}
		$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
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
		$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
		$this->assertFalse($validate($form, ['name' => 'Visible', 'mode' => 'plain']));
	}

	/**
	 * The condition must inspect the INT value that Joomla actually validates.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFilteredSelectorCannotBypassRequiredTarget(): void
	{
		$group = self::group();
		$group['matches'] = [self::rule(['options' => ['6'], 'array' => false])];
		$validate = $this->validator([$group], []);
		$form = $this->form();
		$form->setFieldAttribute('mode', 'filter', 'int');

		$this->assertFalse($validate($form, ['name' => 'Record', 'mode' => '6garbage']));
		$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
		$this->assertSame(
			['name' => 'Record', 'mode' => 6, 'details' => 'present'],
			$validate($form, ['name' => 'Record', 'mode' => '6garbage', 'details' => 'present'])
		);
	}

	/**
	 * Group selectors follow Joomla's nested Registry paths.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testGroupedSelectorUsesNestedFilteredValue(): void
	{
		$validate = $this->validator([self::group()], []);
		$form = $this->form();
		$form->load('<form><fields name="settings">'
			. '<field name="mode" type="text" default="plain" filter="raw" />'
			. '<field name="details" type="text" required="true" filter="raw" />'
			. '</fields></form>');

		$this->assertFalse($validate($form, ['settings' => ['mode' => 'expert']], 'settings'));
		$this->assertSame('true', $form->getFieldAttribute('details', 'required', null, 'settings'));
		$this->assertSame(
			['settings' => ['mode' => 'plain']],
			$validate($form, ['settings' => ['mode' => 'plain']], 'settings')
		);
	}

	/**
	 * Native plugins change the actual form and submitted selector before filtering.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNativePluginChangesAreEvaluatedAndAttributesRestored(): void
	{
		$form = $this->form();
		$dispatcher = new Dispatcher();
		$calls = 0;
		$dispatcher->addListener('onContentBeforeValidateData', function (BeforeValidateDataEvent $event) use ($form, &$calls): void
		{
			$this->assertSame($form, $event->getForm());
			$calls++;
			$form->setFieldAttribute('details', 'required', 'false');
			$event->updateData(array_replace($event->getData(), ['mode' => 'expert']));
		});
		$validate = $this->validator([self::group()], [], true, $dispatcher);

		$this->assertFalse($validate($form, ['name' => 'Record', 'mode' => 'plain']));
		$this->assertSame(1, $calls);
		$this->assertSame('false', $form->getFieldAttribute('details', 'required'));
		$this->assertFalse($form->getFieldXml('__jcb_conditional_required'));
		$this->assertSame(
			['name' => 'Record', 'mode' => 'expert', 'details' => 'present'],
			$validate($form, ['name' => 'Record', 'details' => 'present'])
		);
		$this->assertSame(2, $calls);
	}

	/**
	 * Missing normal-form checkboxes mean unchecked; omitted PATCH values retain state.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testUncheckedAdministratorCheckboxDoesNotReuseStoredSelection(): void
	{
		$group = self::group();
		$group['matches'] = [self::rule(['options' => ['true'], 'checkbox' => true, 'array' => false])];
		$form = $this->form();
		$form->setFieldAttribute('mode', 'type', 'checkbox');
		$data = ['id' => 9, 'name' => 'Record'];
		$admin = $this->validator([$group], ['id' => 9, 'mode' => 1], false);
		$this->assertSame($data, $admin($form, $data));
		$patch = $this->validator([$group], ['id' => 9, 'mode' => 1]);
		$this->assertFalse($patch($form, $data));
		$this->assertFalse($form->getFieldXml('__jcb_conditional_required'));
	}

	/**
	 * Missing or reordered internal rules fail closed and leave a reusable form.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testPluginCannotRemoveOrMoveInternalRuleAfterFields(): void
	{
		foreach (['remove', 'move'] as $change)
		{
			$form = $this->form();
			$dispatcher = new Dispatcher();
			$dispatcher->addListener('onContentBeforeValidateData', static function (BeforeValidateDataEvent $event) use ($change): void
			{
				$form = $event->getForm();
				if ($change === 'remove')
				{
					$form->removeField('__jcb_conditional_required');
				}
				else
				{
					$node = dom_import_simplexml($form->getFieldXml('__jcb_conditional_required'));
					$node->parentNode->appendChild($node);
				}
			});
			$validate = $this->validator([self::group()], [], true, $dispatcher);
			$this->assertFalse($validate($form, ['name' => 'Record', 'mode' => 'expert', 'details' => 'present']));
			$this->assertFalse($form->getFieldXml('__jcb_conditional_required'));
			$validate = $this->validator([self::group()], []);
			$this->assertSame(['name' => 'Record', 'mode' => 'plain'], $validate($form, ['name' => 'Record', 'mode' => 'plain']));
		}
	}

	/**
	 * Exceptions release the callback and internal field without losing plugin edits.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testPluginExceptionCleansTemporaryState(): void
	{
		$form = $this->form();
		$dispatcher = new Dispatcher();
		$dispatcher->addListener('onContentBeforeValidateData', static function (BeforeValidateDataEvent $event): void
		{
			throw new RuntimeException('plugin failure');
		});
		$validate = $this->validator([self::group()], [], true, $dispatcher);
		try
		{
			$validate($form, ['name' => 'Record', 'mode' => 'plain']);
			$this->fail('The native plugin exception must remain observable.');
		}
		catch (RuntimeException $error)
		{
			$this->assertSame('plugin failure', $error->getMessage());
		}
		$this->assertFalse($form->getFieldXml('__jcb_conditional_required'));
		$validate = $this->validator([self::group()], []);
		$this->assertSame(['name' => 'Record', 'mode' => 'plain'], $validate($form, ['name' => 'Record', 'mode' => 'plain']));
	}

	/**
	 * Internal input is discarded and missing required attributes are restored exactly.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testInternalInputNeverReachesPersistenceAndAbsentAttributesStayAbsent(): void
	{
		$validate = $this->validator([self::group()], []);
		$form = $this->form();
		unset($form->getFieldXml('details')['required']);
		$this->assertFalse($validate($form, ['name' => 'Record', 'mode' => 'expert', '__jcb_conditional_required' => 'forged']));
		$this->assertNull($form->getFieldAttribute('details', 'required', null));
		$this->assertSame(
			['name' => 'Record', 'mode' => 'plain'],
			$validate($form, ['name' => 'Record', 'mode' => 'plain', '__jcb_conditional_required' => 'forged'])
		);
		$this->assertNull($form->getFieldAttribute('details', 'required', null));
	}

	/**
	 * Custom form methods keep their original identity and execute once per request.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testCustomFormLifecycleRunsOnceAndRestoresStateAfterPostProcessException(): void
	{
		$form = new class('custom-conditional') extends Form
		{
			/**
			 * Native lifecycle calls, in order.
			 *
			 * @var    array
			 * @since  6.2.0
			 */
			public array $calls = [];

			/**
			 * Whether the custom post-processor throws after validation succeeds.
			 *
			 * @var    bool
			 * @since  6.2.0
			 */
			public bool $throw = false;

			/**
			 * Transform the selector exactly once through a custom form filter.
			 *
			 * @param   mixed   $data   Submitted values.
			 * @param   string  $group  Selected group.
			 * @return  mixed
			 * @since   6.2.0
			 */
			public function filter($data, $group = null)
			{
				$this->calls[] = 'filter';
				$data['mode'] = 'expert';
				return parent::filter($data, $group);
			}

			/**
			 * Retain custom validation on the original Form instance.
			 *
			 * @param   mixed   $data   Filtered values.
			 * @param   string  $group  Selected group.
			 * @return  bool
			 * @since   6.2.0
			 */
			public function validate($data, $group = null)
			{
				$this->calls[] = 'validate';
				return parent::validate($data, $group);
			}

			/**
			 * Keep post-processing changes and exceptions observable.
			 *
			 * @param   mixed   $data   Validated values.
			 * @param   string  $group  Selected group.
			 * @return  mixed
			 * @throws  RuntimeException  When the test requests a post-process failure.
			 * @since   6.2.0
			 */
			public function postProcess($data, $group = null)
			{
				$this->calls[] = 'postProcess';
				if ($this->throw)
				{
					throw new RuntimeException('post-process failure');
				}
				$data = parent::postProcess($data, $group);
				$data['name'] .= ' processed';
				return $data;
			}
		};
		$this->form($form);
		$form->setFieldAttribute('details', 'required', 'false');
		$validate = $this->validator([self::group()], []);
		$this->assertFalse($validate($form, ['name' => 'Record', 'mode' => 'plain']));
		$this->assertSame(['filter', 'validate'], $form->calls);
		$form->calls = [];
		$this->assertSame(
			['name' => 'Record processed', 'mode' => 'expert', 'details' => 'present'],
			$validate($form, ['name' => 'Record', 'mode' => 'plain', 'details' => 'present'])
		);
		$this->assertSame(['filter', 'validate', 'postProcess'], $form->calls);
		$form->throw = true;
		try
		{
			$validate($form, ['name' => 'Record', 'details' => 'present']);
			$this->fail('The custom form exception must remain observable.');
		}
		catch (RuntimeException $error)
		{
			$this->assertSame('post-process failure', $error->getMessage());
		}
		$this->assertSame('false', $form->getFieldAttribute('details', 'required'));
		$this->assertFalse($form->getFieldXml('__jcb_conditional_required'));
		$form->throw = false;
		$this->assertIsArray($validate($form, ['name' => 'Record', 'details' => 'present']));
	}

	/**
	 * Execute the generated override on the real FormModel parent lifecycle.
	 *
	 * @param   array            $groups      Normalized condition groups.
	 * @param   array|false      $stored      Decoded values or a failed load.
	 * @param   bool             $patch       Whether this is an API PATCH request.
	 * @param   Dispatcher|null  $dispatcher  Native validation plugin dispatcher.
	 *
	 * @return  Closure
	 * @since   6.2.0
	 */
	private function validator(array $groups, array|false $stored, bool $patch = true, ?Dispatcher $dispatcher = null): Closure
	{
		$registry = new ValidationFixRegistry();
		$registry->set('record', ['details']);
		$registry->setConditions('record', $groups);
		$compiler = new CompilerRegistry();
		$method = (new ValidationFix($registry, $compiler, new ConditionalRule()))->get('record', 'Demo');
		$this->assertStringContainsString('parent::validate(', $method);
		$this->assertStringNotContainsString('->filter(', $method);
		$this->assertSame((new ConditionalRule())->get(), $compiler->get('validation.rules.jcbconditionalrequired'));
		$app = $this->createStub(CMSApplication::class);
		$app->method('isClient')->willReturnCallback(static fn (string $client): bool => $patch && $client === 'api');
		$input = new Input([]);
		$input->server->set('REQUEST_METHOD', $patch ? 'PATCH' : 'POST');
		$app->method('getInput')->willReturn($input);
		Factory::$application = $app;
		$dispatcher ??= new Dispatcher();
		$record = eval('use Joomla\\String\\StringHelper; return new class($stored, $dispatcher) extends \\'
			. GeneratedConditionalModelFixture::class . ' {' . $method . '};');

		return Closure::fromCallable([$record, 'validate']);
	}

	/**
	 * Build a real Joomla form with persistence and user boundaries isolated.
	 *
	 * @param   Form|null  $form  Optional custom Form implementation.
	 * @return  Form
	 * @since   6.2.0
	 */
	private function form(?Form $form = null): Form
	{
		$form ??= new Form('conditional');
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
