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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\TargetRelationScript;


/**
 * Form condition chaining contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class FieldTargetRelationScriptTest extends ArchitectureTestCase
{
	/**
	 * Build one condition of a view.
	 *
	 * @param   string          $field     The GUID of the field it matches on.
	 * @param   string          $match     The name of the field it matches on.
	 * @param   array<string>   $targets   The names of the fields it steers.
	 * @param   int             $relation  Whether the condition may be chained.
	 *
	 * @return  array  The condition.
	 * @since   6.1.7
	 */
	private static function condition(string $field, string $match, array $targets,
		int $relation = 1): array
	{
		return [
			'match_field' => $field,
			'match_name' => $match,
			'target_relation' => $relation,
			'target_field' => array_map(static fn(string $name) => ['name' => $name], $targets),
		];
	}

	/**
	 * A condition that steers two fields, one of which others also steer.
	 *
	 * @return  array  The condition.
	 * @since   6.1.7
	 */
	private static function kind(): array
	{
		return self::condition('f-a', 'kind', ['note', 'colour']);
	}

	/**
	 * Build the subject.
	 *
	 * @return  TargetRelationScript
	 * @since   6.1.7
	 */
	private function chain(): TargetRelationScript
	{
		return $this->renderer(TargetRelationScript::class);
	}

	/**
	 * A condition steering the same field is chained to this one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAConditionSteeringTheSameFieldIsChained(): void
	{
		$level = self::condition('f-b', 'level', ['colour']);

		$this->assertSame(
			[$level],
			$this->chain()->get([self::kind(), $level], self::kind(), 'demo')
		);
	}

	/**
	 * The conditions that are left alone.
	 *
	 * @return  array<string, array{array}>
	 * @since   6.1.7
	 */
	public static function unchained(): array
	{
		return [
			// the condition being chained is in the list it searches
			'itself' => [self::condition('f-a', 'kind', ['note', 'colour'])],
			'one that steers other fields' => [self::condition('f-c', 'other', ['unrelated'])],
			'one that declines to be chained' => [self::condition('f-d', 'loose', ['colour'], 0)],
		];
	}

	/**
	 * A condition with nothing in common is quietly left alone.
	 *
	 * @param   array  $other  The condition that must not be chained.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('unchained')]
	public function testAConditionWithNothingInCommonIsLeftAlone(array $other): void
	{
		$this->assertSame(
			[],
			$this->chain()->get([self::kind(), $other], self::kind(), 'demo')
		);
	}

	/**
	 * One pair of matches may claim a target only once.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOnePairOfMatchesClaimsATargetOnlyOnce(): void
	{
		$level = self::condition('f-b', 'level', ['colour']);
		$chain = $this->chain();

		$this->assertSame([$level], $chain->get([self::kind(), $level], self::kind(), 'demo'));
		$this->assertSame([], $chain->get([self::kind(), $level], self::kind(), 'demo'));
	}

	/**
	 * A different pair may still claim a target another pair holds.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testADifferentPairMayStillClaimTheSameTarget(): void
	{
		$level = self::condition('f-b', 'level', ['colour']);
		$depth = self::condition('f-e', 'depth', ['colour']);
		$chain = $this->chain();

		$chain->get([self::kind(), $level], self::kind(), 'demo');

		$this->assertSame(
			['depth'],
			array_column($chain->get([self::kind(), $level, $depth], self::kind(), 'demo'), 'match_name')
		);
	}

	/**
	 * Each view keeps its own claims, so the next view starts clean.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEachViewKeepsItsOwnClaims(): void
	{
		$level = self::condition('f-b', 'level', ['colour']);
		$chain = $this->chain();

		$chain->get([self::kind(), $level], self::kind(), 'demo');

		$this->assertSame(
			[$level],
			$chain->get([self::kind(), $level], self::kind(), 'other')
		);
	}

	/**
	 * A target nobody has claimed is free.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATargetNobodyHasClaimedIsFree(): void
	{
		$this->assertTrue($this->chain()->checkControl('colour', 'level', 'kind', 'demo'));
	}

	/**
	 * A claim closes the target to that pair alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAClaimClosesTheTargetToThatPairAlone(): void
	{
		$chain = $this->chain();
		$chain->get(
			[self::kind(), self::condition('f-b', 'level', ['colour'])],
			self::kind(),
			'demo'
		);

		$this->assertFalse($chain->checkControl('colour', 'level', 'kind', 'demo'));
		$this->assertTrue($chain->checkControl('colour', 'depth', 'kind', 'demo'));
		$this->assertTrue($chain->checkControl('note', 'level', 'kind', 'demo'));
	}
}
