<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    11th November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use VDM\Tests\Support\ConditionalBrowserCompiler;


require dirname(__DIR__) . '/bootstrap.php';

$compiler = new ConditionalBrowserCompiler();
$cases = json_decode(
	file_get_contents(dirname(__DIR__) . '/Support/fixtures/conditional-browser.json'),
	true,
	512,
	JSON_THROW_ON_ERROR
);

foreach ($cases as &$case)
{
	foreach ($case['conditions'] as &$condition)
	{
		$condition += [
			'match_name' => 'mode',
			'match_type' => 'list',
			'match_extends' => '',
			'match_behavior' => 1,
			'match_options' => 'yes',
			'target_relation' => 0,
			'target_behavior' => 1,
			'target_field' => [['name' => 'details', 'type' => 'text', 'required' => 'yes']],
		];
		$condition['match_field'] = $condition['match_name'];
	}
	unset($condition);

	try
	{
		$case += $compiler->render($case['conditions']);
		foreach ($case['steps'] ?? [] as $index => $step)
		{
			$case['steps'][$index]['phpRequired'] = $compiler->requirements($case['groups'], $step['values']);
		}
	}
	catch (UnexpectedValueException $error)
	{
		if (!isset($case['error']))
		{
			throw $error;
		}
		$case['compileError'] = $error->getMessage();
	}
}
unset($case);

fwrite(STDOUT, json_encode($cases, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
