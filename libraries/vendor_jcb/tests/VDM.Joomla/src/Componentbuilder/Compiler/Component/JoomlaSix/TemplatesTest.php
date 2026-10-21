<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    21st October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Component\JoomlaSix;


use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;


/**
 * Reviewed Joomla 6 template differences preserve all other generated source.
 *
 * @since  6.1.6
 */
#[CoversNothing]
final class TemplatesTest extends TestCase
{
	/**
	 * Each override changes only the reviewed API calls, including their counts.
	 *
	 * @param   string  $name          Original compiler template filename.
	 * @param   array   $replacements  Reviewed old/new source and occurrence counts.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('templates')]
	public function testOnlyReviewedDeprecationsChange(string $name, array $replacements): void
	{
		$root = dirname(JPATH_COMPONENT_ADMINISTRATOR);
		$expected = file_get_contents($root . '/admin/compiler/joomla_4/' . $name);
		$actual = file_get_contents($root . '/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Component/JoomlaSix/Templates/' . $name . '.tpl');
		$this->assertIsString($expected);
		$this->assertIsString($actual);

		foreach ($replacements as [$before, $after, $count])
		{
			$this->assertSame($count, substr_count($expected, $before), $name . ': ' . $before);
			$expected = str_replace($before, $after, $expected);
		}

		$this->assertSame($expected, $actual, $name);
	}

	/**
	 * Exact source differences approved for each Joomla 6 override.
	 *
	 * @return  iterable<string, array{string, array}>
	 * @since   6.1.6
	 */
	public static function templates(): iterable
	{
		$queries = [
			'ADMIN_HELPER_CLASS.php' => 3,
			'ADMIN_HELPER_CONTROLLER.php' => 1,
			'ADMIN_IMPORT_MODEL.php' => 1,
			'ADMIN_IMPORT_MODEL_CUSTOM.php' => 1,
			'ADMIN_VIEW_MODEL.php' => 1,
			'ADMIN_VIEW_TABLE.php' => 1,
			'FORM_MODAL_SELECT_FIELD.php' => 1,
			'INSTALLER_SCRIPT.php' => 20,
			'SITE_ADMIN_VIEW_MODEL.php' => 1,
			'SITE_FORM_MODAL_SELECT_FIELD.php' => 1,
			'SITE_HELPER_CLASS.php' => 3,
			'SITE_HELPER_CONTROLLER.php' => 1,
		];

		foreach ($queries as $name => $count)
		{
			$changes[$name] = [['->getQuery(true)', '->createQuery()', $count]];
		}

		$factory = 'Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power';
		$access = [
			'ADMIN_IMPORT_MODEL.php' => [4, 2, 9],
			'ADMIN_IMPORT_MODEL_CUSTOM.php' => [4, 1, 3],
			'ADMIN_IMPORT_HTML.php' => [0, 1, 1],
			'API_VIEW_CONTROLLER.php' => [0, 0, 2],
		];

		foreach ($access as $name => [$config, $session, $remove])
		{
			if ($config > 0)
			{
				$changes[$name][] = [$factory . '::getConfig()', $factory . '::getApplication()->getConfig()', $config];
			}

			if ($session > 0)
			{
				$changes[$name][] = [$factory . '::getSession()', $factory . '::getApplication()->getSession()', $session];
			}

			$changes[$name][] = ['$session->clear(', '$session->remove(', $remove];
		}

		$changes['ADMIN_IMPORT_HTML.php'][] = [
			'$state = $this->get(\'state\');',
			'$model = $this->getModel();' . "\n\t\t" . '$state = $model->getState();',
			1,
		];
		$changes['DASH_VIEW_HTML.php'][] = [
			'// Assign data to the view' . "\n",
			'// Assign data to the view' . "\n\t\t" . '$model = $this->getModel();' . "\n",
			1,
		];

		foreach (['Icons', 'Styles', 'Scripts'] as $property)
		{
			$changes['DASH_VIEW_HTML.php'][] = [
				'$this->get(\'' . $property . '\')',
				'$model->get' . $property . '()',
				1,
			];
		}

		foreach (['ADMIN_IMPORT_HTML.php', 'DASH_VIEW_HTML.php', 'ADMIN_VIEW_HTML.php', 'ADMIN_VIEWS_HTML.php', 'SITE_ADMIN_VIEW_HTML.php'] as $name)
		{
			$changes[$name][] = ['$this->get(\'Errors\')', '$model->getErrors()', 1];
		}

		foreach ($changes as $name => $replacements)
		{
			yield $name => [$name, $replacements];
		}
	}
}
