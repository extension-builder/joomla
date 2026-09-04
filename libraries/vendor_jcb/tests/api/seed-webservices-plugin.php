<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    4th September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Link a web services plugin to the shipped demo component.
 *
 * The compiler never generates a plugin of the webservices group; a JCB user
 * creates one and links it to the component, and the compiler fills its
 * [[[API_ROUTES_METHOD]]] placeholder with the routes of every view that has
 * an API. The shipped demo data has no such plugin, so this seeds one, test
 * only, straight into the JCB tables of an installed site.
 *
 * usage: php seed-webservices-plugin.php <site root> [<component guid>]
 */

$site = $argv[1] ?? '';
$component = $argv[2] ?? '1c20aec5-bf1a-44e7-9deb-d1c920ca591d';

if ($site === '' || !is_file($site . '/configuration.php'))
{
	fwrite(STDERR, "usage: php seed-webservices-plugin.php <site root> [<component guid>]\n");
	exit(2);
}

require_once $site . '/configuration.php';

$config = new JConfig();
$db = @new mysqli(...(static function (string $host, string $user, string $password, string $name): array
{
	$port = 3306;

	if (str_contains($host, ':'))
	{
		[$host, $port] = explode(':', $host, 2);
		$port = (int) $port;
	}

	return [$host, $user, $password, $name, $port];
})($config->host, $config->user, $config->password, $config->db));

if ($db->connect_error)
{
	fwrite(STDERR, "database: {$db->connect_error}\n");
	exit(1);
}

$db->set_charset('utf8mb4');
$prefix = $config->dbprefix;

// the identities are fixed so a re-run is a no-op
$groupGuid = 'e7a0b6c2-9d4f-4c3e-8a1b-2f5d6c7e8f90';
$pluginGuid = 'f1c2d3e4-5a6b-4c7d-8e9f-0a1b2c3d4e5f';
$classExtends = 'ae2fafb4-e84b-4534-ba9c-6c9e1700b318'; // CMSPlugin, as every shipped plugin group
$now = gmdate('Y-m-d H:i:s');

$exists = static function (string $table, string $guid) use ($db, $prefix): bool
{
	$stmt = $db->prepare("SELECT id FROM `{$prefix}componentbuilder_{$table}` WHERE guid = ?");
	$stmt->bind_param('s', $guid);
	$stmt->execute();
	$found = $stmt->get_result()->num_rows > 0;
	$stmt->close();

	return $found;
};

if (!$exists('joomla_plugin_group', $groupGuid))
{
	$stmt = $db->prepare(
		"INSERT INTO `{$prefix}componentbuilder_joomla_plugin_group`"
		. " (`class_extends`, `name`, `params`, `published`, `created`, `version`, `guid`, `hits`, `ordering`)"
		. " VALUES (?, 'webservices', '', 1, ?, 1, ?, 0, 14)"
	);
	$stmt->bind_param('sss', $classExtends, $now, $groupGuid);
	$stmt->execute();
	$stmt->close();
	echo "plugin group webservices: created\n";
}
else
{
	echo "plugin group webservices: present\n";
}

if (!$exists('joomla_plugin', $pluginGuid))
{
	$mainClassCode = base64_encode("\t[[[API_ROUTES_METHOD]]]");
	$description = 'Registers the JSON:API routes of [[[Component]]] (test fixture).';
	$readme = base64_encode('Test fixture: the routes of every view with an API.');
	$stmt = $db->prepare(
		"INSERT INTO `{$prefix}componentbuilder_joomla_plugin`"
		. " (`add_head`, `add_php_method_uninstall`, `add_php_postflight_install`, `add_php_postflight_update`,"
		. " `add_php_preflight_install`, `add_php_preflight_uninstall`, `add_php_preflight_update`, `add_php_script_construct`,"
		. " `add_sales_server`, `add_sql`, `add_sql_uninstall`, `add_update_server`, `addreadme`, `class_extends`, `description`,"
		. " `fields`, `guid`, `head`, `joomla_plugin_group`, `method_selection`, `main_class_code`, `name`, `plugin_version`,"
		. " `property_selection`, `readme`, `sales_server`, `system_name`, `update_server`, `update_server_target`,"
		. " `published`, `created`, `version`, `hits`, `ordering`)"
		. " VALUES (0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, ?, ?, '{}', ?, '', ?, '{}', ?, '[[[ComponentNamespace]]]Api', '1.0.0',"
		. " '{}', ?, 0, 'API routes (test fixture)', 0, 3, 1, ?, 1, 0, 99)"
	);
	$stmt->bind_param('sssssss', $classExtends, $description, $pluginGuid, $groupGuid, $mainClassCode, $readme, $now);
	$stmt->execute();
	$stmt->close();
	echo "plugin [[[ComponentNamespace]]]Api: created\n";
}
else
{
	echo "plugin [[[ComponentNamespace]]]Api: present\n";
}

$stmt = $db->prepare("SELECT id, addjoomla_plugins FROM `{$prefix}componentbuilder_component_plugins` WHERE joomla_component = ?");
$stmt->bind_param('s', $component);
$stmt->execute();
$link = $stmt->get_result()->fetch_assoc();
$stmt->close();

$plugins = $link ? (json_decode((string) $link['addjoomla_plugins'], true) ?: []) : [];
$linked = false;

foreach ($plugins as $entry)
{
	if (($entry['plugin'] ?? '') === $pluginGuid)
	{
		$linked = true;
	}
}

if (!$linked)
{
	$plugins['addjoomla_plugins' . count($plugins)] = ['plugin' => $pluginGuid, 'target' => '1'];
	$json = json_encode($plugins);

	if ($link)
	{
		$stmt = $db->prepare("UPDATE `{$prefix}componentbuilder_component_plugins` SET addjoomla_plugins = ?, modified = ? WHERE id = ?");
		$stmt->bind_param('ssi', $json, $now, $link['id']);
	}
	else
	{
		$stmt = $db->prepare(
			"INSERT INTO `{$prefix}componentbuilder_component_plugins`"
			. " (`addjoomla_plugins`, `joomla_component`, `published`, `created`, `version`, `hits`, `access`, `ordering`)"
			. " VALUES (?, ?, 1, ?, 1, 0, 1, 1)"
		);
		$stmt->bind_param('sss', $json, $component, $now);
	}

	$stmt->execute();
	$stmt->close();
	echo "component {$component}: plugin linked\n";
}
else
{
	echo "component {$component}: plugin already linked\n";
}

$db->close();
