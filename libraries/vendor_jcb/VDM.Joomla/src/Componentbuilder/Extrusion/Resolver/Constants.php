<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    24th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Resolver;


use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;


/**
 * Turns the language constants in harvested code back into their own text.
 *
 * JCB stores code with the readable string inside its text calls, and its
 * compiler makes the constant: the language extractor takes what stands
 * between Text::_(' and ') and asks the language builder for a key, then
 * writes that key into the compiled code and the string into the language
 * file. Harvested code arrives the other way around -- the constant is
 * already there, because it came out of a compiled component -- so storing
 * it unchanged would have the compiler build a key from a key, and the
 * component would show a constant to its users.
 *
 * The reversal is therefore what makes harvested code JCB's own again: every
 * constant the catalogue knows becomes the English it stands for, and the
 * compiler regenerates the constant and the language file entry from that.
 * A constant nothing can resolve is left exactly as it stands and reported,
 * because inventing text for it would be a lie about what the source said.
 *
 * @since 6.1.8
 */
final class Constants
{
	/**
	 * The text calls whose first argument is a language string.
	 *
	 * Written in parts so this very file never carries a whole call the
	 * compiler's own extractor would then find and rewrite.
	 *
	 * @var    array<string>
	 * @since  6.1.8
	 */
	private const CALLS = [
		'Text:' . ':_', 'Text:' . ':sprintf', 'Text:' . ':plural',
		'Text:' . ':script', 'Text:' . ':alt', 'JustTEXT:' . ':_'
	];

	/**
	 * The Language Resolver.
	 *
	 * @var    Language
	 * @since  6.1.8
	 */
	protected Language $language;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.8
	 */
	protected Report $report;

	/**
	 * Constructor.
	 *
	 * @param   Language  $language  The language constant resolver.
	 * @param   Report    $report    The run report registry.
	 *
	 * @since   6.1.8
	 */
	public function __construct(Language $language, Report $report)
	{
		$this->language = $language;
		$this->report = $report;
	}

	/**
	 * Every language constant in one piece of code, as its own text.
	 *
	 * @param   string  $code  The harvested code.
	 *
	 * @return  string  The code, speaking text where it spoke constants.
	 * @since   6.1.8
	 */
	public function reverse(string $code): string
	{
		if ($code === '' || !str_contains($code, '('))
		{
			return $code;
		}

		foreach (self::CALLS as $call)
		{
			$code = $this->call($code, preg_quote($call, '/') . '\s*\(\s*');
		}

		// the JavaScript side names the same strings through Joomla's own
		// text object, and the compiler's extractor reads those too
		return $this->call(
			$code,
			'Joomla\s*\.\s*' . preg_quote('J', '/') . '?Text\s*\.\s*_\s*\(\s*'
		);
	}

	/**
	 * Reverse every constant one call form carries.
	 *
	 * @param   string  $code     The harvested code.
	 * @param   string  $opening  The escaped pattern up to the first argument.
	 *
	 * @return  string  The code with that call form's constants reversed.
	 * @since   6.1.8
	 */
	protected function call(string $code, string $opening): string
	{
		$pattern = '/(' . $opening . ')([\'"])([A-Z][A-Z0-9_]{2,})\2/';

		$replaced = preg_replace_callback(
			$pattern,
			function (array $found): string
			{
				$constant = $found[3];
				$quote = $found[2];
				$text = $this->language->resolve($constant, '');

				if ($text === '' || $text === $constant)
				{
					$this->report->set(
						'unresolved.code_language.' . $constant,
						'the catalogue holds no text for this constant, so the '
						. 'code keeps it exactly as the source stated it'
					);

					return $found[0];
				}

				return $found[1] . $quote . $this->quote($text, $quote) . $quote;
			},
			$code
		);

		return is_string($replaced) ? $replaced : $code;
	}

	/**
	 * One resolved string, safe inside the quotes the call already used.
	 *
	 * @param   string  $text   The resolved text.
	 * @param   string  $quote  The quote character the call used.
	 *
	 * @return  string  The text, escaped for that quoting.
	 * @since   6.1.8
	 */
	protected function quote(string $text, string $quote): string
	{
		$text = str_replace(["\r", "\n"], ' ', $text);

		return str_replace(
			['\\', $quote],
			['\\\\', '\\' . $quote],
			$text
		);
	}
}
