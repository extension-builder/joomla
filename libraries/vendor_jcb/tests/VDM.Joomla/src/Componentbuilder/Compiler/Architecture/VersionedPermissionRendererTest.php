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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Category;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CategoryOtherName;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;


/**
 * Generated permission and model contracts across all Joomla target versions.
 *
 * @since  6.1.6
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedPermissionRendererTest extends ArchitectureTestCase
{
	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.6
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => ['JoomlaThree', 3],
			'Joomla 4' => ['JoomlaFour', 4],
			'Joomla 5' => ['JoomlaFive', 5],
			'Joomla 6' => ['JoomlaSix', 6],
		];
	}

	/**
	 * Keep the four-version, 24-family renderer matrix complete.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testArchitectureContainsEveryVersionedRendererFamily(): void
	{
		$families = [
			'AdminView/AddModalToolBar',
			'AdminView/AddToolBar',
			'AdminViews/AddToolBar',
			'ComHelperClass/CreateUser',
			'Controller/AllowAdd',
			'Controller/AllowEdit',
			'Controller/AllowEditViews',
			'CustomAdminView/AddToolBar',
			'CustomAdminViews/AddToolBar',
			'Dashboard/View',
			'Model/AllowEdit',
			'Model/CanDelete',
			'Model/CanEditState',
			'Model/CheckInNow',
			'Module/Dispatcher',
			'Module/Helper',
			'Module/Library',
			'Module/MainXML',
			'Module/Provider',
			'Module/Template',
			'Plugin/Extension',
			'Plugin/MainXML',
			'Plugin/Provider',
			'SiteView/AddToolBar',
		];

		$this->assertCount(24, $families);

		foreach (self::versions() as [$version])
		{
			foreach ($families as $family)
			{
				$this->assertTrue(
					class_exists($this->rendererClass($version, $family)),
					$version . '/' . $family . ' is missing.'
				);
			}
		}
	}

	/**
	 * Protect the compact modern user-helper delegation and the legacy J3 body.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testCreateUserRendererPreservesTargetSpecificImplementation(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'ComHelperClass/CreateUser'));

		$this->assertSame('', $subject->get(false));

		$code = $subject->get(true);

		$this->assertStringStartsWith(PHP_EOL . PHP_EOL . "\t/**", $code);
		$this->assertStringContainsString(
			"\tpublic static function createUser(\$credentials, \$autologin = 0,",
			$code
		);
		$this->assertStringEndsWith("\t}", $code);

		if ($major === 3)
		{
			$this->assertStringContainsString('Factory::getLanguage();', $code);
			$this->assertStringContainsString("\$model->register(\$data);", $code);
			$this->assertStringNotContainsString('UserHelper class', $code);
			return;
		}

		$this->assertStringContainsString(
			'Super___7832a726_87b6_4e95_887e_7b725d3fab8f___Power::create',
			$code
		);
		$this->assertStringContainsString('public static function updateUser($userDetails): int', $code);
		$this->assertStringNotContainsString('Factory::getLanguage();', $code);
	}

	/**
	 * Protect the complete check-in call boundary and target-specific database access.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testCheckInRendererPreservesExactCallAndTableContract(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'Model/CheckInNow'));

		$this->assertSame(
			PHP_EOL . "\t\t// Check in items" . PHP_EOL . "\t\t\$this->checkInNow();" . PHP_EOL,
			$subject->getCall()
		);

		$method = $subject->getMethod('articles', 'demo');

		$this->assertStringContainsString("getParams('com_demo')->get('check_in')", $method);
		$this->assertStringContainsString("quoteName('#__demo_articles')", $method);
		$this->assertStringContainsString('protected function checkInNow(): void', $method);

		if ($major === 3)
		{
			$this->assertStringContainsString('::getDbo();', $method);
			$this->assertStringContainsString(
				"quoteName('checked_out_time') . ' = ' . \$db->quote('0000-00-00 00:00:00')",
				$method
			);
			$this->assertStringNotContainsString('$this->getDatabase();', $method);
		}
		else
		{
			$this->assertStringContainsString('$this->getDatabase();', $method);
			$this->assertStringContainsString("quoteName('checked_out_time') . ' = NULL'", $method);
			$this->assertStringNotContainsString('::getDbo();', $method);
		}
	}

	/**
	 * Protect delete authorization source and component asset construction.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testCanDeleteRendererUsesTheTargetIdentityApi(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'Model/CanDelete'));
		$code = $subject->get('article');

		$this->assertStringContainsString("'core.delete', 'com_demo.article.' . (int) \$record->id)", $code);

		if ($major === 3)
		{
			$this->assertStringStartsWith(PHP_EOL . "\t\tif (!empty(\$record->id))", $code);
			$this->assertStringContainsString("\t\t\tif (\$record->published != -2)", $code);
			$this->assertStringContainsString('$user = Factory::getUser();', $code);
			$this->assertStringContainsString('$user->authorise(', $code);
		}
		else
		{
			$this->assertStringStartsWith(
				PHP_EOL . "\t\tif (empty(\$record->id) || (\$record->published != -2))",
				$code
			);
			$this->assertStringContainsString('$this->getCurrentUser()->authorise(', $code);
			$this->assertStringNotContainsString('Factory::getUser();', $code);
		}
	}

	/**
	 * Protect edit-state record fallbacks and current-user selection.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testCanEditStateRendererPreservesRecordAndFallbackFlow(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'Model/CanEditState'));
		$code = $subject->get('article');

		$this->assertStringContainsString(
			$major === 3 ? '$recordId = $record->id ??  0;' : '$recordId = $record->id ?? 0;',
			$code
		);
		$this->assertStringContainsString("'core.edit.state', 'com_demo.article.' . (int) \$recordId)", $code);
		$this->assertStringEndsWith("\t\treturn parent::canEditState(\$record);", $code);
		$this->assertStringContainsString(
			$major === 3 ? '$user = Factory::getUser();' : '$user = $this->getCurrentUser();',
			$code
		);
	}

	/**
	 * Protect the injected custom-code boundary in controller allow-add renderers.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testControllerAllowAddConsumesCustomCodeOnce(string $version, int $major): void
	{
		$dispenser = $this->getMockBuilder(Dispenser::class)
			->disableOriginalConstructor()
			->onlyMethods(['get'])
			->getMock();
		$dispenser->expects($this->once())
			->method('get')
			->with('php_allowadd', 'article', '', null, true)
			->willReturn("\t\tCUSTOM_ALLOW_ADD;");

		$subject = $this->renderer(
			$this->rendererClass($version, 'Controller/AllowAdd'),
			['dispenser' => $dispenser]
		);
		$code = $subject->get('article');

		$this->assertSame(1, substr_count($code, 'CUSTOM_ALLOW_ADD;'));
		$this->assertStringEndsWith("\t\treturn parent::allowAdd(\$data);", $code);
		$this->assertStringContainsString(
			$major === 3 ? '$user = Factory::getUser();' : '$user = $this->app->getIdentity();',
			$code
		);
	}

	/**
	 * Protect non-category controller edit output and custom-code insertion.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testControllerAllowEditPreservesNonCategoryFlow(string $version, int $major): void
	{
		$dispenser = $this->getMockBuilder(Dispenser::class)
			->disableOriginalConstructor()
			->onlyMethods(['get'])
			->getMock();
		$dispenser->expects($this->once())
			->method('get')
			->with('php_allowedit', 'article')
			->willReturn("\t\tCUSTOM_ALLOW_EDIT;");

		$subject = $this->renderer(
			$this->rendererClass($version, 'Controller/AllowEdit'),
			[
				'dispenser' => $dispenser,
				'category' => new Category(),
				'categoryothername' => new CategoryOtherName(),
			]
		);
		$code = $subject->get('article', 'articles');

		$this->assertSame(1, substr_count($code, 'CUSTOM_ALLOW_EDIT;'));
		$this->assertStringContainsString("'core.edit', 'com_demo.article.' . (int) \$recordId", $code);
		$this->assertStringEndsWith("\t\treturn parent::allowEdit(\$data, \$key);", $code);
		$this->assertStringContainsString(
			$major === 3 ? '$user = Factory::getUser();' : '$user = $this->app->getIdentity();',
			$code
		);
	}

	/**
	 * Protect version selection for the view-permission array renderer.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testAllowEditViewsIsDisabledOnlyForJoomlaThree(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'Controller/AllowEditViews'));
		$array = $subject->getArray(['article' => 'articles']);

		if ($major === 3)
		{
			$this->assertSame('', $array);
			$this->assertSame('', $subject->getFunctions(['article' => 'articles']));
			return;
		}

		$this->assertSame(
			PHP_EOL . "\t\t'article' => [" . PHP_EOL
				. "\t\t\t'edit' => 'core.edit'," . PHP_EOL
				. "\t\t\t'edit.own' => 'core.edit.own'" . PHP_EOL
				. "\t\t]",
			$array
		);
		$this->assertSame('', $subject->getFunctions(['article' => 'articles']));
	}

	/**
	 * Protect model allow-edit delegation and custom-code placement.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testModelAllowEditPreservesGeneratedAuthorizationAsset(string $version, int $major): void
	{
		$dispenser = $this->getMockBuilder(Dispenser::class)
			->disableOriginalConstructor()
			->onlyMethods(['get'])
			->getMock();
		$dispenser->expects($this->once())
			->method('get')
			->willReturn("\t\tCUSTOM_MODEL_EDIT;");

		$subject = $this->renderer(
			$this->rendererClass($version, 'Model/AllowEdit'),
			[
				'dispenser' => $dispenser,
				'category' => new Category(),
				'categoryothername' => new CategoryOtherName(),
			]
		);
		$code = $subject->get('article', 'articles');

		$this->assertSame(1, substr_count($code, 'CUSTOM_MODEL_EDIT;'));
		$this->assertStringContainsString('com_demo.article', $code);

		if ($major === 3)
		{
			$this->assertStringContainsString('::getUser();', $code);
			$this->assertStringNotContainsString('$this->getCurrentUser();', $code);
		}
		else
		{
			$this->assertStringContainsString('$this->getCurrentUser();', $code);
			$this->assertStringEndsWith(
				"\t\treturn \$user->authorise('core.edit', \$this->option);",
				$code
			);
		}
	}

	/**
	 * Build a versioned renderer class name.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   string  $family   Slash-delimited renderer family.
	 *
	 * @return  class-string
	 * @since   6.1.6
	 */
	private function rendererClass(string $version, string $family): string
	{
		return 'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\'
			. $version . '\\' . str_replace('/', '\\', $family);
	}
}
