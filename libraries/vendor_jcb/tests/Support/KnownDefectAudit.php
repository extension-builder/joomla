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

namespace VDM\Tests\Support;


use PHPUnit\Event\Event;
use PHPUnit\Event\Test as TestEvent;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionStarted;
use PHPUnit\Event\Tracer\Tracer;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RuntimeException;


/**
 * Record clean recoveries separately from expected failures and diagnostic passes.
 *
 * Enabled only when the known-defect runner supplies an output path. Native
 * PHPUnit events include diagnostics absent from JUnit testcase outcomes.
 *
 * @since  6.2.0
 */
final class KnownDefectAudit implements Extension, Tracer
{
	/**
	 * Explicit output destination, absent in ordinary unit and coverage runs.
	 *
	 * @var    string|null
	 * @since  6.2.0
	 */
	private ?string $path = null;

	/**
	 * Number of cases selected by PHPUnit, including individual provider cases.
	 *
	 * @var    int
	 * @since  6.2.0
	 */
	private int $expected = 0;

	/**
	 * Whether native execution has started.
	 *
	 * @var    bool
	 * @since  6.2.0
	 */
	private bool $started = false;

	/**
	 * Per-case native outcomes and all diagnostics, including post-pass issues.
	 *
	 * @var    array<string, array{passed: bool, outcome: string|null, issues: array<string>}>
	 * @since  6.2.0
	 */
	private array $cases = [];

	/**
	 * Register the tracer only for an explicitly requested audit.
	 *
	 * @param   Configuration      $configuration  Native runner configuration.
	 * @param   Facade             $facade         Extension registration facade.
	 * @param   ParameterCollection $parameters    Extension parameters.
	 *
	 * @return  void
	 * @throws  RuntimeException  When the report cannot be initialized.
	 * @since   6.2.0
	 */
	public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
	{
		$path = getenv('JCB_KNOWN_DEFECT_AUDIT');
		if (!is_string($path) || $path === '')
		{
			return;
		}

		$this->path = $path;
		$directory = dirname($path);
		if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory))
		{
			throw new RuntimeException('Cannot create known-defect audit directory: ' . $directory);
		}

		// Replace stale evidence before execution: interruption must remain incomplete.
		$this->write(false);
		$facade->registerTracer($this);
	}

	/**
	 * Observe native outcomes without changing PHPUnit's test result or exit code.
	 *
	 * @param   Event  $event  The event emitted by PHPUnit.
	 *
	 * @return  void
	 * @throws  RuntimeException  When the report cannot be written.
	 * @since   6.2.0
	 */
	public function trace(Event $event): void
	{
		if ($event instanceof ExecutionStarted)
		{
			$this->started = true;
			$this->expected = $event->testSuite()->count();
			$selected = [];
			foreach ($event->testSuite()->tests() as $test)
			{
				$selected[$test->id()] = $this->cases[$test->id()] ?? $this->emptyCase();
			}
			$this->cases = $selected;
			$this->write(false);
			return;
		}

		if ($event instanceof ExecutionFinished)
		{
			$this->write(true);
			return;
		}

		$outcome = null;
		$issue = false;
		if ($event instanceof TestEvent\Passed)
		{
			$outcome = 'passed';
		}
		elseif ($event instanceof TestEvent\Failed || $event instanceof TestEvent\PreparationFailed)
		{
			$outcome = 'failed';
		}
		elseif ($event instanceof TestEvent\Errored || $event instanceof TestEvent\PreparationErrored)
		{
			$outcome = 'errored';
		}
		elseif ($event instanceof TestEvent\Skipped)
		{
			$outcome = 'skipped';
		}
		elseif ($event instanceof TestEvent\MarkedIncomplete)
		{
			$outcome = 'incomplete';
		}
		elseif (str_starts_with($event::class, 'PHPUnit\\Event\\Test\\')
			&& (str_ends_with($event::class, 'Triggered') || $event instanceof TestEvent\ConsideredRisky))
		{
			// Include user/PHP/PHPUnit notices, warnings, errors and deprecations.
			$issue = true;
		}
		else
		{
			return;
		}

		$id = $event->test()->id();
		$this->cases[$id] ??= $this->emptyCase();
		if ($outcome !== null)
		{
			$this->cases[$id]['outcome'] = $outcome;
			$this->cases[$id]['passed'] = $outcome === 'passed';
		}
		if ($issue)
		{
			$this->cases[$id]['issues'][] = substr($event::class, strrpos($event::class, '\\') + 1);
			$this->cases[$id]['issues'] = array_values(array_unique($this->cases[$id]['issues']));
		}
	}

	/**
	 * Create an outcome that has not yet finished.
	 *
	 * @return  array{passed: bool, outcome: null, issues: array}
	 * @since   6.2.0
	 */
	private function emptyCase(): array
	{
		return ['passed' => false, 'outcome' => null, 'issues' => []];
	}

	/**
	 * Persist auditable native outcomes; incomplete execution can never look green.
	 *
	 * @param   bool  $finished  Whether native ExecutionFinished was observed.
	 *
	 * @return  void
	 * @throws  RuntimeException  When JSON cannot be written completely.
	 * @since   6.2.0
	 */
	private function write(bool $finished): void
	{
		$completed = count(array_filter($this->cases, static fn (array $case): bool => $case['outcome'] !== null));
		$report = [
			'schema' => 1,
			'complete' => $finished && $this->started && $this->expected > 0
				&& count($this->cases) === $this->expected && $completed === $this->expected,
			'expected' => $this->expected,
			'completed' => $completed,
			'cases' => $this->cases,
		];
		$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
		if (file_put_contents($this->path, $json, LOCK_EX) !== strlen($json))
		{
			throw new RuntimeException('Cannot write complete known-defect audit: ' . $this->path);
		}
	}
}
