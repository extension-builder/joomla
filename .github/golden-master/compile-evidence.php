<?php
/**
 * Run the full compiler in the disposable golden stack and retain final state.
 *
 * This verification driver is never shipped in an installed component.
 */

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| !is_file('/tmp/jcb-disposable-gui-stack'))
{
	fwrite(STDERR, "This driver requires the disposable golden stack.\n");
	exit(2);
}

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use VDM\Joomla\Componentbuilder\Compiler\Factory as Compiler;

$componentGuid = $argv[1] ?? '';
$target = (int) ($argv[2] ?? 0);
$evidencePath = $argv[3] ?? '';

if (!preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', $componentGuid)
	|| !in_array($target, [3, 4, 5, 6], true)
	|| !preg_match('~^/tmp/jcb-golden-[a-z-]+\.json$~', $evidencePath))
{
	fwrite(STDERR, "Usage: compile-evidence.php COMPONENT_GUID TARGET /tmp/jcb-golden-NAME.json [compiler options]\n");
	exit(2);
}

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_componentbuilder');
require_once JPATH_COMPONENT_ADMINISTRATOR . '/src/Helper/PowerloaderHelper.php';
VDM\Joomla\Utilities\Component\Helper::setOption('com_componentbuilder');
Joomla\CMS\Layout\LayoutHelper::$defaultBasePath = JPATH_COMPONENT_ADMINISTRATOR . '/layouts';

$db = $container->get(DatabaseInterface::class);
$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__componentbuilder_joomla_component'))
	->where($db->quoteName('guid') . ' = ' . $db->quote($componentGuid));
$components = $db->setQuery($query)->loadObjectList();

if (count($components) !== 1)
{
	throw new RuntimeException('A complete, unique component definition is required before compiling.');
}

$component = $components[0];
$input = Factory::$application->getInput();
$input->post->set('component_id', (int) $component->id);
$input->post->set('joomla_version', $target);
$input->post->set('show_advanced_options', 1);

foreach (array_slice($argv, 4) as $option)
{
	if (!preg_match('/^--([a-z-]+)=(.*)$/', $option, $match))
	{
		throw new RuntimeException('Golden compiler options must use --name=value syntax.');
	}

	$input->post->set(str_replace('-', '_', $match[1]), $match[2]);
}

Compiler::unset();
$config = Compiler::_('Config');
$config->set('joomla_version', $target);
$config->set('show_advanced_options', true);
$config->set('backup', 0);
$config->set('repository', 0);
$config->set('add_super_powers', false);
$started = hrtime(true);

try
{
	$compiler = Compiler::_('Compiler');
	$early = array_keys(Compiler::_('Power')->active);

	if (!$compiler->run())
	{
		throw new RuntimeException('Complete compiler run failed.');
	}

	$powers = [];
	$libraryRoots = [];

	foreach (Compiler::_('Power')->active as $guid => $power)
	{
		if (!is_object($power) || !isset($power->namespace, $power->type, $power->path,
			$power->file_name, $power->path_root, $power->path_jcb))
		{
			throw new RuntimeException('The completed compiler contains an unresolved Power: ' . $guid);
		}

		$powers[$guid] = [
			'fqn' => $power->namespace,
			'type' => $power->type,
			'path' => $power->path . '/' . $power->file_name . '.php',
			'root' => $power->path_root,
		];

		if (str_starts_with($power->path_jcb, 'libraries/'))
		{
			$libraryRoots[$power->path_jcb] = true;
		}
	}

	ksort($powers);
	$joomlaPowers = [];

	foreach (Compiler::_('Joomla.Power')->active as $guid => $power)
	{
		if (!is_object($power) || !isset($power->namespace, $power->type))
		{
			throw new RuntimeException('The completed compiler contains an unresolved Joomla Power: ' . $guid);
		}

		$joomlaPowers[$guid] = [
			'namespace' => $power->namespace,
			'type' => $power->type,
		];
	}

	ksort($joomlaPowers);
	$late = array_values(array_diff(array_keys($powers), $early));
	sort($early);
	sort($late);
	$roots = array_keys($libraryRoots);
	sort($roots);
	$evidence = [
		'host' => Joomla\CMS\Version::MAJOR_VERSION,
		'target' => $target,
		'add_power' => (bool) $config->get('add_power', true),
		'component' => $componentGuid,
		'name' => $component->system_name,
		'input_sha256' => hash('sha256', json_encode($component, JSON_THROW_ON_ERROR)),
		'completed' => true,
		'early_guids' => $early,
		'late_guids' => $late,
		'powers' => $powers,
		'joomla_powers' => $joomlaPowers,
		'library_roots' => $roots,
	];
	file_put_contents($evidencePath, json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
	fwrite(STDOUT, json_encode([
		'component' => $componentGuid,
		'host' => $evidence['host'],
		'target' => $target,
		'final_powers' => count($powers),
		'late_powers' => count($late),
		'seconds' => (hrtime(true) - $started) / 1e9,
		'peak_bytes' => memory_get_peak_usage(true),
	], JSON_THROW_ON_ERROR) . "\n");
}
finally
{
	Compiler::unset();
}
