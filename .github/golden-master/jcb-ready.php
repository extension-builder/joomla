<?php
/**
 * Verify the released JCB is present on a disposable CI test stack.
 */

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

define('_JEXEC', 1);
define('JPATH_BASE', $argv[1]);

try
{
	if (!is_file(JPATH_BASE . '/administrator/components/com_componentbuilder/componentbuilder.xml'))
	{
		throw new RuntimeException('The JCB administrator manifest is missing.');
	}

	require JPATH_BASE . '/includes/defines.php';
	require JPATH_BASE . '/includes/framework.php';

	$db = Factory::getContainer()->get(DatabaseInterface::class);
	$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__extensions'))
		->where($db->quoteName('type') . ' = ' . $db->quote('component'))
		->where($db->quoteName('element') . ' = ' . $db->quote('com_componentbuilder'))
		->where($db->quoteName('enabled') . ' = 1');

	if ((int) $db->setQuery($query)->loadResult() !== 1)
	{
		throw new RuntimeException('The Joomla registry does not have exactly one enabled JCB component.');
	}
}
catch (Throwable $exception)
{
	fwrite(STDERR, $exception->getMessage() . PHP_EOL);
	exit(1);
}
