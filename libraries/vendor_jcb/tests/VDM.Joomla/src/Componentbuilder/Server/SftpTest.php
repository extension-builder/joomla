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


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Language;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Net\SFTP as SftpClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Crypt\KeyLoader;
use VDM\Joomla\Componentbuilder\Server\ClientFactory;
use VDM\Joomla\Componentbuilder\Server\Sftp;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * SFTP authentication, exact secret, and destination regression tests.
 *
 * @since  6.2.1
 */
#[CoversClass(Sftp::class)]
final class SftpTest extends FilesystemTestCase
{
	/**
	 * Authenticate each supported mode with only the selected credentials.
	 *
	 * @param   int  $authentication  Configured authentication mode.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('authenticationModes')]
	public function testSupportedAuthenticationModes(int $authentication): void
	{
		$key = EC::createKey('Ed25519');
		$privateKey = $key->withPassword(' passphrase ')->toString('PKCS8');
		$details = $this->details();
		$details->authentication = $authentication;
		$details->password = ' password ';
		$details->secret = ' passphrase ';
		$details->private_key = $privateKey;
		$details->private = $this->writeTemporaryFile('key.pem', $privateKey);
		$path = $this->writeTemporaryFile('release.zip', "PK\0\x01binary");
		$client = $this->createMock(SftpClient::class);
		$client->expects($this->once())->method('login')->willReturnCallback(
			function (string $username, ...$credentials) use ($authentication, $key): bool
			{
				$this->assertSame('alice', $username);
				$requiresKey = $authentication !== 1;
				$requiresPassword = in_array($authentication, [1, 3, 5], true);
				$this->assertCount((int) $requiresKey + (int) $requiresPassword, $credentials);

				if ($requiresKey)
				{
					$this->assertInstanceOf(PrivateKey::class, $credentials[0]);
					$this->assertSame($key->getPublicKey()->toString('OpenSSH'), $credentials[0]->getPublicKey()->toString('OpenSSH'));
				}

				if ($requiresPassword)
				{
					$this->assertSame(' password ', end($credentials));
				}

				return true;
			}
		);
		$client->expects($this->once())->method('put')->with('releases/release.zip', "PK\0\x01binary")->willReturn(true);
		$subject = $this->subject($client);
		$subject->set($details);

		$this->assertTrue($subject->move($path, 'release.zip'));
	}

	/**
	 * Provide the five modes exposed by JCB's server form.
	 *
	 * @return  iterable<string, array{int}>
	 * @since   6.2.1
	 */
	public static function authenticationModes(): iterable
	{
		yield 'password' => [1];
		yield 'key file' => [2];
		yield 'key file and password' => [3];
		yield 'key text' => [4];
		yield 'key text and password' => [5];
	}

	/**
	 * Keep relative, absolute, root, and empty destination paths distinct.
	 *
	 * @param   string  $directory    Configured remote directory.
	 * @param   string  $destination  Expected upload path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('remotePaths')]
	public function testRemotePathsAndZeroPassword(string $directory, string $destination): void
	{
		$path = $this->writeTemporaryFile('empty.zip', '');
		$client = $this->createMock(SftpClient::class);
		$client->expects($this->once())->method('login')->with('alice', '0')->willReturn(true);
		$client->expects($this->once())->method('put')->with($destination, '')->willReturn(true);
		$details = $this->details();
		$details->path = $directory;
		$details->password = '0';
		$subject = $this->subject($client);
		$subject->set($details);

		$this->assertTrue($subject->move($path, 'release.zip'));
	}

	/**
	 * Provide configured directory semantics.
	 *
	 * @return  iterable<string, array{string, string}>
	 * @since   6.2.1
	 */
	public static function remotePaths(): iterable
	{
		yield 'login directory' => ['', 'release.zip'];
		yield 'root' => ['/', '/release.zip'];
		yield 'absolute' => ['/releases', '/releases/release.zip'];
		yield 'relative' => ['releases', 'releases/release.zip'];
		yield 'trailing slash' => ['releases/', 'releases/release.zip'];
		yield 'spaces' => ['folder with spaces', 'folder with spaces/release.zip'];
	}

	/**
	 * Never upload or cache a client that rejected authentication.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testRejectedAuthenticationIsNotCached(): void
	{
		$path = $this->writeTemporaryFile('release.zip', 'data');
		$client = $this->createMock(SftpClient::class);
		$client->expects($this->exactly(2))->method('login')->with('alice', 'secret')->willReturn(false);
		$client->expects($this->never())->method('put');
		$clients = $this->createMock(ClientFactory::class);
		$clients->expects($this->exactly(2))->method('sftp')->with('sftp.example.test', 2222)->willReturn($client);
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->exactly(2))->method('enqueueMessage')->with('COM_COMPONENTBUILDER_LOGIN_FAILED', 'Error');
		$subject = new Sftp(new KeyLoader(), $app, $clients);
		$subject->set($this->details());

		$this->assertFalse($subject->move($path, 'release.zip'));
		$this->assertFalse($subject->move($path, 'release.zip'));
	}

	/**
	 * Reuse an authenticated live client for subsequent uploads.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testAuthenticatedConnectionIsReused(): void
	{
		$path = $this->writeTemporaryFile('release.zip', 'data');
		$client = $this->createMock(SftpClient::class);
		$client->expects($this->once())->method('login')->willReturn(true);
		$client->expects($this->once())->method('isAuthenticated')->willReturn(true);
		$client->expects($this->once())->method('isConnected')->willReturn(true);
		$client->expects($this->never())->method('ping');
		$client->expects($this->exactly(2))->method('put')->willReturn(true);
		$subject = $this->subject($client);
		$subject->set($this->details());

		$this->assertTrue($subject->move($path, 'release.zip'));
		$this->assertTrue($subject->move($path, 'release.zip'));
	}

	/**
	 * Missing local files and invalid keys cannot reach a remote connection.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testMissingFileAndInvalidKeyDoNotConnect(): void
	{
		$clients = $this->createMock(ClientFactory::class);
		$clients->expects($this->never())->method('sftp');
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())->method('enqueueMessage')->with('COM_COMPONENTBUILDER_LOADING_THE_PRIVATE_KEY_TEXT_FAILED', 'Error');
		$subject = new Sftp(new KeyLoader(), $app, $clients);
		$details = $this->details();
		$subject->set($details);
		$this->assertFalse($subject->move($this->temporaryPath('missing.zip'), 'release.zip'));
		$details = clone $details;
		$details->authentication = 4;
		$details->private_key = 'invalid';
		$subject->set($details);

		$this->assertFalse($subject->move($this->writeTemporaryFile('release.zip', 'data'), 'release.zip'));
	}

	/**
	 * Report one localized diagnostic for a rejected key without connecting.
	 *
	 * @param   int          $authentication  Configured key authentication mode.
	 * @param   string|null  $privateKey      Key contents, or a missing key file.
	 * @param   string       $message         Expected user-facing message.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('invalidKeys')]
	public function testInvalidKeysHaveLocalizedDiagnostics(int $authentication, ?string $privateKey, string $message): void
	{
		$language = new Language('en-GB');
		$this->assertTrue($language->load('com_componentbuilder', JPATH_COMPONENT_ADMINISTRATOR, 'en-GB', true));
		$this->setJoomlaFactoryProperty('language', $language);
		$clients = $this->createMock(ClientFactory::class);
		$clients->expects($this->never())->method('sftp');
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())->method('enqueueMessage')->with($message, 'Error');
		$subject = new Sftp(new KeyLoader(), $app, $clients);
		$details = $this->details();
		$details->authentication = $authentication;
		$details->private_key = $privateKey;
		$details->private = $privateKey === null
			? $this->temporaryPath('missing.pem')
			: $this->writeTemporaryFile('key.pem', $privateKey);
		$subject->set($details);

		$this->assertFalse($subject->move($this->writeTemporaryFile('release.zip', 'data'), 'release.zip'));
	}

	/**
	 * Cover missing, empty and malformed keys for every key authentication mode.
	 *
	 * @return  iterable<string, array{int, string|null, string}>
	 * @since   6.2.1
	 */
	public static function invalidKeys(): iterable
	{
		foreach ([2, 3] as $authentication)
		{
			yield 'missing file mode ' . $authentication => [$authentication, null, 'Loading the private key file failed'];
			yield 'empty file mode ' . $authentication => [$authentication, '', 'Loading the private key file failed'];
			yield 'invalid file mode ' . $authentication => [$authentication, 'invalid', 'Loading the private key file failed'];
		}

		foreach ([4, 5] as $authentication)
		{
			yield 'missing text mode ' . $authentication => [$authentication, null, 'Loading the private key text failed'];
			yield 'empty text mode ' . $authentication => [$authentication, '', 'Loading the private key text failed'];
			yield 'invalid text mode ' . $authentication => [$authentication, 'invalid', 'Loading the private key text failed'];
		}
	}

	/**
	 * Construct the real transport around a mocked remote client.
	 *
	 * @param   SftpClient  $client  Isolated SSH transport boundary.
	 *
	 * @return  Sftp
	 * @since   6.2.1
	 */
	private function subject(SftpClient $client): Sftp
	{
		$clients = $this->createMock(ClientFactory::class);
		$clients->expects($this->once())->method('sftp')->with('sftp.example.test', 2222)->willReturn($client);
		$app = $this->createStub(CMSApplicationInterface::class);

		return new Sftp(new KeyLoader(), $app, $clients);
	}

	/**
	 * Provide valid default server details.
	 *
	 * @return  object
	 * @since   6.2.1
	 */
	private function details(): object
	{
		return (object) [
			'host' => 'sftp.example.test',
			'port' => 2222,
			'username' => 'alice',
			'authentication' => 1,
			'password' => 'secret',
			'path' => 'releases',
		];
	}
}
