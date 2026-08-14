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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;


/**
 * Discovers production declarations and validates their test ownership.
 *
 * @since  1.0.0
 */
final class SourceInventory
{
	/**
	 * Supported test ownership modes.
	 *
	 * @var    array<string>
	 * @since  1.0.0
	 */
	public const OWNERSHIP_MODES = [
		'unit',
		'contract',
		'provider',
		'characterization',
		'integration'
	];

	/**
	 * Production source roots, relative to libraries/vendor_jcb.
	 *
	 * @var    array<string>
	 * @since  1.0.0
	 */
	private const SOURCE_ROOTS = [
		'VDM.Joomla/src',
		'VDM.Joomla.Gitea/src',
		'VDM.Joomla.Openai/src',
		'VDM.Joomla.Github/src',
		'VDM.Minify/src',
		'VDM.Joomla.Git/src'
	];

	/**
	 * PHPUnit suite roots eligible to own production declarations.
	 *
	 * @var    array<string>
	 * @since  1.0.0
	 */
	private const TEST_ROOTS = [
		'VDM.Joomla/src',
		'VDM.Joomla.Gitea/src',
		'VDM.Joomla.Openai/src',
		'VDM.Joomla.Github/src',
		'VDM.Minify/src',
		'VDM.Joomla.Git/src'
	];

	/**
	 * Exact production files excluded from the test programme.
	 *
	 * @var    array<string>
	 * @since  1.0.0
	 */
	private const EXCLUDED_PATHS = [
		'VDM.Joomla/src/Componentbuilder/Compiler/Helper/Fields.php',
		'VDM.Joomla/src/Componentbuilder/Compiler/Helper/Infusion.php',
		'VDM.Joomla/src/Componentbuilder/Compiler/Helper/Interpretation.php'
	];

	/**
	 * Repository-relative prefix for the production source roots.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private const REPOSITORY_SOURCE_PREFIX = 'libraries/vendor_jcb/';

	/**
	 * Get the production source roots.
	 *
	 * @return  array<string>
	 * @since   1.0.0
	 */
	public static function sourceRoots(): array
	{
		return self::SOURCE_ROOTS;
	}

	/**
	 * Get the PHPUnit suite roots eligible to own production declarations.
	 *
	 * @return  array<string>
	 * @since   1.0.0
	 */
	public static function testRoots(): array
	{
		return self::TEST_ROOTS;
	}

	/**
	 * Get the exact excluded production paths.
	 *
	 * @return  array<string>
	 * @since   1.0.0
	 */
	public static function excludedPaths(): array
	{
		return self::EXCLUDED_PATHS;
	}

	/**
	 * Discover every in-scope PHP source file and its named declarations.
	 *
	 * @param   string|null  $vendorRoot  The libraries/vendor_jcb directory.
	 *
	 * @return  array<string, array{declarations: array<int, array{kind: string, name: string}>}>
	 * @since   1.0.0
	 */
	public static function discover(?string $vendorRoot = null): array
	{
		$vendorRoot = self::normalizeAbsolutePath($vendorRoot ?? dirname(__DIR__, 2));
		$inventory = [];

		foreach (self::SOURCE_ROOTS as $sourceRoot)
		{
			$absoluteRoot = $vendorRoot . '/' . $sourceRoot;

			if (!is_dir($absoluteRoot))
			{
				throw new RuntimeException('Production source root does not exist: ' . $absoluteRoot);
			}

			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($absoluteRoot, FilesystemIterator::SKIP_DOTS)
			);

			/** @var SplFileInfo $file */
			foreach ($iterator as $file)
			{
				if (!$file->isFile() || strtolower($file->getExtension()) !== 'php')
				{
					continue;
				}

				$absolutePath = self::normalizeAbsolutePath($file->getPathname());
				$relativePath = $sourceRoot . substr($absolutePath, strlen($absoluteRoot));

				if (in_array($relativePath, self::EXCLUDED_PATHS, true))
				{
					continue;
				}

				$inventory[$relativePath] = [
					'declarations' => self::discoverDeclarations($absolutePath)
				];
			}
		}

		ksort($inventory, SORT_STRING);

		return $inventory;
	}

	/**
	 * Validate the baseline and ownership ledgers against the current source.
	 *
	 * Baseline entries are explicitly untested debt. An ownership entry only
	 * becomes valid after the subject is removed from that baseline.
	 *
	 * @param   array<string, array{declarations: array<int, array{kind: string, name: string}>}>  $inventory  Source inventory.
	 * @param   array<mixed>                                                                  $baseline   Untested debt paths.
	 * @param   array<mixed>                                                                  $ownership  Tested subject ownership.
	 * @param   string                                                                        $testsRoot  Test-suite root.
	 *
	 * @return  array<string>
	 * @since   1.0.0
	 */
	public static function validate(
		array $inventory,
		array $baseline,
		array $ownership,
		string $testsRoot
	): array
	{
		$errors = [];
		$testsRoot = self::normalizeAbsolutePath($testsRoot);
		$sourcePaths = array_fill_keys(array_keys($inventory), true);
		$baselinePaths = self::validateBaseline($baseline, $errors);
		$ownershipPaths = self::validateOwnership($inventory, $ownership, $testsRoot, $errors);

		foreach ($inventory as $path => $metadata)
		{
			$declarations = $metadata['declarations'] ?? null;

			if (!is_array($declarations) || count($declarations) !== 1)
			{
				$count = is_array($declarations) ? count($declarations) : 0;
				$errors[] = sprintf(
					'Source file must declare exactly one named class, interface, trait, or enum: %s (%d found).',
					$path,
					$count
				);
			}

			$inBaseline = isset($baselinePaths[$path]);
			$hasOwner = isset($ownershipPaths[$path]);

			if (!$inBaseline && !$hasOwner)
			{
				$errors[] = 'Production source is missing from both ownership ledgers: ' . $path;
			}

			if ($inBaseline && $hasOwner)
			{
				$errors[] = 'Production source cannot be both untested and owned: ' . $path;
			}
		}

		foreach (array_keys($baselinePaths) as $path)
		{
			if (!isset($sourcePaths[$path]))
			{
				$errors[] = 'Stale untested baseline entry: ' . $path;
			}
		}

		foreach (array_keys($ownershipPaths) as $path)
		{
			if (!isset($sourcePaths[$path]))
			{
				$errors[] = 'Stale test ownership subject: ' . $path;
			}
		}

		sort($errors, SORT_STRING);

		return array_values(array_unique($errors));
	}

	/**
	 * Find production PHP paths added or renamed since a base commit.
	 *
	 * @param   string  $repositoryRoot  Repository root directory.
	 * @param   string  $baseSha         Base commit SHA.
	 *
	 * @return  array<string>
	 * @since   1.0.0
	 */
	public static function addedPathsSince(string $repositoryRoot, string $baseSha): array
	{
		$repositoryRoot = self::normalizeAbsolutePath($repositoryRoot);
		$baseSha = trim($baseSha);

		if (!preg_match('/^[a-f0-9]{7,64}$/i', $baseSha))
		{
			throw new RuntimeException('The ownership base must be a hexadecimal Git commit SHA.');
		}

		if (!is_dir($repositoryRoot . '/.git'))
		{
			throw new RuntimeException('Git repository not found at: ' . $repositoryRoot);
		}

		$command = [
			'git',
			'-C',
			$repositoryRoot,
			'diff',
			'--name-status',
			'--diff-filter=ACR',
			'--find-renames',
			'--find-copies',
			$baseSha . '...HEAD',
			'--'
		];

		foreach (self::SOURCE_ROOTS as $sourceRoot)
		{
			$command[] = self::REPOSITORY_SOURCE_PREFIX . $sourceRoot;
		}

		$descriptors = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w']
		];
		$process = proc_open($command, $descriptors, $pipes, $repositoryRoot);

		if (!is_resource($process))
		{
			throw new RuntimeException('Unable to start Git for the ownership comparison.');
		}

		$output = stream_get_contents($pipes[1]);
		$errorOutput = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);

		if ($exitCode !== 0)
		{
			throw new RuntimeException(
				'Unable to compare ownership with base ' . $baseSha . ': ' . trim((string) $errorOutput)
			);
		}

		$addedPaths = [];

		foreach (preg_split('/\R/', trim((string) $output)) ?: [] as $line)
		{
			if ($line === '')
			{
				continue;
			}

			$columns = explode("\t", $line);
			$repositoryPath = end($columns);

			if (!is_string($repositoryPath))
			{
				continue;
			}

			$sourcePath = self::repositoryPathToSourcePath($repositoryPath);

			if ($sourcePath !== null && self::isInScope($sourcePath))
			{
				$addedPaths[$sourcePath] = true;
			}
		}

		$paths = array_keys($addedPaths);
		sort($paths, SORT_STRING);

		return $paths;
	}

	/**
	 * Check whether a source-relative path belongs to the test programme.
	 *
	 * @param   string  $path  Source-relative path.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public static function isInScope(string $path): bool
	{
		if (self::sourcePathError($path) !== null)
		{
			return false;
		}

		return !in_array($path, self::EXCLUDED_PATHS, true);
	}

	/**
	 * Discover named declarations using PHP's tokenizer.
	 *
	 * @param   string  $absolutePath  Absolute PHP source path.
	 *
	 * @return  array<int, array{kind: string, name: string}>
	 * @since   1.0.0
	 */
	private static function discoverDeclarations(string $absolutePath): array
	{
		$source = file_get_contents($absolutePath);

		if ($source === false)
		{
			throw new RuntimeException('Unable to read production source: ' . $absolutePath);
		}

		$tokens = token_get_all($source, TOKEN_PARSE);
		$declarationTokens = [
			T_CLASS => 'class',
			T_INTERFACE => 'interface',
			T_TRAIT => 'trait'
		];

		if (defined('T_ENUM'))
		{
			$declarationTokens[constant('T_ENUM')] = 'enum';
		}

		$namespace = '';
		$previousSignificantToken = null;
		$declarations = [];

		foreach ($tokens as $index => $token)
		{
			if (!is_array($token))
			{
				if (trim($token) !== '')
				{
					$previousSignificantToken = $token;
				}

				continue;
			}

			[$tokenId] = $token;

			if ($tokenId === T_NAMESPACE)
			{
				$namespace = self::readNamespace($tokens, $index + 1);
			}

			if (isset($declarationTokens[$tokenId]))
			{
				if (
					$tokenId === T_CLASS
					&& in_array($previousSignificantToken, [T_NEW, T_DOUBLE_COLON], true)
				)
				{
					$previousSignificantToken = $tokenId;
					continue;
				}

				$name = self::readDeclarationName($tokens, $index + 1);

				if ($name !== null)
				{
					$declarations[] = [
						'kind' => $declarationTokens[$tokenId],
						'name' => ltrim($namespace . '\\' . $name, '\\')
					];
				}
			}

			if (!in_array($tokenId, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
			{
				$previousSignificantToken = $tokenId;
			}
		}

		return $declarations;
	}

	/**
	 * Read a namespace from a token stream.
	 *
	 * @param   array<int, array<int, mixed>|string>  $tokens  PHP tokens.
	 * @param   int                                    $start   First token after T_NAMESPACE.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private static function readNamespace(array $tokens, int $start): string
	{
		$nameTokenIds = [T_STRING, T_NS_SEPARATOR];

		foreach (['T_NAME_QUALIFIED', 'T_NAME_FULLY_QUALIFIED', 'T_NAME_RELATIVE'] as $constantName)
		{
			if (defined($constantName))
			{
				$nameTokenIds[] = constant($constantName);
			}
		}

		$namespace = '';

		for ($index = $start, $count = count($tokens); $index < $count; $index++)
		{
			$token = $tokens[$index];

			if (is_string($token) && in_array($token, [';', '{'], true))
			{
				break;
			}

			if (is_array($token) && in_array($token[0], $nameTokenIds, true))
			{
				$namespace .= $token[1];
			}
		}

		return trim($namespace, '\\');
	}

	/**
	 * Read the declaration name following a declaration token.
	 *
	 * @param   array<int, array<int, mixed>|string>  $tokens  PHP tokens.
	 * @param   int                                    $start   First token after the declaration token.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private static function readDeclarationName(array $tokens, int $start): ?string
	{
		for ($index = $start, $count = count($tokens); $index < $count; $index++)
		{
			$token = $tokens[$index];

			if (is_array($token) && $token[0] === T_STRING)
			{
				return $token[1];
			}

			if (is_string($token) && in_array($token, ['{', ';', '('], true))
			{
				return null;
			}
		}

		return null;
	}

	/**
	 * Validate and index the untested debt baseline.
	 *
	 * @param   array<mixed>   $baseline  Untested debt paths.
	 * @param   array<string>  $errors    Validation errors.
	 *
	 * @return  array<string, true>
	 * @since   1.0.0
	 */
	private static function validateBaseline(array $baseline, array &$errors): array
	{
		$paths = [];

		if (!array_is_list($baseline))
		{
			$errors[] = 'The untested coverage baseline must be a list of source-relative paths.';
		}

		foreach ($baseline as $index => $path)
		{
			if (!is_string($path))
			{
				$errors[] = sprintf('Untested baseline entry %s must be a string.', (string) $index);
				continue;
			}

			if (($pathError = self::sourcePathError($path)) !== null)
			{
				$errors[] = 'Invalid untested baseline path ' . $path . ': ' . $pathError;
				continue;
			}

			if (isset($paths[$path]))
			{
				$errors[] = 'Duplicate untested baseline entry: ' . $path;
			}

			$paths[$path] = true;
		}

		return $paths;
	}

	/**
	 * Validate and index explicit test ownership.
	 *
	 * @param   array<string, array{declarations: array<int, array{kind: string, name: string}>}>  $inventory  Source inventory.
	 * @param   array<mixed>                                                                    $ownership  Tested subject ownership.
	 * @param   string                                                                          $testsRoot  Test-suite root.
	 * @param   array<string>                                                                   $errors     Validation errors.
	 *
	 * @return  array<string, true>
	 * @since   1.0.0
	 */
	private static function validateOwnership(
		array $inventory,
		array $ownership,
		string $testsRoot,
		array &$errors
	): array
	{
		$paths = [];
		$ownerMetadata = [];
		$autoloadMappings = self::controlledOwnerAutoloadMappings($testsRoot);

		foreach ($ownership as $subject => $record)
		{
			if (!is_string($subject))
			{
				$errors[] = 'Every test ownership subject must be a source-relative string path.';
				continue;
			}

			if (($pathError = self::sourcePathError($subject)) !== null)
			{
				$errors[] = 'Invalid test ownership subject ' . $subject . ': ' . $pathError;
				continue;
			}

			$paths[$subject] = true;

			if (!is_array($record))
			{
				$errors[] = 'Test ownership record must contain mode and owner: ' . $subject;
				continue;
			}

			$recordKeys = array_keys($record);
			sort($recordKeys, SORT_STRING);

			if ($recordKeys !== ['mode', 'owner'])
			{
				$errors[] = 'Test ownership record must contain exactly mode and owner: ' . $subject;
			}

			$mode = $record['mode'] ?? null;

			if (!is_string($mode) || !in_array($mode, self::OWNERSHIP_MODES, true))
			{
				$errors[] = sprintf(
					'Invalid test ownership mode for %s. Allowed modes: %s.',
					$subject,
					implode(', ', self::OWNERSHIP_MODES)
				);
			}

			$owner = $record['owner'] ?? null;

			if (!is_string($owner))
			{
				$errors[] = 'Invalid test owner for ' . $subject . ': owner must be a string';
				continue;
			}

			if (($ownerError = self::ownerPathError($owner)) !== null)
			{
				$errors[] = 'Invalid test owner for ' . $subject . ': ' . $ownerError;
				continue;
			}

			$ownerPath = $testsRoot . '/' . $owner;

			if (!is_file($ownerPath))
			{
				$errors[] = sprintf('Test owner does not exist for %s: %s', $subject, $owner);
				continue;
			}

			if (!isset($ownerMetadata[$owner]))
			{
				$ownerMetadata[$owner] = self::inspectOwner(
					$ownerPath,
					$testsRoot,
					$autoloadMappings
				);
			}

			$metadata = $ownerMetadata[$owner];

			if (!$metadata['has_tests'])
			{
				$errors[] = 'Test owner has no public test method: ' . $owner;
			}

			if (!$metadata['is_phpunit_test_case'])
			{
				$errors[] = 'Test owner is not a PHPUnit TestCase: ' . $owner;
			}

			if (!$metadata['has_blocking_test'])
			{
				$errors[] = 'Test owner has no blocking test outside the known-defect group: ' . $owner;
			}

			$declarations = $inventory[$subject]['declarations'] ?? [];

			if (count($declarations) === 1)
			{
				$declaration = $declarations[0]['name'] ?? null;
				$kind = $declarations[0]['kind'] ?? null;

				if (is_string($declaration) && is_string($kind)
					&& !self::ownerCoversDeclaration($metadata, $declaration, $kind))
				{
					$errors[] = sprintf(
						'Test owner coverage metadata does not include %s: %s',
						$declaration,
						$owner
					);
				}
			}
		}

		return $paths;
	}

	/**
	 * Inspect one PHPUnit owner without loading Composer or the test class.
	 *
	 * @param   string                 $path              Absolute test-owner path.
	 * @param   string                 $testsRoot         Test-suite root.
	 * @param   array<string, string>  $autoloadMappings  Controlled PSR-4 mappings.
	 *
	 * @return  array{
	 *     classes: array<string, true>,
	 *     traits: array<string, true>,
	 *     namespaces: array<string, true>,
	 *     is_phpunit_test_case: bool,
	 *     has_tests: bool,
	 *     has_blocking_test: bool
	 * }
	 * @since   1.0.0
	 */
	private static function inspectOwner(
		string $path,
		string $testsRoot,
		array $autoloadMappings
	): array
	{
		$source = file_get_contents($path);

		if ($source === false)
		{
			return [
				'classes' => [],
				'traits' => [],
				'namespaces' => [],
				'is_phpunit_test_case' => false,
				'has_tests' => false,
				'has_blocking_test' => false
			];
		}

		$namespace = '';

		if (preg_match('/\bnamespace\s+([^;{]+)\s*[;{]/', $source, $match) === 1)
		{
			$namespace = trim($match[1], " \t\n\r\0\x0B\\");
		}

		$imports = self::ownerImports($source);
		$classes = [];
		$traits = [];

		if (preg_match_all(
			'/#\[[^\]]*\bCovers(Class|Trait)\s*\(\s*(\\\\?[A-Za-z_][A-Za-z0-9_\\\\]*)::class\s*\)/',
			$source,
			$coverage,
			PREG_SET_ORDER
		) !== false)
		{
			foreach ($coverage as $attribute)
			{
				$name = self::resolveOwnerName($attribute[2], $namespace, $imports);

				if ($attribute[1] === 'Trait')
				{
					$traits[$name] = true;
				}
				else
				{
					$classes[$name] = true;
				}
			}
		}

		$namespaces = [];

		if (preg_match_all(
			'/#\[[^\]]*\bCoversNamespace\s*\(\s*([\'\"])(.*?)\1\s*\)/',
			$source,
			$namespaceCoverage,
			PREG_SET_ORDER
		) !== false)
		{
			foreach ($namespaceCoverage as $attribute)
			{
				$namespaces[trim(str_replace('\\\\', '\\', $attribute[2]), '\\')] = true;
			}
		}

		$classPosition = self::ownerClassPosition($source);
		$classAttributes = $classPosition === null ? '' : substr($source, 0, $classPosition);
		$classKnownDefect = self::hasKnownDefectGroup($classAttributes);
		$hasTests = false;
		$hasBlockingTest = false;

		if (preg_match_all(
			'/((?:\s*#\[[^\]]+\])*)\s*public\s+function\s+(test[A-Za-z0-9_]*)\s*\(/',
			$source,
			$methods,
			PREG_SET_ORDER
		) !== false)
		{
			foreach ($methods as $method)
			{
				$hasTests = true;

				if (!$classKnownDefect && !self::hasKnownDefectGroup($method[1]))
				{
					$hasBlockingTest = true;
				}
			}
		}

		return [
			'classes' => $classes,
			'traits' => $traits,
			'namespaces' => $namespaces,
			'is_phpunit_test_case' => self::ownerIsPhpUnitTestCase(
				$path,
				$testsRoot,
				$autoloadMappings
			),
			'has_tests' => $hasTests,
			'has_blocking_test' => $hasBlockingTest
		];
	}

	/**
	 * Read controlled project test PSR-4 mappings without loading Composer.
	 *
	 * Only the shared Support directory and configured package suite roots may
	 * participate in an owner's inheritance chain.
	 *
	 * @param   string  $testsRoot  Test-suite root.
	 *
	 * @return  array<string, string>  Namespace prefixes to relative directories.
	 * @since   1.0.0
	 */
	private static function controlledOwnerAutoloadMappings(string $testsRoot): array
	{
		$composerPath = $testsRoot . '/composer.json';

		if (!is_file($composerPath))
		{
			return [];
		}

		$source = file_get_contents($composerPath);

		if ($source === false)
		{
			return [];
		}

		$composer = json_decode($source, true);
		$mappings = $composer['autoload-dev']['psr-4'] ?? null;

		if (!is_array($mappings))
		{
			return [];
		}

		$allowedRoots = array_fill_keys(array_merge(['Support'], self::TEST_ROOTS), true);
		$controlled = [];

		foreach ($mappings as $prefix => $directory)
		{
			if (!is_string($prefix) || !is_string($directory))
			{
				continue;
			}

			$directory = trim(str_replace('\\', '/', $directory), '/');

			if (isset($allowedRoots[$directory]))
			{
				$controlled[$prefix] = $directory;
			}
		}

		uksort(
			$controlled,
			static fn(string $left, string $right): int => strlen($right) <=> strlen($left)
		);

		return $controlled;
	}

	/**
	 * Prove that an owner has a static inheritance chain to PHPUnit TestCase.
	 *
	 * @param   string                 $path              Class source path.
	 * @param   string                 $testsRoot         Test-suite root.
	 * @param   array<string, string>  $autoloadMappings  Controlled PSR-4 mappings.
	 * @param   array<string, true>    $visited           Paths already inspected.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	private static function ownerIsPhpUnitTestCase(
		string $path,
		string $testsRoot,
		array $autoloadMappings,
		array $visited = []
	): bool
	{
		$path = self::normalizeAbsolutePath($path);

		if (isset($visited[$path]))
		{
			return false;
		}

		$visited[$path] = true;
		$inheritance = self::ownerClassInheritance($path);

		if ($inheritance === null || $inheritance['parent'] === null)
		{
			return false;
		}

		if ($inheritance['parent'] === 'PHPUnit\\Framework\\TestCase')
		{
			return true;
		}

		$parentPath = self::controlledOwnerClassPath(
			$inheritance['parent'],
			$testsRoot,
			$autoloadMappings
		);

		if ($parentPath === null)
		{
			return false;
		}

		$parentInheritance = self::ownerClassInheritance($parentPath);

		if ($parentInheritance === null || $parentInheritance['class'] !== $inheritance['parent'])
		{
			return false;
		}

		return self::ownerIsPhpUnitTestCase(
			$parentPath,
			$testsRoot,
			$autoloadMappings,
			$visited
		);
	}

	/**
	 * Read the first named class and its resolved parent from one PHP file.
	 *
	 * @param   string  $path  Class source path.
	 *
	 * @return  array{class: string, parent: string|null}|null
	 * @since   1.0.0
	 */
	private static function ownerClassInheritance(string $path): ?array
	{
		$source = file_get_contents($path);

		if ($source === false)
		{
			return null;
		}

		$namespace = '';

		if (preg_match('/\bnamespace\s+([^;{]+)\s*[;{]/', $source, $namespaceMatch) === 1)
		{
			$namespace = trim($namespaceMatch[1], " \t\n\r\0\x0B\\");
		}

		$classPosition = self::ownerClassPosition($source);

		if ($classPosition === null || preg_match(
			'/\Aclass\s+([A-Za-z_][A-Za-z0-9_]*)(?:\s+extends\s+(\\\\?[A-Za-z_][A-Za-z0-9_\\\\]*))?/',
			substr($source, $classPosition),
			$classMatch
		) !== 1)
		{
			return null;
		}

		$parent = $classMatch[2] ?? null;

		return [
			'class' => ltrim($namespace . '\\' . $classMatch[1], '\\'),
			'parent' => is_string($parent)
				? self::resolveOwnerName($parent, $namespace, self::ownerImports($source))
				: null
		];
	}

	/**
	 * Resolve a controlled project test class to its source path.
	 *
	 * @param   string                 $class             Fully qualified class name.
	 * @param   string                 $testsRoot         Test-suite root.
	 * @param   array<string, string>  $autoloadMappings  Controlled PSR-4 mappings.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private static function controlledOwnerClassPath(
		string $class,
		string $testsRoot,
		array $autoloadMappings
	): ?string
	{
		foreach ($autoloadMappings as $prefix => $directory)
		{
			if (!str_starts_with($class, $prefix))
			{
				continue;
			}

			$relativeClass = substr($class, strlen($prefix));
			$path = $testsRoot . '/' . $directory . '/'
				. str_replace('\\', '/', $relativeClass) . '.php';

			return is_file($path) ? self::normalizeAbsolutePath($path) : null;
		}

		return null;
	}

	/**
	 * Read ordinary class imports from a PHPUnit owner.
	 *
	 * @param   string  $source  Owner source.
	 *
	 * @return  array<string, string>  Import aliases to fully qualified names.
	 * @since   1.0.0
	 */
	private static function ownerImports(string $source): array
	{
		$imports = [];

		if (preg_match_all('/^use\s+([^;]+);/m', $source, $matches) === false)
		{
			return $imports;
		}

		foreach ($matches[1] as $statement)
		{
			$statement = trim($statement);

			if (preg_match('/^(?:function|const)\s+/i', $statement) === 1
				|| str_contains($statement, '{'))
			{
				continue;
			}

			if (preg_match('/^(.+?)\s+as\s+([A-Za-z_][A-Za-z0-9_]*)$/i', $statement, $alias) === 1)
			{
				$name = trim($alias[1], '\\');
				$shortName = $alias[2];
			}
			else
			{
				$name = trim($statement, '\\');
				$position = strrpos($name, '\\');
				$shortName = $position === false ? $name : substr($name, $position + 1);
			}

			$imports[$shortName] = $name;
		}

		return $imports;
	}

	/**
	 * Find the first named class declaration in owner source.
	 *
	 * @param   string  $source  Owner source.
	 *
	 * @return  int|null  Byte offset of the class token.
	 * @since   1.0.0
	 */
	private static function ownerClassPosition(string $source): ?int
	{
		$offset = 0;
		$previous = null;

		foreach (token_get_all($source, TOKEN_PARSE) as $token)
		{
			if (is_array($token))
			{
				[$tokenId, $text] = $token;

				if ($tokenId === T_CLASS
					&& !in_array($previous, [T_NEW, T_DOUBLE_COLON], true))
				{
					return $offset;
				}

				if (!in_array($tokenId, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
				{
					$previous = $tokenId;
				}

				$offset += strlen($text);
				continue;
			}

			if (trim($token) !== '')
			{
				$previous = $token;
			}

			$offset += strlen($token);
		}

		return null;
	}

	/**
	 * Resolve a class constant expression from an owner namespace/import table.
	 *
	 * @param   string                 $name       Class expression.
	 * @param   string                 $namespace  Owner namespace.
	 * @param   array<string, string>  $imports    Owner imports.
	 *
	 * @return  string  Fully qualified declaration name.
	 * @since   1.0.0
	 */
	private static function resolveOwnerName(string $name, string $namespace, array $imports): string
	{
		if (str_starts_with($name, '\\'))
		{
			return ltrim($name, '\\');
		}

		$segments = explode('\\', $name);
		$first = array_shift($segments);

		if (is_string($first) && isset($imports[$first]))
		{
			return $imports[$first] . ($segments === [] ? '' : '\\' . implode('\\', $segments));
		}

		return ltrim($namespace . '\\' . $name, '\\');
	}

	/**
	 * Determine whether owner metadata explicitly covers a declaration.
	 *
	 * @param   array{
	 *     classes: array<string, true>,
	 *     traits: array<string, true>,
	 *     namespaces: array<string, true>,
	 *     is_phpunit_test_case: bool,
	 *     has_tests: bool,
	 *     has_blocking_test: bool
	 * }               $metadata     Owner metadata.
	 * @param   string  $declaration  Production declaration name.
	 * @param   string  $kind         Production declaration kind.
	 *
	 * @return  bool  True when exact or containing-namespace coverage is declared.
	 * @since   1.0.0
	 */
	private static function ownerCoversDeclaration(
		array $metadata,
		string $declaration,
		string $kind
	): bool
	{
		if ($kind === 'trait' && isset($metadata['traits'][$declaration]))
		{
			return true;
		}

		if ($kind !== 'trait' && isset($metadata['classes'][$declaration]))
		{
			return true;
		}

		foreach (array_keys($metadata['namespaces']) as $namespace)
		{
			if ($declaration === $namespace || str_starts_with($declaration, $namespace . '\\'))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether an attribute block selects the known-defect group.
	 *
	 * @param   string  $source  Attribute source.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	private static function hasKnownDefectGroup(string $source): bool
	{
		return preg_match(
			'/\bGroup\s*\(\s*([\'\"])known-defect\1\s*\)/',
			$source
		) === 1;
	}

	/**
	 * Validate a production source-relative path.
	 *
	 * @param   string  $path  Source-relative path.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private static function sourcePathError(string $path): ?string
	{
		if (($relativeError = self::relativePhpPathError($path)) !== null)
		{
			return $relativeError;
		}

		foreach (self::SOURCE_ROOTS as $sourceRoot)
		{
			if (str_starts_with($path, $sourceRoot . '/'))
			{
				return null;
			}
		}

		return 'path is outside the six production source roots';
	}

	/**
	 * Validate a test owner path.
	 *
	 * @param   string  $path  Test-root-relative owner path.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private static function ownerPathError(string $path): ?string
	{
		if (($relativeError = self::relativePhpPathError($path)) !== null)
		{
			return $relativeError;
		}

		if (!str_ends_with($path, 'Test.php'))
		{
			return 'owner must identify a PHPUnit *Test.php file';
		}

		foreach (self::TEST_ROOTS as $testRoot)
		{
			if (str_starts_with($path, $testRoot . '/'))
			{
				return null;
			}
		}

		return 'owner must be inside one of the six configured package test suites';
	}

	/**
	 * Validate a canonical relative PHP path.
	 *
	 * @param   string  $path  Relative PHP path.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private static function relativePhpPathError(string $path): ?string
	{
		if ($path === '' || trim($path) !== $path)
		{
			return 'path must be non-empty and cannot contain surrounding whitespace';
		}

		if (str_contains($path, '\\') || str_starts_with($path, '/') || preg_match('/^[a-z]:/i', $path))
		{
			return 'path must be relative and use forward slashes';
		}

		if (str_contains($path, "\0"))
		{
			return 'path cannot contain a null byte';
		}

		$segments = explode('/', $path);

		if (in_array('', $segments, true) || in_array('.', $segments, true) || in_array('..', $segments, true))
		{
			return 'path cannot contain empty, current-directory, or parent-directory segments';
		}

		if (!str_ends_with(strtolower($path), '.php'))
		{
			return 'path must identify a PHP file';
		}

		return null;
	}

	/**
	 * Convert a repository-relative source path to a vendor-JCB-relative path.
	 *
	 * @param   string  $path  Repository-relative path.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private static function repositoryPathToSourcePath(string $path): ?string
	{
		$path = str_replace('\\', '/', trim($path));

		if (!str_starts_with($path, self::REPOSITORY_SOURCE_PREFIX))
		{
			return null;
		}

		return substr($path, strlen(self::REPOSITORY_SOURCE_PREFIX));
	}

	/**
	 * Normalize an absolute path for internal comparisons.
	 *
	 * @param   string  $path  Absolute path.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private static function normalizeAbsolutePath(string $path): string
	{
		return rtrim(str_replace('\\', '/', $path), '/');
	}
}
