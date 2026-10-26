<?php
/**
 * @package    Joomla.Component.Builder.Tests
 * @created    26th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\Dispatcher;
use Joomla\Event\SubscriberInterface;

/**
 * Boot and dispatch the compiled, installed FieldOrderingTabs plugin in Joomla.
 *
 * Usage: php verify-native-content-plugin.php <site root> <plugins root>
 *
 * @since  6.2.0
 */

/**
 * Compare form semantics after Joomla trims XML whitespace while merging nodes.
 *
 * @param   SimpleXMLElement  $element  A source or loaded form element.
 *
 * @return  array
 * @since   6.2.0
 */
function nativeContentFormShape(SimpleXMLElement $element): array
{
	$attributes = [];
	$children = [];

	foreach ($element->attributes() as $name => $value)
	{
		$attributes[$name] = (string) $value;
	}

	ksort($attributes);

	foreach ($element->children() as $child)
	{
		$children[] = nativeContentFormShape($child);
	}

	return [$element->getName(), $attributes, trim((string) $element), $children];
}

$site = $argv[1] ?? '';
$plugins = $argv[2] ?? '';

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| $site === '' || !is_file($site . '/configuration.php')
	|| !is_file($site . '/.jcb-api-test-site')
	|| trim((string) file_get_contents($site . '/.jcb-api-test-site')) !== realpath($site))
{
	fwrite(STDERR, "usage: php verify-native-content-plugin.php <disposable site root> <plugins root>\n");
	exit(2);
}

define('_JEXEC', 1);
define('JPATH_BASE', realpath($site));
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
Factory::$application = $container->get(Joomla\Console\Application::class);
Factory::$application->createExtensionNamespaceMap();

if (Factory::$application->get('sitename') !== 'JCB API tests')
{
	throw new RuntimeException('Native plugin dispatch requires the disposable JCB API tests site.');
}

$name = 'componentbuilderfieldorderingtabs';
$installed = JPATH_PLUGINS . '/content/' . $name;
$sourceForm = $plugins . '/plg_content_' . $name . '/forms/admin_view.xml';
$installedForm = $installed . '/forms/admin_view.xml';

if (!is_file($sourceForm) || !is_file($installedForm)
	|| hash_file('sha256', $sourceForm) !== hash_file('sha256', $installedForm))
{
	throw new RuntimeException('The generated package must install the exact pinned FieldOrderingTabs form.');
}

$manifest = simplexml_load_file($installed . '/' . $name . '.xml');

if (!$manifest || (string) $manifest['group'] !== 'content'
	|| (string) $manifest->namespace['path'] !== 'src'
	|| (string) $manifest->namespace === '')
{
	throw new RuntimeException('The installed plugin must have a native namespace and content manifest.');
}

$db = $container->get(DatabaseInterface::class);
$query = $db->createQuery()
	->select($db->quoteName('extension_id'))
	->from($db->quoteName('#__extensions'))
	->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
	->where($db->quoteName('folder') . ' = ' . $db->quote('content'))
	->where($db->quoteName('element') . ' = ' . $db->quote($name));
$ids = $db->setQuery($query)->loadColumn();

if (count($ids) !== 1)
{
	throw new RuntimeException('The compiled plugin must have exactly one installed extension record.');
}

$db->setQuery($db->createQuery()
	->update($db->quoteName('#__extensions'))
	->set($db->quoteName('enabled') . ' = 1')
	->set($db->quoteName('access') . ' = 1')
	->where($db->quoteName('extension_id') . ' = ' . (int) $ids[0]))->execute();
$container->get(CacheControllerFactoryInterface::class)
	->createCacheController('callback', ['defaultgroup' => 'com_plugins'])->clean();

// Load Joomla's published-plugin metadata before checking plugin deprecations:
// Joomla 6's loader itself still calls deprecated Factory accessors. No plugin
// class is booted here; the provider and subscriber below remain under capture.
if (!PluginHelper::getPlugin('content', $name))
{
	throw new RuntimeException('The installed content plugin must be discoverable by Joomla.');
}

$dispatcher = new Dispatcher();
$deprecations = [];
set_error_handler(static function (int $severity, string $message) use (&$deprecations): bool
{
	$deprecations[] = $message;
	return true;
}, E_DEPRECATED | E_USER_DEPRECATED);

try
{
	if (PluginHelper::importPlugin('content', $name, true, $dispatcher) !== true)
	{
		throw new RuntimeException('Joomla failed to import the compiled content subscriber.');
	}

	$plugin = Factory::getApplication()->bootPlugin($name, 'content');
	$reflection = new ReflectionClass($plugin);
	$expectedClass = (string) $manifest->namespace . '\\Extension\\ComponentbuilderFieldOrderingTabs';

	if (!$plugin instanceof SubscriberInterface || $reflection->getName() !== $expectedClass
		|| realpath($reflection->getFileName()) !== realpath($installed . '/src/Extension/ComponentbuilderFieldOrderingTabs.php')
		|| $plugin::getSubscribedEvents() !== ['onContentPrepareForm' => 'onContentPrepareForm']
		|| !in_array([$plugin, 'onContentPrepareForm'], $dispatcher->getListeners('onContentPrepareForm'), true))
	{
		throw new RuntimeException('The generated provider must boot the installed class and register its native subscriber directly.');
	}

	$form = new Form('com_componentbuilder.admin_view');
	$form->load('<form><fields name="params"><field name="existing" type="text" default="retained" /></fields></form>');
	$form->bind(['params' => ['existing' => 'preserved']]);
	$event = new PrepareFormEvent('onContentPrepareForm', ['subject' => $form, 'data' => []]);
	$dispatcher->dispatch('onContentPrepareForm', $event);
	$fields = $form->getXml()->xpath('./fields[@name="params"]/fieldset[@name="fieldordering"]/fields[@name="fieldordering"]/field');
	$actual = [];

	foreach ($fields as $field)
	{
		$actual[(string) $field['name']] = (string) $field['type'];
	}

	if ($actual !== [
		'add_admin_ordering' => 'radio',
		'admin_ordering_fields' => 'subform',
		'add_linked_ordering' => 'radio',
		'linked_ordering_fields' => 'subform',
	] || $form->getValue('existing', 'params') !== 'preserved'
		|| $event->getForm() !== $form || $event->getArgument('result', []) !== [])
	{
		throw new RuntimeException('Native dispatch must add all four ordering fields, preserve form data, and produce no legacy result.');
	}

	$source = simplexml_load_file($sourceForm);
	$expectedFields = $source->xpath('./fields[@name="params"]/fieldset[@name="fieldordering"]/fields[@name="fieldordering"]/field');

	foreach ($expectedFields as $index => $field)
	{
		if (nativeContentFormShape($field) !== nativeContentFormShape($fields[$index]))
		{
			throw new RuntimeException('Native dispatch must retain the real subforms, options and conditional rules.');
		}
	}

	$unrelated = new Form('com_content.article');
	$unrelated->load('<form><field name="title" type="text" /></form>');
	$before = $unrelated->getXml()->asXML();
	$dispatcher->dispatch('onContentPrepareForm', new PrepareFormEvent('onContentPrepareForm', [
		'subject' => $unrelated, 'data' => [],
	]));

	if ($unrelated->getXml()->asXML() !== $before)
	{
		throw new RuntimeException('The installed plugin must leave unrelated forms unchanged.');
	}
}
finally
{
	restore_error_handler();
}

if ($deprecations !== [])
{
	throw new RuntimeException('Installed native plugin emitted deprecations: ' . implode('; ', $deprecations));
}

echo "Installed provider booted {$expectedClass} from {$reflection->getFileName()}.\n";
echo "Native PrepareFormEvent added four real ordering fields and preserved nested subforms and existing data.\n";
echo "Unrelated forms were unchanged; no legacy results or plugin deprecations were emitted.\n";
