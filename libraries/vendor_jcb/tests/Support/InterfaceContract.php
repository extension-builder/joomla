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


use ReflectionClass;
use ReflectionClassConstant;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;


/**
 * Canonical public declaration fingerprint for one interface.
 *
 * @since  1.0.0
 */
final class InterfaceContract
{
	/**
	 * Capture inherited interfaces, constants, and public method signatures.
	 *
	 * @param   class-string  $interface  Interface under test.
	 *
	 * @return  array{snapshot: string, hash: string}
	 * @since   1.0.0
	 */
	public static function capture(string $interface): array
	{
		$reflection = new ReflectionClass($interface);

		if (!$reflection->isInterface())
		{
			throw new RuntimeException('Declaration is not an interface: ' . $interface);
		}

		$lines = [];
		$parents = $reflection->getInterfaceNames();
		sort($parents, SORT_STRING);

		foreach ($parents as $parent)
		{
			$lines[] = 'extends|' . $parent;
		}

		$constants = $reflection->getReflectionConstants();
		usort(
			$constants,
			static fn(ReflectionClassConstant $left, ReflectionClassConstant $right): int =>
				$left->getName() <=> $right->getName()
		);

		foreach ($constants as $constant)
		{
			$lines[] = implode('|', [
				'constant',
				$constant->getName(),
				method_exists($constant, 'getType') ? self::type($constant->getType()) : 'none',
				$constant->isFinal() ? 'final' : 'extensible',
				self::value($constant->getValue())
			]);
		}

		$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
		usort(
			$methods,
			static fn(ReflectionMethod $left, ReflectionMethod $right): int =>
				$left->getName() <=> $right->getName()
		);

		foreach ($methods as $method)
		{
			$parameters = array_map(self::parameter(...), $method->getParameters());
			$lines[] = implode('|', [
				'method',
				$method->getName(),
				$method->isStatic() ? 'static' : 'instance',
				$method->returnsReference() ? 'returns-reference' : 'returns-value',
				'(' . implode(',', $parameters) . ')',
				self::type($method->getReturnType())
			]);
		}

		$snapshot = implode("\n", $lines);

		return [
			'snapshot' => $snapshot,
			'hash' => hash('sha256', $snapshot)
		];
	}

	/**
	 * Describe one parameter without losing optionality or reference semantics.
	 *
	 * @param   ReflectionParameter  $parameter  Reflected parameter.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private static function parameter(ReflectionParameter $parameter): string
	{
		$parts = [
			self::type($parameter->getType()),
			$parameter->isPassedByReference() ? '&' : '-',
			$parameter->isVariadic() ? '...' : '-',
			'$' . $parameter->getName()
		];

		if ($parameter->isDefaultValueAvailable())
		{
			$parts[] = $parameter->isDefaultValueConstant()
				? 'constant:' . $parameter->getDefaultValueConstantName()
				: 'default:' . self::value($parameter->getDefaultValue());
		}
		elseif ($parameter->isOptional())
		{
			$parts[] = 'optional';
		}
		else
		{
			$parts[] = 'required';
		}

		return implode(':', $parts);
	}

	/**
	 * Describe a reflection type deterministically.
	 *
	 * @param   ReflectionType|null  $type  Reflected type.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private static function type(?ReflectionType $type): string
	{
		if ($type === null)
		{
			return 'none';
		}

		if ($type instanceof ReflectionNamedType)
		{
			$name = $type->getName();

			return $type->allowsNull() && !in_array($name, ['mixed', 'null'], true)
				? '?' . $name
				: $name;
		}

		if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType)
		{
			$types = array_map(self::type(...), $type->getTypes());
			$separator = $type instanceof ReflectionUnionType ? '|' : '&';

			return implode($separator, $types);
		}

		return (string) $type;
	}

	/**
	 * Serialize a declaration default or constant value deterministically.
	 *
	 * @param   mixed  $value  Declaration value.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private static function value(mixed $value): string
	{
		return base64_encode(serialize($value));
	}
}
