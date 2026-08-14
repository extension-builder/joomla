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

namespace VDM\Tests\Support;


/**
 * Shared assertions for interface declarations and their production conformers.
 *
 * @since  1.0.0
 */
abstract class InterfaceContractTestCase extends TestCase
{
	/**
	 * Assert an interface's exact implementation set and declaration signature.
	 *
	 * @param   class-string  $interface  Interface under test.
	 * @param   array{implementations: int, concrete: int, implementation_hash: string, signature_hash: string}  $expected  Reviewed contract.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function assertInterfaceContract(string $interface, array $expected): void
	{
		$inventory = InterfaceContractInventory::discover();
		$this->assertArrayHasKey($interface, $inventory, 'Interface is absent from the production declaration graph.');
		$implementations = $inventory[$interface]['implementations'];
		$concrete = array_filter(
			$implementations,
			static fn(string $implementation): bool => str_starts_with($implementation, 'concrete|')
		);
		$implementationSnapshot = implode("\n", $implementations);
		$implementationMessage = 'The canonical conformance map changed for ' . $interface
			. ". Review every abstract and concrete implementation:\n"
			. $implementationSnapshot;

		$this->assertSame($expected['implementations'], count($implementations), $implementationMessage);
		$this->assertSame($expected['concrete'], count($concrete), $implementationMessage);
		$this->assertSame(
			$expected['implementation_hash'],
			hash('sha256', $implementationSnapshot),
			$implementationMessage
		);

		$signature = InterfaceContract::capture($interface);
		$signatureMessage = 'The public interface declaration changed for ' . $interface
			. ". Review inherited contracts, constants, parameters, and return types:\n"
			. $signature['snapshot'];
		$this->assertSame($expected['signature_hash'], $signature['hash'], $signatureMessage);
	}
}
