<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    22nd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Powers\Reader;


use VDM\Joomla\Componentbuilder\Power\Parser;


/**
 * Reads one PHP file into the parts a power row stores.
 *
 * The declaration is located with PHP's own lexer rather than patterns, so a
 * class name inside a string, a ::class constant, or an anonymous class can
 * never be mistaken for the file's declaration. The body, license and import
 * statements come from the shared power Parser, so what this reader extracts is
 * exactly what the compiler will later reassemble.
 *
 * Reading never interprets: what a parent or import refers to is the
 * assembler's question, so this class hands back names exactly as written.
 *
 * @since 6.1.7
 */
final class ClassFile
{
	/**
	 * The declaration keywords and the power type each one declares.
	 *
	 * An enum has no power type, so it is read but reported unsupported.
	 *
	 * @var    array<int, string|null>
	 * @since  6.1.7
	 */
	private const KINDS = [
		T_CLASS => 'class',
		T_INTERFACE => 'interface',
		T_TRAIT => 'trait',
		T_ENUM => null
	];

	/**
	 * The Power Parser.
	 *
	 * @var    Parser
	 * @since  6.1.7
	 */
	protected Parser $parser;

	/**
	 * Constructor.
	 *
	 * @param   Parser  $parser  The shared power parser.
	 *
	 * @since   6.1.7
	 */
	public function __construct(Parser $parser)
	{
		$this->parser = $parser;
	}

	/**
	 * Read one PHP file's source into the parts a power row stores.
	 *
	 * @param   string  $code  The complete file source.
	 *
	 * @return  array{
	 *              namespace: string, class: string, type: string|null,
	 *              docblock: string, license: string,
	 *              extends: array<string>, implements: array<string>,
	 *              uses: array<int, array{raw: string, name: string, alias: string|null, kind: string}>,
	 *              body: string
	 *          }|null  The parts, or null when the file declares no class at all.
	 * @since   6.1.7
	 */
	public function read(string $code): ?array
	{
		$declaration = $this->declaration($code);

		if ($declaration === null)
		{
			return null;
		}

		$declaration['license'] = $this->license($code);
		$declaration['uses'] = $this->imports($code);
		$declaration['body'] = (string) $this->parser->getClassCode($code);

		return $declaration;
	}

	/**
	 * Locate and decompose the first real type declaration in the file.
	 *
	 * @param   string  $code  The complete file source.
	 *
	 * @return  array{
	 *              namespace: string, class: string, type: string|null,
	 *              docblock: string, extends: array<string>, implements: array<string>
	 *          }|null  The declaration parts, or null when none was found.
	 * @since   6.1.7
	 */
	protected function declaration(string $code): ?array
	{
		$tokens = @token_get_all($code);

		if (!is_array($tokens) || $tokens === [])
		{
			return null;
		}

		$namespace = '';
		$docblock = null;
		$final = false;
		$abstract = false;
		$previous = 0;
		$total = count($tokens);

		for ($i = 0; $i < $total; $i++)
		{
			$token = $tokens[$i];

			if (!is_array($token))
			{
				// punctuation ends whatever statement the last docblock described
				$docblock = null;
				$final = false;
				$abstract = false;
				$previous = 0;

				continue;
			}

			switch ($token[0])
			{
				case T_WHITESPACE:
				case T_COMMENT:
					continue 2;

				case T_DOC_COMMENT:
					$docblock = $token[1];
					continue 2;

				case T_ATTRIBUTE:
					// an attribute stands between a docblock and its subject
					$i = $this->closingBracket($tokens, $i);
					continue 2;

				case T_NAMESPACE:
					$namespace = $this->name($tokens, $i);
					$docblock = null;
					$final = false;
					$abstract = false;
					$previous = $token[0];
					continue 2;

				case T_FINAL:
					$final = true;
					$previous = $token[0];
					continue 2;

				case T_ABSTRACT:
					$abstract = true;
					$previous = $token[0];
					continue 2;

				case T_READONLY:
					// a readonly class is still stored as a plain class
					$previous = $token[0];
					continue 2;

				case T_CLASS:
				case T_INTERFACE:
				case T_TRAIT:
				case T_ENUM:
					// a ::class constant or an anonymous class is not a declaration
					if ($previous === T_DOUBLE_COLON || $previous === T_NEW)
					{
						$previous = $token[0];
						continue 2;
					}

					return $this->decompose(
						$tokens, $i, $token[0], $namespace,
						$docblock, $final, $abstract
					);

				default:
					// any other statement claims the docblock and the modifiers
					$docblock = null;
					$final = false;
					$abstract = false;
					$previous = $token[0];
					continue 2;
			}
		}

		return null;
	}

	/**
	 * Decompose one located declaration into its parts.
	 *
	 * @param   array<int, mixed>  $tokens     The file's tokens.
	 * @param   int                $at         The offset of the declaration keyword.
	 * @param   int                $kind       The declaration keyword token.
	 * @param   string             $namespace  The namespace the declaration sits in.
	 * @param   string|null        $docblock   The docblock standing above it.
	 * @param   bool               $final      Whether it was declared final.
	 * @param   bool               $abstract   Whether it was declared abstract.
	 *
	 * @return  array{
	 *              namespace: string, class: string, type: string|null,
	 *              docblock: string, extends: array<string>, implements: array<string>
	 *          }|null  The declaration parts, or null when it carries no name.
	 * @since   6.1.7
	 */
	protected function decompose(
		array $tokens,
		int $at,
		int $kind,
		string $namespace,
		?string $docblock,
		bool $final,
		bool $abstract
	): ?array
	{
		$class = '';
		$named = ['extends' => [], 'implements' => []];
		$bucket = '';
		$buffer = '';
		$total = count($tokens);

		for ($i = $at + 1; $i < $total; $i++)
		{
			$token = $tokens[$i];

			if (!is_array($token))
			{
				if ($token === '{')
				{
					break;
				}

				if ($token === ',' && $bucket !== '' && $buffer !== '')
				{
					$named[$bucket][] = $buffer;
					$buffer = '';
				}

				continue;
			}

			switch ($token[0])
			{
				case T_STRING:
				case T_NAME_QUALIFIED:
				case T_NAME_FULLY_QUALIFIED:
				case T_NAME_RELATIVE:
				case T_NS_SEPARATOR:
					if ($class === '')
					{
						$class = $token[1];
					}
					elseif ($bucket !== '')
					{
						$buffer .= $token[1];
					}
					break;

				case T_EXTENDS:
				case T_IMPLEMENTS:
					if ($bucket !== '' && $buffer !== '')
					{
						$named[$bucket][] = $buffer;
						$buffer = '';
					}

					$bucket = $token[0] === T_EXTENDS ? 'extends' : 'implements';
					break;
			}
		}

		if ($bucket !== '' && $buffer !== '')
		{
			$named[$bucket][] = $buffer;
		}

		if ($class === '')
		{
			return null;
		}

		$type = self::KINDS[$kind];

		if ($type === 'class' && $abstract)
		{
			$type = 'abstract class';
		}
		elseif ($type === 'class' && $final)
		{
			$type = 'final class';
		}

		return [
			'namespace' => $namespace,
			'class' => $class,
			'type' => $type,
			'docblock' => $this->comment((string) $docblock),
			'extends' => $named['extends'],
			'implements' => $named['implements']
		];
	}

	/**
	 * The file's license header, as the complete comment block.
	 *
	 * A power's licensing template stores the whole block, markers included,
	 * exactly as the compiler will emit it again -- so the block is taken
	 * verbatim rather than through the parser, which strips the markers.
	 * Only a comment that opens the file states its license.
	 *
	 * @param   string  $code  The complete file source.
	 *
	 * @return  string  The license block, or an empty string.
	 * @since   6.1.7
	 */
	protected function license(string $code): string
	{
		if (preg_match('/\A(?:\xEF\xBB\xBF)?<\?php\s+(\/\*.*?\*\/)/s', $code, $matches) === 1)
		{
			return $matches[1];
		}

		return '';
	}

	/**
	 * Every import statement above the class, decomposed for linking.
	 *
	 * @param   string  $code  The complete file source.
	 *
	 * @return  array<int, array{raw: string, name: string, alias: string|null, kind: string}>  The imports.
	 * @since   6.1.7
	 */
	protected function imports(string $code): array
	{
		$imports = [];

		foreach ((array) $this->parser->getUseStatements($code) as $statement)
		{
			foreach ($this->import((string) $statement) as $import)
			{
				$imports[] = $import;
			}
		}

		return $imports;
	}

	/**
	 * Decompose one import statement, expanding groups and comma lists.
	 *
	 * @param   string  $statement  The complete use statement.
	 *
	 * @return  array<int, array{raw: string, name: string, alias: string|null, kind: string}>  The imports it binds.
	 * @since   6.1.7
	 */
	protected function import(string $statement): array
	{
		$inner = trim(preg_replace('/^use\s+/i', '', rtrim(trim($statement), ';')));
		$kind = 'class';

		if (preg_match('/^(function|const)\s+/i', $inner, $matches) === 1)
		{
			$kind = strtolower($matches[1]);
			$inner = trim(substr($inner, strlen($matches[0])));
		}

		// a group import binds every branch under one prefix
		if (preg_match('/^([^\{\}]+)\{([^\{\}]+)\}$/', $inner, $matches) === 1)
		{
			$prefix = rtrim(trim($matches[1]), '\\');
			$branches = explode(',', $matches[2]);
			$imports = [];

			foreach ($branches as $branch)
			{
				$import = $this->binding($prefix . '\\' . trim($branch), $kind);

				if ($import !== null)
				{
					$imports[] = $import;
				}
			}

			return $imports;
		}

		$imports = [];

		foreach (explode(',', $inner) as $part)
		{
			$import = $this->binding(trim($part), $kind);

			if ($import !== null)
			{
				$imports[] = $import;
			}
		}

		return $imports;
	}

	/**
	 * Decompose one name-with-optional-alias binding.
	 *
	 * @param   string  $binding  The bound name, possibly aliased.
	 * @param   string  $kind     Whether a class, function or const was bound.
	 *
	 * @return  array{raw: string, name: string, alias: string|null, kind: string}|null  The import, or null when empty.
	 * @since   6.1.7
	 */
	protected function binding(string $binding, string $kind): ?array
	{
		$alias = null;

		if (preg_match('/^(.*?)\s+as\s+(\S+)$/i', $binding, $matches) === 1)
		{
			$binding = trim($matches[1]);
			$alias = trim($matches[2]);
		}

		$binding = ltrim(trim($binding), '\\');

		if ($binding === '')
		{
			return null;
		}

		$prefix = $kind === 'class' ? '' : $kind . ' ';

		return [
			'raw' => 'use ' . $prefix . $binding
				. ($alias !== null ? ' as ' . $alias : '') . ';',
			'name' => $binding,
			'alias' => $alias,
			'kind' => $kind
		];
	}

	/**
	 * The offset that closes the attribute opened at the given offset.
	 *
	 * @param   array<int, mixed>  $tokens  The file's tokens.
	 * @param   int                $at      The offset of the attribute opener.
	 *
	 * @return  int  The offset of the closing bracket, or the last offset.
	 * @since   6.1.7
	 */
	protected function closingBracket(array $tokens, int $at): int
	{
		$depth = 1;
		$total = count($tokens);

		for ($i = $at + 1; $i < $total; $i++)
		{
			$token = $tokens[$i];

			if ($token === '[')
			{
				$depth++;
			}
			elseif ($token === ']' && --$depth === 0)
			{
				return $i;
			}
		}

		return $total - 1;
	}

	/**
	 * The name written directly after the token at the given offset.
	 *
	 * @param   array<int, mixed>  $tokens  The file's tokens.
	 * @param   int                $at      The offset of the introducing keyword.
	 *
	 * @return  string  The name, or an empty string.
	 * @since   6.1.7
	 */
	protected function name(array $tokens, int $at): string
	{
		$name = '';
		$total = count($tokens);

		for ($i = $at + 1; $i < $total; $i++)
		{
			$token = $tokens[$i];

			if (!is_array($token))
			{
				break;
			}

			if ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT)
			{
				continue;
			}

			if ($token[0] === T_STRING
				|| $token[0] === T_NAME_QUALIFIED
				|| $token[0] === T_NS_SEPARATOR)
			{
				$name .= $token[1];
				continue;
			}

			break;
		}

		return trim($name, '\\');
	}

	/**
	 * Strip the comment markers off a docblock, keeping its text.
	 *
	 * @param   string  $comment  The raw docblock.
	 *
	 * @return  string  The cleaned text, line by line.
	 * @since   6.1.7
	 */
	protected function comment(string $comment): string
	{
		if ($comment === '')
		{
			return '';
		}

		$comment = preg_replace('/^\/\*\*[\r\n\s]*|[\r\n\s]*\*\/$/m', '', $comment);
		$comment = preg_replace('/^[\s]*\*[\s]?/m', '', (string) $comment);
		$lines = array_map('trim', preg_split('/\r\n|\r|\n/', (string) $comment));

		return implode("\n", array_filter($lines, static fn (string $line): bool => $line !== ''));
	}
}
