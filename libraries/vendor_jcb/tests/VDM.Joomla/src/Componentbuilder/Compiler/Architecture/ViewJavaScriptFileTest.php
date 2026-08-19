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
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\JavaScriptFile;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Library\IncludeHelper;
use VDM\Joomla\Componentbuilder\Compiler\Model\Createdate;
use VDM\Joomla\Componentbuilder\Compiler\Model\Modifieddate;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;


/**
 * Generated view script file contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ViewJavaScriptFileTest extends ArchitectureTestCase
{
	/**
	 * What the structure was asked to build.
	 *
	 * @var    array
	 * @since  6.1.7
	 */
	private array $built = [];

	/**
	 * What the compiler was told to write into the view.
	 *
	 * @var    ContentMulti
	 * @since  6.1.7
	 */
	private ContentMulti $content;

	/**
	 * Build the script file writer.
	 *
	 * @return  JavaScriptFile
	 * @since   6.1.7
	 */
	private function subject(): JavaScriptFile
	{
		$this->built = [];
		$this->content = new ContentMulti();

		$created = $this->createStub(Createdate::class);
		$created->method('get')->willReturn('made on a day');
		$modified = $this->createStub(Modifieddate::class);
		$modified->method('get')->willReturn('changed on a day');

		return $this->renderer(JavaScriptFile::class, [
			'contentmulti' => $this->content,
			'structure' => $this->structure(),
			'createdate' => $created,
			'modifieddate' => $modified,
			'includehelper' => new IncludeHelper(),
		]);
	}

	/**
	 * A structure that only records what it was asked to build.
	 *
	 * @return  Structure
	 * @since   6.1.7
	 */
	private function structure(): Structure
	{
		$test = $this;

		return new class($test) extends Structure
		{
			/**
			 * The test to record against.
			 *
			 * @var    object
			 * @since  6.1.7
			 */
			private object $test;

			/**
			 * Constructor.
			 *
			 * @param   object  $test  The test to record against.
			 *
			 * @since   6.1.7
			 */
			public function __construct(object $test)
			{
				$this->test = $test;
			}

			/**
			 * Record what was asked for.
			 *
			 * @param   array        $target    What is being built.
			 * @param   string       $type      The kind of file.
			 * @param   string|null  $fileName  What to call it.
			 * @param   array|null   $config    What to fill it with.
			 *
			 * @return  bool
			 * @since   6.1.7
			 */
			public function build(array $target, string $type,
				?string $fileName = null, ?array $config = null): bool
			{
				$this->test->record([$target, $type, $fileName, $config]);

				return true;
			}
		};
	}

	/**
	 * Record what the structure was asked to build.
	 *
	 * @param   array  $call  What it was asked for.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function record(array $call): void
	{
		$this->built[] = $call;
	}

	/**
	 * Build a view definition.
	 *
	 * @param   int     $on      Whether the view asks for a script file.
	 * @param   string  $script  What the view declared.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(int $on, string $script): array
	{
		$settings = new stdClass();
		$settings->code = 'demo';
		$settings->version = '1.0.1';
		$settings->add_javascript_file = $on;
		$settings->javascript_file = $script;

		return ['settings' => $settings];
	}

	/**
	 * A view that asked for no script file gets none, and nothing is written.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatAsksForNoScriptFileGetsNone(): void
	{
		$subject = $this->subject();
		$view = $this->view(0, 'alert(1);');

		$this->assertSame('', $subject->get($view, 'ADMIN'));
		$this->assertSame([], $this->built);
	}

	/**
	 * A view that asked for a script file and declared nothing gets none.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatDeclaredNoScriptGetsNoFile(): void
	{
		$subject = $this->subject();
		$view = $this->view(1, '');

		$this->assertSame('', $subject->get($view, 'ADMIN'));
		$this->assertSame([], $this->built);
	}

	/**
	 * The script a view declared is written into the file the view includes.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheDeclaredScriptIsWrittenIntoTheFile(): void
	{
		$subject = $this->subject();
		$view = $this->view(1, 'alert(1);');

		$subject->get($view, 'ADMIN');

		$this->assertSame(
			'alert(1);', $this->content->get('demo|ADMIN_JAVASCRIPT_FILE')
		);
	}

	/**
	 * The file is built for the target being compiled, and carries the dates
	 * and the version of the view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheFileIsBuiltWithTheDatesAndVersionOfTheView(): void
	{
		$subject = $this->subject();
		$view = $this->view(1, 'alert(1);');

		$subject->get($view, 'ADMIN');

		$this->assertCount(1, $this->built);
		$this->assertSame(['admin' => 'demo'], $this->built[0][0]);
		$this->assertSame('javascript_file', $this->built[0][1]);
		$this->assertSame([
			Placefix::_h('CREATIONDATE') => 'made on a day',
			Placefix::_h('BUILDDATE') => 'changed on a day',
			Placefix::_h('VERSION') => '1.0.1',
		], $this->built[0][3]);
	}

	/**
	 * An administrator view includes the file from the administrator assets.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAdministratorViewIncludesTheAdministratorFile(): void
	{
		$subject = $this->subject();
		$view = $this->view(1, 'alert(1);');

		$this->assertStringContainsString(
			"'administrator/components/com_demo/assets/js/demo.js'",
			$subject->get($view, 'ADMIN')
		);
	}

	/**
	 * A site view includes the file from the site assets.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASiteViewIncludesTheSiteFile(): void
	{
		$this->config()->set('build_target', 'site');
		$subject = $this->subject();
		$view = $this->view(1, 'alert(1);');

		$this->assertStringContainsString(
			"'components/com_demo/assets/js/demo.js'",
			$subject->get($view, 'SITE')
		);
	}
}
