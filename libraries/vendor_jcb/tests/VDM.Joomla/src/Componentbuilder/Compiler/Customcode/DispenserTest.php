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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Customcode;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Hash;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\LockBase;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Custom-code dispenser transformation and hub-state contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Dispenser::class)]
final class DispenserTest extends CompilerDomainTestCase
{
	/**
	 * Decode, update, map, hash, lock, and store a script in the documented order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRunsTheTransformationPipelineAndMutatesTheSourceReference(): void
	{
		$calls = [];
		$placeholder = $this->createStub(Placeholder::class);
		$customcode = $this->createMock(Customcode::class);
		$customcode->expects($this->once())->method('update')->willReturnCallback(
			static function (string $script) use (&$calls): string
			{
				$calls[] = ['dynamic', $script];

				return 'dynamic:' . $script;
			}
		);
		$gui = $this->createMock(Gui::class);
		$gui->expects($this->once())->method('set')->willReturnCallback(
			static function (string $script, array $config) use (&$calls): string
			{
				$calls[] = ['gui', $script, $config];

				return 'gui:' . $script;
			}
		);
		$hash = $this->createMock(Hash::class);
		$hash->expects($this->once())->method('set')->willReturnCallback(
			static function (string $script) use (&$calls): string
			{
				$calls[] = ['hash', $script];

				return 'hash:' . $script;
			}
		);
		$lock = $this->createMock(LockBase::class);
		$lock->expects($this->once())->method('set')->willReturnCallback(
			static function (string $script) use (&$calls): string
			{
				$calls[] = ['lock', $script];

				return 'lock:' . $script;
			}
		);
		$subject = new Dispenser($placeholder, $customcode, $gui, $hash, $lock);
		$script = base64_encode('echo 42;');

		$this->assertTrue($subject->set(
			$script,
			'admin',
			'model',
			'article',
			['field' => 'php_model']
		));

		$this->assertSame('lock:hash:gui:dynamic:echo 42;', $script);
		$this->assertSame($script, $subject->hub['admin']['model']['article']);
		$this->assertSame([
			['dynamic', 'echo 42;'],
			['gui', 'dynamic:echo 42;', ['field' => 'php_model']],
			['hash', 'gui:dynamic:echo 42;'],
			['lock', 'hash:gui:dynamic:echo 42;'],
		], $calls);
	}

	/**
	 * Append scripts, expand placeholders at read time, and optionally consume the value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testHubAppendReadAndUnsetPreserveTheStoredScriptContract(): void
	{
		$placeholder = $this->createStub(Placeholder::class);
		$placeholder->method('update_')->willReturnCallback(
			static fn(string $script): string => str_replace('[[name]]', 'Article', $script)
		);
		$hash = $this->createStub(Hash::class);
		$hash->method('set')->willReturnArgument(0);
		$lock = $this->createStub(LockBase::class);
		$lock->method('set')->willReturnArgument(0);
		$subject = new Dispenser(
			$placeholder,
			$this->createStub(Customcode::class),
			$this->createStub(Gui::class),
			$hash,
			$lock
		);
		$first = 'Hello ';
		$second = '[[name]]';

		$this->assertTrue($subject->set($first, 'message', 'body', null, [], false, false, true));
		$this->assertTrue($subject->set($second, 'message', 'body', null, [], false, false, true));
		$this->assertSame(
			"/*note*/\n<p>Hello Article</p>",
			$subject->get('message', 'body', '<p>', "/*note*/\n", true, 'missing', '</p>')
		);
		$this->assertSame('missing', $subject->get('message', 'body', '', null, false, 'missing'));
	}

	/**
	 * Reject empty scripts before creating hub state or invoking collaborators.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRejectsEmptyInputBeforeAnyTransformation(): void
	{
		$customcode = $this->createMock(Customcode::class);
		$customcode->expects($this->never())->method('update');
		$subject = new Dispenser(
			$this->createStub(Placeholder::class),
			$customcode,
			$this->createStub(Gui::class),
			$this->createStub(Hash::class),
			$this->createStub(LockBase::class)
		);
		$script = '';

		$this->assertFalse($subject->set($script, 'empty'));
		$this->assertFalse(isset($subject->hub));
	}
}
