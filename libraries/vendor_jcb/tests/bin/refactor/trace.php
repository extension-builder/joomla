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
 * Trace where each legacy method's code now lives.
 *
 * Names are unreliable because methods were renamed when they moved, so this
 * matches on content: it takes the most distinctive string literals out of each
 * legacy method and finds which of the new classes contain them. A method that
 * split across target variants shows up in several classes, which is the point.
 *
 * usage: php trace.php <legacy.php> <search-root> > map.json
 */

/**
 * List every method in a file with its token span.
 *
 * @param   array  $tokens  Token list.
 *
 * @return  array<string,array{int,int}>
 */
function spans(array $tokens): array
{
	$out = [];
	$count = count($tokens);

	for ($i = 0; $i < $count; $i++)
	{
		if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION)
		{
			continue;
		}

		$name = null;

		for ($j = $i + 1; $j < $count; $j++)
		{
			if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE)
			{
				continue;
			}

			if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING)
			{
				$name = $tokens[$j][1];
			}

			break;
		}

		if ($name === null)
		{
			continue;
		}

		$depth = 0;
		$started = false;

		for ($k = $i; $k < $count; $k++)
		{
			$t = $tokens[$k];

			if ($t === '{' || (is_array($t) && in_array($t[0],
				[T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)))
			{
				$depth++;
				$started = true;
			}
			elseif ($t === '}')
			{
				$depth--;

				if ($started && $depth === 0)
				{
					$out[$name] = [$i, $k];
					break;
				}
			}
			elseif (!$started && $t === ';')
			{
				break;
			}
		}
	}

	return $out;
}

/**
 * Collect the distinctive string literals of one span.
 *
 * @param   array  $tokens  Token list.
 * @param   int    $from    First index.
 * @param   int    $to      Last index.
 *
 * @return  array<string>
 */
function fingerprints(array $tokens, int $from, int $to): array
{
	$out = [];

	for ($i = $from; $i <= $to; $i++)
	{
		$t = $tokens[$i];

		if (!is_array($t) || !in_array($t[0],
			[T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true))
		{
			continue;
		}

		$raw = trim($t[1], "'\"");

		// long literals are the ones unlikely to appear anywhere else
		if (strlen($raw) >= 24 && !str_contains($raw, '___Power'))
		{
			$out[] = $raw;
		}
	}

	usort($out, fn($a, $b) => strlen($b) <=> strlen($a));

	return array_slice(array_values(array_unique($out)), 0, 6);
}

$legacyFile = $argv[1] ?? null;
$root = $argv[2] ?? null;

if ($legacyFile === null || $root === null)
{
	fwrite(STDERR, "usage: php trace.php <legacy.php> <search-root>\n");
	exit(2);
}

// every candidate destination file, read once
$haystack = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,
	FilesystemIterator::SKIP_DOTS));

foreach ($it as $file)
{
	if ($file->getExtension() !== 'php')
	{
		continue;
	}

	$haystack[$file->getPathname()] = file_get_contents($file->getPathname());
}

$tokens = token_get_all(file_get_contents($legacyFile));
$map = [];

foreach (spans($tokens) as $name => $span)
{
	$prints = fingerprints($tokens, $span[0], $span[1]);

	if ($prints === [])
	{
		continue;
	}

	$hits = [];

	foreach ($haystack as $path => $body)
	{
		$n = 0;

		foreach ($prints as $print)
		{
			if (str_contains($body, $print))
			{
				$n++;
			}
		}

		if ($n > 0)
		{
			$hits[$path] = $n;
		}
	}

	if ($hits === [])
	{
		continue;
	}

	arsort($hits);
	$map[$name] = [
		'fingerprints' => count($prints),
		'classes' => $hits,
	];
}

echo json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
