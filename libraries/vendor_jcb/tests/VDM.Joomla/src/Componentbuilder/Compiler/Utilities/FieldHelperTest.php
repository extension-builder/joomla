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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\FieldHelper;
use VDM\Joomla\Utilities\Base64Helper;
use VDM\Joomla\Utilities\GetHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\TestCase;


/**
 * Stored field-XML attribute extraction contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(FieldHelper::class)]
#[UsesClass(Base64Helper::class)]
#[UsesClass(GetHelper::class)]
#[UsesClass(StringHelper::class)]
final class FieldHelperTest extends TestCase
{
	/**
	 * Extract the first exact quoted attribute without mutating reference inputs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetValueExtractsExactAttributeAndPreservesInputs(): void
	{
		$xml = '<field name="title" label="Article Title" name="ignored" />';
		$get = 'name';

		$this->assertSame('title', FieldHelper::getValue($xml, $get));
		$this->assertSame('<field name="title" label="Article Title" name="ignored" />', $xml);
		$this->assertSame('name', $get);
	}

	/**
	 * Return the supplied confirmation when XML or the selected attribute is absent.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetValueUsesConfirmationForEmptyOrMissingData(): void
	{
		$get = 'name';
		$empty = '';
		$xml = '<field label="Title" />';

		$this->assertSame('fallback', FieldHelper::getValue($empty, $get, 'fallback'));
		$this->assertSame('fallback', FieldHelper::getValue($xml, $get, 'fallback'));
	}

	/**
	 * Decode PHP-bearing attributes from both raw and JCB-suffixed Base64 values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetValueDecodesPhpAttributes(): void
	{
		$get = 'type_php_code';
		$code = "return ['enabled' => true];";
		$encoded = base64_encode($code);
		$rawXml = '<field type_php_code="' . $encoded . '" />';
		$suffixedXml = '<field type_php_code="' . $encoded . '__.o0=base64=Oo.__" />';

		$this->assertSame($code, FieldHelper::getValue($rawXml, $get));
		$this->assertSame($code, FieldHelper::getValue($suffixedXml, $get));
	}
}
