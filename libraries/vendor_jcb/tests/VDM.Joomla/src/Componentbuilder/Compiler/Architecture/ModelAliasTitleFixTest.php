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


use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\AliasTitleFix;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\Model\AliasTitleFix as J6AliasTitleFix;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Alias;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CategoryCode;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomAlias;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Title;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModel;


/**
 * Generated model title and alias uniqueness fix contracts.
 *
 * Joomla 6 reads Unicode-slug configuration from the active application;
 * Joomla 3, 4, and 5 retain their existing generated configuration access.
 *
 * @since  6.1.7
 */
#[CoversClass(AliasTitleFix::class)]
#[CoversClass(J6AliasTitleFix::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ModelAliasTitleFixTest extends ArchitectureTestCase
{
	/**
	 * Supported targets and their configuration access.
	 *
	 * @return  array<string, array{string, string}>
	 * @since   6.2.0
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => ['JoomlaThree', "getConfig()->get('unicodeslugs')"],
			'Joomla 4' => ['JoomlaFour', "getConfig()->get('unicodeslugs')"],
			'Joomla 5' => ['JoomlaFive', "getConfig()->get('unicodeslugs')"],
			'Joomla 6' => ['JoomlaSix', "getApplication()->get('unicodeslugs')"],
		];
	}

	/**
	 * Alias generation reads the target's configuration and keeps both slug paths.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   string  $lookup   Expected application or factory configuration lookup.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('versions')]
	public function testUnicodeSlugConfigurationFollowsTheTarget(string $version,
		string $lookup): void
	{
		$code = $this->fix(true, true, null, $version);

		$this->assertStringContainsString(
			"\t\t\t\tif (Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::"
			. $lookup . ' == 1)', $code
		);
		$this->assertStringContainsString("OutputFilter::stringURLUnicodeSlug(\$data['title']);", $code);
		$this->assertStringContainsString("OutputFilter::stringURLSafe(\$data['title']);", $code);

		if ($version === 'JoomlaSix')
		{
			$this->assertStringNotContainsString('::getConfig()', $code);
		}
	}

	/**
	 * Joomla 6 changes only the configuration lookup in the complete alias fix.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testJoomlaSixPreservesTheRestOfTheAliasFix(): void
	{
		$shared = $this->fix();

		$this->assertSame($shared, $this->fix(true, true, null, 'JoomlaThree'));
		$this->assertSame($shared, $this->fix(true, true, null, 'JoomlaFour'));
		$this->assertSame(
			str_replace("::getConfig()->get('unicodeslugs')", "::getApplication()->get('unicodeslugs')", $shared),
			$this->fix(true, true, null, 'JoomlaSix')
		);
	}

	/**
	 * The unique field fix is emitted even when there is no alias.
	 *
	 * It sits outside the alias and title guard, so every view carrying
	 * unique fields gets it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheUniqueFieldFixIsAlwaysEmitted(): void
	{
		foreach ([$this->fix(false, true), $this->fix(true, false)] as $code)
		{
			$this->assertStringContainsString(
				'// Alter the unique field for save as copy', $code
			);
			$this->assertStringContainsString(
				'$uniqueFields = $this->getUniqueFields();', $code
			);
			// but nothing for the title or alias
			$this->assertStringNotContainsString('// Alter the title for save as copy', $code);
		}
	}

	/**
	 * Saving as a copy gives the record a fresh title and alias.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testSaveAsCopyGetsAFreshTitleAndAlias(): void
	{
		$code = $this->fix();

		$this->assertStringContainsString('// Alter the title for save as copy', $code);
		$this->assertStringContainsString(
			"if (\$input->get('task') === 'save2copy')", $code
		);
		$this->assertStringContainsString('$origTable = clone $this->getTable();', $code);
		$this->assertStringContainsString(
			"list(\$title, \$alias) = \$this->_generateNewTitle(\$data['alias'], \$data['title']);",
			$code
		);
		$this->assertStringContainsString("\$data['published'] = 0;", $code);
	}

	/**
	 * An empty alias is filled from the title, honouring unicode slugs.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnEmptyAliasIsFilledFromTheTitle(): void
	{
		$code = $this->fix();

		$this->assertStringContainsString(
			'// Automatic handling of alias for empty fields', $code
		);
		$this->assertStringContainsString(
			"OutputFilter::stringURLUnicodeSlug(\$data['title']);", $code
		);
		$this->assertStringContainsString(
			"OutputFilter::stringURLSafe(\$data['title']);", $code
		);
	}

	/**
	 * An uncategorised view checks uniqueness on the alias alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnUncategorisedViewChecksTheAliasAlone(): void
	{
		$code = $this->fix();

		$this->assertStringContainsString(
			"\$table->load(array('alias' => \$data['alias']))", $code
		);
		$this->assertStringNotContainsString("'catid' => \$data['catid']", $code);
	}

	/**
	 * A categorised view scopes uniqueness to the record's category.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACategorisedViewScopesToTheCategory(): void
	{
		$code = $this->fix(true, true, 'catid');

		$this->assertStringContainsString(
			"\$table->load(['alias' => \$data['alias'], 'catid' => \$data['catid']])", $code
		);
		$this->assertStringContainsString(
			"\$this->generateNewTitle(\$data['catid'], \$data['alias'], \$data['title']);",
			$code
		);
		// the uncategorised helper is not used when a category scopes the view
		$this->assertStringNotContainsString(
			"\$this->_generateNewTitle(\$data['alias']);", $code
		);
	}

	/**
	 * Build the fix for one view shape.
	 *
	 * @param   bool         $withAlias  Whether the view has an alias field.
	 * @param   bool         $withTitle  Whether the view has a title field.
	 * @param   string|null  $category   The category code, when categorised.
	 * @param   string       $version    Target namespace segment.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function fix(bool $withAlias = true, bool $withTitle = true,
		?string $category = null, string $version = 'JoomlaFive'): string
	{
		$alias = new Alias();

		if ($withAlias)
		{
			$alias->set('article', 'alias');
		}

		$title = new Title();

		if ($withTitle)
		{
			$title->set('article', 'title');
		}

		$categorycode = new CategoryCode();

		if ($category !== null)
		{
			$categorycode->set('article.code', $category);
		}

		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');

		$targets = ['JoomlaThree' => 3, 'JoomlaFour' => 4, 'JoomlaFive' => 5, 'JoomlaSix' => 6];
		$this->config()->set('joomla_version', $targets[$version]);

		$container = new Container();
		$container->share('Config', $this->config(), true);
		$container->share('Compiler.Builder.Alias', $alias, true);
		$container->share('Compiler.Builder.Title', $title, true);
		$container->share('Compiler.Builder.Custom.Alias', new CustomAlias(), true);
		$container->share('Compiler.Builder.Category.Code', $categorycode, true);
		$container->share('Compiler.Builder.Content.One', $contentone, true);
		(new ArchitectureModel())->register($container);

		$subject = $container->get('Architecture.Model.AliasTitleFix');

		$this->assertSame($subject, $container->get(AliasTitleFix::class));
		$this->assertSame($subject, $container->get('Architecture.Model.AliasTitleFix'));

		return $subject->get('article');
	}
}
