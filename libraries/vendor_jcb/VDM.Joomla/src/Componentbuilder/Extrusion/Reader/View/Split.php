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

namespace VDM\Joomla\Componentbuilder\Extrusion\Reader\View;


/**
 * Splits one view source file into its PHP part and its HTML part.
 *
 * A JCB template or layout is one file that is really two artifacts: the PHP
 * above the closing tag becomes php_view and everything after it becomes the
 * template or layout column. The cut is therefore found by tokenising, never by
 * searching for the literal characters, because a closing tag written inside a
 * single quoted string, a double quoted string, a heredoc, a nowdoc, or a block
 * comment is content and must not divide the file.
 *
 * Two rules need stating because the language itself is ambiguous there. First,
 * a line comment is genuinely terminated by a closing tag, so PHP would cut the
 * file in the middle of a sentence a developer wrote as prose; such a tag is
 * masked before the scan, keeping the comment whole, and because the mask is the
 * same length as the tag every offset still addresses the original source.
 *
 * Second, the tag that cuts the file is the one closing the file's first PHP
 * block, because that is the exact inverse of how the compiler writes the file:
 * an opening tag, the regenerated header, php_view, the closing tag, then the
 * HTML column. A later closing tag belongs to an inline block decorating the
 * HTML and is left in place, which is what the HTML column is for. Taking the
 * last tag instead would be wrong on real layouts: in admin_view/publishing.php
 * every markup line is its own inline block, and since a closing tag swallows
 * the newline behind it those blocks sit adjacent with no inline HTML between
 * them, so the last tag is the file's final line and the HTML part would come
 * out empty.
 *
 * The header is discarded because JCB regenerates it: the opening tag, the file
 * docblock, declare, namespace, every use statement, and any access guard built
 * on defined(). A leading comment run that is not followed by such a construct
 * is kept, since it belongs to the view logic rather than to the boilerplate; the
 * one exception is a leading docblock carrying a file header tag, which is the
 * file docblock whatever follows it. That distinction earns its keep on the real
 * tree, where several layouts open their php_view with a docblock of their own
 * and a blanket rule would eat a line of the view on every pass.
 *
 * Both parts are handed back raw. The layout and template columns declare
 * store: base64, and the Data pipeline applies that encoding itself, so
 * encoding here would double encode. Nothing is included, required, or
 * evaluated: the source is only ever read as a string of bytes.
 *
 * @since 6.1.6
 */
final class Split
{
	/**
	 * The PHP closing tag.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const CLOSE = '?>';

	/**
	 * The stand-in written over a closing tag that lives inside a line comment.
	 *
	 * It is exactly as long as the tag it replaces, which is what lets the scan
	 * run on the masked copy while every offset it reports still addresses the
	 * original source.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const MASK = '??';

	/**
	 * The docblock tags that identify a file header rather than view documentation.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const TAGS = [
		'@package',
		'@subpackage',
		'@copyright',
		'@license',
		'@created',
		'@author',
		'@git'
	];

	/**
	 * How many masking passes may run before the scan gives up.
	 *
	 * Masking one tag can reveal a later line comment that the tokeniser had
	 * previously read as inline HTML, so the pass repeats. The bound exists
	 * because the source comes from an unzipped upload.
	 *
	 * @var    int
	 * @since  6.1.6
	 */
	private const PASSES = 64;

	/**
	 * Split one view source file into its PHP part and its HTML part.
	 *
	 * @param   string  $source  The complete file content.
	 *
	 * @return  array{php: string, html: string, add_php: bool}  The raw PHP part, the raw HTML part, and whether a PHP part survived.
	 * @since   6.1.6
	 */
	public function split(string $source): array
	{
		$tokens = $this->tokens($source);
		$region = $this->region($tokens);
		$php = '';
		$html = '';

		if ($region['tag'] !== null)
		{
			$start = min($this->header($tokens, $region['tag']), $region['tag']);
			$php = substr($source, $start, $region['tag'] - $start);
			$html = substr($source, $region['html']);
		}
		elseif ($region['open'])
		{
			$length = strlen($source);
			$start = min($this->header($tokens, $length), $length);
			$php = substr($source, $start);
		}
		else
		{
			$html = $source;
		}

		$php = $this->lines($php);
		$html = $this->lines($html);

		return ['php' => $php, 'html' => $html, 'add_php' => $php !== ''];
	}

	/**
	 * Rebuild one file body from the parts a split produced.
	 *
	 * The result is the body only: the discarded header is not invented back, so
	 * a round trip is lossless for everything the two columns actually carry and
	 * splitting the result again yields the same parts.
	 *
	 * The opening and closing tags are written even when there is no PHP part,
	 * which is what the compiler does too, and skipping them would not merely
	 * look different: an HTML part that begins with an inline PHP block, as a
	 * layout wrapping its markup in a condition does, would then start the file
	 * and be read back as the PHP part.
	 *
	 * @param   array  $parts  The parts, as returned by split.
	 *
	 * @return  string  The rebuilt file body.
	 * @since   6.1.6
	 */
	public function reassemble(array $parts): string
	{
		$php = $this->lines((string) ($parts['php'] ?? ''));
		$html = $this->lines((string) ($parts['html'] ?? ''));

		return '<?php' . "\n"
			. ($php === '' ? '' : $php . "\n")
			. self::CLOSE . "\n"
			. $html;
	}

	/**
	 * Locate the tag that closes the file's first PHP block.
	 *
	 * The scan stops at the first inline HTML that holds anything other than
	 * whitespace, so a file opening with markup is recognised as all HTML rather
	 * than cut at a closing tag belonging to some later inline block.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int}>  $tokens  The normalised token stream.
	 *
	 * @return  array{open: bool, tag: int|null, html: int|null}  Whether PHP was ever opened, the offset of the closing tag, and the offset the HTML part starts at.
	 * @since   6.1.6
	 */
	protected function region(array $tokens): array
	{
		$open = false;

		foreach ($tokens as $token)
		{
			if ($token['id'] === T_OPEN_TAG || $token['id'] === T_OPEN_TAG_WITH_ECHO)
			{
				$open = true;

				continue;
			}

			if ($token['id'] === T_CLOSE_TAG)
			{
				return ['open' => $open, 'tag' => $token['offset'], 'html' => $this->after($token)];
			}

			if ($token['id'] === T_INLINE_HTML && trim($token['text']) !== '')
			{
				break;
			}
		}

		return ['open' => $open, 'tag' => null, 'html' => null];
	}

	/**
	 * Find the offset at which the discardable header ends.
	 *
	 * A comment run is only dropped once a header construct is seen behind it, so
	 * a comment that introduces real view logic survives, and a run that reaches
	 * the end of the PHP part without any code behind it was header noise and
	 * goes. The single exception is a leading docblock that carries a file header
	 * tag: that is the file docblock and it is dropped whatever follows.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int}>  $tokens  The normalised token stream.
	 * @param   int                                                   $limit   The offset the PHP part ends at.
	 *
	 * @return  int  The offset the PHP part starts at.
	 * @since   6.1.6
	 */
	protected function header(array $tokens, int $limit): int
	{
		$count = count($tokens);
		$index = 0;
		$end = 0;
		$pending = null;
		$first = true;

		while ($index < $count)
		{
			$token = $tokens[$index];

			if ($token['offset'] >= $limit || $token['id'] === T_CLOSE_TAG)
			{
				return $token['offset'];
			}

			if ($token['id'] === T_OPEN_TAG || $token['id'] === T_OPEN_TAG_WITH_ECHO)
			{
				$end = $this->after($token);
				$index++;

				continue;
			}

			if ($token['id'] === T_WHITESPACE || $token['id'] === T_INLINE_HTML)
			{
				$index++;

				continue;
			}

			if ($token['id'] === T_COMMENT || $token['id'] === T_DOC_COMMENT)
			{
				if ($first && $token['id'] === T_DOC_COMMENT && $this->tagged($token['text']))
				{
					$end = $this->after($token);
				}
				elseif ($pending === null)
				{
					$pending = $token['offset'];
				}

				$first = false;
				$index++;

				continue;
			}

			$first = false;
			$next = $this->construct($tokens, $index, $limit);

			if ($next === null)
			{
				return $pending ?? $end;
			}

			$end = $this->after($tokens[$next - 1]);
			$pending = null;
			$index = $next;
		}

		return $limit;
	}

	/**
	 * Whether one docblock carries a file header tag.
	 *
	 * @param   string  $text  The docblock text.
	 *
	 * @return  bool  True when the docblock is a file header rather than view documentation.
	 * @since   6.1.6
	 */
	protected function tagged(string $text): bool
	{
		foreach (self::TAGS as $tag)
		{
			if (stripos($text, $tag) !== false)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Consume one header construct.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int}>  $tokens  The normalised token stream.
	 * @param   int                                                   $index   Index of the construct's first token.
	 * @param   int                                                   $limit   The offset the PHP part ends at.
	 *
	 * @return  int|null  The index just past the construct, or null when this is not a header construct.
	 * @since   6.1.6
	 */
	protected function construct(array $tokens, int $index, int $limit): ?int
	{
		$id = $tokens[$index]['id'];

		if ($id === T_DECLARE || $id === T_NAMESPACE || $id === T_USE)
		{
			return $this->statement($tokens, $index, $limit);
		}

		if ($this->named($tokens, $index, 'defined'))
		{
			return $this->statement($tokens, $index, $limit);
		}

		if ($id === T_IF)
		{
			return $this->conditional($tokens, $index, $limit);
		}

		return null;
	}

	/**
	 * Consume one statement up to and including its semicolon.
	 *
	 * Nesting is tracked so a group use statement or an argument list keeps its
	 * own semicolons, and a braced namespace simply finds no terminator and is
	 * reported as not a header construct rather than mangled.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int}>  $tokens  The normalised token stream.
	 * @param   int                                                   $index   Index of the statement's first token.
	 * @param   int                                                   $limit   The offset the PHP part ends at.
	 *
	 * @return  int|null  The index just past the semicolon, or null when there is none.
	 * @since   6.1.6
	 */
	protected function statement(array $tokens, int $index, int $limit): ?int
	{
		$count = count($tokens);
		$depth = 0;

		for ($cursor = $index; $cursor < $count; $cursor++)
		{
			$token = $tokens[$cursor];

			if ($token['offset'] >= $limit || $token['id'] === T_CLOSE_TAG)
			{
				return null;
			}

			if ($token['text'] === '(' || $token['text'] === '[' || $token['text'] === '{')
			{
				$depth++;

				continue;
			}

			if ($token['text'] === ')' || $token['text'] === ']' || $token['text'] === '}')
			{
				$depth--;

				continue;
			}

			if ($token['text'] === ';' && $depth === 0)
			{
				return $cursor + 1;
			}
		}

		return null;
	}

	/**
	 * Consume one access guard written as a conditional.
	 *
	 * Only a conditional whose test calls defined() qualifies, which keeps real
	 * view logic that happens to open with an if statement out of the header.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int}>  $tokens  The normalised token stream.
	 * @param   int                                                   $index   Index of the if keyword.
	 * @param   int                                                   $limit   The offset the PHP part ends at.
	 *
	 * @return  int|null  The index just past the guard, or null when this is not a guard.
	 * @since   6.1.6
	 */
	protected function conditional(array $tokens, int $index, int $limit): ?int
	{
		$count = count($tokens);
		$cursor = $this->skip($tokens, $index + 1, $limit);

		if ($cursor === null || $tokens[$cursor]['text'] !== '(')
		{
			return null;
		}

		$depth = 0;
		$guard = false;

		for (; $cursor < $count; $cursor++)
		{
			$token = $tokens[$cursor];

			if ($token['offset'] >= $limit || $token['id'] === T_CLOSE_TAG)
			{
				return null;
			}

			if ($this->named($tokens, $cursor, 'defined'))
			{
				$guard = true;
			}

			if ($token['text'] === '(')
			{
				$depth++;

				continue;
			}

			if ($token['text'] === ')')
			{
				$depth--;

				if ($depth === 0)
				{
					$cursor++;

					break;
				}
			}
		}

		$cursor = $guard ? $this->skip($tokens, $cursor, $limit) : null;

		if ($cursor === null)
		{
			return null;
		}

		return $tokens[$cursor]['text'] === '{'
			? $this->block($tokens, $cursor, $limit)
			: $this->statement($tokens, $cursor, $limit);
	}

	/**
	 * Consume one braced block.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int}>  $tokens  The normalised token stream.
	 * @param   int                                                   $index   Index of the opening brace.
	 * @param   int                                                   $limit   The offset the PHP part ends at.
	 *
	 * @return  int|null  The index just past the closing brace, or null when there is none.
	 * @since   6.1.6
	 */
	protected function block(array $tokens, int $index, int $limit): ?int
	{
		$count = count($tokens);
		$depth = 0;

		for ($cursor = $index; $cursor < $count; $cursor++)
		{
			$token = $tokens[$cursor];

			if ($token['offset'] >= $limit || $token['id'] === T_CLOSE_TAG)
			{
				return null;
			}

			if ($token['text'] === '{')
			{
				$depth++;

				continue;
			}

			if ($token['text'] === '}')
			{
				$depth--;

				if ($depth === 0)
				{
					return $cursor + 1;
				}
			}
		}

		return null;
	}

	/**
	 * Find the next token that is neither whitespace nor a comment.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int}>  $tokens  The normalised token stream.
	 * @param   int                                                   $index   Index to start looking from.
	 * @param   int                                                   $limit   The offset the PHP part ends at.
	 *
	 * @return  int|null  The index found, or null when there is none inside the limit.
	 * @since   6.1.6
	 */
	protected function skip(array $tokens, int $index, int $limit): ?int
	{
		$count = count($tokens);

		for ($cursor = $index; $cursor < $count; $cursor++)
		{
			$token = $tokens[$cursor];

			if ($token['offset'] >= $limit || $token['id'] === T_CLOSE_TAG)
			{
				return null;
			}

			if ($token['id'] !== T_WHITESPACE
				&& $token['id'] !== T_COMMENT
				&& $token['id'] !== T_DOC_COMMENT)
			{
				return $cursor;
			}
		}

		return null;
	}

	/**
	 * Whether one token names a given function, qualified or not.
	 *
	 * @param   array<int, array{id: int, text: string, offset: int}>  $tokens  The normalised token stream.
	 * @param   int                                                   $index   Index of the token to test.
	 * @param   string                                                $name    The unqualified function name.
	 *
	 * @return  bool  True when the token names that function.
	 * @since   6.1.6
	 */
	protected function named(array $tokens, int $index, string $name): bool
	{
		if ($tokens[$index]['id'] === T_NS_SEPARATOR)
		{
			$index++;
		}

		if (!isset($tokens[$index]))
		{
			return false;
		}

		$token = $tokens[$index];
		$ids = [T_STRING, T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED];

		return in_array($token['id'], $ids, true)
			&& strcasecmp(ltrim($token['text'], '\\'), $name) === 0;
	}

	/**
	 * Tokenise the source with every misleading closing tag masked out.
	 *
	 * @param   string  $source  The complete file content.
	 *
	 * @return  array<int, array{id: int, text: string, offset: int}>  The normalised token stream, addressing the original source.
	 * @since   6.1.6
	 */
	protected function tokens(string $source): array
	{
		return $this->normalise($this->parse($this->mask($source)));
	}

	/**
	 * Mask every closing tag that only exists inside a line comment.
	 *
	 * @param   string  $source  The complete file content.
	 *
	 * @return  string  A copy of the source, the same length, with those tags masked.
	 * @since   6.1.6
	 */
	protected function mask(string $source): string
	{
		for ($pass = 0; $pass < self::PASSES; $pass++)
		{
			$offsets = [];
			$previous = null;

			foreach ($this->normalise($this->parse($source)) as $token)
			{
				if ($token['id'] === T_CLOSE_TAG
					&& $previous !== null
					&& $previous['id'] === T_COMMENT
					&& $this->inline($previous['text']))
				{
					$offsets[] = $token['offset'];
				}

				$previous = $token;
			}

			if ($offsets === [])
			{
				break;
			}

			foreach ($offsets as $offset)
			{
				$source = substr_replace($source, self::MASK, $offset, strlen(self::CLOSE));
			}
		}

		return $source;
	}

	/**
	 * Whether one comment is a line comment that a closing tag cut short.
	 *
	 * A line comment normally carries its own newline, so a comment without one
	 * that is immediately followed by a closing tag was ended by that tag.
	 *
	 * @param   string  $text  The comment text.
	 *
	 * @return  bool  True when the comment was cut short by a closing tag.
	 * @since   6.1.6
	 */
	protected function inline(string $text): bool
	{
		if (str_starts_with($text, '#['))
		{
			return false;
		}

		if (!str_starts_with($text, '//') && !str_starts_with($text, '#'))
		{
			return false;
		}

		return !str_ends_with($text, "\n") && !str_ends_with($text, "\r");
	}

	/**
	 * Tokenise without allowing a malformed file to surface as a warning.
	 *
	 * @param   string  $source  The content to tokenise.
	 *
	 * @return  array<int, array{0: int, 1: string, 2: int}|string>  The raw token stream.
	 * @since   6.1.6
	 */
	protected function parse(string $source): array
	{
		return @token_get_all($source);
	}

	/**
	 * Give every token one shape and its byte offset.
	 *
	 * @param   array<int, array{0: int, 1: string, 2: int}|string>  $tokens  The raw token stream.
	 *
	 * @return  array<int, array{id: int, text: string, offset: int}>  The normalised token stream.
	 * @since   6.1.6
	 */
	protected function normalise(array $tokens): array
	{
		$normalised = [];
		$offset = 0;

		foreach ($tokens as $token)
		{
			$text = is_array($token) ? $token[1] : $token;
			$normalised[] = [
				'id' => is_array($token) ? $token[0] : -1,
				'text' => $text,
				'offset' => $offset
			];
			$offset += strlen($text);
		}

		return $normalised;
	}

	/**
	 * The offset just past one token.
	 *
	 * @param   array{id: int, text: string, offset: int}  $token  The token.
	 *
	 * @return  int  The offset just past it.
	 * @since   6.1.6
	 */
	protected function after(array $token): int
	{
		return $token['offset'] + strlen($token['text']);
	}

	/**
	 * Drop the blank lines that open and close one part.
	 *
	 * Only whole blank lines go, so the inner indentation of both parts survives
	 * untouched. Line endings are normalised to newlines first, because a source
	 * unzipped from a Windows machine would otherwise carry its carriage returns
	 * into the stored column.
	 *
	 * @param   string  $value  The part.
	 *
	 * @return  string  The part without its leading and trailing blank lines.
	 * @since   6.1.6
	 */
	protected function lines(string $value): string
	{
		$lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $value));

		while ($lines !== [] && trim($lines[0]) === '')
		{
			array_shift($lines);
		}

		while ($lines !== [] && trim($lines[count($lines) - 1]) === '')
		{
			array_pop($lines);
		}

		return implode("\n", $lines);
	}
}
