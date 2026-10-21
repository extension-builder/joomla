<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    21st October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Component;


use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Component\Template;
use VDM\Joomla\Componentbuilder\Compiler\Component\JoomlaSix\Template as JoomlaSixTemplate;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Component\TemplateInterface;
use VDM\Joomla\Componentbuilder\Compiler\Service\Component as ComponentProvider;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Tests\Support\TestCase;


/**
 * Component template source selection and custom directory precedence.
 *
 * @since  6.1.7
 */
#[CoversClass(Template::class)]
#[CoversClass(JoomlaSixTemplate::class)]
#[UsesClass(ComponentProvider::class)]
final class TemplateTest extends TestCase
{
	/**
	 * The overlay changes only existing leaf template sources from the default root.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOverlayPreservesFallbackAndCustomSourcePaths(): void
	{
		$root = sys_get_temp_dir() . '/jcb-template-' . bin2hex(random_bytes(6));
		if (!mkdir($root, 0700))
		{
			throw new RuntimeException('Unable to create the template source fixture.');
		}

		try
		{
			if (file_put_contents($root . '/VIEW.php.tpl', 'native') === false)
			{
				throw new RuntimeException('Unable to write the template source fixture.');
			}

			$sourcePath = '/built-in/joomla_4';
			$paths = $this->createStub(Paths::class);
			$paths->method('__get')->willReturnCallback(static function (string $key) use (&$sourcePath): string
			{
				return $sourcePath;
			});
			$shared = new Template($paths);
			$native = new JoomlaSixTemplate($paths, $root, $sourcePath);

			$this->assertSame('/built-in/joomla_4/VIEW.php', $shared->get('VIEW.php'));
			$this->assertSame($root . '/VIEW.php.tpl', $native->get('VIEW.php'));
			$this->assertSame('native', file_get_contents($native->get('VIEW.php')));

			foreach (['MISSING.php', '../VIEW.php', 'folder/VIEW.php', 'folder\\VIEW.php', '/VIEW.php', 'C:\\VIEW.php', '', '.', '..'] as $file)
			{
				$this->assertSame($sourcePath . '/' . $file, $native->get($file));
			}

			$sourcePath = '/custom/joomla_4';
			$this->assertSame('/custom/joomla_4/VIEW.php', $native->get('VIEW.php'));
			$this->assertSame('/custom/joomla_4/MISSING.php', $native->get('MISSING.php'));

			$sourcePath = '\\built-in\\joomla_4\\';
			$this->assertSame($root . '/VIEW.php.tpl', $native->get('VIEW.php'));
		}
		finally
		{
			if (is_file($root . '/VIEW.php.tpl') && !unlink($root . '/VIEW.php.tpl'))
			{
				throw new RuntimeException('Unable to remove the template source fixture.');
			}

			if (!rmdir($root))
			{
				throw new RuntimeException('Unable to remove the template fixture directory.');
			}
		}
	}

	/**
	 * Every target key resolves one shared resolver and preserves configured input paths.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testProviderUsesTargetMajorAndStableSharedAliases(): void
	{
		foreach ([3, 4, 5, 6] as $version)
		{
			$container = new Container();
			$container->share('Config', (object) ['joomla_version' => $version], true);
			$paths = $this->createStub(Paths::class);
			$paths->method('__get')->willReturn('/custom/joomla_' . ($version === 3 ? 3 : 4));
			$container->share('Utilities.Paths', $paths, true);
			(new ComponentProvider())->register($container);

			$resolver = $container->get(TemplateInterface::class);
			$this->assertSame('/custom/joomla_' . ($version === 3 ? 3 : 4) . '/VIEW.php', $resolver->get('VIEW.php'));
			$this->assertSame($resolver, $container->get('Component.Template'));
			$this->assertSame($resolver, $container->get('Component.J' . $version . '.Template'));
			$this->assertSame($version === 6 ? JoomlaSixTemplate::class : Template::class, $resolver::class);
			$this->assertSame($container->get('Component.Shared.Template'), $container->get('Component.J3.Template'));
			$this->assertSame($container->get('Component.Shared.Template'), $container->get('Component.J4.Template'));
			$this->assertSame($container->get('Component.Shared.Template'), $container->get('Component.J5.Template'));
		}
	}
}
