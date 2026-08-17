<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DoNotEscape;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListFieldClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Lists;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\AdminViews\ListItemBuilderInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\AdminViews\ListLinkInterface;


/**
 * Generated admin list view table body contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedAdminViewsListBodyTest extends ArchitectureTestCase
{
	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.7
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
	 * A view with no list fields renders no table body.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewWithoutListFieldsRendersNothing(string $version, int $major): void
	{
		$subject = $this->listBody($version, ['lists' => new Lists()]);

		$this->assertSame('', $subject->get('article', 'articles'));
	}

	/**
	 * Each target looks the checked out user up its own way.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheCheckedOutUserLookupFollowsTheTarget(string $version, int $major): void
	{
		$code = $this->body($version);

		if ($major === 3)
		{
			$this->assertStringContainsString(
				'___Power::getUser($item->checked_out);', $code
			);
			$this->assertStringNotContainsString('loadUserById', $code);

			return;
		}

		$this->assertStringContainsString('___Power::getContainer()->', $code);
		$this->assertStringContainsString(
			'loadUserById((int) ($item->checked_out ?? 0));', $code
		);
		$this->assertStringNotContainsString('::getUser($item->checked_out);', $code);
	}

	/**
	 * Only targets with a modal admin list guard their permission tests on it.
	 *
	 * The three guards are assembled from a shared prefix and the target's
	 * modal guard, so these assertions pin the exact strings the legacy
	 * helper wrote out in full.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheModalGuardFollowsTheTarget(string $version, int $major): void
	{
		$code = $this->body($version);

		if ($major === 3)
		{
			$this->assertStringContainsString('<?php if ($canDo->get(\'', $code);
			$this->assertStringNotContainsString('$this->isModal', $code);

			return;
		}

		$this->assertStringContainsString(
			'<?php if (!$this->isModal && $canDo->get(\'', $code
		);
	}

	/**
	 * The sorting, selection and publish guards keep their own suffixes.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testEachGuardKeepsItsOwnSuffix(string $version, int $major): void
	{
		$code = $this->body($version);

		// the sorting and selection guards close without a space
		$this->assertStringContainsString('\')): ?>', $code);
		// the publish guard closes with one, exactly as the legacy helper wrote it
		$this->assertStringContainsString('\')) : ?>', $code);
	}

	/**
	 * The row opens the item loop and closes it once.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheBodyLoopsOverEveryItemOnce(): void
	{
		$code = $this->body('JoomlaSix');

		$this->assertStringContainsString(
			'<?php foreach ($this->items as $i => $item): ?>', $code
		);
		$this->assertStringContainsString('<tr class="row<?php echo $i % 2; ?>">', $code);
		$this->assertSame(1, substr_count($code, '<?php endforeach; ?>'));
		$this->assertStringContainsString(
			"\$canCheckin = \$this->user->authorise('core.manage', 'com_checkin')",
			$code
		);
	}

	/**
	 * The ordering column carries the drag handle and the disabled tooltip.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheOrderingColumnCarriesTheDragHandle(): void
	{
		$code = $this->body('JoomlaSix');

		$this->assertStringContainsString('<td class="order nowrap center hidden-phone">', $code);
		$this->assertStringContainsString('if (!$this->saveOrder)', $code);
		$this->assertStringContainsString("Html::tooltipText('JORDERINGDISABLED');", $code);
		$this->assertStringContainsString('<i class="icon-menu"></i>', $code);
		$this->assertStringContainsString('name="order[]"', $code);
	}

	/**
	 * An overridden ordering field drops the ordering column entirely.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnOverriddenOrderingFieldDropsTheColumn(): void
	{
		$fieldnames = new FieldNames();
		$fieldnames->set('article.ordering', 'ordering');

		$code = $this->body('JoomlaSix', ['fieldnames' => $fieldnames]);

		$this->assertStringNotContainsString('<td class="order nowrap center hidden-phone">', $code);
		$this->assertStringNotContainsString('JORDERINGDISABLED', $code);
	}

	/**
	 * The publish and id columns are dropped when the view overrides them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOverriddenPublishAndIdFieldsDropTheirColumns(): void
	{
		$fieldnames = new FieldNames();
		$fieldnames->set('article.published', 'published');
		$fieldnames->set('article.id', 'id');

		$code = $this->body('JoomlaSix', ['fieldnames' => $fieldnames]);

		$this->assertStringNotContainsString("Html::_('jgrid.published'", $code);
		$this->assertStringNotContainsString('<?php echo $item->id; ?>', $code);
	}

	/**
	 * By default the publish and id columns are both rendered.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testThePublishAndIdColumnsAreRenderedByDefault(): void
	{
		$code = $this->body('JoomlaSix');

		$this->assertStringContainsString(
			"Html::_('jgrid.published', \$item->published, \$i, 'articles.', true, 'cb');",
			$code
		);
		$this->assertStringContainsString('<?php echo $item->id; ?>', $code);
	}

	/**
	 * Only columns that target the admin list are rendered.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOnlyAdminListColumnsAreRendered(): void
	{
		$lists = new Lists();
		$lists->set('articles', [
			// 1 and 3 target the admin list, 4 targets the linked list only
			['target' => 1, 'code' => 'title'],
			['target' => 4, 'code' => 'linked_only'],
			['target' => 3, 'code' => 'state'],
		]);

		$listfieldclass = new ListFieldClass();
		$listfieldclass->set('articles.title', 'title-class');
		$listfieldclass->set('articles.linked_only', 'linked-class');
		$listfieldclass->set('articles.state', 'state-class');

		$code = $this->body('JoomlaSix', [
			'lists' => $lists,
			'listfieldclass' => $listfieldclass,
		]);

		$this->assertStringContainsString('<td class="title-class">', $code);
		$this->assertStringContainsString('<td class="state-class">', $code);
		$this->assertStringNotContainsString('linked-class', $code);
	}

	/**
	 * The custom admin view buttons are added to the first column only.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheCustomButtonsAreAddedOnlyOnce(): void
	{
		$lists = new Lists();
		$lists->set('articles', [
			['target' => 1, 'code' => 'title'],
			['target' => 1, 'code' => 'state'],
		]);

		$listlink = $this->createStub(ListLinkInterface::class);
		$listlink->method('getButtons')->willReturn('<!--BUTTONS-->');

		$code = $this->body('JoomlaSix', [
			'lists' => $lists,
			'listlink' => $listlink,
		]);

		$this->assertSame(1, substr_count($code, '<!--BUTTONS-->'));
	}

	/**
	 * A view that opts out of escaping passes that on to the item builder.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheDoNotEscapeFlagReachesTheItemBuilder(): void
	{
		$donotescape = new DoNotEscape();
		$donotescape->set('articles', ['title']);

		$listitembuilder = $this->createMock(ListItemBuilderInterface::class);
		$listitembuilder->expects($this->once())
			->method('get')
			->with(
				$this->anything(), 'article', 'articles', 'hidden-phone', true
			)
			->willReturn('<!--ITEM-->');

		$code = $this->body('JoomlaSix', [
			'donotescape' => $donotescape,
			'listitembuilder' => $listitembuilder,
		]);

		$this->assertStringContainsString('<!--ITEM-->', $code);
	}

	/**
	 * Build the table body of one target.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   array   $overrides  Constructor dependency overrides.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function body(string $version, array $overrides = []): string
	{
		return $this->listBody($version, $overrides)->get('article', 'articles');
	}

	/**
	 * Create the table body builder of one target with real registries.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   array   $overrides  Constructor dependency overrides.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function listBody(string $version, array $overrides = []): object
	{
		// only Joomla 3 loads the checked out user from the global factory
		// and carries no modal guard on its permission tests
		$class = $this->targetClass(
			$version, 'AdminViews\\ListBody', ['JoomlaThree']
		);

		$lists = new Lists();
		$lists->set('articles', [
			['target' => 1, 'code' => 'title'],
		]);

		$listlink = $this->createStub(ListLinkInterface::class);
		$listlink->method('getButtons')->willReturn('');

		return $this->renderer($class, $overrides + [
			'permission' => $this->permission(),
			'lists' => $lists,
			'listlink' => $listlink,
			'listfieldclass' => new ListFieldClass(),
			'donotescape' => new DoNotEscape(),
			'fieldnames' => new FieldNames(),
		]);
	}
}
