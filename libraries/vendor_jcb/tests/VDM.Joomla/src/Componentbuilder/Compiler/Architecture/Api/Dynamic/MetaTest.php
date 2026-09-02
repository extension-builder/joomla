<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    2nd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Dynamic;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\Meta;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The document meta of a dynamic get list resource.
 *
 * @since 6.1.7
 */
#[CoversClass(Meta::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class MetaTest extends ArchitectureTestCase
{
	/**
	 * The meta lines of a list view with two custom gets.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED = <<<'GEN'


		// The owner custom get of the trucks view rides along as meta.
		$this->getDocument()->addMeta('owner', $this->getModel()->getOwner());

		// The brands custom get of the trucks view rides along as meta.
		$this->getDocument()->addMeta('brands', $this->getModel()->getBrands());
GEN;

	/**
	 * A view without custom gets adds no meta.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutCustomGetsAddsNoMeta(): void
	{
		$this->assertSame('', $this->renderer(Meta::class)->get([
			'code' => 'trucks', 'settings' => (object) ['custom_get' => null],
		]));
	}

	/**
	 * Every custom get of a list view rides along as meta.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryCustomGetOfAListViewRidesAlongAsMeta(): void
	{
		$this->assertSame(self::EXPECTED, $this->renderer(Meta::class)->get([
			'code' => 'trucks',
			'settings' => (object) ['custom_get' => [
				(object) ['gettype' => 3, 'getcustom' => 'getOwner'],
				(object) ['gettype' => 4, 'getcustom' => 'getBrands'],
			]],
		]));
	}
}
