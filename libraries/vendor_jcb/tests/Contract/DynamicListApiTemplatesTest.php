<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    5th November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Contract;


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\JsonapiDocument;
use Joomla\CMS\MVC\View\Event\OnGetApiFields;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\JsonApiView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Event\Dispatcher;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\Meta;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\PrepareItem;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\JoinStructure;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Execute generated dynamic list views through Joomla's real JSON:API pipeline.
 *
 * Application and model boundaries are injected without starting Joomla or a
 * database. Native field validation, events, collection serialization and
 * document rendering remain active, including the zero-row failure in #1330.
 *
 * @since  6.2.1
 */
#[CoversNothing]
final class DynamicListApiTemplatesTest extends ArchitectureTestCase
{
	/**
	 * Distinct generated namespaces within the randomized PHPUnit process.
	 *
	 * @var   int
	 * @since 6.2.1
	 */
	private static int $sequence = 0;

	/**
	 * Native URI cache restored after every generated view fixture.
	 *
	 * @var   array
	 * @since 6.2.1
	 */
	private array $uriInstances = [];

	/**
	 * Give native pagination a deterministic request without changing server globals.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$instances = new ReflectionProperty(Uri::class, 'instances');
		$this->uriInstances = $instances->getValue();
		$instances->setValue(null, ['SERVER' => new Uri('https://example.test/api/v1/demo/records')]);
	}

	/**
	 * Restore native URI state as well as compiler and Joomla Factory state.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	protected function tearDown(): void
	{
		(new ReflectionProperty(Uri::class, 'instances'))->setValue(null, $this->uriInstances);
		parent::tearDown();
	}

	/**
	 * Both source templates must render successful empty model results as collections.
	 *
	 * @param   int          $folder  Compiler template folder key.
	 * @param   array|false  $items   Empty model return value.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('emptyModelResults')]
	public function testEmptyModelResultsRenderAnEmptyCollection(int $folder, array|false $items): void
	{
		$model = $this->model();
		$model->expects($this->once())->method('getItems')->willReturn($items);
		$fields = [];
		$view = $this->view($folder, $model, $fields);
		$response = json_decode($view->displayList(), true, 512, JSON_THROW_ON_ERROR);

		$this->assertSame([], $response['data']);
		$this->assertSame([['id']], $fields);
		$this->assertSame(['matched' => 0], $response['meta']['summary']);
		$this->assertSame(0, $response['meta']['total-pages']);
		$this->assertSame('https://example.test/api/v1/demo/records', $response['links']['self']);
	}

	/**
	 * An explicitly supplied empty collection must not query the model again.
	 *
	 * @param   int  $folder  Compiler template folder key.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testSuppliedEmptyCollectionIsNotReloaded(int $folder): void
	{
		$model = $this->model();
		$model->expects($this->never())->method('getItems');
		$fields = [];
		$view = $this->view($folder, $model, $fields);
		$response = json_decode($view->displayList([]), true, 512, JSON_THROW_ON_ERROR);

		$this->assertSame([], $response['data']);
		$this->assertSame([['id']], $fields);
		$this->assertSame(['matched' => 0], $response['meta']['summary']);
	}

	/**
	 * Empty pages retain native pagination links and do not become errors.
	 *
	 * @param   int  $folder  Compiler template folder key.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testEmptyPageRetainsPaginationMetadata(int $folder): void
	{
		Uri::getInstance()->setVar('page', ['offset' => 60, 'limit' => 20]);
		$model = $this->model(2, 60);
		$model->expects($this->once())->method('getItems')->willReturn([]);
		$fields = [];
		$view = $this->view($folder, $model, $fields);
		$response = json_decode($view->displayList(), true, 512, JSON_THROW_ON_ERROR);

		$this->assertSame([], $response['data']);
		$this->assertSame(2, $response['meta']['total-pages']);
		$this->assertArrayHasKey('first', $response['links']);
		$this->assertArrayHasKey('previous', $response['links']);
		$this->assertArrayNotHasKey('next', $response['links']);
	}

	/**
	 * Nonempty rows preserve discovered custom fields, joins and prepared identifiers.
	 *
	 * @param   int  $folder  Compiler template folder key.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testPopulatedCollectionsKeepAllRuntimeAttributes(int $folder): void
	{
		$model = $this->model(1);
		$model->expects($this->once())->method('getItems')->willReturn([
			(object) ['id' => 42, 'name' => 'First', 'calculated' => 7],
			(object) ['name' => 'Second', 'joined' => [['name' => 'Child']]],
		]);
		$fields = [];
		$view = $this->view($folder, $model, $fields);
		$response = json_decode($view->displayList(), true, 512, JSON_THROW_ON_ERROR);

		$this->assertSame([['id', 'name', 'calculated', 'joined']], $fields);
		$this->assertCount(2, $response['data']);
		$this->assertSame('42', $response['data'][0]['id']);
		$this->assertSame('First', $response['data'][0]['attributes']['name']);
		$this->assertSame(7, $response['data'][0]['attributes']['calculated']);
		$this->assertSame('1', $response['data'][1]['id']);
		$this->assertSame([['name' => 'Child']], $response['data'][1]['attributes']['joined']);
		$this->assertSame(['matched' => 0], $response['meta']['summary']);
	}

	/**
	 * A real model exception remains a failure instead of an empty success response.
	 *
	 * @param   int  $folder  Compiler template folder key.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testModelExceptionsAreNotConvertedToEmptyCollections(int $folder): void
	{
		$model = $this->model(0, 0, false);
		$error = new RuntimeException('Dynamic query failed', 503);
		$model->expects($this->once())->method('getItems')->willThrowException($error);
		$fields = [];
		$view = $this->view($folder, $model, $fields);

		$this->expectExceptionObject($error);
		$view->displayList();
	}

	/**
	 * The parent still rejects stored model errors after the empty field fallback.
	 *
	 * @param   int  $folder  Compiler template folder key.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testStoredModelErrorsStillFailThroughTheNativeParent(int $folder): void
	{
		$model = $this->model(0, 0, true, ['Database read failed']);
		$model->expects($this->once())->method('getItems')->willReturn([]);
		$fields = [];
		$view = $this->view($folder, $model, $fields);

		$this->expectException(GenericDataException::class);
		$this->expectExceptionMessage('Database read failed');
		$this->expectExceptionCode(500);
		$view->displayList();
	}

	/**
	 * Joomla 4 and 5 share folder 4; Joomla 6 has its own source template.
	 *
	 * @return  array<string, array{int}>
	 * @since   6.2.1
	 */
	public static function templates(): array
	{
		return ['Joomla 4 and 5' => [4], 'Joomla 6' => [6]];
	}

	/**
	 * Dynamic models can return an empty array or the existing false sentinel.
	 *
	 * @return  array<string, array{int, array|false}>
	 * @since   6.2.1
	 */
	public static function emptyModelResults(): array
	{
		return [
			'Joomla 4 and 5 empty array' => [4, []],
			'Joomla 4 and 5 false sentinel' => [4, false],
			'Joomla 6 empty array' => [6, []],
			'Joomla 6 false sentinel' => [6, false],
		];
	}

	/**
	 * Inject model boundaries without constructing a database-backed list model.
	 *
	 * @param   int    $pages    Native page count.
	 * @param   int    $offset   Native pagination offset.
	 * @param   bool   $summary  Whether rendering should reach the custom metadata get.
	 * @param   array  $errors   Errors consumed by the native API view.
	 *
	 * @return  DynamicListApiModelFixture
	 * @since   6.2.1
	 */
	private function model(int $pages = 0, int $offset = 0, bool $summary = true, array $errors = []): DynamicListApiModelFixture
	{
		$pagination = $this->createStub(Pagination::class);
		$pagination->pagesTotal = $pages;
		$pagination->limitstart = $offset;
		$pagination->limit = 20;
		$model = $this->createMock(DynamicListApiModelFixture::class);
		$model->method('getName')->willReturn('records');
		$model->method('getPagination')->willReturn($pagination);
		$model->method('getErrors')->willReturn($errors);
		$model->expects($summary ? $this->once() : $this->never())->method('getSummary')->willReturn(['matched' => 0]);

		return $model;
	}

	/**
	 * Render the authoritative template with real compiler prepare/meta fragments.
	 *
	 * @param   int                        $folder  Compiler template folder key.
	 * @param   DynamicListApiModelFixture  $model   Native model boundary.
	 * @param   array                      $fields  Field-list events observed during rendering.
	 *
	 * @return  JsonApiView
	 * @since   6.2.1
	 */
	private function view(int $folder, DynamicListApiModelFixture $model, array &$fields): JsonApiView
	{
		$dispatcher = new Dispatcher();
		$dispatcher->addListener('onApiGetFields', static function (OnGetApiFields $event) use (&$fields): void
		{
			$fields[] = $event->getAllPropertiesToRender();
		});
		$app = $this->createStub(CMSApplication::class);
		$app->method('getDispatcher')->willReturn($dispatcher);
		$this->setJoomlaApplication($app);
		$resource = [
			'code' => 'records',
			'settings' => (object) [
				'main_get' => (object) ['gettype' => 2],
				'custom_get' => [(object) ['gettype' => 3, 'getcustom' => 'getSummary']],
			],
		];
		$template = file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . '/compiler/joomla_' . $folder . '/API_DYNAMIC_VIEWS_JSON.php');
		$this->assertIsString($template);
		$namespace = 'DynamicListApiFixture' . ++self::$sequence;
		$code = substr($template, strpos($template, '###BOM###') + strlen('###BOM###'));
		$code = strtr($code, [
			'###NAMESPACEPREFIX###' => $namespace,
			'###ComponentNamespace###' => 'Demo',
			'###Component###' => 'Demo',
			'###ApiName###' => 'Records',
			'###sview###' => 'records',
			'###API_DYNAMIC_VIEWS_JSON_HEADER###' => 'use Joomla\\CMS\\MVC\\View\\JsonApiView as BaseApiView;',
			'###API_DYNAMIC_VIEWS_JSON_PREPAREITEM###' => (new PrepareItem(new JoinStructure()))->get($resource, true),
			'###API_DYNAMIC_VIEWS_JSON_META###' => (new Meta())->get($resource),
		]);
		$this->assertDoesNotMatchRegularExpression('/###[A-Za-z0-9_]+###/', $code);
		eval($code);
		$class = $namespace . '\\Component\\Demo\\Api\\View\\Records\\JsonapiView';
		$view = new $class(['name' => 'records', 'contentType' => 'records', 'option' => 'com_demo']);
		$view->setModel($model, true);
		$view->setDocument(new JsonapiDocument(['webAssetManager' => $this->createStub(WebAssetManager::class)]));

		return $view;
	}
}
