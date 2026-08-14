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


use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitor\NameResolver;
use RuntimeException;


/**
 * Source-level inventory of interface inheritance and implementation contracts.
 *
 * The source graph avoids loading every production class merely to inspect its
 * declaration. That keeps architectural tests free of constructor, Joomla
 * application, network, and legacy class-loading side effects.
 *
 * @since  1.0.0
 */
final class InterfaceContractInventory
{
	/**
	 * Cached contract inventory by normalized vendor root.
	 *
	 * @var    array<string, array<string, array{implementations: array<int, string>}>>
	 * @since  1.0.0
	 */
	private static array $cache = [];

	/**
	 * Discover every production interface and its transitive class conformers.
	 *
	 * Entries are prefixed with `concrete|` or `abstract|` so a change in the
	 * executable implementation set cannot hide behind an unchanged class name.
	 *
	 * @param   string|null  $vendorRoot  The libraries/vendor_jcb directory.
	 *
	 * @return  array<string, array{implementations: array<int, string>}>
	 * @since   1.0.0
	 */
	public static function discover(?string $vendorRoot = null): array
	{
		$vendorRoot = self::normalizePath($vendorRoot ?? dirname(__DIR__, 2));

		if (isset(self::$cache[$vendorRoot]))
		{
			return self::$cache[$vendorRoot];
		}

		$parser = (new ParserFactory())->createForNewestSupportedVersion();
		$finder = new NodeFinder();
		$classes = [];
		$interfaces = [];

		foreach (array_keys(SourceInventory::discover($vendorRoot)) as $relativePath)
		{
			$source = file_get_contents($vendorRoot . '/' . $relativePath);

			if ($source === false)
			{
				throw new RuntimeException('Unable to read production declaration: ' . $relativePath);
			}

			$ast = $parser->parse($source);

			if ($ast === null)
			{
				throw new RuntimeException('Unable to parse production declaration: ' . $relativePath);
			}

			$traverser = new NodeTraverser();
			$traverser->addVisitor(new NameResolver());
			$ast = $traverser->traverse($ast);

			foreach ($finder->findInstanceOf($ast, Class_::class) as $class)
			{
				if ($class->isAnonymous())
				{
					continue;
				}

				$name = self::declarationName($class);
				$classes[$name] = [
					'abstract' => $class->isAbstract(),
					'parent' => $class->extends === null ? null : self::name($class->extends),
					'interfaces' => array_map(self::name(...), $class->implements)
				];
			}

			foreach ($finder->findInstanceOf($ast, Interface_::class) as $interface)
			{
				$name = self::declarationName($interface);
				$interfaces[$name] = [
					'parents' => array_map(self::name(...), $interface->extends)
				];
			}
		}

		ksort($classes, SORT_STRING);
		ksort($interfaces, SORT_STRING);
		$contracts = [];

		foreach (array_keys($interfaces) as $interface)
		{
			$implementations = [];

			foreach ($classes as $class => $metadata)
			{
				if (self::classConforms($class, $interface, $classes, $interfaces))
				{
					$implementations[] = ($metadata['abstract'] ? 'abstract|' : 'concrete|') . $class;
				}
			}

			sort($implementations, SORT_STRING);
			$contracts[$interface] = ['implementations' => $implementations];
		}

		return self::$cache[$vendorRoot] = $contracts;
	}

	/**
	 * Determine whether a class transitively conforms to an interface.
	 *
	 * @param   string                                                                                      $class       Candidate class.
	 * @param   string                                                                                      $interface   Required interface.
	 * @param   array<string, array{abstract: bool, parent: string|null, interfaces: array<int, string>}>    $classes     Class graph.
	 * @param   array<string, array{parents: array<int, string>}>                                            $interfaces  Interface graph.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	private static function classConforms(
		string $class,
		string $interface,
		array $classes,
		array $interfaces
	): bool
	{
		$visited = [];

		while (isset($classes[$class]) && !isset($visited[$class]))
		{
			$visited[$class] = true;

			foreach ($classes[$class]['interfaces'] as $candidate)
			{
				if (self::interfaceExtends($candidate, $interface, $interfaces))
				{
					return true;
				}
			}

			$class = $classes[$class]['parent'] ?? '';
		}

		return false;
	}

	/**
	 * Determine whether an interface is or extends the required interface.
	 *
	 * @param   string                                             $candidate   Candidate interface.
	 * @param   string                                             $required    Required interface.
	 * @param   array<string, array{parents: array<int, string>}>   $interfaces  Interface graph.
	 * @param   array<string, true>                                $visited     Recursion guard.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	private static function interfaceExtends(
		string $candidate,
		string $required,
		array $interfaces,
		array &$visited = []
	): bool
	{
		if ($candidate === $required)
		{
			return true;
		}

		if (isset($visited[$candidate]))
		{
			return false;
		}

		$visited[$candidate] = true;

		foreach ($interfaces[$candidate]['parents'] ?? [] as $parent)
		{
			if (self::interfaceExtends($parent, $required, $interfaces, $visited))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Read a fully resolved declaration name.
	 *
	 * @param   Class_|Interface_  $declaration  Named declaration node.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private static function declarationName(Class_|Interface_ $declaration): string
	{
		$name = $declaration->namespacedName ?? null;

		if (!$name instanceof Node\Name)
		{
			throw new RuntimeException('Name resolver did not annotate a production declaration.');
		}

		return self::name($name);
	}

	/**
	 * Normalize one parser name to its canonical class-string form.
	 *
	 * @param   Node\Name  $name  Resolved parser name.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private static function name(Node\Name $name): string
	{
		$resolved = $name->getAttribute('resolvedName');

		return ltrim(($resolved instanceof Node\Name ? $resolved : $name)->toString(), '\\');
	}

	/**
	 * Normalize an absolute path without resolving symlinks.
	 *
	 * @param   string  $path  Filesystem path.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private static function normalizePath(string $path): string
	{
		return rtrim(str_replace('\\', '/', $path), '/');
	}
}
