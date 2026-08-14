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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler;


use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\External;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor as JoomlaPowerExtractor;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Language\Extractor as LanguageExtractor;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Power\Extractor as PowerExtractor;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Dynamic custom-code expansion and cache contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Customcode::class)]
#[UsesClass(LanguageExtractor::class)]
#[UsesClass(PowerExtractor::class)]
#[UsesClass(JoomlaPowerExtractor::class)]
final class CustomcodeTest extends CompilerDomainTestCase
{
	/**
	 * External expansion flows through language and Power discovery without database work.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdatePreservesExpandedContentWhenNoEmbeddedTokensArePresent(): void
	{
		$config = $this->compilerConfig(['lang_string_targets' => []]);
		$placeholder = new Placeholder($config);
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->never())->method('getQuery');
		$external = $this->createMock(External::class);
		$external->expects($this->once())
			->method('set')
			->with('source code', 0)
			->willReturn('expanded source code');
		$subject = new Customcode(
			$config,
			$placeholder,
			new LanguageExtractor($config, new Language($config), $placeholder),
			new PowerExtractor($database),
			new JoomlaPowerExtractor($database, 6),
			$external,
			$this->inertCompilerCollaborator(Counter::class),
			$database
		);

		$this->assertSame('expanded source code', $subject->update('source code'));
	}

	/**
	 * Cached custom-code IDs are returned without executing a database query.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetUsesMemoryAndRefreshesTheActiveBucketWithoutDatabaseExecution(): void
	{
		$config = $this->compilerConfig(['lang_string_targets' => []]);
		$placeholder = new Placeholder($config);
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->once())
			->method('getQuery')
			->with(true)
			->willReturn($this->createStub(QueryInterface::class));
		$database->expects($this->never())->method('setQuery');
		$database->expects($this->never())->method('execute');
		$item = ['id' => 9, 'code' => 'cached', 'comment_type' => 1];
		$subject = new Customcode(
			$config,
			$placeholder,
			new LanguageExtractor($config, new Language($config), $placeholder),
			new PowerExtractor($database),
			new JoomlaPowerExtractor($database, 6),
			$this->createStub(External::class),
			$this->inertCompilerCollaborator(Counter::class),
			$database
		);
		$subject->memory = [9 => $item];
		$subject->active = [['id' => 1]];

		$this->assertTrue($subject->get([9], false));
		$this->assertSame([$item], $subject->active);
	}
}
