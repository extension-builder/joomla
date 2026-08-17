<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\FieldRelation;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListJoin;


/**
 * Model related field statement contracts.
 *
 * @since  6.1.7
 */
#[CoversClass(FieldRelation::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ModelFieldRelationTest extends ArchitectureTestCase
{
	/**
	 * A field with no joins concatenates only itself.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFieldWithoutJoinsConcatenatesOnlyItself(): void
	{
		$code = $this->subject()->get($this->item(), 'articles', '');

		$this->assertStringContainsString('// concatenate these fields', $code);
		$this->assertStringContainsString('$item->title = $item->title;', $code);
	}

	/**
	 * Joined fields are concatenated in order with the separator between them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoinedFieldsAreConcatenatedWithTheSeparator(): void
	{
		$item = $this->item();
		$item['joinfields'] = ['guid-first', 'guid-second'];

		$code = $this->subject($this->listjoin())->get($item, 'articles', '');

		$this->assertStringContainsString(
			"\$item->title = \$item->title . ' - ' . \$item->first . ' - ' . \$item->second;",
			$code
		);
	}

	/**
	 * A separator containing a quote is escaped so the statement stays valid.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAQuoteInTheSeparatorIsEscaped(): void
	{
		$item = $this->item();
		$item['set'] = "'s ";
		$item['joinfields'] = ['guid-first'];

		$code = $this->subject($this->listjoin())->get($item, 'articles', '');

		$this->assertStringContainsString("&apos;s ", $code);
		$this->assertStringNotContainsString(". ''s ' .", $code);
	}

	/**
	 * Custom code replaces field references by id and by guid alike.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCustomCodeResolvesFieldReferencesByIdAndGuid(): void
	{
		$item = $this->item();
		$item['join_type'] = 2;
		$item['joinfields'] = ['guid-first'];
		$item['set'] = '$item->title = $item->{7} . $item->{guid-first} . $item->{guid-title};';

		$code = $this->subject($this->listjoin())->get($item, 'articles', '');

		$this->assertStringContainsString(
			'$item->title = $item->title . $item->first . $item->title;',
			$code
		);
		$this->assertStringNotContainsString('$item->{', $code);
		// custom code is emitted as-is, never concatenated
		$this->assertStringNotContainsString('concatenate these fields', $code);
	}

	/**
	 * A join the registry does not know resolves to the error placeholder.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnUnknownJoinResolvesToTheErrorPlaceholder(): void
	{
		$item = $this->item();
		$item['joinfields'] = ['guid-missing'];

		$code = $this->subject()->get($item, 'articles', '');

		$this->assertStringContainsString('$item->error', $code);
	}

	/**
	 * Build a related field definition.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function item(): array
	{
		return [
			'id' => 7,
			'guid' => 'guid-title',
			'code' => 'title',
			'set' => ' - ',
			'join_type' => 1,
		];
	}

	/**
	 * Build a join registry naming two joined fields.
	 *
	 * @return  ListJoin
	 * @since   6.1.7
	 */
	private function listjoin(): ListJoin
	{
		$listjoin = new ListJoin();
		$listjoin->set('articles.guid-first', ['id' => 11, 'code' => 'first']);
		$listjoin->set('articles.guid-second', ['id' => 12, 'code' => 'second']);

		return $listjoin;
	}

	/**
	 * Create the field relation builder with real collaborators.
	 *
	 * @param   ListJoin|null  $listjoin  The join registry.
	 *
	 * @return  FieldRelation
	 * @since   6.1.7
	 */
	private function subject(?ListJoin $listjoin = null): FieldRelation
	{
		return new FieldRelation(
			$listjoin ?? new ListJoin(),
			$this->placeholder()
		);
	}
}
