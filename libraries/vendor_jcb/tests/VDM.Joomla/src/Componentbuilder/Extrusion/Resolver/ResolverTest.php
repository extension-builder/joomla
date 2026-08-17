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

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Resolver;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Form as FormRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Language as Catalogue;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Condition;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\FieldXml;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Fieldtype;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Language;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Relation;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Role;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Tab;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Text;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\ViewName;
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Tests\Support\TestCase;


/**
 * The extrusion resolvers: the decisions taken between reading and writing.
 *
 * Every resolver here answers one question about a source artifact, and the
 * answer is either derived deterministically or recorded as a guess. These
 * tests hold each answer, and each recorded guess, to its specification.
 *
 * @since  6.1.6
 */
#[CoversClass(Condition::class)]
#[CoversClass(FieldXml::class)]
#[CoversClass(Guid::class)]
#[CoversClass(Language::class)]
#[CoversClass(Relation::class)]
#[CoversClass(Role::class)]
#[CoversClass(Tab::class)]
#[CoversClass(Text::class)]
#[CoversClass(ViewName::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(Config::class)]
#[UsesClass(Catalogue::class)]
#[UsesClass(Fieldtype::class)]
#[UsesClass(FormRegistry::class)]
#[UsesClass(Registry::class)]
#[UsesClass(Report::class)]
#[UsesClass(Resolved::class)]
#[UsesClass(Source::class)]
final class ResolverTest extends TestCase
{
	/**
	 * A canonical GUID a source may already carry, in upper case.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const SUPPLIED_GUID = 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

	/**
	 * The version 5 GUID the fixed source coordinates must always derive to.
	 *
	 * Hard coded on purpose: a derived identity that changed between runs would
	 * make a second extrusion duplicate every definition instead of updating it.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const DERIVED_GUID = '1ce6d6ca-758d-5385-a1c9-5d6c1a29f989';

	/**
	 * The source coordinates that derive DERIVED_GUID.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const DERIVED_PARTS = ['com_demo', '#__demo_item', 'name'];

	/**
	 * An identifier turns into the label a human would have written.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testHumaniseTurnsAnIdentifierIntoAReadableLabel(): void
	{
		$text = new Text();

		$this->assertSame('Name Code', $text->humanise('name_code'));
		$this->assertSame('Name Code', $text->humanise('nameCode'));
		$this->assertSame('Name Code', $text->humanise('name-code'));
		$this->assertSame('Name Code', $text->humanise('  NAME_CODE  '));
		$this->assertSame(
			'List of the Items',
			$text->humanise('list_of_the_items'),
			'A minor word must stay lower case unless it opens the label.'
		);
		$this->assertSame(
			'The List',
			$text->humanise('the_list'),
			'The first word is capitalised even when it is a minor word.'
		);
		$this->assertSame('', $text->humanise('   ---   '));
		$this->assertSame('', $text->humanise(''));
	}

	/**
	 * Words and safe names come from one shared splitting rule.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testWordsAndSafeShareOneSplittingRule(): void
	{
		$text = new Text();

		$this->assertSame(['name', 'code'], $text->words('nameCode'));
		$this->assertSame(['name', 'code'], $text->words('name_code'));
		$this->assertSame(['the', 'quick', 'brown', 'fox'], $text->words('the-quick brown_fox'));
		$this->assertSame(
			['item2', 'code'],
			$text->words('item2_code'),
			'A digit is part of the word it trails, not a boundary.'
		);
		$this->assertSame([], $text->words('  ***  '));
		$this->assertSame([], $text->words(''));
		$this->assertSame('the_quick_brown_fox', $text->safe('The Quick-Brown fox'));
		$this->assertSame('name_code', $text->safe('nameCode'));
		$this->assertSame('', $text->safe('***'));
	}

	/**
	 * Only a canonical GUID is accepted as an identity a source supplied.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValidAcceptsOnlyACanonicalGuid(): void
	{
		$guid = new Guid();

		$this->assertTrue($guid->valid(Guid::NAMESPACE));
		$this->assertTrue($guid->valid('b7d0f1a2-3c64-4f18-9a5e-2f7c8d1b6e30'));
		$this->assertTrue(
			$guid->valid(self::SUPPLIED_GUID),
			'Case must not decide validity.'
		);
		$this->assertFalse($guid->valid('b7d0f1a23c644f189a5e2f7c8d1b6e30'), 'Hyphens are required.');
		$this->assertFalse($guid->valid('b7d0f1a2-3c64-4f18-9a5e-2f7c8d1b6e3'), 'A short group is junk.');
		$this->assertFalse($guid->valid('g7d0f1a2-3c64-4f18-9a5e-2f7c8d1b6e30'), 'Only hex is a GUID.');
		$this->assertFalse($guid->valid('not-a-guid'));
		$this->assertFalse($guid->valid(''));
		$this->assertFalse($guid->valid(null));
		$this->assertFalse($guid->valid(12345));
		$this->assertFalse($guid->valid(['b7d0f1a2-3c64-4f18-9a5e-2f7c8d1b6e30']));
	}

	/**
	 * A derived identity is stable, distinct per source, and version 5 shaped.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDeriveIsDeterministicAndShapedAsVersionFive(): void
	{
		$guid = new Guid();
		$first = $guid->derive(self::DERIVED_PARTS);
		$second = $guid->derive(self::DERIVED_PARTS);

		$this->assertSame($first, $second, 'The same parts must derive the same GUID twice.');
		$this->assertSame(
			self::DERIVED_GUID,
			$first,
			'The derived identity must not move between runs or installations.'
		);
		$this->assertTrue($guid->valid($first));
		$this->assertSame(
			$first,
			$guid->derive([' COM_DEMO ', '#__DEMO_ITEM', 'NAME']),
			'Case and surrounding space are not part of a source coordinate.'
		);
		$this->assertNotSame(
			$first,
			$guid->derive(['com_demo', '#__demo_item', 'code']),
			'A different column must not share an identity.'
		);
		$this->assertNotSame($first, $guid->derive([]));

		$segments = explode('-', $first);

		$this->assertCount(5, $segments);
		$this->assertSame('5', $segments[2][0], 'The version nibble must say version 5.');
		$this->assertContains(
			$segments[3][0],
			['8', '9', 'a', 'b'],
			'The variant bits must be RFC 4122.'
		);
		$this->assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $segments[0]);
		$this->assertMatchesRegularExpression('/^[0-9a-f]{12}$/', $segments[4]);
	}

	/**
	 * A supplied identity wins, and anything else falls back to derivation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPreferKeepsASuppliedGuidAndFallsBackToDerivation(): void
	{
		$guid = new Guid();
		$derived = $guid->derive(self::DERIVED_PARTS);

		$this->assertSame(
			strtolower(self::SUPPLIED_GUID),
			$guid->prefer(self::SUPPLIED_GUID, self::DERIVED_PARTS),
			'A supplied GUID is kept, lower cased.'
		);
		$this->assertSame(self::DERIVED_GUID, $derived);
		$this->assertSame($derived, $guid->prefer('junk', self::DERIVED_PARTS));
		$this->assertSame($derived, $guid->prefer('', self::DERIVED_PARTS));
		$this->assertSame($derived, $guid->prefer(null, self::DERIVED_PARTS));
		$this->assertSame($derived, $guid->prefer(0, self::DERIVED_PARTS));
		$this->assertSame($derived, $guid->prefer([self::SUPPLIED_GUID], self::DERIVED_PARTS));
	}

	/**
	 * Only an upper-case, underscored token is treated as a language constant.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testIsConstantSeparatesConstantsFromText(): void
	{
		[$language] = $this->language();

		$this->assertTrue($language->isConstant('COM_X_Y'));
		$this->assertTrue($language->isConstant('COM_DEMO_ITEM_NAME_LABEL'));
		$this->assertTrue($language->isConstant('JGLOBAL_TITLE'));
		$this->assertFalse($language->isConstant('Name'), 'Mixed case is text, not a constant.');
		$this->assertFalse($language->isConstant('lowercase'));
		$this->assertFalse(
			$language->isConstant('DETAILS'),
			'A single word without an underscore is a label shouted, not a constant.'
		);
		$this->assertFalse($language->isConstant('COM_X Y'));
		$this->assertFalse($language->isConstant('_LEADING'));
		$this->assertFalse($language->isConstant(''));
		$this->assertFalse($language->isConstant(42));
		$this->assertFalse($language->isConstant(null));
	}

	/**
	 * A constant resolves to its English string, and a miss is reported.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResolveReturnsTheCatalogueValueAndRecordsEveryMiss(): void
	{
		[$language, $report] = $this->language();

		$this->assertSame('Name', $language->resolve('COM_DEMO_ITEM_NAME_LABEL'));
		$this->assertFalse(
			$report->exists('unresolved.language.COM_DEMO_ITEM_NAME_LABEL'),
			'A resolved constant is not a gap.'
		);
		$this->assertSame(
			'COM_DEMO_MISSING_LABEL',
			$language->resolve('COM_DEMO_MISSING_LABEL'),
			'An unknown constant is kept verbatim rather than invented.'
		);
		$this->assertTrue($report->get('unresolved.language.COM_DEMO_MISSING_LABEL'));
		$this->assertSame('Fallback', $language->resolve('COM_DEMO_OTHER_LABEL', 'Fallback'));
		$this->assertTrue($report->get('unresolved.language.COM_DEMO_OTHER_LABEL'));
		$this->assertSame(
			'COM_DEMO_EMPTY_LABEL',
			$language->resolve('COM_DEMO_EMPTY_LABEL'),
			'An empty catalogue entry is a miss.'
		);
		$this->assertTrue($report->get('unresolved.language.COM_DEMO_EMPTY_LABEL'));
		$this->assertSame('Plain text', $language->resolve('Plain text'));
		$this->assertSame('', $language->resolve(4321));
		$this->assertSame('fallback', $language->resolve(null, 'fallback'));
	}

	/**
	 * A bag resolves the named display keys and leaves the rest alone.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBagResolvesOnlyTheNamedKeys(): void
	{
		[$language] = $this->language();
		$attributes = [
			'label' => 'COM_DEMO_ITEM_NAME_LABEL',
			'hint' => 'COM_DEMO_ITEM_NAME_LABEL',
			'class' => 'input',
			'size' => 40
		];

		$this->assertSame(
			[
				'label' => 'Name',
				'hint' => 'COM_DEMO_ITEM_NAME_LABEL',
				'class' => 'input',
				'size' => 40
			],
			$language->bag($attributes, ['label']),
			'Only the named keys may be rewritten.'
		);
		$this->assertSame(
			$attributes,
			$language->bag($attributes, ['missing', 'class', 'size']),
			'A key that is absent or not a constant is untouched.'
		);
		$this->assertSame(
			['label' => 'Name', 'hint' => 'Name', 'class' => 'input', 'size' => 40],
			$language->bag($attributes, ['label', 'hint'])
		);
		$this->assertSame([], $language->bag([], ['label']));
	}

	/**
	 * A view name is what remains once the table prefixes are removed.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSingleStripsTheTableAndComponentPrefixes(): void
	{
		$viewname = $this->viewname('com_demo');

		$this->assertSame('item', $viewname->single('#__demo_item'));
		$this->assertSame('item', $viewname->single('#__com_demo_item'));
		$this->assertSame('item', $viewname->single('#__DEMO_ITEM'));
		$this->assertSame('look', $viewname->single('demo_look'));
		$this->assertSame(
			'other_thing',
			$viewname->single('#__other_thing'),
			'A foreign table keeps its own name.'
		);
		$this->assertSame(
			'#__demo_',
			$viewname->single('#__demo_'),
			'Stripping everything is refused; the raw table name is returned.'
		);
		$this->assertSame('categories', $viewname->list('#__demo_category'));
		$this->assertSame(
			'item',
			$this->viewname('demo')->single('#__demo_item'),
			'A code name without the com_ prefix strips the same way.'
		);
		$this->assertSame(
			'demo_item',
			$this->viewname('')->single('#__demo_item'),
			'Without a code name only the table prefix is known.'
		);
	}

	/**
	 * The naive English plural rules JCB itself generates against.
	 *
	 * @return  array<string, array{0: string, 1: string}>
	 * @since   6.1.6
	 */
	public static function plurals(): array
	{
		return [
			'sibilant s' => ['address', 'addresses'],
			'sibilant x' => ['box', 'boxes'],
			'sibilant z' => ['quiz', 'quizes'],
			'sibilant ch' => ['branch', 'branches'],
			'sibilant sh' => ['wish', 'wishes'],
			'consonant y' => ['category', 'categories'],
			'consonant y again' => ['company', 'companies'],
			'vowel y' => ['day', 'days'],
			'plain' => ['item', 'items'],
			'already plural looking' => ['news', 'newses'],
			'empty' => ['', '']
		];
	}

	/**
	 * A list view name is the naive plural of the single view name.
	 *
	 * @param   string  $single  The single view name.
	 * @param   string  $plural  The expected plural view name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('plurals')]
	public function testPluralFollowsTheNaiveEnglishRules(string $single, string $plural): void
	{
		$this->assertSame($plural, $this->viewname('com_demo')->plural($single));
	}

	/**
	 * A view's system name is the humanised view name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTitleHumanisesTheViewName(): void
	{
		$viewname = $this->viewname('com_demo');

		$this->assertSame('Name Code', $viewname->title('name_code'));
		$this->assertSame('Demo Item', $viewname->title('demoItem'));
		$this->assertSame('', $viewname->title(''));
	}

	/**
	 * Roles a table definition class stated outright are used as stated.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAssignPrefersTheRolesStatedByTheTableTier(): void
	{
		$report = new Report();
		$role = new Role(new Resolved(), $report);
		$roles = $role->assign('demo-item', [
			'id' => ['datatype' => ['value' => 'INT', 'origin' => 'xml']],
			'code' => [
				'title' => ['value' => 1, 'origin' => 'table'],
				'list' => ['value' => 1, 'origin' => 'table']
			],
			'name' => ['xml_type' => ['value' => 'text', 'origin' => 'xml']]
		]);

		$this->assertSame(
			[
				'id' => [
					'title' => false, 'alias' => false, 'description' => false,
					'list' => false, 'order' => 0
				],
				'code' => [
					'title' => true, 'alias' => false, 'description' => false,
					'list' => true, 'order' => 1
				],
				'name' => [
					'title' => false, 'alias' => false, 'description' => false,
					'list' => false, 'order' => 2
				]
			],
			$roles,
			'A stated title must beat the name column that would have been inferred.'
		);
		$this->assertSame(
			'table',
			$report->get('roles.demo_item.origin'),
			'The report must say the roles were stated, not guessed.'
		);
		$this->assertSame('demo_item', $role->key('demo-item'));
		$this->assertSame('a_b_c', $role->key('a/b c'));
	}

	/**
	 * A role stated by any tier other than the table tier is not a statement.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAssignIgnoresRolesClaimedByAWeakerTier(): void
	{
		$report = new Report();
		$role = new Role(new Resolved(), $report);
		$roles = $role->assign('demo_item', [
			'code' => [
				'title' => ['value' => 1, 'origin' => 'derived'],
				'list' => ['value' => 0, 'origin' => 'table']
			],
			'name' => ['xml_type' => ['value' => 'text', 'origin' => 'xml']]
		]);

		$this->assertSame('derived', $report->get('roles.demo_item.origin'));
		$this->assertFalse($roles['code']['title'], 'Only the table tier states a role.');
		$this->assertFalse($roles['code']['list'], 'An empty stated value states nothing.');
		$this->assertTrue($roles['name']['title'], 'The name column is inferred instead.');
	}

	/**
	 * Nothing stated means the roles are inferred, capped, and recorded as guesses.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAssignInfersRolesCapsTheListAndKeepsTheFirstListFieldFlagged(): void
	{
		$report = new Report();
		$role = new Role(new Resolved(), $report);
		$roles = $role->assign('demo_item', [
			'name' => ['xml_type' => ['value' => 'text', 'origin' => 'xml']],
			'alias' => ['xml_type' => ['value' => 'text', 'origin' => 'xml']],
			'description' => ['xml_type' => ['value' => 'editor', 'origin' => 'xml']],
			'code' => ['xml_type' => ['value' => 'text', 'origin' => 'xml']],
			'email' => ['xml_type' => ['value' => 'email', 'origin' => 'xml']],
			'website' => ['xml_type' => ['value' => 'url', 'origin' => 'xml']],
			'phone' => ['xml_type' => ['value' => 'tel', 'origin' => 'xml']],
			'note' => ['datatype' => ['value' => 'VARCHAR', 'origin' => 'xml']],
			'params' => ['datatype' => ['value' => 'TEXT', 'origin' => 'xml']]
		]);

		$this->assertTrue(
			$roles['name']['title'],
			'A name column is the title when nothing states one.'
		);
		$this->assertTrue(
			$roles['name']['list'],
			'The first list field must keep its list flag alongside its title flag.'
		);
		$this->assertSame(0, $roles['name']['order']);
		$this->assertTrue($roles['alias']['alias']);
		$this->assertFalse($roles['alias']['list'], 'An alias is not a list column.');
		$this->assertTrue($roles['description']['description']);
		$this->assertTrue($roles['description']['list']);
		$this->assertSame(
			['name', 'description', 'code', 'email', 'website'],
			$this->listed($roles),
			'Exactly five list columns are inferred, in column order.'
		);
		$this->assertFalse($roles['phone']['list'], 'The sixth candidate is past the cap.');
		$this->assertFalse($roles['note']['list']);
		$this->assertFalse(
			$roles['params']['list'],
			'A column Joomla manages never takes a display role.'
		);
		$this->assertSame(
			'derived',
			$report->get('roles.demo_item.origin'),
			'An inferred role set must be reported as a guess.'
		);
		$this->assertSame(range(0, 8), array_column($roles, 'order'));
	}

	/**
	 * Tab names are ordered, de-duplicated, and resolved through the catalogue.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNamesDeduplicatesAndResolvesEveryTabLabel(): void
	{
		[$tab, $report] = $this->tab();
		$names = $tab->names('demo_item', [
			'name' => ['tab' => ['value' => 'basic details', 'origin' => 'table']],
			'code' => ['tab' => ['value' => 'basic_details', 'origin' => 'table']],
			'state' => ['fieldset' => ['value' => 'publishing', 'origin' => 'xml']],
			'note' => ['fieldset' => ['value' => 'meta-data', 'origin' => 'xml']],
			'extra' => [
				'tab' => ['value' => 'Extra', 'origin' => 'table'],
				'fieldset' => ['value' => 'publishing', 'origin' => 'xml']
			],
			'plain' => ['label' => ['value' => 'Plain', 'origin' => 'xml']]
		]);

		$this->assertSame(
			['Basic Details', 'Publishing Options', 'Meta Data', 'Extra', 'Details'],
			$names,
			'Order is field order, and one tab is named once.'
		);
		$this->assertSame(
			$names,
			$report->get('tabs.demo_item'),
			'The tab set must be recorded for the run report.'
		);
		$this->assertSame(
			'Extra',
			$tab->nameFor('demo_item', [
				'tab' => ['value' => 'Extra', 'origin' => 'table'],
				'fieldset' => ['value' => 'publishing', 'origin' => 'xml']
			]),
			'A stated tab name beats the fieldset it sits in.'
		);
		$this->assertSame(
			'Publishing Options',
			$tab->nameFor('demo_item', ['fieldset' => ['value' => 'publishing', 'origin' => 'xml']]),
			'A fieldset label is resolved through the language catalogue.'
		);
		$this->assertSame(
			'Metadata',
			$tab->nameFor('demo_item', ['fieldset' => ['value' => 'metadata', 'origin' => 'xml']]),
			'A fieldset with no label keeps its own name.'
		);
		$this->assertSame(Tab::DEFAULT_TAB, $tab->nameFor('demo_item', []));
		$this->assertSame(
			Tab::DEFAULT_TAB,
			$tab->nameFor('demo_item', ['tab' => ['value' => '   ', 'origin' => 'table']]),
			'A blank stated tab states nothing.'
		);
		$this->assertSame(
			Tab::DEFAULT_TAB,
			$tab->nameFor('demo_item', ['tab' => ['value' => '___', 'origin' => 'table']]),
			'A tab name that cleans away to nothing falls back.'
		);
		$this->assertSame(['Details'], $tab->names('empty_view', []));
		$this->assertSame(['Details'], $report->get('tabs.empty_view'));
		$this->assertSame('demo_item', $tab->key('demo-item'));
	}

	/**
	 * A tab index is one-based, and an unknown tab lands on the first one.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testIndexIsOneBasedAndFallsBackToTheFirstTab(): void
	{
		[$tab] = $this->tab();
		$tabs = ['Basic Details', 'Publishing Options', 'Details'];

		$this->assertSame(1, $tab->index('Basic Details', $tabs));
		$this->assertSame(2, $tab->index('Publishing Options', $tabs));
		$this->assertSame(3, $tab->index('Details', $tabs));
		$this->assertSame(
			1,
			$tab->index('Nowhere', $tabs),
			'An unknown tab must not produce a zero or negative index.'
		);
		$this->assertSame(1, $tab->index('Basic Details', []));
	}

	/**
	 * Every showon form the readers can hand over.
	 *
	 * @return  array<string, array{0: string, 1: array<int, array<string, mixed>>}>
	 * @since   6.1.6
	 */
	public static function showons(): array
	{
		return [
			'single value' => [
				'a:1',
				[['field' => 'a', 'values' => ['1'], 'negate' => false, 'join' => 'AND']]
			],
			'negated' => [
				'a!:0',
				[['field' => 'a', 'values' => ['0'], 'negate' => true, 'join' => 'AND']]
			],
			'multiple values' => [
				'a:1,2',
				[['field' => 'a', 'values' => ['1', '2'], 'negate' => false, 'join' => 'AND']]
			],
			'blank values dropped' => [
				'a:1, ,2',
				[['field' => 'a', 'values' => ['1', '2'], 'negate' => false, 'join' => 'AND']]
			],
			'and joined' => [
				'a:1[AND]b:2',
				[
					['field' => 'a', 'values' => ['1'], 'negate' => false, 'join' => 'AND'],
					['field' => 'b', 'values' => ['2'], 'negate' => false, 'join' => 'AND']
				]
			],
			'or joined' => [
				'a:1[OR]b!:2,3',
				[
					['field' => 'a', 'values' => ['1'], 'negate' => false, 'join' => 'AND'],
					['field' => 'b', 'values' => ['2', '3'], 'negate' => true, 'join' => 'OR']
				]
			],
			'lower case join' => [
				'a:1[or]b:2',
				[
					['field' => 'a', 'values' => ['1'], 'negate' => false, 'join' => 'AND'],
					['field' => 'b', 'values' => ['2'], 'negate' => false, 'join' => 'OR']
				]
			],
			'empty' => ['', []],
			'blank' => ['   ', []],
			'no separator' => ['garbage', []],
			'no field' => [':1', []]
		];
	}

	/**
	 * A showon expression parses into field, values, negation, and join.
	 *
	 * @param   string                            $showon    The raw showon value.
	 * @param   array<int, array<string, mixed>>  $expected  The expected clauses.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('showons')]
	public function testParseUnderstandsEveryShowonForm(string $showon, array $expected): void
	{
		$this->assertSame($expected, (new Condition(new Report()))->parse($showon));
	}

	/**
	 * Fields sharing one dependency become one condition with many targets.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBuildGroupsTargetsPerDistinctCondition(): void
	{
		$report = new Report();
		$condition = new Condition($report);
		$conditions = $condition->build('demo-item', [
			'a' => ['showon' => ['value' => 'kind:1', 'origin' => 'xml']],
			'b' => ['showon' => ['value' => 'kind:1', 'origin' => 'xml']],
			'c' => ['showon' => ['value' => 'kind:2', 'origin' => 'xml']],
			'd' => ['showon' => ['value' => 'kind!:1', 'origin' => 'xml']],
			'e' => ['showon' => ['value' => '   ', 'origin' => 'xml']],
			'f' => ['showon' => ['value' => 42, 'origin' => 'xml']],
			'g' => []
		]);

		$this->assertSame(['kind|1|0', 'kind|2|0', 'kind|1|1'], array_keys($conditions));
		$this->assertSame(
			['match' => 'kind', 'values' => ['1'], 'targets' => ['a', 'b'], 'negate' => false],
			$conditions['kind|1|0'],
			'Two fields with the same dependency share one condition.'
		);
		$this->assertSame(
			['match' => 'kind', 'values' => ['2'], 'targets' => ['c'], 'negate' => false],
			$conditions['kind|2|0']
		);
		$this->assertSame(
			['match' => 'kind', 'values' => ['1'], 'targets' => ['d'], 'negate' => true],
			$conditions['kind|1|1'],
			'A negated dependency is a distinct condition.'
		);
		$this->assertSame(3, $report->get('conditions.demo_item'));
		$this->assertSame([], $condition->build('other_view', ['a' => []]));
		$this->assertFalse(
			$report->exists('conditions.other_view'),
			'A view with no conditions is not reported as having any.'
		);
	}

	/**
	 * A declared link becomes a normalised, reported relationship.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRelationResolveNormalisesAndReportsTheDeclaredLink(): void
	{
		[$relation, $report] = $this->relation();

		$this->assertSame(
			[
				'column' => 'category',
				'table' => '#__demo_category',
				'view' => 'category',
				'entity' => 'category',
				'value' => 'title',
				'key' => 'guid',
				'component' => 'com_demo',
				'local' => true
			],
			$relation->resolve('demo-item', 'category', [
				'link' => ['value' => [
					'table' => '#__demo_category',
					'entity' => 'category',
					'value' => 'title',
					'key' => 'guid',
					'component' => 'com_demo'
				], 'origin' => 'table']
			])
		);
		$this->assertSame(
			'#__demo_category via guid showing title',
			$report->get('relations.demo_item.category'),
			'The relationship must be recorded so nothing is silently lost.'
		);
		$this->assertSame(
			[
				'column' => 'cat',
				'table' => '#__demo_category',
				'view' => 'category',
				'entity' => '',
				'value' => 'name',
				'key' => 'id',
				'component' => '',
				'local' => true
			],
			$relation->resolve('item', 'cat', [
				'link' => ['value' => ['table' => '#__demo_category'], 'origin' => 'table']
			]),
			'Without an entity the view name comes from the table, and the defaults apply.'
		);
		$this->assertSame('demo_item', $relation->key('demo-item'));
	}

	/**
	 * No relations option and no link both mean there is no relationship.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRelationResolveReturnsNullWhenDisabledOrUnlinked(): void
	{
		[$relation, $report, $config] = $this->relation();
		$properties = ['link' => ['value' => ['table' => '#__demo_category'], 'origin' => 'table']];

		$config->set('relations', false);

		$this->assertNull(
			$relation->resolve('item', 'cat', $properties),
			'A run with relations off must resolve nothing.'
		);
		$this->assertFalse($report->exists('relations.item.cat'));

		$config->set('relations', true);

		$this->assertNull($relation->resolve('item', 'cat', []), 'No link is no relationship.');
		$this->assertNull($relation->resolve('item', 'cat', ['link' => ['value' => [], 'origin' => 'table']]));
		$this->assertNull(
			$relation->resolve('item', 'cat', ['link' => ['value' => 'category', 'origin' => 'table']]),
			'A link that is not a declaration array is not a relationship.'
		);
		$this->assertNull(
			$relation->resolve('item', 'cat', ['link' => ['value' => ['table' => '   '], 'origin' => 'table']]),
			'A link without a target table names nothing.'
		);
		$this->assertFalse($report->exists('relations.item.cat'));
	}

	/**
	 * A relationship is local only when its target view is part of the run.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testReconcileMarksLocalAndRecordsExternalRelationships(): void
	{
		[$relation, $report] = $this->relation();
		$reconciled = $relation->reconcile(
			[
				['column' => 'category', 'table' => '#__demo_category', 'view' => 'category', 'local' => true],
				['column' => 'owner', 'table' => '#__users', 'view' => 'user', 'local' => true],
				['column' => 'orphan', 'table' => '#__demo_gone', 'view' => '', 'local' => true]
			],
			['item', 'category']
		);

		$this->assertTrue($reconciled[0]['local'], 'A target view inside the run is local.');
		$this->assertFalse($reconciled[1]['local'], 'A target view outside the run is not local.');
		$this->assertFalse($reconciled[2]['local']);
		$this->assertSame('#__users', $report->get('relations.external.user'));
		$this->assertSame(
			'#__demo_gone',
			$report->get('relations.external.unknown'),
			'A relationship with no target view is still reported.'
		);
		$this->assertFalse(
			$report->exists('relations.external.category'),
			'A local relationship is not reported as external.'
		);
		$this->assertSame(
			[['column' => 'a', 'table' => '#__x', 'view' => 'item', 'local' => true]],
			$relation->reconcile([['column' => 'a', 'table' => '#__x', 'view' => 'item']], ['item'])
		);
	}

	/**
	 * The stored field element is self closing, ordered, escaped, and filtered.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldXmlBuildEmitsAFilteredSelfClosingElement(): void
	{
		$xml = $this->fieldXml();
		$properties = [
			'xml_type' => ['value' => 'text', 'origin' => 'xml'],
			'label' => ['value' => 'The "big" name', 'origin' => 'xml'],
			'required' => ['value' => '1', 'origin' => 'xml'],
			'attributes' => ['value' => [
				'type' => 'text',
				'showon' => 'kind:1',
				'hint' => 'Type here',
				'class' => 'form-control'
			], 'origin' => 'xml']
		];

		$this->assertSame(
			implode(PHP_EOL, [
				'<field',
				"\t" . 'name="name"',
				"\t" . 'label="The &quot;big&quot; name"',
				"\t" . 'class="form-control"',
				"\t" . 'required="1"',
				'/>'
			]),
			$xml->build('name', $properties)
		);

		$attributes = $xml->attributes('name', $properties);

		$this->assertSame(
			['name', 'label', 'class', 'required'],
			array_keys($attributes),
			'The name is written first, then JCB attribute order.'
		);
		$this->assertSame('The "big" name', $attributes['label'], 'Escaping happens on output only.');
		$this->assertArrayNotHasKey('type', $attributes, 'The type is implied by the field type.');
		$this->assertArrayNotHasKey('showon', $attributes, 'A showon becomes a JCB condition.');
		$this->assertArrayNotHasKey(
			'hint',
			$attributes,
			'An attribute the field type does not declare is dropped.'
		);
	}

	/**
	 * A field with options becomes an element with option children.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldXmlBuildEmitsOptionChildren(): void
	{
		$xml = $this->fieldXml();

		$this->assertSame(
			implode(PHP_EOL, [
				'<field',
				"\t" . 'name="status"',
				"\t" . 'label="Status"',
				'>',
				"\t" . '<option value="1">Yes</option>',
				"\t" . '<option value="0">No &amp; maybe</option>',
				"\t" . '<option value="">' . '</option>',
				'</field>'
			]),
			$xml->build('status', [
				'xml_type' => ['value' => 'text', 'origin' => 'xml'],
				'label' => ['value' => 'Status', 'origin' => 'xml'],
				'options' => ['value' => [
					['value' => '1', 'text' => 'Yes'],
					['value' => '0', 'text' => 'No & maybe'],
					['text' => ''],
					['nothing' => 'here']
				], 'origin' => 'xml']
			])
		);
		$this->assertSame(
			implode(PHP_EOL, ['<field', "\t" . 'name="status"', '/>']),
			$xml->build('status', [
				'xml_type' => ['value' => 'text', 'origin' => 'xml'],
				'options' => ['value' => 'not-a-list', 'origin' => 'xml']
			]),
			'An option list that is not a list produces no children.'
		);
	}

	/**
	 * An unresolvable field type declares nothing, so nothing is filtered out.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldXmlKeepsEveryAttributeWhenTheFieldTypeIsUnknown(): void
	{
		$xml = $this->fieldXml();
		$attributes = $xml->attributes('name', [
			'xml_type' => ['value' => 'nosuchtype', 'origin' => 'xml'],
			'label' => ['value' => 'Name', 'origin' => 'xml'],
			'attributes' => ['value' => [
				'type' => 'nosuchtype',
				'showon' => 'kind:1',
				'hint' => 'Type here'
			], 'origin' => 'xml']
		]);

		$this->assertSame(['name', 'label', 'hint'], array_keys($attributes));
		$this->assertSame(
			'Type here',
			$attributes['hint'],
			'With no declared property set, filtering must let everything through.'
		);
		$this->assertArrayNotHasKey('type', $attributes, 'The type is dropped regardless.');
		$this->assertArrayNotHasKey('showon', $attributes);
	}

	/**
	 * A language resolver over a small catalogue, with its report.
	 *
	 * @return  array{0: Language, 1: Report}
	 * @since   6.1.6
	 */
	private function language(): array
	{
		$catalogue = new Catalogue();
		$catalogue->set('constant.COM_DEMO_ITEM_NAME_LABEL', 'Name');
		$catalogue->set('constant.COM_DEMO_EMPTY_LABEL', '');
		$report = new Report();

		return [new Language($catalogue, $report), $report];
	}

	/**
	 * A view name resolver for one component code name.
	 *
	 * @param   string  $codeName  The component code name, which may be empty.
	 *
	 * @return  ViewName  The resolver.
	 * @since   6.1.6
	 */
	private function viewname(string $codeName): ViewName
	{
		$source = new Source();
		$source->set('code_name', $codeName);

		return new ViewName($source, new Text());
	}

	/**
	 * A tab resolver over one fieldset label, with its report.
	 *
	 * @return  array{0: Tab, 1: Report}
	 * @since   6.1.6
	 */
	private function tab(): array
	{
		$form = new FormRegistry();
		$form->set('view.demo_item.fieldset.publishing.label', 'COM_DEMO_PUBLISHING_FIELDSET');
		$catalogue = new Catalogue();
		$catalogue->set('constant.COM_DEMO_PUBLISHING_FIELDSET', 'Publishing Options');
		$report = new Report();

		return [new Tab($form, new Language($catalogue, $report), $report), $report];
	}

	/**
	 * A relation resolver, with its report and the configuration it reads.
	 *
	 * @return  array{0: Relation, 1: Report, 2: Config}
	 * @since   6.1.6
	 */
	private function relation(): array
	{
		$config = new Config();
		$report = new Report();

		return [new Relation($config, $this->viewname('com_demo'), $report), $report, $config];
	}

	/**
	 * A field XML composer over a catalogue declaring a limited property set.
	 *
	 * @return  FieldXml  The composer.
	 * @since   6.1.6
	 */
	private function fieldXml(): FieldXml
	{
		$report = new Report();

		return new FieldXml(
			new Fieldtype($this->loader(), new Source(), $report),
			$report
		);
	}

	/**
	 * A database boundary returning one field type row.
	 *
	 * The row's properties JSON declares the type property, whose example is the
	 * Joomla XML type string, and only four attributes beyond it. That limited
	 * set is what the XML composer must filter against.
	 *
	 * @return  LoadInterface  The database loader.
	 * @since   6.1.6
	 */
	private function loader(): LoadInterface
	{
		return new class implements LoadInterface
		{
			/**
			 * Load data rows as an array of associated arrays.
			 *
			 * @param   array       $select  Array of selection keys.
			 * @param   array       $tables  Array of tables to search.
			 * @param   array|null  $where   Array of where key=>value match exist.
			 * @param   array|null  $order   Array of how to order the data.
			 * @param   int|null    $limit   Limit the number of values returned.
			 *
			 * @return  array|null
			 * @since   6.1.6
			 */
			public function rows(array $select, array $tables, ?array $where = null,
				?array $order = null, ?int $limit = null): ?array
			{
				return null;
			}

			/**
			 * Load data rows as an array of objects.
			 *
			 * @param   array       $select  Array of selection keys.
			 * @param   array       $tables  Array of tables to search.
			 * @param   array|null  $where   Array of where key=>value match exist.
			 * @param   array|null  $order   Array of how to order the data.
			 * @param   int|null    $limit   Limit the number of values returned.
			 *
			 * @return  array|null
			 * @since   6.1.6
			 */
			public function items(array $select, array $tables, ?array $where = null,
				?array $order = null, ?int $limit = null): ?array
			{
				return [(object) [
					'id' => 7,
					'name' => 'Text',
					'properties' => json_encode([
						['name' => 'type', 'example' => 'text'],
						['name' => 'name', 'example' => 'name'],
						['name' => 'label', 'example' => 'COM_X_LABEL'],
						['name' => 'class', 'example' => 'form-control'],
						['name' => 'required', 'example' => 'true']
					])
				]];
			}

			/**
			 * Load data row as an associated array.
			 *
			 * @param   array       $select  Array of selection keys.
			 * @param   array       $tables  Array of tables to search.
			 * @param   array|null  $where   Array of where key=>value match exist.
			 * @param   array|null  $order   Array of how to order the data.
			 *
			 * @return  array|null
			 * @since   6.1.6
			 */
			public function row(array $select, array $tables, ?array $where = null,
				?array $order = null): ?array
			{
				return null;
			}

			/**
			 * Load data row as an object.
			 *
			 * @param   array       $select  Array of selection keys.
			 * @param   array       $tables  Array of tables to search.
			 * @param   array|null  $where   Array of where key=>value match exist.
			 * @param   array|null  $order   Array of how to order the data.
			 *
			 * @return  object|null
			 * @since   6.1.6
			 */
			public function item(array $select, array $tables, ?array $where = null,
				?array $order = null): ?object
			{
				return null;
			}

			/**
			 * Get the max value based on a filtered result from a given table.
			 *
			 * @param   string  $field   The field key.
			 * @param   array   $tables  The table.
			 * @param   array   $filter  The filter keys.
			 *
			 * @return  int|null
			 * @since   6.1.6
			 */
			public function max($field, array $tables, array $filter): ?int
			{
				return null;
			}

			/**
			 * Count the number of items based on filter result from a given table.
			 *
			 * @param   array  $tables  The table.
			 * @param   array  $filter  The filter keys.
			 *
			 * @return  int|null
			 * @since   6.1.6
			 */
			public function count(array $tables, array $filter): ?int
			{
				return null;
			}

			/**
			 * Load one value from a row.
			 *
			 * @param   array       $select  Array of selection keys.
			 * @param   array       $tables  Array of tables to search.
			 * @param   array|null  $where   Array of where key=>value match exist.
			 * @param   array|null  $order   Array of how to order the data.
			 *
			 * @return  mixed
			 * @since   6.1.6
			 */
			public function value(array $select, array $tables, ?array $where = null,
				?array $order = null)
			{
				return null;
			}

			/**
			 * Load values from multiple rows.
			 *
			 * @param   array       $select  Array of selection keys.
			 * @param   array       $tables  Array of tables to search.
			 * @param   array|null  $where   Array of where key=>value match exist.
			 * @param   array|null  $order   Array of how to order the data.
			 * @param   int|null    $limit   Limit the number of values returned.
			 *
			 * @return  array|null
			 * @since   6.1.6
			 */
			public function values(array $select, array $tables, ?array $where = null,
				?array $order = null, ?int $limit = null): ?array
			{
				return null;
			}
		};
	}

	/**
	 * The columns an assigned role set marked as list columns, in order.
	 *
	 * @param   array<string, array<string, mixed>>  $roles  The assigned roles.
	 *
	 * @return  array<int, string>  The list column names.
	 * @since   6.1.6
	 */
	private function listed(array $roles): array
	{
		$columns = [];

		foreach ($roles as $column => $flags)
		{
			if (($flags['list'] ?? false) === true)
			{
				$columns[] = (string) $column;
			}
		}

		return $columns;
	}
}
