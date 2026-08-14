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

namespace VDM\Joomla\Tests\Componentbuilder\Fieldtype;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionMethod;
use VDM\Joomla\Abstraction\Grep as ExtendingGrep;
use VDM\Joomla\Abstraction\Remote\Config as ExtendingRemoteConfig;
use VDM\Joomla\Abstraction\Remote\Set as ExtendingSet;
use VDM\Joomla\Componentbuilder\Abstraction\ComponentConfig;
use VDM\Joomla\Componentbuilder\Fieldtype\Config;
use VDM\Joomla\Componentbuilder\Fieldtype\Grep;
use VDM\Joomla\Componentbuilder\Fieldtype\Readme\Item;
use VDM\Joomla\Componentbuilder\Fieldtype\Readme\Main;
use VDM\Joomla\Componentbuilder\Fieldtype\Remote\Config as RemoteConfig;
use VDM\Joomla\Componentbuilder\Fieldtype\Remote\Set;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Package\MessageBus;
use VDM\Joomla\Componentbuilder\Package\Readme\Main as ExtendingMain;
use VDM\Joomla\Componentbuilder\Power\Table;
use VDM\Joomla\Interfaces\Data\ItemsInterface;
use VDM\Joomla\Interfaces\Git\Repository\ContentsInterface;
use VDM\Joomla\Interfaces\GrepInterface;
use VDM\Tests\Support\TestCase;


/**
 * Field-type configuration, repository, README, and remote-write contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Config::class)]
#[CoversClass(Grep::class)]
#[CoversClass(Item::class)]
#[CoversClass(Main::class)]
#[CoversClass(RemoteConfig::class)]
#[CoversClass(Set::class)]
#[UsesClass(ComponentConfig::class)]
#[UsesClass(ExtendingGrep::class)]
#[UsesClass(ExtendingRemoteConfig::class)]
#[UsesClass(ExtendingSet::class)]
#[UsesClass(ExtendingMain::class)]
final class FieldtypeContractTest extends TestCase
{
	/**
	 * Install the compatibility alias required by the current constructor typo.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		if (!class_exists('VDM\Joomla\Componentbuilder\Fieldtype\Input', false))
		{
			class_alias(Input::class, 'VDM\Joomla\Componentbuilder\Fieldtype\Input');
		}
	}

	/**
	 * Resolve credentials, organization, and ordered user/core repositories.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConfigBuildsFieldtypeRepositorySettings(): void
	{
		$params = new Registry([
			'gitea_username' => 'alice',
			'gitea_token' => 'secret',
			'joomla_fieldtype_core_organisation' => 'core-team',
		]);
		$global = new Registry(['tmp_path' => '/isolated/tmp']);
		$subject = new Config(new Input(['request_key' => 'request']), $params, $global);

		$this->assertSame('alice', $subject->gitea_username);
		$this->assertSame('secret', $subject->gitea_token);
		$this->assertSame('core-team', $subject->joomla_fieldtype_core_organisation);
		$this->assertSame(['alice.joomla-fieldtypes', 'core-team.joomla-fieldtypes'], array_keys($subject->joomla_fieldtype_init_repos));
		$this->assertSame('master', $subject->joomla_fieldtype_init_repos['alice.joomla-fieldtypes']->read_branch);
		$this->assertSame('request', $subject->get('request_key'));
		$this->assertSame($global, (new ReflectionClass(Config::class))->getProperty('config')->getValue($subject));

		$params->set('gitea_username', 'changed');
		$this->assertSame('alice', $subject->gitea_username);
	}

	/**
	 * The public constructor contract must name Joomla's Input dependency.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testConfigConstructorDeclaresJoomlaInputType(): void
	{
		$type = (new ReflectionMethod(Config::class, '__construct'))->getParameters()[0]->getType();

		$this->assertSame(Input::class, $type?->getName());
	}

	/**
	 * Generate escaped property tables and linked PHP examples in item README.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemReadmeRendersDescriptionsPropertiesAndCodeAnchors(): void
	{
		$markdown = (new Item())->get((object) [
			'name' => 'Dynamic List',
			'short_description' => 'Reusable list field.',
			'description' => 'Full field type description.',
			'properties' => [
				(object) [
					'name' => 'source|query',
					'example' => '$query = $db->getQuery(true);',
					'adjustable' => '1',
					'mandatory' => '0',
					'description' => 'Build A|B options',
				],
				(object) ['name' => 'required', 'example' => 'true', 'mandatory' => '1'],
			],
		]);

		$this->assertStringContainsString('# Dynamic List', $markdown);
		$this->assertStringContainsString('> Reusable list field.', $markdown);
		$this->assertStringContainsString('Full field type description.', $markdown);
		$this->assertStringContainsString('| source&#124;query | [code](#code-source-124-query) |', $markdown);
		$this->assertStringContainsString('Build A&#124;B options', $markdown);
		$this->assertStringContainsString("```php\n\$query = \$db->getQuery(true);\n```", $markdown);
		$this->assertStringContainsString('![yes](https://img.shields.io/badge/yes-success?style=flat-square)', $markdown);
	}

	/**
	 * Build an alphabetized main repository index with details and settings links.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMainReadmeBuildsSortedFieldtypeIndex(): void
	{
		$items = [
			'z' => ['name' => 'Zulu', 'path' => 'src/zulu', 'settings' => 'src/zulu/item.json', 'desc' => '<b>Last field</b>'],
			'a' => ['name' => 'Alpha', 'path' => 'src/alpha', 'settings' => 'src/alpha/item.json', 'desc' => "First\nfield"],
		];
		$markdown = (new Main())->get($items);

		$this->assertStringContainsString('# JCB! Field Types', $markdown);
		$this->assertStringContainsString('**Alpha** | [Details](src/alpha) | [Settings](src/alpha/item.json) | First field', $markdown);
		$this->assertStringContainsString('**Zulu** | [Details](src/zulu) | [Settings](src/zulu/item.json) | Last field', $markdown);
		$this->assertLessThan(strpos($markdown, '**Zulu**'), strpos($markdown, '**Alpha**'));
	}

	/**
	 * Publish the exact field-type remote repository schema.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRemoteConfigPublishesFieldtypeRepositorySchema(): void
	{
		$subject = new RemoteConfig(new Table());

		$this->assertSame('fieldtype', $subject->getTable());
		$this->assertSame('Field Type', $subject->getArea());
		$this->assertSame('', $subject->getPrefixKey());
		$this->assertSame('', $subject->getSuffixKey());
		$this->assertSame(
			['name' => 'index_map_IndexName', 'path' => 'index_map_IndexPath', 'settings' => 'index_map_IndexSettingsPath', 'guid' => 'index_map_IndexGUID', 'desc' => 'index_map_ShortDescription'],
			$subject->getIndexMap()
		);
		$this->assertSame(['name', 'desc', 'path', 'settings', 'guid', 'local'], $subject->getIndexHeader());
		$this->assertSame('src', $subject->getSrcPath());
		$this->assertTrue($subject->hasMainReadme());
		$this->assertTrue($subject->hasItemReadme());
		$this->assertSame('fieldtypes', $subject->getListViewCodeName());
		$this->assertArrayNotHasKey('catid', $subject->getMap());
		$this->assertArrayNotHasKey('access', $subject->getMap());
	}

	/**
	 * Emit the field-type-specific remote-index diagnostic at the app boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGrepReportsRemoteIndexFailureWithApiAndPath(): void
	{
		$subject = (new ReflectionClass(Grep::class))->newInstanceWithoutConstructor();
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())->method('enqueueMessage')->with($this->callback(
			static fn(string $message): bool => str_contains($message, 'COM_COMPONENTBUILDER_PJOOMLA_FIELD_TYPEB_REPOSITORY_AT_BSSB_GAVE_THE_FOLLOWING_ERRORBR_SP')
		), 'Error');
		$contents = $this->createMock(ContentsInterface::class);
		$contents->expects($this->once())->method('api')->willReturn('https://git.example.test/api');
		$reflection = new ReflectionClass(ExtendingGrep::class);
		$reflection->getProperty('app')->setValue($subject, $app);
		$reflection->getProperty('contents')->setValue($subject, $contents);

		(new ReflectionMethod($subject, 'setRemoteIndexMessage'))->invoke(
			$subject,
			'broken JSON',
			'index.json',
			'joomla-fieldtypes',
			'joomla',
			null
		);
	}

	/**
	 * Create the exact settings file and normalize index display fields.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRemoteSetCreatesSettingsAndMapsIndexValues(): void
	{
		$item = (object) ['guid' => 'fieldtype-guid', 'name' => 'Dynamic List', 'short_description' => '  Reusable list field.  '];
		$repo = (object) [
			'organisation' => 'acme',
			'repository' => 'joomla-fieldtypes',
			'write_branch' => 'main',
			'author_name' => 'Build Bot',
			'author_email' => 'bot@example.test',
		];
		$git = $this->createMock(ContentsInterface::class);
		$git->expects($this->once())->method('create')->with(
			'acme',
			'joomla-fieldtypes',
			'src/fieldtype-guid/item.json',
			json_encode($item, JSON_PRETTY_PRINT),
			'Create Dynamic List',
			'main',
			'Build Bot',
			'bot@example.test'
		)->willReturn((object) ['sha' => 'created']);
		$subject = new Set(
			new RemoteConfig(new Table()),
			$this->createStub(GrepInterface::class),
			$this->createStub(ItemsInterface::class),
			new Item(),
			new Main(),
			$git,
			new Tracker(),
			new MessageBus(),
			[]
		);

		$this->assertTrue($this->invoke($subject, 'createItem', [$item, $repo]));
		$this->assertSame('Dynamic List', $this->invoke($subject, 'index_map_IndexName', [$item]));
		$this->assertSame('Reusable list field.', $this->invoke($subject, 'index_map_ShortDescription', [$item]));
		$this->assertNull($this->invoke($subject, 'index_map_ShortDescription', [(object) []]));
	}

	/**
	 * Invoke a protected remote specialization method.
	 *
	 * @param   object             $subject    Target object.
	 * @param   string             $method     Method name.
	 * @param   array<int, mixed>  $arguments  Arguments.
	 *
	 * @return  mixed
	 * @since   6.1.6
	 */
	private function invoke(object $subject, string $method, array $arguments): mixed
	{
		return (new ReflectionMethod($subject, $method))->invokeArgs($subject, $arguments);
	}
}
