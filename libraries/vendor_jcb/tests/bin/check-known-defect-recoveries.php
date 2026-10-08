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

$path = $argv[1] ?? '';
if ($path === '' || !is_file($path) || !is_readable($path))
{
	fwrite(STDERR, "Known-defect audit is missing or unreadable. Run the complete audited group.\n");
	exit(1);
}

try
{
	$report = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
}
catch (JsonException $error)
{
	fwrite(STDERR, "Known-defect audit is invalid JSON.\n");
	exit(1);
}

if (!is_array($report) || ($report['schema'] ?? null) !== 1 || ($report['complete'] ?? null) !== true
	|| !is_int($report['expected'] ?? null) || $report['expected'] <= 0
	|| ($report['completed'] ?? null) !== $report['expected']
	|| !is_array($report['cases'] ?? null) || count($report['cases']) !== $report['expected'])
{
	fwrite(STDERR, "Known-defect audit is incomplete. A finished outcome is required for every selected case.\n");
	exit(1);
}

$recoveries = [];
foreach ($report['cases'] as $id => $case)
{
	if (!is_string($id) || $id === '' || !is_array($case)
		|| !is_bool($case['passed'] ?? null) || !is_array($case['issues'] ?? null)
		|| !in_array($case['outcome'] ?? null, ['passed', 'failed', 'errored', 'skipped', 'incomplete'], true)
		|| $case['passed'] !== ($case['outcome'] === 'passed'))
	{
		fwrite(STDERR, "Known-defect audit contains an invalid case outcome.\n");
		exit(1);
	}
	foreach ($case['issues'] as $issue)
	{
		if (!is_string($issue) || $issue === '')
		{
			fwrite(STDERR, "Known-defect audit contains an invalid diagnostic.\n");
			exit(1);
		}
	}
	if ($case['passed'] && $case['issues'] === [])
	{
		$recoveries[] = $id;
	}
}

if ($recoveries !== [])
{
	echo "Recovered known-defect cases must become blocking tests:\n";
	foreach ($recoveries as $id)
	{
		echo '- ' . $id . PHP_EOL;
	}
	echo "Remove their obsolete known-defect grouping and update the defect ledger.\n";
	exit(1);
}

echo 'Known-defect audit complete: ' . $report['expected'] . ' cases; no clean quarantined passes.' . PHP_EOL;
exit(0);
