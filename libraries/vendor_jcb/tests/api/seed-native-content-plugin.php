<?php
/**
 * @package    Joomla.Component.Builder.Tests
 * @created    26th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Store the migrated FieldOrderingTabs source as a component-linked definition.
 *
 * The pinned plugins checkout supplies the real class body, imports and forms.
 * Normal compiler database loading, folder copying and packaging must reproduce
 * the plugin; this does not import a complete maintained JCB source export.
 *
 * Usage: php seed-native-content-plugin.php <site root> <plugins root> [component guid]
 *
 * @since  6.2.0
 */

$site = $argv[1] ?? '';
$plugins = $argv[2] ?? '';
$component = $argv[3] ?? '1c20aec5-bf1a-44e7-9deb-d1c920ca591d';

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| $site === '' || !is_file($site . '/configuration.php')
	|| !is_file($site . '/.jcb-api-test-site')
	|| trim((string) file_get_contents($site . '/.jcb-api-test-site')) !== realpath($site)
	|| preg_match('/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/i', $component) !== 1)
{
	fwrite(STDERR, "usage: php seed-native-content-plugin.php <disposable site root> <plugins root> [component guid]\n");
	exit(2);
}

$sourcePath = $plugins . '/plg_content_componentbuilderfieldorderingtabs/src/Extension/ComponentbuilderFieldOrderingTabs.php';
$formsPath = realpath($plugins . '/plg_content_componentbuilderfieldorderingtabs/forms');

if (!is_file($sourcePath) || $formsPath === false || !is_file($formsPath . '/admin_view.xml'))
{
	throw new RuntimeException('The pinned FieldOrderingTabs class and forms are required; no substitute fixture is allowed.');
}

require_once $site . '/configuration.php';
$config = new JConfig();

if ($config->sitename !== 'JCB API tests' || preg_match('/^[A-Za-z0-9_]+$/', $config->dbprefix) !== 1)
{
	throw new RuntimeException('The native plugin fixture requires the disposable JCB API tests site.');
}

require_once $site . '/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Power/Parser.php';
$parser = new VDM\Joomla\Componentbuilder\Power\Parser();
$source = file_get_contents($sourcePath);
$body = $parser->getClassCode($source);
$imports = $parser->getUseStatements($source);

if ($body === null || $body === '' || $imports === null
	|| !str_contains($body, 'function getSubscribedEvents()')
	|| !str_contains($body, 'onContentPrepareForm(PrepareFormEvent $event): void'))
{
	throw new RuntimeException('The source fixture must contain the migrated native form subscriber.');
}

$mainClassCode = base64_encode($body);
$head = base64_encode(implode(PHP_EOL, $imports));
$groupGuid = '73223363-7b75-4e4d-935b-9c6121e2caf1';
$pluginGuid = 'bea9d1e9-391d-43aa-b82d-64f01c7ab2b8';
$classExtends = 'ae2fafb4-e84b-4534-ba9c-6c9e1700b318';
$now = gmdate('Y-m-d H:i:s');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
[$host, $port] = str_contains($config->host, ':') ? explode(':', $config->host, 2) : [$config->host, 3306];
$db = new mysqli($host, $config->user, $config->password, $config->db, (int) $port);
$db->set_charset('utf8mb4');
$prefix = $config->dbprefix;

try
{
	$db->begin_transaction();
	$components = $db->execute_query(
		"SELECT id FROM `{$prefix}componentbuilder_joomla_component` WHERE guid = ? AND published = 1", [$component]
	)->fetch_all(MYSQLI_ASSOC);
	$parents = $db->execute_query(
		"SELECT id FROM `{$prefix}componentbuilder_class_extends` WHERE guid = ? AND name = 'CMSPlugin'", [$classExtends]
	)->fetch_all(MYSQLI_ASSOC);

	if (count($components) !== 1 || count($parents) !== 1)
	{
		throw new RuntimeException('The shipped Demo component and CMSPlugin parent definition must be present.');
	}

	$existing = $db->execute_query(
		"SELECT id FROM `{$prefix}componentbuilder_joomla_plugin` WHERE guid = ?", [$pluginGuid]
	)->num_rows;
	$existing += $db->execute_query(
		"SELECT id FROM `{$prefix}componentbuilder_joomla_plugin_group` WHERE guid = ?", [$groupGuid]
	)->num_rows;

	if ($existing !== 0)
	{
		throw new RuntimeException('Refusing to duplicate native plugin fixture definitions; start with a fresh disposable site.');
	}

	$db->execute_query(
		"INSERT INTO `{$prefix}componentbuilder_joomla_plugin_group`"
		. " (`class_extends`, `name`, `params`, `published`, `created`, `version`, `guid`, `hits`, `ordering`)"
		. " VALUES (?, 'content', '', 1, ?, 1, ?, 0, 99)",
		[$classExtends, $now, $groupGuid]
	);
	$db->execute_query(
		"INSERT INTO `{$prefix}componentbuilder_joomla_plugin`"
		. " (`add_head`, `class_extends`, `description`, `fields`, `guid`, `head`, `joomla_plugin_group`,"
		. " `method_selection`, `main_class_code`, `name`, `plugin_version`, `property_selection`,"
		. " `system_name`, `update_server_target`, `published`, `created`, `version`, `hits`, `ordering`)"
		. " VALUES (1, ?, ?, '{}', ?, ?, ?, '{}', ?, 'ComponentbuilderFieldOrderingTabs', '1.0.0', '{}',"
		. " 'Native FieldOrderingTabs (installed regression fixture)', 3, 1, ?, 1, 0, 100)",
		[$classExtends, 'The migrated native form subscriber, compiled from its stored definition.',
			$pluginGuid, $head, $groupGuid, $mainClassCode, $now]
	);
	$db->execute_query(
		"INSERT INTO `{$prefix}componentbuilder_joomla_plugin_files_folders_urls`"
		. " (`joomla_plugin`, `addfoldersfullpath`, `published`, `created`, `version`, `hits`, `access`, `ordering`)"
		. " VALUES (?, ?, 1, ?, 1, 0, 1, 1)",
		[$pluginGuid, json_encode([['folderpath' => $formsPath, 'path' => '/', 'rename' => 0]], JSON_THROW_ON_ERROR), $now]
	);

	$links = $db->execute_query(
		"SELECT id, addjoomla_plugins FROM `{$prefix}componentbuilder_component_plugins`"
		. ' WHERE joomla_component = ? AND published = 1 FOR UPDATE', [$component]
	)->fetch_all(MYSQLI_ASSOC);

	if (count($links) !== 1)
	{
		throw new RuntimeException('Seed the existing webservices fixture first; its component link must be retained.');
	}

	$linked = json_decode($links[0]['addjoomla_plugins'], true, 512, JSON_THROW_ON_ERROR);

	if (!in_array('f1c2d3e4-5a6b-4c7d-8e9f-0a1b2c3d4e5f', array_column($linked, 'plugin'), true))
	{
		throw new RuntimeException('The API_ROUTES_METHOD webservices fixture must remain linked.');
	}

	$linked['native_content_regression'] = ['plugin' => $pluginGuid, 'target' => '1'];
	$db->execute_query(
		"UPDATE `{$prefix}componentbuilder_component_plugins` SET addjoomla_plugins = ?, modified = ? WHERE id = ?",
		[json_encode($linked, JSON_THROW_ON_ERROR), $now, $links[0]['id']]
	);
	$stored = $db->execute_query(
		"SELECT main_class_code, head FROM `{$prefix}componentbuilder_joomla_plugin` WHERE guid = ?", [$pluginGuid]
	)->fetch_assoc();

	if ($stored['main_class_code'] !== $mainClassCode || $stored['head'] !== $head)
	{
		throw new RuntimeException('The database must retain the exact extracted body and imports.');
	}

	$db->commit();
}
catch (Throwable $error)
{
	$db->rollback();
	throw $error;
}
finally
{
	$db->close();
}

echo "FieldOrderingTabs source SHA-256: " . hash_file('sha256', $sourcePath) . "\n";
echo "FieldOrderingTabs form SHA-256: " . hash_file('sha256', $formsPath . '/admin_view.xml') . "\n";
echo "Stored native plugin {$pluginGuid} and linked it alongside the API fixture to {$component}.\n";
