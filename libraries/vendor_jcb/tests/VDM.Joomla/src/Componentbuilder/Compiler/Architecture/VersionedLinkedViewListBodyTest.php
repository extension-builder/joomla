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
use VDM\Joomla\Componentbuilder\Compiler\Builder\Lists;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;


/**
 * Generated linked admin view table body contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedLinkedViewListBodyTest extends ArchitectureTestCase
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
	public function testAViewWithoutListFieldsHasNoBody(string $version, int $major): void
	{
		$subject = $this->listBody($version, ['lists' => new Lists()]);

		$this->assertSame('', $subject->get('article', 'articles', 'article'));
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
		$code = $this->listBody($version)->get('article', 'articles', 'article');

		$this->assertStringContainsString('$canCheckin = $user->authorise(', $code);

		if ($major === 3)
		{
			$this->assertStringContainsString(
				'___Power::getUser($item->checked_out);',
				$code
			);
			$this->assertStringNotContainsString('loadUserById', $code);

			return;
		}

		$this->assertStringContainsString('___Power::getContainer()->', $code);
		$this->assertStringContainsString(
			'loadUserById((int) ($item->checked_out ?? 0));',
			$code
		);
		$this->assertStringNotContainsString('::getUser($item->checked_out)', $code);
	}

	/**
	 * The four publish states render exactly as the legacy helper built them.
	 *
	 * The four blocks share one parameterised builder now, so this pins the
	 * complete markup rather than a sample of it.
	 *
	 * @param   int     $footable    The configured Footable release.
	 * @param   string  $dataValue   The sort-value attribute of that release.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('footableReleases')]
	public function testThePublishStatesMatchTheLegacyMarkup(int $footable, string $dataValue): void
	{
		$this->config()->set('footable_version', $footable);

		$code = $this->listBody('JoomlaSix')->get('article', 'articles', 'article');

		$states = [
			['1', '1', 'published', 'PUBLISHED'],
			['0', '2', 'inactive', 'INACTIVE'],
			['2', '3', 'archived', 'ARCHIVED'],
			['-2', '4', 'trashed', 'TRASHED'],
		];

		$expected = '';
		foreach ($states as $i => [$published, $sort, $state, $lang])
		{
			$key = 'COM_DEMO_' . $lang;
			$branch = $i === 0
				? "<?php if (\$item->published == {$published}): ?>"
				: "<?php elseif (\$item->published == {$published}): ?>";

			$expected .= PHP_EOL . Indent::_(2) . $branch;
			$expected .= PHP_EOL . Indent::_(3) . '<td class="center"  '
				. $dataValue . '="' . $sort . '">';
			$expected .= PHP_EOL . Indent::_(4)
				. '<span class="status-metro status-' . $state
				. '" title="<?php echo Text:' . ':_(' . "'" . $key . "'"
				. ');  ?>">';
			$expected .= PHP_EOL . Indent::_(5)
				. '<?php echo Joomla__' . '_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('
				. "'" . $key . "'" . '); ?>';
			$expected .= PHP_EOL . Indent::_(4) . '</span>';
			$expected .= PHP_EOL . Indent::_(3) . '</td>';
		}
		$expected .= PHP_EOL . Indent::_(2) . '<?php endif; ?>';

		$this->assertStringContainsString($expected, $code);
	}

	/**
	 * Provide the Footable releases and the sort attribute each one uses.
	 *
	 * @return  array<string, array{int,string}>
	 * @since   6.1.7
	 */
	public static function footableReleases(): array
	{
		return [
			'Footable 2' => [2, 'data-value'],
			'Footable 3' => [3, 'data-sort-value'],
		];
	}

	/**
	 * Only Footable 2 closes the table with a paging footer.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOnlyFootableTwoAddsThePagingFooter(): void
	{
		$subject = $this->listBody('JoomlaSix');

		$this->config()->set('footable_version', 2);
		$two = $subject->get('article', 'articles', 'article');
		$this->assertStringContainsString('<tfoot class="hide-if-no-paging">', $two);
		// one linked column, plus the published and id defaults
		$this->assertStringContainsString('<td colspan="3">', $two);

		$this->config()->set('footable_version', 3);
		$three = $subject->get('article', 'articles', 'article');
		$this->assertStringNotContainsString('<tfoot', $three);
	}

	/**
	 * Declared status and id fields are not rendered a second time.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testDeclaredStatusAndIdFieldsAreNotAddedTwice(): void
	{
		$fieldnames = new FieldNames();
		$fieldnames->set('article.published', 'published');
		$fieldnames->set('article.id', 'id');

		$code = $this->listBody('JoomlaSix', ['fieldnames' => $fieldnames])
			->get('article', 'articles', 'article');

		$this->assertStringNotContainsString('status-metro', $code);
		$this->assertStringNotContainsString('<?php echo $item->id; ?>', $code);
		$this->assertStringContainsString('<td colspan="1">', $code);
	}

	/**
	 * The body always closes its table and offers the no-results notice.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheBodyClosesTheTableAndOffersTheEmptyNotice(): void
	{
		$code = $this->listBody('JoomlaSix')->get('article', 'articles', 'article');

		$this->assertStringStartsWith(PHP_EOL . '<tbody>', $code);
		$this->assertStringContainsString('<?php foreach ($items as $i => $item): ?>', $code);
		$this->assertStringContainsString('JGLOBAL_NO_MATCHING_RESULTS', $code);
		$this->assertStringEndsWith(PHP_EOL . '<?php endif; ?>', $code);
	}

	/**
	 * Create the linked view list body of one target with real registries.
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
		$class = $this->targetClass(
			$version, 'LinkedView\\ListBody', ['JoomlaThree']
		);

		$lists = new Lists();
		$lists->set('articles', [
			['target' => 1, 'guid' => 'a', 'lang' => 'COM_DEMO_TITLE', 'link' => false],
		]);

		return $this->renderer($class, $overrides + [
			'lists' => $lists,
			'donotescape' => new DoNotEscape(),
			'fieldnames' => new FieldNames(),
		]);
	}
}
