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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Controller;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\RecordId;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueGuid;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueKeys;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The record resolution of the item API controller.
 *
 * @since 6.1.7
 */
#[CoversClass(RecordId::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class RecordIdTest extends ArchitectureTestCase
{
	/**
	 * The record resolution of a table with no unique key.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ID_ONLY = <<<'GEN'

		// Take the primary key when the request carries it.
		$id = $this->input->getInt('id', 0);

		if ($id > 0)
		{
			return $id;
		}

		return 0;
GEN;

	/**
	 * The record resolution through the guid and a unique code.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_WITH_KEYS = <<<'GEN'

		// Take the primary key when the request carries it.
		$id = $this->input->getInt('id', 0);

		if ($id > 0)
		{
			return $id;
		}

		// Resolve the record through the first unique key the request carries.
		foreach (['guid', 'code'] as $key)
		{
			$value = $this->input->getString($key, '');

			if ($value === '')
			{
				continue;
			}

			$table = $this->getModel()->getTable();

			if ($table->load([$key => $value]))
			{
				return (int) $table->id;
			}

			return 0;
		}

		return 0;
GEN;

	/**
	 * A table with no unique key is resolved by id alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATableWithNoUniqueKeyIsResolvedByIdAlone(): void
	{
		$subject = $this->renderer(RecordId::class);

		$this->assertSame(self::EXPECTED_ID_ONLY, $subject->get('demo'));
	}

	/**
	 * The guid leads and every unique key follows.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheGuidLeadsAndEveryUniqueKeyFollows(): void
	{
		$keys = new DatabaseUniqueKeys();
		$keys->add('demo', 'code', true);
		$keys->add('demo', 'guid', true);
		$keys->add('demo', 'id', true);

		$subject = $this->renderer(RecordId::class, ['databaseuniquekeys' => $keys]);

		$this->assertSame(self::EXPECTED_WITH_KEYS, $subject->get('demo'));
	}

	/**
	 * A guid without a unique index still resolves the record.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAGuidWithoutAUniqueIndexStillResolvesTheRecord(): void
	{
		$guid = new DatabaseUniqueGuid();
		$guid->set('demo', true);

		$subject = $this->renderer(RecordId::class, ['databaseuniqueguid' => $guid]);

		$this->assertStringContainsString("foreach (['guid'] as \$key)", $subject->get('demo'));
	}

	/**
	 * Another views keys are not borrowed.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnotherViewsKeysAreNotBorrowed(): void
	{
		$keys = new DatabaseUniqueKeys();
		$keys->add('other', 'code', true);

		$subject = $this->renderer(RecordId::class, ['databaseuniquekeys' => $keys]);

		$this->assertSame(self::EXPECTED_ID_ONLY, $subject->get('demo'));
	}
}
