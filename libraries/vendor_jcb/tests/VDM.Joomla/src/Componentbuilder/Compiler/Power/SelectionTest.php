<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    29th September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Power;


use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Compiler\Power\Selection;
use VDM\Joomla\Componentbuilder\Compiler\Service\Power as PowerProvider;
use VDM\Tests\Support\TestCase;


/**
 * Shared compiler dependency selection preserves effective code and sentinels.
 *
 * @since  6.2.0
 */
#[CoversClass(Selection::class)]
final class SelectionTest extends TestCase
{
	/**
	 * Late utility selection follows generated templates and admin-view roles.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testLateUtilitiesRetainOrdinaryTokenEnablementAndPhase(): void
	{
		$subject = new Selection();
		$expected = ['7d95ce74-53dc-4672-bd8a-3b71cdacabea' => 0];
		$this->assertSame([], $subject->lateUtilityPowers(3));
		$this->assertSame($expected, $subject->lateUtilityPowers(3, true));

		foreach ([4, 5, 6] as $target)
		{
			$this->assertSame($expected, $subject->lateUtilityPowers($target));
		}

		$this->assertArrayNotHasKey(array_key_first($expected), $subject->utilityPowers());
		$this->assertFalse($subject->enabled(false, current($expected)));
		$root = dirname(__DIR__, 8);
		$template = file_get_contents($root . '/admin/compiler/joomla_4/ADMIN_HELPER_CLASS.php');
		$this->assertStringContainsString(Selection::permittedActionsToken() . '::get(', $template);
		$this->assertStringNotContainsString(Selection::permittedActionsToken(),
			file_get_contents($root . '/admin/compiler/joomla_3/ADMIN_HELPER_CLASS.php'));
	}

	/**
	 * Stored selector keys, empty values and explicit custom values stay intact.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testSelectionsRetainCompilerJsonValues(): void
	{
		$subject = new Selection();
		$this->assertSame(['slot' => ['load' => 'guid', 'as' => 'Alias']], $subject->decode('{"slot":{"load":"guid","as":"Alias"}}'));
		$this->assertSame([0, '', '-1'], $subject->decode('[0,"","-1"]'));
		$this->assertSame([], $subject->decode('[]'));
		$this->assertSame(false, $subject->decode('false'));
		$this->assertNull($subject->decode('{broken'));
		$this->assertNull($subject->decode(null));
		$this->assertFalse($subject->enabled(false));
		$this->assertTrue($subject->enabled(false, 1));
		$this->assertFalse($subject->enabled(false, 2));
		$this->assertTrue($subject->enabled(true));
		$this->assertSame([
			'name', 'description', 'head', 'main_class_code', 'licensing_template',
			'extends_custom', 'implements_custom', 'extendsinterfaces_custom',
		], $subject->codeFields());
	}

	/**
	 * Inactive inheritance, headers and license code cannot add dependencies.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testCodeEnablementFollowsTheCompilerSelectors(): void
	{
		$subject = new Selection();
		$power = [
			'type' => 'class',
			'add_head' => '0',
			'add_licensing_template' => '1',
			'licensing_template' => 'code',
			'extends' => '-1',
			'extends_custom' => 'Base',
			'extendsinterfaces' => '["-1"]',
			'extendsinterfaces_custom' => 'Contract',
			'implements' => '[0, "", "-1"]',
			'implements_custom' => 'Implemented',
		];
		$this->assertFalse($subject->codeEnabled($power, 'head'));
		$this->assertFalse($subject->codeEnabled($power, 'licensing_template'));
		$this->assertTrue($subject->codeEnabled($power, 'extends_custom'));
		$this->assertFalse($subject->codeEnabled($power, 'extendsinterfaces_custom'));
		$this->assertTrue($subject->codeEnabled($power, 'implements_custom'));
		$power['type'] = 'interface';
		$power['add_head'] = '1';
		$power['add_licensing_template'] = '2';
		$this->assertTrue($subject->codeEnabled($power, 'head'));
		$this->assertTrue($subject->codeEnabled($power, 'licensing_template'));
		$this->assertFalse($subject->codeEnabled($power, 'extends_custom'));
		$this->assertTrue($subject->codeEnabled($power, 'extendsinterfaces_custom'));
		$power['implements'] = '[0, ""]';
		$this->assertFalse($subject->codeEnabled($power, 'implements_custom'));
		$this->assertSame('extends', $subject->inheritanceField('trait'));
		$this->assertSame('extendsinterfaces', $subject->inheritanceField('interface'));
	}

	/**
	 * Both literal quote forms and Joomla Power layout calls retain their order.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testLiteralRoutesUseTheCoreTokenGrammar(): void
	{
		$subject = new Selection();
		$code = <<<'CODE'
[CUSTOMCODE=17+arg1,arg2] [CUSTOMCODE=helper]
$this->loadTemplate('card'); $this->loadTemplate("row");
LayoutHelper::render('shared', []); LayoutHelper::render("other", []);
Joomla___7ab82272_0b3d_4bb1_af35_e63a096cfe0b___Power::render('aliased', []);
LayoutHelper::render($dynamic, []);
CODE;
		$this->assertSame([
			'custom_code' => ['17+arg1,arg2', 'helper'],
			'template' => ['card', 'row'],
			'layout' => ['shared', 'other', 'aliased'],
		], $subject->codeReferences($code));
		$this->assertSame(['custom_code' => [], 'template' => [], 'layout' => []], $subject->codeReferences(''));
	}

	/**
	 * Pure selection resolves independently without instantiating the compiler.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testProviderSharesPureSelectionsWithoutBuildDependencies(): void
	{
		$container = new Container();
		(new PowerProvider())->register($container);
		$selection = $container->get('Power.Selection');
		$this->assertSame($selection, $container->get(Selection::class));
		$this->assertSame(['one'], $selection->decode('["one"]'));
	}
}
