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


use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;


/**
 * Isolates compiler formatting utility state for deterministic unit tests.
 *
 * @since  1.0.0
 */
abstract class CompilerUtilityTestCase extends JoomlaTestCase
{
	/**
	 * Captured static utility state.
	 *
	 * @var    array<string, mixed>
	 * @since  1.0.0
	 */
	private array $compilerUtilityState = [];

	/**
	 * Force tab indentation and disable generated debug-line suffixes.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$indent = new ReflectionProperty(Indent::class, 'indent');
		$bucket = new ReflectionProperty(Indent::class, 'bucket');
		$add = new ReflectionProperty(Line::class, 'add');

		$this->compilerUtilityState = [
			'indentInitialized' => $indent->isInitialized(),
			'indent' => $indent->isInitialized() ? $indent->getValue() : null,
			'bucket' => $bucket->getValue(),
			'add' => $add->getValue()
		];

		$indent->setValue(null, "\t");
		$bucket->setValue(null, []);
		$add->setValue(null, false);
	}

	/**
	 * Restore formatting state or establish the suite's canonical tab sentinel.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		$indent = new ReflectionProperty(Indent::class, 'indent');
		$bucket = new ReflectionProperty(Indent::class, 'bucket');
		$add = new ReflectionProperty(Line::class, 'add');

		$indent->setValue(
			null,
			$this->compilerUtilityState['indentInitialized']
				? $this->compilerUtilityState['indent']
				: "\t"
		);
		$bucket->setValue(null, $this->compilerUtilityState['bucket']);
		$add->setValue(null, $this->compilerUtilityState['add']);
		$this->compilerUtilityState = [];

		parent::tearDown();
	}
}
