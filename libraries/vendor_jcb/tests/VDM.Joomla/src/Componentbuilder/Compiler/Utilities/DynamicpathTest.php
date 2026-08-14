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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Utilities;


use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Dynamicpath;
use VDM\Joomla\Componentbuilder\Utilities\Constantpaths;
use VDM\Tests\Support\TestCase;


/**
 * Constant and compiler-placeholder path expansion contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Dynamicpath::class)]
#[UsesClass(Config::class)]
#[UsesClass(Constantpaths::class)]
#[UsesClass(Placeholder::class)]
final class DynamicpathTest extends TestCase
{
	/**
	 * Apply constant paths before active bracket and hash placeholders.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdateExpandsConstantsAndBothPlaceholderGrammars(): void
	{
		$placeholder = $this->createPlaceholder();
		$placeholder->set('COMPONENT', 'com_example');
		$placeholder->set('AREA', 'administrator');
		$subject = new Dynamicpath($placeholder, new Constantpaths());

		$this->assertSame(
			JPATH_ROOT . '/administrator/components/com_example',
			$subject->update('JPATH_ROOT/###AREA###/components/[[[COMPONENT]]]')
		);
	}

	/**
	 * Preserve strings that contain neither known constants nor active placeholders.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdatePreservesUnknownAndAlreadyAbsolutePaths(): void
	{
		$subject = new Dynamicpath($this->createPlaceholder(), new Constantpaths());
		$path = '/opt/example/JPATH_UNKNOWN/[[[MISSING]]]';

		$this->assertSame($path, $subject->update($path));
		$this->assertSame('', $subject->update(''));
	}

	/**
	 * Construct a placeholder registry without using the static compiler factory.
	 *
	 * @return  Placeholder
	 * @since   6.1.6
	 */
	private function createPlaceholder(): Placeholder
	{
		return new Placeholder(new Config(new Input(), new Registry(), new Registry()));
	}
}
