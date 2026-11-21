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
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\DI\Container;
use phpseclib3\Net\SSH2;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Server\ClientFactory;
use VDM\Joomla\Componentbuilder\Crypt\KeyLoader;
use VDM\Joomla\Componentbuilder\Server\Ftp;
use VDM\Joomla\Componentbuilder\Server\Sftp;
use VDM\Joomla\Componentbuilder\Service\Server as ServerProvider;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Protocol-client isolation and endpoint configuration tests.
 *
 * @since  6.2.1
 */
#[CoversClass(ClientFactory::class)]
#[CoversClass(ServerProvider::class)]
final class ClientFactoryTest extends JoomlaTestCase
{
	/**
	 * Construct independent configured clients without opening network sockets.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testFactoryPreservesConfigurationAndDoesNotShareClients(): void
	{
		$factory = new ClientFactory();
		$ftp = $factory->ftp(['timeout' => 17]);
		$other = $factory->ftp(['timeout' => 29]);
		$this->assertNotSame($ftp, $other);
		$this->assertFalse((bool) $ftp->isConnected());
		$ftpReflection = new ReflectionClass(FtpClient::class);
		$this->assertSame(17, $ftpReflection->getProperty('_timeout')->getValue($ftp));
		$this->assertSame(29, $ftpReflection->getProperty('_timeout')->getValue($other));
		$sftp = $factory->sftp('sftp.example.test', 2222);
		$this->assertFalse($sftp->isConnected());
		$this->assertFalse($sftp->isAuthenticated());
		$sshReflection = new ReflectionClass(SSH2::class);
		$this->assertSame('sftp.example.test', $sshReflection->getProperty('host')->getValue($sftp));
		$this->assertSame(2222, $sshReflection->getProperty('port')->getValue($sftp));
		$this->assertNotSame($sftp, $factory->sftp('sftp.example.test', 2222));
	}
	/**
	 * Resolve shared factory aliases and inject the same boundary into both transports.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testProviderInjectsSharedClientFactory(): void
	{
		$this->setJoomlaApplication($this->createStub(CMSApplicationInterface::class));
		$container = new Container();
		$container->set('Crypt.Key', new KeyLoader());
		(new ServerProvider())->register($container);
		$factory = $container->get('Server.ClientFactory');
		$this->assertSame($factory, $container->get(ClientFactory::class));
		$ftp = $container->get('Server.FTP');
		$sftp = $container->get('Server.SFTP');
		$this->assertSame($ftp, $container->get(Ftp::class));
		$this->assertSame($sftp, $container->get(Sftp::class));
		$this->assertSame($factory, (new ReflectionClass(Ftp::class))->getProperty('clients')->getValue($ftp));
		$this->assertSame($factory, (new ReflectionClass(Sftp::class))->getProperty('clients')->getValue($sftp));
	}

}
