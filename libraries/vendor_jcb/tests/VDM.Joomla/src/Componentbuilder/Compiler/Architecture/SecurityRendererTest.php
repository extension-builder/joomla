<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    15th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use Joomla\CMS\Application\CMSApplicationInterface;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\ComHelperClass\CryptKey;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\LicenseLock;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\Whmcs;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelBasicField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelMediumField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelWhmcsField;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Unique;


/**
 * Generated license-lock, WHMCS, and crypt-key security contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class SecurityRendererTest extends ArchitectureTestCase
{
	/**
	 * Unique-key state restored after each test.
	 *
	 * @var    array<int, array<int, string>>
	 * @since  6.1.7
	 */
	private array $uniqueState = [];

	/**
	 * Reset the process-static unique-key sequence for determinism.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$reflection = new ReflectionProperty(Unique::class, 'unique');
		$this->uniqueState = $reflection->getValue();
		$reflection->setValue(null, []);
	}

	/**
	 * Restore the process-static unique-key sequence.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function tearDown(): void
	{
		try
		{
			(new ReflectionProperty(Unique::class, 'unique'))
				->setValue(null, $this->uniqueState);
		}
		finally
		{
			parent::tearDown();
		}
	}

	/**
	 * Global license-lock content is generated once and guarded thereafter.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testLicenseLockStoresGlobalLockContentOnce(): void
	{
		$component = $this->component(['add_license' => 3]);
		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');
		$contentmulti = new ContentMulti();

		$subject = new LicenseLock($this->config(), $component, $contentone, $contentmulti);
		$subject->set();

		$helper = $contentone->get('HELPER_LICENSE_LOCK');

		$this->assertSame($helper, $contentone->get('HELPER_SITE_LICENSE_LOCK'));
		$this->assertStringStartsWith(PHP_EOL . PHP_EOL . "\t/**", $helper);
		$this->assertStringContainsString("\tpublic static function isGenuine()", $helper);
		$this->assertStringContainsString(
			"\$params = ComponentHelper::getParams('com_demo', true);",
			$helper
		);
		$this->assertStringContainsString('$the = new \WHMCS($whmcs_key);', $helper);

		$this->assertSame(
			PHP_EOL . "if (!defined('_VVVVVVVVVV'))" . PHP_EOL
			. "{" . PHP_EOL
			. "\t\$allow = DemoHelper::isGenuine();" . PHP_EOL
			. "\tif (\$allow)" . PHP_EOL
			. "\t{" . PHP_EOL
			. "\t\tdefine('_VVVVVVVVVV', 1);" . PHP_EOL
			. "\t}" . PHP_EOL
			. "}",
			$contentone->get('LICENSE_LOCKED_INT')
		);
		$this->assertSame(
			PHP_EOL . PHP_EOL . "defined('_VVVVVVVVVV') or die(Joomla__"
			. "_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('NIE_REG_NIE'));",
			$contentone->get('LICENSE_LOCKED_DEFINED')
		);

		// a second run must not regenerate the lock constant
		$subject->set();

		$this->assertStringContainsString(
			"_VVVVVVVVVV",
			(string) $contentone->get('LICENSE_LOCKED_DEFINED')
		);
	}

	/**
	 * Without the license option every global lock placeholder is cleared.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testLicenseLockClearsGlobalLockContentWithoutLicense(): void
	{
		$contentone = new ContentOne();

		$subject = new LicenseLock(
			$this->config(),
			$this->component(['add_license' => 0]),
			$contentone,
			new ContentMulti()
		);
		$subject->set();

		$this->assertSame('', $contentone->get('HELPER_SITE_LICENSE_LOCK'));
		$this->assertSame('', $contentone->get('HELPER_LICENSE_LOCK'));
		$this->assertSame('', $contentone->get('LICENSE_LOCKED_INT'));
		$this->assertSame('', $contentone->get('LICENSE_LOCKED_DEFINED'));
	}

	/**
	 * Per-view lock methods are generated once per view with paired names.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testLicenseLockStoresPerViewLockOnce(): void
	{
		$contentmulti = new ContentMulti();

		$subject = new LicenseLock(
			$this->config(),
			$this->component(['add_license' => 3]),
			new ContentOne(),
			$contentmulti
		);
		$subject->setView('article');

		$this->assertSame('getVvv', $contentmulti->get('article|BOOLMETHOD'));

		$bool = $contentmulti->get('article|LICENSE_LOCKED_SET_BOOL');

		$this->assertStringContainsString("\tprivate \$setVvw;", $bool);
		$this->assertStringContainsString("\tpublic function getVvv()", $bool);
		$this->assertStringContainsString(
			"JLoader::import( 'whmcs', JPATH_ADMINISTRATOR .'/components/com_demo');",
			$bool
		);

		$this->assertSame(
			PHP_EOL . "\t\tif (!\$this->getVvv())" . PHP_EOL
			. "\t\t{" . PHP_EOL
			. "\t\t\t\$app = Joomla__" . "_39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication();" . PHP_EOL
			. "\t\t\t\$app->enqueueMessage(Text::_('NIE_REG_NIE'), 'error');" . PHP_EOL
			. "\t\t\t\$app->redirect('index.php');" . PHP_EOL
			. "\t\t\treturn false;" . PHP_EOL
			. "\t\t}",
			$contentmulti->get('article|LICENSE_LOCKED_CHECK')
		);
		$this->assertStringContainsString(
			"if (!\$table->getVvv())",
			(string) $contentmulti->get('article|LICENSE_TABLE_LOCKED_CHECK')
		);

		// a second run must keep the first generated method name
		$subject->setView('article');

		$this->assertSame('getVvv', $contentmulti->get('article|BOOLMETHOD'));
	}

	/**
	 * Without the license option the per-view lock placeholders are cleared.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testLicenseLockClearsPerViewLockWithoutLicense(): void
	{
		$contentmulti = new ContentMulti();

		$subject = new LicenseLock(
			$this->config(),
			$this->component(['add_license' => 0]),
			new ContentOne(),
			$contentmulti
		);
		$subject->setView('article');

		$this->assertSame('', $contentmulti->get('article|LICENSE_LOCKED_SET_BOOL'));
		$this->assertSame('', $contentmulti->get('article|LICENSE_LOCKED_CHECK'));
		$this->assertSame('', $contentmulti->get('article|LICENSE_TABLE_LOCKED_CHECK'));
		$this->assertNull($contentmulti->get('article|BOOLMETHOD'));
	}

	/**
	 * A missing WHMCS key enqueues error notices and returns the comment fallback.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWhmcsFallsBackWithNoticesWithoutStoredKey(): void
	{
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->exactly(2))
			->method('enqueueMessage')
			->with($this->isString(), 'Error');

		$subject = new Whmcs($this->component([]), $app);
		$code = $subject->get();

		$this->assertStringStartsWith('//', $code);
		$this->assertStringContainsString(
			'The WHMCS class could not be added to this component.',
			$code
		);
		$this->assertStringContainsString(
			'(Add WHMCS)->Yes.',
			$code
		);
	}

	/**
	 * Without any active encryption model the helper method set is empty.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCryptKeyIsEmptyWhenNoEncryptionIsActive(): void
	{
		$contentone = new ContentOne();
		$structure = $this->structureNeverBuilding();

		$subject = $this->cryptKey($this->component([]), $contentone, new ContentMulti(), $structure);

		$this->assertSame('', $subject->get());
		$this->assertSame('', $contentone->get('WHMCS_ENCRYPT_FILE'));
	}

	/**
	 * An active basic model generates only the basic key lookup.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCryptKeyGeneratesBasicKeyLookup(): void
	{
		$contentone = new ContentOne();
		$basic = new ModelBasicField();
		$basic->set('article.password', true);

		$subject = $this->cryptKey(
			$this->component([]),
			$contentone,
			new ContentMulti(),
			$this->structureNeverBuilding(),
			$basic
		);
		$code = $subject->get();

		$this->assertStringContainsString(
			"public static function getCryptKey(\$type, \$default = false)",
			$code
		);
		$this->assertStringContainsString("if ('basic' === \$type)", $code);
		$this->assertStringContainsString(
			'Super_' . '__1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check($basic_key)',
			$code
		);
		$this->assertStringNotContainsString('medium', $code);
		$this->assertStringNotContainsString('getMediumCryptKey', $code);
		$this->assertSame('', $contentone->get('WHMCS_ENCRYPT_FILE'));
	}

	/**
	 * An active medium model generates the medium key machinery.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCryptKeyGeneratesMediumKeyMachinery(): void
	{
		$medium = new ModelMediumField();
		$medium->set('article.secret', true);

		$subject = $this->cryptKey(
			$this->component([]),
			new ContentOne(),
			new ContentMulti(),
			$this->structureNeverBuilding(),
			null,
			$medium
		);
		$code = $subject->get();

		$this->assertStringContainsString("if ('medium' === \$type)", $code);
		$this->assertStringContainsString(
			"protected static \$mediumCryptKey = false;",
			$code
		);
		$this->assertStringContainsString(
			"public static function getMediumCryptKey(\$path)",
			$code
		);
		$this->assertStringContainsString(
			"COM_DEMO_CONFIG_MEDIUM_KEY_PATH_ERROR",
			$code
		);
		$this->assertStringNotContainsString("if ('basic' === \$type)", $code);
	}

	/**
	 * The license option builds the WHMCS file and manifest entry.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCryptKeyBuildsWhmcsFileWhenLicensed(): void
	{
		$contentone = new ContentOne();
		$contentmulti = new ContentMulti();
		$component = $this->component(['add_license' => 1]);

		$structure = $this->getMockBuilder(Structure::class)
			->disableOriginalConstructor()
			->onlyMethods(['build'])
			->getMock();
		$structure->expects($this->once())
			->method('build')
			->with(['admin' => 'whmcs'], 'whmcs')
			->willReturn(true);

		$subject = $this->cryptKey($component, $contentone, $contentmulti, $structure);
		$code = $subject->get();

		$this->assertStringContainsString(
			"public static function getCryptKey(\$type, \$default = false)",
			$code
		);
		$this->assertSame(
			PHP_EOL . "\t\t\t<filename>whmcs.php</filename>",
			$contentone->get('WHMCS_ENCRYPT_FILE')
		);
		$this->assertStringContainsString(
			'The WHMCS class could not be added to this component.',
			(string) $contentmulti->get('whmcs|WHMCS_ENCRYPTION_BODY')
		);
	}

	/**
	 * Create a compiler Component registry seeded with values.
	 *
	 * @param   array<string, mixed>  $values  Component values to set.
	 *
	 * @return  Component
	 * @since   6.1.7
	 */
	private function component(array $values): Component
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));

		foreach ($values as $key => $value)
		{
			$component->set($key, $value);
		}

		return $component;
	}

	/**
	 * Create the crypt-key renderer with real registries and a silent WHMCS app.
	 *
	 * @param   Component              $component     The component registry.
	 * @param   ContentOne             $contentone    The global content registry.
	 * @param   ContentMulti           $contentmulti  The contextual content registry.
	 * @param   Structure              $structure     The structure double.
	 * @param   ModelBasicField|null   $basic         The basic-field registry.
	 * @param   ModelMediumField|null  $medium        The medium-field registry.
	 *
	 * @return  CryptKey
	 * @since   6.1.7
	 */
	private function cryptKey(
		Component $component,
		ContentOne $contentone,
		ContentMulti $contentmulti,
		Structure $structure,
		?ModelBasicField $basic = null,
		?ModelMediumField $medium = null
	): CryptKey
	{
		return new CryptKey(
			$this->config(),
			$component,
			$contentone,
			$contentmulti,
			$basic ?? new ModelBasicField(),
			$medium ?? new ModelMediumField(),
			new ModelWhmcsField(),
			$structure,
			new Whmcs($component, $this->createStub(CMSApplicationInterface::class))
		);
	}

	/**
	 * Create a structure double that must never build a file.
	 *
	 * @return  Structure
	 * @since   6.1.7
	 */
	private function structureNeverBuilding(): Structure
	{
		$structure = $this->getMockBuilder(Structure::class)
			->disableOriginalConstructor()
			->onlyMethods(['build'])
			->getMock();
		$structure->expects($this->never())->method('build');

		return $structure;
	}
}
