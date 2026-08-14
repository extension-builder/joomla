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

namespace VDM\Joomla\Tests\Componentbuilder\Table;


use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionMethod;
use VDM\Joomla\Abstraction\BaseTable;
use VDM\Joomla\Abstraction\Schema as ExtendingSchema;
use VDM\Joomla\Abstraction\SchemaChecker as ExtendingSchemaChecker;
use VDM\Joomla\Componentbuilder\Table;
use VDM\Joomla\Componentbuilder\Table\Schema;
use VDM\Joomla\Componentbuilder\Table\SchemaChecker;
use VDM\Joomla\Componentbuilder\Table\Search;
use VDM\Joomla\Componentbuilder\Table\Validator;
use VDM\Joomla\Interfaces\SchemaInterface;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\MessageApplicationFixture;
use VDM\Tests\Support\TestCase;


/**
 * Component Builder table catalog, search, validation, and schema contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Table::class)]
#[CoversClass(Search::class)]
#[CoversClass(Validator::class)]
#[CoversClass(Schema::class)]
#[CoversClass(SchemaChecker::class)]
#[UsesClass(BaseTable::class)]
#[UsesClass(ExtendingSchema::class)]
#[UsesClass(ExtendingSchemaChecker::class)]
#[UsesClass(StringHelper::class)]
final class TableContractTest extends TestCase
{
	/**
	 * Original component-helper and string-helper process state.
	 *
	 * @var    array<string, mixed>
	 * @since  6.1.6
	 */
	private array $originalState = [];

	/**
	 * Install deterministic language configuration for table search labels.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$helper = new ReflectionClass(Helper::class);
		$string = new ReflectionClass(StringHelper::class);
		$this->originalState = [
			'option' => Helper::$option,
			'params' => $helper->getProperty('params')->getValue(),
			'langTag' => $string->getProperty('langTag')->getValue(),
		];
		Helper::$option = 'com_componentbuilder';
		$helper->getProperty('params')->setValue(
			null,
			['com_componentbuilder' => new Registry(['language' => 'en-GB'])]
		);
		$string->getProperty('langTag')->setValue(null, 'en-GB');
	}

	/**
	 * Restore component and transliteration configuration.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		$helper = new ReflectionClass(Helper::class);
		$string = new ReflectionClass(StringHelper::class);
		Helper::$option = $this->originalState['option'];
		$helper->getProperty('params')->setValue(null, $this->originalState['params']);
		$string->getProperty('langTag')->setValue(null, $this->originalState['langTag']);
		$this->originalState = [];

		parent::tearDown();
	}

	/**
	 * Guard the complete generated table inventory and representative metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTablePublishesReviewedGeneratedMetadataCatalog(): void
	{
		$subject = new Table();
		$tables = $subject->get();
		$fieldCounts = [];

		foreach ($tables as $name => $fields)
		{
			$fieldCounts[$name] = count($fields);
		}
		ksort($fieldCounts);

		$this->assertCount(51, $tables);
		$this->assertSame(699, array_sum($fieldCounts));
		$this->assertSame(
			'0e0b14af21caa6fbfa740173be29c5a59ae3c4642e7a3cc6e166d6643a04c785',
			hash('sha256', (string) json_encode($fieldCounts)),
			'Review the generated table catalog before accepting this fingerprint change.'
		);
		$this->assertSame('system_name', $subject->titleName('joomla_component'));
		$this->assertSame('name', $subject->titleName('field'));
		$this->assertSame('VARCHAR(36)', $subject->get('power', 'guid', 'db')['type']);
		$this->assertSame('basic_encryption', $subject->get('server', 'signature', 'store'));
		$this->assertTrue($subject->exist('admin_view', 'system_name'));
		$this->assertContains('published', $subject->fields('server', true));
	}

	/**
	 * Build stable search plans for code and placeholder discovery.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSearchBuildsAreaSpecificFieldAndDecodePlans(): void
	{
		$subject = new Search();
		$customCode = $subject->getTextSearchSet('customcode');
		$placeholders = $subject->getTextSearchSet('placeholders');

		$this->assertCount(25, $customCode);
		$this->assertCount(29, $placeholders);
		$this->assertSame(
			'779d0040910db10fbc0254034aadbdcc758fe3454dbae56b790e915c7a665173',
			hash('sha256', (string) json_encode($customCode))
		);
		$this->assertSame(
			'cc6b87f75f0d3e5839c81b1f20c1a21ca3b3cd3415ec882c54ca677573868d0c',
			hash('sha256', (string) json_encode($placeholders))
		);
		$this->assertContains('xml', $placeholders['field']['search']);
		$this->assertSame('json', $placeholders['field']['decode']['xml']);
		$this->assertSame('system_name', $customCode['joomla_component']['name']);
		$this->assertSame('joomla_components', $customCode['joomla_component']['views']);
	}

	/**
	 * Validate MySQL field families and apply reviewed table defaults on failure.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValidatorPreservesValidValuesAndUsesDatabaseDefaults(): void
	{
		$subject = new Validator(new Table());

		$this->assertSame('demo', $subject->getValid('demo', 'name_code', 'joomla_component'));
		$this->assertSame('', $subject->getValid(str_repeat('x', 256), 'name_code', 'joomla_component'));
		$this->assertSame(7, $subject->getValid(7, 'type', 'admin_view'));
		$this->assertSame('0', $subject->getValid('not-an-integer', 'type', 'admin_view'));
		$this->assertSame('free form text', $subject->getValid('free form text', 'description', 'joomla_component'));
		$this->assertSame('', $subject->getValid(['not text'], 'description', 'joomla_component'));
	}

	/**
	 * Unknown table metadata is a normal invalid input and should return null.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testValidatorReturnsNullForUnknownField(): void
	{
		try
		{
			$result = (new Validator(new Table()))->getValid('value', 'missing', 'server');
		}
		catch (\Throwable $error)
		{
			$this->fail(
				'Unknown metadata should return null, not raise ' . $error::class . ': ' . $error->getMessage()
			);
		}

		$this->assertNull($result);
	}

	/**
	 * Pin the schema target and checker class-loading conventions.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSchemaSpecializationsTargetComponentbuilderClasses(): void
	{
		$schema = (new ReflectionClass(Schema::class))->newInstanceWithoutConstructor();
		$checker = (new ReflectionClass(SchemaChecker::class))->newInstanceWithoutConstructor();

		$this->assertSame('componentbuilder', $this->invoke($schema, 'getCode'));
		$this->assertSame('componentbuilder', $this->invoke($checker, 'getCode'));
		$this->assertSame('src/Helper/PowerloaderHelper.php', $this->invoke($checker, 'getPowerPath'));
		$this->assertSame(Schema::class, $this->invoke($checker, 'getSchemaClass'));
		$this->assertSame(Table::class, $this->invoke($checker, 'getTableClass'));
	}

	/**
	 * Forward schema results and failures to the application message boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSchemaCheckerReportsUpdateResultsAndFailures(): void
	{
		$app = new MessageApplicationFixture();
		$schema = $this->createMock(SchemaInterface::class);
		$schema->expects($this->once())->method('update')->willReturn(['first change', 'second change']);
		(new SchemaChecker($schema, new Table(), $app))->run();

		$this->assertSame(
			[
				['message' => 'first change', 'type' => 'message'],
				['message' => 'second change', 'type' => 'message'],
			],
			$app->messages
		);

		$failureApp = new MessageApplicationFixture();
		$failure = $this->createMock(SchemaInterface::class);
		$failure->expects($this->once())->method('update')->willThrowException(new \RuntimeException('schema unavailable'));
		(new SchemaChecker($failure, new Table(), $failureApp))->run();
		$this->assertSame([['message' => 'schema unavailable', 'type' => 'warning']], $failureApp->messages);
	}

	/**
	 * Invoke a protected specialization method.
	 *
	 * @param   object  $subject  Target object.
	 * @param   string  $method   Method name.
	 *
	 * @return  mixed
	 * @since   6.1.6
	 */
	private function invoke(object $subject, string $method): mixed
	{
		return (new ReflectionMethod($subject, $method))->invoke($subject);
	}
}
