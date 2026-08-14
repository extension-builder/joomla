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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Field;


use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Compiler\Customcode as CompilerCustomcode;
use VDM\Joomla\Componentbuilder\Compiler\Field\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Field\Data;
use VDM\Joomla\Componentbuilder\Compiler\Field\Rule;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HistoryInterface;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Package\Builder\Get;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Field definition loading and cached-context contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Data::class)]
final class DataTest extends CompilerDomainTestCase
{
	/**
	 * Cached ID and GUID lookups return the same definition and apply view code once per call.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCachedLookupPreservesIdentityAndAppliesRequestedViewCustomCode(): void
	{
		$field = (object) ['id' => 17, 'guid' => '123e4567-e89b-12d3-a456-426614174000'];
		$fieldCustomcode = $this->createMock(Customcode::class);
		$fieldCustomcode->expects($this->exactly(2))
			->method('update')
			->with(17, $this->identicalTo($field), 'article', 'articles');
		$subject = new Data(
			$this->compilerConfig(),
			$this->createStub(EventInterface::class),
			$this->createStub(HistoryInterface::class),
			new Placeholder($this->compilerConfig()),
			$this->createStub(CompilerCustomcode::class),
			$fieldCustomcode,
			$this->createStub(Rule::class),
			$this->createStub(DatabaseInterface::class),
			$this->inertCompilerCollaborator(Get::class)
		);
		$this->setCompilerProperty($subject, 'fields', [17 => $field]);
		$this->setCompilerProperty($subject, 'index', [17 => 17, $field->guid => 17]);

		$this->assertSame($field, $subject->get(17, 'article', 'articles'));
		$this->assertSame($field, $subject->get($field->guid, 'article', 'articles'));
	}

	/**
	 * Empty identifiers are rejected before the database or remote boundary is touched.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmptyIdentifierReturnsNullWithoutExternalWork(): void
	{
		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->never())->method('getQuery');
		$subject = new Data(
			$this->compilerConfig(),
			$this->createStub(EventInterface::class),
			$this->createStub(HistoryInterface::class),
			new Placeholder($this->compilerConfig()),
			$this->createStub(CompilerCustomcode::class),
			$this->createStub(Customcode::class),
			$this->createStub(Rule::class),
			$db,
			$this->inertCompilerCollaborator(Get::class)
		);

		$this->assertNull($subject->get(null));
		$this->assertNull($subject->get(0));
		$this->assertNull($subject->get(''));
	}
}
