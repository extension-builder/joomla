<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    3rd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Resolver;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Diff;
use VDM\Tests\Support\TestCase;


/**
 * What one text would have to gain and lose to become another.
 *
 * @since 6.2.0
 */
#[CoversClass(Diff::class)]
final class DiffTest extends TestCase
{
	/**
	 * The comparison under test.
	 *
	 * @var    Diff
	 * @since  6.2.0
	 */
	private Diff $diff;

	/**
	 * Start every test from a fresh comparison.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->diff = new Diff();
	}

	/**
	 * Two texts that read the same have nothing between them.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTextsThatReadTheSameHaveNothingBetweenThem(): void
	{
		$compared = $this->diff->compare("one\ntwo\nthree", "one\ntwo\nthree");

		$this->assertSame(0, $compared['additions']);
		$this->assertSame(0, $compared['deletions']);
		$this->assertSame([], $compared['hunks'], 'Nothing changed, so there is nothing to show.');
	}

	/**
	 * A value that was never set is no lines at all, never one empty line.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAnEmptyTextIsNoLines(): void
	{
		$this->assertSame(
			['additions' => 2, 'deletions' => 0],
			$this->diff->counts('', "one\ntwo"),
			'A column that was empty gains every line it now has.'
		);
		$this->assertSame(
			['additions' => 0, 'deletions' => 2],
			$this->diff->counts("one\ntwo", ''),
			'A column that is emptied loses every line it had.'
		);
		$this->assertSame(
			['additions' => 0, 'deletions' => 0],
			$this->diff->counts('', ''),
			'Nothing to nothing is no change, not an empty line added.'
		);
	}

	/**
	 * One changed line counts as one gained and one lost.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAChangedLineIsOneGainedAndOneLost(): void
	{
		$compared = $this->diff->compare("one\ntwo\nthree", "one\ndeux\nthree");

		$this->assertSame(1, $compared['additions']);
		$this->assertSame(1, $compared['deletions']);
		$this->assertCount(1, $compared['hunks']);

		$lines = $compared['hunks'][0]['lines'];

		$this->assertSame(['keep', 'remove', 'add', 'keep'], array_column($lines, 'op'));
		$this->assertSame(['one', 'two', 'deux', 'three'], array_column($lines, 'text'));
		$this->assertSame(2, $lines[1]['old'], 'The line it was is numbered as it stands.');
		$this->assertNull($lines[1]['new']);
		$this->assertSame(2, $lines[2]['new'], 'The line it becomes is numbered as it would be.');
		$this->assertNull($lines[2]['old']);
	}

	/**
	 * The lines around a change are shown, and the rest of a long text is not.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testOnlyTheLinesAroundAChangeAreShown(): void
	{
		$before = implode("\n", array_map(static fn (int $line): string => 'line ' . $line, range(1, 40)));
		$after = str_replace('line 20', 'line twenty', $before);
		$compared = $this->diff->compare($before, $after);

		$this->assertSame(1, $compared['additions']);
		$this->assertSame(1, $compared['deletions']);
		$this->assertCount(1, $compared['hunks']);

		$hunk = $compared['hunks'][0];

		$this->assertSame(17, $hunk['old'], 'The hunk opens three lines before the change.');
		$this->assertCount(8, $hunk['lines'], 'Three lines of context on each side of the two changed ones.');
		$this->assertSame('line 17', $hunk['lines'][0]['text']);
		$this->assertSame('line 23', $hunk['lines'][7]['text']);
	}

	/**
	 * Two changes far apart are two hunks; two changes close together are one.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testChangesFarApartAreShownSeparately(): void
	{
		$before = implode("\n", array_map(static fn (int $line): string => 'line ' . $line, range(1, 60)));
		$far = str_replace(['line 10', 'line 50'], ['ten', 'fifty'], $before);
		$near = str_replace(['line 10', 'line 13'], ['ten', 'thirteen'], $before);

		$this->assertCount(2, $this->diff->compare($before, $far)['hunks']);
		$this->assertCount(
			1,
			$this->diff->compare($before, $near)['hunks'],
			'Changes within reach of each other are read together, in one hunk.'
		);
	}

	/**
	 * Two changes whose whole gap would be shown as context anyway are one hunk.
	 *
	 * A unified diff joins two hunks when the unchanged lines between them are
	 * no more than twice the context, so no line is ever shown twice and no
	 * gap is marked where nothing was left out.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testChangesWithinReachOfEachOtherAreOneHunk(): void
	{
		$before = implode("\n", array_map(static fn (int $line): string => 'line ' . $line, range(1, 40)));

		foreach ([4, 5, 6] as $gap)
		{
			$after = str_replace(['line 10', 'line ' . (11 + $gap)], ['ten', 'later'], $before);
			$hunks = $this->diff->compare($before, $after)['hunks'];

			$this->assertCount(1, $hunks, 'Changes ' . $gap . ' lines apart are read together.');
			$this->assertSame(7, $hunks[0]['old']);
			$this->assertSame('line ' . (14 + $gap), $hunks[0]['lines'][count($hunks[0]['lines']) - 1]['text']);
		}

		$after = str_replace(['line 10', 'line 18'], ['ten', 'later'], $before);
		$hunks = $this->diff->compare($before, $after)['hunks'];

		$this->assertCount(2, $hunks, 'Changes seven lines apart leave a line out between them.');
		$this->assertSame('line 13', $hunks[0]['lines'][count($hunks[0]['lines']) - 1]['text']);
		$this->assertSame('line 15', $hunks[1]['lines'][0]['text'], 'The line left out is shown in neither hunk.');
	}

	/**
	 * Lines added at the end of a text are added, not read as a rewrite.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testLinesAppendedAreAddedNotRewritten(): void
	{
		$compared = $this->diff->compare("one\ntwo", "one\ntwo\nthree\nfour");

		$this->assertSame(2, $compared['additions']);
		$this->assertSame(0, $compared['deletions'], 'Nothing was taken away, so nothing is reported as lost.');
		$this->assertSame(
			['keep', 'keep', 'add', 'add'],
			array_column($compared['hunks'][0]['lines'], 'op')
		);
	}

	/**
	 * A text too large to line up exactly is reported as replaced, still truthfully.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAVeryLargeChangeIsReportedAsReplaced(): void
	{
		$before = implode("\n", array_map(static fn (int $line): string => 'a' . $line, range(1, 1600)));
		$after = implode("\n", array_map(static fn (int $line): string => 'b' . $line, range(1, 1600)));
		$compared = $this->diff->compare($before, $after);

		$this->assertSame(1600, $compared['additions']);
		$this->assertSame(1600, $compared['deletions']);
		$this->assertNotSame([], $compared['hunks']);
	}

	/**
	 * Windows line endings are read as the line breaks they are.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testWindowsLineEndingsAreReadAsLineBreaks(): void
	{
		$this->assertSame(
			['additions' => 0, 'deletions' => 0],
			$this->diff->counts("one\r\ntwo", "one\ntwo"),
			'A dump laid out with carriage returns says the same thing as one without.'
		);
	}
}
