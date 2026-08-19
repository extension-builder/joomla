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
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use ReflectionProperty;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminView\ViewScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\IfValueScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\OptionsScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\TargetControlsScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\TargetRelationScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\ValueScript;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ScriptMediaSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ScriptUserSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ValidationFix;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ViewScript as ViewScriptBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Field\Groups as FieldGroups;
use VDM\Joomla\Componentbuilder\Compiler\Library\IncludeHelper;
use VDM\Joomla\Componentbuilder\Compiler\Model\Createdate;
use VDM\Joomla\Componentbuilder\Compiler\Model\Modifieddate;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Unique;


/**
 * Admin view javascript contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class AdminViewScriptTest extends ArchitectureTestCase
{
	/**
	 * The scripts the subject stored, by builder path.
	 *
	 * @var    ViewScriptBuilder
	 * @since  6.1.7
	 */
	private ViewScriptBuilder $scripts;

	/**
	 * The multi content the subject wrote.
	 *
	 * @var    ContentMulti
	 * @since  6.1.7
	 */
	private ContentMulti $content;

	/**
	 * The fields whose required attribute the subject switched.
	 *
	 * @var    ValidationFix
	 * @since  6.1.7
	 */
	private ValidationFix $fixes;

	/**
	 * The custom code the component declared, keyed by the dispenser's first key.
	 *
	 * @var    array<string, string>
	 * @since  6.1.7
	 */
	private array $customcode = [];

	/**
	 * The footer custom code the component declared, keyed by view.
	 *
	 * @var    array<string, string>
	 * @since  6.1.7
	 */
	private array $footercode = [];

	/**
	 * Every file structure the subject asked to be built.
	 *
	 * @var    array<int, array>
	 * @since  6.1.7
	 */
	private array $built = [];

	/**
	 * The unique-key state restored after each test.
	 *
	 * @var    array
	 * @since  6.1.7
	 */
	private array $uniqueState = [];

	/**
	 * Start every test from the first unique key, so generated names are fixed.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$property = new ReflectionProperty(Unique::class, 'unique');
		$this->uniqueState = $property->getValue();
		$property->setValue(null, []);
	}

	/**
	 * Hand the unique keys back to whichever test runs next.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function tearDown(): void
	{
		(new ReflectionProperty(Unique::class, 'unique'))->setValue(null, $this->uniqueState);

		parent::tearDown();
	}

	/**
	 * Build the subject over registries this test can read back.
	 *
	 * @return  ViewScript
	 * @since   6.1.7
	 */
	private function subject(): ViewScript
	{
		$this->scripts = new ViewScriptBuilder();
		$this->content = new ContentMulti();
		$this->fixes = new ValidationFix();
		$groups = (new ReflectionClass(FieldGroups::class))->newInstanceWithoutConstructor();
		$userSwitch = new ScriptUserSwitch();

		return $this->renderer(ViewScript::class, [
			'dispenser' => $this->dispenser(),
			'structure' => $this->structure(),
			'includehelper' => new IncludeHelper(),
			'createdate' => $this->date(Createdate::class, '1st January, 2026'),
			'modifieddate' => $this->date(Modifieddate::class, '2nd February, 2026'),
			'contentmulti' => $this->content,
			'scriptmediaswitch' => new ScriptMediaSwitch(),
			'scriptuserswitch' => $userSwitch,
			'validationfix' => $this->fixes,
			'viewscript' => $this->scripts,
			'valuescript' => new ValueScript($groups, $userSwitch),
			'optionsscript' => new OptionsScript($groups),
			'ifvaluescript' => new IfValueScript($groups, $userSwitch),
			'targetcontrolsscript' => new TargetControlsScript($groups, $this->fixes),
			'targetrelationscript' => new TargetRelationScript(),
		]);
	}

	/**
	 * A dispenser serving only what this test staged.
	 *
	 * @return  Dispenser
	 * @since   6.1.7
	 */
	private function dispenser(): Dispenser
	{
		return new class($this->customcode, $this->footercode) extends Dispenser
		{
			/**
			 * The staged custom code, by the dispenser's first key.
			 *
			 * @var    array<string, string>
			 * @since  6.1.7
			 */
			private array $staged;

			/**
			 * Stage the custom code this view declared.
			 *
			 * @param   array  $staged  Custom code by first key.
			 * @param   array  $footer  Footer custom code by view.
			 *
			 * @since   6.1.7
			 */
			public function __construct(array $staged, array $footer)
			{
				$this->staged = $staged;
				$this->hub = $footer === [] ? [] : ['view_footer' => $footer];
			}

			/**
			 * Serve the staged custom code, or the caller's own default.
			 *
			 * @param   string       $first    The custom code area.
			 * @param   string       $second   The view it belongs to.
			 * @param   string       $prefix   Ignored by this boundary.
			 * @param   string|null  $note     Ignored by this boundary.
			 * @param   bool         $unset    Ignored by this boundary.
			 * @param   mixed        $default  What an area with nothing staged returns.
			 * @param   string       $suffix   Ignored by this boundary.
			 *
			 * @return  mixed
			 * @since   6.1.7
			 */
			public function get(string $first, string $second, string $prefix = '',
				?string $note = null, bool $unset = false, $default = null,
				string $suffix = '')
			{
				return $this->staged[$first] ?? $default;
			}
		};
	}

	/**
	 * A structure boundary that records what it was asked to build.
	 *
	 * @return  Structure
	 * @since   6.1.7
	 */
	private function structure(): Structure
	{
		$structure = $this->createStub(Structure::class);
		$structure->method('build')->willReturnCallback(
			function (array $target, string $type, ?string $fileName = null,
				?array $config = null): bool
			{
				$this->built[] = [$target, $type, $fileName, $config];

				return true;
			}
		);

		return $structure;
	}

	/**
	 * A date service answering one fixed date.
	 *
	 * @param   class-string  $class  The date service to stand in for.
	 * @param   string        $date   The date it answers.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function date(string $class, string $date): object
	{
		$stub = $this->createStub($class);
		$stub->method('get')->willReturn($date);

		return $stub;
	}

	/**
	 * Build one admin view, with the conditions it declares.
	 *
	 * @param   array  $conditions  The conditions the view declares.
	 *
	 * @return  array  The view, as the component data carries it.
	 * @since   6.1.7
	 */
	private static function view(array $conditions = []): array
	{
		$settings = new stdClass();
		$settings->name_single_code = 'look';
		$settings->name_list_code = 'looks';
		$settings->version = '1.0.0';

		if ($conditions !== [])
		{
			$settings->conditions = $conditions;
		}

		return ['settings' => $settings];
	}

	/**
	 * Build one form condition over the demo view's fields.
	 *
	 * @param   array  $over  The parts of the condition this case changes.
	 *
	 * @return  array  The condition.
	 * @since   6.1.7
	 */
	private static function condition(array $over = []): array
	{
		return $over + [
			'match_field' => 'f-a',
			'match_name' => 'kind',
			'match_type' => 'list',
			'match_extends' => '',
			'match_behavior' => 1,
			'match_options' => "one\ntwo",
			'target_relation' => 0,
			'target_behavior' => 1,
			'target_field' => [['name' => 'colour', 'type' => 'text', 'required' => 0]],
		];
	}

	/**
	 * A view that declares no condition is given no script at all.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutConditionsIsGivenNoScript(): void
	{
		$this->subject()->get(self::view());

		$this->assertNull($this->scripts->get('look.fileScript'));
		$this->assertNull($this->scripts->get('look.footerScript'));
		$this->assertNull($this->scripts->get('looks.list_fileScript'));
	}

	/**
	 * The list view's javascript slot is always answered, empty when unused.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheListJavascriptSlotIsAlwaysAnswered(): void
	{
		$this->subject()->get(self::view());

		$this->assertSame(
			'',
			$this->content->get('looks|ADMIN_ADD_JAVASCRIPT_FILE')
		);
	}

	/**
	 * A condition that names no field to watch is quietly passed over.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAConditionThatNamesNoFieldIsPassedOver(): void
	{
		$this->subject()->get(self::view([self::condition(['match_name' => ''])]));

		$this->assertNull($this->scripts->get('look.fileScript'));
		$this->assertNull($this->scripts->get('look.footerScript'));
	}

	/**
	 * One selection condition becomes one function, its test and its listeners.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASelectionConditionBecomesItsFunctionAndListeners(): void
	{
		$this->subject()->get(self::view([self::condition()]));

		$this->assertSame(self::EXPECTED_SIMPLE_FILE, $this->scripts->get('look.fileScript'));
		$this->assertSame(self::EXPECTED_SIMPLE_FOOTER, $this->scripts->get('look.footerScript'));
	}

	/**
	 * A behaviour that only reveals leaves the function without an else branch.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testABehaviourThatOnlyRevealsHasNoElseBranch(): void
	{
		$this->subject()->get(self::view([self::condition(['target_behavior' => 3])]));

		$this->assertSame(self::EXPECTED_NO_TOGGLE_FILE, $this->scripts->get('look.fileScript'));
	}

	/**
	 * A text field is tested directly, with no array pass over its value.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATextFieldIsTestedWithoutAnArrayPass(): void
	{
		$this->subject()->get(self::view([self::condition([
			'match_type' => 'text',
			'match_behavior' => 6,
			'match_options' => 'keywords="red"',
		])]));

		$this->assertSame(self::EXPECTED_TEXT_FILE, $this->scripts->get('look.fileScript'));
	}

	/**
	 * A required target gets its global flag, its switching and the validation override.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testARequiredTargetGetsItsFlagAndValidationOverride(): void
	{
		$this->subject()->get(self::view([self::condition([
			'target_field' => [['name' => 'colour', 'type' => 'text', 'required' => 'yes']],
		])]));

		$this->assertSame(self::EXPECTED_REQUIRED_FILE, $this->scripts->get('look.fileScript'));
		$this->assertSame(['colour'], $this->fixes->get('look'));
	}

	/**
	 * Two conditions steering the same field share one function and both listeners.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTwoConditionsSteeringOneFieldShareAFunction(): void
	{
		$this->subject()->get(self::view([
			self::condition(['target_relation' => 1]),
			self::condition([
				'match_field' => 'f-b',
				'match_name' => 'level',
				'match_options' => "yes\nno",
				'target_relation' => 1,
			]),
		]));

		$this->assertSame(self::EXPECTED_CHAINED_FILE, $this->scripts->get('look.fileScript'));
		$this->assertSame(self::EXPECTED_CHAINED_FOOTER, $this->scripts->get('look.footerScript'));
	}

	/**
	 * The view's own custom script is appended even when it declares no condition.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheViewsCustomScriptIsAppendedOnItsOwn(): void
	{
		$this->customcode = ['view_file' => PHP_EOL . PHP_EOL . 'var extra = 1;'];

		$this->subject()->get(self::view());

		$this->assertSame(
			PHP_EOL . PHP_EOL . 'var extra = 1;',
			$this->scripts->get('look.fileScript')
		);
	}

	/**
	 * Custom footer code is wrapped in the script tags the edit body expects.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCustomFooterCodeIsWrappedInScriptTags(): void
	{
		$this->footercode = ['look' => 'alert(1);'];

		$this->subject()->get(self::view());

		$this->assertSame(
			PHP_EOL . PHP_EOL . '<script type="text/javascript">' . PHP_EOL
			. PHP_EOL . PHP_EOL . 'alert(1);' . PHP_EOL . '</script>',
			$this->scripts->get('look.footerScript')
		);
	}

	/**
	 * Footer code carrying php is still emitted, since it cannot be minified.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testFooterCodeCarryingPhpIsStillEmitted(): void
	{
		$this->footercode = ['look' => '<?php echo 1; ?>'];

		$this->subject()->get(self::view());

		$this->assertStringContainsString(
			'<?php echo 1; ?>',
			(string) $this->scripts->get('look.footerScript')
		);
	}

	/**
	 * A list view with its own script gets a file, a date stamp and an include.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAListViewWithAScriptGetsItsFileAndInclude(): void
	{
		$this->customcode = ['views_file' => PHP_EOL . PHP_EOL . 'var list = 1;'];

		$this->subject()->get(self::view());

		$this->assertSame(
			PHP_EOL . PHP_EOL . 'var list = 1;',
			$this->scripts->get('looks.list_fileScript')
		);
		$this->assertSame(
			[[
				['admin' => 'looks'],
				'javascript_file',
				'',
				[
					'###CREATIONDATE###' => '1st January, 2026',
					'###BUILDDATE###' => '2nd February, 2026',
					'###VERSION###' => '1.0.0',
				],
			]],
			$this->built
		);
		$this->assertStringContainsString(
			"'administrator/components/com_demo/assets/js/looks.js'",
			(string) $this->content->get('looks|ADMIN_ADD_JAVASCRIPT_FILE')
		);
	}

	/**
	 * A build that asks for minification gets minified javascript.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAMinifyingBuildGetsMinifiedJavascript(): void
	{
		$this->config()->set('minify', 1);
		$this->customcode = ['view_file' => PHP_EOL . PHP_EOL . 'var    extra   =   1;'];

		$this->subject()->get(self::view());

		$this->assertSame('var extra=1', $this->scripts->get('look.fileScript'));
	}

	/**
	 * A script is read back by view and kind, and nothing else answers.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAScriptIsReadBackByViewAndKind(): void
	{
		$subject = $this->subject();
		$subject->get(self::view([self::condition()]));

		$this->assertSame(self::EXPECTED_SIMPLE_FILE, $subject->script('look', 'fileScript'));
		$this->assertSame('', $subject->script('other', 'fileScript'));
		$this->assertSame('', $subject->script('look', 'list_fileScript'));
	}

	/**
	 * A function call reads every value it takes before it is made.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFunctionCallReadsEveryValueItTakes(): void
	{
		$this->assertSame(
			[
				'code' => PHP_EOL . "\tvar a = 1;" . PHP_EOL . "\tvar b = 2;"
					. PHP_EOL . "\tfn(a,b);" . PHP_EOL,
				'array' => true,
			],
			$this->subject()->functionCall('fn', ['a', 'b'], [
				'a' => ['get' => 'var a = 1;', 'isArray' => true],
				'b' => ['get' => 'var b = 2;', 'isArray' => false],
			])
		);
	}

	/**
	 * A function that takes no value is not called at all.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFunctionThatTakesNoValueIsNotCalled(): void
	{
		$this->assertSame(
			['code' => '', 'array' => false],
			$this->subject()->functionCall('fn', [], [])
		);
	}

	/**
	 * The generated javascript this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SIMPLE_FILE = <<<'JS'
		// Initial Script
		document.addEventListener('DOMContentLoaded', function()
		{
			var kind_vvvvvvv = jQuery("#jform_kind").val();
			vvvvvvv(kind_vvvvvvv);
		});

		// the vvvvvvv function
		function vvvvvvv(kind_vvvvvvv)
		{
			if (isSet(kind_vvvvvvv) && kind_vvvvvvv.constructor !== Array)
			{
				var temp_vvvvvvv = kind_vvvvvvv;
				var kind_vvvvvvv = [];
				kind_vvvvvvv.push(temp_vvvvvvv);
			}
			else if (!isSet(kind_vvvvvvv))
			{
				var kind_vvvvvvv = [];
			}
			var kind = kind_vvvvvvv.some(kind_vvvvvvv_SomeFunc);


			// set this function logic
			if (kind)
			{
				jQuery('#jform_colour').closest('.control-group').show();
			}
			else
			{
				jQuery('#jform_colour').closest('.control-group').hide();
			}
		}

		// the vvvvvvv Some function
		function kind_vvvvvvv_SomeFunc(kind_vvvvvvv)
		{
			// set the function logic
			if (kind_vvvvvvv == 'one' || kind_vvvvvvv == 'two')
			{
				return true;
			}
			return false;
		}

		// the isSet function
		function isSet(val)
		{
			if ((val != undefined) && (val != null) && 0 !== val.length){
				return true;
			}
			return false;
		}
		JS;

	/**
	 * The generated javascript this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SIMPLE_FOOTER = <<<'JS'


		<script type="text/javascript">

		// #jform_kind listeners for kind_vvvvvvv function
		jQuery('#jform_kind').on('keyup',function()
		{
			var kind_vvvvvvv = jQuery("#jform_kind").val();
			vvvvvvv(kind_vvvvvvv);

		});
		jQuery('#adminForm').on('change', '#jform_kind',function (e)
		{
			e.preventDefault();
			var kind_vvvvvvv = jQuery("#jform_kind").val();
			vvvvvvv(kind_vvvvvvv);

		});

		</script>
		JS;

	/**
	 * The generated javascript this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_NO_TOGGLE_FILE = <<<'JS'
		// Initial Script
		document.addEventListener('DOMContentLoaded', function()
		{
			var kind_vvvvvvv = jQuery("#jform_kind").val();
			vvvvvvv(kind_vvvvvvv);
		});

		// the vvvvvvv function
		function vvvvvvv(kind_vvvvvvv)
		{
			if (isSet(kind_vvvvvvv) && kind_vvvvvvv.constructor !== Array)
			{
				var temp_vvvvvvv = kind_vvvvvvv;
				var kind_vvvvvvv = [];
				kind_vvvvvvv.push(temp_vvvvvvv);
			}
			else if (!isSet(kind_vvvvvvv))
			{
				var kind_vvvvvvv = [];
			}
			var kind = kind_vvvvvvv.some(kind_vvvvvvv_SomeFunc);


			// set this function logic
			if (kind)
			{
				jQuery('#jform_colour').closest('.control-group').show();
			}
		}

		// the vvvvvvv Some function
		function kind_vvvvvvv_SomeFunc(kind_vvvvvvv)
		{
			// set the function logic
			if (kind_vvvvvvv == 'one' || kind_vvvvvvv == 'two')
			{
				return true;
			}
			return false;
		}

		// the isSet function
		function isSet(val)
		{
			if ((val != undefined) && (val != null) && 0 !== val.length){
				return true;
			}
			return false;
		}
		JS;

	/**
	 * The generated javascript this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_TEXT_FILE = <<<'JS'
		// Initial Script
		document.addEventListener('DOMContentLoaded', function()
		{
			var kind_vvvvvvv = jQuery("#jform_kind").val();
			vvvvvvv(kind_vvvvvvv);
		});

		// the vvvvvvv function
		function vvvvvvv(kind_vvvvvvv)
		{
			// set the function logic
			if (kind_vvvvvvv.indexOf("red") >= 0)
			{
				jQuery('#jform_colour').closest('.control-group').show();
			}
			else
			{
				jQuery('#jform_colour').closest('.control-group').hide();
			}
		}

		// the isSet function
		function isSet(val)
		{
			if ((val != undefined) && (val != null) && 0 !== val.length){
				return true;
			}
			return false;
		}
		JS;

	/**
	 * The generated javascript this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_REQUIRED_FILE = <<<'JS'
		// Some Global Values
		jform_vvvvvvvvvv_required = false;

		// Initial Script
		document.addEventListener('DOMContentLoaded', function()
		{
			var kind_vvvvvvv = jQuery("#jform_kind").val();
			vvvvvvv(kind_vvvvvvv);
		});

		// the vvvvvvv function
		function vvvvvvv(kind_vvvvvvv)
		{
			if (isSet(kind_vvvvvvv) && kind_vvvvvvv.constructor !== Array)
			{
				var temp_vvvvvvv = kind_vvvvvvv;
				var kind_vvvvvvv = [];
				kind_vvvvvvv.push(temp_vvvvvvv);
			}
			else if (!isSet(kind_vvvvvvv))
			{
				var kind_vvvvvvv = [];
			}
			var kind = kind_vvvvvvv.some(kind_vvvvvvv_SomeFunc);


			// set this function logic
			if (kind)
			{
				jQuery('#jform_colour').closest('.control-group').show();
				// add required attribute to colour field
				if (jform_vvvvvvvvvv_required)
				{
					updateFieldRequired('colour',0);
					jQuery('#jform_colour').prop('required','required');
					jQuery('#jform_colour').attr('aria-required',true);
					jQuery('#jform_colour').addClass('required');
					jform_vvvvvvvvvv_required = false;
				}
			}
			else
			{
				jQuery('#jform_colour').closest('.control-group').hide();
				// remove required attribute from colour field
				if (!jform_vvvvvvvvvv_required)
				{
					updateFieldRequired('colour',1);
					jQuery('#jform_colour').removeAttr('required');
					jQuery('#jform_colour').removeAttr('aria-required');
					jQuery('#jform_colour').removeClass('required');
					jform_vvvvvvvvvv_required = true;
				}
			}
		}

		// the vvvvvvv Some function
		function kind_vvvvvvv_SomeFunc(kind_vvvvvvv)
		{
			// set the function logic
			if (kind_vvvvvvv == 'one' || kind_vvvvvvv == 'two')
			{
				return true;
			}
			return false;
		}

		/**
		 * Update the "not required" field list by adding or removing a field name.
		 *
		 * Mirrors the original jQuery logic exactly but uses pure JavaScript.
		 *
		 * @param  {string}  name    The field name to add or remove.
		 * @param  {number}  status  1 to add as not required, 0 to remove.
		 *
		 * @return {void}
		 * @since  3.1.3
		 */
		function updateFieldRequired(name, status) {
			// Check if #jform_not_required exists
			const notRequiredField = document.getElementById('jform_not_required');
			if (!notRequiredField) {
				return;
			}

			// Split the comma-separated list into an array
			let not_required = notRequiredField.value ? notRequiredField.value.split(',') : [];

			// Add or remove the field name from the list
			if (status == 1) {
				not_required.push(name);
			} else {
				not_required = removeFieldFromNotRequired(not_required, name);
			}

			// Clean and deduplicate the list
			const fixedList = fixNotRequiredArray(not_required);

			// Write back the updated comma-separated list
			notRequiredField.value = fixedList.toString();
		}

		/**
		 * Remove a specific field name from the "not required" array.
		 *
		 * @param  {Array<string>} array  The list of not-required field names.
		 * @param  {string}        what   The field name to remove.
		 *
		 * @return {Array<string>}        The updated array.
		 * @since  3.1.3
		 */
		function removeFieldFromNotRequired(array, what) {
			return array.filter(function (element) {
				return element !== what;
			});
		}

		/**
		 * Deduplicate and clean a "not required" array.
		 *
		 * @param  {Array<string>} array  The array to fix.
		 *
		 * @return {Array<string>}        A cleaned, unique array.
		 * @since  3.1.3
		 */
		function fixNotRequiredArray(array) {
			const seen = {};
			return removeEmptyFromNotRequiredArray(array).filter(function (item) {
				return seen.hasOwnProperty(item) ? false : (seen[item] = true);
			});
		}

		/**
		 * Remove empty or invalid entries from a "not required" array.
		 *
		 * Also removes the literal '一_一' token (legacy quirk preserved for compatibility).
		 *
		 * @param  {Array<string>} array  The array to process.
		 *
		 * @return {Array<string>}        The cleaned array.
		 * @since  3.1.3
		 */
		function removeEmptyFromNotRequiredArray(array) {
			return array.filter(function (el) {
				return el && el.length > 0 && el !== '一_一';
			});
		}

		// the isSet function
		function isSet(val)
		{
			if ((val != undefined) && (val != null) && 0 !== val.length){
				return true;
			}
			return false;
		}
		JS;

	/**
	 * The generated javascript this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_CHAINED_FILE = <<<'JS'
		// Initial Script
		document.addEventListener('DOMContentLoaded', function()
		{
			var kind_vvvvvvv = jQuery("#jform_kind").val();
			var level_vvvvvvv = jQuery("#jform_level").val();
			vvvvvvv(kind_vvvvvvv,level_vvvvvvv);
		});

		// the vvvvvvv function
		function vvvvvvv(kind_vvvvvvv,level_vvvvvvv)
		{
			if (isSet(kind_vvvvvvv) && kind_vvvvvvv.constructor !== Array)
			{
				var temp_vvvvvvv = kind_vvvvvvv;
				var kind_vvvvvvv = [];
				kind_vvvvvvv.push(temp_vvvvvvv);
			}
			else if (!isSet(kind_vvvvvvv))
			{
				var kind_vvvvvvv = [];
			}
			var kind = kind_vvvvvvv.some(kind_vvvvvvv_SomeFunc);

			if (isSet(level_vvvvvvv) && level_vvvvvvv.constructor !== Array)
			{
				var temp_vvvvvvv = level_vvvvvvv;
				var level_vvvvvvv = [];
				level_vvvvvvv.push(temp_vvvvvvv);
			}
			else if (!isSet(level_vvvvvvv))
			{
				var level_vvvvvvv = [];
			}
			var level = level_vvvvvvv.some(level_vvvvvvv_SomeFunc);


			// set this function logic
			if (kind && level)
			{
				jQuery('#jform_colour').closest('.control-group').show();
			}
			else
			{
				jQuery('#jform_colour').closest('.control-group').hide();
			}
		}

		// the vvvvvvv Some function
		function kind_vvvvvvv_SomeFunc(kind_vvvvvvv)
		{
			// set the function logic
			if (kind_vvvvvvv == 'one' || kind_vvvvvvv == 'two')
			{
				return true;
			}
			return false;
		}

		// the vvvvvvv Some function
		function level_vvvvvvv_SomeFunc(level_vvvvvvv)
		{
			// set the function logic
			if (level_vvvvvvv == 'yes' || level_vvvvvvv == 'no')
			{
				return true;
			}
			return false;
		}

		// the isSet function
		function isSet(val)
		{
			if ((val != undefined) && (val != null) && 0 !== val.length){
				return true;
			}
			return false;
		}
		JS;

	/**
	 * The generated javascript this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_CHAINED_FOOTER = <<<'JS'


		<script type="text/javascript">

		// #jform_kind listeners for kind_vvvvvvv function
		jQuery('#jform_kind').on('keyup',function()
		{
			var kind_vvvvvvv = jQuery("#jform_kind").val();
			var level_vvvvvvv = jQuery("#jform_level").val();
			vvvvvvv(kind_vvvvvvv,level_vvvvvvv);

		});
		jQuery('#adminForm').on('change', '#jform_kind',function (e)
		{
			e.preventDefault();
			var kind_vvvvvvv = jQuery("#jform_kind").val();
			var level_vvvvvvv = jQuery("#jform_level").val();
			vvvvvvv(kind_vvvvvvv,level_vvvvvvv);

		});

		// #jform_level listeners for level_vvvvvvv function
		jQuery('#jform_level').on('keyup',function()
		{
			var kind_vvvvvvv = jQuery("#jform_kind").val();
			var level_vvvvvvv = jQuery("#jform_level").val();
			vvvvvvv(kind_vvvvvvv,level_vvvvvvv);

		});
		jQuery('#adminForm').on('change', '#jform_level',function (e)
		{
			e.preventDefault();
			var kind_vvvvvvv = jQuery("#jform_kind").val();
			var level_vvvvvvv = jQuery("#jform_level").val();
			vvvvvvv(kind_vvvvvvv,level_vvvvvvv);

		});

		</script>
		JS;
}
