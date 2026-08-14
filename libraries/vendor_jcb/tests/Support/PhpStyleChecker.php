<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


use FilesystemIterator;
use RuntimeException;


/**
 * Checks contributed vendor-library PHP against the first-party style contract.
 *
 * The checker deliberately uses only PHP built-ins and Git. Token-aware checks
 * ignore comments and literal content so generated strings remain untouched.
 *
 * @since  1.0.0
 */
final class PhpStyleChecker
{
	/**
	 * Production roots covered by the contribution guard.
	 *
	 * @var    array<string>
	 * @since  1.0.0
	 */
	private const SOURCE_ROOTS = [
		'libraries/vendor_jcb/VDM.Joomla/src',
		'libraries/vendor_jcb/VDM.Joomla.Gitea/src',
		'libraries/vendor_jcb/VDM.Joomla.Openai/src',
		'libraries/vendor_jcb/VDM.Joomla.Github/src',
		'libraries/vendor_jcb/VDM.Minify/src',
		'libraries/vendor_jcb/VDM.Joomla.Git/src'
	];

	/**
	 * Project-owned test root covered by the contribution guard.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private const TEST_ROOT = 'libraries/vendor_jcb/tests';

	/**
	 * Upstream-derived production source retained without JCB restyling.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private const MINIFY_SOURCE_ROOT = 'libraries/vendor_jcb/VDM.Minify/src';

	/**
	 * Third-party or runtime directories below the test project.
	 *
	 * @var    array<string>
	 * @since  1.0.0
	 */
	private const IGNORED_TEST_DIRECTORIES = [
		'vendor',
		'.runtime',
		'build',
		'.phpunit.cache'
	];

	/**
	 * Standard non-package tags required in first-party file headers.
	 *
	 * @var    array<string, string>
	 * @since  1.0.0
	 */
	private const HEADER_TAGS = [
		'created' => '/@created\s+\S+/',
		'author' => '/@author\s+\S+/',
		'git' => '/@git\s+\S+/',
		'copyright' => '/@copyright\s+\S+/',
		'license' => '/@license\s+\S+/'
	];

	/**
	 * File-integrity diagnostics that cannot safely be scoped to one diff line.
	 *
	 * @var    array<string>
	 * @since  1.0.0
	 */
	private const WHOLE_FILE_RULES = ['syntax', 'terminal-newline'];

	/**
	 * Inspect one PHP file.
	 *
	 * When an added-line map is supplied, diagnostics outside those new lines
	 * are suppressed. A null map checks the complete resulting file.
	 *
	 * @param   string                 $absolutePath  Absolute file path.
	 * @param   string                 $displayPath   Repository-relative path for diagnostics.
	 * @param   array<int, true>|null  $addedLines    Added lines to enforce, or null for every line.
	 *
	 * @return  array<string>
	 *
	 * @throws  RuntimeException  If the file cannot be read.
	 *
	 * @since   1.0.0
	 */
	public static function inspectFile(
		string $absolutePath,
		string $displayPath,
		?array $addedLines = null
	): array
	{
		$source = file_get_contents($absolutePath);

		if ($source === false)
		{
			throw new RuntimeException('Unable to read PHP source: ' . $absolutePath);
		}

		return self::inspectSource($source, $displayPath, $addedLines);
	}

	/**
	 * Inspect PHP source supplied by a caller.
	 *
	 * @param   string                 $source       Complete PHP source.
	 * @param   string                 $displayPath  Path used in deterministic diagnostics.
	 * @param   array<int, true>|null  $addedLines   Added lines to enforce, or null for every line.
	 *
	 * @return  array<string>
	 * @since   1.0.0
	 */
	public static function inspectSource(
		string $source,
		string $displayPath,
		?array $addedLines = null
	): array
	{
		$issues = [];
		$tokens = token_get_all($source);

		try
		{
			token_get_all($source, TOKEN_PARSE);
		}
		catch (\ParseError $error)
		{
			self::addIssue(
				$issues,
				$error->getLine(),
				'syntax',
				'PHP source must be syntactically valid: ' . $error->getMessage()
			);
		}

		self::inspectLineEndings($source, $issues);
		self::inspectTerminalNewline($source, $issues);
		self::inspectTrailingWhitespace($source, $issues);
		self::inspectTokens($tokens, $issues);
		self::inspectIndentation($source, $tokens, $issues);
		self::inspectOpeningBraces($source, $tokens, $issues);

		if (self::isStyleInScope($displayPath))
		{
			self::inspectFileHeader(
				$tokens,
				self::isFirstPartyTestPath($displayPath),
				$issues
			);
			self::inspectDocumentation($tokens, $issues);
		}

		return self::formatIssues($issues, $displayPath, $addedLines);
	}

	/**
	 * Discover added, copied, renamed, and modified in-scope PHP since a base.
	 *
	 * Added, copied, and renamed paths receive a null line map and therefore a
	 * whole-file check. Modified paths carry only the new-side lines from Git's
	 * zero-context diff, preventing untouched legacy style from blocking a patch.
	 *
	 * @param   string  $repositoryRoot  Repository root directory.
	 * @param   string  $baseSha         Base commit SHA.
	 *
	 * @return  array<string, array{status: string, added_lines: array<int, true>|null}>
	 *
	 * @throws  RuntimeException  If the base or Git comparison is invalid.
	 *
	 * @since   1.0.0
	 */
	public static function changedPhpFilesSince(string $repositoryRoot, string $baseSha): array
	{
		$repositoryRoot = self::normalizeRepositoryRoot($repositoryRoot);
		$baseSha = trim($baseSha);

		if (preg_match('/^[a-f0-9]{7,64}$/i', $baseSha) !== 1)
		{
			throw new RuntimeException('The PHP style base must be a hexadecimal Git commit SHA.');
		}

		if (!file_exists($repositoryRoot . '/.git'))
		{
			throw new RuntimeException('Git repository not found at: ' . $repositoryRoot);
		}

		$command = [
			'git',
			'-C',
			$repositoryRoot,
			'diff',
			'--name-status',
			'-z',
			'--diff-filter=ACMR',
			'--find-renames',
			'--find-copies',
			$baseSha . '...HEAD',
			'--'
		];

		foreach (self::SOURCE_ROOTS as $sourceRoot)
		{
			$command[] = $sourceRoot;
		}

		$command[] = self::TEST_ROOT;
		$output = self::runProcess(
			$command,
			$repositoryRoot,
			'compare PHP style with base ' . $baseSha
		);
		$fields = explode("\0", rtrim($output, "\0"));
		$changes = [];

		for ($index = 0, $count = count($fields); $index < $count;)
		{
			$statusField = $fields[$index++] ?? '';

			if ($statusField === '')
			{
				continue;
			}

			$status = $statusField[0];
			$path = $fields[$index++] ?? '';

			if ($status === 'C' || $status === 'R')
			{
				$path = $fields[$index++] ?? '';
			}

			$path = self::normalizeRepositoryPath($path);

			if (!self::isCandidatePath($path))
			{
				continue;
			}

			$changes[$path] = [
				'status' => $status,
				'added_lines' => $status === 'M'
					? self::addedLinesSince($repositoryRoot, $baseSha, $path)
					: null
			];
		}

		ksort($changes, SORT_STRING);

		return $changes;
	}

	/**
	 * Discover all project-owned test PHP for the no-base validation mode.
	 *
	 * @param   string  $repositoryRoot  Repository root directory.
	 *
	 * @return  array<string>
	 *
	 * @throws  RuntimeException  If the test root does not exist.
	 *
	 * @since   1.0.0
	 */
	public static function firstPartyTestPhpFiles(string $repositoryRoot): array
	{
		$repositoryRoot = self::normalizeRepositoryRoot($repositoryRoot);
		$absoluteRoot = $repositoryRoot . '/' . self::TEST_ROOT;

		if (!is_dir($absoluteRoot))
		{
			throw new RuntimeException('Vendor-library test root does not exist: ' . $absoluteRoot);
		}

		$paths = [];
		self::collectTestPhpFiles($absoluteRoot, self::TEST_ROOT, $paths);
		sort($paths, SORT_STRING);

		return $paths;
	}

	/**
	 * Determine whether a repository path is a requested PHP candidate.
	 *
	 * This includes preserved Minify production source; use isStyleInScope() to
	 * determine whether the candidate should receive first-party checks.
	 *
	 * @param   string  $path  Repository-relative path.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public static function isCandidatePath(string $path): bool
	{
		$path = self::normalizeRepositoryPath($path);

		if (self::repositoryPathError($path) !== null || !str_ends_with(strtolower($path), '.php'))
		{
			return false;
		}

		if (str_starts_with($path, self::TEST_ROOT . '/'))
		{
			$segments = explode('/', substr($path, strlen(self::TEST_ROOT) + 1));

			foreach (self::IGNORED_TEST_DIRECTORIES as $directory)
			{
				if (in_array($directory, $segments, true))
				{
					return false;
				}
			}

			return true;
		}

		foreach (self::SOURCE_ROOTS as $sourceRoot)
		{
			if (str_starts_with($path, $sourceRoot . '/'))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether a candidate receives the JCB first-party style rules.
	 *
	 * @param   string  $path  Repository-relative path.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public static function isStyleInScope(string $path): bool
	{
		$path = self::normalizeRepositoryPath($path);

		return self::isCandidatePath($path)
			&& !str_starts_with($path, self::MINIFY_SOURCE_ROOT . '/');
	}

	/**
	 * Detect carriage-return bytes.
	 *
	 * @param   string                                                        $source  Complete PHP source.
	 * @param   array<string, array{line: int, rule: string, message: string}>  $issues  Collected issues.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function inspectLineEndings(string $source, array &$issues): void
	{
		$offset = 0;

		while (($offset = strpos($source, "\r", $offset)) !== false)
		{
			self::addIssue(
				$issues,
				self::lineForOffset($source, $offset),
				'line-ending',
				'Carriage returns are forbidden; use LF line endings.'
			);
			$offset++;
		}
	}

	/**
	 * Require exactly one LF byte at the end of the file.
	 *
	 * @param   string                                                        $source  Complete PHP source.
	 * @param   array<string, array{line: int, rule: string, message: string}>  $issues  Collected issues.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function inspectTerminalNewline(string $source, array &$issues): void
	{
		$line = max(
			1,
			substr_count($source, "\n") + (str_ends_with($source, "\n") ? 0 : 1)
		);

		if ($source === '' || !str_ends_with($source, "\n"))
		{
			self::addIssue(
				$issues,
				$line,
				'terminal-newline',
				'File must end with exactly one LF newline.'
			);

			return;
		}

		$trailingNewlines = strlen($source) - strlen(rtrim($source, "\n"));

		if ($trailingNewlines !== 1)
		{
			self::addIssue(
				$issues,
				$line,
				'terminal-newline',
				'File must not contain blank lines after its terminal newline.'
			);
		}
	}

	/**
	 * Detect spaces or tabs immediately before a line ending or end of file.
	 *
	 * @param   string                                                        $source  Complete PHP source.
	 * @param   array<string, array{line: int, rule: string, message: string}>  $issues  Collected issues.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function inspectTrailingWhitespace(string $source, array &$issues): void
	{
		$result = preg_match_all('/[ \t]+(?=\r?$)/m', $source, $matches, PREG_OFFSET_CAPTURE);

		if ($result === false || $result === 0)
		{
			return;
		}

		foreach ($matches[0] as $match)
		{
			self::addIssue(
				$issues,
				self::lineForOffset($source, $match[1]),
				'trailing-whitespace',
				'Trailing spaces and tabs are forbidden.'
			);
		}
	}

	/**
	 * Detect forbidden PHP tokens and declarations.
	 *
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>            $tokens  PHP token stream.
	 * @param   array<string, array{line: int, rule: string, message: string}>  $issues  Collected issues.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function inspectTokens(array $tokens, array &$issues): void
	{
		foreach ($tokens as $index => $token)
		{
			if (!is_array($token))
			{
				continue;
			}

			if ($token[0] === T_CLOSE_TAG)
			{
				self::addIssue(
					$issues,
					$token[2],
					'closing-tag',
					'PHP-only files must not contain a closing PHP tag.'
				);
			}

			if ($token[0] !== T_DECLARE)
			{
				continue;
			}

			$hasStrictTypes = false;

			for ($cursor = $index + 1, $count = count($tokens); $cursor < $count; $cursor++)
			{
				$part = $tokens[$cursor];

				if (is_array($part)
					&& $part[0] === T_STRING
					&& strtolower($part[1]) === 'strict_types')
				{
					$hasStrictTypes = true;
				}

				if ($part === ';' || $part === '{')
				{
					break;
				}
			}

			if ($hasStrictTypes)
			{
				self::addIssue(
					$issues,
					$token[2],
					'strict-types',
					'Do not introduce declare(strict_types); into vendor-library PHP.'
				);
			}
		}
	}

	/**
	 * Detect leading structural indentation that uses spaces.
	 *
	 * @param   string                                                        $source  Complete PHP source.
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>            $tokens  PHP token stream.
	 * @param   array<string, array{line: int, rule: string, message: string}>  $issues  Collected issues.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function inspectIndentation(string $source, array $tokens, array &$issues): void
	{
		$categories = self::lineCategories($tokens);
		$lines = explode("\n", $source);

		foreach ($categories as $lineNumber => $category)
		{
			if ($category !== 'code')
			{
				continue;
			}

			$line = $lines[$lineNumber - 1] ?? '';

			if (preg_match('/^[ \t]* /', $line) !== 1)
			{
				continue;
			}

			self::addIssue(
				$issues,
				$lineNumber,
				'indentation',
				'Leading PHP structural indentation must use tabs, not spaces.'
			);
		}
	}

	/**
	 * Classify the first meaningful token content on every source line.
	 *
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>  $tokens  PHP token stream.
	 *
	 * @return  array<int, string>
	 * @since   1.0.0
	 */
	private static function lineCategories(array $tokens): array
	{
		$categories = [];
		$line = 1;
		$inDoubleQuote = false;
		$inBacktick = false;
		$inHeredoc = false;

		foreach ($tokens as $token)
		{
			$text = is_array($token) ? $token[1] : $token;
			$category = 'code';
			$firstLineOnly = false;

			if (is_array($token))
			{
				$tokenId = $token[0];

				if ($inHeredoc)
				{
					$category = 'ignored';

					if ($tokenId === T_END_HEREDOC)
					{
						$inHeredoc = false;
					}
				}
				elseif ($tokenId === T_START_HEREDOC)
				{
					$firstLineOnly = true;
					$inHeredoc = true;
				}
				elseif ($inDoubleQuote || $inBacktick || $tokenId === T_ENCAPSED_AND_WHITESPACE)
				{
					$category = 'ignored';
				}
				elseif (in_array($tokenId, [T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML], true))
				{
					$category = 'ignored';
				}
				elseif ($tokenId === T_WHITESPACE)
				{
					$category = 'whitespace';
				}
				elseif ($tokenId === T_CONSTANT_ENCAPSED_STRING && str_contains($text, "\n"))
				{
					$firstLineOnly = true;
				}
			}
			elseif ($inHeredoc)
			{
				$category = 'ignored';
			}
			elseif ($text === '"')
			{
				$category = $inDoubleQuote ? 'ignored' : 'code';
				$inDoubleQuote = !$inDoubleQuote;
			}
			elseif ($text === '`')
			{
				$category = $inBacktick ? 'ignored' : 'code';
				$inBacktick = !$inBacktick;
			}
			elseif ($inDoubleQuote || $inBacktick)
			{
				$category = 'ignored';
			}

			self::recordLineCategories($text, $category, $firstLineOnly, $line, $categories);
		}

		return $categories;
	}

	/**
	 * Record line classifications for one token's possibly multiline text.
	 *
	 * @param   string              $text           Token text.
	 * @param   string              $category       Code, ignored content, or whitespace.
	 * @param   bool                $firstLineOnly  Whether later token lines are literal content.
	 * @param   int                 $line           Current source line.
	 * @param   array<int, string>  $categories     First meaningful category by line.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function recordLineCategories(
		string $text,
		string $category,
		bool $firstLineOnly,
		int &$line,
		array &$categories
	): void
	{
		$fragments = explode("\n", $text);
		$last = count($fragments) - 1;

		foreach ($fragments as $index => $fragment)
		{
			$effectiveCategory = $firstLineOnly && $index > 0 ? 'ignored' : $category;

			if (
				$effectiveCategory !== 'whitespace'
				&& !isset($categories[$line])
				&& trim($fragment, " \t\r") !== ''
			)
			{
				$categories[$line] = $effectiveCategory;
			}

			if ($index < $last)
			{
				$line++;
			}
		}
	}

	/**
	 * Require structural opening braces to begin their own line.
	 *
	 * Dynamic member syntax such as `->{$method}` is an expression and is
	 * intentionally excluded. Braces inside strings and heredocs are ignored.
	 *
	 * @param   string                                                        $source  Complete PHP source.
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>            $tokens  PHP token stream.
	 * @param   array<string, array{line: int, rule: string, message: string}>  $issues  Collected issues.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function inspectOpeningBraces(string $source, array $tokens, array &$issues): void
	{
		$lines = explode("\n", $source);
		$line = 1;
		$column = 0;
		$inDoubleQuote = false;
		$inBacktick = false;
		$inHeredoc = false;
		$previousSignificant = null;
		$dynamicTokens = [T_OBJECT_OPERATOR, T_DOUBLE_COLON];

		if (defined('T_NULLSAFE_OBJECT_OPERATOR'))
		{
			$dynamicTokens[] = constant('T_NULLSAFE_OBJECT_OPERATOR');
		}

		foreach ($tokens as $token)
		{
			$text = is_array($token) ? $token[1] : $token;

			if (is_array($token))
			{
				$tokenId = $token[0];

				if ($inHeredoc)
				{
					if ($tokenId === T_END_HEREDOC)
					{
						$inHeredoc = false;
					}
				}
				elseif ($tokenId === T_START_HEREDOC)
				{
					$inHeredoc = true;
					$previousSignificant = $tokenId;
				}
				elseif (!$inDoubleQuote && !$inBacktick
					&& !in_array($tokenId, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
				{
					$previousSignificant = $tokenId;
				}
			}
			elseif (!$inHeredoc && $text === '"')
			{
				$inDoubleQuote = !$inDoubleQuote;
			}
			elseif (!$inHeredoc && $text === '`')
			{
				$inBacktick = !$inBacktick;
			}
			elseif (!$inHeredoc && !$inDoubleQuote && !$inBacktick)
			{
				if ($text === '{'
					&& !in_array($previousSignificant, $dynamicTokens, true)
					&& $previousSignificant !== '$')
				{
					$prefix = substr($lines[$line - 1] ?? '', 0, $column);

					if (trim($prefix, " \t\r") !== '')
					{
						self::addIssue(
							$issues,
							$line,
							'opening-brace',
							'Structural opening braces must use Allman style on a new line.'
						);
					}
				}

				if (trim($text) !== '')
				{
					$previousSignificant = $text;
				}
			}

			self::advancePosition($text, $line, $column);
		}
	}

	/**
	 * Require the standard JCB file header and its identifying tags.
	 *
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>            $tokens  PHP token stream.
	 * @param   bool                                                           $isTest  Whether the file is test-owned PHP.
	 * @param   array<string, array{line: int, rule: string, message: string}>  $issues  Collected issues.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function inspectFileHeader(
		array $tokens,
		bool $isTest,
		array &$issues
	): void
	{
		$seenOpenTag = false;
		$header = null;

		foreach ($tokens as $token)
		{
			if (!$seenOpenTag)
			{
				if (is_array($token) && $token[0] === T_OPEN_TAG)
				{
					$seenOpenTag = true;
				}

				continue;
			}

			if (is_array($token) && $token[0] === T_WHITESPACE)
			{
				continue;
			}

			if (is_array($token) && $token[0] === T_DOC_COMMENT)
			{
				$header = $token;
			}

			break;
		}

		if ($header === null)
		{
			self::addIssue(
				$issues,
				1,
				'file-header',
				'First-party PHP must begin with the standard JCB file header.'
			);

			return;
		}

		$package = $isTest
			? 'Joomla.Component.Builder.Tests'
			: 'Joomla.Component.Builder';

		if (preg_match(
			'/^[ \t]*\*[ \t]*@package[ \t]+'
			. preg_quote($package, '/')
			. '[ \t]*$/m',
			$header[1]
		) !== 1)
		{
			self::addIssue(
				$issues,
				$header[2],
				'file-header',
				'Standard JCB file header requires @package ' . $package . '.'
			);
		}

		foreach (self::HEADER_TAGS as $tag => $pattern)
		{
			if (preg_match($pattern, $header[1]) === 1)
			{
				continue;
			}

			self::addIssue(
				$issues,
				$header[2],
				'file-header',
				'Standard JCB file header is missing @' . $tag . '.'
			);
		}
	}

	/**
	 * Require named first-party declarations and class members to document @since.
	 *
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>            $tokens  PHP token stream.
	 * @param   array<string, array{line: int, rule: string, message: string}>  $issues  Collected issues.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function inspectDocumentation(array $tokens, array &$issues): void
	{
		$classTokens = [T_CLASS, T_INTERFACE, T_TRAIT];

		if (defined('T_ENUM'))
		{
			$classTokens[] = constant('T_ENUM');
		}

		$braceDepth = 0;
		$parenthesisDepth = 0;
		$classBodyDepths = [];
		$pendingClassBodies = [];
		$inDoubleQuote = false;
		$inBacktick = false;
		$inHeredoc = false;

		foreach ($tokens as $index => $token)
		{
			if (is_array($token))
			{
				$tokenId = $token[0];

				if ($inHeredoc)
				{
					if ($tokenId === T_END_HEREDOC)
					{
						$inHeredoc = false;
					}

					continue;
				}

				if ($tokenId === T_START_HEREDOC)
				{
					$inHeredoc = true;
					continue;
				}

				if ($inDoubleQuote || $inBacktick || $tokenId === T_ENCAPSED_AND_WHITESPACE)
				{
					continue;
				}

				if (in_array($tokenId, $classTokens, true)
					&& self::previousSignificantToken($tokens, $index) !== T_DOUBLE_COLON)
				{
					$name = self::readNamedDeclaration($tokens, $index + 1);
					$pendingClassBodies[] = $parenthesisDepth;

					if ($name !== null)
					{
						self::inspectElementDocblock(
							$tokens,
							$index,
							$token[2],
							'declaration',
							$name,
							$issues
						);
					}
				}

				$insideClassBody = $classBodyDepths !== []
					&& $braceDepth === end($classBodyDepths);

				if ($insideClassBody && $parenthesisDepth === 0 && $tokenId === T_FUNCTION)
				{
					$name = self::readNamedFunction($tokens, $index + 1);

					if ($name !== null)
					{
						self::inspectElementDocblock(
							$tokens,
							$index,
							$token[2],
							'method',
							$name . '()',
							$issues
						);
					}
				}
				elseif ($insideClassBody && $parenthesisDepth === 0 && $tokenId === T_VARIABLE)
				{
					self::inspectElementDocblock(
						$tokens,
						$index,
						$token[2],
						'member',
						$token[1],
						$issues
					);
				}
				elseif ($insideClassBody && $parenthesisDepth === 0 && $tokenId === T_CONST)
				{
					self::inspectElementDocblock(
						$tokens,
						$index,
						$token[2],
						'member',
						'class constant',
						$issues
					);
				}

				continue;
			}

			if ($inHeredoc)
			{
				continue;
			}

			if ($token === '"')
			{
				$inDoubleQuote = !$inDoubleQuote;
				continue;
			}

			if ($token === '`')
			{
				$inBacktick = !$inBacktick;
				continue;
			}

			if ($inDoubleQuote || $inBacktick)
			{
				continue;
			}

			if ($token === '(')
			{
				$parenthesisDepth++;
				continue;
			}

			if ($token === ')')
			{
				$parenthesisDepth = max(0, $parenthesisDepth - 1);
				continue;
			}

			if ($token === '{')
			{
				$braceDepth++;

				if ($pendingClassBodies !== []
					&& end($pendingClassBodies) === $parenthesisDepth)
				{
					array_pop($pendingClassBodies);
					$classBodyDepths[] = $braceDepth;
				}

				continue;
			}

			if ($token === '}')
			{
				if ($classBodyDepths !== [] && end($classBodyDepths) === $braceDepth)
				{
					array_pop($classBodyDepths);
				}

				$braceDepth = max(0, $braceDepth - 1);
			}
		}
	}

	/**
	 * Validate the owning docblock for one declaration or class member.
	 *
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>            $tokens       PHP token stream.
	 * @param   int                                                            $index        Element token index.
	 * @param   int                                                            $line         Element source line.
	 * @param   string                                                         $kind         Declaration, member, or method.
	 * @param   string                                                         $name         Element display name.
	 * @param   array<string, array{line: int, rule: string, message: string}>  $issues       Collected issues.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function inspectElementDocblock(
		array $tokens,
		int $index,
		int $line,
		string $kind,
		string $name,
		array &$issues
	): void
	{
		$docblock = $name === 'class constant'
			? self::constantGroupDocblockBefore($tokens, $index)
			: self::docblockBefore($tokens, $index);
		$rule = $kind . '-docblock';

		if ($docblock === null
			|| preg_match('/@package\s+Joomla\.Component\.Builder(?:\.Tests)?\b/', $docblock[1]) === 1)
		{
			self::addIssue(
				$issues,
				$line,
				$rule,
				ucfirst($kind) . ' ' . $name . ' requires a meaningful docblock.'
			);

			return;
		}

		if (preg_match('/@since\s+\S+/', $docblock[1]) !== 1
			&& preg_match('/\{?@inheritDoc\}?/i', $docblock[1]) !== 1)
		{
			self::addIssue(
				$issues,
				$docblock[2],
				'since-tag',
				ucfirst($kind) . ' ' . $name . ' docblock requires @since.'
			);
		}
	}

	/**
	 * Find the docblock attached to the following declaration statement.
	 *
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>  $tokens  PHP token stream.
	 * @param   int                                                  $index   Element token index.
	 *
	 * @return  array{0: int, 1: string, 2: int}|null
	 * @since   1.0.0
	 */
	private static function docblockBefore(array $tokens, int $index): ?array
	{
		for ($cursor = $index - 1; $cursor >= 0; $cursor--)
		{
			$token = $tokens[$cursor];

			if (is_string($token) && in_array($token, [';', '{', '}'], true))
			{
				return null;
			}

			if (is_array($token) && $token[0] === T_DOC_COMMENT)
			{
				return $token;
			}
		}

		return null;
	}

	/**
	 * Find a shared docblock for a contiguous group of class constants.
	 *
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>  $tokens  PHP token stream.
	 * @param   int                                                  $index   Current T_CONST token index.
	 *
	 * @return  array{0: int, 1: string, 2: int}|null
	 * @since   1.0.0
	 */
	private static function constantGroupDocblockBefore(array $tokens, int $index): ?array
	{
		$docblock = self::docblockBefore($tokens, $index);

		if ($docblock !== null)
		{
			return $docblock;
		}

		$seenConstant = false;

		for ($cursor = $index - 1; $cursor >= 0; $cursor--)
		{
			$token = $tokens[$cursor];

			if (is_string($token) && in_array($token, ['{', '}'], true))
			{
				return null;
			}

			if (!is_array($token))
			{
				continue;
			}

			if ($token[0] === T_FUNCTION || $token[0] === T_VARIABLE)
			{
				return null;
			}

			if ($token[0] === T_CONST)
			{
				$seenConstant = true;
				continue;
			}

			if ($token[0] === T_DOC_COMMENT)
			{
				return $seenConstant ? $token : null;
			}
		}

		return null;
	}

	/**
	 * Read a named class-like declaration immediately after its keyword.
	 *
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>  $tokens  PHP token stream.
	 * @param   int                                                  $start   First token after the keyword.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private static function readNamedDeclaration(array $tokens, int $start): ?string
	{
		for ($index = $start, $count = count($tokens); $index < $count; $index++)
		{
			$token = $tokens[$index];

			if (is_array($token) && $token[0] === T_WHITESPACE)
			{
				continue;
			}

			return is_array($token) && $token[0] === T_STRING ? $token[1] : null;
		}

		return null;
	}

	/**
	 * Read a named method after T_FUNCTION while excluding closures.
	 *
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>  $tokens  PHP token stream.
	 * @param   int                                                  $start   First token after T_FUNCTION.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private static function readNamedFunction(array $tokens, int $start): ?string
	{
		for ($index = $start, $count = count($tokens); $index < $count; $index++)
		{
			$token = $tokens[$index];

			if (is_array($token) && $token[0] === T_WHITESPACE)
			{
				continue;
			}

			if ($token === '&')
			{
				continue;
			}

			return is_array($token) && $token[0] === T_STRING ? $token[1] : null;
		}

		return null;
	}

	/**
	 * Read the previous significant token identifier or literal.
	 *
	 * @param   array<int, array{0: int, 1: string, 2?: int}|string>  $tokens  PHP token stream.
	 * @param   int                                                  $index   Current token index.
	 *
	 * @return  int|string|null
	 * @since   1.0.0
	 */
	private static function previousSignificantToken(array $tokens, int $index): int|string|null
	{
		for ($cursor = $index - 1; $cursor >= 0; $cursor--)
		{
			$token = $tokens[$cursor];

			if (is_array($token))
			{
				if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
				{
					continue;
				}

				return $token[0];
			}

			if (trim($token) !== '')
			{
				return $token;
			}
		}

		return null;
	}

	/**
	 * Find added new-side line numbers for one modified file.
	 *
	 * @param   string  $repositoryRoot  Repository root directory.
	 * @param   string  $baseSha         Base commit SHA.
	 * @param   string  $path            Repository-relative modified path.
	 *
	 * @return  array<int, true>
	 * @since   1.0.0
	 */
	private static function addedLinesSince(
		string $repositoryRoot,
		string $baseSha,
		string $path
	): array
	{
		$output = self::runProcess(
			[
				'git',
				'-C',
				$repositoryRoot,
				'diff',
				'--unified=0',
				'--no-color',
				$baseSha . '...HEAD',
				'--',
				$path
			],
			$repositoryRoot,
			'discover added lines for ' . $path
		);
		$lines = [];
		$result = preg_match_all(
			'/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/m',
			$output,
			$matches,
			PREG_SET_ORDER
		);

		if ($result === false || $result === 0)
		{
			return $lines;
		}

		foreach ($matches as $match)
		{
			$start = (int) $match[1];
			$count = isset($match[2]) && $match[2] !== '' ? (int) $match[2] : 1;

			for ($line = $start; $line < $start + $count; $line++)
			{
				$lines[$line] = true;
			}
		}

		return $lines;
	}

	/**
	 * Recursively collect project-owned PHP without following symlinks.
	 *
	 * @param   string         $absoluteDirectory  Current absolute directory.
	 * @param   string         $relativeDirectory  Current repository-relative directory.
	 * @param   array<string>  $paths              Collected repository-relative PHP paths.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function collectTestPhpFiles(
		string $absoluteDirectory,
		string $relativeDirectory,
		array &$paths
	): void
	{
		$iterator = new FilesystemIterator($absoluteDirectory, FilesystemIterator::SKIP_DOTS);

		foreach ($iterator as $item)
		{
			$name = $item->getFilename();

			if ($item->isLink())
			{
				continue;
			}

			if ($item->isDir())
			{
				if (in_array($name, self::IGNORED_TEST_DIRECTORIES, true))
				{
					continue;
				}

				self::collectTestPhpFiles(
					$item->getPathname(),
					$relativeDirectory . '/' . $name,
					$paths
				);
				continue;
			}

			if ($item->isFile() && strtolower($item->getExtension()) === 'php')
			{
				$paths[] = $relativeDirectory . '/' . $name;
			}
		}
	}

	/**
	 * Execute a process without invoking a shell.
	 *
	 * @param   array<string>  $command           Command and arguments.
	 * @param   string         $workingDirectory  Process working directory.
	 * @param   string         $purpose           Failure context.
	 *
	 * @return  string
	 *
	 * @throws  RuntimeException  If the process cannot run successfully.
	 *
	 * @since   1.0.0
	 */
	private static function runProcess(
		array $command,
		string $workingDirectory,
		string $purpose
	): string
	{
		$descriptors = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w']
		];
		$process = proc_open($command, $descriptors, $pipes, $workingDirectory);

		if (!is_resource($process))
		{
			throw new RuntimeException('Unable to start Git to ' . $purpose . '.');
		}

		$output = stream_get_contents($pipes[1]);
		$errorOutput = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);

		if ($exitCode !== 0)
		{
			throw new RuntimeException(
				'Unable to ' . $purpose . ': ' . trim((string) $errorOutput)
			);
		}

		return (string) $output;
	}

	/**
	 * Add one unique structured issue.
	 *
	 * @param   array<string, array{line: int, rule: string, message: string}>  $issues   Collected issues.
	 * @param   int                                                            $line     Source line.
	 * @param   string                                                         $rule     Stable rule identifier.
	 * @param   string                                                         $message  Actionable diagnostic.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function addIssue(
		array &$issues,
		int $line,
		string $rule,
		string $message
	): void
	{
		$key = $line . "\0" . $rule . "\0" . $message;
		$issues[$key] = [
			'line' => max(1, $line),
			'rule' => $rule,
			'message' => $message
		];
	}

	/**
	 * Filter, sort, and format diagnostics.
	 *
	 * @param   array<string, array{line: int, rule: string, message: string}>  $issues       Structured issues.
	 * @param   string                                                         $displayPath  Diagnostic path.
	 * @param   array<int, true>|null                                          $addedLines   Added lines or null.
	 *
	 * @return  array<string>
	 * @since   1.0.0
	 */
	private static function formatIssues(
		array $issues,
		string $displayPath,
		?array $addedLines
	): array
	{
		$filtered = [];

		foreach ($issues as $issue)
		{
			if (
				$addedLines !== null
				&& !isset($addedLines[$issue['line']])
				&& !in_array($issue['rule'], self::WHOLE_FILE_RULES, true)
			)
			{
				continue;
			}

			$filtered[] = $issue;
		}

		usort(
			$filtered,
			static function (array $left, array $right): int
			{
				return [$left['line'], $left['rule'], $left['message']]
					<=> [$right['line'], $right['rule'], $right['message']];
			}
		);
		$formatted = [];

		foreach ($filtered as $issue)
		{
			$formatted[] = sprintf(
				'%s:%d: [%s] %s',
				$displayPath,
				$issue['line'],
				$issue['rule'],
				$issue['message']
			);
		}

		return $formatted;
	}

	/**
	 * Advance a byte column and one-based line through token text.
	 *
	 * @param   string  $text    Token text.
	 * @param   int     $line    Current line.
	 * @param   int     $column  Current zero-based byte column.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private static function advancePosition(string $text, int &$line, int &$column): void
	{
		$lastNewline = strrpos($text, "\n");

		if ($lastNewline === false)
		{
			$column += strlen($text);

			return;
		}

		$line += substr_count($text, "\n");
		$column = strlen($text) - $lastNewline - 1;
	}

	/**
	 * Resolve a one-based line number for a byte offset.
	 *
	 * @param   string  $source  Complete source.
	 * @param   int     $offset  Byte offset.
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	private static function lineForOffset(string $source, int $offset): int
	{
		return substr_count($source, "\n", 0, $offset) + 1;
	}

	/**
	 * Determine whether a display path belongs to project-owned test PHP.
	 *
	 * @param   string  $path  Display or repository-relative path.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	private static function isFirstPartyTestPath(string $path): bool
	{
		$path = self::normalizeRepositoryPath($path);

		return str_starts_with($path, self::TEST_ROOT . '/')
			&& self::isCandidatePath($path);
	}

	/**
	 * Normalize and validate a repository root.
	 *
	 * @param   string  $repositoryRoot  Repository root directory.
	 *
	 * @return  string
	 *
	 * @throws  RuntimeException  If the directory cannot be resolved.
	 *
	 * @since   1.0.0
	 */
	private static function normalizeRepositoryRoot(string $repositoryRoot): string
	{
		$resolved = realpath($repositoryRoot);

		if ($resolved === false || !is_dir($resolved))
		{
			throw new RuntimeException('Repository root does not exist: ' . $repositoryRoot);
		}

		return rtrim(str_replace('\\', '/', $resolved), '/');
	}

	/**
	 * Normalize repository path separators.
	 *
	 * @param   string  $path  Repository-relative path.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private static function normalizeRepositoryPath(string $path): string
	{
		return str_replace('\\', '/', trim($path));
	}

	/**
	 * Validate a repository-relative path before filesystem use.
	 *
	 * @param   string  $path  Repository-relative path.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private static function repositoryPathError(string $path): ?string
	{
		if ($path === '' || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\//', $path) === 1)
		{
			return 'path must be repository-relative';
		}

		if (str_contains($path, "\0"))
		{
			return 'path cannot contain a null byte';
		}

		if (in_array('..', explode('/', $path), true))
		{
			return 'path cannot traverse its repository root';
		}

		return null;
	}
}
