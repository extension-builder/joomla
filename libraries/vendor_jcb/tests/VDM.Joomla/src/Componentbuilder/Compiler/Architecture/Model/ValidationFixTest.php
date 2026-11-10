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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminView\ViewScript;
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
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ValidationFix as ValidationFixRegistry;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ViewScript as ViewScriptBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\RequiredFields;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor as JoomlaPowerExtractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector as JoomlaPowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Power\Parser;
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
	 * Native validation and its registered rule use the configured code formatting.
	 *
	 * @param   string  $indent  One generated indentation level.
	 * @param   bool    $debug   Include compiler source references in comments.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('generatedFormatting')]
	public function testConfiguredFormattingPreservesNativeValidation(string $indent, bool $debug): void
	{
		$definitions = new ValidationFixRegistry();
		$definitions->set('record', ['details']);
		$definitions->setConditions('record', [self::group()]);
		$definitions->setCustomFields('record', ['datatype', 'null_switch', 'store']);
		$compiler = new CompilerRegistry();
		$generator = new ValidationFix($definitions, $compiler, new ConditionalRule());
		$method = $generator->get('record', 'Demo');
		$rule = $compiler->get('validation.rules.jcbconditionalrequired');

		(new ReflectionProperty(Indent::class, 'indent'))->setValue(null, $indent);
		(new ReflectionProperty(Indent::class, 'bucket'))->setValue(null, []);
		(new ReflectionProperty(Line::class, 'add'))->setValue(null, $debug);
		$formatted = $generator->get('record', 'Demo');
		$registered = $compiler->get('validation.rules.jcbconditionalrequired');

		foreach ([[$method, $formatted, ValidationFix::class], [$rule, $registered, ConditionalRule::class]] as [$original, $actual, $class])
		{
			$expected = preg_replace_callback('/^\t+/m', static fn (array $match): string => str_repeat($indent, strlen($match[0])), $original);
			$comments = '/(?<=\/\/) \[' . preg_quote($class, '/') . ' [1-9][0-9]*\]/';
			if ($debug)
			{
				$this->assertMatchesRegularExpression($comments, $actual);
			}
			else
			{
				$this->assertDoesNotMatchRegularExpression($comments, $actual);
			}
			$this->assertSame($expected, preg_replace($comments, '', $actual));
		}

		$validate = $this->validator([self::group()], ['id' => 9, 'mode' => 'expert']);
		$form = $this->form();
		$this->assertFalse($validate($form, ['id' => 9, 'name' => 'Missing details']));
		$data = ['id' => 9, 'name' => 'Complete', 'details' => 'Required value'];
		$this->assertSame($data, $validate($form, $data));
		$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
		$validate = $this->validator([], [], false, null, ['datatype', 'null_switch', 'store']);
		$form = $this->databaseForm();
		$data = ['name' => 'Note', 'not_required' => 'datatype,null_switch,store'];
		$this->assertSame($data, $validate($form, $data));
		$this->assertFalse($validate($form, ['name' => 'Text']));
	}

	/**
	 * Exercise tab and space indentation with compiler source comments on and off.
	 *
	 * @return  iterable<string, array{string, bool}>
	 * @since   6.2.0
	 */
	public static function generatedFormatting(): iterable
	{
		foreach (['tabs' => "\t", 'two spaces' => '  ', 'four spaces' => '    '] as $name => $indent)
		{
			foreach ([false, true] as $debug)
			{
				yield $name . ($debug ? ' with source comments' : '') => [$indent, $debug];
			}
		}
	}

	/**
	 * Emission indentation must not rewrite whitespace inside authored option values.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testConditionValuesKeepTheirOwnWhitespace(): void
	{
		$value = "first\n\t\tsecond\n    third";
		$group = self::group();
		$group['matches'][0]['options'] = [$value];

		foreach (["\t", '  '] as $indent)
		{
			(new ReflectionProperty(Indent::class, 'indent'))->setValue(null, $indent);
			(new ReflectionProperty(Indent::class, 'bucket'))->setValue(null, []);
			$validate = $this->validator([$group], ['id' => 9, 'mode' => $value]);
			$form = $this->form();

			$this->assertFalse($validate($form, ['id' => 9, 'name' => 'Missing details']));
			$data = ['id' => 9, 'name' => 'Complete', 'details' => 'Required value'];
			$this->assertSame($data, $validate($form, $data));
		}
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
	 * Custom targets stay normalized, isolated by view, and replaceable between builds.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testCustomTargetRegistryNormalizesAndResetsEachView(): void
	{
		$registry = new ValidationFixRegistry();
		$registry->setCustomFields('record', ['datatype', 'null_switch', 'datatype', 'with-hyphen', '', 'settings.store', 'bad name', 4, null, [], true]);
		$registry->setCustomFields('other', ['store']);
		$this->assertSame(['datatype', 'null_switch', 'with-hyphen'], $registry->getCustomFields('record'));
		$this->assertSame(['store'], $registry->getCustomFields('other'));
		$this->assertSame([], $registry->getCustomFields('missing'));
		$registry->setCustomFields('record', []);
		$this->assertSame([], $registry->getCustomFields('record'));
		$this->assertSame(['store'], $registry->getCustomFields('other'));
	}

	/**
	 * The shipped Note/Spacer custom script reaches a working generated model.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testShippedDatabaseCheckerSurvivesCustomOnlyCompilation(): void
	{
		$source = file_get_contents(dirname(__DIR__, 9) . '/media/js/admin/field.js');
		$this->assertIsString($source);
		$start = strpos($source, 'function dbChecker(type)');
		$this->assertNotFalse($start);
		$script = substr($source, $start);
		$registry = new ValidationFixRegistry();
		$scripts = new ViewScriptBuilder();
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->hub = [];
		$dispenser->method('get')->willReturnCallback(static function (
			string $first, string $second, string $prefix = '', ?string $note = null,
			bool $unset = false, $default = null, string $suffix = ''
		) use ($script)
		{
			return $first === 'view_file' ? $script : $default;
		});
		$this->renderer(ViewScript::class, [
			'dispenser' => $dispenser,
			'requiredfields' => new RequiredFields(),
			'validationfix' => $registry,
			'viewscript' => $scripts,
			'contentmulti' => new ContentMulti(),
		])->get(['settings' => (object) ['name_single_code' => 'record', 'name_list_code' => 'records']]);

		$this->assertSame(['datatype', 'null_switch', 'store'], $registry->getCustomFields('record'));
		$this->assertSame([], $registry->getConditions('record'));
		$this->assertNull($registry->get('record'));
		$this->assertSame(1, substr_count($scripts->get('record.fileScript'), 'function updateFieldRequired('));
		$this->assertStringContainsString($script, $scripts->get('record.fileScript'));
		foreach (['note', 'spacer'] as $type)
		{
			foreach ([[], ['id' => 9, 'datatype' => '', 'null_switch' => '', 'store' => '']] as $stored)
			{
				$validate = $this->validator([], $stored, false, null, [], $registry);
				$form = $this->databaseForm();
				$data = ['name' => $type, 'fieldtype' => $type, 'datatype' => '', 'null_switch' => '', 'store' => '', 'not_required' => 'datatype,null_switch,store'];
				if ($stored !== [])
				{
					$data = ['id' => 9] + $data;
				}
				$this->assertSame($data, $validate($form, $data));
				$this->assertSame('true', $form->getFieldAttribute('datatype', 'required'));
				$this->assertSame('true', $form->getFieldAttribute('null_switch', 'required'));
				$this->assertSame('true', $form->getFieldAttribute('store', 'required'));
				unset($data['not_required']);
				$this->assertFalse($validate($form, $data));
			}
		}
	}

	/**
	 * Authored exemptions coexist with structured rules without relaxing ordinary fields.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testCustomRequirementsCoexistWithStructuredRulesAndPreserveValues(): void
	{
		$validate = $this->validator([self::group()], ['id' => 9, 'mode' => 'plain'], false, null, ['datatype', 'null_switch', 'store']);
		$form = $this->databaseForm();
		$form->load('<form><field name="details" type="text" required="true" filter="raw" /></form>');
		$form->setFieldAttribute('store', 'filter', 'int');
		$data = [
			'id' => 9, 'name' => 'Note', 'mode' => 'plain',
			'datatype' => '', 'null_switch' => '', 'store' => '6garbage',
			'not_required' => ' datatype, null_switch,store,datatype,name,details,unknown ',
			'details' => 'Keep inactive code',
		];
		$this->assertSame(array_replace($data, ['store' => 6]), $validate($form, $data));
		$this->assertSame('true', $form->getFieldAttribute('details', 'required'));
		$this->assertSame('true', $form->getFieldAttribute('datatype', 'required'));
		unset($data['name']);
		$this->assertFalse($validate($form, $data));
		$data['name'] = 'Note';
		$data['mode'] = 'expert';
		unset($data['details']);
		$this->assertFalse($validate($form, $data));
		$this->assertSame('true', $form->getFieldAttribute('name', 'required'));
	}

	/**
	 * A registered custom target cannot override a structured or unsupported condition.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testCustomListsCannotOverrideStructuredTargets(): void
	{
		foreach ([true, false] as $supported)
		{
			$group = self::group();
			$group['targets'] = ['datatype'];
			$group['matches'][0]['supported'] = $supported;
			$validate = $this->validator([$group], [], false, null, ['datatype', 'null_switch', 'store']);
			$form = $this->databaseForm();
			$data = ['name' => 'Note', 'mode' => 'expert', 'not_required' => 'datatype,null_switch,store'];
			$this->assertFalse($validate($form, $data));
			$this->assertSame('true', $form->getFieldAttribute('datatype', 'required'));
			$data['mode'] = 'plain';
			if ($supported)
			{
				$this->assertSame($data, $validate($form, $data));
			}
			else
			{
				$this->assertFalse($validate($form, $data));
			}
		}
	}

	/**
	 * Native filtering determines the list; malformed or unavailable lists grant nothing.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testCustomListsUseFilteredStringsAndRespectFormAvailability(): void
	{
		$validate = $this->validator([], [], false, null, ['datatype', 'null_switch', 'store']);
		foreach ([null, [], ['datatype', 'null_switch', 'store'], (object) ['datatype' => true], 1, true, '', 'settings.datatype,null_switch,store'] as $list)
		{
			$form = $this->databaseForm();
			$this->assertFalse($validate($form, ['name' => 'Note', 'not_required' => $list]));
			$this->assertSame('true', $form->getFieldAttribute('datatype', 'required'));
			$this->assertFalse($form->getFieldXml('__jcb_conditional_required'));
		}
		foreach (['removed', 'unset', 'disabled', 'int', 'cmd'] as $protection)
		{
			$form = $this->databaseForm();
			if ($protection === 'removed')
			{
				$form->removeField('not_required');
			}
			else
			{
				$form->setFieldAttribute('not_required', $protection === 'disabled' ? 'disabled' : 'filter', $protection === 'disabled' ? 'true' : $protection);
			}
			try
			{
				$this->assertFalse($validate($form, ['name' => 'Note', 'not_required' => 'datatype,null_switch,store']));
			}
			catch (RuntimeException $error)
			{
				$this->assertSame('disabled', $protection);
				$this->assertSame('JLIB_FORM_VALIDATE_FIELD_INVALID', $error->getMessage());
			}
			$this->assertSame('true', $form->getFieldAttribute('datatype', 'required'));
			$this->assertFalse($form->getFieldXml('__jcb_conditional_required'));
		}
		$form = $this->databaseForm();
		$form->setFieldAttribute('not_required', 'filter', 'string');
		$this->assertSame(
			['name' => 'Note', 'not_required' => 'datatype,null_switch,store'],
			$validate($form, ['name' => 'Note', 'not_required' => '<b>datatype</b>,<i>null_switch</i>,store'])
		);
	}

	/**
	 * Grouped custom lists cannot exempt fields outside the group being validated.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testCustomListsUseOnlyTheValidatedGroup(): void
	{
		$validate = $this->validator([], [], false, null, ['datatype', 'null_switch', 'store']);
		$form = $this->databaseForm(null, 'settings');
		$data = ['settings' => ['name' => 'Note', 'not_required' => 'datatype,null_switch,store']];
		$this->assertSame($data, $validate($form, $data, 'settings'));
		$this->assertSame('true', $form->getFieldAttribute('datatype', 'required', null, 'settings'));
		$this->assertFalse($validate($form, ['settings' => ['name' => 'Note'], 'not_required' => 'datatype,null_switch,store'], 'settings'));
		$this->assertFalse($validate($form, ['settings' => ['name' => 'Note'], 'other' => ['not_required' => 'datatype,null_switch,store']], 'settings'));
		$this->assertFalse($form->getFieldXml('__jcb_conditional_required', 'settings'));
	}

	/**
	 * A custom hint neither restores removed controls nor changes protected requirements.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testCustomTargetsRespectRemovedDisabledAndUnsetFields(): void
	{
		$validate = $this->validator([], [], false, null, ['datatype']);
		$data = ['name' => 'Note', 'null_switch' => 'NULL', 'store' => '6', 'not_required' => 'datatype'];
		$form = $this->databaseForm();
		$form->removeField('datatype');
		$this->assertSame($data, $validate($form, $data));
		$this->assertFalse($form->getFieldXml('datatype'));
		foreach (['unset', 'true', '1', 'disabled'] as $protection)
		{
			$form = $this->databaseForm();
			$form->setFieldAttribute('datatype', $protection === 'unset' ? 'filter' : 'disabled', $protection);
			$this->assertFalse($validate($form, $data));
			$this->assertSame('true', $form->getFieldAttribute('datatype', 'required'));
			$this->assertFalse($form->getFieldXml('__jcb_conditional_required'));
		}
	}

	/**
	 * Native events supply the filtered custom hint once and keep plugin edits on failure.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testNativePluginCustomListsAndExceptionsKeepFormState(): void
	{
		$form = $this->databaseForm();
		$dispatcher = new Dispatcher();
		$calls = 0;
		$throw = false;
		$grant = true;
		$dispatcher->addListener('onContentBeforeValidateData', function (BeforeValidateDataEvent $event) use ($form, &$calls, &$throw, &$grant): void
		{
			$this->assertSame($form, $event->getForm());
			$calls++;
			$form->setFieldAttribute('not_required', 'filter', 'string');
			$form->setFieldAttribute('null_switch', 'required', 'false');
			$event->updateData(array_replace($event->getData(), ['not_required' => $grant ? '<b>datatype</b>,null_switch,store' : '']));
			if ($throw)
			{
				throw new RuntimeException('custom plugin failure');
			}
		});
		$validate = $this->validator([], [], false, $dispatcher, ['datatype', 'null_switch', 'store']);
		$this->assertSame(['name' => 'Note', 'not_required' => 'datatype,null_switch,store'], $validate($form, ['name' => 'Note']));
		$this->assertSame(1, $calls);
		$this->assertSame('false', $form->getFieldAttribute('null_switch', 'required'));
		$throw = true;
		try
		{
			$validate($form, ['name' => 'Note']);
			$this->fail('The custom plugin exception must remain observable.');
		}
		catch (RuntimeException $error)
		{
			$this->assertSame('custom plugin failure', $error->getMessage());
		}
		$this->assertSame(2, $calls);
		$this->assertSame('true', $form->getFieldAttribute('datatype', 'required'));
		$this->assertSame('false', $form->getFieldAttribute('null_switch', 'required'));
		$this->assertFalse($form->getFieldXml('__jcb_conditional_required'));
		$throw = false;
		$grant = false;
		$this->assertFalse($validate($form, ['name' => 'Text']));
		$this->assertSame(3, $calls);
	}

	/**
	 * Custom exemptions preserve non-empty native validators and exact XML attributes.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testCustomRequirementsPreserveNativeRulesAndAbsentAttributes(): void
	{
		$validate = $this->validator([], [], false, null, ['datatype', 'null_switch', 'store']);
		$form = $this->databaseForm();
		$form->setFieldAttribute('datatype', 'validate', 'email');
		unset($form->getFieldXml('null_switch')['required']);
		$data = ['name' => 'Note', 'datatype' => 'invalid-email', 'not_required' => 'datatype,null_switch,store'];
		$this->assertFalse($validate($form, $data));
		$this->assertSame('email', $form->getFieldAttribute('datatype', 'validate'));
		$this->assertSame('true', $form->getFieldAttribute('datatype', 'required'));
		$this->assertNull($form->getFieldAttribute('null_switch', 'required', null));
		$data['datatype'] = 'valid@example.test';
		$this->assertSame($data, $validate($form, $data));
		$this->assertSame('true', $form->getFieldAttribute('datatype', 'required'));
		$this->assertNull($form->getFieldAttribute('null_switch', 'required', null));
	}

	/**
	 * API updates retain strict fields even when the browser custom list is supplied.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testApiCannotUseCustomBrowserExemptions(): void
	{
		foreach ([true, false] as $patch)
		{
			$validate = $this->validator([], ['id' => 9, 'datatype' => 'VARCHAR', 'null_switch' => 'NULL', 'store' => '6'], $patch, null, ['datatype', 'null_switch', 'store'], null, true);
			$form = $this->databaseForm();
			$this->assertFalse($validate($form, ['id' => 9, 'name' => 'Updated', 'not_required' => 'datatype,null_switch,store']));
			$data = ['id' => 9, 'name' => 'Updated', 'datatype' => 'VARCHAR', 'null_switch' => 'NULL', 'store' => '6', 'not_required' => 'datatype,null_switch,store'];
			$this->assertSame($data, $validate($form, $data));
			$this->assertSame('true', $form->getFieldAttribute('datatype', 'required'));
		}
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
				$data['not_required'] = 'datatype,null_switch,store';
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
		$this->databaseForm($form);
		$form->load('<form><field name="details" type="text" required="true" filter="raw" /></form>');
		$form->setFieldAttribute('details', 'required', 'false');
		$form->setFieldAttribute('null_switch', 'required', 'false');
		unset($form->getFieldXml('store')['required']);
		$validate = $this->validator([self::group()], [], false, null, ['datatype', 'null_switch', 'store']);
		$this->assertFalse($validate($form, ['name' => 'Record', 'mode' => 'plain']));
		$this->assertSame(['filter', 'validate'], $form->calls);
		$form->calls = [];
		$this->assertSame(
			['name' => 'Record processed', 'mode' => 'expert', 'not_required' => 'datatype,null_switch,store', 'details' => 'present'],
			$validate($form, ['name' => 'Record', 'mode' => 'plain', 'details' => 'present'])
		);
		$this->assertSame(['filter', 'validate', 'postProcess'], $form->calls);
		$form->throw = true;
		$form->calls = [];
		try
		{
			$validate($form, ['name' => 'Record', 'details' => 'present']);
			$this->fail('The custom form exception must remain observable.');
		}
		catch (RuntimeException $error)
		{
			$this->assertSame('post-process failure', $error->getMessage());
		}
		$this->assertSame(['filter', 'validate', 'postProcess'], $form->calls);
		$this->assertSame('false', $form->getFieldAttribute('details', 'required'));
		$this->assertSame('true', $form->getFieldAttribute('datatype', 'required'));
		$this->assertSame('false', $form->getFieldAttribute('null_switch', 'required'));
		$this->assertNull($form->getFieldAttribute('store', 'required', null));
		$this->assertFalse($form->getFieldXml('__jcb_conditional_required'));
		$form->throw = false;
		$this->assertIsArray($validate($form, ['name' => 'Record', 'details' => 'present']));
	}

	/**
	 * Execute the generated override on the real FormModel parent lifecycle.
	 *
	 * @param   array                       $groups        Normalized condition groups.
	 * @param   array|false                 $stored        Decoded values or a failed load.
	 * @param   bool                        $patch         Whether this is a PATCH request.
	 * @param   Dispatcher|null             $dispatcher    Native validation plugin dispatcher.
	 * @param   array                       $customFields  Authored custom-script targets.
	 * @param   ValidationFixRegistry|null  $registry      Optional upstream compiler state.
	 * @param   bool|null                   $api           API client; defaults to the PATCH flag.
	 *
	 * @return  Closure
	 * @since   6.2.0
	 */
	private function validator(array $groups, array|false $stored, bool $patch = true, ?Dispatcher $dispatcher = null,
		array $customFields = [], ?ValidationFixRegistry $registry = null, ?bool $api = null): Closure
	{
		if ($registry === null)
		{
			$registry = new ValidationFixRegistry();
			if ($groups !== [])
			{
				$registry->set('record', ['details']);
			}
			$registry->setConditions('record', $groups);
			$registry->setCustomFields('record', $customFields);
		}
		$compiler = new CompilerRegistry();
		$method = (new ValidationFix($registry, $compiler, new ConditionalRule()))->get('record', 'Demo');
		$this->assertStringContainsString('parent::validate(', $method);
		$this->assertStringNotContainsString('->filter(', $method);
		$this->assertSame((new ConditionalRule())->get(), $compiler->get('validation.rules.jcbconditionalrequired'));
		$app = $this->createStub(CMSApplication::class);
		$api ??= $patch;
		$app->method('isClient')->willReturnCallback(static fn (string $client): bool => $client === ($api ? 'api' : 'administrator'));
		$input = new Input([]);
		$input->server->set('REQUEST_METHOD', $patch ? 'PATCH' : 'POST');
		$app->method('getInput')->willReturn($input);
		Factory::$application = $app;
		$dispatcher ??= new Dispatcher();
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['571422c4-0340-49f8-b846-5729c7af6ed7', 0, (object) ['_namespace' => 'Joomla\CMS\Form', 'class_name' => 'FormHelper', 'type' => 'class']],
			['a87c432d-b5b4-428e-b7ff-14b51664c624', 0, (object) ['_namespace' => 'Joomla\Registry', 'class_name' => 'Registry', 'type' => 'class']],
			['39403062-84fb-46e0-bac4-0023f766e827', 0, (object) ['_namespace' => 'Joomla\CMS', 'class_name' => 'Factory', 'type' => 'class']],
		]);
		$injector = new JoomlaPowerInjector(
			$power,
			new JoomlaPowerExtractor($this->createStub(DatabaseInterface::class), 6),
			new Parser(),
			$this->placeholder()
		);
		$this->assertStringContainsString('Joomla___571422c4_0340_49f8_b846_5729c7af6ed7___Power', $method);
		$this->assertStringContainsString('Joomla___a87c432d_b5b4_428e_b7ff_14b51664c624___Power', $method);
		$this->assertStringContainsString('Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power', $method);
		$code = '<?php' . "\nuse Joomla\String\StringHelper;\n"
			. 'use Joomla\Registry\Registry as StoredRegistry;' . "\n"
			. 'return new class($stored, $dispatcher) extends \\' . GeneratedConditionalModelFixture::class . ' {' . $method . '};';
		$code = $injector->power($code);
		$this->assertStringNotContainsString('Joomla___', $code);
		$this->assertStringContainsString('use Joomla\CMS\Form\FormHelper;', $code);
		$this->assertStringContainsString('use Joomla\CMS\Factory;', $code);
		$this->assertStringContainsString('new StoredRegistry(', $code);
		$record = eval(substr($code, strlen('<?php')));

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
	 * Build the required database controls changed by authored Note/Spacer JavaScript.
	 *
	 * @param   Form|null    $form   Optional custom Form implementation.
	 * @param   string|null  $group  Optional nested form group.
	 *
	 * @return  Form
	 * @since   6.2.1
	 */
	private function databaseForm(?Form $form = null, ?string $group = null): Form
	{
		$form ??= new Form('custom-database');
		$form->setDatabase($this->createStub(DatabaseInterface::class));
		$form->setCurrentUser(new User());
		$fields = '<field name="id" type="text" filter="int" />'
			. '<field name="name" type="text" required="true" filter="raw" />'
			. '<field name="fieldtype" type="text" filter="raw" />'
			. '<field name="mode" type="text" default="plain" filter="raw" />'
			. '<field name="datatype" type="text" required="true" filter="raw" />'
			. '<field name="null_switch" type="text" required="true" filter="raw" />'
			. '<field name="store" type="text" required="true" filter="raw" />'
			. '<field name="not_required" type="hidden" filter="raw" />';
		$form->load('<form>' . ($group === null ? $fields : '<fields name="' . $group . '">' . $fields . '</fields>') . '</form>');

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
