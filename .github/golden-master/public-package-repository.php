<?php
/**
 * Keep the workflow's in-memory GitHub authentication for the public blueprint.
 * Never include a repository credential in the native console's JSON option.
 */

return static function (?object $repository): object
{
	$identity = [
		'guid' => '562624ab-48bf-4979-9a14-6b10cf3635de',
		'target' => 'github',
		'base' => 'https://api.github.com',
		'organisation' => 'joomengine',
		'repository' => 'packages',
	];

	foreach ($identity as $key => $value)
	{
		if (($repository->{$key} ?? null) !== $value)
		{
			throw new RuntimeException('The golden blueprint requires the known public GitHub Packages repository.');
		}
	}

	if (($repository->token ?? '') !== '')
	{
		throw new RuntimeException('The public golden repository must not contain a repository credential.');
	}

	$public = clone $repository;
	unset($public->token, $public->username);

	return $public;
};
