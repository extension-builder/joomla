<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

$testsRoot = dirname(__DIR__);
$repositoryRoot = dirname(__DIR__, 4);

$options = getopt('', ['base:', 'head:', 'help']);

if (isset($options['help']))
{
	fwrite(
		STDOUT,
		"Usage: php bin/check-moved-conditions.php [--base=<git-sha>] [--head=<git-sha>]\n\n"
		. "Compares the conditions that left the legacy compiler helpers with the\n"
		. "conditions that arrived in the classes they moved into. Anything that\n"
		. "does not pair up must be recorded in moved-conditions.php.\n\n"
		. "The base SHA may also be supplied through JCB_MOVED_CONDITIONS_BASE_SHA\n"
		. "or GITHUB_BASE_SHA. The head defaults to HEAD, and is worth setting when\n"
		. "replaying a single past commit.\n"
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
	$baseSha = getenv('JCB_MOVED_CONDITIONS_BASE_SHA');
}

if (!is_string($baseSha) || trim($baseSha) === '')
{
	$baseSha = getenv('GITHUB_BASE_SHA');
}

$baseSha = is_string($baseSha) ? trim($baseSha) : null;

if ($baseSha === null || $baseSha === '')
{
	fwrite(
		STDOUT,
		"Moved conditions were not checked because no base SHA was supplied.\n"
	);

	exit(0);
}

/**
 * The legacy helpers this guard watches, relative to the repository root.
 *
 * @var  array<int,string>
 */
const LEGACY_HELPERS = [
	'libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Helper/Interpretation.php',
	'libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Helper/Fields.php',
];

/**
 * The tree the moved code is allowed to land in.
 *
 * @var  string
 */
const TARGET_TREE = 'libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/';

/**
 * Tokens that mean a line decides something.
 *
 * @var  array<int,int>
 */
const DECIDING = [
	T_ISSET, T_EMPTY, T_UNSET, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL,
	T_IS_EQUAL, T_IS_NOT_EQUAL, T_COALESCE, T_INSTANCEOF,
	T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL, T_THROW,
];

/**
 * Run a git command and return its output.
 *
 * @param   string  $root       The repository root.
 * @param   array   $arguments  The command arguments.
 *
 * @return  string
 * @since   6.1.7
 */
function git(string $root, array $arguments): string
{
	$command = 'git -C ' . escapeshellarg($root);

	foreach ($arguments as $argument)
	{
		$command .= ' ' . escapeshellarg($argument);
	}

	$output = shell_exec($command . ' 2>/dev/null');

	return is_string($output) ? $output : '';
}

/**
 * The line numbers whose real code decides something.
 *
 * String literals are skipped, because the compiler writes conditions into the
 * code it generates and those are output, not decisions.
 *
 * @param   string  $code  The source.
 *
 * @return  array<int,bool>
 * @since   6.1.7
 */
function decidingLines(string $code): array
{
	if ($code === '')
	{
		return [];
	}

	$lines = [];
	$line = 1;

	foreach (@token_get_all($code) as $token)
	{
		if (is_array($token))
		{
			if (in_array($token[0], DECIDING, true))
			{
				$lines[$token[2]] = true;
			}

			$line = $token[2] + substr_count($token[1], "\n");

			continue;
		}

		if ($token === '!' || $token === '<' || $token === '>')
		{
			$lines[$line] = true;
		}
	}

	return $lines;
}

/**
 * Reduce a line to the shape the move is allowed to change.
 *
 * Whitespace, comments and the route to an injected service all change when
 * code moves; what the line tests does not.
 *
 * @param   string  $line  The raw line.
 *
 * @return  string
 * @since   6.1.7
 */
function shape(string $line): string
{
	$line = (string) preg_replace('~//.*$~', '', $line);
	$line = (string) preg_replace("~CFactory::_\(\s*'[^']+'\s*\)\s*->~", 'S->', $line);
	$line = (string) preg_replace('~\$this->[a-z][a-z0-9]*->~', 'S->', $line);

	return (string) preg_replace('~\s+~', '', $line);
}

/**
 * Whether a line only compares the Joomla version being compiled for.
 *
 * These are meant to disappear: the branch becomes a class per target.
 *
 * @param   string  $shape  The shaped line.
 *
 * @return  bool
 * @since   6.1.7
 */
function versionBranch(string $shape): bool
{
	if (!preg_match('~^(else)?if\((.*?)\)?$~', $shape, $match))
	{
		return false;
	}

	$clauses = preg_split('~\|\||&&~', rtrim($match[2], '&|'));

	if ($clauses === false || $clauses === [])
	{
		return false;
	}

	foreach ($clauses as $clause)
	{
		$clause = trim($clause, '()');

		if (!preg_match(
			"~^(S->get\('joomla_version',3\)|\\\$target_version)"
			. '(===|!==|==|!=|<=|>=|<|>)\d+$~', $clause))
		{
			return false;
		}
	}

	return true;
}

/**
 * Whether a line is the target selector a collapsed branch is replaced with.
 *
 * @param   string  $shape  The shaped line.
 *
 * @return  bool
 * @since   6.1.7
 */
function targetSelector(string $shape): bool
{
	return (bool) preg_match('~^if\(empty\(\$this->(target|current)Version\)\)$~', $shape)
		|| (bool) preg_match('~^if\(\(int\)\$this->(target|current)Version===\d+\)$~', $shape);
}

/**
 * Count every shaped deciding line in one file.
 *
 * Counting the file rather than the diff means reindenting, rewrapping and
 * line-ending changes cannot look like a condition moving.
 *
 * @param   string  $code  The source.
 *
 * @return  array<string,int>
 * @since   6.1.7
 */
function shapes(string $code): array
{
	if ($code === '')
	{
		return [];
	}

	$deciding = decidingLines($code);
	$lines = explode("\n", $code);
	$counts = [];

	foreach ($deciding as $number => $ignore)
	{
		$key = shape($lines[$number - 1] ?? '');

		if ($key === '')
		{
			continue;
		}

		$counts[$key] = ($counts[$key] ?? 0) + 1;
	}

	return $counts;
}

/**
 * Add the growth of one count set over another into a running total.
 *
 * @param   array  $total   The running total.
 * @param   array  $bigger  The side that may have gained.
 * @param   array  $former  The side it is measured against.
 *
 * @return  void
 * @since   6.1.7
 */
function gained(array &$total, array $bigger, array $former): void
{
	foreach ($bigger as $key => $count)
	{
		$count -= $former[$key] ?? 0;

		if ($count > 0)
		{
			$total[$key] = ($total[$key] ?? 0) + $count;
		}
	}
}

$ledgerFile = $testsRoot . '/moved-conditions.php';
$ledger = is_file($ledgerFile) ? require $ledgerFile : [];

if (!is_array($ledger) || !isset($ledger['left'], $ledger['arrived']))
{
	fwrite(STDERR, "moved-conditions.php must return an array with 'left' and 'arrived' keys.\n");
	exit(2);
}

$headRev = $options['head'] ?? 'HEAD';

if (!is_string($headRev) || trim($headRev) === '')
{
	fwrite(STDERR, "The --head option may only be supplied once.\n");
	exit(2);
}

$headRev = trim($headRev);
$range = $baseSha . '..' . $headRev;
$touched = array_filter(explode("\n", trim(git($repositoryRoot, array_merge(
	['diff', '--name-only', $range, '--'], LEGACY_HELPERS)))));

if ($touched === [])
{
	fwrite(STDOUT, "No legacy compiler helper changed, so no conditions moved.\n");
	exit(0);
}

$files = array_filter(explode("\n", trim(git($repositoryRoot,
	['diff', '--name-only', $range, '--', TARGET_TREE]))));

$left = [];
$arrived = [];

foreach ($files as $file)
{
	if (!str_ends_with($file, '.php'))
	{
		continue;
	}

	$before = shapes(git($repositoryRoot, ['show', $baseSha . ':' . $file]));
	$after = shapes(git($repositoryRoot, ['show', $headRev . ':' . $file]));

	if (in_array($file, LEGACY_HELPERS, true))
	{
		gained($left, $before, $after);
	}

	gained($arrived, $after, $before);
}

$errors = [];

foreach ($left as $key => $count)
{
	$count -= $arrived[$key] ?? 0;

	if ($count <= 0 || versionBranch($key) || isset($ledger['left'][$key]))
	{
		continue;
	}

	$errors[] = 'A condition left the legacy helper and did not arrive: ' . $key;
}

foreach ($arrived as $key => $count)
{
	$count -= $left[$key] ?? 0;

	if ($count <= 0 || targetSelector($key) || isset($ledger['arrived'][$key]))
	{
		continue;
	}

	$errors[] = 'A condition arrived that the legacy helper never had: ' . $key;
}

if ($errors !== [])
{
	sort($errors, SORT_STRING);

	fwrite(
		STDERR,
		sprintf(
			"Moved conditions changed in %d place(s):\n- %s\n\n"
			. "Moved code must decide on the same grounds it did before. Restore the\n"
			. "condition, or record why it reads differently in tests/moved-conditions.php.\n"
			. "See docs/architecture/moving-code-out-of-the-legacy-helpers.md\n",
			count($errors),
			implode("\n- ", $errors)
		)
	);

	exit(1);
}

fwrite(
	STDOUT,
	sprintf(
		"Moved conditions are intact: %d left the legacy helpers, %d arrived, %d recorded exception(s).\n",
		array_sum($left),
		array_sum($arrived),
		count($ledger['left']) + count($ledger['arrived'])
	)
);
