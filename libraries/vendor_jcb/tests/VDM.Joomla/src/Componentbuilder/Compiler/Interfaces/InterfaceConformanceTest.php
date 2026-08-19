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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Interfaces;


use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Tests\Support\InterfaceContractManifest;
use VDM\Tests\Support\InterfaceContractTestCase;


/**
 * Compiler interface signatures and canonical conformance maps.
 *
 * @since  1.0.0
 */
#[CoversNothing]
final class InterfaceConformanceTest extends InterfaceContractTestCase
{
	/**
	 * Verify one compiler interface's declaration and exact implementation set.
	 *
	 * @param   class-string  $interface  Interface under test.
	 * @param   array{path: string, implementations: int, concrete: int, implementation_hash: string, signature_hash: string}  $expected  Reviewed contract.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('interfaceContracts')]
	public function testInterfaceConformance(string $interface, array $expected): void
	{
		$this->assertInterfaceContract($interface, $expected);
	}

	/**
	 * Lock the reviewed compiler-interface inventory itself.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testManifestContainsEveryCompilerInterface(): void
	{
		$this->assertCount(63, iterator_to_array(self::interfaceContracts()));
	}

	/**
	 * Provide every reviewed compiler interface contract.
	 *
	 * @return  iterable<string, array{class-string, array{path: string, implementations: int, concrete: int, implementation_hash: string, signature_hash: string}}>
	 * @since   1.0.0
	 */
	public static function interfaceContracts(): iterable
	{
		$prefix = 'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\';

		foreach (InterfaceContractManifest::all() as $interface => $contract)
		{
			if (str_starts_with($interface, $prefix))
			{
				yield $interface => [$interface, $contract];
			}
		}
	}
}
