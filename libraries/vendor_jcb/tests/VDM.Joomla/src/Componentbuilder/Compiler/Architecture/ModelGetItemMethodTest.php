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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\GetItemMethod;
use VDM\Joomla\Componentbuilder\Compiler\Builder\BaseSixFour;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\JsonItem;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelBasicField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Tags;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;


/**
 * Generated item model getItem contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ModelGetItemMethodTest extends ArchitectureTestCase
{
	/**
	 * What an item model does with a field stored encoded, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ITEM_BASE64 = <<<'GEN'


			if (!empty($item->secret))
			{
				// base64 Decode secret.
				$item->secret = base64_decode($item->secret);
			}
GEN;

	/**
	 * What an item model does with a field stored encrypted, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ITEM_BASIC = <<<'GEN'


			// Get the basic encryption.
			$basickey = DemoHelper::getCryptKey('basic');
			// Get the encryption object.
			$basic = new Super___99175f6d_dba8_4086_8a65_5c4ec175e61d___Power($basickey);

			if (!empty($item->locked) && $basickey && !is_numeric($item->locked) && $item->locked === base64_encode(base64_decode($item->locked, true)))
			{
				// basic decrypt data locked.
				$item->locked = rtrim($basic->decryptString($item->locked), "\0");
			}
GEN;

	/**
	 * What an item model does with a field stored as json, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ITEM_JSON = <<<'GEN'


			if (!empty($item->payload))
			{
				// Convert the payload field to an array.
				$payload = new Registry;
				$payload->loadString($item->payload);
				$item->payload = $payload->toArray();
			}
GEN;

	/**
	 * What an item model does with the tags of an item, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ITEM_TAGS = <<<'GEN'


			if (!empty($item->id))
			{
				// Get Tag IDs.
				$item->tags = new TagsHelper;
				$item->tags->getTagIds($item->id, 'com_demo.demo');
			}
GEN;

	/**
	 * Build the getItem writer.
	 *
	 * @param   array  $knowledge  What the compiler knows about the item.
	 *
	 * @return  GetItemMethod
	 * @since   6.1.7
	 */
	private function subject(array $knowledge = []): GetItemMethod
	{
		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');
		$contentone->set('component', 'demo');

		$dispenser = (new ReflectionClass(Dispenser::class))->newInstanceWithoutConstructor();
		$dispenser->hub = [];

		return $this->renderer(GetItemMethod::class, array_merge([
			'contentone' => $contentone,
			'dispenser' => $dispenser,
		], $knowledge));
	}

	/**
	 * An item the component stores plainly needs nothing done to it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnItemStoredPlainlyNeedsNothingDone(): void
	{
		$view = 'demo';

		$this->assertSame('', $this->subject()->get($view));
	}

	/**
	 * A field the component stored encoded is decoded again.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFieldStoredEncodedIsDecodedAgain(): void
	{
		$encoded = new BaseSixFour();
		$encoded->set('demo', ['secret']);
		$view = 'demo';

		$this->assertSame(
			self::EXPECTED_ITEM_BASE64,
			$this->subject(['basesixfour' => $encoded])->get($view)
		);
	}

	/**
	 * A field the component stored encrypted is decrypted again.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFieldStoredEncryptedIsDecryptedAgain(): void
	{
		$locked = new ModelBasicField();
		$locked->set('demo', ['locked']);
		$view = 'demo';

		$this->assertSame(
			self::EXPECTED_ITEM_BASIC,
			$this->subject(['modelbasicfield' => $locked])->get($view)
		);
	}

	/**
	 * A field the component stored as json is unpacked again.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFieldStoredAsJsonIsUnpackedAgain(): void
	{
		$json = new JsonItem();
		$json->set('demo', ['payload']);
		$view = 'demo';

		$this->assertSame(
			self::EXPECTED_ITEM_JSON,
			$this->subject(['jsonitem' => $json])->get($view)
		);
	}

	/**
	 * An item that carries tags has them loaded.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnItemThatCarriesTagsHasThemLoaded(): void
	{
		$tags = new Tags();
		$tags->set('demo', true);
		$view = 'demo';

		$this->assertSame(
			self::EXPECTED_ITEM_TAGS,
			$this->subject(['tags' => $tags])->get($view)
		);
	}
}
