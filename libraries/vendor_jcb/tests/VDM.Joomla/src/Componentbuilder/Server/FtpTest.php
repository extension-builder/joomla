<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    27th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Server;


use Joomla\CMS\Client\FtpClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Server\ClientFactory;
use VDM\Joomla\Componentbuilder\Server\Ftp;
use VDM\Tests\Support\TestCase;


/**
 * FTP configuration, authentication, and transfer regression tests.
 *
 * @since  6.2.1
 */
#[CoversClass(Ftp::class)]
final class FtpTest extends TestCase
{
	/**
	 * Pass decoded credentials and typed options to the client, then reuse it.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testValidSignatureAuthenticatesAndReusesConnection(): void
	{
		$client = $this->createMock(FtpClient::class);
		$client->expects($this->once())->method('connect')->with('ftp.example.test', 2121)->willReturn(true);
		$client->expects($this->once())->method('login')->with('user+name', ' pass&word+ ')->willReturn(true);
		$client->expects($this->once())->method('isConnected')->willReturn(true);
		$client->expects($this->exactly(2))->method('store')->with('/tmp/release.zip', 'release.zip')->willReturn(true);
		$clients = $this->createMock(ClientFactory::class);
		$clients->expects($this->once())->method('ftp')->with(['timeout' => 15, 'type' => FTP_BINARY])->willReturn($client);
		$subject = new Ftp($clients);
		$subject->set((object) ['signature' => http_build_query([
			'host' => 'ftp.example.test',
			'port' => 2121,
			'username' => 'user+name',
			'password' => ' pass&word+ ',
			'options' => ['type' => 'FTP_BINARY', 'timeout' => 15, 'unknown' => 'ignored'],
		])]);

		$this->assertTrue($subject->move('/tmp/release.zip', 'release.zip'));
		$this->assertTrue($subject->move('/tmp/release.zip', 'release.zip'));
	}

	/**
	 * Stop before upload when either connection or authentication fails.
	 *
	 * @param   bool  $connects  Whether opening the connection succeeds.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('connectionFailures')]
	public function testFailedConnectionOrLoginStopsTransfer(bool $connects): void
	{
		$client = $this->createMock(FtpClient::class);
		$client->expects($this->once())->method('connect')->with('ftp.example.test', 21)->willReturn($connects);
		$client->expects($connects ? $this->once() : $this->never())->method('login')->willReturn(false);
		$client->expects($connects ? $this->once() : $this->never())->method('quit');
		$client->expects($this->never())->method('store');
		$clients = $this->createMock(ClientFactory::class);
		$clients->expects($this->once())->method('ftp')->with([])->willReturn($client);
		$subject = new Ftp($clients);
		$subject->set((object) ['signature' => 'host=ftp.example.test&port=21&username=alice&password=secret&options[unknown]=ignored']);

		$this->assertFalse($subject->move('/tmp/release.zip', 'release.zip'));
	}

	/**
	 * Provide failure stages before upload.
	 *
	 * @return  iterable<string, array{bool}>
	 * @since   6.2.1
	 */
	public static function connectionFailures(): iterable
	{
		yield 'connect' => [false];
		yield 'login' => [true];
	}

	/**
	 * Reject malformed configuration before constructing a network client.
	 *
	 * @param   string  $suffix  Malformed signature replacement.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('invalidSignatures')]
	public function testInvalidSignatureDoesNotConnect(string $suffix): void
	{
		$clients = $this->createMock(ClientFactory::class);
		$clients->expects($this->never())->method('ftp');
		$subject = new Ftp($clients);
		$subject->set((object) ['signature' => 'host=ftp.example.test&port=21&username=alice&password=secret&' . $suffix]);

		$this->assertFalse($subject->move('/tmp/release.zip', 'release.zip'));
	}

	/**
	 * Provide invalid credential and transfer option shapes.
	 *
	 * @return  iterable<string, array{string}>
	 * @since   6.2.1
	 */
	public static function invalidSignatures(): iterable
	{
		yield 'array host' => ['host[]=bad'];
		yield 'empty password' => ['password='];
		yield 'invalid port' => ['port=70000'];
		yield 'invalid mode' => ['options[type]=INVALID'];
		yield 'array mode' => ['options[type][]=FTP_BINARY'];
		yield 'array timeout' => ['options[timeout][]=5'];
		yield 'negative timeout' => ['options[timeout]=-1'];
		yield 'scalar options' => ['options=bad'];
	}

	/**
	 * Propagate a refused upload and contain network exceptions.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testTransferFailureAndExceptionReturnFalse(): void
	{
		$client = $this->createMock(FtpClient::class);
		$client->method('connect')->willReturn(true);
		$client->method('login')->willReturn(true);
		$client->method('isConnected')->willReturn(true);
		$client->expects($this->exactly(2))->method('store')->willReturnCallback(
			static function (string $localPath, string $fileName): bool
			{
				if ($fileName === 'exception.zip')
				{
					throw new \RuntimeException('Transport disconnected.');
				}

				return false;
			}
		);
		$clients = $this->createMock(ClientFactory::class);
		$clients->expects($this->once())->method('ftp')->with([])->willReturn($client);
		$subject = new Ftp($clients);
		$subject->set((object) ['signature' => 'host=ftp.example.test&port=21&username=alice&password=secret']);

		$this->assertFalse($subject->move('/tmp/release.zip', 'refused.zip'));
		$this->assertFalse($subject->move('/tmp/release.zip', 'exception.zip'));
	}
	/**
	 * Leave an unopened Joomla client alone when the connection attempt fails.
	 *
	 * The real quit implementation must not run with its null connection handle.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testFailedConnectionDoesNotCloseAnUnopenedClient(): void
	{
		$client = $this->getMockBuilder(FtpClient::class)
			->onlyMethods(['connect', 'store'])
			->getMock();
		$client->expects($this->once())->method('connect')->with('ftp.example.test', 21)->willReturn(false);
		$client->expects($this->never())->method('store');
		$clients = $this->createMock(ClientFactory::class);
		$clients->expects($this->once())->method('ftp')->with([])->willReturn($client);
		$subject = new Ftp($clients);
		$subject->set((object) ['signature' => 'host=ftp.example.test&port=21&username=alice&password=secret']);

		$this->assertFalse($subject->move('/tmp/release.zip', 'release.zip'));
	}

}
