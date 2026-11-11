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

use VDM\Tests\Support\ConditionalPreviewCompiler;


$tests = dirname(__DIR__);
$root = dirname($tests, 3);
require $tests . '/vendor/autoload.php';
$options = getopt('', ['write', 'help']);
if (isset($options['help']))
{
	fwrite(STDOUT, "Usage: php bin/regenerate-conditional-model-preview.php [--write]\n"
		. "Checks conditional models and their shared rule against normalized fixtures.\n"
		. "--write replaces only their generated conditional validation members.\n"
		. "This does not rebuild JCB from its authoritative maintenance package.\n");
	exit(0);
}

$compiler = new ConditionalPreviewCompiler();
$outputs = [];
foreach (glob($tests . '/Fixtures/Compiler/ConditionalValidation/*-input.json') as $fixturePath)
{
	$fixture = json_decode(file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);
	if (preg_match('/^[a-z][a-z_]*$/D', $fixture['view']) !== 1)
	{
		throw new RuntimeException('The normalized preview view name is invalid.');
	}
	$class = ucfirst($fixture['view']) . 'Model';
	$path = 'admin/src/Model/' . $class . '.php';
	$fragments = $compiler->fragments($fixture['groups'], $fixture['view'], 'Componentbuilder');
	$outputs[$path] = $compiler->model(file_get_contents($root . '/' . $path), $class, $fragments);
}
$rulePath = 'admin/src/Rule/ConditionalrequiredRule.php';
$outputs[$rulePath] = $compiler->rule(
	file_get_contents($root . '/' . $rulePath),
	'ConditionalrequiredRule',
	$fragments['rule']
);
$stale = [];
foreach ($outputs as $path => $generated)
{
	$source = file_get_contents($root . '/' . $path);
	if ($generated === $source)
	{
		continue;
	}
	if (isset($options['write']))
	{
		file_put_contents($root . '/' . $path, $generated);
		fwrite(STDOUT, 'Regenerated conditional preview: ' . $path . PHP_EOL);
	}
	else
	{
		$stale[] = $path;
	}
}
if ($stale !== [])
{
	fwrite(STDERR, 'Conditional preview differs from the production emitters: ' . implode(', ', $stale) . PHP_EOL);
	exit(1);
}
fwrite(STDOUT, "Conditional model previews match the fixture and production emitters.\n");
