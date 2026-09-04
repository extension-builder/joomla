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
 * Drive the JSON:API of a compiled JCB component through a real HTTP round
 * trip and check the create, read, update and delete contract of a resource
 * whose table carries a guid.
 *
 * usage: php scenarios.php <base url> <token> [<resource path>] [--reproduce] [--hammer=<n>]
 *
 *   base url       where the site answers, e.g. http://127.0.0.1:8090
 *   token          an API token of a user allowed to manage the component
 *   resource path  the list resource, default v1/demo/looks
 *   --reproduce    expect the create to fail the way it did before the fix,
 *                  which proves the harness can see the defect at all
 *   --hammer=<n>   create n records concurrently and require n distinct guids
 */

$args = array_values(array_filter(array_slice($argv, 1), static fn ($a) => !str_starts_with($a, '--')));
$flags = array_values(array_filter(array_slice($argv, 1), static fn ($a) => str_starts_with($a, '--')));

$base = rtrim($args[0] ?? '', '/');
$token = $args[1] ?? '';
$resource = trim($args[2] ?? 'v1/demo/looks', '/');
$reproduce = in_array('--reproduce', $flags, true);
$hammer = 0;

foreach ($flags as $flag)
{
	if (str_starts_with($flag, '--hammer='))
	{
		$hammer = (int) substr($flag, 9);
	}
}

if ($base === '' || $token === '')
{
	fwrite(STDERR, "usage: php scenarios.php <base url> <token> [<resource path>] [--reproduce] [--hammer=<n>]\n");
	exit(2);
}

$endpoint = $base . '/api/index.php/' . $resource;
$guidPattern = '/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i';
$failures = 0;
$passes = 0;

/**
 * One request against the API.
 *
 * @param   string      $method  The HTTP method.
 * @param   string      $url     The URL.
 * @param   array|null  $body    The JSON body, when any.
 *
 * @return  array{status: int, body: array|null, raw: string}
 */
$request = static function (string $method, string $url, ?array $body = null) use ($token): array
{
	$curl = curl_init($url);
	$headers = [
		'Accept: application/vnd.api+json',
		'X-Joomla-Token: ' . $token,
	];

	if ($body !== null)
	{
		$headers[] = 'Content-Type: application/json';
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
	}

	curl_setopt_array($curl, [
		CURLOPT_CUSTOMREQUEST => $method,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 60,
	]);

	$raw = (string) curl_exec($curl);
	$status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
	curl_close($curl);

	return ['status' => $status, 'body' => json_decode($raw, true), 'raw' => $raw];
};

/**
 * Record one check.
 *
 * @param   string  $name  What is checked.
 * @param   bool    $ok    Whether it held.
 * @param   string  $note  Detail for the log.
 *
 * @return  void
 */
$check = static function (string $name, bool $ok, string $note = '') use (&$failures, &$passes): void
{
	if ($ok)
	{
		$passes++;
	}
	else
	{
		$failures++;
	}

	printf("  [%s] %s%s\n", $ok ? 'pass' : 'FAIL', $name, $note !== '' ? ' - ' . $note : '');
};

$name = 'JCB API GUID Test ' . bin2hex(random_bytes(3));
$createBody = ['name' => $name, 'description' => 'Temporary API create regression record.'];

echo "Resource {$endpoint}\n";

if ($reproduce)
{
	echo "\nReproduction: the create fails before the fix\n";
	$response = $request('POST', $endpoint, $createBody);
	$detail = (string) ($response['body']['errors'][0]['detail'] ?? $response['raw']);
	$check('create answers 500', $response['status'] === 500, 'status ' . $response['status']);
	$check(
		'the detail is the GuidHelper::valid() TypeError on a null id',
		str_contains($detail, 'GuidHelper::valid()') && str_contains($detail, 'Argument #3 ($id) must be of type int, null given'),
		substr($detail, 0, 160)
	);
	printf("\n%d passed, %d failed\n", $passes, $failures);
	exit($failures === 0 ? 0 : 1);
}

echo "\n1. POST with no id and no guid\n";
$created = $request('POST', $endpoint, $createBody);
$id = (int) ($created['body']['data']['id'] ?? 0);
$guid = (string) ($created['body']['data']['attributes']['guid'] ?? '');
$check('answers 200 or 201', in_array($created['status'], [200, 201], true), 'status ' . $created['status'] . ' ' . substr($created['raw'], 0, 200));
$check('returns a non-zero numeric id', $id > 0, 'id ' . $id);
$check('returns a canonical guid the server generated', preg_match($guidPattern, $guid) === 1, 'guid ' . $guid);
$check('the alias was built from the name', ($created['body']['data']['attributes']['alias'] ?? '') !== '', 'alias ' . ($created['body']['data']['attributes']['alias'] ?? ''));

echo "\n2. POST with body id 0 and a client guid\n";
$second = $request('POST', $endpoint, $createBody + ['id' => 0, 'guid' => $guid]);
$secondId = (int) ($second['body']['data']['id'] ?? 0);
$secondGuid = (string) ($second['body']['data']['attributes']['guid'] ?? '');
$check('still creates', in_array($second['status'], [200, 201], true) && $secondId > 0 && $secondId !== $id, 'status ' . $second['status'] . ' id ' . $secondId);
$check('ignores the client guid and generates its own', preg_match($guidPattern, $secondGuid) === 1 && $secondGuid !== $guid, 'guid ' . $secondGuid);

echo "\n3. POST with a body id of an existing record\n";
$third = $request('POST', $endpoint, $createBody + ['id' => $id]);
$thirdId = (int) ($third['body']['data']['id'] ?? 0);
$check('creates a new record instead of updating the named one', in_array($third['status'], [200, 201], true) && $thirdId > 0 && $thirdId !== $id, 'status ' . $third['status'] . ' id ' . $thirdId);

echo "\n4. GET by id and by guid\n";
$byId = $request('GET', $endpoint . '/' . $id);
$byGuid = $request('GET', $endpoint . '/guid/' . $guid);
$check('the record reads back by id', $byId['status'] === 200 && ($byId['body']['data']['attributes']['guid'] ?? '') === $guid, 'status ' . $byId['status']);
$check('the record reads back by guid', $byGuid['status'] === 200 && (int) ($byGuid['body']['data']['id'] ?? 0) === $id, 'status ' . $byGuid['status']);

echo "\n5. PATCH without a guid\n";
$patched = $request('PATCH', $endpoint . '/' . $id, ['description' => 'Updated through the API.']);
$check('answers 200', $patched['status'] === 200, 'status ' . $patched['status'] . ' ' . substr($patched['raw'], 0, 200));
$check('keeps the guid', ($patched['body']['data']['attributes']['guid'] ?? '') === $guid);
$check('stored the change', ($patched['body']['data']['attributes']['description'] ?? '') === 'Updated through the API.');

echo "\n6. PATCH with a different client guid\n";
$identity = $request('PATCH', $endpoint . '/' . $id, ['guid' => $secondGuid, 'description' => 'Identity must not move.']);
$check('answers 200', $identity['status'] === 200, 'status ' . $identity['status']);
$check('the stored guid wins', ($identity['body']['data']['attributes']['guid'] ?? '') === $guid, 'guid ' . ($identity['body']['data']['attributes']['guid'] ?? ''));
$stillSecond = $request('GET', $endpoint . '/' . $secondId);
$check('the other record kept its guid too', ($stillSecond['body']['data']['attributes']['guid'] ?? '') === $secondGuid);

echo "\n7. The list carries every record with a distinct guid\n";
$list = $request('GET', $endpoint . '?page[limit]=100&filter[search]=' . rawurlencode('JCB API GUID Test'));
$guids = array_map(static fn ($row) => $row['attributes']['guid'] ?? '', $list['body']['data'] ?? []);
$check('answers 200', $list['status'] === 200, 'status ' . $list['status']);
$check('every listed guid is canonical and unique', $guids !== [] && count($guids) === count(array_unique($guids)) && count(array_filter($guids, static fn ($g) => preg_match($guidPattern, $g) === 1)) === count($guids), count($guids) . ' records');

if ($hammer > 0)
{
	echo "\n8. {$hammer} concurrent creates\n";
	$multi = curl_multi_init();
	$handles = [];

	for ($i = 0; $i < $hammer; $i++)
	{
		$curl = curl_init($endpoint);
		curl_setopt_array($curl, [
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => ['Accept: application/vnd.api+json', 'Content-Type: application/json', 'X-Joomla-Token: ' . $token],
			CURLOPT_POSTFIELDS => json_encode(['name' => $name . ' hammer ' . $i]),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 120,
		]);
		curl_multi_add_handle($multi, $curl);
		$handles[] = $curl;
	}

	do
	{
		$status = curl_multi_exec($multi, $running);

		if ($running)
		{
			curl_multi_select($multi, 1.0);
		}
	}
	while ($running && $status === CURLM_OK);

	$hammered = [];
	$statuses = [];

	foreach ($handles as $curl)
	{
		$body = json_decode((string) curl_multi_getcontent($curl), true);
		$statuses[] = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		$hammered[] = (string) ($body['data']['attributes']['guid'] ?? '');
		curl_multi_remove_handle($multi, $curl);
		curl_close($curl);
	}

	curl_multi_close($multi);

	$created = count(array_filter($statuses, static fn ($s) => in_array($s, [200, 201], true)));
	$check("all {$hammer} creates succeeded", $created === $hammer, $created . ' created, statuses ' . implode(',', array_unique($statuses)));
	$check('every guid is canonical', count(array_filter($hammered, static fn ($g) => preg_match($guidPattern, $g) === 1)) === $hammer);
	$check('every guid is distinct', count(array_unique($hammered)) === $hammer, count(array_unique($hammered)) . ' distinct');
}

echo "\n9. Trash, delete, and a final 404\n";
$trashed = $request('PATCH', $endpoint . '/' . $id, ['published' => -2]);
$check('the record can be trashed', $trashed['status'] === 200 && (int) ($trashed['body']['data']['attributes']['published'] ?? 0) === -2, 'status ' . $trashed['status']);
$deleted = $request('DELETE', $endpoint . '/' . $id);
$check('the trashed record can be deleted', $deleted['status'] === 204, 'status ' . $deleted['status']);
$gone = $request('GET', $endpoint . '/' . $id);
$check('the deleted record answers 404', $gone['status'] === 404, 'status ' . $gone['status']);

printf("\n%d passed, %d failed\n", $passes, $failures);
exit($failures === 0 ? 0 : 1);
