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
 * Seed native Joomla read-only and denied API groups on the disposable site.
 *
 * usage: php seed-read-roles.php <site root>
 *
 * Only the harness site named "JCB API tests" is accepted. Existing native ACL
 * rules and token groups are preserved; new group grants exercise the actual
 * authentication plugin and the generated component's mapped access actions.
 */

$site = $argv[1] ?? '';

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| $site === '' || !is_file($site . '/configuration.php')
	|| !is_file($site . '/.jcb-api-test-site')
	|| trim((string) file_get_contents($site . '/.jcb-api-test-site')) !== realpath($site))
{
	fwrite(STDERR, "usage: php seed-read-roles.php <site root>\n");
	exit(2);
}

require_once $site . '/configuration.php';
$config = new JConfig();

if ($config->sitename !== 'JCB API tests' || preg_match('/^[A-Za-z0-9_]+$/', $config->dbprefix) !== 1)
{
	throw new RuntimeException('Read-role fixtures require the disposable JCB API tests site.');
}

[$host, $port] = str_contains($config->host, ':') ? explode(':', $config->host, 2) : [$config->host, 3306];
$db = new mysqli($host, $config->user, $config->password, $config->db, (int) $port);
$db->set_charset('utf8mb4');
$prefix = $config->dbprefix;

/**
 * Insert a native nested-set child of Public, or use the existing test group.
 *
 * @param   string  $title  The test group's title.
 *
 * @return  int  The group identity.
 * @since   6.1.7
 */
$group = static function (string $title) use ($db, $prefix): int
{
	$stmt = $db->prepare("SELECT id FROM `{$prefix}usergroups` WHERE title = ?");
	$stmt->bind_param('s', $title);
	$stmt->execute();
	$existing = $stmt->get_result()->fetch_assoc();
	$stmt->close();

	if ($existing)
	{
		return (int) $existing['id'];
	}

	$parent = $db->query("SELECT id, rgt FROM `{$prefix}usergroups` WHERE parent_id = 0 FOR UPDATE")->fetch_assoc();

	if (!$parent)
	{
		throw new RuntimeException('The native Public user group is missing.');
	}

	$left = (int) $parent['rgt'];
	$right = $left + 1;
	$parentId = (int) $parent['id'];
	$db->query("UPDATE `{$prefix}usergroups` SET rgt = rgt + 2 WHERE rgt >= {$left}");
	$db->query("UPDATE `{$prefix}usergroups` SET lft = lft + 2 WHERE lft > {$left}");
	$stmt = $db->prepare("INSERT INTO `{$prefix}usergroups` (parent_id, lft, rgt, title) VALUES (?, ?, ?, ?)");
	$stmt->bind_param('iiis', $parentId, $left, $right, $title);
	$stmt->execute();
	$id = (int) $db->insert_id;
	$stmt->close();

	return $id;
};

/**
 * Merge fixture grants into an existing native ACL asset.
 *
 * @param   string  $name    The native asset name.
 * @param   array   $grants  The action/group grant map.
 *
 * @return  void
 * @since   6.1.7
 */
$rules = static function (string $name, array $grants) use ($db, $prefix): void
{
	$stmt = $db->prepare("SELECT id, rules FROM `{$prefix}assets` WHERE name = ? FOR UPDATE");
	$stmt->bind_param('s', $name);
	$stmt->execute();
	$asset = $stmt->get_result()->fetch_assoc();
	$stmt->close();

	if (!$asset)
	{
		throw new RuntimeException('Missing native ACL fixture asset: ' . $name);
	}

	$existing = json_decode((string) $asset['rules'], true, 512, JSON_THROW_ON_ERROR);

	foreach ($grants as $action => $values)
	{
		$existing[$action] = array_replace($existing[$action] ?? [], $values);
	}

	$json = json_encode($existing, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
	$stmt = $db->prepare("UPDATE `{$prefix}assets` SET rules = ? WHERE id = ?");
	$stmt->bind_param('si', $json, $asset['id']);
	$stmt->execute();
	$stmt->close();
};

$db->begin_transaction();

try
{
	$reader = $group('JCB API Readonly');
	$denied = $group('JCB API Denied');
	$rules('root.1', ['core.login.api' => [$reader => 1, $denied => 1]]);
	$grants = [];

	foreach (['look', 'library_config'] as $view)
	{
		$grants[$view . '.access'] = [$reader => 1, $denied => 0];

		foreach (['create', 'edit', 'edit.own', 'edit.state', 'delete'] as $action)
		{
			$grants[$view . '.' . $action] = [$reader => 0, $denied => 0];
		}
	}

	foreach (['core.admin', 'core.options', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'] as $action)
	{
		$grants[$action] = [$reader => 0, $denied => 0];
	}

	$rules('com_demo', $grants);
	$plugin = $db->query("SELECT extension_id, params FROM `{$prefix}extensions` WHERE type = 'plugin' AND folder = 'user' AND element = 'token'")->fetch_assoc();

	if (!$plugin)
	{
		throw new RuntimeException('The native user token plugin is missing.');
	}

	$params = json_decode((string) $plugin['params'], true, 512, JSON_THROW_ON_ERROR);
	$params['allowedUserGroups'] = array_values(array_unique(array_merge($params['allowedUserGroups'] ?? [8], [$reader, $denied])));
	$json = json_encode($params, JSON_THROW_ON_ERROR);
	$stmt = $db->prepare("UPDATE `{$prefix}extensions` SET params = ?, enabled = 1 WHERE extension_id = ?");
	$stmt->bind_param('si', $json, $plugin['extension_id']);
	$stmt->execute();
	$stmt->close();
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

echo "Native API readonly and denied role fixtures ready.\n";
