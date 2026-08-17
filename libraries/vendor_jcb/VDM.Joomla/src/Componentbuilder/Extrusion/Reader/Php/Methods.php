<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Reader\Php;


/**
 * Lifts the top level method bodies out of one class or trait.
 *
 * This is the reader the candidate custom code phase stands on. A model, a
 * controller, or a view class is read as text and every method it declares is
 * handed back with the body a JCB php_* column would hold, so the phase can
 * offer a snippet without ever running the code it found.
 *
 * The file is a security boundary, exactly like Literal. A component source
 * tree may be an unzipped upload, so nothing here includes, requires, or
 * evaluates it, and no reflection is attempted: the only mechanism is
 * token_get_all, which lexes without executing. There is no php-parser in this
 * repository, which is why the brace matching below is written by hand.
 *
 * Three rules need stating because they are where a naive scan goes wrong.
 *
 * First, the matching closing brace is found by counting tokens, never by
 * counting characters. A brace written inside a single quoted string, a double
 * quoted string, a heredoc, a nowdoc, a line comment, or a block comment is
 * content and is invisible to the count because the lexer already folded it
 * into one token. The two interpolation openers are the trap that makes a
 * character scan fail even after strings are skipped: "{$name}" lexes as an
 * opening token whose matching brace is an ordinary brace token, so both
 * openers are counted here or every interpolated string would close the method
 * one level early.
 *
 * Second, nesting is followed rather than avoided. A closure, an arrow
 * function, a match arm, and an anonymous class inside a body all raise the
 * depth and lower it again, so the body ends at the brace that returns the
 * depth to zero, however deeply the author nested. Only depth one of the first
 * class or trait yields a method, which is what keeps the methods of an
 * anonymous class declared inside a body out of the result.
 *
 * Third, only the first class or trait in the file is read. A Joomla artifact
 * declares one type per file, and a second declaration is either a fixture or
 * an anonymous class, neither of which owns a php_* column. An interface is not
 * a carrier and is skipped, so a file that declares one before its class still
 * yields that class.
 *
 * The body is handed back dedented by the method's own indent level and with no
 * outer braces, which is the shape a php_* column holds: a body written at two
 * tabs inside a method written at one comes back at one tab. The value is raw.
 * The php_* columns declare store: base64 and the Data pipeline applies that
 * encoding itself, so encoding here would double encode.
 *
 * @since 6.1.6
 */
final class Methods
{
	/**
	 * The identifier stood in for a token that the lexer returned as a string.
	 *
	 * A single character token such as a brace carries no identifier of its own,
	 * so one is invented to keep every normalised token the same shape and to
	 * let a structural brace be told apart from a brace that merely happens to
	 * be the whole text of some other token.
	 *
	 * @var    int
	 * @since  6.1.6
	 */
	private const PLAIN = -1;

	/**
	 * The modifier keywords a method declaration may open with.
	 *
	 * @var    array<int, string>
	 * @since  6.1.6
	 */
	private const MODIFIERS = [
		T_PUBLIC => 'public',
		T_PROTECTED => 'protected',
		T_PRIVATE => 'private',
		T_STATIC => 'static',
		T_ABSTRACT => 'abstract',
		T_FINAL => 'final'
	];

	/**
	 * The visibility of a method declared without one.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const VISIBILITY = 'public';

	/**
	 * The tokens that carry no meaning for a declaration scan.
	 *
	 * @var    array<int>
	 * @since  6.1.6
	 */
	private const SKIP = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

	/**
	 * Extract every top level method of the first class or trait in one file.
	 *
	 * A method with no body of its own, such as an abstract declaration, is
	 * reported with an empty body and a zero line count rather than dropped, so
	 * the caller sees the whole surface of the class it asked about. A method
	 * whose body is never closed is dropped, because a truncated body is worse
	 * than a missing one. Duplicate names cannot occur in valid PHP; should a
	 * malformed file carry one, the first declaration wins.
	 *
	 * @param   string  $source  The complete PHP source, as text.
	 *
	 * @return  array<string, array{name: string, body: string, signature: string, visibility: string, static: bool, line: int, lines: int}>  Method name keyed to its description, in declaration order.
	 * @since   6.1.6
	 */
	public function parse(string $source): array
	{
		if ($source === '')
		{
			return [];
		}

		$tokens = $this->tokens($source);
		$openers = $this->openers();
		$carrier = $this->carrier($tokens, $openers);

		if ($carrier === null)
		{
			return [];
		}

		return $this->methods($source, $tokens, $carrier, $openers);
	}

	/**
	 * Lex the source without executing any part of it.
	 *
	 * Every token is given one shape, its byte offset, and the line it starts
	 * on. The offsets address the source that was handed in, which is what lets
	 * a body be sliced out of it byte for byte.
	 *
	 * @param   string  $source  The complete PHP source, as text.
	 *
	 * @return  array<int, array{id: int, text: string, offset: int, line: int}>  The normalised token stream.
	 * @since   6.1.6
	 */
	protected function tokens(string $source): array
	{
		$tokens = @token_get_all($source);
		$tokens = is_array($tokens) ? $tokens : [];
		$normalised = [];
		$offset = 0;
		$line = 1;

		foreach ($tokens as $token)
		{
			$text = is_array($token) ? $token[1] : $token;
			$normalised[] = [
				'id' => is_array($token) ? $token[0] : self::PLAIN,
				'text' => $text,
				'offset' => $offset,
				'line' => $line
			];
			$offset += strlen($text);
			$line += substr_count($text, "\n");
		}

		return $normalised;
	}

	/**
	 * Find the body brace of the first class or trait in the file.
	 *
	 * A class constant reference and an anonymous class are both rejected, the
	 * first because Foo::class is not a declaration and the second because it
	 * has no name to attach a column to. Once a named carrier is seen the scan
	 * stops either way: a file whose first carrier has no body has nothing for
	 * this reader, and a later carrier is not the file's subject.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int, line: int}>  $tokens   The normalised token stream.
	 * @param   array<int>                                                       $openers  The bracket opening token identifiers.
	 *
	 * @return  int|null  The token index of the body's opening brace, or null when there is none.
	 * @since   6.1.6
	 */
	protected function carrier(array $tokens, array $openers): ?int
	{
		$count = count($tokens);

		for ($index = 0; $index < $count; $index++)
		{
			$token = $tokens[$index];

			if ($token['id'] !== T_CLASS && $token['id'] !== T_TRAIT)
			{
				continue;
			}

			$previous = $this->previous($tokens, $index);

			if ($previous === T_DOUBLE_COLON || $previous === T_NEW)
			{
				continue;
			}

			$named = $tokens[$this->next($tokens, $index)] ?? null;

			if ($named === null || !$this->identifier($named))
			{
				continue;
			}

			$opening = $this->opening($tokens, $index, $openers);

			if ($opening !== null && $tokens[$opening]['text'] === '{')
			{
				return $opening;
			}

			return null;
		}

		return null;
	}

	/**
	 * Read every method declared directly in the carrier's body.
	 *
	 * The walk starts on the body's opening brace, so a depth of one is the
	 * carrier's own body and nothing else. A function keyword seen there is a
	 * method declaration; the same keyword inside a property initialiser or a
	 * method body sits deeper and is passed over. The walk ends on the brace
	 * that closes the carrier.
	 *
	 * @param   string                                                           $source   The complete PHP source, as text.
	 * @param   array<int, array{id: int, text: string, offset: int, line: int}>  $tokens   The normalised token stream.
	 * @param   int                                                              $carrier  The token index of the body's opening brace.
	 * @param   array<int>                                                       $openers  The bracket opening token identifiers.
	 *
	 * @return  array<string, array{name: string, body: string, signature: string, visibility: string, static: bool, line: int, lines: int}>  Method name keyed to its description.
	 * @since   6.1.6
	 */
	protected function methods(string $source, array $tokens, int $carrier, array $openers): array
	{
		$count = count($tokens);
		$methods = [];
		$depth = 0;

		for ($index = $carrier; $index < $count; $index++)
		{
			$token = $tokens[$index];

			if ($depth === 1 && $token['id'] === T_FUNCTION)
			{
				$method = $this->method($source, $tokens, $index, $openers);

				if ($method !== null && !isset($methods[$method['name']]))
				{
					$methods[$method['name']] = $method;
				}
			}

			$depth += $this->delta($token, $openers);

			if ($depth === 0)
			{
				break;
			}
		}

		return $methods;
	}

	/**
	 * Describe one method declaration.
	 *
	 * A by-reference declaration is matched on the ampersand's text rather than
	 * on its token identifier, because PHP 8.1 split that one character into two
	 * identifiers of its own and a version-specific test would quietly drop
	 * every method returning a reference.
	 *
	 * @param   string                                                           $source   The complete PHP source, as text.
	 * @param   array<int, array{id: int, text: string, offset: int, line: int}>  $tokens   The normalised token stream.
	 * @param   int                                                              $index    The token index of the function keyword.
	 * @param   array<int>                                                       $openers  The bracket opening token identifiers.
	 *
	 * @return  array{name: string, body: string, signature: string, visibility: string, static: bool, line: int, lines: int}|null  The description, or null when this is not a named method.
	 * @since   6.1.6
	 */
	protected function method(string $source, array $tokens, int $index, array $openers): ?array
	{
		$cursor = $this->next($tokens, $index);
		$token = $tokens[$cursor] ?? null;

		if ($token !== null && $token['text'] === '&')
		{
			$cursor = $this->next($tokens, $cursor);
			$token = $tokens[$cursor] ?? null;
		}

		if ($token === null || !$this->identifier($token))
		{
			return null;
		}

		$opening = $this->opening($tokens, $cursor, $openers);

		if ($opening === null)
		{
			return null;
		}

		$body = $this->extract($source, $tokens, $opening, $openers);

		if ($body === null)
		{
			return null;
		}

		$modifiers = $this->modifiers($tokens, $index);
		$start = $tokens[$modifiers['index']];
		$body = $this->dedent($body, $this->indent($source, $start['offset']));

		return [
			'name' => $token['text'],
			'body' => $body,
			'signature' => $this->signature($source, $start['offset'], $tokens[$opening]['offset']),
			'visibility' => $modifiers['visibility'],
			'static' => $modifiers['static'],
			'line' => $start['line'],
			'lines' => $body === '' ? 0 : substr_count($body, "\n") + 1
		];
	}

	/**
	 * Read the modifiers written in front of one function keyword.
	 *
	 * The scan runs backwards and stops on the first token that is not a
	 * modifier, so an owning docblock, a preceding statement, and the body brace
	 * of the member above all end it without being mistaken for part of this
	 * declaration.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int, line: int}>  $tokens  The normalised token stream.
	 * @param   int                                                              $index   The token index of the function keyword.
	 *
	 * @return  array{index: int, visibility: string, static: bool}  The declaration's first token index, its visibility, and whether it is static.
	 * @since   6.1.6
	 */
	protected function modifiers(array $tokens, int $index): array
	{
		$start = $index;
		$visibility = self::VISIBILITY;
		$static = false;

		for ($cursor = $index - 1; $cursor >= 0; $cursor--)
		{
			$token = $tokens[$cursor];

			if (in_array($token['id'], self::SKIP, true))
			{
				continue;
			}

			if (!isset(self::MODIFIERS[$token['id']]))
			{
				break;
			}

			$keyword = self::MODIFIERS[$token['id']];
			$start = $cursor;

			if ($keyword === 'static')
			{
				$static = true;

				continue;
			}

			if ($keyword !== 'abstract' && $keyword !== 'final')
			{
				$visibility = $keyword;
			}
		}

		return ['index' => $start, 'visibility' => $visibility, 'static' => $static];
	}

	/**
	 * Find the token that ends one declaration's header.
	 *
	 * Nesting is tracked so a brace written inside a parameter default, such as
	 * the body of a closure used as a default value, cannot be mistaken for the
	 * body brace. The semicolon of a bodyless declaration is accepted at the
	 * same depth, which is how an abstract method is recognised.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int, line: int}>  $tokens   The normalised token stream.
	 * @param   int                                                              $index    The token index to start from.
	 * @param   array<int>                                                       $openers  The bracket opening token identifiers.
	 *
	 * @return  int|null  The token index of the body brace or the semicolon, or null when there is neither.
	 * @since   6.1.6
	 */
	protected function opening(array $tokens, int $index, array $openers): ?int
	{
		$count = count($tokens);
		$depth = 0;

		for ($cursor = $index; $cursor < $count; $cursor++)
		{
			$token = $tokens[$cursor];

			if ($depth === 0 && $token['id'] === self::PLAIN
				&& ($token['text'] === '{' || $token['text'] === ';'))
			{
				return $cursor;
			}

			$depth += $this->delta($token, $openers);
		}

		return null;
	}

	/**
	 * Slice one method body out of the source, without its outer braces.
	 *
	 * @param   string                                                           $source   The complete PHP source, as text.
	 * @param   array<int, array{id: int, text: string, offset: int, line: int}>  $tokens   The normalised token stream.
	 * @param   int                                                              $opening  The token index that ended the header.
	 * @param   array<int>                                                       $openers  The bracket opening token identifiers.
	 *
	 * @return  string|null  The body source, an empty string when there is no body, or null when the body is never closed.
	 * @since   6.1.6
	 */
	protected function extract(string $source, array $tokens, int $opening, array $openers): ?string
	{
		if ($tokens[$opening]['text'] !== '{')
		{
			return '';
		}

		$close = $this->close($tokens, $opening, $openers);

		if ($close === null)
		{
			return null;
		}

		$start = $tokens[$opening]['offset'] + 1;

		return substr($source, $start, $tokens[$close]['offset'] - $start);
	}

	/**
	 * Find the brace that closes one opening brace.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int, line: int}>  $tokens   The normalised token stream.
	 * @param   int                                                              $index    The token index of the opening brace.
	 * @param   array<int>                                                       $openers  The bracket opening token identifiers.
	 *
	 * @return  int|null  The token index of the matching brace, or null when there is none.
	 * @since   6.1.6
	 */
	protected function close(array $tokens, int $index, array $openers): ?int
	{
		$count = count($tokens);
		$depth = 0;

		for ($cursor = $index; $cursor < $count; $cursor++)
		{
			$depth += $this->delta($tokens[$cursor], $openers);

			if ($depth === 0)
			{
				return $cursor;
			}
		}

		return null;
	}

	/**
	 * How one token changes the nesting depth.
	 *
	 * Only a token the lexer handed back as a bare character counts as a
	 * structural bracket, which is what keeps a bracket that merely happens to
	 * be the whole text of some other token -- a fragment of inline HTML, say --
	 * out of the count. The interpolation openers are the exception: they open a
	 * brace pair the lexer closes with an ordinary brace, so they must be
	 * counted or an interpolated string would unbalance the walk.
	 *
	 * @param   array{id: int, text: string, offset: int, line: int}  $token    The normalised token.
	 * @param   array<int>                                           $openers  The bracket opening token identifiers.
	 *
	 * @return  int  One of 1, 0, or -1.
	 * @since   6.1.6
	 */
	protected function delta(array $token, array $openers): int
	{
		if (in_array($token['id'], $openers, true))
		{
			return 1;
		}

		if ($token['id'] !== self::PLAIN)
		{
			return 0;
		}

		if ($token['text'] === '{' || $token['text'] === '(' || $token['text'] === '[')
		{
			return 1;
		}

		if ($token['text'] === '}' || $token['text'] === ')' || $token['text'] === ']')
		{
			return -1;
		}

		return 0;
	}

	/**
	 * The token identifiers that open a bracket pair of their own.
	 *
	 * The interpolation openers of a double quoted string or a heredoc and the
	 * attribute opener all carry more than one character, so the lexer gives
	 * them identifiers instead of returning them as bare brackets. Each is
	 * resolved through defined() because the syntax they belong to has come and
	 * gone across PHP versions.
	 *
	 * @return  array<int>  The opening token identifiers.
	 * @since   6.1.6
	 */
	protected function openers(): array
	{
		$openers = [T_CURLY_OPEN];

		foreach (['T_DOLLAR_OPEN_CURLY_BRACES', 'T_ATTRIBUTE'] as $name)
		{
			if (defined($name))
			{
				$openers[] = constant($name);
			}
		}

		return $openers;
	}

	/**
	 * The declaration header as one line of text.
	 *
	 * @param   string  $source  The complete PHP source, as text.
	 * @param   int     $start   The byte offset the declaration starts at.
	 * @param   int     $end     The byte offset the header ends at.
	 *
	 * @return  string  The header, with every run of whitespace collapsed.
	 * @since   6.1.6
	 */
	protected function signature(string $source, int $start, int $end): string
	{
		$header = substr($source, $start, max(0, $end - $start));

		return trim((string) preg_replace('/\s+/', ' ', $header));
	}

	/**
	 * The whitespace one declaration is indented by.
	 *
	 * A declaration that does not open its own line, such as one written inside
	 * a class body collapsed onto a single line, is treated as unindented.
	 *
	 * @param   string  $source  The complete PHP source, as text.
	 * @param   int     $offset  The byte offset the declaration starts at.
	 *
	 * @return  string  The leading whitespace of the declaration's line.
	 * @since   6.1.6
	 */
	protected function indent(string $source, int $offset): string
	{
		$break = strrpos(substr($source, 0, $offset), "\n");
		$start = $break === false ? 0 : $break + 1;
		$prefix = substr($source, $start, $offset - $start);

		return trim($prefix) === '' ? $prefix : '';
	}

	/**
	 * Dedent one body by the indent level of the method that owns it.
	 *
	 * Line endings are normalised first, because a source unzipped from a
	 * Windows machine would otherwise carry its carriage returns into the stored
	 * column. A line holding nothing but whitespace becomes empty, and the blank
	 * lines that open and close the body are dropped; everything else keeps its
	 * bytes once the indent is off, which matters for a heredoc whose content the
	 * caller must get back verbatim.
	 *
	 * Two lines are special because a brace, not a newline, bounds them. Content
	 * sharing the opening brace's line carries no indentation of its own, so its
	 * leading whitespace goes rather than one indent of it, and the last line
	 * stops at the closing brace, so the whitespace the brace was sitting on
	 * goes. That is what makes a body written on one line come back as the
	 * statement alone, indented exactly like every other body.
	 *
	 * @param   string  $body    The body source, without its outer braces.
	 * @param   string  $indent  The whitespace the owning declaration is indented by.
	 *
	 * @return  string  The dedented body.
	 * @since   6.1.6
	 */
	protected function dedent(string $body, string $indent): string
	{
		$body = str_replace(["\r\n", "\r"], "\n", $body);
		$lines = explode("\n", $body);

		foreach ($lines as $key => $line)
		{
			$lines[$key] = trim($line) === '' ? '' : $this->strip($line, $indent);
		}

		if (!str_starts_with($body, "\n"))
		{
			$lines[0] = ltrim($lines[0]);
		}

		$last = count($lines) - 1;
		$lines[$last] = rtrim($lines[$last]);

		while ($lines !== [] && $lines[0] === '')
		{
			array_shift($lines);
		}

		while ($lines !== [] && $lines[count($lines) - 1] === '')
		{
			array_pop($lines);
		}

		return implode("\n", $lines);
	}

	/**
	 * Take one indent off the front of one line.
	 *
	 * The longest prefix of the indent that the line actually carries is the one
	 * that comes off, so a body that mixes tabs with spaces, or a heredoc line
	 * written flush against the left margin, loses no character it needs.
	 *
	 * @param   string  $line    The line.
	 * @param   string  $indent  The whitespace the owning declaration is indented by.
	 *
	 * @return  string  The line, dedented as far as it allows.
	 * @since   6.1.6
	 */
	protected function strip(string $line, string $indent): string
	{
		for ($length = strlen($indent); $length > 0; $length--)
		{
			if (str_starts_with($line, substr($indent, 0, $length)))
			{
				return substr($line, $length);
			}
		}

		return $line;
	}

	/**
	 * Whether one token could be a declared name.
	 *
	 * PHP lets a method be named for one of its own keywords, so the test is on
	 * the shape of the text rather than on the token being a plain identifier.
	 * A bare character token can never be a name and is rejected outright.
	 *
	 * @param   array{id: int, text: string, offset: int, line: int}  $token  The normalised token.
	 *
	 * @return  bool  True when the token's text is a valid PHP name.
	 * @since   6.1.6
	 */
	protected function identifier(array $token): bool
	{
		return $token['id'] !== self::PLAIN
			&& preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/', $token['text']) === 1;
	}

	/**
	 * The index of the next significant token after one position.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int, line: int}>  $tokens  The normalised token stream.
	 * @param   int                                                              $index   The current position.
	 *
	 * @return  int  The next significant index, or the token count when exhausted.
	 * @since   6.1.6
	 */
	protected function next(array $tokens, int $index): int
	{
		$count = count($tokens);

		for ($cursor = $index + 1; $cursor < $count; $cursor++)
		{
			if (!in_array($tokens[$cursor]['id'], self::SKIP, true))
			{
				return $cursor;
			}
		}

		return $count;
	}

	/**
	 * The previous significant token before one position.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int, line: int}>  $tokens  The normalised token stream.
	 * @param   int                                                              $index   The current position.
	 *
	 * @return  int|string|null  The token identifier, the text of a bare character token, or null when there is none.
	 * @since   6.1.6
	 */
	protected function previous(array $tokens, int $index)
	{
		for ($cursor = $index - 1; $cursor >= 0; $cursor--)
		{
			$token = $tokens[$cursor];

			if (in_array($token['id'], self::SKIP, true))
			{
				continue;
			}

			return $token['id'] === self::PLAIN ? $token['text'] : $token['id'];
		}

		return null;
	}
}
