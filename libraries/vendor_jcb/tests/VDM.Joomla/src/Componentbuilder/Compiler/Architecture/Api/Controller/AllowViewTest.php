<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    1st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Controller;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Error\JsonApi\NotAllowedExceptionHandler;
use Joomla\CMS\Language\Language;
use Joomla\CMS\MVC\View\JsonApiView;
use Joomla\CMS\User\User;
use Tobscure\JsonApi\ErrorHandler;
use Tobscure\JsonApi\Exception\Handler\FallbackExceptionHandler;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\AllowView;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The view permission of the item API controller.
 *
 * @since 6.1.7
 */
#[CoversClass(AllowView::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class AllowViewTest extends ArchitectureTestCase
{
	/**
	 * The view check of a view without an access permission.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_OPEN = <<<'GEN'

		// In the absence of an access permission, every authenticated user may view.
		return true;
GEN;

	/**
	 * The view check of a view with an access permission.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_GUARDED = <<<'GEN'

		// Get user object.
		$user = $this->app->getIdentity();

		// Access check.
		return ($user->authorise('demo.access', 'com_demo.demo.' . $id) && $user->authorise('demo.access', 'com_demo'));
GEN;

	/**
	 * The stable shared item-model block for a component without API views.
	 * Space markers preserve legacy indentation without adding whitespace errors.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ITEM_EDIT = <<<'GEN'

			// check edit access permissions
			if (!empty($item->id) && !$this->allowEdit((array) $item))
			{
<ONE_SPACE>				$app = Factory::getApplication();
<TWO_SPACES>				$app->enqueueMessage(Text::_('COM_COMPONENTBUILDER_NOT_AUTHORISED'), 'error');
				$app->redirect('index.php?option=com_demo');
				return false;
			}
GEN;

	/**
	 * A view without an access permission lets every user view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutAnAccessPermissionLetsEveryUserView(): void
	{
		$subject = $this->renderer(AllowView::class);

		$this->assertSame(self::EXPECTED_OPEN, $subject->get('demo'));
	}

	/**
	 * A view with an access permission checks the record and the component.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithAnAccessPermissionChecksTheRecordAndTheComponent(): void
	{
		$subject = $this->renderer(AllowView::class, [
			'permission' => $this->permissionWith(['demo|core.access' => 'demo.access'], ['demo.access|demo' => 'demo'])
		]);

		$this->assertSame(self::EXPECTED_GUARDED, $subject->get('demo'));
	}

	/**
	 * An access permission of another view is not applied.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAccessPermissionOfAnotherViewIsNotApplied(): void
	{
		$subject = $this->renderer(AllowView::class, [
			'permission' => $this->permissionWith(['other|core.access' => 'other.access'], ['other.access|other' => 'other'])
		]);

		$this->assertSame(self::EXPECTED_OPEN, $subject->get('demo'));
	}

	/**
	 * Views without API resources retain the stable complete template bytes.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testNonApiItemGuardPreservesTheStableTemplateBytes(): void
	{
		$subject = $this->renderer(AllowView::class);

		$expected = str_replace(['<ONE_SPACE>', '<TWO_SPACES>'], [' ', '  '], self::EXPECTED_ITEM_EDIT);
		$this->assertSame($expected, $subject->getItemGuard('demo', false));
	}

	/**
	 * The original block remains authoritative on an API-disabled component.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testGeneratedTemplateWithoutApiResourcesRetainsTheEditGuard(): void
	{
		$app = $this->createMock(CMSApplication::class);
		$app->expects($this->never())->method('isClient');
		$app->expects($this->never())->method('getIdentity');
		$app->expects($this->once())->method('enqueueMessage')->with('Not authorised!', 'error');
		$app->expects($this->once())->method('redirect')->with('index.php?option=com_demo');
		$model = $this->generatedModel($app, [], false, 1, false);

		$this->assertFalse($model->getItem(42));
		$this->assertSame(1, $model->editChecks);
	}

	/**
	 * The actual generated template permits a reader without edit permission.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testGeneratedTemplatePermitsApiReadsWithoutEditPermission(): void
	{
		$user = $this->createMock(User::class);
		$user->expects($this->exactly(2))->method('authorise')->willReturnMap([
			['demo.access', 'com_demo.demo.42', true],
			['demo.access', 'com_demo', true],
		]);
		$app = $this->createMock(CMSApplication::class);
		$app->method('isClient')->willReturn(true);
		$app->method('getIdentity')->willReturn($user);
		$app->expects($this->never())->method('redirect');
		$app->expects($this->never())->method('enqueueMessage');
		$model = $this->generatedModel($app);

		$this->assertSame(42, $model->getItem(42)->id);
		$this->assertSame(0, $model->editChecks);
	}

	/**
	 * A native management action mapping is preserved without an extra fallback.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testGeneratedTemplateUsesTheMappedNativeManagementAction(): void
	{
		$user = $this->createMock(User::class);
		$user->expects($this->exactly(2))->method('authorise')->willReturnMap([
			['core.manage', 'com_demo.demo.42', true],
			['core.manage', 'com_demo', true],
		]);
		$app = $this->createStub(CMSApplication::class);
		$app->method('isClient')->willReturn(true);
		$app->method('getIdentity')->willReturn($user);
		$model = $this->generatedModel($app, [
			'permission' => $this->permissionWith(['demo|core.access' => 'core.manage'], ['core.manage|demo' => 'demo']),
		]);

		$this->assertSame(42, $model->getItem(42)->id);
		$this->assertSame(0, $model->editChecks);
	}

	/**
	 * Mapped read denials stay HTTP 403 even when edit permission is granted.
	 *
	 * @param   bool  $entityAllowed     The record asset permission.
	 * @param   bool  $componentAllowed  The component asset permission.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('readDenials')]
	public function testGeneratedTemplateRejectsMappedReadDenials(bool $entityAllowed, bool $componentAllowed): void
	{
		$user = $this->createStub(User::class);
		$user->method('authorise')->willReturnMap([
			['demo.access', 'com_demo.demo.42', $entityAllowed],
			['demo.access', 'com_demo', $componentAllowed],
		]);
		$app = $this->createMock(CMSApplication::class);
		$app->method('isClient')->willReturn(true);
		$app->method('getIdentity')->willReturn($user);
		$app->expects($this->never())->method('redirect');
		$app->expects($this->never())->method('enqueueMessage');
		$model = $this->generatedModel($app, [], true);

		$this->expectException(NotAllowed::class);
		$this->expectExceptionCode(403);
		$model->getItem(42);
	}

	/**
	 * Both native asset scopes must allow the read.
	 *
	 * @return  array<string, array{bool, bool}>  The denied combinations.
	 * @since   6.1.7
	 */
	public static function readDenials(): array
	{
		return ['entity denied' => [false, true], 'component denied' => [true, false]];
	}

	/**
	 * View-level access and the native options bypass match the list model.
	 *
	 * @param   int   $level           The item's native view level.
	 * @param   bool  $optionsAllowed  Whether the native bypass is allowed.
	 * @param   bool  $readAllowed     Whether this read should succeed.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('viewLevels')]
	public function testGeneratedTemplatePreservesNativeViewLevelPolicy(int $level, bool $optionsAllowed, bool $readAllowed): void
	{
		$access = new AccessSwitch();
		$access->set('demo', true);
		$user = $this->createStub(User::class);
		$user->method('authorise')->willReturnCallback(
			static function (string $action, string $asset) use ($optionsAllowed): bool
			{
				return $action !== 'core.options' || $optionsAllowed;
			}
		);
		$user->method('getAuthorisedViewLevels')->willReturn([1]);
		$app = $this->createMock(CMSApplication::class);
		$app->method('isClient')->willReturn(true);
		$app->method('getIdentity')->willReturn($user);
		$app->expects($this->never())->method('redirect');
		$model = $this->generatedModel($app, ['accessswitch' => $access], false, $level);

		if (!$readAllowed)
		{
			$this->expectException(NotAllowed::class);
			$this->expectExceptionCode(403);
		}

		$this->assertSame(42, $model->getItem(42)->id);
		$this->assertSame(0, $model->editChecks);
	}

	/**
	 * The native public level, a denied level, and the options bypass.
	 *
	 * @return  array<string, array{int, bool, bool}>  The view-level cases.
	 * @since   6.1.7
	 */
	public static function viewLevels(): array
	{
		return ['public' => [1, false, true], 'restricted' => [2, false, false], 'options bypass' => [2, true, true]];
	}

	/**
	 * Customized native access fields retain the configured view-level policy.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testConfiguredViewLevelsStillApplyToCustomNativeAccessField(): void
	{
		$access = new AccessSwitch();
		$access->set('demo', true);
		$names = new FieldNames();
		$names->set('demo.access', 'access');
		$user = $this->createStub(User::class);
		$user->method('authorise')->willReturnCallback(
			static function (string $action, string $asset): bool
			{
				return $action !== 'core.options';
			}
		);
		$user->method('getAuthorisedViewLevels')->willReturn([1]);
		$app = $this->createMock(CMSApplication::class);
		$app->method('isClient')->willReturn(true);
		$app->method('getIdentity')->willReturn($user);
		$app->expects($this->never())->method('redirect');
		$model = $this->generatedModel($app, ['accessswitch' => $access, 'fieldnames' => $names], false, 2);

		$this->expectException(NotAllowed::class);
		$this->expectExceptionCode(403);
		$model->getItem(42);
	}

	/**
	 * A custom access column stays data when native view levels are disabled.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testGeneratedTemplateDoesNotTreatCustomAccessFieldsAsViewLevels(): void
	{
		$access = new AccessSwitch();
		$names = new FieldNames();
		$names->set('demo.access', 'access');
		$user = $this->createMock(User::class);
		$user->expects($this->exactly(2))->method('authorise')->willReturn(true);
		$user->expects($this->never())->method('getAuthorisedViewLevels');
		$app = $this->createStub(CMSApplication::class);
		$app->method('isClient')->willReturn(true);
		$app->method('getIdentity')->willReturn($user);
		$model = $this->generatedModel($app, ['accessswitch' => $access, 'fieldnames' => $names], false, 'private-data');

		$this->assertSame('private-data', $model->getItem(42)->access);
	}

	/**
	 * Open views do not acquire an invented component management permission.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testGeneratedTemplateKeepsViewsWithoutAccessPermissionsOpen(): void
	{
		$app = $this->createMock(CMSApplication::class);
		$app->method('isClient')->willReturn(true);
		$app->expects($this->never())->method('getIdentity');
		$model = $this->generatedModel($app, ['permission' => $this->permissionWith([], [])]);

		$this->assertSame(42, $model->getItem(42)->id);
		$this->assertSame(0, $model->editChecks);
	}

	/**
	 * Administrator requests retain their edit guard, message and redirect.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testGeneratedTemplatePreservesAdministratorDenial(): void
	{
		$app = $this->createMock(CMSApplication::class);
		$app->method('isClient')->willReturn(false);
		$app->expects($this->never())->method('getIdentity');
		$app->expects($this->once())->method('enqueueMessage')->with('Not authorised!', 'error');
		$app->expects($this->once())->method('redirect')->with('index.php?option=com_demo');
		$model = $this->generatedModel($app);

		$this->assertFalse($model->getItem(42));
		$this->assertSame(1, $model->editChecks);
	}

	/**
	 * Administrator editors still receive the loaded record.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testGeneratedTemplatePreservesAdministratorEditorReads(): void
	{
		$app = $this->createMock(CMSApplication::class);
		$app->method('isClient')->willReturn(false);
		$app->expects($this->never())->method('redirect');
		$model = $this->generatedModel($app, [], true);

		$this->assertSame(42, $model->getItem(42)->id);
		$this->assertSame(1, $model->editChecks);
	}

	/**
	 * The native view and error handlers classify generated read denials as 403.
	 *
	 * @param   bool  $entityAllowed     The mapped record permission.
	 * @param   bool  $componentAllowed  The mapped component permission.
	 * @param   int   $level             The stored native view level.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('nativeReadDenials')]
	public function testGeneratedReadDenialsRemain403AtTheNativeJsonApiBoundary(bool $entityAllowed, bool $componentAllowed, int $level): void
	{
		$access = new AccessSwitch();
		$access->set('demo', true);
		$user = $this->createStub(User::class);
		$user->method('authorise')->willReturnMap([
			['demo.access', 'com_demo.demo.42', $entityAllowed],
			['demo.access', 'com_demo', $componentAllowed],
			['core.options', 'com_demo', false],
		]);
		$user->method('getAuthorisedViewLevels')->willReturn([1]);
		$app = $this->createMock(CMSApplication::class);
		$app->method('isClient')->willReturn(true);
		$app->method('getIdentity')->willReturn($user);
		$app->expects($this->never())->method('redirect');
		$app->expects($this->never())->method('enqueueMessage');
		$model = $this->generatedModel($app, ['accessswitch' => $access], true, $level);
		$view = new class(['name' => 'demo', 'contentType' => 'demos']) extends JsonApiView
		{
		};
		$view->setModel($model, true);
		$handler = new ErrorHandler();
		$handler->registerHandler(new NotAllowedExceptionHandler());
		$handler->registerHandler(new FallbackExceptionHandler(false));

		// Exception codes alone do not become HTTP statuses in Joomla JSON:API.
		$this->assertSame(500, $handler->handle(new \RuntimeException('Denied', 403))->getStatus());

		try
		{
			$view->displayItem();
			$this->fail('The native view must reject an inaccessible item.');
		}
		catch (\Exception $error)
		{
			$response = $handler->handle($error);
			$this->assertInstanceOf(NotAllowed::class, $error);
			$this->assertSame(403, $response->getStatus());
			$this->assertSame([['title' => 'Access Denied', 'code' => 403]], $response->getErrors());
			$this->assertSame(0, $model->editChecks);
		}
	}

	/**
	 * Native mapped assets and view levels each retain the denied response.
	 *
	 * @return  array<string, array{bool, bool, int}>  The independent denials.
	 * @since   6.1.7
	 */
	public static function nativeReadDenials(): array
	{
		return [
			'entity denied' => [false, true, 1],
			'component denied' => [true, false, 1],
			'view level denied' => [true, true, 2],
		];
	}

	/**
	 * Compile the actual protected template method against bounded fixtures.
	 *
	 * @param   object      $app        The request application.
	 * @param   array       $overrides  The renderer's configured field/ACL graph.
	 * @param   bool        $editable   The administrator edit permission.
	 * @param   int|string  $access     The item's access column value.
	 * @param   bool        $apiEnabled Whether this view has generated API resources.
	 *
	 * @return  GeneratedItemModelFixture  The template's emitted method.
	 * @since   6.1.7
	 */
	private function generatedModel(object $app, array $overrides = [], bool $editable = false, int|string $access = 1, bool $apiEnabled = true): GeneratedItemModelFixture
	{
		$subject = $this->renderer(AllowView::class, $overrides + [
			'permission' => $this->permissionWith(['demo|core.access' => 'demo.access'], ['demo.access|demo' => 'demo']),
		]);
		$template = file_get_contents(dirname(__DIR__, 10) . '/admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php');
		$this->assertSame(1, preg_match('/\tpublic function getItem\(\$pk = null\).*?\n\t\}###LINKEDVIEWMETHODS######LICENSE_LOCKED_SET_BOOL###/s', $template, $match));
		$method = str_replace('###ADMIN_VIEW_MODEL_ITEM_ACCESS###', $subject->getItemGuard('demo', $apiEnabled), $match[0]);
		$method = strtr($method, [
			'###component###' => 'demo',
			'Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power' => 'self',
			'Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power' => 'self',
			'Joomla___a87c432d_b5b4_428e_b7ff_14b51664c624___Power' => '\\Joomla\\Registry\\Registry',
		]);
		$method = preg_replace('/###[A-Z_]+###/', '', $method);
		$item = (object) ['id' => 42, 'access' => $access];
		$language = new Language('en-GB');
		$this->assertTrue($language->load('com_componentbuilder', dirname(__DIR__, 10) . '/admin', 'en-GB', true, false));
		$this->setJoomlaFactoryProperty('language', $language);
		$this->setJoomlaFactoryProperty('application', $app);

		// The stable import emits native aliases supplied by the complete model header.
		return eval('use Joomla\\CMS\\Factory; use Joomla\\CMS\\Language\\Text; '
			. 'return new class($item, $app, $editable) extends \\' . GeneratedItemModelFixture::class . ' {' . $method . '};');
	}
}
