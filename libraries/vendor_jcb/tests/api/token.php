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
 * Mint an API token for a user of an installed site.
 *
 * Joomla ships no console command for API tokens, so this writes the two
 * profile rows the token authentication plugin reads and prints the token
 * the way the user's profile page would: base64("sha256:<user id>:<hmac>"),
 * the hmac being the token seed signed with the site secret.
 *
 * usage: php token.php <site root> <username>
 */

$site = $argv[1] ?? '';
$username = $argv[2] ?? '';

if ($site === '' || $username === '' || !is_file($site . '/configuration.php'))
{
	fwrite(STDERR, "usage: php token.php <site root> <username>\n");
	exit(2);
}

require_once $site . '/configuration.php';

$config = new JConfig();
$host = $config->host;
$port = 3306;

if (str_contains($host, ':'))
{
	[$host, $port] = explode(':', $host, 2);
	$port = (int) $port;
}

$db = @new mysqli($host, $config->user, $config->password, $config->db, $port);

if ($db->connect_error)
{
	fwrite(STDERR, "database: {$db->connect_error}\n");
	exit(1);
}

$db->set_charset('utf8mb4');
$prefix = $config->dbprefix;

$stmt = $db->prepare("SELECT id FROM `{$prefix}users` WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user)
{
	fwrite(STDERR, "no user named {$username}\n");
	exit(1);
}

$userId = (int) $user['id'];
$seed = random_bytes(32);
$stored = base64_encode($seed);

$db->query("DELETE FROM `{$prefix}user_profiles` WHERE user_id = {$userId} AND profile_key IN ('joomlatoken.token', 'joomlatoken.enabled')");

$stmt = $db->prepare("INSERT INTO `{$prefix}user_profiles` (user_id, profile_key, profile_value, ordering) VALUES (?, 'joomlatoken.token', ?, 1), (?, 'joomlatoken.enabled', '1', 2)");
$stmt->bind_param('isi', $userId, $stored, $userId);
$stmt->execute();
$stmt->close();
$db->close();

echo base64_encode('sha256:' . $userId . ':' . hash_hmac('sha256', $seed, $config->secret)), PHP_EOL;
