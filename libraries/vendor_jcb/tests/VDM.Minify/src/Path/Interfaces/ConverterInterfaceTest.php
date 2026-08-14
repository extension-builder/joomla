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

namespace VDM\Minify\Tests\Path\Interfaces;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VDM\Minify\Path\Converter;
use VDM\Minify\Path\Interfaces\ConverterInterface;


/**
 * Path converter substitution contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Converter::class)]
final class ConverterInterfaceTest extends TestCase
{
	/**
	 * Accept the production converter through the stable interface boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testProductionConverterSatisfiesRelativePathContract(): void
	{
		$converter = new Converter('/project/css', '/project/build');

		$this->assertSame(
			'../images/icon.svg',
			$this->convert($converter, '../images/icon.svg')
		);
	}

	/**
	 * Consume an implementation only through its public interface.
	 *
	 * @param   ConverterInterface  $converter  The path converter.
	 * @param   string              $path       The relative source path.
	 *
	 * @return  string
	 * @since   6.1.6
	 */
	private function convert(ConverterInterface $converter, string $path): string
	{
		return $converter->convert($path);
	}
}
