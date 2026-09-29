<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    29th September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Contract;


use Joomla\CMS\Language\Language;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\LogEntry;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use TypeError;
use VDM\Component\Componentbuilder\Administrator\Model\AjaxModel;
use VDM\Joomla\Componentbuilder\Extrusion\Factory;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Tests\Support\JoomlaTestCase;


require_once dirname(__DIR__, 4) . '/admin/src/Model/AjaxModel.php';


/**
 * The generated AJAX boundary's diagnostic serialization contract.
 *
 * Exercise the actual protected serialization seams without constructing an
 * installed administrator model. Only Joomla logging and the factory's report
 * dependency are isolated. Browser specs separately drive real public requests.
 *
 * @since  6.2.1
 */
#[CoversClass(AjaxModel::class)]
final class ExtrusionAjaxDiagnosticsTest extends JoomlaTestCase
{
	/**
	 * The real model, without its installed-application constructor.
	 *
	 * @var    AjaxModel
	 * @since  6.2.1
	 */
	private AjaxModel $model;

	/**
	 * The private engine report.
	 *
	 * @var    Report
	 * @since  6.2.1
	 */
	private Report $report;

	/**
	 * Captured structured server diagnostics.
	 *
	 * @var    array<int, array>
	 * @since  6.2.1
	 */
	private array $entries = [];

	/**
	 * The exact logger singleton to restore after the test.
	 *
	 * @var    Log|null
	 * @since  6.2.1
	 */
	private ?Log $originalLog = null;

	/**
	 * Isolate application, report and logger dependencies.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->isolateFactory(Factory::class);
		$this->setJoomlaFactoryProperty('language', new Language('en-GB'));
		$this->report = new Report(['counts' => ['powers' => ['parsed' => 7, 'parse_reused' => 4]]]);
		$container = new Container();
		$container->set('Extrusion.Registry.Report', $this->report);
		(new ReflectionProperty(Factory::class, 'container'))->setValue(null, $container);
		$this->model = (new ReflectionClass(AjaxModel::class))->newInstanceWithoutConstructor();
		$this->originalLog = (new ReflectionProperty(Log::class, 'instance'))->getValue();
		$logger = $this->getStubBuilder(Log::class)->disableOriginalConstructor()
			->onlyMethods(['addLoggerInternal', 'addLogEntry'])->getStub();
		$logger->method('addLogEntry')->willReturnCallback(function (LogEntry $entry): void
		{
			$this->assertSame('com_componentbuilder.extrusion', $entry->category);
			$this->entries[] = json_decode($entry->message, true, 512, JSON_THROW_ON_ERROR);
		});
		Log::setInstance($logger);
	}

	/**
	 * Restore logger state even when an assertion fails.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	protected function tearDown(): void
	{
		Log::setInstance($this->originalLog);
		parent::tearDown();
	}

	/**
	 * Unexpected messages are private; known validation explanations remain useful.
	 *
	 * @param   array   $plan       The engine's private failure record.
	 * @param   string  $phase      The phase reported publicly and in the log.
	 * @param   string  $completed  The last completed phase.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('failures')]
	public function testUnexpectedEngineErrorsAreSanitizedWithoutChangingThePrivateReport(array $plan, string $phase, string $completed): void
	{
		$plan['blockers'][] = ['key' => 'approval.unknown', 'reason' => 'Review the unknown scope before writing.'];
		$this->report->set('plan', $plan);
		$private = $this->report->toArray();
		$method = new ReflectionMethod(AjaxModel::class, 'extrusionPublicReport');
		$public = $method->invoke($this->model);
		$this->assertStringNotContainsString('secret-marker', json_encode($public));
		$this->assertSame($private, $this->report->toArray(), 'Serialization must not rewrite the engine evidence.');
		$this->assertSame($plan['status'], $public['plan']['status']);
		$this->assertContains('Review the unknown scope before writing.', array_column($public['plan']['blockers'], 'reason'));
		$this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/D', $public['failure']['reference']);
		$this->assertSame($phase, $public['failure']['phase']);
		$this->assertSame($completed, $public['failure']['last_completed_phase']);
		$this->assertSame($public, $method->invoke($this->model), 'The same failure has one reference across response sections.');
		$this->assertCount(1, $this->entries);
		$this->assertSame($public['failure']['reference'], $this->entries[0]['reference']);
		$this->assertSame(7, $this->entries[0]['counters']['powers.parsed']);
		$this->assertSame(4, $this->entries[0]['counters']['powers.parse_reused']);
		$this->assertStringNotContainsString('secret-marker', json_encode($this->entries));
	}

	/**
	 * Every currently reported unexpected engine failure field.
	 *
	 * @return  array<string, array{array, string, string}>  The private records and phase expectations.
	 * @since   6.2.1
	 */
	public static function failures(): array
	{
		return [
			'component preparation' => [[
				'status' => 'blocked',
				'blockers' => [['key' => 'component.prepare', 'reason' => '/private/secret-marker.php stack trace']]
			], 'prepare', 'configured'],
			'Power preparation' => [[
				'status' => 'blocked',
				'blockers' => [['key' => 'powers.prepare', 'reason' => 'SQL credentials secret-marker']]
			], 'prepare', 'configured'],
			'commit and rollback' => [[
				'status' => 'failed',
				'error' => 'Database secret-marker',
				'rollback_error' => 'Rollback secret-marker',
				'blockers' => []
			], 'commit', 'prepared']
		];
	}

	/**
	 * The normal report and deliberate blockers do not become generic failures.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testBusinessBlockersRemainUnchangedAndDoNotLogUnexpectedErrors(): void
	{
		$this->report->set('plan', [
			'status' => 'blocked',
			'blockers' => [['key' => 'approval.stale', 'reason' => 'The source changed; review the new plan.']]
		]);
		$private = $this->report->toArray();
		$public = (new ReflectionMethod(AjaxModel::class, 'extrusionPublicReport'))->invoke($this->model);
		$this->assertSame($private, $public);
		$this->assertSame([], $this->entries);
	}

	/**
	 * PHP Errors are reported as safely as ordinary exceptions.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testThrownPhpErrorsExposeOnlySafeDiagnosticFields(): void
	{
		$public = (new ReflectionMethod(AjaxModel::class, 'extrusionFailure'))->invoke(
			$this->model, new TypeError('secret-marker in /private/source.php'), 'configure', 'validated'
		);
		$this->assertStringNotContainsString('secret-marker', json_encode($public));
		$this->assertStringNotContainsString('/private/', json_encode($public));
		$this->assertSame('operation', $public['failure']['kind']);
		$this->assertSame('configure', $public['failure']['phase']);
		$this->assertSame('validated', $public['failure']['last_completed_phase']);
		$this->assertSame(TypeError::class, $this->entries[0]['exception']);
		$this->assertSame($public['failure']['reference'], $this->entries[0]['reference']);
	}
}
