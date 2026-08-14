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

namespace VDM\Joomla\Tests\Abstraction;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use stdClass;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Tests\Support\FilesystemTestCase;
use VDM\Tests\Support\RegistryFixture;


/**
 * Joomla-compatible VDM Registry behavior tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
final class RegistryTest extends FilesystemTestCase
{
	/**
	 * Load constructor arrays, objects, and JSON without losing nested data.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorLoadsSupportedRepresentations(): void
	{
		$fromArray = new RegistryFixture(['alpha' => ['beta' => 7]]);
		$fromObject = new RegistryFixture((object) ['alpha' => (object) ['beta' => 8]]);
		$fromJson = new RegistryFixture('{"alpha":{"beta":9}}');

		$this->assertSame(7, $fromArray->get('alpha.beta'));
		$this->assertSame(8, $fromObject->get('alpha.beta'));
		$this->assertSame(9, $fromJson->get('alpha.beta'));
	}

	/**
	 * Apply path lifecycle operations fluently and ignore empty path segments.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPathLifecycleAndFluentMutationContract(): void
	{
		$subject = new RegistryFixture();

		$this->assertSame($subject, $subject->set('root..leaf', 'alpha'));
		$this->assertSame($subject, $subject->append('root.leaf', '-beta'));
		$this->assertTrue($subject->exists('root.leaf'));
		$this->assertSame('alpha-beta', $subject->get('root.leaf'));
		$this->assertSame('fallback', $subject->get('root.missing', 'fallback'));
		$this->assertSame($subject, $subject->remove('root.leaf'));
		$this->assertFalse($subject->exists('root.leaf'));
	}

	/**
	 * Reject empty paths consistently at the public path boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmptyPathIsRejected(): void
	{
		$subject = new RegistryFixture();

		$this->expectException(InvalidArgumentException::class);

		$subject->set('', 'value');
	}

	/**
	 * Support magic properties and ArrayAccess through the same path semantics.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMagicPropertiesAndArrayAccessShareRegistryState(): void
	{
		$subject = new RegistryFixture();

		$subject->title = 'Component';
		$subject['config.enabled'] = true;

		$this->assertTrue(isset($subject->title));
		$this->assertSame('Component', $subject->title);
		$this->assertTrue(isset($subject['config.enabled']));
		$this->assertTrue($subject['config.enabled']);

		unset($subject->title, $subject['config.enabled']);

		$this->assertFalse(isset($subject->title));
		$this->assertFalse(isset($subject['config.enabled']));

		$subject[7] = 'ignored';
		$this->assertFalse(isset($subject[7]));
		$this->assertNull($subject[7]);
	}

	/**
	 * Expose stable JSON, iterator, object, array, count, and string views.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepresentationContractsExposeTheSameData(): void
	{
		$subject = new RegistryFixture([
			'alpha' => ['beta' => 7],
			'items' => ['first', 'second']
		]);
		$expected = [
			'alpha' => ['beta' => 7],
			'items' => ['first', 'second']
		];
		$formatted = [
			'alpha' => ['beta' => 7],
			'items' => ['item0' => 'first', 'item1' => 'second']
		];

		$this->assertCount(2, $subject);
		$this->assertSame($expected, $subject->toArray());
		$this->assertSame($expected, $subject->jsonSerialize());
		$this->assertSame($expected, iterator_to_array($subject->getIterator()));
		$this->assertSame($expected, json_decode(json_encode($subject), true));
		$this->assertSame($formatted, json_decode((string) $subject, true));
		$this->assertSame($formatted, json_decode($subject->toString('JSON'), true));

		$object = $subject->toObject();
		$this->assertSame(7, $object->alpha->beta);
		$this->assertSame('first', $object->items->item0);
		$this->assertSame('second', $object->items->item1);
	}

	/**
	 * Deep-clone object values so mutations do not leak between registries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCloneDeepCopiesNestedObjectValues(): void
	{
		$object = new stdClass();
		$object->value = 'original';
		$subject = new RegistryFixture(['nested' => ['object' => $object]]);

		$clone = clone $subject;
		$clone->get('nested.object')->value = 'changed';

		$this->assertSame('original', $subject->get('nested.object')->value);
		$this->assertSame('changed', $clone->get('nested.object')->value);
	}

	/**
	 * Flatten either leaf names or complete paths with an explicit delimiter.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFlattenSupportsLeafAndFullPathModes(): void
	{
		$subject = new RegistryFixture([
			'alpha' => [
				'beta' => 1,
				'gamma' => ['delta' => 2]
			]
		]);

		$this->assertSame(['beta' => 1, 'delta' => 2], $subject->flatten());
		$this->assertSame([
			'alpha/beta' => 1,
			'alpha/gamma/delta' => 2
		], $subject->flatten('/', true));
	}

	/**
	 * Set defaults only once, merge recursively, and clear all state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDefaultMergeAndClearContracts(): void
	{
		$subject = new RegistryFixture(['config' => ['existing' => 'kept']]);
		$source = new RegistryFixture([
			'config' => ['existing' => 'replaced', 'added' => 2],
			'other' => true
		]);

		$this->assertSame('first', $subject->def('config.defaulted', 'first'));
		$this->assertSame('first', $subject->def('config.defaulted', 'second'));
		$this->assertSame($subject, $subject->merge($source));
		$this->assertSame([
			'config' => [
				'existing' => 'replaced',
				'defaulted' => 'first',
				'added' => 2
			],
			'other' => true
		], $subject->toArray());
		$this->assertSame($subject, $subject->clear());
		$this->assertFalse($subject->isActive());
	}

	/**
	 * Extract array and scalar nodes into independent registries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExtractReturnsIndependentRegistrySubsets(): void
	{
		$subject = new RegistryFixture([
			'root' => ['branch' => ['leaf' => 'value']],
			'scalar' => 7
		]);

		$branch = $subject->extract('root|branch', null, '|');
		$scalar = $subject->extract('scalar');
		$missing = $subject->extract('missing', 'fallback');

		$this->assertSame(['leaf' => 'value'], $branch->toArray());
		$this->assertSame(['value' => 7], $scalar->toArray());
		$this->assertSame([], $missing->toArray());
		$this->assertSame('.', $subject->getSeparator());

		$branch->set('leaf', 'changed');
		$this->assertSame('value', $subject->get('root.branch.leaf'));
	}

	/**
	 * Preserve registry names and allow explicit flat-key separators.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNameAndSeparatorConfigurationIsFluent(): void
	{
		$subject = new RegistryFixture();

		$this->assertNull($subject->getName());
		$this->assertSame('.', $subject->getSeparator());
		$this->assertSame($subject, $subject->setName('compiler'));
		$this->assertSame($subject, $subject->setSeparator(''));

		$subject->set('literal.path', 'value');

		$this->assertSame('compiler', $subject->getName());
		$this->assertSame('', $subject->getSeparator());
		$this->assertSame(['literal.path' => 'value'], $subject->toArray());
	}

	/**
	 * Load readable files and reject paths that cannot be read.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLoadFileUsesFormatParserAndRejectsMissingFiles(): void
	{
		$file = $this->writeTemporaryFile('registry.json', '{"alpha":{"beta":7}}');
		$subject = new RegistryFixture();

		$this->assertSame($subject, $subject->loadFile($file));
		$this->assertSame(7, $subject->get('alpha.beta'));

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('File does not exist or is not readable');

		$subject->loadFile($this->temporaryPath('missing.json'));
	}
}
