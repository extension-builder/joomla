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
 * Verify omitted-field preservation through the compiled and installed Demo API.
 *
 * Reads raw storage only to prove unchanged bytes; every record write and cleanup
 * goes through HTTP. Source fields are seeded before compilation, so this runs
 * the generated controller, native validation, model transforms and table save.
 *
 * Usage: php partial-patch.php <site root> <base URL> <admin token> <resource>
 *
 * @since  6.2.0
 */

[$site, $base, $token, $resource] = array_pad(array_slice($argv, 1), 4, '');
$url = parse_url($base);
$tables = ['v1/demo/looks' => 'look', 'v1/demo/libraries_config' => 'library_config'];

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| in_array('', [$site, $base, $token, $resource], true)
	|| !is_file($site . '/configuration.php') || !is_file($site . '/.jcb-api-test-site')
	|| trim((string) file_get_contents($site . '/.jcb-api-test-site')) !== realpath($site)
	|| !is_array($url) || ($url['scheme'] ?? '') !== 'http'
	|| !in_array($url['host'] ?? '', ['127.0.0.1', 'localhost', '[::1]'], true)
	|| isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])
	|| !in_array($url['path'] ?? '', ['', '/'], true) || !isset($tables[$resource]))
{
	fwrite(STDERR, "usage: php partial-patch.php <disposable site root> <loopback URL> <token> <Demo resource>\n");
	exit(2);
}

require_once $site . '/configuration.php';
$config = new JConfig();

if ($config->sitename !== 'JCB API tests' || preg_match('/^[A-Za-z0-9_]+$/', $config->dbprefix) !== 1
	|| rtrim($config->live_site, '/') !== rtrim($base, '/'))
{
	throw new RuntimeException('Partial-PATCH acceptance requires the matching disposable JCB API tests site.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
[$host, $port] = str_contains($config->host, ':') ? explode(':', $config->host, 2) : [$config->host, 3306];
$db = new mysqli($host, $config->user, $config->password, $config->db, (int) $port);
$db->set_charset('utf8mb4');
$table = $config->dbprefix . 'demo_' . $tables[$resource];
$endpoint = rtrim($base, '/') . '/api/index.php/' . $resource;
$passes = 0;
$failures = 0;
$id = 0;

/**
 * Send one native API request without following redirects or logging its token.
 *
 * @param   string      $method  The HTTP method.
 * @param   string      $url     The endpoint.
 * @param   array|null  $body    Submitted fields, when present.
 *
 * @return  array{status:int,body:array|null}  The HTTP response.
 * @since   6.2.0
 */
$request = static function (string $method, string $url, ?array $body = null) use ($token): array
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
		throw new RuntimeException('Partial-PATCH request failed: ' . $error);
	}

	curl_close($curl);

	return ['status' => $status, 'body' => json_decode($raw, true)];
};

/**
 * Count an observed contract without exposing credentials or source content.
 *
 * @param   string  $label  The assertion.
 * @param   bool    $ok     Whether the contract held.
 *
 * @return  void
 * @since   6.2.0
 */
$check = static function (string $label, bool $ok) use (&$passes, &$failures): void
{
	$ok ? $passes++ : $failures++;
	printf("[%s] %s\n", $ok ? 'pass' : 'FAIL', $label);
};

/**
 * Read persisted values independently of model decoding and API serialization.
 *
 * @param   int  $id  The temporary record identity.
 *
 * @return  array  Exact stored values for fields the test protects.
 * @since   6.2.0
 */
$stored = static function (int $id) use ($db, $table): array
{
	$stmt = $db->prepare(
		"SELECT guid, description, patch_code, patch_json, created, created_by FROM `{$table}` WHERE id = ?"
	);
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$record = $stmt->get_result()->fetch_assoc();
	$stmt->close();

	if (!$record)
	{
		throw new RuntimeException('The temporary partial-PATCH record is missing from storage.');
	}

	return $record;
};

/**
 * Assert decoded reads and unchanged storage after an omitted-field update.
 *
 * @param   string  $url       The numeric or GUID route.
 * @param   array   $baseline  The expected raw stored values.
 * @param   string  $code      Expected decoded code.
 * @param   string  $json      Expected decoded JSON-stored text.
 * @param   string  $name      The explicitly updated name.
 *
 * @return  void
 * @since   6.2.0
 */
$preserved = static function (string $url, array $baseline, string $code, string $json, string $name)
	use ($request, $check, $stored, &$id): void
{
	$changed = $request('PATCH', $url, ['name' => $name]);
	$check('name-only PATCH succeeds', $changed['status'] === 200);
	$check('name-only PATCH preserves raw code, JSON, description, GUID and created metadata', $stored($id) === $baseline);
	$read = $request('GET', $url);
	$attributes = $read['body']['data']['attributes'] ?? [];
	$check('readback confirms the explicitly changed name', $read['status'] === 200 && ($attributes['name'] ?? null) === $name);
	$check('readback retains decoded omitted code and JSON', ($attributes['patch_code'] ?? null) === $code && ($attributes['patch_json'] ?? null) === $json);
};

try
{
	$name = 'JCB API Partial PATCH ' . bin2hex(random_bytes(6));
	$code = "return 'preserve bytes';\n// second line";
	$json = 'Keep "quoted" JSON text, backslash \\ and UTF-8: Namibia.';
	$created = $request('POST', $endpoint, [
		'name' => $name,
		'description' => 'Unrelated description must survive every partial update.',
		'patch_code' => $code,
		'patch_json' => $json,
	]);
	$id = (int) ($created['body']['data']['id'] ?? 0);
	$guid = (string) ($created['body']['data']['attributes']['guid'] ?? '');
	$check('POST creates a record through the compiled API', in_array($created['status'], [200, 201], true) && $id > 0 && $guid !== '');

	if ($id <= 0 || $guid === '')
	{
		throw new RuntimeException('The compiled API did not return a valid partial-PATCH fixture identity.');
	}

	$baseline = $stored($id);
	$check('the compiled save stores code with exactly one Base64 layer', $baseline['patch_code'] === base64_encode($code));
	$check('the compiled save stores text with exactly one JSON layer', $baseline['patch_json'] === json_encode($json, JSON_THROW_ON_ERROR));
	$idUrl = $endpoint . '/' . $id;
	$guidUrl = $endpoint . '/guid/' . $guid;

	foreach ([$idUrl, $guidUrl] as $route => $itemUrl)
	{
		for ($repeat = 1; $repeat <= 2; $repeat++)
		{
			$preserved($itemUrl, $baseline, $code, $json, $name . ' route ' . $route . ' repeat ' . $repeat);
		}
	}

	// A submitted value that happens to be valid Base64 is still plaintext input.
	$code = 'YQ==';
	$changed = $request('PATCH', $idUrl, ['patch_code' => $code]);
	$baseline['patch_code'] = base64_encode($code);
	$check('explicit Base64-looking code is encoded as plaintext exactly once', $changed['status'] === 200 && $stored($id) === $baseline);
	$preserved($guidUrl, $baseline, $code, $json, $name . ' replaced code');

	$json = 'Replacement "JSON" text';
	$changed = $request('PATCH', $guidUrl, ['patch_json' => $json]);
	$baseline['patch_json'] = json_encode($json, JSON_THROW_ON_ERROR);
	$check('explicit JSON text replacement leaves the code and other fields unchanged', $changed['status'] === 200 && $stored($id) === $baseline);
	$preserved($idUrl, $baseline, $code, $json, $name . ' replaced JSON');

	$code = '';
	$changed = $request('PATCH', $idUrl, ['patch_code' => $code]);
	$baseline['patch_code'] = '';
	$check('explicit empty code clears only that field', $changed['status'] === 200 && $stored($id) === $baseline);
	$preserved($guidUrl, $baseline, $code, $json, $name . ' cleared code');

	$json = '';
	$changed = $request('PATCH', $guidUrl, ['patch_json' => $json]);
	$baseline['patch_json'] = json_encode($json, JSON_THROW_ON_ERROR);
	$check('explicit empty JSON text clears only that field', $changed['status'] === 200 && $stored($id) === $baseline);
	$preserved($idUrl, $baseline, $code, $json, $name . ' cleared JSON');
}
catch (Throwable $error)
{
	$check('partial-PATCH scenario completes: ' . $error->getMessage(), false);
}
finally
{
	if ($id > 0)
	{
		try
		{
			$trashed = $request('PATCH', $endpoint . '/' . $id, ['published' => -2]);
			$deleted = $request('DELETE', $endpoint . '/' . $id);
			$gone = $request('GET', $endpoint . '/' . $id);
			$check('temporary fixture is trashed and deleted through the API', $trashed['status'] === 200 && $deleted['status'] === 204 && $gone['status'] === 404);
		}
		catch (Throwable $error)
		{
			$check('fixture cleanup completes: ' . $error->getMessage(), false);
		}
	}

	$db->close();
}

printf("%d partial-PATCH checks passed, %d failed\n", $passes, $failures);
exit($failures === 0 ? 0 : 1);
