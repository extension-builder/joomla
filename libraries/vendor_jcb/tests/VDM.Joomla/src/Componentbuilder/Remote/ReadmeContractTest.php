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

namespace VDM\Joomla\Tests\Componentbuilder\Remote;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\JoomlaPower\Readme\Item as JoomlaPowerItem;
use VDM\Joomla\Componentbuilder\JoomlaPower\Readme\Main as JoomlaPowerMain;
use VDM\Joomla\Componentbuilder\Power\Plantuml;
use VDM\Joomla\Componentbuilder\Power\Readme\Item as PowerItem;
use VDM\Joomla\Componentbuilder\Power\Readme\Main as PowerMain;
use VDM\Joomla\Componentbuilder\Repository\Readme\Item as RepositoryItem;
use VDM\Joomla\Componentbuilder\Repository\Readme\Main as RepositoryMain;
use VDM\Joomla\Componentbuilder\Snippet\Readme\Item as SnippetItem;
use VDM\Joomla\Componentbuilder\Snippet\Readme\Main as SnippetMain;
use VDM\Tests\Support\TestCase;


/**
 * Repository README metadata, key, formatting, and sorting contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(PowerItem::class)]
#[CoversClass(PowerMain::class)]
#[CoversClass(JoomlaPowerItem::class)]
#[CoversClass(JoomlaPowerMain::class)]
#[CoversClass(RepositoryItem::class)]
#[CoversClass(RepositoryMain::class)]
#[CoversClass(SnippetItem::class)]
#[CoversClass(SnippetMain::class)]
#[UsesClass(Plantuml::class)]
final class ReadmeContractTest extends TestCase
{
	/**
	 * Protect Power identity, namespace, inheritance, UML, and SPK formatting.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPowerItemReadmeBuildsExactIdentityAndActivationKey(): void
	{
		$subject = new PowerItem(new Plantuml());
		$readme = $subject->get((object) [
			'type' => 'final class',
			'code_name' => 'Widget',
			'_namespace' => 'VDM\\Demo',
			'extends_name' => 'BaseWidget',
			'guid' => '1234-abcd',
			'parsed_class_code' => ['properties' => [], 'methods' => []],
		]);

		$this->assertStringStartsWith(
			"### JCB! Power\n# final class Widget (Details)\n"
				. "> namespace: **VDM\\Demo**\n> extends: **BaseWidget**\n\n"
				. "```uml\n@startuml\n\nclass Widget << (F,LightGreen) >> #RoyalBlue {\n}\n\n@enduml\n```",
			$readme
		);
		$this->assertStringContainsString("```\nSuper---1234_abcd---Power\n```", $readme);
		$this->assertStringContainsString('replace the `---` with `___`', $readme);
	}

	/**
	 * Protect Power index namespace grouping, type ordering, and repository links.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPowerMainReadmeSortsByNamespaceDepthTypeAndName(): void
	{
		$items = [
			'z' => ['namespace' => 'VDM\\Deep\\Area', 'type' => 'class', 'name' => 'Zulu', 'path' => 'z', 'power' => 'z.php', 'settings' => 'z.json', 'spk' => 'SPK-Z'],
			'b' => ['namespace' => 'VDM\\Area', 'type' => 'class', 'name' => 'Beta', 'path' => 'b', 'power' => 'b.php', 'settings' => 'b.json', 'spk' => 'SPK-B'],
			'a' => ['namespace' => 'VDM\\Area', 'type' => 'interface', 'name' => 'Alpha', 'path' => 'a', 'power' => 'a.php', 'settings' => 'a.json', 'spk' => 'SPK-A'],
		];
		$readme = (new PowerMain())->get($items);

		$this->assertStringContainsString(
			"# Index of powers\n\n- **Namespace**: [VDM\\Area](#vdm-area)\n\n"
				. "  - **interface Alpha** | [Details](a) | [Raw](a.php) | [Settings](a.json) | SPK: `SPK-A`\n"
				. "  - **class Beta** | [Details](b) | [Raw](b.php) | [Settings](b.json) | SPK: `SPK-B`\n"
				. "- **Namespace**: [VDM\\Deep\\Area](#vdm-deep-area)",
			$readme
		);
	}

	/**
	 * Protect Joomla Power settings table badges and JPK activation syntax.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJoomlaPowerItemReadmeBuildsVersionTableAndJpk(): void
	{
		$readme = (new JoomlaPowerItem())->get((object) [
			'system_name' => 'Application Factory',
			'description' => 'Version-aware Joomla class.',
			'guid' => '3940-3062',
			'settings' => [
				(object) ['namespace' => 'Joomla\\CMS\\Factory', 'joomla_version' => 5],
				(object) ['namespace' => 'Legacy\\Factory', 'joomla_version' => 3],
			],
		]);
		$table = "| Namespace | Joomla Version |\n"
			. "|-----------|----------------|\n"
			. '| `use Joomla\\CMS\\Factory;` | ![Joomla 5](https://img.shields.io/badge/Joomla 5-brightgreen?style=flat-square) |' . "\n"
			. '| `use Legacy\\Factory;` | ![Joomla 3](https://img.shields.io/badge/Joomla 3-blue?style=flat-square) |';

		$this->assertStringStartsWith("### Joomla! Power\n# Application Factory\n\nVersion-aware Joomla class.\n\n{$table}", $readme);
		$this->assertStringContainsString("```\nJoomla---3940_3062---Power\n```", $readme);
	}

	/**
	 * Protect Joomla Power alphabetical index and exact detail/settings/JPK links.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJoomlaPowerMainReadmeSortsIndexAlphabetically(): void
	{
		$items = [
			['name' => 'Zulu', 'path' => 'z', 'settings' => 'z.json', 'jpk' => 'JPK-Z'],
			['name' => 'Alpha', 'path' => 'a', 'settings' => 'a.json', 'jpk' => 'JPK-A'],
		];
		$readme = (new JoomlaPowerMain())->get($items);
		$alpha = '**Alpha** | [Details](a) | [Settings](a.json) | JPK: `JPK-A`';
		$zulu = '**Zulu** | [Details](z) | [Settings](z.json) | JPK: `JPK-Z`';

		$this->assertStringContainsString("# Index of Joomla! Powers\n\n - {$alpha}\n - {$zulu}", $readme);
		$this->assertLessThan(strpos($readme, $zulu), strpos($readme, $alpha));
	}

	/**
	 * Protect repository URL normalization, labels, and complete detail table.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepositoryItemReadmeBuildsLabelsUrlAndDetails(): void
	{
		$readme = (new RepositoryItem())->get((object) [
			'system_name' => 'Team Powers',
			'base' => 'https://git.example.test/',
			'organisation' => '/acme/',
			'repository' => '/powers/',
			'target' => 1,
			'access_repo' => 1,
			'username' => 'builder',
			'author_name' => 'Build Bot',
			'author_email' => 'bot@example.test',
			'read_branch' => 'main',
			'write_branch' => 'develop',
		]);

		$this->assertStringStartsWith(
			"### JCB! Repository\n# [Team Powers](https://git.example.test/acme/powers)\n\n"
				. "- **Target:** Super Power\n- **Access:** Override\n\n"
				. "## Repository Details\n\n| Setting | Value |\n|---------|--------|\n"
				. "| Base Url | https://git.example.test |\n"
				. "| Organisation | acme |\n| Repository | powers |\n| Username | builder |",
			$readme
		);
		$this->assertStringContainsString('| Write Branch | develop |', $readme);
	}

	/**
	 * Protect repository index sorting, optional links, and description cleanup.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepositoryMainReadmeSortsAndSanitizesIndex(): void
	{
		$items = [
			['name' => 'Zulu', 'path' => 'z', 'description' => '<b>Zulu</b> repository'],
			['name' => 'Alpha', 'settings' => 'a.json', 'description' => "Alpha\n repository"],
		];
		$readme = (new RepositoryMain())->get($items);
		$alpha = '**Alpha** | [Settings](a.json) | Alpha repository';
		$zulu = '**Zulu** | [Details](z) | Zulu repository';

		$this->assertStringContainsString("### Index of JCB Repositories\n\n\n - {$alpha}\n - {$zulu}", $readme);
		$this->assertLessThan(strpos($readme, $zulu), strpos($readme, $alpha));
	}

	/**
	 * Protect snippet sections, code trimming, usage, and contributor privacy rules.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSnippetItemReadmeBuildsCodeUsageAndContributorSections(): void
	{
		$readme = (new SnippetItem())->get((object) [
			'name' => 'Card',
			'url' => 'https://example.test/card',
			'heading' => 'Responsive card',
			'description' => 'Reusable markup.',
			'snippet' => "<div>Card</div>\n\n",
			'usage' => 'Insert in a custom view.',
			'contributor_company' => 'Acme',
			'contributor_name' => 'Ada',
			'contributor_email' => 'ada@example.test',
		]);

		$this->assertStringStartsWith(
			"### JCB! Snippet\n# [Card](https://example.test/card)\n\n"
				. "## Responsive card\n\nReusable markup.\n\n"
				. "### Snippet\n```\n<div>Card</div>\n```\n\n"
				. "### Usage\n> Insert in a custom view.\n\n"
				. "### Contributor\n- Acme\n- Ada\n- [email](mailto:ada@example.test)",
			$readme
		);

		$anonymous = (new SnippetItem())->get((object) ['name' => 'Private', 'contributor_company' => 'anonymous']);
		$this->assertStringNotContainsString('### Contributor', $anonymous);
	}

	/**
	 * Protect snippet main index alphabetical ordering and optional metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSnippetMainReadmeSortsIndexAlphabetically(): void
	{
		$items = [
			['name' => 'Zulu', 'path' => 'z', 'desc' => 'Last snippet'],
			['name' => 'Alpha', 'settings' => 'a.json', 'desc' => 'First snippet'],
		];
		$readme = (new SnippetMain())->get($items);
		$alpha = '**Alpha** | [Settings](a.json) | First snippet';
		$zulu = '**Zulu** | [Details](z) | Last snippet';

		$this->assertStringContainsString("### Index of JCB Snippets\n\n\n - {$alpha}\n - {$zulu}", $readme);
		$this->assertLessThan(strpos($readme, $zulu), strpos($readme, $alpha));
	}
}
