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

namespace VDM\Joomla\Tests\Abstraction;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Tests\Support\ActiveRegistryFixture;


/**
 * Active storage registry behavior tests.
 *
 * @since  6.1.6
 */
#[CoversClass(ActiveRegistry::class)]
final class ActiveRegistryTest extends TestCase
{
	/**
	 * Preserve nested set, get, existence, removal, and default-value semantics.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNestedStorageLifecycleIsConsistent(): void
	{
		$subject = new ActiveRegistryFixture();

		$this->assertFalse($subject->isActive());
		$this->assertSame([], $subject->allActive());

		$subject->setActive('first', 'root', 'leaf');
		$subject->setActive('second', 'root', 'sibling');

		$this->assertTrue($subject->isActive());
		$this->assertTrue($subject->existsActive('root', 'leaf'));
		$this->assertSame('first', $subject->getActive('fallback', 'root', 'leaf'));
		$this->assertSame('fallback', $subject->getActive('fallback', 'root', 'missing'));
		$this->assertSame([
			'root' => [
				'leaf' => 'first',
				'sibling' => 'second'
			]
		], $subject->allActive());

		$subject->removeActive('root', 'leaf');

		$this->assertFalse($subject->existsActive('root', 'leaf'));
		$this->assertSame('second', $subject->getActive(null, 'root', 'sibling'));

		$subject->removeActive('does', 'not', 'exist');
		$this->assertSame(['root' => ['sibling' => 'second']], $subject->allActive());
	}

	/**
	 * Concatenate scalar additions when array mode is not selected.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDefaultAdditionConcatenatesStringsAndNumbers(): void
	{
		$subject = new ActiveRegistryFixture();

		$subject->addActive('alpha', null, 'content');
		$subject->addActive('-', null, 'content');
		$subject->addActive(7, null, 'content');

		$this->assertSame('alpha-7', $subject->getActive(null, 'content'));
	}

	/**
	 * Explicit array mode must retain an existing value before appending.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExplicitArrayAdditionConvertsAnExistingScalar(): void
	{
		$subject = new ActiveRegistryFixture();
		$subject->setActive('seed', 'items');

		$subject->addActive('next', true, 'items');

		$this->assertSame(['seed', 'next'], $subject->getActive(null, 'items'));
	}

	/**
	 * Class-level policies must govern null array-mode arguments.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testClassPoliciesAddArraysAndSuppressDuplicateValues(): void
	{
		$subject = (new ActiveRegistryFixture())
			->addValuesAsArrays()
			->keepArrayValuesUnique();

		$subject->addActive('alpha', null, 'items');
		$subject->addActive('alpha', null, 'items');
		$subject->addActive('beta', null, 'items');

		$this->assertSame(['alpha', 'beta'], $subject->getActive(null, 'items'));
	}

	/**
	 * Reject an empty key consistently for every key-based operation.
	 *
	 * @param   string  $operation  Operation to invoke.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('emptyKeyOperations')]
	public function testEmptyKeysAreRejected(string $operation): void
	{
		$subject = new ActiveRegistryFixture();

		$this->expectException(InvalidArgumentException::class);

		match ($operation)
		{
			'set' => $subject->setActive('value', ''),
			'add' => $subject->addActive('value', null, ''),
			'get' => $subject->getActive(null, ''),
			'remove' => $subject->removeActive(''),
			'exists' => $subject->existsActive('')
		};
	}

	/**
	 * Provide every operation that validates keys.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.6
	 */
	public static function emptyKeyOperations(): array
	{
		return [
			'set' => ['set'],
			'add' => ['add'],
			'get' => ['get'],
			'remove' => ['remove'],
			'exists' => ['exists']
		];
	}

	/**
	 * Prevent a nested write from silently replacing an intermediate scalar.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetCannotTraverseThroughAScalar(): void
	{
		$subject = new ActiveRegistryFixture();
		$subject->setActive('terminal', 'root');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Attempted to use key 'child' on a non-array value");

		$subject->setActive('value', 'root', 'child');
	}

	/**
	 * Prevent a nested addition from silently replacing an intermediate scalar.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAddCannotTraverseThroughAScalar(): void
	{
		$subject = new ActiveRegistryFixture();
		$subject->setActive('terminal', 'root');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Attempted to use key 'child' on a non-array value");

		$subject->addActive('value', null, 'root', 'child');
	}
}
