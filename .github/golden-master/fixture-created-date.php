<?php
/**
 * Pin imported creation dates for the reviewed disposable Hello World fixture.
 *
 * The importer assigns today's date. The build-date option controls last build,
 * not component/view creation dates, so both inputs must be deterministic.
 */

require __DIR__ . '/bootstrap.php';

use Joomla\Database\DatabaseInterface;

$componentGuid = $argv[1] ?? '';
$created = '2026-10-07 00:00:00';

if ($componentGuid !== '3745af8f-f96b-4e17-831e-eb4062cd4389'
	|| ($argv[2] ?? '') !== '562624ab-48bf-4979-9a14-6b10cf3635de')
{
	fwrite(STDOUT, "No reviewed creation-date fixture for this component.\n");
	exit(0);
}

$db = $container->get(DatabaseInterface::class);
$records = [];
$find = static function (string $table, string $guid) use ($db): object
{
	$query = $db->getQuery(true)->select($db->quoteName(['id', 'guid', 'created']))
		->from($db->quoteName('#__componentbuilder_' . $table))
		->where($db->quoteName('guid') . ' = ' . $db->quote($guid));
	$rows = $db->setQuery($query)->loadObjectList();

	if (count($rows) !== 1)
	{
		throw new RuntimeException('The creation-date fixture requires one ' . $table . ' definition per GUID.');
	}

	return $rows[0];
};

$records['joomla_component'][$componentGuid] = $find('joomla_component', $componentGuid);

foreach (['admin_view' => 'adminview', 'site_view' => 'siteview', 'custom_admin_view' => 'customadminview'] as $table => $key)
{
	$column = 'add' . $table . 's';
	$query = $db->getQuery(true)->select($db->quoteName($column))
		->from($db->quoteName('#__componentbuilder_component_' . $table . 's'))
		->where($db->quoteName('joomla_component') . ' = ' . $db->quote($componentGuid));

	foreach ($db->setQuery($query)->loadColumn() as $json)
	{
		$views = $json === null || $json === '' ? [] : json_decode($json, true, 512, JSON_THROW_ON_ERROR);

		if (!is_array($views))
		{
			throw new RuntimeException('The creation-date fixture requires a valid component view list.');
		}

		foreach ($views as $view)
		{
			$guid = $view[$key] ?? '';

			if (!is_string($guid) || !preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', $guid))
			{
				throw new RuntimeException('The creation-date fixture requires linked view GUIDs.');
			}

			$records[$table][$guid] = $find($table, $guid);
		}
	}
}

$db->transactionStart();

try
{
	foreach ($records as $table => $rows)
	{
		foreach ($rows as $record)
		{
			$query = $db->getQuery(true)->update($db->quoteName('#__componentbuilder_' . $table))
				->set($db->quoteName('created') . ' = ' . $db->quote($created))
				->where($db->quoteName('id') . ' = ' . (int) $record->id);
			$db->setQuery($query)->execute();

			if ($find($table, $record->guid)->created !== $created)
			{
				throw new RuntimeException('The creation-date fixture could not verify its stored input.');
			}
		}
	}

	$db->transactionCommit();
}
catch (Throwable $error)
{
	$db->transactionRollback();
	throw $error;
}

fwrite(STDOUT, json_encode(['component' => $componentGuid, 'created' => $created,
	'records' => array_map('array_keys', $records)], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
