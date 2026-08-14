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

namespace VDM\Joomla\Tests\Componentbuilder\Server;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Client\FtpClient;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Abstraction\Model;
use VDM\Joomla\Componentbuilder\Crypt;
use VDM\Joomla\Componentbuilder\Crypt\KeyLoader;
use VDM\Joomla\Componentbuilder\Server;
use VDM\Joomla\Componentbuilder\Server\Ftp;
use VDM\Joomla\Componentbuilder\Server\Load;
use VDM\Joomla\Componentbuilder\Server\Model\Load as LoadModel;
use VDM\Joomla\Componentbuilder\Server\Sftp;
use VDM\Joomla\Componentbuilder\Table;
use VDM\Tests\Support\TestCase;


/**
 * Server protocol selection, detail loading, and stored-value modelling tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Server::class)]
#[CoversClass(Ftp::class)]
#[CoversClass(Sftp::class)]
#[CoversClass(Load::class)]
#[CoversClass(LoadModel::class)]
#[UsesClass(Model::class)]
final class ServerContractTest extends TestCase
{
	/**
	 * Route an explicit FTP protocol through exact detail fields and transport.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testServerMovesThroughExplicitFtpProtocol(): void
	{
		$details = (object) ['name' => 'Release server', 'signature' => 'encoded'];
		$load = $this->createMock(Load::class);
		$load->expects($this->never())->method('value');
		$load->expects($this->once())->method('item')->with(12, ['name', 'signature'])->willReturn($details);
		$ftp = $this->createMock(Ftp::class);
		$ftp->expects($this->once())->method('set')->with($details)->willReturnSelf();
		$ftp->expects($this->once())->method('move')->with('/tmp/archive.zip', 'archive.zip')->willReturn(true);
		$sftp = $this->createMock(Sftp::class);
		$sftp->expects($this->never())->method('set');
		$user = $this->createMock(User::class);
		$user->expects($this->once())->method('authorise')->with('core.export', 'com_componentbuilder')->willReturn(true);

		$this->assertTrue((new Server($load, $ftp, $sftp, $user))->move(12, '/tmp/archive.zip', 'archive.zip', 1));
	}

	/**
	 * Discover SFTP from storage and load its complete credential record.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testServerDiscoversSftpProtocolAndCredentialFields(): void
	{
		$fields = ['name', 'authentication', 'username', 'host', 'password', 'path', 'port', 'private', 'private_key', 'secret'];
		$details = (object) ['name' => 'Secure server', 'authentication' => 1];
		$load = $this->createMock(Load::class);
		$load->expects($this->once())->method('value')->with(23, 'protocol')->willReturn(2);
		$load->expects($this->once())->method('item')->with(23, $fields)->willReturn($details);
		$ftp = $this->createMock(Ftp::class);
		$ftp->expects($this->never())->method('set');
		$sftp = $this->createMock(Sftp::class);
		$sftp->expects($this->once())->method('set')->with($details)->willReturnSelf();
		$sftp->expects($this->once())->method('move')->with('/tmp/release.tar', 'release.tar')->willReturn(true);
		$user = $this->createMock(User::class);
		$user->expects($this->once())->method('authorise')->with('component.deploy', 'com_componentbuilder')->willReturn(true);

		$this->assertTrue((new Server($load, $ftp, $sftp, $user))->move(
			23,
			'/tmp/release.tar',
			'release.tar',
			null,
			'component.deploy'
		));
	}

	/**
	 * Stop before data or transport boundaries when authorization fails.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testServerRejectsUnauthorizedMoveAndLegacySignature(): void
	{
		$load = $this->createMock(Load::class);
		$load->expects($this->never())->method('value');
		$load->expects($this->never())->method('item');
		$ftp = $this->createMock(Ftp::class);
		$ftp->expects($this->never())->method('move');
		$sftp = $this->createMock(Sftp::class);
		$sftp->expects($this->never())->method('move');
		$user = $this->createMock(User::class);
		$user->expects($this->exactly(2))->method('authorise')->with('core.export', 'com_componentbuilder')->willReturn(false);
		$subject = new Server($load, $ftp, $sftp, $user);

		$this->assertFalse($subject->move(1, '/tmp/a', 'a', 1));
		$this->assertFalse($subject->legacyMove('/tmp/b', 'b', 2, 2));
	}

	/**
	 * Convert selected field names to aliased database selections.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLoadBuildsDatabaseSelectionAliases(): void
	{
		$subject = new class extends Load
		{
			/**
			 * Create an isolated loader fixture.
			 *
			 * @since  6.1.6
			 */
			public function __construct()
			{
			}

			/**
			 * Expose database-field alias generation.
			 *
			 * @param   array<int, string>  $fields  Field names to select.
			 * @param   string              $key     Table alias.
			 *
			 * @return  array<string, string>  Aliased selection map.
			 * @since   6.1.6
			 */
			public function fields(array $fields, string $key = 'a'): array
			{
				return $this->setDatabaseFields($fields, $key);
			}
		};

		$this->assertSame(
			['a.name' => 'name', 'a.protocol' => 'protocol', 'a.path' => 'path'],
			$subject->fields(['name', 'protocol', 'path'])
		);
		$this->assertSame(['server.id' => 'id'], $subject->fields(['id'], 'server'));
		$this->assertSame([], $subject->fields([]));
	}

	/**
	 * Decrypt configured server fields and preserve unconfigured values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLoadModelAppliesTableStorageTransformations(): void
	{
		$crypt = $this->createMock(Crypt::class);
		$crypt->expects($this->exactly(2))->method('decrypt')->willReturnCallback(
			static fn(string $value, string $method): string => $method . ':' . $value
		);
		$crypt->expects($this->never())->method('exist');
		$subject = new LoadModel($crypt, new Table());

		$this->assertSame('basic:cipher-signature', $subject->value('cipher-signature', 'signature', 'server'));
		$this->assertSame('basic:cipher-path', $subject->value('cipher-path', 'path'));
		$this->assertSame('Release server', $subject->value('Release server', 'name', 'server'));
		$this->assertSame('', $subject->value('', 'signature', 'server'));
	}

	/**
	 * Reset cached protocol clients only when connection details change.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testProtocolSettersPreserveEqualDetailsAndResetChangedClients(): void
	{
		$ftp = new Ftp();
		$first = (object) ['signature' => 'host=HOSTNAME'];
		$this->assertSame($ftp, $ftp->set($first));
		$ftpReflection = new ReflectionClass(Ftp::class);
		$client = $this->createStub(FtpClient::class);
		$ftpReflection->getProperty('client')->setValue($ftp, $client);
		$ftp->set(clone $first);
		$this->assertSame($client, $ftpReflection->getProperty('client')->getValue($ftp));
		$ftp->set((object) ['signature' => 'host=changed']);
		$this->assertNull($ftpReflection->getProperty('client')->getValue($ftp));
		$this->assertFalse($ftp->set((object) ['signature' => 'host=HOSTNAME'])->move('/missing', 'file.txt'));

		$sftp = new Sftp($this->createStub(KeyLoader::class), $this->createStub(CMSApplicationInterface::class));
		$sftpReflection = new ReflectionClass(Sftp::class);
		$this->assertSame($sftp, $sftp->set((object) ['host' => '', 'username' => '']));
		$this->assertFalse($sftp->move('/missing', 'file.txt'));
		$this->assertNull($sftpReflection->getProperty('client')->getValue($sftp));
	}
}
