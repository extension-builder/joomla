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

// Shipped public repository rows have access_repo=1 and token=''. That explicit
// empty override removes the global GitHub Authorization header. Resolve and
// verify only our known fixture, then omit its empty override in memory.
$repositoryOption = array_search('-r', $argv, true);

if (($argv[1] ?? '') === 'componentbuilder:pull:joomla_component'
	&& $repositoryOption !== false
	&& ($argv[$repositoryOption + 1] ?? '') === '562624ab-48bf-4979-9a14-6b10cf3635de')
{
	$prepareRepository = require __DIR__ . '/public-package-repository.php';
	$repository = $prepareRepository(VDM\Joomla\Componentbuilder\Utilities\RepoHelper::getRepo(
		'562624ab-48bf-4979-9a14-6b10cf3635de'
	));
	$arguments = $argv;
	$arguments[$repositoryOption + 1] = json_encode($repository, JSON_THROW_ON_ERROR);
	$input = Joomla\CMS\Factory::$application->getConsoleInput();

	if (!$input instanceof Symfony\Component\Console\Input\ArgvInput)
	{
		throw new RuntimeException('The golden fixture requires the native argv console input.');
	}

	// Joomla exposes no console-input setter. Reinitialize its native ArgvInput
	// through the public constructor before execute() first binds the command.
	$input->__construct($arguments);
}

Joomla\CMS\Factory::$application->execute();
