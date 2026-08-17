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
 * List the methods a PHP file really declares.
 *
 * Grep cannot do this: `function x(` also appears inside the string literals the
 * compiler emits, so a grep both invents methods that do not exist and can hide
 * one that was lost. Run this before and after moving a method and diff.
 *
 * usage: php methods.php <file.php>
 */

$tokens = token_get_all(file_get_contents($argv[1]));
$names = [];

for ($i = 0; $i < count($tokens); $i++)
{
	if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION)
	{
		continue;
	}

	for ($j = $i + 1; $j < count($tokens); $j++)
	{
		if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE)
		{
			continue;
		}

		if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING)
		{
			$names[] = $tokens[$j][1];
		}

		break;
	}
}

sort($names);

echo implode("\n", $names) . "\n";
