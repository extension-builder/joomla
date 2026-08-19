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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\ImageType;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\PostUpdateScript;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AssetsRules;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ExtensionsParams;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Component\AssetsTableInterface;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Component\ContentTypesInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;


/**
 * Generated install and update script contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedInstallScriptTest extends ArchitectureTestCase
{
	/**
	 * The install script a modern target writes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_INSTALL_MODERN = <<<'GEN'

			// Install the global extension assets permission.
			$this->setAssetsRules(
				'{"core.admin":{"6":1}}'
			);

			// Install the global extension params.
			$this->setExtensionsParams(
				'{"a":"b"}'
			);


			echo '<div style="background-color: #fff;" class="alert alert-info"><a target="_blank" href="https://example.test" title="Demo">
				<img src="components/com_demo/assets/images/vdm-component.jpg"/>
				</a></div>';
GEN;

	/**
	 * The install script Joomla 3 writes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_INSTALL_J3 = <<<'GEN'

			// Install the global extension assets permission.
			$db = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getDbo();
			$query = $db->getQuery(true);
			// Field to update.
			$fields = array(
				$db->quoteName('rules') . ' = ' . $db->quote('{"core.admin":{"6":1}}'),
			);
			// Condition.
			$conditions = array(
				$db->quoteName('name') . ' = ' . $db->quote('com_demo')
			);
			$query->update($db->quoteName('#__assets'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$allDone = $db->execute();

			// Install the global extension params.
			$query = $db->getQuery(true);
			// Field to update.
			$fields = array(
				$db->quoteName('params') . ' = ' . $db->quote('{"a":"b"}'),
			);
			// Condition.
			$conditions = array(
				$db->quoteName('element') . ' = ' . $db->quote('com_demo')
			);
			$query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$allDone = $db->execute();


			echo '<div style="background-color: #fff;" class="alert alert-info"><a target="_blank" href="https://example.test" title="Demo">
				<img src="components/com_demo/assets/images/vdm-component.jpg"/>
				</a></div>';
GEN;

	/**
	 * What a component with nothing to install is given, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_INSTALL_NOTHING = <<<'GEN'

			// noting to install.
GEN;

	/**
	 * The update script this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_UPDATE = <<<'GEN'


			echo '<div style="background-color: #fff;" class="alert alert-info"><a target="_blank" href="https://example.test" title="Demo">
				<img src="components/com_demo/assets/images/vdm-component.jpg"/>
				</a>
				<h3>Upgrade to Version  Was Successful! Let us know if anything is not working as expected.</h3></div>';
GEN;

	/**
	 * The targets whose install script hands the extension setup to a method.
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
	 * What the compiler knows about the component being installed.
	 *
	 * @param   bool  $withRules   Whether the extension has permissions to install.
	 * @param   bool  $withParams  Whether the extension has params to install.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function knowledge(bool $withRules = true, bool $withParams = true): array
	{
		$rules = new AssetsRules();
		if ($withRules)
		{
			$rules->set('site', ['"core.admin":{"6":1}']);
		}

		$params = new ExtensionsParams();
		if ($withParams)
		{
			$params->set('component', ['"a":"b"']);
		}

		$contentone = new ContentOne();
		$contentone->set('AUTHORWEBSITE', 'https://example.test');
		$contentone->set('Component_name', 'Demo');

		return [
			'assetsrules' => $rules,
			'extensionsparams' => $params,
			'contentone' => $contentone,
			'imagetype' => (new ReflectionClass(ImageType::class))->newInstanceWithoutConstructor(),
			'contenttypes' => $this->contentTypes(),
			'assetstable' => $this->assetsTable(),
		];
	}

	/**
	 * A content type writer that declares nothing of its own.
	 *
	 * @return  ContentTypesInterface
	 * @since   6.1.7
	 */
	private function contentTypes(): ContentTypesInterface
	{
		return new class implements ContentTypesInterface
		{
			/**
			 * Declare no content types.
			 *
			 * @param   string  $action  Whether the component installs or updates.
			 *
			 * @return  string
			 * @since   6.1.7
			 */
			public function get(string $action): string
			{
				return '';
			}

			/**
			 * Declare no content type for a view.
			 *
			 * @param   string  $view       The view.
			 * @param   string  $component  The component.
			 *
			 * @return  mixed
			 * @since   6.1.7
			 */
			public function contentType(string $view, string $component)
			{
				return null;
			}

			/**
			 * Declare no category content type for a view.
			 *
			 * @param   string  $view       The single view.
			 * @param   string  $views      The list view.
			 * @param   string  $component  The component.
			 *
			 * @return  array
			 * @since   6.1.7
			 */
			public function categoryContentType(string $view, string $views, string $component): array
			{
				return [];
			}
		};
	}

	/**
	 * An assets table writer that asks for nothing.
	 *
	 * @return  AssetsTableInterface
	 * @since   6.1.7
	 */
	private function assetsTable(): AssetsTableInterface
	{
		return new class implements AssetsTableInterface
		{
			/**
			 * Ask for nothing on install.
			 *
			 * @return  string
			 * @since   6.1.7
			 */
			public function install(): string
			{
				return '';
			}

			/**
			 * Ask for nothing on uninstall.
			 *
			 * @return  string
			 * @since   6.1.7
			 */
			public function uninstall(): string
			{
				return '';
			}
		};
	}

	/**
	 * A component that declares a view of its own.
	 *
	 * @return  Component
	 * @since   6.1.7
	 */
	private function componentWithViews(): Component
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));
		$component->set('admin_views', [['settings' => new stdClass()]]);

		return $component;
	}

	/**
	 * Build the install script writer of a target.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   array   $knowledge  What the compiler knows.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function installer(string $version, ?array $knowledge = null): object
	{
		return $this->renderer(
			$this->targetClass($version, 'Component\\PostInstallScript', ['JoomlaThree']),
			$knowledge ?? $this->knowledge()
		);
	}

	/**
	 * A modern install script hands the extension setup to a method of its own.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernInstallScriptHandsTheSetupToAMethod(string $version): void
	{
		$this->assertSame(self::EXPECTED_INSTALL_MODERN, $this->installer($version)->get());
	}

	/**
	 * A Joomla 3 install script writes the extension setup into the database
	 * itself, there being no method to hand it to.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAJoomlaThreeInstallScriptWritesItIntoTheDatabase(): void
	{
		$this->assertSame(self::EXPECTED_INSTALL_J3, $this->installer('JoomlaThree')->get());
	}

	/**
	 * A component with nothing to install says so rather than running an empty
	 * script.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAComponentWithNothingToInstallSaysSo(string $version): void
	{
		$this->assertSame(
			self::EXPECTED_INSTALL_NOTHING,
			$this->installer($version, $this->knowledge(false, false))->get()
		);
	}

	/**
	 * A component with views of its own says who built it when it updates.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithViewsSaysWhoBuiltItOnUpdate(): void
	{
		$knowledge = $this->knowledge();
		$component = $this->componentWithViews();

		$subject = $this->renderer(PostUpdateScript::class, [
			'contentone' => $knowledge['contentone'],
			'component' => $component,
			'imagetype' => $knowledge['imagetype'],
			'contenttypes' => $knowledge['contenttypes'],
		]);

		$this->assertSame(self::EXPECTED_UPDATE, $subject->get());
	}

	/**
	 * A component with no views of its own says there was nothing to update,
	 * rather than running an empty script.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithoutViewsSaysThereWasNothingToUpdate(): void
	{
		$knowledge = $this->knowledge();

		$subject = $this->renderer(PostUpdateScript::class, [
			'contentone' => $knowledge['contentone'],
			'imagetype' => $knowledge['imagetype'],
			'contenttypes' => $knowledge['contenttypes'],
		]);

		$this->assertStringContainsString('noting to update.', $subject->get());
	}
}
