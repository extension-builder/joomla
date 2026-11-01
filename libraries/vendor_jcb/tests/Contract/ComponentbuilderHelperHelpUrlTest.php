<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    1st November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Contract;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Component\Componentbuilder\Administrator\Helper\ComponentbuilderHelper;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Protect administrator help selection without Joomla's legacy User getter.
 *
 * The shipped administrator helper is outside the vendor coverage inventory.
 * Its real selection logic runs against isolated user and database boundaries.
 *
 * @since  6.2.1
 */
#[CoversNothing]
final class ComponentbuilderHelperHelpUrlTest extends JoomlaTestCase
{
	/**
	 * Resolve help through public groups while preserving access and fallback rules.
	 *
	 * @param   array<int, int>           $groups    Current user's assigned groups.
	 * @param   array<int, array<mixed>>  $records   Ordered help records from the database.
	 * @param   string|false             $expected  First accessible URL or no help.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('helpSelections')]
	public function testHelpSelectionUsesPublicUserGroups(array $groups, array $records, string|false $expected): void
	{
		$user = $this->getMockBuilder(User::class)->disableOriginalConstructor()
			->onlyMethods(['get'])->getMock();
		$user->groups = $groups;
		$user->expects($this->never())->method('get');
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())->method('getIdentity')->willReturn($user);
		$this->setJoomlaApplication($app);

		$query = $this->createMock(DatabaseQuery::class);
		$query->expects($this->once())->method('select')
			->with(['a.id', 'a.groups', 'a.target', 'a.type', 'a.article', 'a.url'])->willReturnSelf();
		$query->expects($this->once())->method('from')
			->with('#__componentbuilder_help_document AS a')->willReturnSelf();
		$conditions = [];
		$query->expects($this->exactly(3))->method('where')
			->willReturnCallback(static function (mixed $condition) use (&$conditions, $query): DatabaseQuery
			{
				$conditions[] = $condition;

				return $query;
			});
		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->once())->method('createQuery')->willReturn($query);
		$db->expects($this->once())->method('quote')->with('compiler')->willReturn("'compiler'");
		$db->expects($this->once())->method('setQuery')->with($query)->willReturnSelf();
		$db->expects($this->once())->method('execute')->willReturn(true);
		$db->expects($this->once())->method('getNumRows')->willReturn(count($records));
		$db->expects($records === [] ? $this->never() : $this->once())->method('loadObjectList')
			->willReturn(array_map(static fn (array $record): object => (object) $record, $records));
		$container = new Container();
		$container->set(DatabaseInterface::class, $db);
		$this->setJoomlaContainer($container);

		$this->assertSame($expected, ComponentbuilderHelper::getHelpUrl('compiler'));
		$this->assertSame(
			["a.admin_view = 'compiler'", 'a.location = 1', 'a.published = 1'],
			$conditions
		);
	}

	/**
	 * Cover matching membership, denied membership, general help and empty results.
	 *
	 * @return  array<string, array{array<int, int>, array<int, array<mixed>>, string|false}>
	 * @since   6.2.1
	 */
	public static function helpSelections(): array
	{
		$restricted = ['id' => 1, 'groups' => '[8]', 'target' => 1, 'type' => 3, 'article' => 0, 'url' => 'https://example.test/restricted'];
		$otherGroup = ['id' => 2, 'groups' => '[3]', 'target' => 1, 'type' => 3, 'article' => 0, 'url' => 'https://example.test/other-group'];
		$general = ['id' => 3, 'groups' => '[]', 'target' => 0, 'type' => 3, 'article' => 0, 'url' => 'https://example.test/general'];

		return [
			'matching membership after denied record' => [[2, 8], [$otherGroup, $restricted, $general], $restricted['url']],
			'denied membership' => [[2], [$restricted], false],
			'empty membership denies restricted help' => [[], [$restricted], false],
			'general fallback after denied record' => [[2], [$restricted, $general], $general['url']],
			'general help without group membership' => [[], [$general], $general['url']],
			'no configured help' => [[8], [], false],
		];
	}
}
