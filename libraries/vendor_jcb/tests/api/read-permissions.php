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
 * Exercise native read/edit permissions on the installed compiled JSON:API.
 *
 * usage: php read-permissions.php <base url> <admin token> <reader token> <denied token> <resource>
 *
 * The reader may view but never edit. The denied user authenticates through
 * the same native token plugin but may not access the component's resource.
 * All temporary records are removed by the administrator through the API.
 */

[$base, $adminToken, $readerToken, $deniedToken, $resource] = array_pad(array_slice($argv, 1), 5, '');

if (in_array('', [$base, $adminToken, $readerToken, $deniedToken, $resource], true))
{
	fwrite(STDERR, "usage: php read-permissions.php <base url> <admin token> <reader token> <denied token> <resource>\n");
	exit(2);
}

$url = parse_url($base);

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| !is_array($url) || ($url['scheme'] ?? '') !== 'http'
	|| !in_array($url['host'] ?? '', ['127.0.0.1', 'localhost', '[::1]'], true)
	|| isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])
	|| !in_array($url['path'] ?? '', ['', '/'], true)
	|| !in_array($resource, ['v1/demo/looks', 'v1/demo/libraries_config'], true))
{
	throw new RuntimeException('Permission acceptance requires an explicit disposable loopback Demo API.');
}

$endpoint = rtrim($base, '/') . '/api/index.php/' . trim($resource, '/');
$passes = 0;
$failures = 0;
$id = 0;

/**
 * Send a native API request without following administrator redirects.
 *
 * @param   string      $method  The HTTP method.
 * @param   string      $url     The endpoint.
 * @param   string      $token   The caller's API token.
 * @param   array|null  $body    The body, if any.
 *
 * @return  array{status:int,body:array|null,raw:string}  The HTTP response.
 * @since   6.1.7
 */
$request = static function (string $method, string $url, string $token, ?array $body = null): array
{
	$curl = curl_init($url);
	$headers = ['Accept: application/vnd.api+json', 'X-Joomla-Token: ' . $token];

	if ($body !== null)
	{
		$headers[] = 'Content-Type: application/json';
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
	}

	curl_setopt_array($curl, [
		CURLOPT_CUSTOMREQUEST => $method,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_TIMEOUT => 60,
	]);
	$raw = curl_exec($curl);
	$status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

	if ($raw === false)
	{
		$error = curl_error($curl);
		curl_close($curl);
		throw new RuntimeException('API fixture request failed: ' . $error);
	}

	curl_close($curl);

	return ['status' => $status, 'body' => json_decode($raw, true), 'raw' => $raw];
};

/**
 * Record a behavioral assertion without logging credentials.
 *
 * @param   string  $label  The assertion.
 * @param   bool    $ok     Whether it passed.
 * @param   int     $code   The observed HTTP status.
 *
 * @return  void
 * @since   6.1.7
 */
$check = static function (string $label, bool $ok, int $code = 0) use (&$passes, &$failures): void
{
	$ok ? $passes++ : $failures++;
	printf("[%s] %s (HTTP %d)\n", $ok ? 'pass' : 'FAIL', $label, $code);
};

try
{
	$created = $request('POST', $endpoint, $adminToken, [
		'name' => 'JCB API Read Permission ' . bin2hex(random_bytes(6)),
		'description' => 'Read-only permission regression record.',
		'access' => 1,
	]);
	$id = (int) ($created['body']['data']['id'] ?? 0);
	$guid = (string) ($created['body']['data']['attributes']['guid'] ?? '');
	$check('administrator creates a disposable item', in_array($created['status'], [200, 201], true) && $id > 0 && $guid !== '', $created['status']);

	if ($id <= 0 || $guid === '')
	{
		throw new RuntimeException('Cannot exercise API read permissions without a created fixture record.');
	}

	foreach ([$endpoint . '/' . $id, $endpoint . '/guid/' . $guid] as $url)
	{
		$read = $request('GET', $url, $readerToken);
		$check('read-only user retrieves the same item by numeric id or GUID', $read['status'] === 200 && (int) ($read['body']['data']['id'] ?? 0) === $id && ($read['body']['data']['attributes']['guid'] ?? '') === $guid, $read['status']);
		$denied = $request('GET', $url, $deniedToken);
		$check('denied user receives JSON:API 403 by numeric id or GUID', $denied['status'] === 403 && isset($denied['body']['errors'][0]), $denied['status']);
	}

	$edited = $request('PATCH', $endpoint . '/' . $id, $readerToken, ['description' => 'A reader must not write this.']);
	$check('read permission does not grant edit permission', $edited['status'] === 403 && isset($edited['body']['errors'][0]), $edited['status']);
	$unchanged = $request('GET', $endpoint . '/' . $id, $adminToken);
	$check('rejected edit leaves the record unchanged', $unchanged['status'] === 200 && ($unchanged['body']['data']['attributes']['description'] ?? '') === 'Read-only permission regression record.', $unchanged['status']);

	$restricted = $request('PATCH', $endpoint . '/' . $id, $adminToken, ['access' => 2]);
	$check('administrator places the item in a restricted native view level', $restricted['status'] === 200 && (int) ($restricted['body']['data']['attributes']['access'] ?? 0) === 2, $restricted['status']);
	$hidden = $request('GET', $endpoint . '/' . $id, $readerToken);
	$check('read action alone does not bypass native view-level access', $hidden['status'] === 403 && isset($hidden['body']['errors'][0]), $hidden['status']);
}
finally
{
	if ($id > 0)
	{
		$trashed = $request('PATCH', $endpoint . '/' . $id, $adminToken, ['published' => -2]);
		$check('administrator trashes the temporary fixture', $trashed['status'] === 200, $trashed['status']);
		$deleted = $request('DELETE', $endpoint . '/' . $id, $adminToken);
		$check('administrator removes the temporary fixture', $deleted['status'] === 204, $deleted['status']);
	}
}

printf("%d permission checks passed, %d failed\n", $passes, $failures);
exit($failures === 0 ? 0 : 1);
