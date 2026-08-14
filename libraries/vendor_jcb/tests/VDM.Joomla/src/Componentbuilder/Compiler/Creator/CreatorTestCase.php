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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Creator;


use ReflectionProperty;
use VDM\Joomla\Utilities\String\FieldHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\CompilerUtilityTestCase;


/**
 * Isolates compiler creator formatting and transliteration state.
 *
 * @since  6.1.6
 */
abstract class CreatorTestCase extends CompilerUtilityTestCase
{
	/**
	 * Language tag active before the current test.
	 *
	 * @var    string|null
	 * @since  6.1.6
	 */
	private ?string $languageTag = null;

	/**
	 * Field naming mode active before the current test.
	 *
	 * @var    mixed
	 * @since  6.1.6
	 */
	private $fieldNameBuilder;

	/**
	 * Use deterministic English transliteration without booting Joomla.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->languageTag = StringHelper::$langTag;
		StringHelper::$langTag = 'en-GB';
		$builder = new ReflectionProperty(FieldHelper::class, 'builder');
		$this->fieldNameBuilder = $builder->getValue();
		$builder->setValue(null, 1);
	}

	/**
	 * Restore process-static transliteration state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		StringHelper::$langTag = $this->languageTag;
		$this->languageTag = null;
		(new ReflectionProperty(FieldHelper::class, 'builder'))
			->setValue(null, $this->fieldNameBuilder);
		$this->fieldNameBuilder = null;

		parent::tearDown();
	}
}
