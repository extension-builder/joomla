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

namespace VDM\Tests\Contract;


use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Exercise native PHPUnit events in subprocesses, including diagnostics after pass.
 *
 * @since  6.2.0
 */
#[CoversNothing]
final class KnownDefectAuditTest extends FilesystemTestCase
{
	/**
	 * Classify real native outcomes rather than assuming JUnit captures diagnostics.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNativeEventsIdentifyOnlyCleanRecoveries(): void
	{
		$result = $this->runFixture(self::fixture());
		$this->assertNotSame(0, $result['exit'], $result['output']);
		$report = $this->report();
		$this->assertTrue($report['complete']);
		$this->assertSame(10, $report['expected']);
		$this->assertSame(10, $report['completed']);
		$cases = $report['cases'];
		$this->assertSame(['passed' => true, 'outcome' => 'passed', 'issues' => []], $cases['AuditFixtureTest::testClean']);
		foreach ([
			'testWarning' => 'WarningTriggered',
			'testDeprecation' => 'DeprecationTriggered',
			'testNotice' => 'NoticeTriggered',
			'testPhpunitNotice' => 'PhpunitNoticeTriggered',
			'testRisky' => 'ConsideredRisky',
		] as $method => $issue)
		{
			$this->assertTrue($cases['AuditFixtureTest::' . $method]['passed']);
			$this->assertContains($issue, $cases['AuditFixtureTest::' . $method]['issues']);
		}
		$this->assertSame('failed', $cases['AuditFixtureTest::testFailure']['outcome']);
		$this->assertSame('errored', $cases['AuditFixtureTest::testError']['outcome']);
		$this->assertSame('skipped', $cases['AuditFixtureTest::testSkip']['outcome']);
		$this->assertSame('incomplete', $cases['AuditFixtureTest::testIncomplete']['outcome']);

		$check = $this->check();
		$this->assertSame(1, $check['exit']);
		$this->assertStringContainsString('- AuditFixtureTest::testClean', $check['output']);
		$this->assertStringNotContainsString('- AuditFixtureTest::testWarning', $check['output']);
		$this->assertStringNotContainsString('- AuditFixtureTest::testRisky', $check['output']);
	}

	/**
	 * Existing failures and diagnostic-bearing passes remain nonblocking.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFailureAndDiagnosticCasesDoNotRequirePromotion(): void
	{
		$result = $this->runFixture(self::fixture(), true, ['--filter', '/test(Warning|Deprecation|Notice|PhpunitNotice|Risky|Failure|Error|Skip|Incomplete)$/']);
		$this->assertNotSame(0, $result['exit']);
		$this->assertSame(9, $this->report()['completed']);
		$check = $this->check();
		$this->assertSame(0, $check['exit'], $check['output']);
	}

	/**
	 * A passing provider case must be promoted even when its sibling still fails.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testIndividualDataProviderCasesRemainDistinct(): void
	{
		$source = <<<'PHP'
<?php
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
#[CoversNothing]
#[Group('known-defect')]
final class AuditFixtureTest extends TestCase
{
	#[DataProvider('cases')]
	public function testContract(bool $recovered): void
	{
		$this->assertTrue($recovered);
	}
	public static function cases(): array
	{
		return ['recovered' => [true], 'still broken' => [false]];
	}
}
PHP;
		$this->assertNotSame(0, $this->runFixture($source)['exit']);
		$this->assertSame(2, $this->report()['completed']);
		$check = $this->check();
		$this->assertSame(1, $check['exit']);
		$this->assertStringContainsString('AuditFixtureTest::testContract#recovered', $check['output']);
		$this->assertStringNotContainsString('still broken', $check['output']);
	}

	/**
	 * A crash replaces stale success evidence and leaves an incomplete audit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testInterruptedExecutionCannotReuseACompleteReport(): void
	{
		$this->writeTemporaryFile('audit.json', '{"schema":1,"complete":true,"expected":1,"completed":1,"cases":{}}');
		$source = str_replace('$this->assertTrue(true);', 'exit(7);', self::fixture());
		$result = $this->runFixture($source, true, ['--filter', 'testClean']);
		$this->assertNotSame(0, $result['exit']);
		$this->assertStringContainsString('Premature end of PHP process', $result['output']);
		$this->assertFalse($this->report()['complete']);
		$check = $this->check();
		$this->assertSame(1, $check['exit']);
		$this->assertStringContainsString('incomplete', $check['output']);
	}

	/**
	 * Ordinary unit/coverage runs do not activate or write the audit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testUnsetEnvironmentLeavesOrdinaryRunsUnaffected(): void
	{
		$result = $this->runFixture(self::fixture(), false, ['--filter', 'testClean']);
		$this->assertSame(0, $result['exit'], $result['output']);
		$this->assertFileDoesNotExist($this->temporaryPath('audit.json'));
	}

	/**
	 * Missing, malformed or inconsistent audit evidence always blocks the report.
	 *
	 * @param   string|null  $json  Invalid report, or no file at all.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('invalidReports')]
	public function testCheckerRejectsUntrustworthyReports(?string $json): void
	{
		if ($json !== null)
		{
			$this->writeTemporaryFile('audit.json', $json);
		}
		$this->assertSame(1, $this->check()['exit']);
	}

	/**
	 * Invalid evidence does not silently become an empty recovery list.
	 *
	 * @return  array<string, array{string|null}>
	 * @since   6.2.0
	 */
	public static function invalidReports(): array
	{
		return [
			'missing file' => [null],
			'truncated JSON' => ['{"schema":1'],
			'empty selected group' => ['{"schema":1,"complete":true,"expected":0,"completed":0,"cases":{}}'],
			'missing outcome' => ['{"schema":1,"complete":true,"expected":1,"completed":0,"cases":{"x":{"passed":false,"outcome":null,"issues":[]}}}'],
			'inconsistent pass' => ['{"schema":1,"complete":true,"expected":1,"completed":1,"cases":{"x":{"passed":true,"outcome":"failed","issues":[]}}}'],
			'invalid diagnostic' => ['{"schema":1,"complete":true,"expected":1,"completed":1,"cases":{"x":{"passed":true,"outcome":"passed","issues":[false]}}}'],
		];
	}

	/**
	 * Create an isolated native PHPUnit run using only the ordinary Composer loader.
	 *
	 * @param   string         $source  Complete fixture class source.
	 * @param   bool           $audit   Whether the opt-in environment is present.
	 * @param   array<string>  $extra   Additional native PHPUnit arguments.
	 *
	 * @return  array{exit: int, output: string}
	 * @since   6.2.0
	 */
	private function runFixture(string $source, bool $audit = true, array $extra = []): array
	{
		$tests = dirname(__DIR__);
		$this->writeTemporaryFile('AuditFixtureTest.php', $source);
		$this->writeTemporaryFile('bootstrap.php', '<?php require ' . var_export($tests . '/vendor/autoload.php', true) . ';');
		$config = $this->writeTemporaryFile('phpunit.xml', <<<'XML'
<?xml version="1.0"?>
<phpunit bootstrap="bootstrap.php" cacheResult="false" colors="false" failOnRisky="true" failOnWarning="true" failOnNotice="true" failOnDeprecation="true" failOnPhpunitNotice="true">
	<extensions><bootstrap class="VDM\Tests\Support\KnownDefectAudit"/></extensions>
	<testsuites><testsuite name="audit"><file>AuditFixtureTest.php</file></testsuite></testsuites>
</phpunit>
XML);
		$environment = getenv();
		unset($environment['JCB_KNOWN_DEFECT_AUDIT']);
		if ($audit)
		{
			$environment['JCB_KNOWN_DEFECT_AUDIT'] = $this->temporaryPath('audit.json');
		}

		return $this->runProcess(array_merge([
			PHP_BINARY, $tests . '/vendor/bin/phpunit', '--configuration', $config,
			'--group', 'known-defect', '--no-coverage', '--do-not-cache-result',
		], $extra), $environment);
	}

	/**
	 * Read the native report written by the fixture process.
	 *
	 * @return  array
	 * @since   6.2.0
	 */
	private function report(): array
	{
		return json_decode(file_get_contents($this->temporaryPath('audit.json')), true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * Run the same evidence checker used by the blocking CI report step.
	 *
	 * @return  array{exit: int, output: string}
	 * @since   6.2.0
	 */
	private function check(): array
	{
		return $this->runProcess([
			PHP_BINARY, dirname(__DIR__) . '/bin/check-known-defect-recoveries.php',
			$this->temporaryPath('audit.json'),
		]);
	}

	/**
	 * Start a process without a shell and capture a bounded fixture transcript.
	 *
	 * @param   array<string>       $command      Executable and literal arguments.
	 * @param   array<string>|null  $environment  Explicit child environment.
	 *
	 * @return  array{exit: int, output: string}
	 * @throws  RuntimeException  When process creation fails.
	 * @since   6.2.0
	 */
	private function runProcess(array $command, ?array $environment = null): array
	{
		$output = $this->temporaryPath('process-output.txt');
		$process = proc_open($command, [1 => ['file', $output, 'w'], 2 => ['redirect', 1]], $pipes, $this->temporaryPath(), $environment);
		if (!is_resource($process))
		{
			throw new RuntimeException('Unable to start the known-defect audit fixture.');
		}

		return ['exit' => proc_close($process), 'output' => file_get_contents($output)];
	}

	/**
	 * Native outcomes and diagnostics that must not all be treated as clean passes.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	private static function fixture(): string
	{
		return <<<'PHP'
<?php
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
#[CoversNothing]
#[Group('known-defect')]
final class AuditFixtureTest extends TestCase
{
	public function testClean(): void
	{
		$this->assertTrue(true);
	}
	public function testWarning(): void
	{
		trigger_error('fixture warning', E_USER_WARNING);
		$this->assertTrue(true);
	}
	public function testDeprecation(): void
	{
		trigger_error('fixture deprecation', E_USER_DEPRECATED);
		$this->assertTrue(true);
	}
	public function testNotice(): void
	{
		trigger_error('fixture notice', E_USER_NOTICE);
		$this->assertTrue(true);
	}
	public function testPhpunitNotice(): void
	{
		$this->createMock(stdClass::class);
		$this->assertTrue(true);
	}
	public function testRisky(): void
	{
	}
	public function testFailure(): void
	{
		$this->assertTrue(false);
	}
	public function testError(): void
	{
		throw new RuntimeException('fixture error');
	}
	public function testSkip(): void
	{
		$this->markTestSkipped('fixture skip');
	}
	public function testIncomplete(): void
	{
		$this->markTestIncomplete('fixture incomplete');
	}
}
PHP;
	}
}
