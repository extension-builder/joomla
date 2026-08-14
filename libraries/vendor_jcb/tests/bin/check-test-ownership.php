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

use VDM\Tests\Support\SourceInventory;


$testsRoot = dirname(__DIR__);
$repositoryRoot = dirname(__DIR__, 4);

require_once $testsRoot . '/Support/SourceInventory.php';

$options = getopt('', ['base:', 'help']);

if (isset($options['help']))
{
	fwrite(
		STDOUT,
		"Usage: php bin/check-test-ownership.php [--base=<git-sha>]\n\n"
		. "The base SHA may also be supplied through JCB_TEST_OWNERSHIP_BASE_SHA "
		. "or GITHUB_BASE_SHA.\n"
	);

	exit(0);
}

$baseSha = $options['base'] ?? null;

if (is_array($baseSha))
{
	fwrite(STDERR, "The --base option may only be supplied once.\n");
	exit(2);
}

if (!is_string($baseSha) || trim($baseSha) === '')
{
	$baseSha = getenv('JCB_TEST_OWNERSHIP_BASE_SHA');
}

if (!is_string($baseSha) || trim($baseSha) === '')
{
	$baseSha = getenv('GITHUB_BASE_SHA');
}

$baseSha = is_string($baseSha) ? trim($baseSha) : null;

try
{
	$inventory = SourceInventory::discover();
	$baseline = require $testsRoot . '/coverage-baseline.php';
	$ownership = require $testsRoot . '/test-ownership.php';

	if (!is_array($baseline))
	{
		throw new RuntimeException('coverage-baseline.php must return an array.');
	}

	if (!is_array($ownership))
	{
		throw new RuntimeException('test-ownership.php must return an array.');
	}

	$errors = SourceInventory::validate($inventory, $baseline, $ownership, $testsRoot);

	if ($baseSha !== null)
	{
		$baselinePaths = [];

		foreach ($baseline as $path)
		{
			if (is_string($path))
			{
				$baselinePaths[$path] = true;
			}
		}

		foreach (SourceInventory::addedPathsSince($repositoryRoot, $baseSha) as $path)
		{
			if (!isset($inventory[$path]))
			{
				$errors[] = 'New in-scope production file was not discovered: ' . $path;
				continue;
			}

			if (isset($baselinePaths[$path]))
			{
				$errors[] = 'New production files cannot enter the untested baseline: ' . $path;
			}

			if (!array_key_exists($path, $ownership))
			{
				$errors[] = 'New production file requires explicit non-baseline test ownership: ' . $path;
			}
		}
	}

	sort($errors, SORT_STRING);
	$errors = array_values(array_unique($errors));

	if ($errors !== [])
	{
		fwrite(
			STDERR,
			sprintf(
				"Test ownership validation failed with %d error(s):\n- %s\n",
				count($errors),
				implode("\n- ", $errors)
			)
		);

		exit(1);
	}

	fwrite(
		STDOUT,
		sprintf(
			"Test ownership is valid: %d production files; %d explicitly untested baseline entries; %d tested ownership entries.\n",
			count($inventory),
			count($baseline),
			count($ownership)
		)
	);

	if ($baseSha === null)
	{
		fwrite(
			STDOUT,
			"New-file debt expansion was not checked because no base SHA was supplied.\n"
		);
	}
}
catch (Throwable $error)
{
	fwrite(STDERR, 'Test ownership validation could not run: ' . $error->getMessage() . "\n");
	exit(2);
}
