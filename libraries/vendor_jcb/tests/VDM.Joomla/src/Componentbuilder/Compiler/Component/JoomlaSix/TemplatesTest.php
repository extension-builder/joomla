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
use PHPUnit\Framework\TestCase;


/**
 * Conventional target-specific templates and settings remain the package source.
 *
 * Behavior changes are exercised by the native MVC, asset, input/import and
 * actual Power-injection runtime tests rather than a Joomla 4 text rewrite.
 *
 * @since  6.2.0
 */
#[CoversNothing]
final class TemplatesTest extends TestCase
{
	/**
	 * Every configured source resolves inside the Joomla 6 PHP template set.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testSettingsResolveAllStaticAndDynamicTemplateSources(): void
	{
		$root = dirname(JPATH_COMPONENT_ADMINISTRATOR) . '/admin/compiler/joomla_6/';
		$settings = json_decode(file_get_contents($root . 'settings.json'), true, 512, JSON_THROW_ON_ERROR);
		$this->assertSame(['admin', 'api', 'site', 'custom_admin'], array_keys($settings['move']['dynamic']));
		$this->assertArrayHasKey('media', $settings['create']);

		foreach (['static' => $settings['move']['static']] + $settings['move']['dynamic'] as $area => $sources)
		{
			$this->assertNotEmpty($sources, $area);

			foreach ($sources as $name => $definition)
			{
				$this->assertSame(basename($name), $name, 'Templates cannot escape their target folder.');
				$this->assertFileExists($root . $name, $area . ': ' . $name);
				$this->assertStringStartsWith('c0mp0n3nt/', $definition['path'], $name);
				$this->assertStringNotContainsString('.tpl', $name);
			}
		}

		// These paths are active settings entries and must not be deleted as obsolete.
		$this->assertArrayHasKey('ADMIN_IMPORT_MODEL.php', $settings['move']['dynamic']['admin']);
		$this->assertArrayHasKey('ADMIN_IMPORT_CONTROLLER.php', $settings['move']['dynamic']['admin']);
		$this->assertArrayHasKey('ADMIN_CHARTBUILDER.php', $settings['move']['static']);
		$this->assertSame(
			'c0mp0n3nt/admin/src/Helper',
			$settings['move']['static']['ADMIN_CHARTBUILDER.php']['path']
		);
		$this->assertSame('Chartbuilder.php', $settings['move']['static']['ADMIN_CHARTBUILDER.php']['newName']);
	}

	/**
	 * Unrelated templates keep their existing source; native changes are explicit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTemplateInventoryAndUnchangedSources(): void
	{
		$root = dirname(JPATH_COMPONENT_ADMINISTRATOR) . '/admin/compiler/';
		$previous = array_map('basename', glob($root . 'joomla_4/*'));
		$current = array_map('basename', glob($root . 'joomla_6/*'));
		$expected = array_merge($previous, ['ADMIN_CHARTBUILDER.php']);
		sort($expected);
		$this->assertSame($expected, $current);
		$native = [
			'ADMIN_HEADERCHECK.php',
			'ADMIN_HELPER_CLASS.php',
			'ADMIN_HELPER_CONTROLLER.php',
			'ADMIN_IMPORT_CONTROLLER.php',
			'ADMIN_IMPORT_CONTROLLER_CUSTOM.php',
			'ADMIN_IMPORT_HTML.php',
			'ADMIN_IMPORT_MODEL.php',
			'ADMIN_IMPORT_MODEL_CUSTOM.php',
			'ADMIN_VIEWS.php',
			'ADMIN_VIEWS_HTML.php',
			'ADMIN_VIEWS_MODEL.php',
			'ADMIN_VIEW_HTML.php',
			'ADMIN_VIEW_MODEL.php',
			'ADMIN_VIEW_TABLE.php',
			'API_VIEW_CONTROLLER.php',
			'CUSTOM_ADMIN_VIEWS_HTML.php',
			'CUSTOM_ADMIN_VIEWS_MODEL.php',
			'CUSTOM_ADMIN_VIEW_HTML.php',
			'CUSTOM_ADMIN_VIEW_MODEL.php',
			'DASH_MODEL.php',
			'DASH_VIEW_HTML.php',
			'FORM_MODAL_SELECT_FIELD.php',
			'INSTALLER_SCRIPT.php',
			'SITE_ADMIN_VIEW_HTML.php',
			'SITE_ADMIN_VIEW_MODEL.php',
			'SITE_DISPLAY_CONTROLLER.php',
			'SITE_FORM_MODAL_SELECT_FIELD.php',
			'SITE_HEADERCHECK.php',
			'SITE_HELPER_CLASS.php',
			'SITE_HELPER_CONTROLLER.php',
			'SITE_VIEWS_HTML.php',
			'SITE_VIEWS_MODEL.php',
			'SITE_VIEW_HTML.php',
			'SITE_VIEW_MODEL.php',
			'default_import.php',
			'default_import_custom.php',
			'default_toolbar.php',
			'settings.json',
			'submitbutton.js',
			'submitbutton_site.js',
		];

		foreach (array_diff($previous, $native) as $name)
		{
			$this->assertFileEquals($root . 'joomla_4/' . $name, $root . 'joomla_6/' . $name);
		}
	}
}
