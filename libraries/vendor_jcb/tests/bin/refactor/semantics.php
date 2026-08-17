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
/**
 * Compare the control-flow semantics of a legacy method against the classes it
 * was moved into. Moving code must not change how it tests values: isset must
 * stay isset, == must stay ==, and no new strictness may appear.
 *
 * usage: php semantics.php <orig-helper.php> <methodName> <new class files...>
 */

const WATCH = [
	T_ISSET => 'isset',
	T_EMPTY => 'empty',
	T_IS_IDENTICAL => '===',
	T_IS_NOT_IDENTICAL => '!==',
	T_IS_EQUAL => '==',
	T_IS_NOT_EQUAL => '!=',
	T_COALESCE => '??',
	T_UNSET => 'unset',
	T_INSTANCEOF => 'instanceof',
];

/**
 * Count the watched comparison tokens in one span of tokens.
 *
 * @param   array  $tokens  The token list.
 * @param   int    $from    First index.
 * @param   int    $to      Last index.
 *
 * @return  array<string,int>
 */
function tally(array $tokens, int $from, int $to): array
{
	$counts = [];

	for ($i = $from; $i <= $to; $i++)
	{
		$token = $tokens[$i];

		if (is_array($token) && isset(WATCH[$token[0]]))
		{
			$name = WATCH[$token[0]];
			$counts[$name] = ($counts[$name] ?? 0) + 1;
		}
	}

	ksort($counts);

	return $counts;
}

/**
 * Find one method's token span in a file.
 *
 * @param   array   $tokens  The token list.
 * @param   string  $method  The method name.
 *
 * @return  array{int,int}|null
 */
function span(array $tokens, string $method): ?array
{
	$count = count($tokens);

	for ($i = 0; $i < $count; $i++)
	{
		if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION)
		{
			continue;
		}

		for ($j = $i + 1; $j < $count; $j++)
		{
			if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE)
			{
				continue;
			}

			if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING
				|| $tokens[$j][1] !== $method)
			{
				break;
			}

			$depth = 0;
			$started = false;

			for ($k = $j; $k < $count; $k++)
			{
				$token = $tokens[$k];

				if ($token === '{' || (is_array($token) && in_array($token[0],
					[T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)))
				{
					$depth++;
					$started = true;
				}
				elseif ($token === '}')
				{
					$depth--;

					if ($started && $depth === 0)
					{
						return [$j, $k];
					}
				}
			}

			break;
		}
	}

	return null;
}

$origFile = $argv[1] ?? null;
$methods = array_values(array_filter(array_slice($argv, 2),
	fn($a) => !str_contains($a, '/') && !str_ends_with($a, '.php')));
$newFiles = array_values(array_filter(array_slice($argv, 2),
	fn($a) => str_ends_with($a, '.php')));

if ($origFile === null || $methods === [] || $newFiles === [])
{
	fwrite(STDERR, "usage: php semantics.php <orig.php> <method...> <file.php...>\n");
	exit(2);
}

$origTokens = token_get_all(file_get_contents($origFile));
$before = [];

foreach ($methods as $method)
{
	$origSpan = span($origTokens, $method);

	if ($origSpan === null)
	{
		fwrite(STDERR, "method not found in original: $method\n");
		exit(2);
	}

	foreach (tally($origTokens, $origSpan[0], $origSpan[1]) as $k => $v)
	{
		$before[$k] = ($before[$k] ?? 0) + $v;
	}
}

ksort($before);
$method = implode('+', $methods);

// the new classes hold the whole moved body, so every file counts in full
$after = [];

foreach ($newFiles as $file)
{
	$tokens = token_get_all(file_get_contents($file));
	foreach (tally($tokens, 0, count($tokens) - 1) as $name => $n)
	{
		$after[$name] = ($after[$name] ?? 0) + $n;
	}
}

ksort($after);

$names = array_unique(array_merge(array_keys($before), array_keys($after)));
sort($names);
$drift = [];

foreach ($names as $name)
{
	$b = $before[$name] ?? 0;
	$a = $after[$name] ?? 0;

	if ($a !== $b)
	{
		$drift[] = sprintf('%s: %d -> %d', $name, $b, $a);
	}
}

if ($drift === [])
{
	printf("%-32s OK  (%s)\n", $method,
		implode(' ', array_map(fn($k, $v) => "$k=$v",
			array_keys($before), $before)) ?: 'no comparisons');
	exit(0);
}

printf("%-32s DRIFT  %s\n", $method, implode(', ', $drift));
exit(1);
