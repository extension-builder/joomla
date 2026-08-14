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

namespace VDM\Joomla\Tests\Utilities\Component;


use InvalidArgumentException;
use Joomla\Registry\Registry;
use Joomla\Input\Input;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Joomla\Utilities\String\NamespaceHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\JoomlaTestCase;
use VDM\Tests\Support\LegacyComponentHelperFixture;


/**
 * Component metadata, cache, and compatibility-dispatch tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Helper::class)]
#[UsesClass(NamespaceHelper::class)]
#[UsesClass(StringHelper::class)]
final class HelperTest extends JoomlaTestCase
{
	/**
	 * Original helper state.
	 *
	 * @var    array{option: string|null, manifest: array, params: array}
	 * @since  6.1.6
	 */
	private array $helperState = [];

	/**
	 * Snapshot and isolate process-static component metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$reflection = new ReflectionClass(Helper::class);
		$this->helperState = [
			'option' => Helper::$option,
			'manifest' => Helper::$manifest,
			'params' => $reflection->getProperty('params')->getValue()
		];

		Helper::$option = null;
		Helper::$manifest = [];
		$reflection->getProperty('params')->setValue(null, []);
	}

	/**
	 * Restore process-static component metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		$reflection = new ReflectionClass(Helper::class);
		Helper::$option = $this->helperState['option'];
		Helper::$manifest = $this->helperState['manifest'];
		$reflection->getProperty('params')->setValue(null, $this->helperState['params']);

		parent::tearDown();
	}

	/**
	 * Preserve explicit options and derive component code names.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExplicitOptionDrivesCodeResolution(): void
	{
		Helper::setOption('com_ExampleComponent');

		$this->assertSame('com_ExampleComponent', Helper::getOption());
		$this->assertSame('examplecomponent', Helper::getCode());
		$this->assertSame('fallback', Helper::getCode('org_example', 'fallback'));

		Helper::setOption(null);
		$application = new class
		{
			/**
			 * Return deterministic request input.
			 *
			 * @return  Input
			 * @since   6.1.6
			 */
			public function getInput(): Input
			{
				return new Input(['option' => 'fallback-option']);
			}
		};
		$this->setJoomlaFactoryProperty('application', $application);

		$this->assertSame('fallback-option', Helper::getOption('fallback-option'));
	}

	/**
	 * Return the exact cached registry and avoid a database update for an unchanged value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCachedParametersPreserveIdentityAndUnchangedSetValue(): void
	{
		$params = new Registry(['channel' => 'stable']);
		$reflection = new ReflectionClass(Helper::class);
		$reflection->getProperty('params')->setValue(null, ['com_example' => $params]);

		$this->assertSame($params, Helper::getParams('com_example'));
		$this->assertSame('stable', Helper::setParams('channel', 'stable', 'com_example'));
		$this->assertSame('stable', $params->get('channel'));
	}

	/**
	 * Read a cached manifest and expose its namespace without a database lookup.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCachedManifestProvidesNamespace(): void
	{
		$manifest = (object) [
			'namespace' => 'Vendor\\Component\\Example',
			'version' => '1.2.3'
		];
		Helper::$manifest['com_example'] = $manifest;

		$this->assertSame($manifest, Helper::getManifest('com_example'));
		$this->assertSame('Vendor\\Component\\Example', Helper::getNamespace('com_example'));
	}

	/**
	 * Resolve and call a legacy global helper while rejecting missing methods.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLegacyHelperDispatchForwardsArguments(): void
	{
		if (!class_exists('ExamplefixtureHelper', false))
		{
			class_alias(LegacyComponentHelperFixture::class, 'ExamplefixtureHelper');
		}

		$this->assertSame('\\ExamplefixtureHelper', Helper::get('com_examplefixture'));
		$this->assertTrue(Helper::methodExists('combine', 'com_examplefixture'));
		$this->assertFalse(Helper::methodExists('missing', 'com_examplefixture'));
		$this->assertSame(
			'alpha:beta',
			Helper::_('combine', ['alpha', 'beta'], 'com_examplefixture')
		);
		$this->assertNull(Helper::_('missing', [], 'com_examplefixture'));
	}

	/**
	 * Reject an empty model name before consulting Joomla application state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetModelRejectsEmptyType(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('$type parameter cannot be empty');

		Helper::getModel('');
	}
}
