<?php
/**
 * Seed and remove owned language-filter fixtures in the disposable GUI stack.
 * Existing rows are never updated. The manifest records each inserted identity
 * before the transaction commits, including when later harness steps fail.
 */

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use VDM\Joomla\Componentbuilder\Compiler\Language\Insert;

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| !is_file('/tmp/jcb-disposable-gui-stack'))
{
	fwrite(STDERR, "Language fixtures require the disposable GUI harness.\n");
	exit(2);
}

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_ADMINISTRATOR . '/components/com_componentbuilder/src/Helper/PowerloaderHelper.php';
$db = Factory::getContainer()->get(DatabaseInterface::class);
$manifest = '/tmp/jcb-language-translation-fixtures.json';
$mode = $argv[1] ?? '';

if ($mode === '--cleanup')
{
	if (!is_file($manifest))
	{
		exit(0);
	}
	$state = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
	$db->transactionStart();
	try
	{
		foreach (array_reverse($state['owned']) as $row)
		{
			if (!in_array($row['table'], ['language', 'language_translation', 'joomla_component'], true)
				|| !in_array($row['key'], ['source', 'name', 'guid'], true))
			{
				throw new RuntimeException('Unexpected fixture ownership entry.');
			}
			$query = $db->getQuery(true)->delete($db->quoteName('#__componentbuilder_' . $row['table']))
				->where($db->quoteName('id') . ' = ' . (int) $row['id'])
				->where($db->quoteName($row['key']) . ' = ' . $db->quote($row['value']));
			$db->setQuery($query)->execute();
		}
		$db->transactionCommit();
		unlink($manifest);
	}
	catch (Throwable $error)
	{
		$db->transactionRollback();
		throw $error;
	}
	echo "PASS Removed owned language-filter fixtures.\n";
	exit(0);
}

if ($mode !== '--seed' || file_exists($manifest))
{
	throw new RuntimeException('Use --seed with no existing language-filter manifest.');
}

$suffix = bin2hex(random_bytes(5));
$state = ['prefix' => 'JCB GUI 1309 ' . $suffix, 'owned' => [], 'sources' => []];
$save = static function () use (&$state, $manifest): void
{
	$content = json_encode($state, JSON_THROW_ON_ERROR);
	if (file_put_contents($manifest, $content, LOCK_EX) !== strlen($content))
	{
		throw new RuntimeException('Unable to record fixture ownership.');
	}
};
$insert = static function (string $table, array $data, string $key) use ($db, &$state, $save): void
{
	$object = (object) ($data + ['published' => 1, 'access' => 1, 'created' => gmdate('Y-m-d H:i:s')]);
	$db->insertObject('#__componentbuilder_' . $table, $object, 'id');
	if ($table === 'language_translation' && $data['translation'] === null)
	{
		// insertObject omits null properties; preserve the explicit SQL NULL case.
		$db->setQuery($db->getQuery(true)->update($db->quoteName('#__componentbuilder_language_translation'))
			->set($db->quoteName('translation') . ' = NULL')->where($db->quoteName('id') . ' = ' . (int) $object->id))->execute();
	}
	$state['owned'][] = ['table' => $table, 'id' => (int) $object->id, 'key' => $key, 'value' => $data[$key]];
	$save();
};
$save();
$db->transactionStart();
try
{
	// Unique private-use tags cannot collide with existing published definitions.
	$state['language_a'] = 'x-jcb-a-' . $suffix;
	$state['language_b'] = 'x-jcb-b-' . $suffix;
	foreach (['a', 'b'] as $letter)
	{
		$insert('language', ['name' => $state['prefix'] . ' ' . strtoupper($letter), 'langtag' => $state['language_' . $letter]], 'name');
		$hex = hash('sha256', $state['prefix'] . $letter);
		$guid = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4' . substr($hex, 13, 3)
			. '-a' . substr($hex, 17, 3) . '-' . substr($hex, 20, 12);
		$state['extension_' . $letter] = 'joomla_component__' . $guid;
		$insert('joomla_component', ['guid' => $guid, 'name' => $state['prefix'] . $letter,
			'system_name' => $state['prefix'] . $letter, 'name_code' => 'jcbfilterfixture' . $letter,
			'component_version' => '1.0.0'], 'guid');
	}
	$tags = $db->setQuery($db->getQuery(true)->select($db->quoteName('langtag'))
		->from($db->quoteName('#__componentbuilder_language'))->where($db->quoteName('published') . ' = 1'))->loadColumn();
	$entry = static fn (string $tag, string $text): array => ['language' => $tag, 'translation' => $text];
	$values = [
		'missing' => null,
		'empty' => '[]',
		'blank' => json_encode([$entry($state['language_a'], '')]),
		'incidental' => json_encode([$entry($state['language_b'], 'Text mentioning ' . $state['language_a'])]),
		'zero' => json_encode([$entry($state['language_a'], '0')]),
		'translated' => json_encode([$entry($state['language_a'], 'Translation')]),
		'complete' => json_encode(array_map(static fn (string $tag): array => $entry($tag, 'Translation'), $tags)),
		'other_extension' => json_encode([$entry($state['language_a'], 'Translation')]),
	];
	foreach ($values as $name => $value)
	{
		$state['sources'][$name] = $state['prefix'] . ' ' . $name;
		$extension = substr($state[$name === 'other_extension' ? 'extension_b' : 'extension_a'], strlen('joomla_component__'));
		$insert('language_translation', ['source' => $state['sources'][$name], 'translation' => $value,
			'components' => json_encode([$extension]), 'modules' => '[]', 'plugins' => '[]'], 'source');
	}

	// Use the compiler's real seven-value insert contract, without a manual model save.
	$state['sources']['compiler_created'] = $state['prefix'] . ' compiler_created';
	$compilerInsert = new Insert($db);
	$extension = substr($state['extension_a'], strlen('joomla_component__'));
	foreach ([json_encode([$extension]), $state['sources']['compiler_created'], '1', gmdate('Y-m-d H:i:s'), '1', '1', '1'] as $value)
	{
		$compilerInsert->set('components', 0, $value);
	}
	$compilerInsert->execute('components');
	$state['owned'][] = ['table' => 'language_translation', 'id' => (int) $db->insertid(),
		'key' => 'source', 'value' => $state['sources']['compiler_created']];
	$save();

	$stored = $db->setQuery($db->getQuery(true)->select($db->quoteName(['source', 'translation']))
		->from($db->quoteName('#__componentbuilder_language_translation'))
		->where($db->quoteName('source') . ' IN (' . implode(',', $db->quote([
			$state['sources']['compiler_created'], $state['sources']['missing']
		])) . ')'))->loadAssocList('source');
	if ($stored[$state['sources']['compiler_created']]['translation'] !== ''
		|| $stored[$state['sources']['missing']]['translation'] !== null)
	{
		throw new RuntimeException('Expected compiler-created empty string and legacy SQL NULL before any manual save.');
	}
	$db->transactionCommit();
}
catch (Throwable $error)
{
	$db->transactionRollback();
	throw $error;
}
echo "PASS Seeded nine translation cases, including actual compiler insertion and legacy SQL NULL, before any manual save.\n";
