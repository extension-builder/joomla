<?php
/**
 * Run the normal Joomla console with in-memory public-fixture authentication.
 */

require __DIR__ . '/bootstrap.php';

if (($argv[1] ?? '') === '--component-inventory')
{
	$db = $container->get(Joomla\Database\DatabaseInterface::class);
	$query = $db->getQuery(true)->select($db->quoteName(['id', 'guid', 'system_name']))
		->from($db->quoteName('#__componentbuilder_joomla_component'))->order($db->quoteName('id'));
	fwrite(STDOUT, json_encode($db->setQuery($query)->loadAssocList(),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
	exit(0);
}

Joomla\CMS\Factory::$application->execute();
