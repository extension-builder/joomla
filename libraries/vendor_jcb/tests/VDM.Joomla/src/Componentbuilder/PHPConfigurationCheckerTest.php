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

namespace VDM\Joomla\Tests\Componentbuilder;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\PHPConfigurationChecker as AbstractChecker;
use VDM\Joomla\Componentbuilder\PHPConfigurationChecker;
use VDM\Tests\Support\MessageApplicationFixture;
use VDM\Tests\Support\TestCase;


/**
 * Component Builder PHP requirement catalog tests.
 *
 * @since  6.1.6
 */
#[CoversClass(PHPConfigurationChecker::class)]
#[UsesClass(AbstractChecker::class)]
final class PHPConfigurationCheckerTest extends TestCase
{
	/**
	 * Publish the reviewed Component Builder runtime thresholds and help metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorPublishesJcbRuntimeRequirements(): void
	{
		$subject = new PHPConfigurationChecker(new MessageApplicationFixture());

		$this->assertSame('128M', $subject->get('php.upload_max_filesize.value'));
		$this->assertSame('128M', $subject->get('php.post_max_size.value'));
		$this->assertSame(60, $subject->get('php.max_execution_time.value'));
		$this->assertSame(7000, $subject->get('php.max_input_vars.value'));
		$this->assertSame(60, $subject->get('php.max_input_time.value'));
		$this->assertSame('256M', $subject->get('php.memory_limit.value'));
		$this->assertSame('Componentbuilder environment', $subject->get('environment.name'));
		$this->assertSame(
			'git.vdm.dev/joomla/Component-Builder/wiki/PHP-Settings',
			$subject->get('environment.wiki_url')
		);
	}
}
