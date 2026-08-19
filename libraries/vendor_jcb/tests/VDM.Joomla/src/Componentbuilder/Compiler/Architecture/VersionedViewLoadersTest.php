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
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\GetModules;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\GetModule;
use VDM\Joomla\Componentbuilder\Compiler\Builder\LibraryManager;
use VDM\Joomla\Componentbuilder\Compiler\Library\Document;
use VDM\Joomla\Componentbuilder\Compiler\Registry;


/**
 * Generated view loader contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedViewLoadersTest extends ArchitectureTestCase
{
	/**
	 * The loader a modern target writes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_LIBS_MODERN_ADMIN = <<<'GEN'


		// Only load jQuery if needed. (default is true)
		if ($this->params->get('add_jquery_framework', 1) == 1)
		{
			Html::_('jquery.framework');
		}
		// Load the header checker class.
		// Initialize the header checker.
		$HeaderCheck = new HeaderCheck();
GEN;

	/**
	 * The loader Joomla 3 writes for the administrator, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_LIBS_J3_ADMIN = <<<'GEN'


		// Only load jQuery if needed. (default is true)
		if ($this->params->get('add_jquery_framework', 1) == 1)
		{
			Html::_('jquery.framework');
		}
		// Load the header checker class.
		require_once( JPATH_ADMINISTRATOR . '/components/com_demo/helpers/headercheck.php' );
		// Initialize the header checker.
		$HeaderCheck = new demoHeaderCheck();
GEN;

	/**
	 * The loader Joomla 3 writes for the site, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_LIBS_J3_SITE = <<<'GEN'


		// Only load jQuery if needed. (default is true)
		if ($this->params->get('add_jquery_framework', 1) == 1)
		{
			Html::_('jquery.framework');
		}
		// Load the header checker class.
		require_once( JPATH_SITE . '/components/com_demo/helpers/headercheck.php' );
		// Initialize the header checker.
		$HeaderCheck = new demoHeaderCheck();
GEN;

	/**
	 * The loader of a view linked to a library, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_LIBS_WITH_LIBRARY = <<<'GEN'


		// Only load jQuery if needed. (default is true)
		if ($this->params->get('add_jquery_framework', 1) == 1)
		{
			Html::_('jquery.framework');
		}
		// Load the header checker class.
		// Initialize the header checker.
		$HeaderCheck = new HeaderCheck();

$this->getDocument()->addScript("a.js");
GEN;

	/**
	 * The module loader this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODULES = <<<'GEN'


	/**
	 * Get the modules published in a position
	 */
	public function getModules($position, $seperator = '', $class = '')
	{
		// set default
		$found = false;
		// check if we aleady have these modules loaded
		if (isset($this->setModules[$position]))
		{
			$found = true;
		}
		else
		{
			// this is where you want to load your module position
			$modules = Joomla___f15d556d_33dd_4ee3_a0f7_0653e4a7a1e4___Power::getModules($position);
			if (Super___0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check($modules, true))
			{
				// set the place holder
				$this->setModules[$position] = [];
				foreach($modules as $module)
				{
					$this->setModules[$position][] = Joomla___f15d556d_33dd_4ee3_a0f7_0653e4a7a1e4___Power::renderModule($module);
				}
				$found = true;
			}
		}
		// check if modules were found
		if ($found && isset($this->setModules[$position]) && Super___0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check($this->setModules[$position]))
		{
			// set class
			if (Super___1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check($class))
			{
				$class = ' class="'.$class.'" ';
			}
			// set seperating return values
			switch($seperator)
			{
				case 'none':
					return implode('', $this->setModules[$position]);
					break;
				case 'div':
					return '<div'.$class.'>'.implode('</div><div'.$class.'>', $this->setModules[$position]).'</div>';
					break;
				case 'list':
					return '<ul'.$class.'><li>'.implode('</li><li>', $this->setModules[$position]).'</li></ul>';
					break;
				case 'array':
				case 'Array':
					return $this->setModules[$position];
					break;
				default:
					return implode('<br />', $this->setModules[$position]);
					break;
			}
		}
		return false;
	}
GEN;

	/**
	 * The targets that reach the header checker through the autoloader.
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
	 * Build the libraries loader of a target.
	 *
	 * @param   string          $version   Target namespace segment.
	 * @param   Registry|null   $registry  What the compiler knows about libraries.
	 * @param   LibraryManager  $manager   Which libraries a view is linked to.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function loader(string $version, ?Registry $registry = null,
		?LibraryManager $manager = null): object
	{
		return $this->renderer(
			$this->targetClass($version, 'View\\LibrariesLoader', ['JoomlaThree']),
			[
				'registry' => $registry ?: new Registry(),
				'librarymanager' => $manager ?: new LibraryManager(),
				'document' => (new ReflectionClass(Document::class))->newInstanceWithoutConstructor(),
			]
		);
	}

	/**
	 * Build a view definition.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(): array
	{
		$settings = new stdClass();
		$settings->code = 'demo';

		return ['settings' => $settings];
	}

	/**
	 * Every modern target reaches the header checker through the autoloader.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernTargetReachesTheHeaderCheckerThroughTheAutoloader(string $version): void
	{
		$view = $this->view();

		$this->assertSame(
			self::EXPECTED_LIBS_MODERN_ADMIN, $this->loader($version)->get($view)
		);
	}

	/**
	 * Joomla 3 requires the header checker from where the administrator build
	 * put it, and names the class after the component.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeRequiresTheAdministratorHeaderChecker(): void
	{
		$view = $this->view();

		$this->assertSame(
			self::EXPECTED_LIBS_J3_ADMIN, $this->loader('JoomlaThree')->get($view)
		);
	}

	/**
	 * Joomla 3 requires the header checker from where the site build put it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeRequiresTheSiteHeaderChecker(): void
	{
		$this->config()->set('build_target', 'site');
		$view = $this->view();

		$this->assertSame(
			self::EXPECTED_LIBS_J3_SITE, $this->loader('JoomlaThree')->get($view)
		);
	}

	/**
	 * A view linked to a library that carries its own statements is given them,
	 * written against the document a view reaches for.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testALinkedLibraryBringsItsOwnStatements(): void
	{
		$registry = new Registry();
		$library = new stdClass();
		$library->document = '$document->addScript("a.js");';
		$registry->set('builder.libraries.7', $library);

		$manager = new LibraryManager();
		$manager->set('admin.demo', ['7' => true]);

		$view = $this->view();

		$this->assertSame(
			self::EXPECTED_LIBS_WITH_LIBRARY,
			$this->loader('JoomlaSix', $registry, $manager)->get($view)
		);
	}

	/**
	 * A view that calls for no modules is given no loader, and the import it
	 * would have needed is cleared.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatCallsForNoModulesIsGivenNoLoader(): void
	{
		$multi = new ContentMulti();
		$subject = $this->renderer(GetModules::class, [
			'contentmulti' => $multi,
			'getmodule' => new GetModule(),
		]);
		$view = $this->view();

		$this->assertSame('', $subject->get($view, 'ADMIN'));
		$this->assertSame('', $multi->get('demo|ADMIN_GET_MODULE_JIMPORT'));
	}

	/**
	 * A view that calls for modules is given the loader and the import it needs.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatCallsForModulesIsGivenTheLoader(): void
	{
		$modules = new GetModule();
		$modules->set('admin.demo', true);

		$multi = new ContentMulti();
		$subject = $this->renderer(GetModules::class, [
			'contentmulti' => $multi,
			'getmodule' => $modules,
		]);
		$view = $this->view();

		$this->assertSame(self::EXPECTED_MODULES, $subject->get($view, 'ADMIN'));
		$this->assertSame(
			"\nuse Joomla\\CMS\\Helper\\ModuleHelper;",
			$multi->get('demo|ADMIN_GET_MODULE_JIMPORT')
		);
	}
}
