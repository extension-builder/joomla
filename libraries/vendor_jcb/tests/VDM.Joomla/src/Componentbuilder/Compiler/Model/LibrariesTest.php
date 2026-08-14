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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Model;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\LibraryManager;
use VDM\Joomla\Componentbuilder\Compiler\Library\Data as LibraryData;
use VDM\Joomla\Componentbuilder\Compiler\Model\Libraries;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\GuidHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Library-selection normalization and manager-state contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Libraries::class)]
#[UsesClass(LibraryManager::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(GuidHelper::class)]
#[UsesClass(JsonHelper::class)]
final class LibrariesTest extends CompilerDomainTestCase
{
	/**
	 * Decode JSON, ignore invalid and duplicate values, and register loaded libraries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetNormalizesJsonAndRegistersOnlyLoadedUniqueLibraries(): void
	{
		$loadedGuid = '57d5f6fa-7b1c-4d36-b382-93281c3ef020';
		$missingGuid = '9bf7fc62-6f25-44b6-82a8-3f053019a16e';
		$manager = new LibraryManager();
		$libraryCalls = [];
		$library = $this->createStub(LibraryData::class);
		$library->method('get')->willReturnCallback(
			static function (string $guid) use (&$libraryCalls, $loadedGuid): object|false
			{
				$libraryCalls[] = $guid;

				return $guid === $loadedGuid ? (object) ['guid' => $guid] : false;
			}
		);
		$item = (object) [
			'libraries' => json_encode([
				$loadedGuid,
				$loadedGuid,
				'not-a-guid',
				$missingGuid
			], JSON_THROW_ON_ERROR)
		];

		(new Libraries($this->compilerConfig(), $manager, $library))
			->set('article', $item, 'site');

		$this->assertSame([
			$loadedGuid,
			$missingGuid
		], $libraryCalls);
		$this->assertSame([
			$loadedGuid,
			$loadedGuid,
			'not-a-guid',
			$missingGuid
		], $item->libraries);
		$this->assertTrue($manager->get('site.article.' . $loadedGuid));
		$this->assertFalse($manager->exists('site.article.' . $missingGuid));
	}

	/**
	 * Use the configured build target for a single GUID value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetUsesConfiguredTargetForSingleGuid(): void
	{
		$guid = 'bc336e59-8b43-4b38-b72b-f41416300947';
		$manager = new LibraryManager();
		$library = $this->createMock(LibraryData::class);
		$library->expects($this->once())
			->method('get')
			->with($guid)
			->willReturn((object) ['guid' => $guid]);
		$item = (object) ['libraries' => $guid];

		(new Libraries(
			$this->compilerConfig(['build_target' => 'administrator']),
			$manager,
			$library
		))->set('dashboard', $item);

		$this->assertTrue($manager->get('administrator.dashboard.' . $guid));
		$this->assertSame($guid, $item->libraries);
	}

	/**
	 * Reject absent, malformed, and empty library selections without loading data.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetIgnoresUnusableLibrarySelections(): void
	{
		$manager = new LibraryManager();
		$library = $this->createMock(LibraryData::class);
		$library->expects($this->never())->method('get');
		$subject = new Libraries($this->compilerConfig(), $manager, $library);
		$absent = (object) [];
		$malformed = (object) ['libraries' => '{invalid'];
		$empty = (object) ['libraries' => []];

		$subject->set('absent', $absent);
		$subject->set('malformed', $malformed);
		$subject->set('empty', $empty);

		$this->assertSame([], $manager->allActive());
		$this->assertObjectNotHasProperty('libraries', $absent);
		$this->assertSame('{invalid', $malformed->libraries);
		$this->assertSame([], $empty->libraries);
	}
}
