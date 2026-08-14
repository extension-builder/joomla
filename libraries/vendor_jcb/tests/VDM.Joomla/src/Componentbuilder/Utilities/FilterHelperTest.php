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

namespace VDM\Joomla\Tests\Componentbuilder\Utilities;


use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Utilities\FilterHelper;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Database-backed component-builder filter projection tests.
 *
 * @since  6.1.6
 */
#[CoversClass(FilterHelper::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(JsonHelper::class)]
final class FilterHelperTest extends JoomlaTestCase
{
	/**
	 * Return GUID-to-name options and preserve the empty-result contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNamesProjectsPublishedRowsByGuid(): void
	{
		[$database] = $this->database(2);
		$database->expects($this->once())
			->method('loadAssocList')
			->with('guid', 'system_name')
			->willReturn(['guid-one' => 'Alpha', 'guid-two' => 'Beta']);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame(
			['guid-one' => 'Alpha', 'guid-two' => 'Beta'],
			FilterHelper::names('joomla_component')
		);
	}

	/**
	 * Match translation records only within the reviewed extension column.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTranslationFiltersJsonMembershipAndRejectsUnknownTypes(): void
	{
		$this->assertNull(FilterHelper::translation('extension-guid', 'unsupported'));

		[$database] = $this->database(3);
		$database->expects($this->once())->method('loadAssocList')->willReturn([
			['id' => '4', 'components' => '["other-guid"]'],
			['id' => '7', 'components' => '["extension-guid","other-guid"]'],
			['id' => '9', 'components' => '["extension-guid"]']
		]);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame([7, 9], FilterHelper::translation('extension-guid', 'joomla_component'));
	}

	/**
	 * Retain only powers whose decoded approved-path list contains the exact path.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPathsRequiresExactDecodedRepositoryMembership(): void
	{
		[$database] = $this->database(4);
		$database->expects($this->once())
			->method('loadAssocList')
			->with('id', 'approved_paths')
			->willReturn([
				3 => '["owner/repository","owner/other"]',
				4 => '["owner/repository-extra"]',
				5 => 'not-json',
				6 => '[]'
			]);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame([3 => 3], FilterHelper::paths('owner/repository'));
	}

	/**
	 * Build unique organisation/repository option identities in database order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepositoriesMapsObjectsToStablePathOptions(): void
	{
		[$database] = $this->database(3);
		$database->expects($this->once())->method('loadObjectList')->willReturn([
			(object) ['organisation' => 'alpha', 'repository' => 'one'],
			(object) ['organisation' => 'beta', 'repository' => 'two'],
			(object) ['organisation' => 'alpha', 'repository' => 'one']
		]);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame(
			['alpha/one' => 'alpha/one', 'beta/two' => 'beta/two'],
			FilterHelper::repositories(2)
		);
	}

	/**
	 * Return the published language option map and null for an empty query.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLanguagesPreservesTagToNameProjection(): void
	{
		[$database] = $this->database(2);
		$database->expects($this->once())
			->method('loadAssocList')
			->with('langtag', 'name')
			->willReturn(['en-GB' => 'English', 'af-ZA' => 'Afrikaans']);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame(
			['en-GB' => 'English', 'af-ZA' => 'Afrikaans'],
			FilterHelper::languages()
		);
	}

	/**
	 * Return namespace prefixes as both option values and labels.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNamespacesPreservesTrimmedNamespaceProjection(): void
	{
		[$database] = $this->database(2);
		$database->expects($this->once())
			->method('loadAssocList')
			->with('trimmed_namespace', 'trimmed_namespace')
			->willReturn(['VDM\\Joomla' => 'VDM\\Joomla', 'Acme\\Tools' => 'Acme\\Tools']);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame(
			['VDM\\Joomla' => 'VDM\\Joomla', 'Acme\\Tools' => 'Acme\\Tools'],
			FilterHelper::namespaces()
		);
	}

	/**
	 * Return matching power identifiers for every namespace segment.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNamegroupReturnsPowerIdentifiers(): void
	{
		[$database] = $this->database(2);
		$database->expects($this->once())->method('loadColumn')->willReturn([17, 23]);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame([17, 23], FilterHelper::namegroup('VDM\\Joomla'));
	}

	/**
	 * De-duplicate matching translation identifiers without changing their order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTranslationsReturnsUniqueMatchingIdentifiers(): void
	{
		[$database] = $this->database(3);
		$database->expects($this->once())->method('loadColumn')->willReturn([4, 4, 9]);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame([4, 9], array_values(FilterHelper::translations('af-ZA')));
	}

	/**
	 * Reject unknown dynamic link methods without invoking a database boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLinkedRejectsUnknownRelationshipMethod(): void
	{
		$this->assertNull(FilterHelper::linked('guid-one', 'unknown_relationship'));
	}

	/**
	 * Build a fluent database/query pair with deterministic row cardinality.
	 *
	 * @param   int  $rows  Reported row count.
	 *
	 * @return  array{0: DatabaseDriver, 1: DatabaseQuery}  Database and query mocks.
	 * @since   6.1.6
	 */
	private function database(int $rows): array
	{
		$query = $this->createStub(DatabaseQuery::class);

		foreach (['select', 'from', 'where', 'order', 'setLimit', 'bind'] as $method)
		{
			$query->method($method)->willReturnSelf();
		}

		$database = $this->createMock(DatabaseDriver::class);
		$database->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$database->method('quoteName')->willReturnCallback(
			static function (mixed $name): mixed
			{
				if (is_array($name))
				{
					return array_map(static fn (string $item): string => '`' . $item . '`', $name);
				}

				return '`' . $name . '`';
			}
		);
		$database->method('quote')->willReturnCallback(
			static fn (mixed $value): string => "'" . (string) $value . "'"
		);
		$database->expects($this->once())->method('setQuery')->with($query)->willReturnSelf();
		$database->expects($this->once())->method('execute')->willReturn(true);
		$database->expects($this->once())->method('getNumRows')->willReturn($rows);

		return [$database, $query];
	}
}
