<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    15th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ComHelperClass;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\ComHelperClass\UikitMethods;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Component Helper Class UIkit Methods Test.
 *
 * @since  6.1.7
 */
#[CoversClass(UikitMethods::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class UikitMethodsTest extends ArchitectureTestCase
{
	/**
	 * Without UIkit version 2 support no helper methods are generated.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testUikitMethodsAreEmptyWithoutUikitTwo(): void
	{
		$this->config()->set('uikit', 0);

		$subject = new UikitMethods($this->config());

		$this->assertSame('', $subject->get());
	}

	/**
	 * UIkit switches 1 and 2 generate the component map and detector.
	 *
	 * @param   int  $uikit  The UIkit configuration switch.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('uikitSwitches')]
	public function testUikitMethodsGenerateTheComponentMapAndDetector(int $uikit): void
	{
		$this->config()->set('uikit', $uikit);

		$subject = new UikitMethods($this->config());
		$code = $subject->get();

		$this->assertStringStartsWith(PHP_EOL . PHP_EOL . "\t/**", $code);
		$this->assertStringContainsString(
			"\tpublic static \$uk_components = array(",
			$code
		);
		$this->assertStringContainsString("\t\t\t'data-uk-grid' => array(", $code);
		$this->assertStringContainsString("\t\t\t\t'upload', 'form-file' )", $code);
		$this->assertStringContainsString("\tpublic static \$uikit = false;", $code);
		$this->assertStringContainsString(
			"\tpublic static function getUikitComp(\$content,\$classes = array())",
			$code
		);
		$this->assertStringContainsString(
			'Super_' . '__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check($temp)',
			$code
		);
		$this->assertStringContainsString("\t\tif (strpos(\$content ?? '','class=\"uk-') !== false)", $code);
		$this->assertStringEndsWith("\t}", $code);
	}

	/**
	 * The UIkit switches that activate the generated methods.
	 *
	 * @return  array<string, array{int}>
	 * @since   6.1.7
	 */
	public static function uikitSwitches(): array
	{
		return [
			'version two' => [2],
			'version one compatibility' => [1],
		];
	}
}
