<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    7th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Add encoded fields and a conditional requirement to the Demo source definition.
 *
 * The compiler, installer and HTTP API generate and exercise these fields through
 * their normal paths. No emitted controller, model, form or table is patched.
 * Clone the shipped textarea definition with fresh GUIDs and explicit raw,
 * optional XML, retaining its native field-type identity and database settings.
 *
 * Usage: php seed-partial-patch.php <site root>
 *
 * @since  6.2.0
 */

$site = $argv[1] ?? '';

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| $site === '' || !is_file($site . '/configuration.php')
	|| !is_file($site . '/.jcb-api-test-site')
	|| trim((string) file_get_contents($site . '/.jcb-api-test-site')) !== realpath($site))
{
	fwrite(STDERR, "usage: php seed-partial-patch.php <disposable site root>\n");
	exit(2);
}

require_once $site . '/configuration.php';
$config = new JConfig();

if ($config->sitename !== 'JCB API tests' || preg_match('/^[A-Za-z0-9_]+$/', $config->dbprefix) !== 1)
{
	throw new RuntimeException('Partial-PATCH fixtures require the disposable JCB API tests site.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
[$host, $port] = str_contains($config->host, ':') ? explode(':', $config->host, 2) : [$config->host, 3306];
$db = new mysqli($host, $config->user, $config->password, $config->db, (int) $port);
$db->set_charset('utf8mb4');
$prefix = $config->dbprefix;
$viewGuid = '93a34bf3-aa25-4a14-8496-ae7d0340e0b9';
$sourceGuid = '749a9917-90c3-49c4-9e72-aa33b0683a87';
$fixtures = [
	['patch_code', '3e4563e5-9361-4cf0-adb4-b64246cc2808', 2],
	['patch_json', '7420ed82-7108-4217-a0ca-03f75e46f8ae', 1],
	['validation_mode', 'dbb7ae5d-71c3-4131-bd6f-4cae8f1d7257', 0],
	['validation_details', 'c65dc692-25c0-45e4-b03c-d2d8c44313b6', 0],
];

try
{
	$db->begin_transaction();
	$stmt = $db->prepare("SELECT * FROM `{$prefix}componentbuilder_field` WHERE guid = ? AND published = 1");
	$stmt->bind_param('s', $sourceGuid);
	$stmt->execute();
	$source = $stmt->get_result()->fetch_assoc();
	$stmt->close();

	if (!$source || (int) $source['store'] !== 0 || $source['datatype'] !== 'TEXT')
	{
		throw new RuntimeException('The shipped description textarea must retain its plain TEXT storage definition.');
	}

	$listTypes = $db->query(
		"SELECT guid FROM `{$prefix}componentbuilder_fieldtype` WHERE LOWER(name) = 'list' AND published = 1"
	)->fetch_all(MYSQLI_ASSOC);

	if (count($listTypes) !== 1)
	{
		throw new RuntimeException('The conditional fixture requires one published native List field type.');
	}

	$stmt = $db->prepare(
		"SELECT id, addfields FROM `{$prefix}componentbuilder_admin_fields`"
		. ' WHERE admin_view = ? AND published = 1 FOR UPDATE'
	);
	$stmt->bind_param('s', $viewGuid);
	$stmt->execute();
	$links = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	$stmt->close();

	if (count($links) !== 1)
	{
		throw new RuntimeException('The shipped Look view must have exactly one native field link.');
	}

	$fields = json_decode($links[0]['addfields'], true, 512, JSON_THROW_ON_ERROR);

	if (!in_array($sourceGuid, array_column($fields, 'field'), true))
	{
		throw new RuntimeException('The shipped Look view must retain its native description field.');
	}

	foreach ($fixtures as [$name, $guid, $store])
	{
		$required = $name === 'validation_details' ? 'true' : 'false';
		$xml = '<field type="textarea" name="' . $name . '" label="' . $name
			. '" rows="5" cols="40" filter="RAW" required="' . $required . '" />';

		if ($name === 'validation_mode')
		{
			$xml = '<field type="list" name="validation_mode" label="validation_mode"'
				. ' filter="INT" default="0" required="false" option="0|Inactive,6|Active" />';
		}

		$encodedXml = json_encode($xml, JSON_THROW_ON_ERROR);
		$stmt = $db->prepare("SELECT guid, xml, store FROM `{$prefix}componentbuilder_field` WHERE guid = ?");
		$stmt->bind_param('s', $guid);
		$stmt->execute();
		$existing = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if ($existing && ($existing['xml'] !== $encodedXml || (int) $existing['store'] !== $store))
		{
			throw new RuntimeException('Refusing to reuse an unexpected partial-PATCH field definition.');
		}

		if (!$existing)
		{
			$record = $source;
			unset($record['id']);
			$record['guid'] = $guid;
			$record['asset_id'] = 0;
			$record['checked_out'] = null;
			$record['checked_out_time'] = null;
			$record['name'] = 'API partial PATCH fixture: ' . $name;
			$record['store'] = $store;
			$record['xml'] = $encodedXml;
			$record['version'] = 1;
			$record['created'] = gmdate('Y-m-d H:i:s');
			$record['modified'] = null;

			if ($name === 'validation_mode')
			{
				$record['fieldtype'] = $listTypes[0]['guid'];
				$record['datatype'] = 'INT';
				$record['datalenght'] = '11';
				$record['datalenght_other'] = '';
				$record['datadefault'] = '0';
				$record['datadefault_other'] = '';
			}

			$columns = array_keys($record);

			foreach ($columns as $column)
			{
				if (preg_match('/^[A-Za-z0-9_]+$/', $column) !== 1)
				{
					throw new RuntimeException('Unexpected native field column name.');
				}
			}

			$stmt = $db->prepare(
				"INSERT INTO `{$prefix}componentbuilder_field` (`" . implode('`, `', $columns) . '`)'
				. ' VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')'
			);
			$values = array_values($record);
			$stmt->bind_param(str_repeat('s', count($values)), ...$values);
			$stmt->execute();
			$stmt->close();
		}

		if (!in_array($guid, array_column($fields, 'field'), true))
		{
			$fields[] = [
				'field' => $guid,
				'list' => '1',
				'order_list' => (string) (count($fields) + 1),
				'filter' => '',
				'tab' => '1',
				'alignment' => 3,
				'order_edit' => (string) (count($fields) + 1),
			];
		}
	}

	$json = json_encode($fields, JSON_THROW_ON_ERROR);
	$id = (int) $links[0]['id'];
	$stmt = $db->prepare("UPDATE `{$prefix}componentbuilder_admin_fields` SET addfields = ? WHERE id = ?");
	$stmt->bind_param('si', $json, $id);
	$stmt->execute();
	$stmt->close();

	// Link the native definitions so the compiler emits both browser and server rules.
	$condition = [
		'target_field' => ['c65dc692-25c0-45e4-b03c-d2d8c44313b6'],
		'target_behavior' => 1,
		'target_relation' => 0,
		'match_field' => 'dbb7ae5d-71c3-4131-bd6f-4cae8f1d7257',
		'match_behavior' => 1,
		'match_options' => '6',
	];
	$stmt = $db->prepare(
		"SELECT id, addconditions FROM `{$prefix}componentbuilder_admin_fields_conditions`"
		. ' WHERE admin_view = ? FOR UPDATE'
	);
	$stmt->bind_param('s', $viewGuid);
	$stmt->execute();
	$conditionRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
	$stmt->close();

	if (count($conditionRows) > 1)
	{
		throw new RuntimeException('The disposable Look view has ambiguous conditional definitions.');
	}

	$conditions = $conditionRows === [] || !$conditionRows[0]['addconditions']
		? [] : json_decode($conditionRows[0]['addconditions'], true, 512, JSON_THROW_ON_ERROR);
	if (!in_array($condition, $conditions, true))
	{
		$conditions[] = $condition;
	}
	$json = json_encode($conditions, JSON_THROW_ON_ERROR);

	if ($conditionRows === [])
	{
		$stmt = $db->prepare(
			"INSERT INTO `{$prefix}componentbuilder_admin_fields_conditions` (admin_view, addconditions, published) VALUES (?, ?, 1)"
		);
		$stmt->bind_param('ss', $viewGuid, $json);
	}
	else
	{
		$id = (int) $conditionRows[0]['id'];
		$stmt = $db->prepare(
			"UPDATE `{$prefix}componentbuilder_admin_fields_conditions` SET addconditions = ?, published = 1 WHERE id = ?"
		);
		$stmt->bind_param('si', $json, $id);
	}

	$stmt->execute();
	$stmt->close();
	$db->commit();
	echo "Demo Look includes encoded fields and validation_details required only when validation_mode is 6.\n";
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
