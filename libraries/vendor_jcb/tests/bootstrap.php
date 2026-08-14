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

$testsRoot = __DIR__;
$projectRoot = dirname(__DIR__, 3);
$runtimeRoot = $testsRoot . '/.runtime';
$configuredJoomlaRoot = getenv('JCB_JOOMLA_ROOT');
$joomlaRoot = is_string($configuredJoomlaRoot) && $configuredJoomlaRoot !== ''
	? $configuredJoomlaRoot
	: $runtimeRoot . '/joomla-cms';
$composerAutoloader = $testsRoot . '/vendor/autoload.php';

if (!is_file($composerAutoloader))
{
	throw new RuntimeException(
		'Test dependencies are not installed. Run composer install in ' . $testsRoot . '.'
	);
}

if (!is_dir($joomlaRoot))
{
	throw new RuntimeException(
		'Joomla CMS 6.1.2 source is not available at ' . $joomlaRoot . '.'
	);
}

if (!is_dir($runtimeRoot) && !mkdir($runtimeRoot, 0700, true) && !is_dir($runtimeRoot))
{
	throw new RuntimeException('Unable to create the test runtime directory: ' . $runtimeRoot);
}

$resolvedTestsRoot = realpath($testsRoot);
$resolvedRuntimeRoot = realpath($runtimeRoot);

if ($resolvedTestsRoot === false
	|| $resolvedRuntimeRoot === false
	|| $resolvedRuntimeRoot !== $resolvedTestsRoot . DIRECTORY_SEPARATOR . '.runtime')
{
	throw new RuntimeException(
		'The test runtime directory must resolve directly below the test project: ' . $runtimeRoot
	);
}

$runtimeRoot = $resolvedRuntimeRoot;
$cacheName = 'cache-' . getmypid() . '-' . bin2hex(random_bytes(8));
$cacheRoot = $runtimeRoot . DIRECTORY_SEPARATOR . $cacheName;
$cachePrefix = $runtimeRoot . DIRECTORY_SEPARATOR . 'cache-';

if (dirname($cacheRoot) !== $runtimeRoot
	|| !str_starts_with($cacheRoot, $cachePrefix)
	|| preg_match('/^cache-[0-9]+-[a-f0-9]{16}$/D', $cacheName) !== 1)
{
	throw new RuntimeException('Refusing to use an uncontained Joomla test cache: ' . $cacheRoot);
}

if (!mkdir($cacheRoot, 0700))
{
	throw new RuntimeException('Unable to create the isolated Joomla test cache: ' . $cacheRoot);
}

$removeCacheRoot = static function () use ($cacheRoot, $cachePrefix, $runtimeRoot): void
{
	$cacheName = basename($cacheRoot);

	if (dirname($cacheRoot) !== $runtimeRoot
		|| !str_starts_with($cacheRoot, $cachePrefix)
		|| preg_match('/^cache-[0-9]+-[a-f0-9]{16}$/D', $cacheName) !== 1)
	{
		throw new RuntimeException(
			'Refusing to remove an uncontained Joomla test cache: ' . $cacheRoot
		);
	}

	if (is_link($cacheRoot) || is_file($cacheRoot))
	{
		if (!unlink($cacheRoot))
		{
			throw new RuntimeException('Unable to remove the Joomla test cache path: ' . $cacheRoot);
		}

		return;
	}

	if (!file_exists($cacheRoot))
	{
		return;
	}

	if (!is_dir($cacheRoot))
	{
		throw new RuntimeException('The Joomla test cache is not a directory: ' . $cacheRoot);
	}

	$contents = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($cacheRoot, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	$ownedPrefix = $cacheRoot . DIRECTORY_SEPARATOR;

	foreach ($contents as $item)
	{
		$path = $item->getPathname();

		if (!str_starts_with($path, $ownedPrefix))
		{
			throw new RuntimeException('Refusing to remove an uncontained cache entry: ' . $path);
		}

		if ($item->isLink() || !$item->isDir())
		{
			if (!unlink($path))
			{
				throw new RuntimeException('Unable to remove the Joomla test cache file: ' . $path);
			}

			continue;
		}

		if (!rmdir($path))
		{
			throw new RuntimeException('Unable to remove the Joomla test cache directory: ' . $path);
		}
	}

	unset($contents);

	if (!rmdir($cacheRoot))
	{
		throw new RuntimeException('Unable to remove the Joomla test cache root: ' . $cacheRoot);
	}
};

// Append cleanup during shutdown so it runs after later Joomla cache writers.
register_shutdown_function(
	static function () use ($removeCacheRoot): void
	{
		register_shutdown_function($removeCacheRoot);
	}
);

$constants = [
	'_JEXEC' => 1,
	'JCB_TESTS_ROOT' => $testsRoot,
	'JCB_PROJECT_ROOT' => $projectRoot,
	'JCB_JOOMLA_ROOT' => $joomlaRoot,
	'JPATH_ROOT' => $joomlaRoot,
	'JPATH_SITE' => $joomlaRoot,
	'JPATH_CONFIGURATION' => $joomlaRoot,
	'JPATH_ADMINISTRATOR' => $joomlaRoot . '/administrator',
	'JPATH_BASE' => $joomlaRoot . '/administrator',
	'JPATH_LIBRARIES' => $projectRoot . '/libraries',
	'JPATH_PLATFORM' => $joomlaRoot . '/libraries',
	'JPATH_PLUGINS' => $joomlaRoot . '/plugins',
	'JPATH_INSTALLATION' => $joomlaRoot . '/installation',
	'JPATH_THEMES' => $joomlaRoot . '/templates',
	'JPATH_CACHE' => $cacheRoot,
	'JPATH_MANIFESTS' => $joomlaRoot . '/administrator/manifests',
	'JPATH_API' => $joomlaRoot . '/api',
	'JPATH_CLI' => $joomlaRoot . '/cli',
	'JPATH_COMPONENT' => $projectRoot . '/admin',
	'JPATH_COMPONENT_ADMINISTRATOR' => $projectRoot . '/admin',
	'JPATH_COMPONENT_SITE' => $joomlaRoot . '/components/com_componentbuilder'
];

foreach ($constants as $name => $value)
{
	if (!defined($name))
	{
		define($name, $value);
	}
}

date_default_timezone_set('UTC');

require_once $composerAutoloader;

$joomlaVersion = new Joomla\CMS\Version();

if ($joomlaVersion->getShortVersion() !== '6.1.2')
{
	throw new RuntimeException(
		'The test runtime requires Joomla CMS 6.1.2; found '
		. $joomlaVersion->getShortVersion() . ' at ' . $joomlaRoot . '.'
	);
}

$bundledAutoloaders = [
	$projectRoot . '/libraries/phpspreadsheet/vendor/autoload.php',
	$projectRoot . '/libraries/phpseclib3/vendor/autoload.php'
];

foreach ($bundledAutoloaders as $bundledAutoloader)
{
	if (!is_file($bundledAutoloader))
	{
		throw new RuntimeException('Required bundled autoloader not found: ' . $bundledAutoloader);
	}

	require_once $bundledAutoloader;
}
