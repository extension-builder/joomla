<?php
/**
 * Extract the generated component and pass actual emitted library roots to the
 * disposable installed SQL/scale probe. No generated component is installed.
 */

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| !is_file('/tmp/jcb-disposable-gui-stack'))
{
	fwrite(STDERR, "This driver requires the disposable golden stack.\n");
	exit(2);
}

$evidence = json_decode(file_get_contents('/tmp/jcb-golden-candidate.json'), true, 512, JSON_THROW_ON_ERROR);
$packages = glob('/tmp/jcb-golden-output/com_*.zip');

if (count($packages) !== 1 || empty($evidence['library_roots'])
	|| ($argv[1] ?? '') !== $evidence['component'])
{
	throw new RuntimeException('Scale verification requires one completed component package with emitted Power libraries.');
}

$root = '/tmp/jcb-golden-output/component';
$archive = new ZipArchive();

if ($archive->open($packages[0]) !== true)
{
	throw new RuntimeException('Cannot open the compiled component package.');
}

for ($index = 0; $index < $archive->numFiles; $index++)
{
	$name = str_replace('\\', '/', $archive->getNameIndex($index));

	if (str_starts_with($name, '/') || preg_match('~(^|/)\.\.(/|$)|^[a-z]:~i', $name))
	{
		throw new RuntimeException('The generated archive contains an unsafe path.');
	}
}

if (!$archive->extractTo($root))
{
	throw new RuntimeException('Cannot extract the compiled component package.');
}

$archive->close();
$command = ['php', '/tmp/discovery-scale.php', $evidence['component'], $root];

foreach ($evidence['library_roots'] as $relative)
{
	if (!preg_match('~^libraries/[a-zA-Z0-9_./-]+$~', $relative)
		|| str_contains($relative, '..') || !is_dir($root . '/' . $relative))
	{
		throw new RuntimeException('An observed Power library is absent from the generated component.');
	}

	$command[] = $root . '/' . $relative;
}

$process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);

if (!is_resource($process))
{
	throw new RuntimeException('Cannot start the installed discovery probe.');
}

exit(proc_close($process));
