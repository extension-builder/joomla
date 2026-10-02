<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    2nd October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Rename the shipped Demo J6 API view for a second disposable compilation.
 *
 * The singular "Library Config" and plural "Libraries Config" distinguish
 * item and list models even when Joomla's English inflector leaves the
 * list name unchanged. Only native JCB definitions are updated. The existing
 * view GUID, component link, field GUIDs, custom code and access settings are
 * retained; the compiler generates and installs the resulting API normally.
 *
 * Run after the original v1/demo/looks HTTP acceptance has passed, then
 * compile/install the same component again and drive v1/demo/libraries_config.
 * This script accepts only the disposable site created by api-tests/run.sh.
 *
 * Usage: php seed-model-role.php <site root> [<component guid>]
 *
 * @since  6.2.0
 */

$site = $argv[1] ?? '';
$component = $argv[2] ?? '1c20aec5-bf1a-44e7-9deb-d1c920ca591d';
$viewGuid = '93a34bf3-aa25-4a14-8496-ae7d0340e0b9';

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| $site === '' || !is_file($site . '/configuration.php')
	|| !is_file($site . '/.jcb-api-test-site')
	|| trim((string) file_get_contents($site . '/.jcb-api-test-site')) !== realpath($site)
	|| preg_match('/^[a-f\d]{8}(?:-[a-f\d]{4}){3}-[a-f\d]{12}$/i', $component) !== 1)
{
	fwrite(STDERR, "usage: php seed-model-role.php <site root> [<component guid>]\n");
	exit(2);
}

require_once $site . '/configuration.php';

$config = new JConfig();

if ($config->sitename !== 'JCB API tests'
	|| preg_match('/^[a-zA-Z0-9_]+$/', $config->dbprefix) !== 1)
{
	fwrite(STDERR, "The model-role fixture requires the disposable JCB API tests site.\n");
	exit(2);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = null;

try
{
	[$host, $port] = str_contains($config->host, ':')
		? explode(':', $config->host, 2)
		: [$config->host, 3306];
	$db = new mysqli($host, $config->user, $config->password, $config->db, (int) $port);
	$db->set_charset('utf8mb4');
	$db->begin_transaction();
	$prefix = $config->dbprefix;

	$stmt = $db->prepare(
		"SELECT id, addadmin_views FROM `{$prefix}componentbuilder_component_admin_views`"
		. ' WHERE joomla_component = ? AND published = 1 FOR UPDATE'
	);
	$stmt->bind_param('s', $component);
	$stmt->execute();
	$links = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	$stmt->close();

	if (count($links) !== 1)
	{
		throw new RuntimeException('The shipped Demo component must have exactly one native admin-view link.');
	}

	$link = $links[0];
	$views = json_decode($link['addadmin_views'], true, 512, JSON_THROW_ON_ERROR);
	$matching = array_filter(
		$views,
		static fn (array $view): bool => ($view['adminview'] ?? '') === $viewGuid
	);

	if (count($matching) !== 1)
	{
		throw new RuntimeException('The shipped Demo Look GUID must remain linked exactly once.');
	}

	$apiView = reset($matching);

	if ((int) ($apiView['add_api'] ?? 0) !== 2 || (int) ($apiView['access'] ?? 0) !== 1)
	{
		throw new RuntimeException('The source Look view must retain its read/write API and native access setting.');
	}

	$stmt = $db->prepare(
		"SELECT id, name_single, name_list, type FROM `{$prefix}componentbuilder_admin_view`"
		. ' WHERE guid = ? AND published = 1 FOR UPDATE'
	);
	$stmt->bind_param('s', $viewGuid);
	$stmt->execute();
	$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	$stmt->close();

	if (count($records) !== 1 || (int) $records[0]['type'] !== 1)
	{
		throw new RuntimeException('The shipped Look definition must be one regular admin view.');
	}

	$view = $records[0];
	$originalNames = $view['name_single'] === 'Look' && $view['name_list'] === 'Looks';
	$fixtureNames = $view['name_single'] === 'Library Config' && $view['name_list'] === 'Libraries Config';

	if (!$originalNames && !$fixtureNames)
	{
		throw new RuntimeException('Refusing to rename a Demo view whose native names are unexpected.');
	}

	$stmt = $db->prepare(
		"SELECT addfields FROM `{$prefix}componentbuilder_admin_fields`"
		. ' WHERE admin_view = ? AND published = 1'
	);
	$stmt->bind_param('s', $viewGuid);
	$stmt->execute();
	$fieldLinks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	$stmt->close();

	if (count($fieldLinks) !== 1)
	{
		throw new RuntimeException('The source Look view must have one native field link.');
	}

	$fields = json_decode($fieldLinks[0]['addfields'], true, 512, JSON_THROW_ON_ERROR);
	$fieldGuids = array_column($fields, 'field');
	$requiredFields = [
		'5d3d34dd-4876-4c6a-86ab-b4e162f22c08', // Native name field.
		'749a9917-90c3-49c4-9e72-aa33b0683a87', // Native description field.
		'335866ce-b81b-4329-901d-c20254135c9c', // Native alias field.
		'5aa57bbe-7b19-4db9-915c-561863458d2b', // Native GUID field.
	];

	if (array_diff($requiredFields, $fieldGuids) !== [])
	{
		throw new RuntimeException('The model-role fixture must reuse the native Demo name, description, alias and GUID fields.');
	}

	if ($originalNames)
	{
		$single = 'Library Config';
		$list = 'Libraries Config';
		$now = gmdate('Y-m-d H:i:s');
		$viewId = (int) $view['id'];
		$stmt = $db->prepare(
			"UPDATE `{$prefix}componentbuilder_admin_view`"
			. ' SET name_single = ?, name_list = ?, modified = ?, version = version + 1 WHERE id = ?'
		);
		$stmt->bind_param('sssi', $single, $list, $now, $viewId);
		$stmt->execute();
		$stmt->close();
	}

	$db->commit();
	printf(
		"Demo view %s: Library Config / Libraries Config; %d native field links retained; API v1/demo/libraries_config.\n",
		$viewGuid,
		count($fieldGuids)
	);
}
catch (Throwable $error)
{
	if ($db instanceof mysqli)
	{
		$db->rollback();
	}

	fwrite(STDERR, 'Model-role fixture: ' . $error->getMessage() . "\n");
	exit(1);
}
finally
{
	if ($db instanceof mysqli)
	{
		$db->close();
	}
}
