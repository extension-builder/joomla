<?php
/**
 * Disposable installed-Joomla integration for the extrusion view and engine.
 *
 * Invoked only by .github/gui-tests/run.sh. This is never installed with JCB.
 * Seeds isolated records, verifies real Data writes and the Power compiler,
 * restores test changes, and leaves fixtures for read-only browser journeys.
 */

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| !is_file('/tmp/jcb-disposable-gui-stack'))
{
	fwrite(STDERR, "This fixture requires the disposable GUI harness.\n");
	exit(2);
}

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Factory as Extrusion;
use VDM\Joomla\Componentbuilder\Compiler\Factory as Compiler;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_componentbuilder');
require_once JPATH_COMPONENT_ADMINISTRATOR . '/src/Helper/PowerloaderHelper.php';

// CLI has no component request option; the Data table prefix needs the same
// explicit context the administrator establishes before resolving JCB services.
VDM\Joomla\Utilities\Component\Helper::setOption('com_componentbuilder');

$db = $container->get(DatabaseInterface::class);
$root = JPATH_ROOT . '/tmp/jcb-extrusion-fixtures';
$manifestPath = $root . '/manifest.json';

function check(bool $condition, string $description): void
{
	if (!$condition)
	{
		throw new RuntimeException($description);
	}

	echo 'PASS ' . $description . PHP_EOL;
}

function identity(string $name): string
{
	return Extrusion::_('Extrusion.Resolver.Guid')->derive(['disposable-gui-extrusion', $name]);
}

function readRecord(string $table, string $guid): array
{
	global $db;
	$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__componentbuilder_' . $table))
		->where($db->quoteName('guid') . ' = ' . $db->quote($guid));
	return (array) $db->setQuery($query)->loadAssoc();
}

function insertRecord(string $table, array $row): int
{
	global $db;
	check(readRecord($table, $row['guid']) === [], 'fixture GUID is not an existing record: ' . $row['guid']);
	$object = (object) $row;
	$db->insertObject('#__componentbuilder_' . $table, $object, 'id');
	return (int) $object->id;
}

function writeSource(string $path, string $content): void
{
	if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0755, true))
	{
		throw new RuntimeException('Cannot create source directory.');
	}
	if (file_put_contents($path, $content) !== strlen($content))
	{
		throw new RuntimeException('Cannot write source fixture.');
	}
}

function engine(array $manifest, bool $dry): object
{
	$engine = Extrusion::_('Extrusion.Powers.Extruder');
	$engine->reset()->libraries([$manifest['library_b']])->component($manifest['component_b_id'])
		->onExisting('update')->dryRun($dry);
	Extrusion::_('Extrusion.Config')->set('sourceComponent', $manifest['component_b_id']);
	return $engine;
}

/** Read database records through the actual compiler Power preparation service. */
function compiled(array $manifest, string $owner, string $guid): array
{
	Compiler::unset();
	$config = Compiler::_('Config');
	foreach (['component_id' => $manifest['component_' . $owner . '_id'],
		'component_code_name' => 'extrusionfixture' . $owner, 'joomla_version' => 6,
		'lang_target' => 'both', 'lang_prefix' => 'COM_EXTRUSIONFIXTURE' . strtoupper($owner),
		'namespace_prefix' => 'ExtrusionFixture', 'component_namespace' => 'Extrusionfixture' . $owner,
		'add_power' => true, 'add_super_powers' => false, 'add_placeholders' => false,
		'approved_paths' => [], 'jcb_powers_path' => 'libraries/jcb_powers'] as $key => $value)
	{
		$config->set($key, $value);
	}
	$values = Extrusion::_('Extrusion.Powers.Resolver.Namespacer')->context($manifest['component_' . $owner . '_id']);
	foreach ($values['map'] as $key => $value)
	{
		Compiler::_('Placeholder')->set(substr($key, 3, -3), $value);
	}
	$power = Compiler::_('Power')->get($guid);
	check(is_object($power), 'the actual Power compiler resolves ' . $guid);
	return [
		'fqn' => $power->namespace, 'namespace' => $power->_namespace,
		'path' => $power->path . '/' . $power->file_name . '.php',
		'body' => $power->main_class_code, 'head' => $power->head ?? ''
	];
}

try
{
	$mode = $argv[1] ?? '';
	if ($mode === '--seed')
	{
		check(!file_exists($manifestPath), 'fixture directory is fresh');
		$manifest = [];
		$template = '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory';
		foreach (['a', 'b'] as $owner)
		{
			$guid = identity('factory-' . $owner);
			$manifest['factory_' . $owner] = $guid;
			insertRecord('power', [
				'guid' => $guid, 'name' => 'Factory', 'system_name' => 'Extrusion Fixture Factory ' . strtoupper($owner),
				'namespace' => $template, 'type' => 'class',
				'main_class_code' => base64_encode("\tpublic function value(): int\n\t{\n\t\treturn 1;\n\t}\n"),
				'licensing_template' => base64_encode('A curated licence.'), 'add_licensing_template' => 2,
				'power_version' => '1.0.0', 'published' => 1
			]);
		}
		$manifest['consumer_b'] = identity('consumer-b');
		insertRecord('power', [
			'guid' => $manifest['consumer_b'], 'name' => 'Consumer', 'system_name' => 'Extrusion Fixture Consumer B',
			'namespace' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Consumer', 'type' => 'class',
			'main_class_code' => base64_encode("\tpublic function value(): int { return 1; }\n"),
			'power_version' => '1.0.0', 'published' => 1
		]);
		$manifest['shared'] = identity('shared');
		insertRecord('power', [
			'guid' => $manifest['shared'], 'name' => 'Value', 'system_name' => 'Extrusion Fixture Shared Value',
			'namespace' => '[[[NamespacePrefix]]]\\Joomla\\Abstraction.Registry.Value', 'type' => 'class',
			'main_class_code' => base64_encode("\tpublic function value(): int { return 1; }\n"), 'published' => 1
		]);
		foreach (['a', 'b'] as $owner)
		{
			$references = [$manifest['factory_' . $owner], $manifest['shared']];
			if ($owner === 'b')
			{
				$references[] = $manifest['consumer_b'];
			}
			$tokens = array_map(static fn(string $guid): string => 'Super___' . str_replace('-', '_', $guid) . '___Power', $references);
			$manifest['component_' . $owner] = identity('component-' . $owner);
			$manifest['component_' . $owner . '_id'] = insertRecord('joomla_component', [
				'guid' => $manifest['component_' . $owner], 'name' => 'Extrusion Fixture ' . strtoupper($owner),
				'system_name' => 'Extrusion Fixture ' . strtoupper($owner), 'name_code' => 'extrusionfixture' . $owner,
				'add_namespace_prefix' => 1, 'namespace_prefix' => 'ExtrusionFixture',
				'add_php_helper_both' => 1, 'php_helper_both' => base64_encode(implode("\n", $tokens)),
				'component_version' => '1.0.0', 'published' => 1
			]);
		}
		$manifest['library_b'] = $root . '/b/ExtrusionFixture.Joomla';
		$manifest['library_shared'] = $root . '/shared/ExtrusionFixture.Joomla';
		$manifest['library_new'] = $root . '/new/ExtrusionFixture.Independent';
		writeSource($manifest['library_b'] . '/src/Extrusionfixtureb/Factory.php', "<?php\nnamespace ExtrusionFixture\\Joomla\\Extrusionfixtureb;\nclass Factory\n{\n\tpublic function value(): int\n\t{\n\t\treturn 22;\n\t}\n}\n");
		writeSource($manifest['library_b'] . '/src/Extrusionfixtureb/Consumer.php', "<?php\nnamespace ExtrusionFixture\\Joomla\\Extrusionfixtureb;\nuse ExtrusionFixture\\Joomla\\Extrusionfixtureb\\Factory as Maker;\nclass Consumer\n{\n\tpublic function value(): int\n\t{\n\t\treturn (new Maker())->value();\n\t}\n}\n");
		writeSource($manifest['library_shared'] . '/src/Abstraction/Registry/Value.php', "<?php\nnamespace ExtrusionFixture\\Joomla\\Abstraction\\Registry;\nclass Value\n{\n\tpublic function value(): int { return 2; }\n}\n");
		foreach (['Alpha', 'Beta', 'Delta', 'Entry', 'Load', 'Report', 'Source', 'Write'] as $name)
		{
			writeSource($manifest['library_new'] . '/src/' . $name . '.php', "<?php\nnamespace ExtrusionFixture\\Independent;\nclass " . $name . "\n{\n\tpublic function value(): int { return 1; }\n}\n");
		}
		writeSource($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
		echo json_encode($manifest, JSON_THROW_ON_ERROR) . PHP_EOL;
	}
	elseif ($mode === '--verify')
	{
		$manifest = json_decode(file_get_contents($manifestPath), true, 32, JSON_THROW_ON_ERROR);
		$before = [];
		foreach (['factory_a', 'factory_b', 'consumer_b', 'shared'] as $name)
		{
			$before[$name] = readRecord('power', $manifest[$name]);
		}
		$componentBefore = readRecord('joomla_component', $manifest['component_b']);
		$compiledA = compiled($manifest, 'a', $manifest['factory_a']);
		$compiledB = compiled($manifest, 'b', $manifest['factory_b']);
		try
		{
			$report = engine($manifest, true)->extrude();
			check($report->get('plan.status') === 'preview', 'real schema preview is valid: ' . json_encode($report->get('plan')));
			check(readRecord('power', $manifest['factory_b']) === $before['factory_b'], 'dry run performs no Power writes');
			$expected = Extrusion::_('Extrusion.Registry.Plan')->writes();
			$fingerprint = $report->get('plan.fingerprint');
			$engine = engine($manifest, false);
			Extrusion::_('Extrusion.Config')->set('approvedPlan', $fingerprint);
			$report = $engine->extrude();
			check($report->get('plan.status') === 'committed', 'real Data pipeline commits approved changes: ' . json_encode($report->get('plan')));
			check(readRecord('power', $manifest['factory_a']) === $before['factory_a'], 'every A field and metadata value remains unchanged');
			check(readRecord('power', $manifest['factory_b'])['namespace'] === $before['factory_b']['namespace'], 'B retains its curated namespace representation');
			check(readRecord('power', $manifest['factory_b'])['licensing_template'] === $before['factory_b']['licensing_template'], 'B retains its curated licence');
			$consumer = Extrusion::_('Data.Item')->table('power')->get($manifest['consumer_b'], 'guid');
			check(str_contains(json_encode($consumer->use_selection), $manifest['factory_b']), 'the persisted alias references B GUID');
			check(!str_contains(json_encode($consumer->use_selection), $manifest['factory_a']), 'the persisted alias never references A GUID');
			foreach ($expected as $entry)
			{
				$record = Extrusion::_('Data.Item')->table($entry['table'])->get($entry['identity'], $entry['key']);
				foreach ($entry['payload'] as $column => $value)
				{
					// Model Load decodes JSON objects as stdClass; compare canonical
					// raw values, retaining exact scalar types and relationship data.
					check(VDM\Joomla\Componentbuilder\Extrusion\Registry\Plan::digest($record->{$column})
						=== VDM\Joomla\Componentbuilder\Extrusion\Registry\Plan::digest($value),
						'preview equals actual Data value: ' . $entry['table'] . '.' . $column);
				}
			}
			$compiledAAfter = compiled($manifest, 'a', $manifest['factory_a']);
			$compiledBAfter = compiled($manifest, 'b', $manifest['factory_b']);
			check($compiledAAfter === $compiledA, 'A compiler namespace, file placement and code are unchanged');
			check($compiledBAfter['fqn'] === $compiledB['fqn'] && $compiledBAfter['path'] === $compiledB['path'], 'B compiler namespace and output placement are preserved');
			check(str_contains($compiledBAfter['body'], 'return 22;'), 'B compiler consumes the updated code');
			$compiledConsumer = compiled($manifest, 'b', $manifest['consumer_b']);
			check(str_contains($compiledConsumer['head'], 'ExtrusionFixture\\Joomla\\Extrusionfixtureb\\Factory as Maker'), 'Power compiler restores the correct aliased namespace');
			writeSource($root . '/compiler-evidence.json', json_encode(['a' => $compiledAAfter, 'b' => $compiledBAfter, 'consumer' => $compiledConsumer], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
			$standingB = readRecord('power', $manifest['factory_b']);
			$standingConsumer = readRecord('power', $manifest['consumer_b']);
			$report = engine($manifest, false)->extrude();
			check($report->get('plan.status') === 'unchanged', 'identical re-import is an effective no-op: ' . json_encode($report->get('plan')));
			check(readRecord('power', $manifest['factory_b']) === $standingB && readRecord('power', $manifest['consumer_b']) === $standingConsumer, 'no-op does not modify database metadata');
			check(readRecord('joomla_component', $manifest['component_b']) === $componentBefore, 'no auxiliary component writes');
			// Bind an approval to old input, then prove the engine rejects new source.
			$fingerprint = engine($manifest, true)->extrude()->get('plan.fingerprint');
			$path = $manifest['library_b'] . '/src/Extrusionfixtureb/Factory.php';
			$source = file_get_contents($path);
			try
			{
				writeSource($path, str_replace('return 22;', 'return 23;', $source));
				$engine = engine($manifest, false);
				Extrusion::_('Extrusion.Config')->set('approvedPlan', $fingerprint);
				check($engine->extrude()->get('plan.status') === 'blocked', 'stale approval blocks real persistence');
				check(readRecord('power', $manifest['factory_b']) === $standingB, 'stale rejection writes nothing');
			}
			finally
			{
				writeSource($path, $source);
			}
		}
		finally
		{
			// Preserve seeded starting values for the browser. The entire stack is
			// destroyed by the harness even if verification fails before here.
			foreach ($before as $row)
			{
				$restore = (object) $row;
				$db->updateObject('#__componentbuilder_power', $restore, 'id', true);
			}
		}
		echo "PASS installed-schema extrusion and actual Power compiler integration\n";
	}
	else
	{
		throw new InvalidArgumentException('Expected --seed or --verify.');
	}
}
catch (Throwable $error)
{
	fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
	exit(1);
}
