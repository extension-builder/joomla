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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\LinkedView\ListHead;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Component;


/**
 * Linked view assembly contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedLinkedViewBuilderTest extends ArchitectureTestCase
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
	 * Each target puts the input object in scope its own way.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheInputAcquisitionFollowsTheTarget(string $version, int $major): void
	{
		$header = $this->build($version)['header'];

		if ($major === 3)
		{
			$this->assertStringContainsString(
				'___Power::getApplication()->input;', $header
			);
			$this->assertStringNotContainsString('$displayData->input', $header);

			return;
		}

		$this->assertStringContainsString(
			'$jinput = $displayData->input ?? (method_exists($app, \'getInput\')'
			. ' ? $app->getInput() : $app->input);',
			$header
		);
		$this->assertStringNotContainsString('___Power::getApplication()->input;', $header);
	}

	/**
	 * A new record link uses the edit task on Joomla 3 and add everywhere else.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheNewRecordTaskFollowsTheTarget(string $version, int $major): void
	{
		// 2 asks for both the create new and the create new and close link
		$header = $this->build($version, 'article', 2)['header'];

		$task = $major === 3 ? 'comment.edit' : 'comment.add';

		$this->assertStringContainsString('$new = "index.php?option=com_demo', $header);
		$this->assertStringContainsString('$close_new = "index.php?option=com_demo', $header);
		$this->assertStringContainsString('&task=' . $task . '" . $ref;', $header);
		$this->assertStringContainsString('&task=' . $task . '";', $header);
	}

	/**
	 * Only targets that support guid keys seed a new record from the guid.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testOnlyGuidCapableTargetsSeedFromTheGuid(string $version, int $major): void
	{
		$header = $this->build($version, 'guid')['header'];

		if ($major < 5)
		{
			$this->assertStringContainsString('$ref = ($id) ? "&ref=article&refid=" . $id', $header);
			$this->assertStringNotContainsString('init_defaults', $header);

			return;
		}

		$this->assertStringContainsString('$guid = $displayData->item->guid ?? null;', $header);
		$this->assertStringContainsString('&init_defaults=', $header);
		$this->assertStringNotContainsString('&refid=', $header);
	}

	/**
	 * A non guid key always passes a referring id, on every target.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAPlainKeyAlwaysPassesAReferringId(string $version, int $major): void
	{
		$header = $this->build($version)['header'];

		$this->assertStringContainsString('$ref = ($id) ? "&ref=article&refid=" . $id', $header);
		$this->assertStringNotContainsString('init_defaults', $header);
	}

	/**
	 * The edit and return URLs are built from the linked view, not the parent.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheEditUrlPointsAtTheLinkedView(): void
	{
		$header = $this->build('JoomlaSix')['header'];

		$this->assertStringContainsString(
			'$edit = "index.php?option=com_demo&view=comments&task=comment.edit";',
			$header
		);
		$this->assertStringContainsString(
			'$return = ($id) ? "index.php?option=com_demo&view=article&layout=edit&id=" . $id : "";',
			$header
		);
	}

	/**
	 * Without a new button no create links and no action object are loaded.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWithoutANewButtonNoCreateLinksAreBuilt(): void
	{
		$header = $this->build('JoomlaSix', 'article', 0)['header'];

		$this->assertStringNotContainsString('$new = ', $header);
		$this->assertStringNotContainsString('$close_new = ', $header);
		$this->assertStringNotContainsString('$can = ', $header);
	}

	/**
	 * The parent value is carried into a global the linked getter reads.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheParentValueIsCarriedIntoAGlobal(): void
	{
		$built = $this->build('JoomlaSix');

		$this->assertMatchesRegularExpression(
			'/\$this->id\w+ = \$item->article;/',
			$built['global']
		);
		$this->assertStringContainsString(
			"\$this->comments = \$this->get('Comments');",
			$built['items']
		);
	}

	/**
	 * An OR parent key carries one global per column it names.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnOrParentKeyCarriesOneGlobalPerColumn(): void
	{
		$built = $this->build('JoomlaSix', 'article-OR>author');

		$this->assertStringContainsString('= $item->article;', $built['global']);
		$this->assertStringContainsString('= $item->author;', $built['global']);
	}

	/**
	 * An unknown linked view guid leaves an error marker behind.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnUnknownLinkedViewLeavesAnErrorMarker(): void
	{
		$contentmulti = new ContentMulti();
		$component = $this->component();
		$component->set('admin_views', []);

		$this->subject('JoomlaSix', [
			'contentmulti' => $contentmulti,
			'component' => $component,
		])->set($this->args());

		$this->assertSame(
			'oops! error.....',
			$contentmulti->get('article_comments|LAYOUTITEMSTABLE')
		);
		$this->assertSame('', $contentmulti->get('article_comments|LAYOUTITEMSHEADER'));
	}

	/**
	 * Build one linked view of one target and return what it wrote.
	 *
	 * @param   string  $version       Target namespace segment.
	 * @param   string  $parentKey     The key tying the two views together.
	 * @param   int     $addNewButton  The new record button mode.
	 *
	 * @return  array<string, string>
	 * @since   6.1.7
	 */
	private function build(string $version, string $parentKey = 'article',
		int $addNewButton = 1): array
	{
		$contentmulti = new ContentMulti();

		$this->subject($version, ['contentmulti' => $contentmulti])
			->set($this->args($parentKey, $addNewButton));

		return [
			'header' => (string) $contentmulti->get('article_comments|LAYOUTITEMSHEADER'),
			'table' => (string) $contentmulti->get('article_comments|LAYOUTITEMSTABLE'),
			'items' => (string) $contentmulti->get('article|LINKEDVIEWITEMS'),
			'global' => (string) $contentmulti->get('article|LINKEDVIEWGLOBAL'),
		];
	}

	/**
	 * Create the linked view builder of one target.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   array   $overrides  Constructor dependency overrides.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function subject(string $version, array $overrides = []): object
	{
		// Joomla 3 has its own input and task, and guid seeding only
		// arrived in Joomla 5, so Joomla 4 carries its own class too
		$class = $this->targetClass(
			$version, 'LinkedView\\Builder', ['JoomlaThree', 'JoomlaFour']
		);

		return $this->renderer($class, $overrides + [
			'component' => $this->component(),
			// the table head is final, so it is built for real
			'listhead' => $this->renderer(ListHead::class),
		]);
	}

	/**
	 * Create a component registry carrying one linked admin view.
	 *
	 * @return  Component
	 * @since   6.1.7
	 */
	private function component(): Component
	{
		$component = $this->renderer(Component::class);
		$component->set('admin_views', [
			[
				'adminview' => 'comment-guid',
				'settings' => (object) [
					'name_single_code' => 'comment',
					'name_list_code' => 'comments',
				],
			],
		]);

		return $component;
	}

	/**
	 * Build the linked view definition the edit body queues.
	 *
	 * @param   string  $parentKey     The key tying the two views together.
	 * @param   int     $addNewButton  The new record button mode.
	 *
	 * @return  array<string, mixed>
	 * @since   6.1.7
	 */
	private function args(string $parentKey = 'article', int $addNewButton = 1): array
	{
		return [
			'viewGuid' => 'comment-guid',
			'nameSingleCode' => 'article',
			'codeName' => 'comments',
			'layoutCodeName' => 'comments',
			'key' => 'id',
			'parentKey' => $parentKey,
			'addNewButon' => $addNewButton,
		];
	}
}
