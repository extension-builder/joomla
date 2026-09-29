<?php
/**
 * Bootstrap the installed disposable Joomla host for golden evidence drivers.
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

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
Factory::$application->createExtensionNamespaceMap();

// The regular console supplies an HTTP origin before booting extension plugins.
$_SERVER['HTTP_HOST'] = 'joomla.invalid';
$_SERVER['REQUEST_URI'] = '/set/by/golden/driver';
$_SERVER['HTTPS'] = 'on';
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_componentbuilder');
require_once JPATH_COMPONENT_ADMINISTRATOR . '/src/Helper/PowerloaderHelper.php';
VDM\Joomla\Utilities\Component\Helper::setOption('com_componentbuilder');
Joomla\CMS\Layout\LayoutHelper::$defaultBasePath = JPATH_COMPONENT_ADMINISTRATOR . '/layouts';

// The read-only Actions token stays in process memory, never in definitions.
$githubToken = getenv('GITHUB_TOKEN');

if (is_string($githubToken) && $githubToken !== '')
{
	VDM\Joomla\Utilities\Component\Helper::getParams('com_componentbuilder')
		->set('github_access_token', $githubToken);
}

unset($githubToken);
