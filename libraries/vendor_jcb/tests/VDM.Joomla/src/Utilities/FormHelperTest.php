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

namespace VDM\Joomla\Tests\Utilities;


use Joomla\CMS\Form\Field\TextField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use SimpleXMLElement;
use stdClass;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\FormHelper;
use VDM\Tests\Support\TestCase;


/**
 * Joomla form-field XML construction contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(FormHelper::class)]
#[UsesClass(ArrayHelper::class)]
final class FormHelperTest extends TestCase
{
	/**
	 * Build exact field attributes and option nodes in insertion order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testXmlBuildsAttributesAndOptionsInOrder(): void
	{
		$xml = FormHelper::xml(
			[
				'name' => 'status',
				'type' => 'list',
				'label' => 'Status & state',
				'description' => null
			],
			[
				'' => 'Select a value',
				'1' => 'Published & visible',
				'0' => 'Unpublished'
			]
		);

		$this->assertInstanceOf(SimpleXMLElement::class, $xml);
		$this->assertSame('status', (string) $xml['name']);
		$this->assertSame('list', (string) $xml['type']);
		$this->assertSame('Status & state', (string) $xml['label']);
		$this->assertSame('', (string) $xml['description']);
		$texts = [];
		$values = [];

		foreach ($xml->option as $option)
		{
			$texts[] = (string) $option;
			$values[] = (string) $option['value'];
		}

		$this->assertSame(['Select a value', 'Published & visible', 'Unpublished'], $texts);
		$this->assertSame(['', '1', '0'], $values);
	}

	/**
	 * Reject an empty attribute map rather than creating a meaningless field node.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testXmlRequiresAtLeastOneAttribute(): void
	{
		$this->assertNull(FormHelper::xml([]));
	}

	/**
	 * Mutate an existing node with exact attributes and child option values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAttributesAndOptionsMutateExistingNode(): void
	{
		$xml = new SimpleXMLElement('<field/>');

		FormHelper::attributes($xml, ['name' => 'category', 'multiple' => 'true']);
		FormHelper::options($xml, ['all' => 'All', 'featured' => 'Featured']);

		$this->assertSame('category', (string) $xml['name']);
		$this->assertSame('true', (string) $xml['multiple']);
		$this->assertSame('all', (string) $xml->option[0]['value']);
		$this->assertSame('All', (string) $xml->option[0]);
		$this->assertSame('featured', (string) $xml->option[1]['value']);
		$this->assertSame('Featured', (string) $xml->option[1]);
	}

	/**
	 * Import an independent XML subtree without losing nested content.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAppendImportsCompleteSimpleXmlSubtree(): void
	{
		$xml = new SimpleXMLElement('<form><fields name="main"/></form>');
		$node = new SimpleXMLElement(
			'<fieldset name="details"><field name="title" type="text"/></fieldset>'
		);

		FormHelper::append($xml->fields, $node);

		$this->assertSame('details', (string) $xml->fields->fieldset['name']);
		$this->assertSame('title', (string) $xml->fields->fieldset->field['name']);
		$this->assertSame('text', (string) $xml->fields->fieldset->field['type']);
	}

	/**
	 * Append a documented comment-wrapper object before its field XML.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAppendSupportsCommentAndFieldWrapper(): void
	{
		$xml = new SimpleXMLElement('<fieldset/>');
		$wrapper = new stdClass();
		$wrapper->comment = 'Generated field contract';
		$wrapper->fieldXML = new SimpleXMLElement('<field name="alias" type="text"/>');

		FormHelper::append($xml, $wrapper);

		$this->assertSame(
			'<fieldset><!--Generated field contract--><field name="alias" type="text"/></fieldset>',
			$this->withoutXmlDeclaration($xml)
		);
	}

	/**
	 * Append standalone comments and ignore falsy nodes without changing XML.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCommentAndFalsyAppendHaveStableEffects(): void
	{
		$xml = new SimpleXMLElement('<fieldset/>');

		FormHelper::append($xml, null);
		FormHelper::comment($xml, 'One & two');

		$this->assertSame(
			'<fieldset><!--One & two--></fieldset>',
			$this->withoutXmlDeclaration($xml)
		);
	}

	/**
	 * Load and set up a Joomla core field from the generated XML definition.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldLoadsJoomlaTypeAndAppliesDefaultValue(): void
	{
		$field = FormHelper::field(
			['name' => 'title', 'type' => 'text', 'label' => 'Title'],
			'Default title'
		);

		$this->assertInstanceOf(TextField::class, $field);
		$this->assertSame('title', $field->fieldname);
		$this->assertSame('Text', $field->type);
		$this->assertSame('Default title', $field->value);
	}

	/**
	 * Reject field requests without a configured Joomla field type.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldRequiresAttributesAndType(): void
	{
		$this->assertNull(FormHelper::field([]));
		$this->assertNull(FormHelper::field(['name' => 'title']));
	}

	/**
	 * Serialize SimpleXML without its declaration or trailing newline.
	 *
	 * @param   SimpleXMLElement  $xml  XML node to serialize.
	 *
	 * @return  string  Serialized node.
	 * @since   6.1.6
	 */
	private function withoutXmlDeclaration(SimpleXMLElement $xml): string
	{
		$serialized = $xml->asXML();

		$this->assertIsString($serialized);

		return trim((string) preg_replace('/^<\?xml version="1\.0"\?>\s*/', '', $serialized));
	}
}
