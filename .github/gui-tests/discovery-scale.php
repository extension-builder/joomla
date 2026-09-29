<?php
/**
 * Installed SQL and Power-discovery scale evidence for a compiled component.
 *
 * Usage: php discovery-scale.php COMPONENT_GUID COMPONENT_ROOT LIBRARY_ROOT...
 * Called by the disposable golden harness after a real core compilation.
 * The library arguments identify emitted Power source, not generated MVC code.
 * All catalogue growth is rolled back. Harvest and preview must issue no writes.
 * Timings cover CLI parsing, discovery, assembly, preview and serialization;
 * they do not measure HTTP, browser rendering or production server latency.
 */

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| !is_file('/tmp/jcb-disposable-gui-stack'))
{
	fwrite(STDERR, "This probe requires the disposable integration harness.\n");
	exit(2);
}

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Monitor\DebugMonitor;
use VDM\Joomla\Componentbuilder\Extrusion\Factory as Extrusion;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_componentbuilder');
require_once JPATH_COMPONENT_ADMINISTRATOR . '/src/Helper/PowerloaderHelper.php';
VDM\Joomla\Utilities\Component\Helper::setOption('com_componentbuilder');

$db = $container->get(DatabaseInterface::class);
$transaction = false;
$previousMonitor = $db->getMonitor();

function requireEvidence(bool $condition, string $description): void
{
	if (!$condition)
	{
		throw new RuntimeException($description);
	}
}

/** Deterministic inventory includes actual bytes and excludes no source silently. */
function inventory(array $roots): array
{
	$files = [];
	foreach ($roots as $root)
	{
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
		foreach ($iterator as $file)
		{
			if (!$file->isFile() || $file->isLink())
			{
				continue;
			}
			$path = $file->getRealPath();
			$files[$path] = ['bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $path)];
		}
	}
	ksort($files);
	return [
		'files' => count($files), 'bytes' => array_sum(array_column($files, 'bytes')),
		'php_files' => count(array_filter(array_keys($files), static fn(string $path): bool => str_ends_with(strtolower($path), '.php'))),
		'sha256' => hash('sha256', json_encode($files, JSON_THROW_ON_ERROR))
	];
}

/** Capture semantic identities without copying private source code to evidence. */
function identities(): array
{
	$result = [];
	foreach ((array) Extrusion::_('Extrusion.Registry.Harvest')->get('classes', []) as $key => $class)
	{
		$resolution = $class['resolution'];
		$candidates = array_keys($resolution['candidates'] ?? []);
		sort($candidates);
		$result[$key] = [
			'fqn' => $class['fqn'], 'stored' => $class['stored'], 'type' => $class['type'],
			'matched_guid' => $class['matched_guid'], 'write_guid' => $class['guid'],
			'status' => $resolution['status'], 'write_scope' => $resolution['write_scope'],
			'write_eligibility' => $resolution['write_eligibility'],
			'namespace' => $resolution['namespace'], 'candidates' => $candidates,
			'blockers' => $resolution['blockers'] ?? []
		];
	}
	ksort($result);
	return $result;
}

/** Compare actual cold discoveries with the full compiler's final emitted set. */
function compilerParity(array $compiler, string $componentRoot, array $libraries, int $componentId): array
{
	$expected = [];
	foreach ($compiler['powers'] as $guid => $power)
	{
		$path = $componentRoot . '/' . $power['path'];
		$included = array_filter($libraries, static fn(string $root): bool => str_starts_with($path, $root . '/'));
		if ($power['root'] !== 'component' || $included === [])
		{
			continue;
		}
		requireEvidence(is_file($path), 'A final compiled Power file is absent: ' . $power['path']);
		$expected[$guid] = ['fqn' => $power['fqn'], 'type' => $power['type'], 'path' => $power['path']];
	}
	requireEvidence($expected !== [], 'The compiler oracle contains no Power emitted in the supplied libraries.');
	$observed = [];
	$unmatched = [];
	foreach ((array) Extrusion::_('Extrusion.Registry.Harvest')->get('classes', []) as $class)
	{
		$guid = $class['matched_guid'];
		if ($class['resolution']['status'] !== 'matched' || !$guid || isset($observed[$guid]))
		{
			$unmatched[] = ['fqn' => $class['fqn'], 'status' => $class['resolution']['status'], 'matched_guid' => $guid];
			continue;
		}
		$observed[$guid] = ['fqn' => $class['fqn'], 'type' => $class['type'],
			'path' => substr($class['file'], strlen($componentRoot) + 1)];
	}
	$context = Extrusion::_('Extrusion.Powers.Resolver.References')->context($componentId);
	$expectedGuids = array_keys($compiler['powers']);
	$discoveredGuids = array_keys($context['powers']);
	sort($expectedGuids);
	sort($discoveredGuids);
	$mismatches = [];
	foreach (array_intersect(array_keys($expected), array_keys($observed)) as $guid)
	{
		if ($expected[$guid] !== $observed[$guid])
		{
			$mismatches[$guid] = ['expected' => $expected[$guid], 'observed' => $observed[$guid]];
		}
	}
	$result = [
		'expected_final_powers' => count($expectedGuids), 'discovered_graph_powers' => count($discoveredGuids),
		'expected_emitted_library_powers' => count($expected), 'matched_emitted_library_powers' => count($observed),
		'graph_missing' => array_values(array_diff($expectedGuids, $discoveredGuids)),
		'graph_extra' => array_values(array_diff($discoveredGuids, $expectedGuids)),
		'emitted_missing' => array_values(array_diff(array_keys($expected), array_keys($observed))),
		'emitted_extra' => array_values(array_diff(array_keys($observed), array_keys($expected))),
		'emitted_mismatches' => $mismatches, 'unmatched_sources' => $unmatched, 'graph_gaps' => $context['gaps']
	];
	$result['passed'] = $context['complete'] && $result['graph_missing'] === [] && $result['graph_extra'] === []
		&& $result['emitted_missing'] === [] && $result['emitted_extra'] === [] && $mismatches === [] && $unmatched === [];
	return $result;
}

/** Measure the real engine, including JSON serialization of its public result. */
function phase(object $engine, string $method): array
{
	global $db, $previousMonitor;
	$monitor = new DebugMonitor();
	$db->setMonitor($monitor);
	memory_reset_peak_usage();
	$started = hrtime(true);
	try
	{
		$report = $engine->{$method}();
		requireEvidence(!$report->get('skipped.depth') && !$report->get('skipped.maxfiles'),
			'The source scanner hit a limit; a partial workload cannot establish scale evidence.');
		$semantic = identities();
		$serialized = json_encode(['report' => $report->toArray(), 'harvest' => $engine->harvested()], JSON_THROW_ON_ERROR);
		$seconds = (hrtime(true) - $started) / 1e9;
		$peak = memory_get_peak_usage(true);
		$queries = $monitor->getLogs();
		foreach ($queries as $query)
		{
			requireEvidence(!preg_match('/^\s*(?:INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|TRUNCATE|CREATE|RENAME|LOAD|CALL|GRANT|REVOKE|COMMIT|ROLLBACK)\b/i', $query),
				'Harvest/preview attempted a mutating SQL statement.');
			if (preg_match('/\bFROM\s+`?' . preg_quote($db->getPrefix() . 'componentbuilder_power', '/') . '`?(?:\s|$)/i', $query))
			{
				requireEvidence((bool) preg_match('/\bWHERE\b/i', $query), 'Power discovery performed an unbounded catalogue query.');
			}
		}
		$statuses = array_count_values(array_column($semantic, 'status'));
		ksort($statuses);
		return [
			'wall_seconds' => $seconds, 'peak_memory_bytes_including_query_monitor' => $peak,
			'sql_queries' => count($queries), 'serialized_bytes' => strlen($serialized),
			'classes' => count($semantic), 'statuses' => $statuses,
			'parsed_files' => (int) $report->get('counts.powers.parsed', 0),
			'reused_parses' => (int) $report->get('counts.powers.parse_reused', 0),
			'graph' => Extrusion::_('Extrusion.Powers.Resolver.References')->diagnostics(),
			'identity_work' => Extrusion::_('Extrusion.Powers.Resolver.Identity')->work(),
			'semantic_sha256' => hash('sha256', json_encode($semantic, JSON_THROW_ON_ERROR)),
			'plan' => $method === 'extrude' ? $report->get('plan') : null,
			'no_database_writes' => true
		];
	}
	finally
	{
		$db->setMonitor($previousMonitor);
	}
}

/** Unrelated roots reference only their own uniquely named, unrelated Powers. */
function growCatalogue(int $from, int $to, string $prefix): void
{
	global $db;
	$guid = Extrusion::_('Extrusion.Resolver.Guid');
	for ($number = $from; $number < $to; $number++)
	{
		$name = $prefix . $number;
		$powerGuid = $guid->derive(['installed-discovery-scale', $name, 'power']);
		$power = (object) [
			'guid' => $powerGuid, 'name' => $name, 'namespace' => $prefix . '\\Unrelated.' . $name,
			'system_name' => $name, 'type' => 'class', 'main_class_code' => base64_encode(''), 'published' => 1
		];
		$db->insertObject('#__componentbuilder_power', $power, 'id');
		$component = (object) [
			'guid' => $guid->derive(['installed-discovery-scale', $name, 'component']),
			'name' => $name, 'name_code' => strtolower($name), 'system_name' => $name,
			'add_namespace_prefix' => 1, 'namespace_prefix' => $prefix, 'add_powers' => 1,
			'add_php_helper_both' => 1,
			'php_helper_both' => base64_encode('Super___' . str_replace('-', '_', $powerGuid) . '___Power'),
			'component_version' => '1.0.0', 'published' => 1
		];
		$db->insertObject('#__componentbuilder_joomla_component', $component, 'id');
	}
}

/** EXPLAIN the same projected equality lookup used by Existing::read(). */
function queryPlans(): array
{
	global $db;
	$table = $db->quoteName('#__componentbuilder_power');
	$sample = $db->setQuery('SELECT guid, name, namespace FROM ' . $table . ' ORDER BY id DESC', 0, 1)->loadAssoc();
	requireEvidence(is_array($sample), 'No installed Power exists for indexed lookup evidence.');
	$indexes = $db->setQuery('SHOW INDEX FROM ' . $table)->loadAssocList();
	$plans = [];
	foreach (['guid', 'name', 'namespace'] as $column)
	{
		$matching = array_values(array_filter($indexes, static fn(array $index): bool =>
			$index['Column_name'] === $column && (int) $index['Seq_in_index'] === 1));
		requireEvidence($matching !== [], 'The installed schema has no leading index for Power ' . $column . '.');
		$sql = 'SELECT a.id, a.guid, a.name, a.namespace, a.type, a.system_name FROM ' . $table . ' AS a WHERE '
			. $db->quoteName('a.' . $column) . ' = ' . $db->quote($sample[$column]);
		$rows = $db->setQuery('EXPLAIN ' . $sql)->loadAssocList();
		requireEvidence($rows !== [], 'EXPLAIN returned no evidence for Power ' . $column . '.');
		foreach ($rows as $row)
		{
			requireEvidence(!empty($row['key']) && !in_array(strtoupper((string) $row['type']), ['ALL', 'INDEX'], true),
				'Power ' . $column . ' equality does not use an indexed seek: ' . json_encode($row));
		}
		$plans[$column] = ['indexes' => $matching, 'explain' => $rows];
	}
	return $plans;
}

try
{
	$componentGuid = strtolower((string) ($argv[1] ?? ''));
	$componentRoot = realpath((string) ($argv[2] ?? ''));
	$libraries = array_map('realpath', array_slice($argv, 3));
	requireEvidence(VDM\Joomla\Utilities\GuidHelper::valid($componentGuid), 'A valid actual component GUID is required.');
	requireEvidence(is_string($componentRoot) && is_dir($componentRoot), 'The actual compiled component source root is required.');
	requireEvidence($libraries !== [] && !in_array(false, $libraries, true), 'Supply at least one real emitted Power library root.');
	foreach ($libraries as $library)
	{
		requireEvidence(is_dir($library), 'A Power library root must be a directory.');
	}
	$componentQuery = $db->getQuery(true)->select('*')->from($db->quoteName('#__componentbuilder_joomla_component'))
		->where($db->quoteName('guid') . ' = ' . $db->quote($componentGuid));
	$rows = $db->setQuery($componentQuery)->loadAssocList();
	requireEvidence(count($rows) === 1, 'Exactly one actual component definition must be installed before the probe.');
	$component = $rows[0];
	$target = (int) (getenv('JCB_TARGET_VERSION') ?: 6);
	requireEvidence(in_array($target, [3, 4, 5, 6], true), 'Unsupported generated Joomla target.');
	$compilerPath = getenv('JCB_COMPILER_EVIDENCE');
	$compiler = null;
	if ($compilerPath !== false && $compilerPath !== '')
	{
		$compiler = json_decode(file_get_contents($compilerPath), true, 512, JSON_THROW_ON_ERROR);
		requireEvidence(!empty($compiler['completed']) && $compiler['component'] === $componentGuid
			&& $compiler['target'] === $target && is_array($compiler['powers']), 'Compiler evidence does not identify this completed build.');
	}
	$evidence = [
		'kind' => 'installed-cli-power-harvest-preview-scale', 'php' => PHP_VERSION,
		'joomla' => (new Joomla\CMS\Version())->getShortVersion(), 'generated_target' => $target,
		'database' => ['driver' => $db->getName(), 'version' => $db->getVersion()],
		'component' => ['guid' => $componentGuid, 'id' => (int) $component['id'], 'name_code' => $component['name_code']],
		'component_source' => inventory([$componentRoot]), 'power_source' => inventory($libraries),
		'library_roots' => $libraries, 'source_revision' => getenv('JCB_SOURCE_REVISION') ?: null,
		'compiler_evidence_sha256' => $compiler === null ? null : hash_file('sha256', $compilerPath),
		'limitations' => [
			'CLI timings exclude HTTP and browser rendering.',
			'EXPLAIN rows are optimizer estimates, not measured rows examined.',
			'Joomla DebugMonitor records call stacks and adds measured time and memory overhead.',
			'Cold means empty operation-local extrusion caches, not a cold operating-system or database cache.',
			'Preview blockers are retained; this probe never supplies manual pairing or write approval.'
		],
		'samples' => []
	];
	$prefix = 'JcbScaleUnrelated' . bin2hex(random_bytes(8));
	$db->transactionStart();
	$transaction = true;
	$previousSize = 0;
	foreach ([0, 100, 1000] as $size)
	{
		growCatalogue($previousSize, $size, $prefix);
		$previousSize = $size;
		$engine = Extrusion::_('Extrusion.Powers.Extruder');
		$engine->reset()->libraries($libraries)->component((int) $component['id'])->onExisting('update')->dryRun(true);
		$config = Extrusion::_('Extrusion.Config');
		$config->set('sourceComponent', (int) $component['id']);
		$config->set('joomla_version', $target);
		$cold = phase($engine, 'harvest');
		if ($compiler !== null)
		{
			$parity = compilerParity($compiler, $componentRoot, $libraries, (int) $component['id']);
			$evidence['compiler_parity'][$size] = $parity;
			requireEvidence($parity['passed'], 'Discovery differs from the final core compiler; inspect compiler_parity evidence.');
		}
		$repeat = phase($engine, 'harvest');
		$preview = phase($engine, 'extrude');
		requireEvidence($cold['classes'] > 0 && $cold['parsed_files'] > 0, 'The compiled Power workload is empty.');
		$observations = $cold['parsed_files'] + $cold['reused_parses'];
		requireEvidence($repeat['parsed_files'] === 0 && $repeat['reused_parses'] === $observations, 'Unchanged harvest reparsed source files.');
		requireEvidence($preview['parsed_files'] === 0 && $preview['reused_parses'] === $observations, 'Preview reparsed unchanged source files.');
		requireEvidence($cold['semantic_sha256'] === $repeat['semantic_sha256'] && $cold['semantic_sha256'] === $preview['semantic_sha256'],
			'Unchanged harvest/preview changed semantic Power identities.');
		requireEvidence(!in_array('powers.prepare', array_column($preview['plan']['blockers'] ?? [], 'key'), true),
			'The actual dry-run preview threw an exception.');
		$sample = ['unrelated_components_added' => $size, 'unrelated_powers_added' => $size,
			'cold_harvest' => $cold, 'unchanged_harvest' => $repeat, 'dry_run_preview' => $preview];
		if ($evidence['samples'] !== [])
		{
			$baseline = $evidence['samples'][0];
			foreach (['cold_harvest', 'unchanged_harvest', 'dry_run_preview'] as $phase)
			{
				requireEvidence($sample[$phase]['graph'] === $baseline[$phase]['graph'], 'Unrelated catalogue growth increased selected graph work.');
				requireEvidence($sample[$phase]['identity_work'] === $baseline[$phase]['identity_work'], 'Unrelated catalogue growth changed candidate evaluation work.');
				requireEvidence($sample[$phase]['semantic_sha256'] === $baseline[$phase]['semantic_sha256'], 'Unrelated catalogue growth changed semantic identities.');
				requireEvidence($sample[$phase]['sql_queries'] <= $baseline[$phase]['sql_queries'], 'Unrelated catalogue growth increased SQL query count.');
			}
		}
		$evidence['samples'][] = $sample;
	}
	$evidence['power_equality_query_plans_at_1000'] = queryPlans();
	requireEvidence($db->setQuery($componentQuery)->loadAssocList() === [$component], 'The selected component definition changed.');
	$db->transactionRollback();
	$transaction = false;
	foreach (['joomla_component', 'power'] as $table)
	{
		$left = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName('#__componentbuilder_' . $table)
			. ' WHERE ' . $db->quoteName('system_name') . ' LIKE ' . $db->quote($prefix . '%'))->loadResult();
		requireEvidence($left === 0, 'Rollback left unrelated fixture records in ' . $table . '.');
	}
	$evidence['rollback_verified'] = true;
	$evidence['passed'] = true;
	echo json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
}
catch (Throwable $error)
{
	if ($transaction)
	{
		$db->setMonitor($previousMonitor);
		$db->transactionRollback();
	}
	if (isset($evidence))
	{
		$evidence['passed'] = false;
		$evidence['failure'] = $error->getMessage();
		echo json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
	}
	fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
	exit(1);
}
