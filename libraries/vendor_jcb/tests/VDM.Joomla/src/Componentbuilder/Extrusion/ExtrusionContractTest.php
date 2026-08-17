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

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Extrusion\Helper\Extrusion;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Message;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * The dump-driven entry point delegates rather than parsing anything itself.
 *
 * This entry point used to be a three-class inheritance stack that parsed a dump,
 * guessed field roles and wrote rows of its own -- a second engine doing the same
 * job as the extrusion domain, free to disagree with it. It is now a delegate, and
 * these cases hold it to that: it must own no parsing, no writing and no guessing,
 * and it must still refuse the two inputs it cannot act on.
 *
 * A run needs a database, so the successful path is proven by the extrusion
 * domain's own tests rather than duplicated here against a live connection.
 *
 * @since  6.1.6
 */
#[CoversClass(Extrusion::class)]
#[UsesClass(Message::class)]
final class ExtrusionContractTest extends JoomlaTestCase
{
	/**
	 * Saving without a component id is refused, and says why.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAMissingComponentIdIsRefused(): void
	{
		$data = ['id' => 0, 'buildcomp' => 1, 'buildcompsql' => base64_encode('CREATE TABLE `a` (`b` INT);')];
		$subject = new Extrusion($data);

		$this->assertFalse($subject->completed());
		$this->assertSame([], $subject->messages());
	}

	/**
	 * An empty dump is refused, and the build values are still cleared.
	 *
	 * A pasted dump must never be persisted on the component, whether or not it
	 * turned out to be usable.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAnEmptyDumpIsRefusedAndTheBuildValuesAreCleared(): void
	{
		$data = ['id' => 42, 'buildcomp' => 1, 'buildcompsql' => base64_encode('   ')];
		$subject = new Extrusion($data);

		$this->assertFalse($subject->completed());
		$this->assertSame(0, $data['buildcomp'], 'The build switch must be turned off.');
		$this->assertSame('', $data['buildcompsql'], 'A pasted dump must never be persisted.');
	}

	/**
	 * Data that is not a usable payload is refused without touching it.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAnUnusablePayloadIsRefused(): void
	{
		$empty = [];

		$this->assertFalse((new Extrusion($empty))->completed());

		$noId = ['buildcompsql' => base64_encode('CREATE TABLE `a` (`b` INT);')];

		$this->assertFalse((new Extrusion($noId))->completed());
		$this->assertArrayNotHasKey(
			'buildcomp',
			$noId,
			'A payload that was never actionable must not be rewritten.'
		);
	}

	/**
	 * The entry point owns no parsing, writing or guessing of its own.
	 *
	 * This is the contract that keeps one engine behind both entry points. If any
	 * of these reappear here, the duplicate engine has come back.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheEntryPointOnlyDelegates(): void
	{
		$file = (new ReflectionClass(Extrusion::class))->getFileName();

		$this->assertIsString($file);

		$source = file_get_contents($file);

		$this->assertIsString($source);

		foreach (['CREATE TABLE', 'insertObject', 'getTableColumns', 'base64_encode', 'stripos'] as $forbidden)
		{
			$this->assertStringNotContainsString(
				$forbidden,
				$source,
				'The entry point must delegate, not parse, write or guess: ' . $forbidden
			);
		}

		$this->assertStringContainsString(
			"Factory::_('Extruder')",
			$source,
			'The entry point must resolve the one engine from the container.'
		);
	}

	/**
	 * The class is a plain delegate with no inherited engine behind it.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheClassNoLongerExtendsAnEngine(): void
	{
		$reflection = new ReflectionClass(Extrusion::class);

		$this->assertFalse($reflection->getParentClass(), 'The inheritance stack is gone.');
		$this->assertFalse(
			class_exists('VDM\\Joomla\\Componentbuilder\\Extrusion\\Helper\\Mapping'),
			'The duplicate parser must be deleted, not merely unused.'
		);
		$this->assertFalse(
			class_exists('VDM\\Joomla\\Componentbuilder\\Extrusion\\Helper\\Builder'),
			'The duplicate writer must be deleted, not merely unused.'
		);
	}
}
