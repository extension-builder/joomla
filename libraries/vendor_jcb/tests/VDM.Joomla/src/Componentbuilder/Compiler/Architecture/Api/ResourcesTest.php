<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    2nd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Resources;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The API resources of a component and their names.
 *
 * @since 6.1.7
 */
#[CoversClass(Resources::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ResourcesTest extends ArchitectureTestCase
{
	/**
	 * An admin view with an API is a resource named by its list code.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAdminViewWithAnApiIsNamedByItsListCode(): void
	{
		$subject = $this->subject();
		$entries = $subject->map([$this->admin('truck', 'trucks', 2)]);

		$this->assertCount(1, $entries);
		$this->assertSame('admin', $entries[0]['area']);
		$this->assertSame('truck', $entries[0]['code']);
		$this->assertSame('truck', $entries[0]['single']);
		$this->assertSame('trucks', $entries[0]['name']);
		$this->assertTrue($entries[0]['item']);
		$this->assertTrue($entries[0]['list']);
		$this->assertFalse($entries[0]['public']);
		$this->assertSame($entries[0], $subject->get('admin', 'truck'));
		$this->assertSame('trucks', $subject->name('admin', 'truck'));
		$this->assertTrue($subject->mapped());
	}

	/**
	 * The list and item options keep their own resource.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheListAndItemOptionsKeepTheirOwnResource(): void
	{
		$subject = $this->subject();

		$list = $subject->map([$this->admin('truck', 'trucks', 1)])[0];
		$item = $subject->map([$this->admin('truck', 'trucks', 3)])[0];

		$this->assertTrue($list['list']);
		$this->assertFalse($list['item']);
		$this->assertFalse($item['list']);
		$this->assertTrue($item['item']);
	}

	/**
	 * An admin view without an API is no resource but reserves its codes.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAdminViewWithoutAnApiReservesItsCodesOnly(): void
	{
		$subject = $this->subject();
		$entries = $subject->map(
			[$this->admin('truck', 'trucks', 0), $this->admin('note', 'notes', 2)],
			[],
			[$this->dynamic('truck', 1), $this->dynamic('trucks', 2)]
		);

		$this->assertSame(['notes', 'site_truck', 'site_trucks'], array_column($entries, 'name'));
		$this->assertNull($subject->get('admin', 'truck'));
		$this->assertSame([], $subject->warnings());
	}

	/**
	 * Without an admin API the other views get no resource.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWithoutAnAdminApiTheOtherViewsGetNoResource(): void
	{
		$subject = $this->subject();

		$this->assertSame([], $subject->map([$this->admin('truck', 'trucks', 0)], [$this->dynamic('report', 2)], [$this->dynamic('page', 1)]));
		$this->assertSame([], $subject->map([], [$this->dynamic('report', 2)], [$this->dynamic('page', 1)]));
		$this->assertNull($subject->name('site', 'page'));
		$this->assertTrue($subject->enabled(true));
		$this->assertFalse($subject->enabled(false));
	}

	/**
	 * A custom admin view takes its code and a site view follows.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomAdminViewTakesItsCodeAndASiteViewFollows(): void
	{
		$subject = $this->subject();
		$entries = $subject->map(
			[$this->admin('truck', 'trucks', 2)],
			[$this->dynamic('report', 2, ['access' => 1])],
			[$this->dynamic('page', 1, ['access' => 0, 'public_access' => 1])]
		);

		$this->assertSame(['trucks', 'report', 'page'], array_column($entries, 'name'));
		$this->assertSame('custom_admin', $entries[1]['area']);
		$this->assertTrue($entries[1]['list']);
		$this->assertTrue($entries[1]['access']);
		$this->assertFalse($entries[1]['public']);
		$this->assertSame('site', $entries[2]['area']);
		$this->assertTrue($entries[2]['item']);
		$this->assertFalse($entries[2]['access']);
		$this->assertTrue($entries[2]['public']);
	}

	/**
	 * A custom admin view colliding with an admin view is skipped with a warning.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACollidingCustomAdminViewIsSkippedWithAWarning(): void
	{
		$subject = $this->subject();
		$entries = $subject->map([$this->admin('truck', 'trucks', 2)], [$this->dynamic('trucks', 2)]);

		$this->assertSame(['trucks'], array_column($entries, 'name'));
		$this->assertNull($subject->get('custom_admin', 'trucks'));
		$this->assertCount(1, $subject->warnings());
		$this->assertStringContainsString('custom admin view <b>trucks</b>', $subject->warnings()[0]);
		$this->assertStringContainsString('admin view trucks', $subject->warnings()[0]);
		$this->assertStringContainsString('serious collision', $subject->warnings()[0]);
	}

	/**
	 * A colliding site view takes the site prefix, and is skipped when that collides too.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACollidingSiteViewTakesThePrefixOrIsSkipped(): void
	{
		$subject = $this->subject();
		$entries = $subject->map(
			[$this->admin('truck', 'trucks', 2), $this->admin('page', 'site_page', 0)],
			[$this->dynamic('report', 2)],
			[$this->dynamic('truck', 1), $this->dynamic('report', 1), $this->dynamic('page', 2), $this->dynamic('note', 1)]
		);

		$this->assertSame(['trucks', 'report', 'site_truck', 'site_report', 'note'], array_column($entries, 'name'));
		$this->assertSame('site_truck', $subject->name('site', 'truck'));
		$this->assertSame('site_report', $subject->name('site', 'report'));
		$this->assertSame('note', $subject->name('site', 'note'));
		$this->assertNull($subject->name('site', 'page'));
		$this->assertCount(1, $subject->warnings());
		$this->assertStringContainsString('site view <b>page</b>', $subject->warnings()[0]);
		$this->assertStringContainsString('<b>site_page</b> is taken by the admin view site_page', $subject->warnings()[0]);
	}

	/**
	 * A view with a custom SQL get, a custom get type or no code gets no resource.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewTheCompilerCannotDescribeGetsNoResource(): void
	{
		$subject = $this->subject();

		$sql = $this->dynamic('raw', 2);
		$sql['settings']->main_get->main_source = 3;
		$custom = $this->dynamic('helper', 3);
		$blank = $this->dynamic('', 1);

		$entries = $subject->map([$this->admin('truck', 'trucks', 2)], [], [$sql, $custom, $blank, 'not a view', ['settings' => 'no object']]);

		$this->assertSame(['trucks'], array_column($entries, 'name'));
		$this->assertSame([], $subject->warnings());
	}

	/**
	 * Below Joomla 4 nothing is a resource.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testBelowJoomlaFourNothingIsAResource(): void
	{
		$this->config()->set('joomla_version', 3);

		$this->assertSame([], $this->subject()->map([$this->admin('truck', 'trucks', 2)], [], [$this->dynamic('page', 1)]));
	}

	/**
	 * A new map forgets the last one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testANewMapForgetsTheLastOne(): void
	{
		$subject = $this->subject();

		$this->assertFalse($subject->mapped());
		$subject->map([$this->admin('truck', 'trucks', 2)], [$this->dynamic('trucks', 2)]);
		$this->assertCount(1, $subject->warnings());

		$subject->map([$this->admin('note', 'notes', 2)]);

		$this->assertSame([], $subject->warnings());
		$this->assertNull($subject->get('admin', 'truck'));
		$this->assertSame(['notes'], array_column($subject->all(), 'name'));
	}

	/**
	 * The resources map.
	 *
	 * @return  Resources
	 * @since   6.1.7
	 */
	private function subject(): Resources
	{
		return new Resources($this->config());
	}

	/**
	 * An admin view link.
	 *
	 * @param   string  $single  The single code.
	 * @param   string  $list    The list code.
	 * @param   int     $api     The add_api option.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function admin(string $single, string $list, int $api): array
	{
		return [
			'add_api' => $api,
			'settings' => (object) [
				'name_single' => ucfirst($single),
				'name_list' => ucfirst($list),
				'name_single_code' => $single,
				'name_list_code' => $list,
			],
		];
	}

	/**
	 * A site view or custom admin view link.
	 *
	 * @param   string  $code  The view code.
	 * @param   int     $type  The main get type.
	 * @param   array   $link  The link flags.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function dynamic(string $code, int $type, array $link = []): array
	{
		return $link + [
			'settings' => (object) [
				'code' => $code,
				'Code' => ucfirst($code),
				'main_get' => (object) ['gettype' => $type, 'main_source' => 1],
			],
		];
	}
}
