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

namespace VDM\Joomla\Tests\Componentbuilder\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Utilities\Constantpaths;
use VDM\Tests\Support\TestCase;


/**
 * Joomla constant-path catalog contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Constantpaths::class)]
final class ConstantpathsTest extends TestCase
{
	/**
	 * Expose the complete reviewed constant catalog with exact runtime values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetReturnsCompleteConstantCatalog(): void
	{
		$subject = new Constantpaths();

		$this->assertSame(
			[
				'JPATH_ADMINISTRATOR' => JPATH_ADMINISTRATOR,
				'JPATH_BASE' => JPATH_BASE,
				'JPATH_CACHE' => JPATH_CACHE,
				'JPATH_COMPONENT_ADMINISTRATOR' => JPATH_ADMINISTRATOR . '/components/com_componentbuilder',
				'JPATH_COMPONENT_SITE' => JPATH_SITE . '/components/com_componentbuilder',
				'JPATH_COMPONENT' => JPATH_BASE . '/components/com_componentbuilder',
				'JPATH_CONFIGURATION' => JPATH_CONFIGURATION,
				'JPATH_INSTALLATION' => JPATH_INSTALLATION,
				'JPATH_LIBRARIES' => JPATH_LIBRARIES,
				'JPATH_PLUGINS' => JPATH_PLUGINS,
				'JPATH_ROOT' => JPATH_ROOT,
				'JPATH_SITE' => JPATH_SITE,
				'JPATH_THEMES' => JPATH_THEMES
			],
			$subject->get()
		);
	}

	/**
	 * Resolve one known path and return null for unknown or empty keys.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetResolvesSingleKnownPathAndRejectsUnknownKey(): void
	{
		$subject = new Constantpaths();

		$this->assertSame(JPATH_ROOT, $subject->get('JPATH_ROOT'));
		$this->assertSame(JPATH_LIBRARIES, $subject->get('JPATH_LIBRARIES'));
		$this->assertNull($subject->get('JPATH_UNKNOWN'));
		$this->assertNull($subject->get(''));
	}
}
