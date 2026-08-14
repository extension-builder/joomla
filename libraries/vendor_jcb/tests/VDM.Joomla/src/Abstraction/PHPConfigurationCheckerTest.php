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


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\PHPConfigurationChecker;
use VDM\Joomla\Abstraction\Registry;
use VDM\Tests\Support\MessageApplicationFixture;
use VDM\Tests\Support\PHPConfigurationCheckerFixture;
use VDM\Tests\Support\TestCase;


/**
 * PHP configuration threshold, conversion, and reporting tests.
 *
 * @since  6.1.6
 */
#[CoversClass(PHPConfigurationChecker::class)]
#[UsesClass(Registry::class)]
final class PHPConfigurationCheckerTest extends TestCase
{
	/**
	 * Convert each supported INI size suffix into bytes.
	 *
	 * @param   string  $value     INI size.
	 * @param   int     $expected  Expected bytes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('provideIniSizes')]
	public function testConvertToBytesSupportsIniSuffixes(string $value, int $expected): void
	{
		$subject = new PHPConfigurationCheckerFixture(new MessageApplicationFixture());

		$this->assertSame($expected, $subject->bytes($value));
	}

	/**
	 * Supply byte, kibibyte, mebibyte, and gibibyte values.
	 *
	 * @return  iterable<string, array{string, int}>
	 * @since   6.1.6
	 */
	public static function provideIniSizes(): iterable
	{
		yield 'bytes' => ['512', 512];
		yield 'kilobytes' => ['2K', 2048];
		yield 'megabytes' => ['3M', 3145728];
		yield 'gigabytes' => ['1G', 1073741824];
		yield 'lowercase and whitespace' => [' 4m ', 4194304];
	}

	/**
	 * Report every satisfied requirement as a successful application message.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRunReportsSatisfiedRequirementsWithoutHelpNotice(): void
	{
		$application = new MessageApplicationFixture();
		$subject = new PHPConfigurationCheckerFixture($application);

		$subject->run();

		$this->assertCount(6, $application->messages);
		$this->assertSame(
			['message'],
			array_values(array_unique(array_column($application->messages, 'type')))
		);
		$this->assertStringContainsString('Success: upload_max_filesize', $application->messages[0]['message']);
	}

	/**
	 * Add a help notice when at least one runtime threshold is not met.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRunAddsHelpNoticeForFailedThreshold(): void
	{
		$application = new MessageApplicationFixture();
		$subject = new PHPConfigurationCheckerFixture($application);
		$subject->set('php.max_execution_time.value', PHP_INT_MAX);
		$subject->set('environment.name', 'test extension');
		$subject->set('environment.wiki_url', 'docs.example/php');

		$subject->run();

		$this->assertCount(7, $application->messages);
		$this->assertContains('warning', array_column($application->messages, 'type'));
		$this->assertSame('notice', $application->messages[6]['type']);
		$this->assertStringContainsString('test extension', $application->messages[6]['message']);
		$this->assertStringContainsString('https://docs.example/php', $application->messages[6]['message']);
	}
}
