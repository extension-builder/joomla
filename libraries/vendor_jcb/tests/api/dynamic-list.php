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
 * Drive a compiled site-view list through empty, populated and empty results.
 *
 * The native dynamic get is seeded before compilation. Record creation and
 * cleanup use the installed admin API; every list read uses its separate,
 * generated read-only site resource and Joomla's real JSON:API rendering.
 *
 * Usage: php dynamic-list.php <disposable site root> <loopback URL> <token>
 *
 * @since  6.2.1
 */

[$site, $base, $token] = array_pad(array_slice($argv, 1), 3, '');
$url = parse_url($base);

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| in_array('', [$site, $base, $token], true)
	|| !is_file($site . '/configuration.php') || !is_file($site . '/.jcb-api-test-site')
	|| trim((string) file_get_contents($site . '/.jcb-api-test-site')) !== realpath($site)
	|| !is_array($url) || ($url['scheme'] ?? '') !== 'http'
	|| !in_array($url['host'] ?? '', ['127.0.0.1', 'localhost', '[::1]'], true)
	|| isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])
	|| !in_array($url['path'] ?? '', ['', '/'], true))
{
	fwrite(STDERR, "usage: php dynamic-list.php <disposable site root> <loopback URL> <token>\n");
	exit(2);
}

require_once $site . '/configuration.php';
$config = new JConfig();

if ($config->sitename !== 'JCB API tests' || rtrim($config->live_site, '/') !== rtrim($base, '/'))
{
	throw new RuntimeException('Dynamic-list acceptance requires the matching disposable JCB API tests site.');
}

$adminEndpoint = rtrim($base, '/') . '/api/index.php/v1/demo/looks';
$listEndpoint = rtrim($base, '/') . '/api/index.php/v1/demo/site_looks';
$passes = 0;
$failures = 0;
$id = 0;

/**
 * Send one native API request without redirecting or exposing its token.
 *
 * @param   string      $method  The HTTP method.
 * @param   string      $url     The endpoint.
 * @param   array|null  $body    Submitted fields, when present.
 *
 * @return  array{status:int,body:array|null,dataIsArray:bool}  The HTTP response.
 * @throws  RuntimeException  If the transport fails.
 * @since   6.2.1
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
		throw new RuntimeException('Dynamic-list request failed: ' . $error);
	}

	curl_close($curl);
	$document = json_decode($raw);

	return [
		'status' => $status,
		'body' => json_decode($raw, true),
		'dataIsArray' => is_object($document) && isset($document->data) && is_array($document->data),
	];
};

/**
 * Count an observed HTTP contract.
 *
 * @param   string  $label  The assertion.
 * @param   bool    $ok     Whether the contract held.
 *
 * @return  void
 * @since   6.2.1
 */
$check = static function (string $label, bool $ok) use (&$passes, &$failures): void
{
	$ok ? $passes++ : $failures++;
	printf("[%s] %s\n", $ok ? 'pass' : 'FAIL', $label);
};

/**
 * Require a normal empty document, including Joomla's pagination metadata.
 *
 * @param   string  $phase  The state transition being checked.
 *
 * @return  void
 * @since   6.2.1
 */
$emptyList = static function (string $phase) use ($request, $check, $listEndpoint): void
{
	$response = $request('GET', $listEndpoint);
	$check($phase . ': read-only list returns HTTP 200', $response['status'] === 200);
	$check($phase . ': data is an empty JSON array', $response['dataIsArray']
		&& ($response['body']['data'] ?? null) === []);
	$check($phase . ': the normal document retains links and pagination meta',
		!empty($response['body']['links']['self']) && isset($response['body']['meta']['total-pages']));
	$check($phase . ': no error document replaces the empty collection', !isset($response['body']['errors']));
};

try
{
	$emptyList('Before any matching row');
	$name = 'JCB API dynamic list #1330.fixture';
	$description = 'Dynamic-list attributes must survive the empty-result fallback.';
	$created = $request('POST', $adminEndpoint, [
		'name' => $name,
		'description' => $description,
		'published' => 1,
	]);
	$id = (int) ($created['body']['data']['id'] ?? 0);
	$check('admin API creates the matching published row', in_array($created['status'], [200, 201], true) && $id > 0);

	if ($id <= 0)
	{
		throw new RuntimeException('The admin API did not create the dynamic-list fixture.');
	}

	$populated = $request('GET', $listEndpoint);
	$rows = $populated['body']['data'] ?? [];
	$check('read-only list returns the matching row with HTTP 200', $populated['status'] === 200
		&& count($rows) === 1 && (int) ($rows[0]['id'] ?? 0) === $id);
	$check('populated lists retain their selected fields', ($rows[0]['attributes']['name'] ?? null) === $name
		&& ($rows[0]['attributes']['description'] ?? null) === $description);

	$trashed = $request('PATCH', $adminEndpoint . '/' . $id, ['published' => -2]);
	$check('admin API removes the row from the dynamic-get selection', $trashed['status'] === 200);
	$emptyList('After filtering out the matching row');
}
catch (Throwable $error)
{
	$check('dynamic-list scenario completes: ' . $error->getMessage(), false);
}
finally
{
	if ($id > 0)
	{
		try
		{
			$trashed = $request('PATCH', $adminEndpoint . '/' . $id, ['published' => -2]);
			$deleted = $request('DELETE', $adminEndpoint . '/' . $id);
			$gone = $request('GET', $adminEndpoint . '/' . $id);
			$check('temporary row is trashed and deleted through the API', $trashed['status'] === 200
				&& $deleted['status'] === 204 && $gone['status'] === 404);
		}
		catch (Throwable $error)
		{
			$check('dynamic-list cleanup completes: ' . $error->getMessage(), false);
		}
	}
}

printf("%d dynamic-list checks passed, %d failed\n", $passes, $failures);
exit($failures === 0 ? 0 : 1);
