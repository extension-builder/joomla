<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    1st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\View;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\View\PrepareItem;
use VDM\Joomla\Componentbuilder\Compiler\Builder\BaseSixFour;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ItemsMethodListString;
use VDM\Joomla\Componentbuilder\Compiler\Builder\JsonItem;
use VDM\Joomla\Componentbuilder\Compiler\Builder\JsonItemArray;
use VDM\Joomla\Componentbuilder\Compiler\Builder\JsonString;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelBasicField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Tags;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The prepare item code of the JSON API views.
 *
 * @since 6.1.7
 */
#[CoversClass(PrepareItem::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class PrepareItemTest extends ArchitectureTestCase
{
	/**
	 * The tag conversion of an item view with tags.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_TAGS = <<<'GEN'

		if (isset($item->tags) && $item->tags instanceof TagsHelper)
		{
			// Convert the tags to their names.
			$tags = (string) $item->tags->tags;
			$ids = ($tags !== '') ? explode(',', $tags) : [];
			$names = ($ids !== []) ? $item->tags->getTagNames($ids) : [];
			$item->tags = (count($ids) === count($names)) ? array_combine($ids, $names) : array_values($names);
		}

GEN;

	/**
	 * The decoding of a list item with base64, encrypted and JSON stored values.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_LIST = <<<'GEN'

		if (isset($item->blob) && is_string($item->blob) && $item->blob !== '')
		{
			// base64 Decode blob.
			$item->blob = base64_decode($item->blob);
		}

		// Get the basic encryption.
		$basickey = DemoHelper::getCryptKey('basic');
		// Get the encryption object.
		$basic = new Super___99175f6d_dba8_4086_8a65_5c4ec175e61d___Power($basickey);

		if (!empty($item->locked) && $basickey && is_string($item->locked) && !is_numeric($item->locked) && $item->locked === base64_encode(base64_decode($item->locked, true)))
		{
			// basic decrypt data locked.
			$item->locked = rtrim($basic->decryptString($item->locked), "\0");
		}

		if (isset($item->options) && is_string($item->options) && $item->options !== '')
		{
			// Convert the options field to an array.
			$registry = new Registry;
			$registry->loadString($item->options);
			$item->options = $registry->toArray();
		}

		if (isset($item->groups) && is_string($item->groups) && $item->groups !== '')
		{
			// JSON Decode groups.
			$item->groups = json_decode($item->groups, true);
		}

GEN;

	/**
	 * An item without tags needs nothing done.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnItemWithoutTagsNeedsNothingDone(): void
	{
		$subject = $this->renderer(PrepareItem::class);

		$this->assertSame('', $subject->get('demo'));
	}

	/**
	 * An items tags become their names.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnItemsTagsBecomeTheirNames(): void
	{
		$tags = new Tags();
		$tags->set('demo', true);

		$subject = $this->renderer(PrepareItem::class, ['tags' => $tags]);

		$this->assertSame(self::EXPECTED_TAGS, $subject->get('demo'));
	}

	/**
	 * A list item decodes what the list model left raw.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAListItemDecodesWhatTheListModelLeftRaw(): void
	{
		$this->assertSame(self::EXPECTED_LIST, $this->subject()->get('demo', true));
	}

	/**
	 * A list item leaves what the list model already prepared.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAListItemLeavesWhatTheListModelAlreadyPrepared(): void
	{
		$code = $this->subject()->get('demo', true);

		$this->assertStringNotContainsString('$item->params', $code);
	}

	/**
	 * A list item needs nothing done when nothing is stored encoded.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAListItemNeedsNothingDoneWhenNothingIsStoredEncoded(): void
	{
		$subject = $this->renderer(PrepareItem::class);

		$this->assertSame('', $subject->get('demo', true));
	}

	/**
	 * A view storing one base64, one encrypted, two JSON and one already listed value.
	 *
	 * @return  PrepareItem
	 * @since   6.1.7
	 */
	private function subject(): PrepareItem
	{
		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');

		$encoded = new BaseSixFour();
		$encoded->add('demo', 'blob', true);

		$locked = new ModelBasicField();
		$locked->add('demo', 'locked', true);

		$json = new JsonItem();
		$json->add('demo', 'options', true);

		$string = new JsonString();
		$string->add('demo', 'params', true);
		$string->add('demo', 'groups', true);

		$array = new JsonItemArray();
		$array->add('demo', 'groups', true);

		$listed = new ItemsMethodListString();
		$listed->add('demo', [
			'name' => 'params',
			'type' => 'textarea',
			'translation' => false,
			'custom' => null,
			'method' => 1,
		], true);

		return $this->renderer(PrepareItem::class, [
			'contentone' => $contentone,
			'basesixfour' => $encoded,
			'modelbasicfield' => $locked,
			'jsonitem' => $json,
			'jsonstring' => $string,
			'jsonitemarray' => $array,
			'itemsmethodliststring' => $listed,
		]);
	}
}
