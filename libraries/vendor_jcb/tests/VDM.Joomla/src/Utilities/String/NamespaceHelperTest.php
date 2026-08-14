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

namespace VDM\Joomla\Tests\Utilities\String;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Utilities\String\NamespaceHelper;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Namespace and namespace-segment normalization test.
 *
 * @since  6.1.6
 */
#[CoversClass(NamespaceHelper::class)]
#[UsesClass(StringHelper::class)]
final class NamespaceHelperTest extends TestCase
{
	/**
	 * Normalize every namespace segment without losing namespace boundaries.
	 *
	 * @param   string  $input     Candidate namespace.
	 * @param   string  $expected  Expected safe namespace.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('namespaceProvider')]
	public function testSafeNormalizesCompleteNamespace(string $input, string $expected): void
	{
		$this->assertSame($expected, NamespaceHelper::safe($input));
	}

	/**
	 * Provide boundary slash, punctuation, number, and empty cases.
	 *
	 * @return  iterable<string, array{string, string}>
	 * @since   6.1.6
	 */
	public static function namespaceProvider(): iterable
	{
		yield 'trim boundaries and clean segments' => [
			'\\VDM\\Joomla-Tools\\3D.Model\\',
			'VDM\\JoomlaTools\\threeDModel'
		];
		yield 'embedded numbers remain' => ['Version6\\Model', 'Version6\\Model'];
		yield 'segment spaces removed' => ['VDM\\Component Builder', 'VDM\\ComponentBuilder'];
		yield 'empty namespace' => ['', ''];
	}

	/**
	 * Convert every leading numeric run and retain only ASCII identifier characters.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSafeSegmentProtectsLeadingNumericIdentifier(): void
	{
		$this->assertSame('twelveModel', NamespaceHelper::safeSegment('12-Model'));
		$this->assertSame('Model6Value', NamespaceHelper::safeSegment('Model.6-Value'));
		$this->assertSame('ber', NamespaceHelper::safeSegment('Über'));
	}

	/**
	 * Prevent an empty namespace segment when repeated separators are supplied.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testSafeRemovesEmptyNamespaceSegments(): void
	{
		$this->assertDoesNotMatchRegularExpression(
			'/\\\\{2,}/',
			NamespaceHelper::safe('VDM\\\\Joomla')
		);
	}
}
