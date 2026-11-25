<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    25th November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Asset;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;

// Save the permission values rendered by the installed Demo's Options form.
$site = $argv[1] ?? '';

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| $site === '' || !is_file($site . '/configuration.php')
	|| !is_file($site . '/.jcb-api-test-site')
	|| trim((string) file_get_contents($site . '/.jcb-api-test-site')) !== realpath($site))
{
	fwrite(STDERR, "usage: php check-api-permissions-save.php <disposable site root>\n");
	exit(2);
}

define('_JEXEC', 1);
define('JPATH_BASE', realpath($site) . '/administrator');
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/administrator/index.php';
$_SERVER['REQUEST_URI'] = '/administrator/index.php?option=com_config&component=com_demo';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

// The normal administrator request sets these before dispatching com_config.
define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_config');
define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_config');
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_COMPONENT);

$container = Factory::getContainer();
$container->alias('session.web', 'session.web.administrator')
	->alias('session', 'session.web.administrator')
	->alias(Joomla\CMS\Session\Session::class, 'session.web.administrator')
	->alias(Joomla\Session\Session::class, 'session.web.administrator')
	->alias(Joomla\Session\SessionInterface::class, 'session.web.administrator');
$app = $container->get(AdministratorApplication::class);
Factory::$application = $app;

if ($app->get('sitename') !== 'JCB API tests')
{
	throw new RuntimeException('Saving component Options requires the disposable JCB API tests site.');
}

$app->createExtensionNamespaceMap();
$app->loadIdentity($container->get(UserFactoryInterface::class)->loadUserByUsername('apiadmin'));
$app->loadLanguage();
$app->loadDocument();
$app->getInput()->set('option', 'com_config');
$app->getInput()->set('component', 'com_demo');

$asset = new Asset($container->get(DatabaseInterface::class));

if (!$asset->loadByName('com_demo'))
{
	throw new RuntimeException('The compiled Demo component asset is missing.');
}

$existing = json_decode($asset->rules, true, 512, JSON_THROW_ON_ERROR);
$expected = array_map('intval', $existing['core.api'] ?? []);
ksort($expected);

if (!in_array(1, $expected, true) || !in_array(0, $expected, true))
{
	throw new RuntimeException('The test requires explicit core.api allow and deny fixtures.');
}

$model = $app->bootComponent('com_config')->getMVCFactory()->createModel('Component', 'Administrator');
$field = $model->getForm()->getField('rules');
$document = new DOMDocument();
$document->loadHTML($field->input, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
$xpath = new DOMXPath($document);
$posted = [];

// A browser submits only the controls present on the generated component's form.
foreach ($xpath->query('//select/option[@selected]') as $selected)
{
	$name = $selected->parentNode->getAttribute('name');

	if (preg_match('/^jform\[rules\]\[([^\]]+)\]\[(\d+)\]$/D', $name, $match) === 1)
	{
		$posted[$match[1]][$match[2]] = $selected->getAttribute('value');
	}
}

foreach ($expected as $group => $grant)
{
	if (($posted['core.api'][$group] ?? null) !== (string) $grant)
	{
		throw new RuntimeException('Demo Options did not render the stored core.api rule for group ' . $group);
	}
}

$component = $model->getComponent();
$params = $component->getParams()->toArray();
$params['rules'] = $field->filter($posted);

if (!$model->save(['option' => 'com_demo', 'id' => $component->id, 'params' => $params])
	|| !$asset->loadByName('com_demo'))
{
	throw new RuntimeException('Joomla could not save and reload Demo component Options.');
}

$saved = json_decode($asset->rules, true, 512, JSON_THROW_ON_ERROR);
$actual = array_map('intval', $saved['core.api'] ?? []);
ksort($actual);

if ($actual !== $expected)
{
	throw new RuntimeException('Saving Demo Options lost or changed an explicit core.api rule.');
}

echo "Saving Demo Options preserves explicit API grants and denials.\n";
