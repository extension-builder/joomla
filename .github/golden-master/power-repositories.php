<?php
/**
 * Pinned public dependency sources for disposable compiler fixtures only.
 *
 * These joomengine repositories mirror git.vdm.dev/joomla's original catalogs.
 * Keep both harnesses on identical definitions without changing installed JCB
 * repository settings, Joomla target selection or Power resolution behavior.
 */

return [
	'approved_paths' => [(object) [
		'target' => 'github',
		'base' => 'https://api.github.com',
		'organisation' => 'joomengine',
		'repository' => 'super-powers',
		'read_branch' => 'adf335173201edeaf95f0ef6c3dc1bcabd23f3bc',
	]],
	'approved_joomla_paths' => [(object) [
		'target' => 'github',
		'base' => 'https://api.github.com',
		'organisation' => 'joomengine',
		'repository' => 'joomla-powers',
		'read_branch' => 'e38ad0600bdd82513021ec7e5b2b9eecfe7f2f0b',
	]],
];
