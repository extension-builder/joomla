<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    5th November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Restrict the native Demo site list to one HTTP-created regression record.
 *
 * Only the source dynamic-get definition changes. Normal compilation creates
 * the read-only site_looks API resource alongside the admin looks resource.
 * The fixed name makes empty results independent of shipped sample records.
 *
 * Usage: php seed-dynamic-list.php <disposable site root>
 *
 * @since  6.2.1
 */

$site = $argv[1] ?? '';

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| $site === '' || !is_file($site . '/configuration.php')
	|| !is_file($site . '/.jcb-api-test-site')
	|| trim((string) file_get_contents($site . '/.jcb-api-test-site')) !== realpath($site))
{
	fwrite(STDERR, "usage: php seed-dynamic-list.php <disposable site root>\n");
	exit(2);
}

require_once $site . '/configuration.php';
$config = new JConfig();

if ($config->sitename !== 'JCB API tests' || preg_match('/^[A-Za-z0-9_]+$/', $config->dbprefix) !== 1)
{
	throw new RuntimeException('Dynamic-list fixture requires the disposable JCB API tests site.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
[$host, $port] = str_contains($config->host, ':') ? explode(':', $config->host, 2) : [$config->host, 3306];
$db = new mysqli($host, $config->user, $config->password, $config->db, (int) $port);
$db->set_charset('utf8mb4');
$prefix = $config->dbprefix;
$component = '1c20aec5-bf1a-44e7-9deb-d1c920ca591d';
$view = '6a2d74e5-5bc6-497e-a075-70f084174eec';
$dynamicGet = 'bf0c72e1-f608-4d15-be16-572453fd0076';

try
{
	$db->begin_transaction();
	$stmt = $db->prepare(
		"SELECT addsite_views FROM `{$prefix}componentbuilder_component_site_views`"
		. ' WHERE joomla_component = ? AND published = 1'
	);
	$stmt->bind_param('s', $component);
	$stmt->execute();
	$links = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	$stmt->close();
	$views = count($links) === 1 ? json_decode($links[0]['addsite_views'], true, 512, JSON_THROW_ON_ERROR) : [];

	if (count(array_filter($views, static fn (array $entry): bool => ($entry['siteview'] ?? '') === $view)) !== 1)
	{
		throw new RuntimeException('The native Demo list must remain linked exactly once.');
	}

	$stmt = $db->prepare(
		"SELECT d.id, d.`where` FROM `{$prefix}componentbuilder_dynamic_get` AS d"
		. " INNER JOIN `{$prefix}componentbuilder_site_view` AS v ON v.main_get = d.guid"
		. " WHERE v.guid = ? AND v.codename = 'looks' AND v.published = 1"
		. ' AND d.guid = ? AND d.gettype = 2 AND d.published = 1 FOR UPDATE'
	);
	$stmt->bind_param('ss', $view, $dynamicGet);
	$stmt->execute();
	$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	$stmt->close();

	if (count($records) !== 1)
	{
		throw new RuntimeException('The native Demo Looks list dynamic get is missing or changed.');
	}

	$record = $records[0];
	$where = json_decode($record['where'], true, 512, JSON_THROW_ON_ERROR);
	$fixture = [
		'table_key' => 'a.name',
		'operator' => 1,
		'value_key' => "'JCB API dynamic list #1330.fixture'",
	];

	if (($where['where0']['table_key'] ?? '') !== 'a.published'
		|| (int) ($where['where0']['operator'] ?? 0) !== 1
		|| (string) ($where['where0']['value_key'] ?? '') !== '1'
		|| array_diff(array_keys($where), ['where0', 'where1330']) !== []
		|| (isset($where['where1330']) && $where['where1330'] !== $fixture))
	{
		throw new RuntimeException('Refusing to overwrite unexpected native dynamic-get conditions.');
	}

	$where['where1330'] = $fixture;
	$json = json_encode($where, JSON_THROW_ON_ERROR);
	$stmt = $db->prepare("UPDATE `{$prefix}componentbuilder_dynamic_get` SET `where` = ? WHERE id = ?");
	$stmt->bind_param('si', $json, $record['id']);
	$stmt->execute();
	$stmt->close();
	$db->commit();
	echo "Native Demo site list restricted to the dynamic-list HTTP fixture.\n";
}
catch (Throwable $error)
{
	$db->rollback();
	fwrite(STDERR, 'Dynamic-list fixture: ' . $error->getMessage() . "\n");
	exit(1);
}
finally
{
	$db->close();
}
