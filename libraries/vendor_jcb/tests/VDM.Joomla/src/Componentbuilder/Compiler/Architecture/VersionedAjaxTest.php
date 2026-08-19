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
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;


/**
 * Generated ajax contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedAjaxTest extends ArchitectureTestCase
{
	/**
	 * The ajax case a modern target writes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_CASES_MODERN = <<<'GEN'

				case 'doThing':
					try
					{
						$idValue = $jinput->get('id', 0, 'INT');
						if($idValue)
						{
							$ajaxModule = $this->getModel('ajax', 'Administrator');
							if ($ajaxModule)
							{
								$result = $ajaxModule->getThing($idValue);
							}
							else
							{
								$result = ['error' => 'There was an error! [149]'];
							}
						}
						else
						{
							$result = ['error' => 'There was an error! [149]'];
						}
						if($callback)
						{
							echo $callback . "(".json_encode($result).");";
						}
						elseif($returnRaw)
						{
							echo json_encode($result);
						}
						else
						{
							echo "(".json_encode($result).");";
						}
					}
					catch(\Exception $e)
					{
						if($callback)
						{
							echo $callback."(".json_encode($e).");";
						}
						elseif($returnRaw)
						{
							echo json_encode($e);
						}
						else
						{
							echo "(".json_encode($e).");";
						}
					}
				break;
GEN;

	/**
	 * The ajax case Joomla 3 writes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_CASES_J3 = <<<'GEN'

				case 'doThing':
					try
					{
						$idValue = $jinput->get('id', 0, 'INT');
						if($idValue)
						{
							$ajaxModule = $this->getModel('ajax');
							if ($ajaxModule)
							{
								$result = $ajaxModule->getThing($idValue);
							}
							else
							{
								$result = false;
							}
						}
						else
						{
							$result = false;
						}
						if($callback)
						{
							echo $callback . "(".json_encode($result).");";
						}
						elseif($returnRaw)
						{
							echo json_encode($result);
						}
						else
						{
							echo "(".json_encode($result).");";
						}
					}
					catch(\Exception $e)
					{
						if($callback)
						{
							echo $callback."(".json_encode($e).");";
						}
						elseif($returnRaw)
						{
							echo json_encode($e);
						}
						else
						{
							echo "(".json_encode($e).");";
						}
					}
				break;
GEN;

	/**
	 * The ajax case of a task that allows a zero value, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_CASES_UNCHECKED = <<<'GEN'

				case 'doThing':
					try
					{
						$idValue = $jinput->get('id', 0, 'INT');
						$ajaxModule = $this->getModel('ajax', 'Administrator');
						if ($ajaxModule)
						{
							$result = $ajaxModule->getThing($idValue);
						}
						else
						{
							$result = ['error' => 'There was an error! [149]'];
						}
						if($callback)
						{
							echo $callback . "(".json_encode($result).");";
						}
						elseif($returnRaw)
						{
							echo json_encode($result);
						}
						else
						{
							echo "(".json_encode($result).");";
						}
					}
					catch(\Exception $e)
					{
						if($callback)
						{
							echo $callback."(".json_encode($e).");";
						}
						elseif($returnRaw)
						{
							echo json_encode($e);
						}
						else
						{
							echo "(".json_encode($e).");";
						}
					}
				break;
GEN;

	/**
	 * The token declaration a modern target writes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_TOKEN_MODERN = <<<'GEN'

		// Add Ajax Token
		$this->getDocument()->getWebAssetManager()->addInlineScript("var token = '" . Joomla___5ba38513_5c4f_4b0d_935e_49e986a6bce8___Power::getFormToken() . "';");
GEN;

	/**
	 * The token declaration Joomla 3 writes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_TOKEN_J3 = <<<'GEN'

		// Add Ajax Token
		$this->getDocument()->addScriptDeclaration("var token = '" . Joomla___5ba38513_5c4f_4b0d_935e_49e986a6bce8___Power::getFormToken() . "';");
GEN;

	/**
	 * The targets that name the side a model belongs to.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function modernVersions(): array
	{
		return [
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
			'Joomla 6' => ['JoomlaSix'],
		];
	}

	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => ['JoomlaThree'],
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
			'Joomla 6' => ['JoomlaSix'],
		];
	}

	/**
	 * A dispenser that only carries what it was handed.
	 *
	 * @param   array  $hub  What the compiler collected.
	 *
	 * @return  Dispenser
	 * @since   6.1.7
	 */
	private function dispenser(array $hub): Dispenser
	{
		$dispenser = (new ReflectionClass(Dispenser::class))->newInstanceWithoutConstructor();
		$dispenser->hub = $hub;

		return $dispenser;
	}

	/**
	 * One ajax task, as the compiler collected it.
	 *
	 * @param   array  $over  What to say differently about it.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function task(array $over = []): array
	{
		return array_merge([
			'task_name' => 'doThing',
			'value_name' => 'id',
			'input_default' => '0',
			'input_filter' => 'INT',
			'method_name' => 'getThing',
		], $over);
	}

	/**
	 * Build the ajax case writer of a target.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   array   $hub      What the compiler collected.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function cases(string $version, array $hub): object
	{
		return $this->renderer(
			$this->targetClass($version, 'Controller\\AjaxCases', ['JoomlaThree']),
			['dispenser' => $this->dispenser($hub)]
		);
	}

	/**
	 * Build the ajax token writer of a target.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   array   $hub      What the compiler collected.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function token(string $version, array $hub): object
	{
		return $this->renderer(
			$this->targetClass($version, 'View\\AjaxToken', ['JoomlaThree']),
			['dispenser' => $this->dispenser($hub)]
		);
	}

	/**
	 * A target that declares no ajax is given no cases.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testATargetWithoutAjaxIsGivenNoCases(string $version): void
	{
		$this->assertSame('', $this->cases($version, [])->get('admin'));
		$this->assertSame(
			'', $this->cases($version, ['admin' => ['ajax_controller' => []]])->get('admin')
		);
	}

	/**
	 * A modern target asks for its ajax model by the side it belongs to, and
	 * answers a task that cannot run with an error.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernTargetNamesTheSideOfItsAjaxModel(string $version): void
	{
		$hub = ['admin' => ['ajax_controller' => [[$this->task()]]]];

		$this->assertSame(
			self::EXPECTED_CASES_MODERN, $this->cases($version, $hub)->get('admin')
		);
	}

	/**
	 * Joomla 3 asks for its ajax model without naming a side, and answers a
	 * task that cannot run with false.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeAsksForItsAjaxModelWithoutASide(): void
	{
		$hub = ['admin' => ['ajax_controller' => [[$this->task()]]]];

		$this->assertSame(
			self::EXPECTED_CASES_J3, $this->cases('JoomlaThree', $hub)->get('admin')
		);
	}

	/**
	 * A task that allows a zero value is run without checking its values first.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATaskThatAllowsZeroIsRunWithoutAValueCheck(): void
	{
		$hub = ['admin' => ['ajax_controller' => [[$this->task(['allow_zero' => 1])]]]];

		$this->assertSame(
			self::EXPECTED_CASES_UNCHECKED, $this->cases('JoomlaSix', $hub)->get('admin')
		);
	}

	/**
	 * A task that asks for a user check has one added to its values.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATaskThatAsksForAUserCheckGetsOne(): void
	{
		$hub = ['admin' => ['ajax_controller' => [[$this->task(['user_check' => 1])]]]];

		$this->assertStringContainsString(
			'if($idValue && $user->id != 0)', $this->cases('JoomlaSix', $hub)->get('admin')
		);
	}

	/**
	 * A site task asks for the model on the site side.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASiteTaskAsksForTheSiteModel(): void
	{
		$hub = ['site' => ['ajax_controller' => [[$this->task()]]]];

		$this->assertStringContainsString(
			"\$this->getModel('ajax', 'Site');", $this->cases('JoomlaSix', $hub)->get('site')
		);
	}

	/**
	 * A view that makes no ajax calls declares no token.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewWithoutAjaxDeclaresNoToken(string $version): void
	{
		$view = 'demo';
		$off = 'demo';

		$this->assertSame('', $this->token($version, [])->get($view));
		$this->assertSame(
			'', $this->token($version, ['token' => ['demo' => 0]])->get($off)
		);
	}

	/**
	 * A modern target hands its token to the web asset manager.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernTargetHandsItsTokenToTheAssetManager(string $version): void
	{
		$view = 'demo';

		$this->assertSame(
			self::EXPECTED_TOKEN_MODERN,
			$this->token($version, ['token' => ['demo' => 1]])->get($view)
		);
	}

	/**
	 * Joomla 3 declares its token straight on the document.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeDeclaresItsTokenOnTheDocument(): void
	{
		$view = 'demo';

		$this->assertSame(
			self::EXPECTED_TOKEN_J3,
			$this->token('JoomlaThree', ['token' => ['demo' => 1]])->get($view)
		);
	}
}
