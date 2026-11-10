<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    10th November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Customcode;


use VDM\Minify\JavaScript;


/**
 * Discover literal conditional-required field names in authored JavaScript.
 *
 * Reuses the bundled JavaScript minifier's lexical extraction without changing
 * or executing the authored source. Discovery is based on unqualified call
 * names, not JavaScript binding analysis. Dynamic arguments and property calls
 * are excluded. Unsupported lexical fragments produce no declarations.
 *
 * @since  6.2.1
 */
final class RequiredFields extends JavaScript
{
	/**
	 * Get unique field names from unqualified updateFieldRequired calls.
	 *
	 * Only plain ASCII field names in a quoted first argument are accepted.
	 * PHP markers, complex template interpolation, escaped identifiers and
	 * ambiguous remaining slashes cause the whole fragment to be skipped. Simple
	 * template substitutions are opaque; their expressions are never searched.
	 *
	 * @param   string  $script  The authored JavaScript or PHP template source.
	 *
	 * @return  list<string>  Field names in their first occurrence order.
	 * @since   6.2.1
	 */
	public function get(string $script): array
	{
		$this->patterns = [];
		$this->extracted = [];

		try
		{
			if (strpos($script, '<?') !== false)
			{
				return [];
			}

			$this->extractStrings('\'"`');
			$commentOffset = count($this->patterns);
			$this->stripComments();

			// Comments separate tokens, including return/**/updateFieldRequired(...).
			for ($index = $commentOffset; $index < count($this->patterns); $index++)
			{
				$this->patterns[$index][1] = ' ';
			}

			$this->extractRegex();
			$code = $this->replace($script);

			if (!$this->isSupported($code))
			{
				return [];
			}

			$pattern = '~(?<![\p{L}\p{N}\p{M}\p{Pc}$#\x{200C}\x{200D}\\\\])'
				. 'updateFieldRequired\s*\(\s*(([\'"])[0-9]+\2)\s*,~u';
			preg_match_all($pattern, $code, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
			$fields = [];

			foreach ($matches as $match)
			{
				// Trivia between a property operator and its name does not make it unqualified.
				if (preg_match('~\.\s*$~u', substr($code, 0, $match[0][1])) === 1)
				{
					continue;
				}

				$literal = $this->extracted[$match[1][0]] ?? '';

				if (preg_match('~^([\'"])([a-zA-Z_][a-zA-Z0-9_-]*)\1$~D', $literal, $field) === 1)
				{
					$fields[] = $field[2];
				}
			}

			return array_values(array_unique($fields));
		}
		finally
		{
			$this->patterns = [];
			$this->extracted = [];
		}
	}

	/**
	 * Reject fragments outside the bundled lexical extractor's supported subset.
	 *
	 * Simple template substitutions allow member names, a numeric index and an
	 * empty call. Remaining slashes must follow an ordinary identifier, number or
	 * array index; other slash contexts may hide a regex the minifier missed.
	 *
	 * @param   string  $code  The source after strings, comments and regex extraction.
	 *
	 * @return  bool
	 * @since   6.2.1
	 */
	private function isSupported(string $code): bool
	{
		if (strpos($code, '\\') !== false)
		{
			return false;
		}

		foreach ($this->extracted as $literal)
		{
			if ($literal[0] === '`')
			{
				$template = preg_replace(
					'~\$\{[a-zA-Z_$][a-zA-Z0-9_.$]*(?:\[[0-9]+\])?(?:\(\))?\}~',
					'',
					$literal
				);

				if (strpos($template, '${') !== false)
				{
					return false;
				}
			}
		}

		preg_match_all('~/~', $code, $slashes, PREG_OFFSET_CAPTURE);

		foreach ($slashes[0] as $slash)
		{
			$prefix = substr($code, 0, $slash[1]);

			if (preg_match('~(?:([a-zA-Z_$][a-zA-Z0-9_$]*)|[0-9]+|\])\s*$~D', $prefix, $operand) !== 1
				|| in_array($operand[1] ?? '', array_merge($this->keywordsReserved, ['await', 'yield', 'of']), true))
			{
				return false;
			}
		}

		return true;
	}
}
