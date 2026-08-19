<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Dashboard;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Dashboard\ModelMethods;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The methods a dashboard model was built with, and what the view reads back.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\Dashboard')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ModelMethodsTest extends ArchitectureTestCase
{
	/**
	 * What a component was built with.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const GIVEN = <<<'PHP'
public function getMyThing()
{
	return 1;
}

public function getOther()
{
	return 2;
}
PHP;

	/**
	 * The statements the dashboard view reads its data back with.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_DATA = <<<'GEN'

		$this->mything = $this->get('MyThing');
		$this->other = $this->get('Other');
GEN;

	/**
	 * The methods a component was built with are carried into its model.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheMethodsAComponentWasBuiltWithAreCarriedIntoItsModel(): void
	{
		$subject = $this->builtWith(self::GIVEN);

		$this->assertSame(PHP_EOL . PHP_EOL . self::GIVEN, $subject->get());
	}

	/**
	 * The view reads back whatever those methods get.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheViewReadsBackWhateverThoseMethodsGet(): void
	{
		$subject = $this->builtWith(self::GIVEN);
		$subject->get();

		$this->assertSame(['MyThing', 'Other'], $subject->names());
		$this->assertSame(self::EXPECTED_DATA, $subject->customData());
	}

	/**
	 * Nothing is read back before the methods have been read.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testNothingIsReadBackBeforeTheMethodsHaveBeen(): void
	{
		$subject = $this->builtWith(self::GIVEN);

		$this->assertNull($subject->names());
		$this->assertSame('', $subject->customData());
	}

	/**
	 * A component built with no methods gets none, and reads nothing back.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentBuiltWithNoMethodsGetsNone(): void
	{
		$subject = $this->builtWith(null);

		$this->assertSame('', $subject->get());
		$this->assertNull($subject->names());
		$this->assertSame('', $subject->customData());
	}

	/**
	 * A component built with methods that get nothing names none of them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testMethodsThatGetNothingNameNoneOfThem(): void
	{
		$subject = $this->builtWith('public function notAGetter() {}');
		$subject->get();

		$this->assertNull($subject->names());
		$this->assertSame('', $subject->customData());
	}

	/**
	 * A renderer over a component built with the given methods.
	 *
	 * @param   string|null  $methods  What the component was built with.
	 *
	 * @return  ModelMethods
	 * @since   6.1.7
	 */
	private function builtWith(?string $methods): ModelMethods
	{
		$subject = $this->renderer(ModelMethods::class);

		if ($methods !== null)
		{
			$this->componentOf($subject)->set('php_dashboard_methods', $methods);
		}

		return $subject;
	}

	/**
	 * The component registry a renderer was given.
	 *
	 * @param   ModelMethods  $subject  The renderer.
	 *
	 * @return  Component
	 * @since   6.1.7
	 */
	private function componentOf(ModelMethods $subject): Component
	{
		$property = new ReflectionProperty($subject, 'component');
		$property->setAccessible(true);

		return $property->getValue($subject);
	}
}
