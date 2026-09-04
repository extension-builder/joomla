<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    4th September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Model;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use TypeError;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\RecordKeyFix;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Alias;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueGuid;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueKeys;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The record keys that open the generated save method.
 *
 * The exact fragments are asserted, and then executed against the shapes
 * Joomla hands the model, with the power calls routed to closures, so the
 * behaviour of the emitted code is proven and not only its text.
 *
 * @since 6.1.7
 */
#[CoversClass(RecordKeyFix::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class RecordKeyFixTest extends ArchitectureTestCase
{
	/**
	 * The primary key of a table with neither a guid nor an alias.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ID_ONLY = <<<'GEN'


		// The record keys, as every line below expects them: the primary key as an
		// integer that is never taken from the request (null from the API on create).
		$data['id'] = (int) ($data['id'] ?? 0);
GEN;

	/**
	 * The guid lifecycle of a table with a guid column.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_GUID = <<<'GEN'


		// The guid is the server's: an existing record keeps the guid it was stored
		// with, and the API never takes one from the request.
		if ($data['id'] > 0)
		{
			$data['guid'] = (string) Super___db87c339_5bb6_4291_a7ef_2c48ea1b06bc___Power::var('article', $data['id'], 'id', 'guid', '=', 'demo');
		}
		elseif (Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->isClient('api'))
		{
			$data['guid'] = '';
		}
		else
		{
			$data['guid'] = (string) ($data['guid'] ?? '');
		}

		// Set the guid while it is empty, not valid, or not unique in this table.
		while (!Super___9c513baf_b279_43fd_ae29_a585c8cbc4f0___Power::valid($data['guid'], 'article', $data['id'], 'demo'))
		{
			$data['guid'] = (string) Super___9c513baf_b279_43fd_ae29_a585c8cbc4f0___Power::get();
		}
GEN;

	/**
	 * The alias seed of a view with an alias.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ALIAS = <<<'GEN'


		// A new record without an alias gets one from its title when the table checks it.
		if ($data['id'] === 0 && !isset($data['alias']))
		{
			$data['alias'] = '';
		}
GEN;

	/**
	 * A table with neither a guid nor an alias only resolves its primary key.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATableWithoutGuidOrAliasResolvesOnlyThePrimaryKey(): void
	{
		$subject = $this->renderer(RecordKeyFix::class);

		$this->assertSame(self::EXPECTED_ID_ONLY, $subject->get('article'));
		$this->assertFalse($subject->hasGuid('article'));
	}

	/**
	 * A guid registered in the unique guid registry gets the guid lifecycle.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAGuidColumnGetsTheServerOwnedLifecycle(): void
	{
		$subject = $this->renderer(RecordKeyFix::class, ['databaseuniqueguid' => $this->guidRegistry()]);

		$this->assertTrue($subject->hasGuid('article'));
		$this->assertSame(self::EXPECTED_ID_ONLY . self::EXPECTED_GUID, $subject->get('article'));
	}

	/**
	 * A guid declared with a unique index registers among the unique keys and
	 * still gets the lifecycle, as the API record resolution already reads it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAUniqueIndexedGuidGetsTheSameLifecycle(): void
	{
		$keys = new DatabaseUniqueKeys();
		$keys->add('article', 'code', true);
		$keys->add('article', 'guid', true);

		$subject = $this->renderer(RecordKeyFix::class, ['databaseuniquekeys' => $keys]);

		$this->assertTrue($subject->hasGuid('article'));
		$this->assertSame(self::EXPECTED_ID_ONLY . self::EXPECTED_GUID, $subject->get('article'));
	}

	/**
	 * A view with an alias seeds the alias key of a new record.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithAnAliasSeedsTheAliasOfANewRecord(): void
	{
		$subject = $this->renderer(RecordKeyFix::class, [
			'databaseuniqueguid' => $this->guidRegistry(),
			'alias' => $this->aliasRegistry(),
		]);

		$this->assertSame(
			self::EXPECTED_ID_ONLY . self::EXPECTED_GUID . self::EXPECTED_ALIAS,
			$subject->get('article')
		);
	}

	/**
	 * Another view's registrations never leak into this view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheRegistriesAreReadPerView(): void
	{
		$subject = $this->renderer(RecordKeyFix::class, [
			'databaseuniqueguid' => $this->guidRegistry(),
			'alias' => $this->aliasRegistry(),
		]);

		$this->assertSame(self::EXPECTED_ID_ONLY, $subject->get('category'));
	}

	/**
	 * The shape the API hands the model on create no longer reaches the
	 * strictly typed guid helper as null: the key becomes 0, the guid is
	 * generated, and the alias is seeded for the table.
	 *
	 * This is the bug report's failing shape, made a permanent contract.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnApiCreateWithoutKeysGetsAnIntegerIdAndAGeneratedGuid(): void
	{
		$world = $this->world(isApi: true);

		// the primary key was null and the form filter dropped it, the body had no guid
		$data = $world['run'](['name' => 'JCB API GUID Test']);

		$this->assertSame(0, $data['id']);
		$this->assertSame('v-1', $data['guid']);
		$this->assertSame('', $data['alias']);
		$this->assertSame([], $world['stored']());
	}

	/**
	 * The API never takes a guid from the request body on create.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnApiCreateIgnoresAGuidFromTheBody(): void
	{
		$world = $this->world(isApi: true);

		$data = $world['run'](['id' => null, 'guid' => 'v-77', 'name' => 'x']);

		$this->assertSame(0, $data['id']);
		$this->assertSame('v-1', $data['guid']);
	}

	/**
	 * A body id can never turn a create into an update: an absent key is 0,
	 * never the model state, which the API populates from the body.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAbsentPrimaryKeyIsZeroAndNeverAnotherRecord(): void
	{
		$world = $this->world(isApi: true);

		$data = $world['run'](['name' => 'x']);

		$this->assertSame(0, $data['id']);
		$this->assertSame([], $world['stored'](), 'no stored record was looked up');
	}

	/**
	 * The administrator form keeps the valid unique guid it was seeded with,
	 * and the string id the form posts becomes an integer.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFormCreateKeepsAValidUniqueGuid(): void
	{
		$world = $this->world(isApi: false);

		$data = $world['run'](['id' => '0', 'guid' => 'v-42', 'alias' => 'given']);

		$this->assertSame(0, $data['id']);
		$this->assertSame('v-42', $data['guid']);
		$this->assertSame('given', $data['alias'], 'an alias that was posted is left alone');
	}

	/**
	 * A guid that is taken, or not a guid at all, is replaced until unique.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testADuplicateOrInvalidGuidIsReplacedUntilUnique(): void
	{
		$world = $this->world(isApi: false, taken: ['v-1', 'v-2']);

		$duplicate = $world['run'](['guid' => 'v-1']);
		$invalid = $world['run'](['guid' => 'not-a-guid']);

		$this->assertSame('v-3', $duplicate['guid']);
		$this->assertSame('v-4', $invalid['guid']);
	}

	/**
	 * An existing record keeps the guid it was stored with, whatever the
	 * request carries, and its alias is never seeded.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnExistingRecordKeepsItsStoredGuid(): void
	{
		$world = $this->world(isApi: true, storedGuid: 'v-9');

		$data = $world['run'](['id' => '12', 'guid' => 'v-77', 'name' => 'x']);

		$this->assertSame(12, $data['id']);
		$this->assertSame('v-9', $data['guid']);
		$this->assertArrayNotHasKey('alias', $data);
		$this->assertSame([['article', 12, 'id', 'guid', '=', 'demo']], $world['stored']());
	}

	/**
	 * An existing record stored without a valid guid gets one on its next save.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnExistingRecordWithoutAValidGuidIsHealed(): void
	{
		$world = $this->world(isApi: false, storedGuid: '');

		$data = $world['run'](['id' => 12]);

		$this->assertSame('v-1', $data['guid']);
	}

	/**
	 * The helper the generated code calls really is strict: the shape that
	 * reached it before this renderer existed still throws, which is what
	 * makes the executed contracts above meaningful.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheStrictHelperStillRejectsANullId(): void
	{
		$world = $this->world(isApi: true);

		$this->expectException(TypeError::class);

		$world['valid']('', 'article', null);
	}

	/**
	 * A unique guid registry carrying the article table.
	 *
	 * @return  DatabaseUniqueGuid
	 * @since   6.1.7
	 */
	private function guidRegistry(): DatabaseUniqueGuid
	{
		$guid = new DatabaseUniqueGuid();
		$guid->set('article', true);

		return $guid;
	}

	/**
	 * An alias registry carrying the article table.
	 *
	 * @return  Alias
	 * @since   6.1.7
	 */
	private function aliasRegistry(): Alias
	{
		$alias = new Alias();
		$alias->set('article', 'alias');

		return $alias;
	}

	/**
	 * Execute the emitted block for a guid and alias view against a small
	 * world: the power calls become closures with the real signatures.
	 *
	 * @param   bool     $isApi       Whether the running client is the API.
	 * @param   array    $taken       Guids already stored in the table.
	 * @param   string   $storedGuid  The guid an existing record was stored with.
	 *
	 * @return  array{run: callable, stored: callable, valid: callable}
	 * @since   6.1.7
	 */
	private function world(bool $isApi, array $taken = [], string $storedGuid = 'v-9'): array
	{
		$subject = $this->renderer(RecordKeyFix::class, [
			'databaseuniqueguid' => $this->guidRegistry(),
			'alias' => $this->aliasRegistry(),
		]);

		$code = strtr($subject->get('article'), [
			"Super___db87c339_5bb6_4291_a7ef_2c48ea1b06bc___Power::var(" => '$stored(',
			"Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->isClient('api')" => '$isApi',
			"Super___9c513baf_b279_43fd_ae29_a585c8cbc4f0___Power::valid(" => '$valid(',
			"Super___9c513baf_b279_43fd_ae29_a585c8cbc4f0___Power::get()" => '$next()',
		]);

		$this->assertStringNotContainsString('___Power', $code, 'every power call is routed');

		$lookups = [];
		$sequence = 0;

		// the real helper declares int $id, which is what the bug report hit
		$valid = static function ($guid, ?string $table = null, int $id = 0, ?string $component = null) use ($taken): bool
		{
			return is_string($guid) && preg_match('/^v-\d+$/', $guid) === 1 && !in_array($guid, $taken, true);
		};
		$next = static function () use (&$sequence): string
		{
			return 'v-' . (++$sequence);
		};
		$stored = static function () use (&$lookups, $storedGuid): string
		{
			$lookups[] = func_get_args();

			return $storedGuid;
		};

		$run = static function (array $data) use ($code, $valid, $next, $stored, $isApi): array
		{
			eval($code);

			return $data;
		};

		return [
			'run' => $run,
			'stored' => static function () use (&$lookups): array
			{
				return $lookups;
			},
			'valid' => $valid,
		];
	}
}
