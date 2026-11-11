<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    11th November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


use ReflectionProperty;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\ConditionalRule;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\ValidationFix;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ValidationFix as Conditions;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;


/**
 * Reproduce conditional model previews from explicit normalized fixture input.
 *
 * Only designated members of the existing generated application are replaced.
 * Token offsets preserve all unrelated source bytes. This is not a component
 * build or an export of the authoritative JCB maintenance package.
 *
 * @since  6.2.1
 */
final class ConditionalPreviewCompiler
{
	/**
	 * Render exact production fragments with stable preview formatting.
	 *
	 * @param   array   $groups     Normalized server condition groups.
	 * @param   string  $view       The single view name.
	 * @param   string  $component  The component name.
	 *
	 * @return  array{properties:string,methods:string,rule:string}
	 * @since   6.2.1
	 */
	public function fragments(array $groups, string $view, string $component): array
	{
		$state = [];
		foreach ([
			[Indent::class, 'indent', "\t"],
			[Indent::class, 'bucket', []],
			[Line::class, 'add', false],
		] as [$class, $name, $value])
		{
			$property = new ReflectionProperty($class, $name);
			$state[] = [$property, $property->isInitialized() ? $property->getValue() : $value];
			$property->setValue(null, $value);
		}

		try
		{
			$conditions = new Conditions();
			$conditions->setConditions($view, $groups);
			$rule = new ConditionalRule();
			$emitter = new ValidationFix($conditions, new Registry(), $rule);

			return [
				'properties' => $emitter->getProperties($view),
				'methods' => $this->resolvePowers($emitter->get($view, $component)),
				'rule' => $rule->get(),
			];
		}
		finally
		{
			foreach ($state as [$property, $value])
			{
				$property->setValue(null, $value);
			}
		}
	}

	/**
	 * Replace validation members while preserving the rest of a generated model.
	 *
	 * @param   string  $source     Existing model source.
	 * @param   string  $class      The model class name.
	 * @param   array   $fragments  Production property and method fragments.
	 *
	 * @return  string
	 * @throws  RuntimeException  When expected model declarations are absent or separated.
	 * @since   6.2.1
	 */
	public function model(string $source, string $class, array $fragments): string
	{
		$members = $this->members($source, $class);
		if (!isset($members['validate'], $members['$typeAlias']))
		{
			throw new RuntimeException('The preview model needs validate() and typeAlias declarations.');
		}
		[$start, $end] = $members['validate'];
		if (isset($members['resolveJcbConditionalData']))
		{
			[$resolverStart, $resolverEnd] = $members['resolveJcbConditionalData'];
			if ($resolverStart < $end || trim(substr($source, $end, $resolverStart - $end)) !== '')
			{
				throw new RuntimeException('Generated validation and resolver methods must remain adjacent.');
			}
			$end = $resolverEnd;
		}
		$source = substr($source, 0, $start) . ltrim($fragments['methods'], "\r\n") . substr($source, $end);
		$members = $this->members($source, $class);

		if (isset($members['$jcbConditionalRequirements']))
		{
			[$start, $end] = $members['$jcbConditionalRequirements'];
			return substr($source, 0, $start) . ltrim($fragments['properties'], "\r\n") . substr($source, $end);
		}

		$after = $members['$typeAlias'][1];
		return substr($source, 0, $after) . $fragments['properties'] . substr($source, $after);
	}

	/**
	 * Replace a generated rule body, retaining its header, namespace and imports.
	 *
	 * @param   string  $source  Existing rule source.
	 * @param   string  $class   The rule class name.
	 * @param   string  $body    The exact production rule fragment.
	 *
	 * @return  string
	 * @throws  RuntimeException  When the named class cannot be found.
	 * @since   6.2.1
	 */
	public function rule(string $source, string $class, string $body): string
	{
		$tokens = $this->tokens($source);
		[$open, $close] = $this->classRange($tokens, $class);

		return substr($source, 0, $tokens[$open][2] + 1) . PHP_EOL . $body . PHP_EOL
			. substr($source, $tokens[$close][2]);
	}

	/**
	 * Map the emitted Power references to the imports in the installed preview.
	 *
	 * @param   string  $source  Generated static model methods.
	 *
	 * @return  string
	 * @since   6.2.1
	 */
	private function resolvePowers(string $source): string
	{
		return strtr($source, [
			'Joomla___571422c4_0340_49f8_b846_5729c7af6ed7___Power' => 'FormHelper',
			'Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power' => 'Factory',
			'Joomla___a87c432d_b5b4_428e_b7ff_14b51664c624___Power' => 'Registry',
		]);
	}

	/**
	 * Index documented methods and properties directly inside the named class.
	 *
	 * @param   string  $source  Complete PHP source.
	 * @param   string  $class   The intended class, excluding nested declarations.
	 *
	 * @return  array<string, array{int,int}>
	 * @since   6.2.1
	 */
	private function members(string $source, string $class): array
	{
		$tokens = $this->tokens($source);
		[$open, $close] = $this->classRange($tokens, $class);
		$result = [];
		$depth = 1;
		for ($index = $open + 1; $index < $close; $index++)
		{
			[$type, $text] = $tokens[$index];
			if ($depth === 1 && $type === T_FUNCTION)
			{
				$name = $this->nextToken($tokens, $index + 1);
				$body = $name + 1;
				while ($tokens[$body][0] !== null || $tokens[$body][1] !== '{')
				{
					$body++;
				}
				$end = $this->closingToken($tokens, $body);
				$result[$tokens[$name][1]] = [$this->memberStart($source, $tokens, $index), $tokens[$end][2] + 1];
				$index = $end;
				continue;
			}
			if ($depth === 1 && $type === T_VARIABLE)
			{
				$end = $index;
				while ($tokens[$end][0] !== null || $tokens[$end][1] !== ';')
				{
					$end++;
				}
				$result[$text] = [$this->memberStart($source, $tokens, $index), $tokens[$end][2] + 1];
				$index = $end;
				continue;
			}
			if (($type === null && $text === '{') || in_array($type, [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))
			{
				$depth++;
			}
			elseif ($type === null && $text === '}')
			{
				$depth--;
			}
		}
		return $result;
	}

	/**
	 * Find the member's documentation and its leading indentation.
	 *
	 * @param   string  $source  Original PHP source.
	 * @param   array   $tokens  Positioned tokens.
	 * @param   int     $index   A method or property token.
	 *
	 * @return  int
	 * @since   6.2.1
	 */
	private function memberStart(string $source, array $tokens, int $index): int
	{
		while ($index > 0 && in_array($tokens[$index - 1][0], [
			T_WHITESPACE, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_FINAL, T_DOC_COMMENT, T_STRING, T_ARRAY,
		], true))
		{
			$index--;
			if ($tokens[$index][0] === T_DOC_COMMENT)
			{
				break;
			}
		}
		return strrpos(substr($source, 0, $tokens[$index][2]), "\n") + 1;
	}

	/**
	 * Find the opening and closing tokens of the named class.
	 *
	 * @param   array   $tokens  Positioned PHP tokens.
	 * @param   string  $class   The named class.
	 *
	 * @return  array{int,int}
	 * @throws  RuntimeException  When the class is absent.
	 * @since   6.2.1
	 */
	private function classRange(array $tokens, string $class): array
	{
		foreach ($tokens as $index => [$type])
		{
			if ($type !== T_CLASS || $tokens[$this->nextToken($tokens, $index + 1)][1] !== $class)
			{
				continue;
			}
			$open = $index + 1;
			while ($tokens[$open][0] !== null || $tokens[$open][1] !== '{')
			{
				$open++;
			}
			return [$open, $this->closingToken($tokens, $open)];
		}
		throw new RuntimeException('The preview class was not found: ' . $class);
	}

	/**
	 * Balance code braces without treating punctuation in literals as PHP syntax.
	 *
	 * @param   array  $tokens  Positioned PHP tokens.
	 * @param   int    $start   The opening brace token.
	 *
	 * @return  int
	 * @throws  RuntimeException  When the source braces are unbalanced.
	 * @since   6.2.1
	 */
	private function closingToken(array $tokens, int $start): int
	{
		$depth = 0;
		for ($index = $start; isset($tokens[$index]); $index++)
		{
			[$type, $text] = $tokens[$index];
			if (($type === null && $text === '{') || in_array($type, [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))
			{
				$depth++;
			}
			elseif ($type === null && $text === '}' && --$depth === 0)
			{
				return $index;
			}
		}
		throw new RuntimeException('The preview PHP contains unbalanced braces.');
	}

	/**
	 * Locate the next token that carries PHP syntax.
	 *
	 * @param   array  $tokens  Positioned PHP tokens.
	 * @param   int    $start   The first candidate index.
	 *
	 * @return  int
	 * @since   6.2.1
	 */
	private function nextToken(array $tokens, int $start): int
	{
		while (isset($tokens[$start]) && in_array($tokens[$start][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
		{
			$start++;
		}
		return $start;
	}

	/**
	 * Tokenize syntactically valid PHP and retain each original byte position.
	 *
	 * @param   string  $source  Complete source to inspect.
	 *
	 * @return  array<int, array{int|null,string,int}>
	 * @since   6.2.1
	 */
	private function tokens(string $source): array
	{
		$result = [];
		$offset = 0;
		foreach (token_get_all($source, TOKEN_PARSE) as $token)
		{
			$text = is_array($token) ? $token[1] : $token;
			$result[] = [is_array($token) ? $token[0] : null, $text, $offset];
			$offset += strlen($text);
		}
		return $result;
	}
}
