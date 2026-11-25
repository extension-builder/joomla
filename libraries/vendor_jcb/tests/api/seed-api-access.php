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

$site = realpath($argv[1] ?? '');
if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1' || !$site
	|| !is_file($site . '/.jcb-api-test-site')
	|| trim(file_get_contents($site . '/.jcb-api-test-site')) !== $site)
{
	throw new RuntimeException('API access fixtures require the disposable compiler site.');
}

require $site . '/configuration.php';
$config = new JConfig();
if ($config->sitename !== 'JCB API tests' || !preg_match('/^\w+$/D', $config->dbprefix))
{
	throw new RuntimeException('Unexpected compiler site configuration.');
}

[$host, $port] = array_pad(explode(':', $config->host, 2), 2, 3306);
$db = new mysqli($host, $config->user, $config->password, $config->db, (int) $port);
$prefix = $config->dbprefix;
$demo = '1c20aec5-bf1a-44e7-9deb-d1c920ca591d';
$db->begin_transaction();

try
{
	$links = $db->query("SELECT id, addcustom_admin_views FROM `{$prefix}componentbuilder_component_custom_admin_views`
		WHERE joomla_component = '{$demo}' AND published = 1 FOR UPDATE")->fetch_assoc();
	if (!$links)
	{
		throw new RuntimeException('Demo is missing its Custom Admin links.');
	}
	$views = json_decode($links['addcustom_admin_views'], true, 512, JSON_THROW_ON_ERROR);
	$insert = $db->prepare("INSERT INTO `{$prefix}componentbuilder_custom_admin_view`
		(guid, name, system_name, codename, main_get, `default`, published, created)
		VALUES (?, ?, ?, ?, ?, '', 1, NOW())");

	foreach ([
		['1c236fab-67ee-45f4-b547-b24d70035e9e', 'qareport', 'bf0c72e1-f608-4d15-be16-572453fd0076'],
		['13cbf976-5234-47f2-a42e-38c57765dd72', 'qadetail', '9e74033f-f49e-486f-9879-779fa3bd2417'],
	] as [$guid, $code, $mainGet])
	{
		$insert->bind_param('sssss', $guid, $code, $code, $code, $mainGet);
		$insert->execute();
		$views[$code] = ['customadminview' => $guid, 'access' => '1', 'icomoon' => 'list'];
	}

	$json = json_encode($views, JSON_THROW_ON_ERROR);
	$update = $db->prepare("UPDATE `{$prefix}componentbuilder_component_custom_admin_views` SET addcustom_admin_views = ? WHERE id = ?");
	$update->bind_param('si', $json, $links['id']);
	$update->execute();

	// Allow the anonymous Looks list. Keep the Looking item private with its view ACL.
	$siteLinks = $db->query("SELECT id, addsite_views FROM `{$prefix}componentbuilder_component_site_views`
		WHERE joomla_component = '{$demo}' AND published = 1 FOR UPDATE")->fetch_assoc();
	$siteViews = json_decode($siteLinks['addsite_views'], true, 512, JSON_THROW_ON_ERROR);
	foreach ($siteViews as &$view)
	{
		if ($view['siteview'] === '6a2d74e5-5bc6-497e-a075-70f084174eec')
		{
			$view['access'] = '0';
			$view['public_access'] = '1';
		}
		elseif ($view['siteview'] === 'b302faa4-7476-4b1f-bcce-0b9b3f2a8be1')
		{
			$view['access'] = '1';
			$view['public_access'] = '0';
		}
	}
	unset($view);
	$json = json_encode($siteViews, JSON_THROW_ON_ERROR);
	$update = $db->prepare("UPDATE `{$prefix}componentbuilder_component_site_views` SET addsite_views = ? WHERE id = ?");
	$update->bind_param('si', $json, $siteLinks['id']);
	$update->execute();
	$db->commit();
}
catch (Throwable $e)
{
	$db->rollback();
	throw $e;
}
