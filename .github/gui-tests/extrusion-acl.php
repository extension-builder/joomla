<?php
/**
 * Disposable native Joomla user/group fixtures for Extrusion permission tests.
 *
 * This helper is never installed. It records original asset rules before any
 * fixture changes, creates no JCB definitions, and restores owned ACL fixtures
 * even when the GUI harness fails.
 */

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Asset;
use Joomla\CMS\Table\Usergroup;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;

if (PHP_SAPI !== 'cli' || getenv('JCB_DISPOSABLE_TEST') !== '1'
	|| !is_file('/tmp/jcb-disposable-gui-stack'))
{
	fwrite(STDERR, "Extrusion ACL fixtures require the disposable GUI harness.\n");
	exit(2);
}

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias(Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\Session::class, 'session.cli')
	->alias(Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(Joomla\Console\Application::class);
Factory::$application = $app;
$users = $container->get(UserFactoryInterface::class);
$app->loadIdentity($users->loadUserByUsername(getenv('JCB_ADMIN_USER') ?: 'jcbgui'));
$db = $container->get(DatabaseInterface::class);
$manifest = '/tmp/jcb-extrusion-acl.json';
$mode = $argv[1] ?? '';
$check = static function (bool $condition, string $message): void
{
	if (!$condition)
	{
		throw new RuntimeException($message);
	}
	echo 'PASS ' . $message . PHP_EOL;
};
$save = static function (array $state) use ($manifest): void
{
	$old = umask(0077);
	try
	{
		$content = json_encode($state, JSON_THROW_ON_ERROR);
		if (file_put_contents($manifest, $content, LOCK_EX) !== strlen($content))
		{
			throw new RuntimeException('Unable to preserve disposable ACL fixture ownership.');
		}
	}
	finally
	{
		umask($old);
	}
};

if ($mode === '--seed')
{
	$check(!file_exists($manifest), 'Extrusion ACL fixture ownership is fresh');
	$state = ['assets' => [], 'groups' => [], 'users' => []];
	foreach (['root.1', 'com_componentbuilder'] as $name)
	{
		$asset = new Asset($db);
		$check($asset->loadByName($name), 'Resolve the native ACL asset ' . $name);
		$state['assets'][] = ['id' => (int) $asset->id, 'name' => $name, 'rules' => $asset->rules];
	}
	$save($state);
	$db->transactionStart();
	try
	{
		foreach (['access' => true, 'denied' => false] as $role => $allowed)
		{
			$username = 'jcb_gui_extrusion_' . $role;
			$title = 'JCB GUI Extrusion ' . ucfirst($role);
			$existing = $users->loadUserByUsername($username);
			$check((int) $existing->id === 0, 'The ACL fixture does not overwrite user ' . $username);
			$count = (int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__usergroups'))
				->where($db->quoteName('title') . ' = ' . $db->quote($title)))->loadResult();
			$check($count === 0, 'The ACL fixture does not overwrite group ' . $title);
			$group = new Usergroup($db);
			$check($group->bind(['title' => $title, 'parent_id' => 2]) && $group->check() && $group->store(),
				'Create an isolated non-super-user ACL group');
			$groupId = (int) $group->id;
			$state['groups'][] = ['id' => $groupId, 'title' => $title];
			$save($state);

			foreach ($state['assets'] as $original)
			{
				$asset = new Asset($db);
				$check($asset->load($original['id']), 'Load the native asset for a fixture rule');
				$rules = json_decode($asset->rules, true, 512, JSON_THROW_ON_ERROR);
				if ($original['name'] === 'root.1')
				{
					$rules['core.login.admin'][(string) $groupId] = 1;
					$rules['core.login.site'][(string) $groupId] = 1;
				}
				else
				{
					$rules['core.manage'][(string) $groupId] = 1;
					$rules['extrusion.access'][(string) $groupId] = $allowed ? 1 : 0;
				}
				$asset->rules = json_encode($rules, JSON_THROW_ON_ERROR);
				$check($asset->store(), 'Grant only fixture login, component entry and the selected view access');
			}

			$user = new User();
			$userData = [
				'name' => 'JCB GUI Extrusion ' . ucfirst($role),
				'username' => $username,
				'email' => $username . '@jcb.invalid',
				'password' => 'Jcb-Gui-Acl-2026!',
				'password2' => 'Jcb-Gui-Acl-2026!',
				'groups' => [$groupId],
				'block' => 0,
			];
			$check($user->bind($userData) && $user->save(), 'Create an isolated native Joomla ACL user');
			$state['users'][] = ['id' => (int) $user->id, 'username' => $username];
			$save($state);
			Access::clearStatics();
			$check(!$user->authorise('core.admin') && !$user->authorise('extrusion.import', 'com_componentbuilder')
				&& $user->authorise('extrusion.access', 'com_componentbuilder') === $allowed,
				'Native ACL confirms the fixture has no super-user or separate import authority');
		}
		$db->transactionCommit();
	}
	catch (Throwable $error)
	{
		$db->transactionRollback();
		throw $error;
	}
}
elseif ($mode === '--cleanup')
{
	if (!is_file($manifest))
	{
		echo "No Extrusion ACL fixtures require cleanup.\n";
		exit(0);
	}
	$state = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
	foreach ($state['users'] as $owned)
	{
		$user = $users->loadUserById((int) $owned['id']);
		if ((int) $user->id !== 0)
		{
			$check($user->username === $owned['username'] && str_starts_with($user->username, 'jcb_gui_extrusion_'),
				'Only the owned ACL user is selected for cleanup');
			$check($user->delete(), 'Remove the owned native Joomla ACL user');
		}
	}
	foreach (array_reverse($state['groups']) as $owned)
	{
		$group = new Usergroup($db);
		if ($group->load((int) $owned['id']))
		{
			$check($group->title === $owned['title'] && (int) $group->parent_id === 2,
				'Only the owned non-super-user group is selected for cleanup');
			$check((int) $group->rgt === (int) $group->lft + 1,
				'The owned ACL group has no descendants that cleanup could remove');
			$members = (int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')
				->from($db->quoteName('#__user_usergroup_map'))
				->where($db->quoteName('group_id') . ' = ' . (int) $owned['id']))->loadResult();
			$check($members === 0, 'The owned ACL group has no remaining user memberships');
			$check($group->delete(), 'Remove the owned native Joomla ACL group');
		}
	}
	foreach ($state['assets'] as $original)
	{
		$asset = new Asset($db);
		$check($asset->load((int) $original['id']) && $asset->name === $original['name'],
			'Restore only the original fixture ACL asset');
		$asset->rules = $original['rules'];
		$check($asset->store(), 'Restore the exact original native asset rules');
	}
	Access::clearStatics();
	$check(unlink($manifest), 'Remove the private disposable ACL ownership manifest');
}
else
{
	throw new InvalidArgumentException('Choose --seed or --cleanup for the disposable ACL fixture.');
}
