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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Customcode;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry as JoomlaRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\External;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder\Reverse;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Tests\Support\ExternalFixture;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * External-code slicing and editable-GUI marker contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(External::class)]
#[CoversClass(Gui::class)]
#[UsesClass(Placeholder::class)]
final class ExternalAndGuiTest extends JoomlaTestCase
{
	/**
	 * Cut the requested top and bottom rows without disturbing the retained order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExternalSliceAppliesBothEndsAndTracksNoFetchedEntry(): void
	{
		$this->installApplication();
		$config = $this->config();
		$subject = new ExternalFixture(
			new Placeholder($config),
			$this->createStub(DatabaseInterface::class)
		);

		$this->assertSame(
			"line 2\nline 3\nline 4",
			$subject->slice("line 1\nline 2\nline 3\nline 4\nline 5", '1|1', '[EXTERNALCODE=test]')
		);
		$this->assertSame(0, $subject->count());
	}

	/**
	 * Wrap eligible PHP in the exact GUI markers used by IDE round trips.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGuiSetEmitsExactPhpMarkersForAStoredField(): void
	{
		$this->installApplication();
		$config = $this->config();
		$config->set('add_placeholders', true);
		$reverse = (new ReflectionClass(Reverse::class))->newInstanceWithoutConstructor();
		$parser = (new ReflectionClass(Parser::class))->newInstanceWithoutConstructor();
		$subject = new Gui(
			$config,
			$reverse,
			$parser,
			$this->createStub(DatabaseInterface::class)
		);

		$this->assertSame(
			"\n/***[JCBGUI.admin_view.php_model.42.$$$$]***/\necho 42;/***[/JCBGUI$$$$]***/\n",
			$subject->set('echo 42;', [
				'table' => 'admin_view',
				'field' => 'php_model',
				'type' => 'php',
				'id' => 42,
			])
		);
	}

	/**
	 * Existing JCB marker content is never nested inside another GUI wrapper.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGuiSetLeavesAlreadyMarkedCodeUnwrappedButHonorsItsPrefix(): void
	{
		$this->installApplication();
		$config = $this->config();
		$config->set('add_placeholders', true);
		$subject = new Gui(
			$config,
			(new ReflectionClass(Reverse::class))->newInstanceWithoutConstructor(),
			(new ReflectionClass(Parser::class))->newInstanceWithoutConstructor(),
			$this->createStub(DatabaseInterface::class)
		);
		$source = '/***[JCBGUI.demo.field.1.$$$$]***/code';

		$this->assertSame('// prefix' . $source, $subject->set($source, [
			'table' => 'demo',
			'field' => 'field',
			'type' => 'php',
			'id' => 1,
			'prefix' => '// prefix',
		]));
	}

	/**
	 * Install an application identity required by both legacy services.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function installApplication(): void
	{
		$application = $this->createStub(CMSApplicationInterface::class);
		$application->method('getIdentity')->willReturn($this->createStub(User::class));
		$this->setJoomlaApplication($application);
	}

	/**
	 * Create an isolated compiler configuration.
	 *
	 * @return  Config
	 * @since   6.1.6
	 */
	private function config(): Config
	{
		return new Config(new Input(), new JoomlaRegistry(), new JoomlaRegistry());
	}
}
