<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Utilities\String;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Utilities\String\PluginHelper;


/**
 * Joomla plugin folder, class, installer, and language naming test.
 *
 * @since  6.1.6
 */
#[CoversClass(PluginHelper::class)]
final class PluginHelperTest extends TestCase
{
	/**
	 * Build every generated plugin identifier from the same group and code name.
	 *
	 * @param   string  $codeName          Plugin code name.
	 * @param   string  $group             Joomla plugin group.
	 * @param   string  $folder            Expected folder name.
	 * @param   string  $class             Expected runtime class name.
	 * @param   string  $installer         Expected installer class name.
	 * @param   string  $languagePrefix    Expected language prefix.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('pluginNameProvider')]
	public function testGeneratedPluginNamesRemainSynchronized(
		string $codeName,
		string $group,
		string $folder,
		string $class,
		string $installer,
		string $languagePrefix
	): void
	{
		$this->assertSame($folder, PluginHelper::safeFolderName($codeName, $group));
		$this->assertSame($class, PluginHelper::safeClassName($codeName, $group));
		$this->assertSame($installer, PluginHelper::safeInstallClassName($codeName, $group));
		$this->assertSame($languagePrefix, PluginHelper::safeLangPrefix($codeName, $group));
	}

	/**
	 * Provide ordinary and editors-xtd special-case plugin identifiers.
	 *
	 * @return  iterable<string, array{string, string, string, string, string, string}>
	 * @since   6.1.6
	 */
	public static function pluginNameProvider(): iterable
	{
		yield 'content plugin' => [
			'myPlugin',
			'content',
			'plg_content_myplugin',
			'PlgContentMyPlugin',
			'plgContentMyPluginInstallerScript',
			'PLG_CONTENT_MYPLUGIN'
		];
		yield 'editors xtd maps to button' => [
			'insertArticle',
			'editors-xtd',
			'plg_button_insertarticle',
			'PlgButtonInsertArticle',
			'plgButtonInsertArticleInstallerScript',
			'PLG_BUTTON_INSERTARTICLE'
		];
	}
}
