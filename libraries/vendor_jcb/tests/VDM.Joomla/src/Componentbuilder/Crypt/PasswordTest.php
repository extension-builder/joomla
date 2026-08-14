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

namespace VDM\Joomla\Tests\Componentbuilder\Crypt;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Crypt\Password;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\JoomlaTestCase;
use VDM\Tests\Support\LegacyComponentHelperFixture;


/**
 * Component crypt-key lookup and fallback tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Password::class)]
#[UsesClass(Helper::class)]
final class PasswordTest extends JoomlaTestCase
{
	/**
	 * Original component option.
	 *
	 * @var    string|null
	 * @since  6.1.6
	 */
	private ?string $originalOption = null;

	/**
	 * Register the deterministic legacy helper dispatch target.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->originalOption = Helper::$option;

		if (!class_exists('ExamplefixtureHelper', false))
		{
			class_alias(LegacyComponentHelperFixture::class, 'ExamplefixtureHelper');
		}

		Helper::setOption('com_examplefixture');
	}

	/**
	 * Restore the component option.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		Helper::setOption($this->originalOption);

		parent::tearDown();
	}

	/**
	 * Resolve known component keys and preserve caller fallback for unknown keys.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetDelegatesToComponentKeyProviderAndFallback(): void
	{
		$subject = new Password();

		$this->assertSame('fixture-basic-key', $subject->get('basic'));
		$this->assertSame('fixture-medium-key', $subject->get('medium', 'fallback'));
		$this->assertSame('fallback', $subject->get('unknown', 'fallback'));
		$this->assertNull($subject->get('unknown'));
	}
}
