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

use VDM\Tests\Support\PhpStyleChecker;


$testsRoot = dirname(__DIR__);
$repositoryRoot = dirname(__DIR__, 4);

require_once $testsRoot . '/Support/PhpStyleChecker.php';

$options = getopt('', ['base:', 'help']);

if (isset($options['help']))
{
	fwrite(
		STDOUT,
		"Usage: php bin/check-php-style.php [--base=<git-sha>]\n\n"
		. "The base SHA may also be supplied through JCB_PHP_STYLE_BASE_SHA, "
		. "JCB_TEST_OWNERSHIP_BASE_SHA, or GITHUB_BASE_SHA.\n"
		. "With a base, complete added/copied/renamed PHP files and only added "
		. "lines in modified PHP files are checked. Without a base, every "
		. "project-owned test PHP file is checked.\n"
	);

	exit(0);
}

$baseSha = $options['base'] ?? null;

if (is_array($baseSha))
{
	fwrite(STDERR, "The --base option may only be supplied once.\n");
	exit(2);
}

foreach (['JCB_PHP_STYLE_BASE_SHA', 'JCB_TEST_OWNERSHIP_BASE_SHA', 'GITHUB_BASE_SHA'] as $variable)
{
	if (is_string($baseSha) && trim($baseSha) !== '')
	{
		break;
	}

	$baseSha = getenv($variable);
}

$baseSha = is_string($baseSha) && trim($baseSha) !== '' ? trim($baseSha) : null;

try
{
	$changes = [];

	if ($baseSha === null)
	{
		foreach (PhpStyleChecker::firstPartyTestPhpFiles($repositoryRoot) as $path)
		{
			$changes[$path] = [
				'status' => 'T',
				'added_lines' => null
			];
		}
	}
	else
	{
		$changes = PhpStyleChecker::changedPhpFilesSince($repositoryRoot, $baseSha);
	}

	$errors = [];
	$checked = 0;
	$preserved = 0;

	foreach ($changes as $path => $change)
	{
		if (!PhpStyleChecker::isStyleInScope($path))
		{
			$preserved++;
			continue;
		}

		$absolutePath = $repositoryRoot . '/' . $path;

		if (!is_file($absolutePath))
		{
			throw new RuntimeException('Changed PHP path does not exist: ' . $path);
		}

		$checked++;
		$errors = array_merge(
			$errors,
			PhpStyleChecker::inspectFile(
				$absolutePath,
				$path,
				$change['added_lines']
			)
		);
	}

	sort($errors, SORT_STRING);
	$errors = array_values(array_unique($errors));

	if ($errors !== [])
	{
		fwrite(
			STDERR,
			sprintf(
				"PHP contribution style validation failed with %d error(s):\n- %s\n",
				count($errors),
				implode("\n- ", $errors)
			)
		);

		exit(1);
	}

	fwrite(
		STDOUT,
		sprintf(
			"PHP contribution style is valid: %d file(s) checked; %d preserved Minify source file(s) skipped.\n",
			$checked,
			$preserved
		)
	);

	if ($baseSha === null)
	{
		fwrite(
			STDOUT,
			"No base SHA was supplied; all project-owned test PHP was checked instead.\n"
		);
	}
}
catch (Throwable $error)
{
	fwrite(STDERR, 'PHP contribution style validation could not run: ' . $error->getMessage() . "\n");
	exit(2);
}
