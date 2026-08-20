<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Prove a method body moved unchanged, once the route to each service is named.
 *
 * A method that is lifted whole out of a legacy helper differs from where it
 * landed in one way only: `CFactory::_('Some.Service')` became a property the
 * class was handed. Name each of those, and what is left must be identical
 * text. Anything else the diff reports is a change that has to be justified.
 *
 * This is the cheapest proof there is for a straight move, and the strongest:
 * it needs no fixtures, so it cannot pass because nothing was exercised. It
 * says nothing about which service reached which property, though — that is
 * what the provider registration and the constructor's type declarations
 * decide, and what a run through the container proves.
 *
 * usage: php verbatim.php <old.php> <old signature> <new.php> <new signature> \
 *            [<what it was> <what it became>]...
 *
 * The signature is enough of the declaration to find it, e.g.
 * 'public function setLangFileData(): void'. Each route is two arguments, so
 * either side may contain anything, newlines and '=' included.
 *
 * exit 0 when the two bodies are identical, 1 when they are not.
 */

if ($argc < 5 || ($argc - 5) % 2 !== 0)
{
	fwrite(STDERR, "usage: php verbatim.php <old.php> <old signature> <new.php> <new signature> [<what it was> <what it became>]...\n");
	exit(2);
}

/**
 * Read the body of one method out of a file.
 *
 * @param   string  $file       The file to read.
 * @param   string  $signature  Enough of the declaration to find it.
 *
 * @return  string  What sits between its braces.
 */
function methodBody(string $file, string $signature): string
{
	$source = @file_get_contents($file);

	if ($source === false)
	{
		fwrite(STDERR, "Cannot read {$file}\n");
		exit(2);
	}

	$at = strpos($source, $signature);

	if ($at === false)
	{
		fwrite(STDERR, "No '{$signature}' in {$file}\n");
		exit(2);
	}

	$open = strpos($source, '{', $at);
	$depth = 0;

	for ($i = $open; $i < strlen($source); $i++)
	{
		if ($source[$i] === '{')
		{
			$depth++;
		}
		elseif ($source[$i] === '}')
		{
			$depth--;

			if ($depth === 0)
			{
				return substr($source, $open + 1, $i - $open - 1);
			}
		}
	}

	fwrite(STDERR, "Unbalanced braces after '{$signature}' in {$file}\n");
	exit(2);
}

/**
 * Trim every line and drop the blank ones at each end.
 *
 * @param   string  $body  The method body.
 *
 * @return  array<string>  Its lines.
 */
function lines(string $body): array
{
	return explode("\n", trim(implode("\n", array_map(
		static fn(string $line): string => rtrim($line), explode("\n", $body)
	)), "\n"));
}

$old = methodBody($argv[1], $argv[2]);
$new = methodBody($argv[3], $argv[4]);

$routes = array_slice($argv, 5);

for ($i = 0; $i < count($routes); $i += 2)
{
	$old = str_replace($routes[$i], $routes[$i + 1], $old);
}

$a = lines($old);
$b = lines($new);

if ($a === $b)
{
	printf("The body moved verbatim: %d lines, %d route(s) named.\n", count($a), intdiv($argc - 5, 2));
	exit(0);
}

$total = max(count($a), count($b));
$shown = 0;

fwrite(STDERR, "The body did not move verbatim:\n");

for ($i = 0; $i < $total; $i++)
{
	$left = $a[$i] ?? null;
	$right = $b[$i] ?? null;

	if ($left === $right)
	{
		continue;
	}

	fwrite(STDERR, sprintf("  line %d\n  - %s\n  + %s\n", $i + 1, $left ?? '', $right ?? ''));

	if (++$shown >= 20)
	{
		fwrite(STDERR, "  ...\n");
		break;
	}
}

exit(1);
