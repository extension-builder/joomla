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


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\Input\Input;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\EximportButtons;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\CustomView\SubmitButtonScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\ClearValueScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaThree\AdminViews\EximportButtons as J3EximportButtons;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\CheckboxSave;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Table\Constructor;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\Jquery;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CategoryCode;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CheckBox;
use VDM\Joomla\Componentbuilder\Compiler\Builder\EximportView;
use VDM\Joomla\Componentbuilder\Compiler\Builder\History;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Tags;


/**
 * The small pieces every generated view is assembled from.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedViewPiecesTest extends ArchitectureTestCase
{
	/**
	 * The check box handling of a save method, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_CHECKBOX = <<<'GEN'


		// Set the empty published item to data
		if (!isset($data['published']) && !($input->getMethod() === 'PATCH' && Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->isClient('api')))
		{
			$data['published'] = '';
		}

		// Set the empty featured item to data
		if (!isset($data['featured']) && !($input->getMethod() === 'PATCH' && Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->isClient('api')))
		{
			$data['featured'] = '';
		}
GEN;

	/**
	 * The observers of a table watching both, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_TABLE_BOTH = <<<'GEN'


		// Adding Tag Options
		Joomla___fe63add8_0a40_4b3d_b548_f735fa6072fb___Power::createObserver($this, array('typeAlias' => 'com_demo.demo'));

		// Adding History Options
		Joomla___9ac794c2_f96d_4522_8acf_b8d48c4f51c5___Power::createObserver($this, array('typeAlias' => 'com_demo.demo'));
GEN;

	/**
	 * The observers of a table watching only its tags, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_TABLE_TAGS = <<<'GEN'


		// Adding Tag Options
		Joomla___fe63add8_0a40_4b3d_b548_f735fa6072fb___Power::createObserver($this, array('typeAlias' => 'com_demo.demo'));
GEN;

	/**
	 * The submit button script of a custom view, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SUBMIT = <<<'GEN'

<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task === 'demo.back') {
			parent.history.back();
			return false;
		} else {
			var form = document.getElementById('adminForm');
			form.task.value = task;
			form.submit();
		}
	}
</script>
GEN;

	/**
	 * The jQuery load of a view, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_JQUERY = <<<'GEN'

		// Load jQuery
		Html::_('jquery.framework');
GEN;

	/**
	 * The export button of a Joomla 3 list view, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_EXPORT_J3 = <<<'GEN'


			if ($this->canDo->get('core.export') && $this->canDo->get('demo.export'))
			{
				Joomla___0c1a176a_304f_433a_8233_37d01ff87815___Power::custom('demos.exportData', 'download', '', 'COM_DEMO_EXPORT_DATA', true);
			}
GEN;

	/**
	 * The import button of a Joomla 3 list view, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_IMPORT_J3 = <<<'GEN'


		if ($this->canDo->get('core.import') && $this->canDo->get('demo.import'))
		{
			Joomla___0c1a176a_304f_433a_8233_37d01ff87815___Power::custom('demos.importData', 'upload', '', 'COM_DEMO_IMPORT_DATA', false);
		}
GEN;

	/**
	 * The targets that were never given these buttons.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function modernVersions(): array
	{
		return [
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
			'Joomla 6' => ['JoomlaSix'],
		];
	}

	/**
	 * Every statement the compiler writes to clear one kind of field.
	 *
	 * @return  array<string, array{string, string}>
	 * @since   6.1.7
	 */
	public static function fieldKinds(): array
	{
		return [
			'text' => ['text', "jQuery('#jform_name').value = '';"],
			'password' => ['password', "jQuery('#jform_name').value = '';"],
			'textarea' => ['textarea', "jQuery('#jform_name').value = '';"],
			'radio' => ['radio', "jQuery('#jform_name').checked = false;"],
			'checkboxes' => ['checkboxes', "jQuery('#jform_name').selectedIndex = -1;"],
			'checkbox' => ['checkbox', "jQuery('#jform_name').selectedIndex = -1;"],
			'one it was never taught' => ['list', '']
		];
	}

	/**
	 * A view with check boxes is given the statements that put them back.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithCheckBoxesIsGivenTheStatementsThatPutThemBack(): void
	{
		$boxes = new CheckBox();
		$boxes->set('demo', ['published', 'featured']);

		$subject = $this->renderer(CheckboxSave::class, ['checkbox' => $boxes]);
		$view = 'demo';

		$this->assertSame(self::EXPECTED_CHECKBOX, $subject->get($view));
	}

	/**
	 * A view with no check boxes is given no statements.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithNoCheckBoxesIsGivenNoStatements(): void
	{
		$subject = $this->renderer(CheckboxSave::class, ['checkbox' => new CheckBox()]);
		$view = 'demo';

		$this->assertSame('', $subject->get($view));
	}

	/**
	 * API PATCH omission is preserved; complete forms still clear unchecked boxes.
	 *
	 * @param   bool    $api       Whether the request is an API request.
	 * @param   string  $method    The HTTP method.
	 * @param   array   $data      Validated input, possibly filtered by ACL.
	 * @param   array   $expected  Data presented to the remaining save pipeline.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('checkboxRequests')]
	public function testCheckboxDefaultsRespectPartialUpdates(bool $api, string $method, array $data, array $expected): void
	{
		$app = $this->createMock(CMSApplication::class);
		$app->method('isClient')->with('api')->willReturn($api);
		Factory::$application = $app;
		$input = new Input([]);
		$input->server->set('REQUEST_METHOD', $method);
		$boxes = new CheckBox();
		$boxes->set('demo', ['featured']);
		$view = 'demo';
		$code = $this->renderer(CheckboxSave::class, ['checkbox' => $boxes])->get($view);
		$code = str_replace('Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power', '\\Joomla\\CMS\\Factory', $code);
		$save = eval('return static function (array $data, $input): array {' . $code . ' return $data; };');

		$this->assertSame($expected, $save($data, $input));
	}

	/**
	 * Missing and explicitly supplied checkbox values across request types.
	 *
	 * @return  array<string, array{bool,string,array,array}>
	 * @since   6.2.0
	 */
	public static function checkboxRequests(): array
	{
		return [
			'API omission' => [true, 'PATCH', [], []],
			'API null' => [true, 'PATCH', ['featured' => null], ['featured' => null]],
			'API false' => [true, 'PATCH', ['featured' => false], ['featured' => false]],
			'API zero' => [true, 'PATCH', ['featured' => 0], ['featured' => 0]],
			'API clearing' => [true, 'PATCH', ['featured' => []], ['featured' => []]],
			'API create default' => [true, 'POST', [], ['featured' => '']],
			'administrator form' => [false, 'POST', [], ['featured' => '']],
			'administrator PATCH' => [false, 'PATCH', [], ['featured' => '']],
		];
	}

	/**
	 * A table watching its tags and its history observes both.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATableWatchingBothObservesBoth(): void
	{
		$subject = $this->renderer(Constructor::class, [
			'tags' => $this->watching(new Tags()),
			'history' => $this->watching(new History())
		]);
		$view = 'demo';

		$this->assertSame(self::EXPECTED_TABLE_BOTH, $subject->get($view));
	}

	/**
	 * A table watching only its tags observes only them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATableWatchingOnlyItsTagsObservesOnlyThem(): void
	{
		$subject = $this->renderer(Constructor::class, [
			'tags' => $this->watching(new Tags()),
			'history' => new History()
		]);
		$view = 'demo';

		$this->assertSame(self::EXPECTED_TABLE_TAGS, $subject->get($view));
	}

	/**
	 * A table watching neither observes nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATableWatchingNeitherObservesNothing(): void
	{
		$subject = $this->renderer(Constructor::class, [
			'tags' => new Tags(),
			'history' => new History()
		]);
		$view = 'demo';

		$this->assertSame('', $subject->get($view));
	}

	/**
	 * A view the compiler found a category for keeps it beside its alias.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithACategoryKeepsItBesideItsAlias(): void
	{
		$codes = new CategoryCode();
		$codes->set('demo.code', 'category');

		$subject = $this->renderer(Constructor::class, ['categorycode' => $codes]);
		$view = 'demo';

		$this->assertSame(", 'category' => \$this->category", $subject->aliasCategory($view));
	}

	/**
	 * A view with no category keeps nothing beside its alias.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithNoCategoryKeepsNothingBesideItsAlias(): void
	{
		$subject = $this->renderer(Constructor::class, [
			'categorycode' => new CategoryCode()
		]);
		$view = 'demo';

		$this->assertSame('', $subject->aliasCategory($view));
	}

	/**
	 * A custom view without a submit button of its own is given one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomViewWithoutASubmitButtonIsGivenOne(): void
	{
		$subject = $this->renderer(SubmitButtonScript::class);
		$view = $this->customView('a body with no submit button');

		$this->assertSame(self::EXPECTED_SUBMIT, $subject->get($view));
	}

	/**
	 * A custom view that submits itself is left to do it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomViewThatSubmitsItselfIsLeftToDoIt(): void
	{
		$subject = $this->renderer(SubmitButtonScript::class);
		$view = $this->customView('Joomla.submitbutton = function(task) {}');

		$this->assertSame('', $subject->get($view));
	}

	/**
	 * A custom view that was drawn with nothing is given no script.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomViewDrawnWithNothingIsGivenNoScript(): void
	{
		$subject = $this->renderer(SubmitButtonScript::class);
		$view = $this->customView('');

		$this->assertSame('', $subject->get($view));
	}

	/**
	 * Each kind of watched field is cleared the way that kind expects.
	 *
	 * @param   string  $type      The kind of field.
	 * @param   string  $expected  The statement that clears it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('fieldKinds')]
	public function testEachKindOfFieldIsClearedTheWayItExpects(string $type, string $expected): void
	{
		$subject = $this->renderer(ClearValueScript::class);

		$this->assertSame($expected, $subject->get($type, 'name', 'a1'));
	}

	/**
	 * Every view is given the jQuery load.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryViewIsGivenTheJqueryLoad(): void
	{
		$subject = $this->renderer(Jquery::class);
		$view = ['settings' => new stdClass()];

		$this->assertSame(self::EXPECTED_JQUERY, $subject->get($view));
	}

	/**
	 * A Joomla 3 list view that allows export is given the button.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAJoomlaThreeListViewThatAllowsExportIsGivenTheButton(): void
	{
		$subject = $this->renderer(J3EximportButtons::class, [
			'eximportview' => $this->allowed()
		]);

		$this->assertSame(self::EXPECTED_EXPORT_J3, $subject->export('demo', 'demos'));
		$this->assertSame(
			'Export Data', $this->language()->getTarget('admin')['COM_DEMO_EXPORT_DATA']
		);
	}

	/**
	 * A Joomla 3 list view that allows import is given the button.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAJoomlaThreeListViewThatAllowsImportIsGivenTheButton(): void
	{
		$subject = $this->renderer(J3EximportButtons::class, [
			'eximportview' => $this->allowed()
		]);

		$this->assertSame(self::EXPECTED_IMPORT_J3, $subject->import('demo', 'demos'));
		$this->assertSame(
			'Import Data', $this->language()->getTarget('admin')['COM_DEMO_IMPORT_DATA']
		);
	}

	/**
	 * A Joomla 3 list view that allows neither is given neither button.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAJoomlaThreeListViewThatAllowsNeitherIsGivenNeitherButton(): void
	{
		$subject = $this->renderer(J3EximportButtons::class, [
			'eximportview' => new EximportView()
		]);

		$this->assertSame('', $subject->export('demo', 'demos'));
		$this->assertSame('', $subject->import('demo', 'demos'));
		$this->assertSame([], $this->language()->getTarget('admin'));
	}

	/**
	 * Every later target is still given neither button.
	 *
	 * @param   string  $version  The target being built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testEveryLaterTargetIsGivenNeitherButton(string $version): void
	{
		$subject = $this->renderer(
			$this->targetClass($version, 'AdminViews\\EximportButtons', ['JoomlaThree'])
		);

		$this->assertSame('', $subject->export('demo', 'demos'));
		$this->assertSame('', $subject->import('demo', 'demos'));
	}

	/**
	 * A registry that says the demo view is watched.
	 *
	 * @param   object  $registry  The registry to say it in.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function watching(object $registry): object
	{
		$registry->set('demo', true);

		return $registry;
	}

	/**
	 * A registry that says the demos list view allows export and import.
	 *
	 * @return  EximportView
	 * @since   6.1.7
	 */
	private function allowed(): EximportView
	{
		$eximport = new EximportView();
		$eximport->set('demos', true);

		return $eximport;
	}

	/**
	 * A custom view drawn with the given body.
	 *
	 * @param   string  $default  What the view was drawn with.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function customView(string $default): array
	{
		$settings = new stdClass();
		$settings->default = $default;
		$settings->code = 'demo';

		return ['settings' => $settings];
	}
}
