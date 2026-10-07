<?php
/**
 * Run the normal compile command with pinned disposable-fixture repositories.
 *
 * JCB_API_SITE_ROOT names the site made by run.sh. The read-only Actions token
 * and repository overrides stay in process memory, outside the definitions.
 */

use Joomla\CMS\Factory;
use VDM\Joomla\Componentbuilder\Compiler\Factory as Compiler;
use VDM\Joomla\Utilities\Component\Helper;

$site = getenv('JCB_API_SITE_ROOT');

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| !is_string($site) || $site === '' || !is_file($site . '/configuration.php')
	|| !is_file($site . '/.jcb-api-test-site')
	|| trim((string) file_get_contents($site . '/.jcb-api-test-site')) !== realpath($site)
	|| ($argv[1] ?? '') !== 'componentbuilder:compile:component')
{
	fwrite(STDERR, "This compile driver requires the disposable API test site.\n");
	exit(2);
}

define('_JEXEC', 1);
define('JPATH_BASE', realpath($site));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
Factory::$application->createExtensionNamespaceMap();

if (Factory::getConfig()->get('sitename') !== 'JCB API tests')
{
	throw new RuntimeException('Pinned Power fixtures require the disposable JCB API tests site.');
}

define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_componentbuilder');
require_once JPATH_COMPONENT_ADMINISTRATOR . '/src/Helper/PowerloaderHelper.php';
Helper::setOption('com_componentbuilder');
$githubToken = getenv('GITHUB_TOKEN');

if (is_string($githubToken) && $githubToken !== '')
{
	Helper::getParams('com_componentbuilder')->set('github_access_token', $githubToken);
}

unset($githubToken);
Compiler::unset();
$config = Compiler::_('Config');

foreach (require __DIR__ . '/../golden-master/power-repositories.php' as $key => $paths)
{
	$config->set($key, $paths);
}

// The existing command sets component/target options and runs the real compiler.
Factory::$application->execute();
