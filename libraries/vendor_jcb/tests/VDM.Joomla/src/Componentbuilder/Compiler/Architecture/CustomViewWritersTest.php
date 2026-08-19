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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\CustomView\Layouts;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\CustomView\TemplateBody;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\LayoutData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\TemplateData;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HeaderInterface;
use VDM\Joomla\Componentbuilder\Compiler\Model\Createdate;
use VDM\Joomla\Componentbuilder\Compiler\Model\Modifieddate;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;


/**
 * Generated custom view template and layout writing contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class CustomViewWritersTest extends ArchitectureTestCase
{
	/**
	 * What the compiler was told to write.
	 *
	 * @var    ContentMulti
	 * @since  6.1.7
	 */
	private ContentMulti $content;

	/**
	 * What the structure was asked to build.
	 *
	 * @var    array
	 * @since  6.1.7
	 */
	private array $built = [];

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
	 * A header writer that only says it was asked.
	 *
	 * @return  HeaderInterface
	 * @since   6.1.7
	 */
	private function header(): HeaderInterface
	{
		return new class implements HeaderInterface
		{
			/**
			 * Say the headers were asked for.
			 *
			 * @param   string       $context  What is being built.
			 * @param   mixed        $id       Which one.
			 * @param   string|null  $target   Which side of the component.
			 *
			 * @return  string
			 * @since   6.1.7
			 */
			public function get(string $context, $id = null, ?string $target = null): string
			{
				return 'HEADERS';
			}

			/**
			 * Take note of the headers asked for.
			 *
			 * @param   string       $context  What is being built.
			 * @param   mixed        $id       Which one.
			 * @param   string|null  $target   Which side of the component.
			 *
			 * @return  void
			 * @since   6.1.7
			 */
			public function set(string $context, $id = null, ?string $target = null): void
			{
			}
		};
	}

	/**
	 * Build the template writer.
	 *
	 * @param   TemplateData  $templates  What the compiler collected.
	 *
	 * @return  TemplateBody
	 * @since   6.1.7
	 */
	private function templateWriter(TemplateData $templates): TemplateBody
	{
		$this->content = new ContentMulti();
		$this->built = [];

		$created = $this->createStub(Createdate::class);
		$created->method('get')->willReturn('made on a day');
		$modified = $this->createStub(Modifieddate::class);
		$modified->method('get')->willReturn('changed on a day');

		return $this->renderer(TemplateBody::class, [
			'contentmulti' => $this->content,
			'templatedata' => $templates,
			'structure' => $this->structure(),
			'createdate' => $created,
			'modifieddate' => $modified,
		]);
	}

	/**
	 * Build the layout writer.
	 *
	 * @param   LayoutData  $layouts  What the compiler collected.
	 *
	 * @return  Layouts
	 * @since   6.1.7
	 */
	private function layoutWriter(LayoutData $layouts): Layouts
	{
		$this->content = new ContentMulti();
		$this->built = [];

		return $this->renderer(Layouts::class, [
			'contentmulti' => $this->content,
			'layoutdata' => $layouts,
			'structure' => $this->structure(),
			'header' => $this->header(),
		]);
	}

	/**
	 * Build a custom view definition.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(): array
	{
		$settings = new stdClass();
		$settings->code = 'demo';
		$settings->version = '1.0.1';

		return ['settings' => $settings];
	}

	/**
	 * A view drawn with no templates has none written.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithNoTemplatesHasNoneWritten(): void
	{
		$writer = $this->templateWriter(new TemplateData());
		$view = $this->view();

		$writer->set($view);

		$this->assertSame([], $this->built);
		$this->assertSame([], $this->content->allActive());
	}

	/**
	 * Every template a view was drawn with is written into the component, with
	 * the dates and the version of the view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryTemplateOfAViewIsWritten(): void
	{
		$templates = new TemplateData();
		$templates->set('admin.demo.first', ['php_view' => 'echo 1;', 'html' => '<p>hi</p>']);

		$writer = $this->templateWriter($templates);
		$view = $this->view();

		$writer->set($view);

		$this->assertCount(1, $this->built);
		$this->assertSame(['admin' => 'demo'], $this->built[0][0]);
		$this->assertSame('template', $this->built[0][1]);
		$this->assertSame('first', $this->built[0][2]);
		$this->assertTrue($this->content->exists('demo_first|ADMIN_TEMPLATE_BODY'));
	}

	/**
	 * The php a template was drawn with reaches the file it belongs to.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testThePhpOfATemplateReachesItsFile(): void
	{
		$templates = new TemplateData();
		$templates->set('admin.demo.first', ['php_view' => 'echo 1;', 'html' => '<p>hi</p>']);

		$writer = $this->templateWriter($templates);
		$view = $this->view();

		$writer->set($view);

		$this->assertStringContainsString(
			'echo 1;', (string) $this->content->get('demo_first|ADMIN_TEMPLATE_CODE_BODY')
		);
	}

	/**
	 * A build target with no layouts has none written.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATargetWithNoLayoutsHasNoneWritten(): void
	{
		$writer = $this->layoutWriter(new LayoutData());

		$writer->set();

		$this->assertSame([], $this->built);
		$this->assertSame([], $this->content->allActive());
	}

	/**
	 * Every layout the target collected is written into the component.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryLayoutOfTheTargetIsWritten(): void
	{
		$layouts = new LayoutData();
		$layouts->set('admin.one', ['php_view' => 'echo 2;', 'html' => '<p>there</p>']);

		$writer = $this->layoutWriter($layouts);

		$writer->set();

		$this->assertCount(1, $this->built);
		$this->assertSame(['admin' => 'one'], $this->built[0][0]);
		$this->assertSame('layout', $this->built[0][1]);
		$this->assertTrue($this->content->exists('one|ADMIN_LAYOUT_BODY'));
		$this->assertStringContainsString(
			'echo 2;', (string) $this->content->get('one|ADMIN_LAYOUT_CODE')
		);
	}
}
