<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


use InvalidArgumentException;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RuntimeException;


/**
 * Base test case with deterministic process-setting isolation.
 *
 * @since  1.0.0
 */
abstract class TestCase extends PHPUnitTestCase
{
	/**
	 * Original environment-variable state keyed by variable name.
	 *
	 * @var    array<string, array<string, mixed>>
	 * @since  1.0.0
	 */
	private array $environmentVariables = [];

	/**
	 * Timezone active before the current test.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private string $timezone = 'UTC';

	/**
	 * Working directory active before the current test.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private string $workingDirectory = '';

	/**
	 * Capture process settings that tests are allowed to change.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$workingDirectory = getcwd();

		if ($workingDirectory === false)
		{
			throw new RuntimeException('Unable to capture the current working directory before the test.');
		}

		$this->workingDirectory = $workingDirectory;
		$this->timezone = date_default_timezone_get();
	}

	/**
	 * Set or remove an environment variable for the current test.
	 *
	 * The process environment, $_ENV, and $_SERVER are restored during tear-down.
	 *
	 * @param   string       $name   The environment-variable name.
	 * @param   string|null  $value  The value, or null to remove the variable.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setEnvironmentVariable(string $name, ?string $value): void
	{
		if ($name === '' || str_contains($name, '=') || str_contains($name, "\0"))
		{
			throw new InvalidArgumentException('Environment-variable names must be non-empty and cannot contain "=" or null bytes.');
		}

		if (!array_key_exists($name, $this->environmentVariables))
		{
			$processValue = getenv($name);

			$this->environmentVariables[$name] = [
				'processExists' => $processValue !== false,
				'processValue' => $processValue === false ? null : $processValue,
				'envExists' => array_key_exists($name, $_ENV),
				'envValue' => $_ENV[$name] ?? null,
				'serverExists' => array_key_exists($name, $_SERVER),
				'serverValue' => $_SERVER[$name] ?? null
			];
		}

		if ($value === null)
		{
			putenv($name);
			unset($_ENV[$name], $_SERVER[$name]);

			return;
		}

		putenv($name . '=' . $value);
		$_ENV[$name] = $value;
		$_SERVER[$name] = $value;
	}

	/**
	 * Restore environment variables changed by the current test.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		foreach ($this->environmentVariables as $name => $state)
		{
			if ($state['processExists'])
			{
				putenv($name . '=' . $state['processValue']);
			}
			else
			{
				putenv($name);
			}

			if ($state['envExists'])
			{
				$_ENV[$name] = $state['envValue'];
			}
			else
			{
				unset($_ENV[$name]);
			}

			if ($state['serverExists'])
			{
				$_SERVER[$name] = $state['serverValue'];
			}
			else
			{
				unset($_SERVER[$name]);
			}
		}

		$this->environmentVariables = [];
		date_default_timezone_set($this->timezone);

		if ($this->workingDirectory !== getcwd() && !chdir($this->workingDirectory))
		{
			throw new RuntimeException('Unable to restore the working directory after the test: ' . $this->workingDirectory);
		}

		parent::tearDown();
	}
}
