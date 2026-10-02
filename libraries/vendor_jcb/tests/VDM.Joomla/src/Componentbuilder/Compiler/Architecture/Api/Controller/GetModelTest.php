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


use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Document\Document;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\User\User;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionProperty;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\GetModel;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The explicit model mapping of the API controllers.
 *
 * @since 6.1.7
 */
#[CoversClass(GetModel::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class GetModelTest extends ArchitectureTestCase
{
	/**
	 * The get model body of a view named demo and demos.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED = <<<'GEN'

		// The controller role selects its explicit native model.
		$name = 'demo';

		// The API carries no request state for the model, as the form controller does not:
		// the id a save sets must never be replaced by a later read of the request.
		if (!array_key_exists('ignore_request', $config))
		{
			$config['ignore_request'] = true;
		}

		return parent::getModel($name, $prefix, $config);
GEN;

	/**
	 * Existing two-argument renderer calls select the item model.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testExistingRendererCallsSelectTheItemModel(): void
	{
		$subject = $this->renderer(GetModel::class);

		$this->assertSame(self::EXPECTED, $subject->get('demo', 'demos'));
	}

	/**
	 * Only the declared resource role selects its explicit native model.
	 *
	 * @param   string  $single  The configured item model name.
	 * @param   string  $list    The configured list model name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modelNames')]
	public function testTheResourceRoleSelectsTheExplicitNativeModel(string $single, string $list): void
	{
		$subject = $this->renderer(GetModel::class);
		$itemCode = $subject->get($single, $list, false);
		$listCode = $subject->get($single, $list, true);

		$this->assertStringContainsString("\$name = '" . $single . "';", $itemCode);
		$this->assertStringNotContainsString("\$name = '" . $list . "';", $itemCode);
		$this->assertStringContainsString("\$name = '" . $list . "';", $listCode);
		$this->assertStringNotContainsString("\$name = '" . $single . "';", $listCode);
		$this->assertStringNotContainsString('contentType', $itemCode . $listCode);
		$this->assertStringNotContainsString('singular', $itemCode . $listCode);
	}

	/**
	 * Joomla's native CRUD and list entry points reach the model declared by the role.
	 *
	 * The MVC factory stops the native call at model creation, before database work.
	 * Joomla's own content-type inflection and request handling execute unchanged.
	 *
	 * @param   string  $single        The configured item model name.
	 * @param   string  $list          The configured list model name.
	 * @param   string  $method        The native Joomla controller entry point.
	 * @param   string  $requestModel  An optional request value that must not change the role.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('nativeEntryPoints')]
	public function testNativeEntryPointsUseTheDeclaredRole(
		string $single,
		string $list,
		string $method,
		string $requestModel
	): void
	{
		$calls = [];
		$factory = $this->modelFactory($calls);
		$request = $requestModel === '' ? [] : ['model' => $requestModel];
		$controller = $this->controller($single, $list, $method === 'displayList', $factory, $request);

		try
		{
			$controller->{$method}();
			$this->fail('The native controller did not reach the model factory.');
		}
		catch (RuntimeException $exception)
		{
			$this->assertSame('Model selection reached the MVC factory.', $exception->getMessage());
		}

		$this->assertCount(1, $calls);
		$this->assertSame($method === 'displayList' ? $list : $single, $calls[0]['name']);
		$this->assertSame('api', $calls[0]['prefix']);
		$this->assertTrue($calls[0]['config']['ignore_request']);

		if ($method === 'displayItem' || $method === 'displayList')
		{
			$this->assertInstanceOf(Registry::class, $calls[0]['config']['state']);
		}
	}

	/**
	 * Explicit caller configuration and model prefixes survive role selection.
	 *
	 * @param   bool  $isList  Whether the generated controller serves a list.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('roles')]
	public function testExplicitPrefixAndConfigurationArePreserved(bool $isList): void
	{
		$calls = [];
		$factory = $this->modelFactory($calls);
		$controller = $this->controller('entry', 'inventory', $isList, $factory);
		$config = ['ignore_request' => false, 'state' => new Registry(['custom' => 'value']), 'custom' => 'preserved'];

		try
		{
			$controller->getModel('unrelated_request_model', 'Administrator', $config);
			$this->fail('The generated getModel did not reach the model factory.');
		}
		catch (RuntimeException $exception)
		{
			$this->assertSame('Model selection reached the MVC factory.', $exception->getMessage());
		}

		$this->assertSame([[
			'name' => $isList ? 'inventory' : 'entry',
			'prefix' => 'Administrator',
			'config' => $config
		]], $calls);
	}

	/**
	 * Names cover ordinary, irregular, middle-plural and arbitrary resource pairs.
	 *
	 * @return  array<string, array{string,string}>
	 * @since   6.1.7
	 */
	public static function modelNames(): array
	{
		return [
			'regular' => ['demo', 'demos'],
			'irregular' => ['person', 'people'],
			'component configuration' => ['component_config', 'components_config'],
			'component dashboard' => ['component_dashboard', 'components_dashboard'],
			'library configuration' => ['library_config', 'libraries_config'],
			'arbitrary' => ['entry', 'inventory']
		];
	}

	/**
	 * Exercise native item CRUD and list calls with absent and adversarial model input.
	 *
	 * @return  array<string, array{string,string,string,string}>
	 * @since   6.1.7
	 */
	public static function nativeEntryPoints(): array
	{
		$cases = [];

		foreach (self::modelNames() as $label => [$single, $list])
		{
			foreach (['displayItem', 'add', 'edit', 'delete', 'displayList'] as $method)
			{
				$cases[$label . ' ' . $method] = [$single, $list, $method, ''];
				$cases[$label . ' ' . $method . ' request override'] = [$single, $list, $method, 'unrelated_model'];
			}
		}

		return $cases;
	}

	/**
	 * Both generated controller roles preserve explicit runtime configuration.
	 *
	 * @return  array<string, array{bool}>
	 * @since   6.1.7
	 */
	public static function roles(): array
	{
		return ['item' => [false], 'list' => [true]];
	}

	/**
	 * Record the real Joomla MVC factory boundary and stop before native database work.
	 *
	 * @param   array  $calls  Recorded factory calls.
	 *
	 * @return  MVCFactoryInterface
	 * @since   6.1.7
	 */
	private function modelFactory(array &$calls): MVCFactoryInterface
	{
		$factory = $this->createMock(MVCFactoryInterface::class);
		$factory->expects($this->once())->method('createModel')->willReturnCallback(
			static function ($name, $prefix, array $config) use (&$calls): void
			{
				$calls[] = ['name' => $name, 'prefix' => $prefix, 'config' => $config];
				throw new RuntimeException('Model selection reached the MVC factory.');
			}
		);

		return $factory;
	}

	/**
	 * Instantiate the rendered override on Joomla's real API controller.
	 *
	 * The view is an external boundary that is unused after the factory sentinel.
	 * Legacy model search paths are restored immediately after native construction.
	 *
	 * @param   string               $single   The configured item model name.
	 * @param   string               $list     The configured list model name.
	 * @param   bool                 $isList   Whether this controller serves a list.
	 * @param   MVCFactoryInterface  $factory  The native model factory boundary.
	 * @param   array                $request  Native request input.
	 *
	 * @return  ApiController
	 * @since   6.1.7
	 */
	private function controller(
		string $single,
		string $list,
		bool $isList,
		MVCFactoryInterface $factory,
		array $request = []
	): ApiController
	{
		$document = $this->createStub(Document::class);
		$document->method('getType')->willReturn('jsonapi');
		$user = $this->createStub(User::class);
		$user->method('authorise')->willReturn(true);
		$application = $this->createStub(CMSWebApplicationInterface::class);
		$application->method('getName')->willReturn('api');
		$application->method('getDocument')->willReturn($document);
		$application->method('getIdentity')->willReturn($user);
		$input = new Input($request);
		$config = [
			'name' => 'contract',
			'base_path' => $this->temporaryPath(),
			'default_view' => $isList ? $list : $single,
			'model_path' => [],
			'view_path' => []
		];
		$body = $this->renderer(GetModel::class)->get($single, $list, $isList);
		$template = <<<'PHP'
return new class($config, $factory, $application, $input) extends \Joomla\CMS\MVC\Controller\ApiController
{
	/**
	 * The native component owning this generated resource.
	 * @var    string
	 * @since  6.1.7
	 */
	protected $option = 'com_demo';

	/**
	 * The native pagination state context.
	 * @var    string
	 * @since  6.1.7
	 */
	protected $context = 'demo';

	/**
	 * The generated API resource type, independent of its model role.
	 * @var    string
	 * @since  6.1.7
	 */
	protected $contentType = MODEL_CONTENT_TYPE;

	/**
	 * Supply the unrelated view boundary before native model selection.
	 * @param   string  $name    Native view name.
	 * @param   string  $type    Native view format.
	 * @param   string  $prefix  Native namespace prefix.
	 * @param   array   $config  Native view configuration.
	 * @return  object
	 * @since   6.1.7
	 */
	public function getView($name = '', $type = '', $prefix = '', $config = [])
	{
		return new \stdClass();
	}

	/**
	 * Run the rendered native model selection without substituting its implementation.
	 * @param   string  $name    Native caller's requested model.
	 * @param   string  $prefix  Native namespace prefix.
	 * @param   array   $config  Native model configuration.
	 * @return  object|false
	 * @since   6.1.7
	 */
	public function getModel($name = '', $prefix = '', $config = [])
	{
MODEL_BODY
	}
};
PHP;
		$code = str_replace(['MODEL_CONTENT_TYPE', 'MODEL_BODY'], [var_export($list, true), $body], $template);
		$paths = new ReflectionProperty(BaseModel::class, 'paths');
		$previousPaths = $paths->getValue();

		try
		{
			return eval($code);
		}
		finally
		{
			$paths->setValue(null, $previousPaths);
		}
	}
}
