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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\AdminViews;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\EditView;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\AllowView;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\ValidationFix;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ValidationFix as ValidationFixRegistry;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * What the edit view of one admin view is made of.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class EditViewTest extends ArchitectureTestCase
{
	/**
	 * What was written for each view.
	 *
	 * @var    ContentMulti|null
	 * @since  6.1.7
	 */
	private ?ContentMulti $multi = null;

	/**
	 * What was written for the component as a whole.
	 *
	 * @var    ContentOne|null
	 * @since  6.1.7
	 */
	private ?ContentOne $one = null;

	/**
	 * A view the component gave a single name is given an edit view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithASingleNameIsGivenAnEditView(): void
	{
		$this->build($this->view('demo'));

		$written = $this->multi->get('demo');

		$this->assertIsArray($written);
		$this->assertNotSame([], $written);
	}

	/**
	 * A view the component gave no single name is given nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithNoSingleNameIsGivenNothing(): void
	{
		$this->build($this->view(null));

		$this->assertSame([], $this->multi->allActive());
	}

	/**
	 * A view named "null" is treated as having no single name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewNamedNullIsTreatedAsHavingNoSingleName(): void
	{
		$this->build($this->view('null'));

		$this->assertSame([], $this->multi->allActive());
	}

	/**
	 * The modal a view is picked through is given the defaults it needs.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheModalIsGivenTheDefaultsItNeeds(): void
	{
		$this->build($this->view('demo'));

		$written = $this->multi->get('demo');

		$this->assertSame('id', $written['###SQL_TITLE_KEY###']);
		$this->assertSame('name', $written['###SQL_TITLE_COLUMN###']);
	}

	/**
	 * The edit view is told what to do before and after it is built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheEventsAreFiredAroundTheBuild(): void
	{
		$fired = [];
		$event = $this->createStub(
			'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\EventInterface'
		);
		$event->method('trigger')->willReturnCallback(
			static function (string $name, $data = null) use (&$fired): void
			{
				$fired[] = $name;
			}
		);

		$this->build($this->view('demo'), ['event' => $event]);

		$this->assertContains('jcb_ce_onBeforeBuildAdminEditViewContent', $fired);
		$this->assertContains('jcb_ce_onAfterBuildAdminEditViewContent', $fired);
	}

	/**
	 * The api controller and view are given their bodies.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheApiControllerAndViewAreGivenTheirBodies(): void
	{
		$this->build($this->view('demo'));

		$written = $this->multi->get('demo');

		$this->assertStringContainsString("\$name = 'demo';", $written['###API_VIEW_CONTROLLER_GETMODEL###']);
		$this->assertStringNotContainsString("\$name = 'demos';", $written['###API_VIEW_CONTROLLER_GETMODEL###']);
		$this->assertStringContainsString('return parent::getModel($name, $prefix, $config);', $written['###API_VIEW_CONTROLLER_GETMODEL###']);
		$this->assertStringContainsString("\$id = \$this->input->getInt('id', 0);", $written['###API_VIEW_CONTROLLER_RECORDID###']);
		$this->assertStringContainsString('return true;', $written['###API_VIEW_CONTROLLER_ALLOWVIEW###']);
		$this->assertStringContainsString('if (!empty($item->id) && !$this->allowEdit((array) $item))', $written['###ADMIN_VIEW_MODEL_ITEM_ACCESS###']);
		$this->assertStringContainsString("return \$user->authorise('core.delete', \$this->option);", $written['###API_VIEW_CONTROLLER_ALLOWDELETE###']);
		$this->assertStringContainsString("\t\t'id',", $written['###API_VIEW_JSON_FIELDS###']);
		$this->assertSame('', $written['###API_VIEW_JSON_PERMISSIONS###']);
		$this->assertSame('', $written['###API_VIEW_JSON_PREPAREITEM###']);
		$this->assertStringContainsString("\t\t'created_by',", $written['###API_VIEW_JSON_RELATIONSHIP###']);
		$this->assertStringContainsString('public function createdBy($item)', $written['###API_VIEW_SERIALIZER_RELATIONS###']);
		$this->assertArrayHasKey('###API_VIEW_SERIALIZER_HEADER###', $written);
	}

	/**
	 * The shared admin model gets its native guard even without generated routes.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAdminViewWithoutApiRoutesStillReceivesItsModelReadGuard(): void
	{
		$view = $this->view('demo');
		$view['add_api'] = 0;
		$access = new AccessSwitch();
		$access->set('demo', true);
		$this->build($view, [
			'apiallowview' => $this->renderer(AllowView::class, [
				'accessswitch' => $access,
				'permission' => $this->permissionWith(['demo|core.access' => 'demo.access'], ['demo.access|demo' => 'demo']),
			]),
		]);
		$written = $this->multi->get('demo');

		$this->assertStringContainsString('if (!empty($item->id) && !$this->allowEdit((array) $item))', $written['###ADMIN_VIEW_MODEL_ITEM_ACCESS###']);
		$this->assertStringNotContainsString("isClient('api')", $written['###ADMIN_VIEW_MODEL_ITEM_ACCESS###']);
		$this->assertStringNotContainsString('getAuthorisedViewLevels()', $written['###ADMIN_VIEW_MODEL_ITEM_ACCESS###']);
	}

	/**
	 * Only API-enabled views receive the native read/client branch.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnApiEnabledViewReceivesItsModelReadGuard(): void
	{
		$view = $this->view('demo');
		$view['add_api'] = 2;
		$access = new AccessSwitch();
		$access->set('demo', true);
		$this->build($view, [
			'apiallowview' => $this->renderer(AllowView::class, [
				'accessswitch' => $access,
				'permission' => $this->permissionWith(['demo|core.access' => 'demo.access'], ['demo.access|demo' => 'demo']),
			]),
		]);
		$written = $this->multi->get('demo');

		$this->assertStringContainsString("\$user->authorise('demo.access', 'com_demo.demo.' . \$item->id)", $written['###ADMIN_VIEW_MODEL_ITEM_ACCESS###']);
		$this->assertStringContainsString('getAuthorisedViewLevels()', $written['###ADMIN_VIEW_MODEL_ITEM_ACCESS###']);
		$this->assertStringContainsString("'JERROR_ALERTNOAUTHOR'), 403)", $written['###ADMIN_VIEW_MODEL_ITEM_ACCESS###']);
	}

	/**
	 * Conditional definitions occupy the property area in every generated model.
	 *
	 * @param   int     $target    The generated Joomla major.
	 * @param   string  $template  The administrator or site edit model template.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('modelTemplates')]
	public function testConditionalPropertiesPrecedeMethodsInEveryModelTemplate(int $target, string $template): void
	{
		$this->config()->set('joomla_version', $target);
		$definitions = new ValidationFixRegistry();
		$definitions->set('demo', ['details']);
		$definitions->setConditions('demo', [[
			'matches' => [[
				'name' => 'mode',
				'behavior' => 1,
				'options' => ['expert'],
				'array' => true,
				'user' => false,
				'checkbox' => false,
				'supported' => true,
			]],
			'targets' => ['details'],
			'show' => true,
			'toggle' => true,
		]]);
		$this->build($this->view('demo'), [
			'validationfix' => $this->renderer(ValidationFix::class, [
				'validationfix' => $definitions,
			]),
		]);
		$written = $this->multi->get('demo');
		$properties = $written['###VALIDATION_PROPERTIES###'];
		$methods = $written['###VALIDATIONFIX###'];
		$source = file_get_contents(JCB_PROJECT_ROOT . '/admin/compiler/' . $template);

		$this->assertIsString($source);
		$this->assertSame(1, substr_count($source, '###VALIDATION_PROPERTIES###'));
		$this->assertSame(1, substr_count($source, '###VALIDATIONFIX###'));
		$this->assertStringContainsString('protected $conditionalRequirements = [', $properties);
		$this->assertStringContainsString("'name' => 'mode'", $properties);
		$this->assertStringContainsString("'details'", $properties);
		$this->assertStringNotContainsString('function ', $properties);
		$this->assertStringContainsString('public function validate(', $methods);
		$this->assertStringNotContainsString('protected $conditionalRequirements', $methods);

		$generated = strtr($source, [
			'###VALIDATION_PROPERTIES###' => $properties,
			'###VALIDATIONFIX###' => $methods,
		]);
		$propertyOffset = strpos($generated, 'protected $conditionalRequirements');
		$tableOffset = strpos($generated, 'public function getTable(');
		$this->assertIsInt($propertyOffset);
		$this->assertIsInt($tableOffset);
		$this->assertGreaterThan(strpos($generated, 'public $typeAlias'), $propertyOffset);
		$this->assertLessThan($tableOffset, $propertyOffset);
		$this->assertStringNotContainsString('###VALIDATION_PROPERTIES###', $generated);
		$this->assertStringNotContainsString('###VALIDATIONFIX###', $generated);
	}

	/**
	 * Views without conditions add no validation properties or model methods.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testViewsWithoutConditionsReceiveEmptyValidationFragments(): void
	{
		$this->build($this->view('demo'));
		$written = $this->multi->get('demo');

		$this->assertSame('', $written['###VALIDATION_PROPERTIES###']);
		$this->assertSame('', $written['###VALIDATIONFIX###']);
	}

	/**
	 * Both edit model locations remain covered for every supported compile target.
	 *
	 * @return  array<string, array{int, string}>
	 * @since   6.2.1
	 */
	public static function modelTemplates(): array
	{
		return [
			'Joomla 3 administrator' => [3, 'joomla_3/AdminModel.php'],
			'Joomla 3 site' => [3, 'joomla_3/AdminModel_site.php'],
			'Joomla 4 administrator' => [4, 'joomla_4/ADMIN_VIEW_MODEL.php'],
			'Joomla 4 site' => [4, 'joomla_4/SITE_ADMIN_VIEW_MODEL.php'],
			'Joomla 5 administrator' => [5, 'joomla_4/ADMIN_VIEW_MODEL.php'],
			'Joomla 5 site' => [5, 'joomla_4/SITE_ADMIN_VIEW_MODEL.php'],
			'Joomla 6 administrator' => [6, 'joomla_6/ADMIN_VIEW_MODEL.php'],
			'Joomla 6 site' => [6, 'joomla_6/SITE_ADMIN_VIEW_MODEL.php'],
		];
	}

	/**
	 * Build the edit view of one admin view.
	 *
	 * @param   array  $view       The view being built.
	 * @param   array  $overrides  What the compiler collected.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function build(array $view, array $overrides = []): void
	{
		$this->multi = new ContentMulti();
		$this->one = new ContentOne();

		$subject = $this->renderer(EditView::class, $overrides + [
			'contentmulti' => $this->multi,
			'contentone' => $this->one
		]);

		$single = 'demo';
		$list = 'demos';
		$subject->build($view, $single, $list);
	}

	/**
	 * A view the compiler collected.
	 *
	 * @param   string|null  $single  Its single name, when it has one.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(?string $single): array
	{
		$settings = new stdClass();
		$settings->name_single_code = 'demo';
		$settings->name_list_code = 'demos';
		$settings->add_fadein = 1;

		if ($single !== null)
		{
			$settings->name_single = $single;
		}

		return ['settings' => $settings];
	}
}
