<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    18th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Every key a service provider asks the container for must be a key some
 * service provider puts in it.
 *
 * A class that is built by hand in a unit test never touches the container, so
 * a provider that reaches for a service nobody registers passes every test and
 * then throws the moment the compiler runs. That is not a hypothetical: the
 * four view providers that build the edit body and the filter field helper
 * asked for 'Application', which has never been registered anywhere, and the
 * whole compile died on it.
 *
 * This reads every provider in the library, collects what they register and
 * what they ask for, and reports anything asked for and never registered.
 */

$librarySource = dirname(__DIR__, 2) . '/VDM.Joomla/src';

if (!is_dir($librarySource))
{
	fwrite(STDERR, "The library source was not found at {$librarySource}.\n");
	exit(2);
}

$options = getopt('', ['help']);

if (isset($options['help']))
{
	fwrite(
		STDOUT,
		"Usage: php bin/check-container-keys.php\n\n"
		. "Reports any container key a service provider asks for that no service\n"
		. "provider registers. Takes no options and reads no git history: the\n"
		. "question it answers is about the tree as it stands.\n"
	);

	exit(0);
}

/**
 * Every PHP file under a directory.
 *
 * @param   string  $directory  The directory to walk.
 *
 * @return  array<int, string>
 */
function phpFilesIn(string $directory): array
{
	$files = [];

	$walker = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($walker as $file)
	{
		if ($file->isFile() && strtolower($file->getExtension()) === 'php')
		{
			$files[] = $file->getPathname();
		}
	}

	sort($files);

	return $files;
}

/**
 * The next meaningful token after a position, skipping whitespace and comments.
 *
 * @param   array  $tokens  The token stream.
 * @param   int    $index   Where to start looking, exclusive.
 *
 * @return  array{0: int, 1: mixed}|null  The token and its index, or null at the end.
 */
function nextMeaningful(array $tokens, int $index): ?array
{
	$count = count($tokens);

	for ($i = $index + 1; $i < $count; $i++)
	{
		if (is_array($tokens[$i])
			&& in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
		{
			continue;
		}

		return [$i, $tokens[$i]];
	}

	return null;
}

/**
 * The value of a single-quoted or double-quoted string token, or null.
 *
 * @param   mixed  $token  The token to read.
 *
 * @return  string|null
 */
function stringValue($token): ?string
{
	if (!is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING)
	{
		return null;
	}

	$raw = $token[1];

	if (strlen($raw) < 2)
	{
		return null;
	}

	return substr($raw, 1, -1);
}

/**
 * Whether a method-name token is being called on the container.
 *
 * Reads backwards over the arrow and any -> chain, so both $container->get()
 * and $container->alias(...)->share(...) are seen for what they are.
 *
 * @param   array  $tokens  The token stream.
 * @param   int    $index   The index of the method-name token.
 *
 * @return  bool
 */
function isContainerCall(array $tokens, int $index): bool
{
	$arrow = null;

	for ($i = $index - 1; $i >= 0; $i--)
	{
		if (is_array($tokens[$i])
			&& in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
		{
			continue;
		}

		$arrow = $i;
		break;
	}

	if ($arrow === null || !is_array($tokens[$arrow]) || $tokens[$arrow][0] !== T_OBJECT_OPERATOR)
	{
		return false;
	}

	// Walk back over the chain. alias() and share() hand the container back, so
	// a call on one of those is still a call on the container; get() hands back
	// the service it built, so $container->get('Config')->get('x') is not.
	$depth = 0;

	for ($i = $arrow - 1; $i >= 0; $i--)
	{
		$token = $tokens[$i];

		if ($token === ')')
		{
			$depth++;
			continue;
		}

		if ($token === '(')
		{
			$depth--;
			continue;
		}

		if ($depth > 0)
		{
			continue;
		}

		if (is_array($token)
			&& in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OBJECT_OPERATOR], true))
		{
			continue;
		}

		if (is_array($token) && $token[0] === T_STRING)
		{
			if ($token[1] === 'get')
			{
				return false;
			}

			continue;
		}

		return is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$container';
	}

	return false;
}

$registered = [];
$requested  = [];

foreach (phpFilesIn($librarySource) as $file)
{
	$code = file_get_contents($file);

	// Only the providers matter, and they all say so in their own source.
	if (strpos($code, 'ServiceProviderInterface') === false)
	{
		continue;
	}

	$tokens = token_get_all($code);
	$count  = count($tokens);

	for ($i = 0; $i < $count; $i++)
	{
		$token = $tokens[$i];

		if (!is_array($token) || $token[0] !== T_STRING)
		{
			continue;
		}

		$method = $token[1];

		if (!in_array($method, ['share', 'set', 'alias', 'get', 'protect'], true))
		{
			continue;
		}

		// The receiver has to be the container itself. Without this, every
		// $config->get('some_setting') in the tree reads as a container key.
		if (!isContainerCall($tokens, $i))
		{
			continue;
		}

		$open = nextMeaningful($tokens, $i);

		if ($open === null || $open[1] !== '(')
		{
			continue;
		}

		$first = nextMeaningful($tokens, $open[0]);

		if ($first === null)
		{
			continue;
		}

		$line = $token[2];

		if ($method === 'alias')
		{
			// alias(Something::class, 'Key') — the key is the second argument.
			$depth = 0;
			$comma = null;

			for ($j = $open[0] + 1; $j < $count; $j++)
			{
				$current = $tokens[$j];

				if ($current === '(')
				{
					$depth++;
					continue;
				}

				if ($current === ')')
				{
					if ($depth === 0)
					{
						break;
					}

					$depth--;
					continue;
				}

				if ($current === ',' && $depth === 0)
				{
					$comma = $j;
					break;
				}
			}

			if ($comma === null)
			{
				continue;
			}

			$second = nextMeaningful($tokens, $comma);

			if ($second !== null && ($key = stringValue($second[1])) !== null)
			{
				$registered[$key] = true;
			}

			continue;
		}

		$key = stringValue($first[1]);

		if ($key === null)
		{
			continue;
		}

		// A key built by concatenation is not a key this can check.
		$after = nextMeaningful($tokens, $first[0]);

		if ($after !== null && ($after[1] === '.' || (is_array($after[1]) && $after[1][0] === T_STRING)))
		{
			continue;
		}

		if ($method === 'get')
		{
			$requested[$key][] = str_replace($librarySource . '/', '', $file) . ':' . $line;

			continue;
		}

		$registered[$key] = true;
	}
}

// Keys that were already unregistered before any of this began. They are
// recorded rather than fixed: changing code this refactor never touched is not
// this refactor's business.
$knownFile = dirname(__DIR__) . '/container-keys.php';
$known     = is_file($knownFile) ? array_flip((array) require $knownFile) : [];

$missing = [];

foreach ($requested as $key => $places)
{
	if (!isset($registered[$key]) && !isset($known[$key]))
	{
		$missing[$key] = $places;
	}
}

if ($missing === [])
{
	printf(
		"Every one of the %d container keys asked for is registered%s.\n",
		count($requested),
		$known === [] ? '' : sprintf(', bar the %d recorded in container-keys.php', count($known))
	);

	exit(0);
}

fwrite(STDERR, "These container keys are asked for and never registered:\n\n");

ksort($missing);

foreach ($missing as $key => $places)
{
	fwrite(STDERR, "  '{$key}'\n");

	foreach ($places as $place)
	{
		fwrite(STDERR, "      {$place}\n");
	}

	fwrite(STDERR, "\n");
}

fwrite(
	STDERR,
	"A provider that asks for a service nobody registers throws the moment the\n"
	. "compiler builds it. Register the service, or take the argument the way the\n"
	. "rest of the library takes the application: optional, defaulted in the\n"
	. "constructor, and left out of the provider.\n"
);

exit(1);
