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

use VDM\Tests\Support\ConditionalBrowserCompiler;


$tests = dirname(__DIR__);
$root = dirname($tests, 3);
require $tests . '/bootstrap.php';
$options = getopt('', ['write', 'help']);
if (isset($options['help']))
{
	fwrite(STDOUT, "Usage: php bin/regenerate-conditional-browser-preview.php [--write]\n"
		. "Checks the Field browser prefix and listeners against the normalized fixture.\n"
		. "--write replaces only those generated areas, preserving authored custom code.\n"
		. "This does not rebuild JCB from its authoritative maintenance package.\n");

	exit(0);
}

$fixture = json_decode(file_get_contents(
	$tests . '/Fixtures/Compiler/ConditionalValidation/field-input.json'
), true, 512, JSON_THROW_ON_ERROR);
$xml = simplexml_load_file($root . '/admin/forms/field.xml');
if ($xml === false)
{
	throw new RuntimeException('Unable to read the Field preview form metadata.');
}
$fields = [];
foreach ($xml->xpath('/form/fieldset/field') as $field)
{
	$fields[(string) $field['name']] = $field;
}

/**
 * Reconstruct normalized browser metadata from the reviewed group and form XML.
 *
 * @param   array  $group   One trusted preview group.
 * @param   array  $extras  Optional visual targets absent from required metadata.
 * @param   array  $fields  Top-level fields from the installed form definition.
 *
 * @return  array  Input for the real ViewScript renderer.
 *
 * @throws  RuntimeException  If the fixture cannot resolve its declared fields.
 * @since   6.2.1
 */
function previewBrowserCondition(array $group, array $extras, array $fields): array
{
	if (count($group['matches']) !== 1)
	{
		throw new RuntimeException('The Field preview expects one selector per authored condition.');
	}
	$match = $group['matches'][0];
	if (!isset($fields[$match['name']]))
	{
		throw new RuntimeException('Unknown preview selector: ' . $match['name']);
	}
	$targets = [];
	foreach (array_merge($group['targets'], $extras) as $name)
	{
		if (!isset($fields[$name]))
		{
			throw new RuntimeException('Unknown preview target: ' . $name);
		}
		$field = $fields[$name];
		$targets[] = [
			'name' => $name,
			'type' => strtolower((string) $field['type']),
			'required' => in_array((string) $field['required'], ['true', '1'], true) ? 'yes' : 'no',
			'filter' => (string) $field['filter'],
		];
	}
	$behavior = $group['show'] ? 1 : 2;
	if (!$group['toggle'])
	{
		$behavior += 2;
	}

	return [
		'match_field' => $match['name'],
		'match_name' => $match['name'],
		'match_type' => strtolower((string) $fields[$match['name']]['type']),
		'match_extends' => (string) $fields[$match['name']]['extends'],
		'match_behavior' => $match['behavior'],
		'match_options' => implode(PHP_EOL, $match['options']),
		'target_relation' => 0,
		'target_behavior' => $behavior,
		'target_field' => $targets,
	];
}

$conditions = [];
$last = count($fixture['groups']) - 1;
foreach ($fixture['groups'] as $index => $group)
{
	if ($index === $last)
	{
		foreach ($fixture['browserOnlyGroups'] as $visual)
		{
			$conditions[] = previewBrowserCondition([
				'matches' => [[
					'name' => $visual['match'], 'behavior' => 1,
					'options' => [$visual['option']],
				]],
				'targets' => [$visual['target']], 'show' => true, 'toggle' => true,
			], [], $fields);
		}
	}
	$conditions[] = previewBrowserCondition($group, $fixture['browserExtraTargets'][$index] ?? [], $fields);
}
$rendered = (new ConditionalBrowserCompiler())->render($conditions, 'field');
if ($rendered['groups'] !== $fixture['groups'])
{
	throw new RuntimeException('Field browser metadata differs from the server preview fixture.');
}

$scriptPath = 'media/js/field.js';
$script = file_get_contents($root . '/' . $scriptPath);
$headerEnd = strpos($script, '*/') + 2;
$customStart = strpos($script, '// set properties the options');
if ($headerEnd === 2 || $customStart === false)
{
	throw new RuntimeException('Unable to locate the Field script header or authored custom-code boundary.');
}
$outputs[$scriptPath] = substr($script, 0, $headerEnd) . PHP_EOL . PHP_EOL
	. $rendered['script'] . PHP_EOL . PHP_EOL . PHP_EOL . substr($script, $customStart);

$templatePath = 'admin/tmpl/field/default.php';
$template = file_get_contents($root . '/' . $templatePath);
$start = strpos($template, '// Generated field conditions (compiler preview).');
if ($start === false)
{
	$start = strpos($template, '// #jform_datalenght listeners');
}
$found = preg_match('/\/\*\*\r?\n \* Initialize form listeners and code block click handlers\./', $template, $boundary, PREG_OFFSET_CAPTURE);
if ($start === false || $found !== 1 || $boundary[0][1] <= $start)
{
	throw new RuntimeException('Unable to locate the Field generated-listener boundary.');
}
$listeners = preg_replace('/<\/?script[^>]*>/', '', $rendered['footer']);
$outputs[$templatePath] = substr($template, 0, $start)
	. '// Generated field conditions (compiler preview).' . PHP_EOL
	. trim($listeners) . PHP_EOL . PHP_EOL
	. substr($template, $boundary[0][1]);

$stale = [];
foreach ($outputs as $path => $generated)
{
	if ($generated === file_get_contents($root . '/' . $path))
	{
		continue;
	}
	if (isset($options['write']))
	{
		file_put_contents($root . '/' . $path, $generated);
		fwrite(STDOUT, 'Regenerated conditional browser preview: ' . $path . PHP_EOL);
	}
	else
	{
		$stale[] = $path;
	}
}
if ($stale !== [])
{
	fwrite(STDERR, 'Conditional browser preview differs from the production emitter: ' . implode(', ', $stale) . PHP_EOL);
	exit(1);
}
fwrite(STDOUT, "Conditional browser preview matches the fixture and production emitter.\n");
