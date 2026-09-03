<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    3rd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Reader\Php;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Php\Template;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * What a screen shows, separated from how it prepares to say it.
 *
 * @since 6.2.0
 */
#[CoversClass(Template::class)]
final class TemplateTest extends FilesystemTestCase
{
	/**
	 * The reader under test.
	 *
	 * @var    Template
	 * @since  6.2.0
	 */
	private Template $template;

	/**
	 * Start every test from a fresh reader.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->template = new Template();
	}

	/**
	 * What a screen shows begins after the file stops preparing to show it.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTheBodyBeginsAfterTheFirstClosingTag(): void
	{
		$body = $this->template->body(
			"<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Language\\Text;\n\n?>\n"
			. "<div class=\"showroom\">\n\t<h1>The showroom</h1>\n</div>\n"
		);

		$this->assertSame("<div class=\"showroom\">\n\t<h1>The showroom</h1>\n</div>", $body);
		$this->assertStringNotContainsString('_JEXEC', $body, 'The guard is how it prepares, not what it shows.');
		$this->assertStringNotContainsString('use Joomla', $body);
	}

	/**
	 * A template that opens and closes PHP again keeps everything after the first close.
	 *
	 * A view's markup is full of PHP -- a loop, an escape, a conditional -- and
	 * all of it belongs to what the screen shows. Only the block the file opens
	 * with is the preparation.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testEverythingAfterTheFirstCloseIsTheBody(): void
	{
		$body = $this->template->body(
			"<?php\ndefined('_JEXEC') or die;\n?>\n<ul>\n"
			. "<?php foreach (\$this->items as \$item) : ?>\n"
			. "\t<li><?php echo \$item->name; ?></li>\n"
			. "<?php endforeach; ?>\n</ul>\n"
		);

		$this->assertStringStartsWith('<ul>', $body);
		$this->assertStringContainsString('foreach ($this->items', $body);
		$this->assertStringEndsWith('</ul>', $body);
	}

	/**
	 * A file that is all markup is all body.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAFileWithoutPhpIsAllBody(): void
	{
		$this->assertSame('<p>Plain</p>', $this->template->body("<p>Plain</p>\n"));
	}

	/**
	 * A file that never stops preparing shows nothing of its own.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAFileThatNeverClosesShowsNothing(): void
	{
		$this->assertSame(
			'',
			$this->template->body("<?php\ndefined('_JEXEC') or die;\n\$this->doWork();\n"),
			'There is no markup in it, so there is no body to take.'
		);
	}

	/**
	 * A template is read off disk, and a path that names no file reads as nothing.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testATemplateIsReadOffDiskAndAMissingOneIsNull(): void
	{
		$path = $this->writeTemporaryFile(
			'com_example/tmpl/showroom/default.php',
			"<?php\ndefined('_JEXEC') or die;\n?>\n<h1>Showroom</h1>\n"
		);

		$this->assertSame('<h1>Showroom</h1>', $this->template->read($path));
		$this->assertNull($this->template->read($path . '.missing'));
		$this->assertNull($this->template->read(''));
		$this->assertNull(
			$this->template->read(dirname($path)),
			'A folder is not a template.'
		);
	}
}
