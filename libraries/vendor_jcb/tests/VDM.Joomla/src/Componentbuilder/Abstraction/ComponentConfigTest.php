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

namespace VDM\Joomla\Tests\Componentbuilder\Abstraction;

use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\FunctionRegistry;
use VDM\Joomla\Componentbuilder\Abstraction\ComponentConfig;
use VDM\Tests\Support\ComponentConfigFixture;
use VDM\Tests\Support\TestCase;

/**
 * Component configuration precedence and cache contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(ComponentConfig::class)]
#[UsesClass(FunctionRegistry::class)]
final class ComponentConfigTest extends TestCase
{
	/**
	 * Resolve generated, parameter, input, and default values in strict order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetAppliesDynamicParamInputAndDefaultPrecedence(): void
	{
		$input = new Input(
			[
				'parameter_value' => 'input-shadowed',
				'input_value' => 'request-value',
			]
		);
		$params = new Registry(
			[
				'parameter_value' => 'configured-value',
				'generated_value' => 'parameter-shadowed',
			]
		);
		$subject = new ComponentConfigFixture($input, $params);

		$this->assertSame('generated-value', $subject->get('generated_value', 'fallback'));
		$this->assertSame('configured-value', $subject->get('parameter_value'));
		$this->assertSame('request-value', $subject->get('input_value'));
		$this->assertSame('fallback', $subject->get('missing', 'fallback'));
		$this->assertSame(1, $subject->dynamicCalls);
	}

	/**
	 * Cache external values and keep explicit registry state authoritative.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResolvedExternalValuesAreCachedAndExplicitStateWins(): void
	{
		$input = new Input(['input_value' => 'first']);
		$params = new Registry(['parameter_value' => 'first-param']);
		$subject = new ComponentConfigFixture($input, $params);

		$this->assertSame('first', $subject->get('input_value'));
		$this->assertSame('first-param', $subject->get('parameter_value'));
		$input->set('input_value', 'second');
		$params->set('parameter_value', 'second-param');
		$this->assertSame('first', $subject->get('input_value'));
		$this->assertSame('first-param', $subject->get('parameter_value'));

		$subject->set('input_value', 'explicit');
		$this->assertSame('explicit', $subject->get('input_value'));
	}
}
