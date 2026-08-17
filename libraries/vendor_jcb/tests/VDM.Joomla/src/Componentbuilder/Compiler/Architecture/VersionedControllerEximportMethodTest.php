<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    18th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\EximportView;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ImportCustomScripts;


/**
 * Generated admin list controller export and import contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedControllerEximportMethodTest extends ArchitectureTestCase
{
	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.7
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => ['JoomlaThree', 3],
			'Joomla 4' => ['JoomlaFour', 4],
			'Joomla 5' => ['JoomlaFive', 5],
			'Joomla 6' => ['JoomlaSix', 6],
		];
	}

	/**
	 * Each target puts the current user in scope its own way.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheUserLookupFollowsTheTarget(string $version, int $major): void
	{
		$code = $this->method($version);

		if ($major === 3)
		{
			$this->assertSame(2, substr_count($code, '___Power::getUser();'));
			$this->assertStringNotContainsString(
				'___Power::getApplication()->getIdentity();', $code
			);

			return;
		}

		$this->assertSame(2, substr_count(
			$code, '$user = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->getIdentity();'
		));
		$this->assertStringNotContainsString('___Power::getUser();', $code);
	}

	/**
	 * A list view without the export feature builds nothing at all.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewWithoutTheFeatureBuildsNothing(string $version,
		int $major): void
	{
		$this->assertSame('', $this->method($version, false, false));
	}

	/**
	 * Both methods are declared, in order, on the controller.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testBothMethodsAreDeclared(): void
	{
		$code = $this->method('JoomlaSix');

		$this->assertStringStartsWith(
			PHP_EOL . PHP_EOL . "\tpublic function exportData()", $code
		);
		$this->assertStringContainsString(
			PHP_EOL . PHP_EOL . "\tpublic function importData()", $code
		);
		$this->assertLessThan(
			strpos($code, 'public function importData()'),
			strpos($code, 'public function exportData()')
		);
	}

	/**
	 * Neither method runs before the request token is checked.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testBothMethodsCheckTheRequestToken(): void
	{
		$code = $this->method('JoomlaSix');

		$this->assertSame(2, substr_count(
			$code,
			"Joomla___5ba38513_5c4f_4b0d_935e_49e986a6bce8___Power::checkToken()"
			. " or die(Text::_('JINVALID_TOKEN'));"
		));
	}

	/**
	 * Each method demands both its own and the core permission.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEachMethodDemandsItsOwnAndTheCorePermission(): void
	{
		$code = $this->method('JoomlaSix');

		$this->assertStringContainsString(
			"if (\$user->authorise('article.export', 'com_demo')"
			. " && \$user->authorise('core.export', 'com_demo'))", $code
		);
		$this->assertStringContainsString(
			"if (\$user->authorise('article.import', 'com_demo')"
			. " && \$user->authorise('core.import', 'com_demo'))", $code
		);
	}

	/**
	 * The export writes the selected rows out through the component helper.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheExportWritesTheSelectedRowsThroughTheComponentHelper(): void
	{
		$code = $this->method('JoomlaSix');

		$this->assertStringContainsString(
			"\$pks = \$input->post->get('cid', array(), 'array');", $code
		);
		$this->assertStringContainsString('$pks = ArrayHelper::toInteger($pks);', $code);
		$this->assertStringContainsString("\$model = \$this->getModel('Articles');", $code);
		$this->assertStringContainsString('$data = $model->getExportData($pks);', $code);
		$this->assertStringContainsString(
			"DemoHelper::xls(\$data,'Articles_'.\$date->format('jS_F_Y'),"
			. "'Articles exported ('.\$date->format('jS F, Y').')','articles');", $code
		);
	}

	/**
	 * The import hands its headers and its origin to the session.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheImportHandsItsHeadersAndOriginToTheSession(): void
	{
		$code = $this->method('JoomlaSix');

		$this->assertStringContainsString(
			'$headers = $model->getExImPortHeaders();', $code
		);
		$this->assertStringContainsString(
			"\$session->set('article_VDM_IMPORTHEADERS', \$headers);", $code
		);
		$this->assertStringContainsString(
			"\$session->set('backto_VDM_IMPORT', 'articles');", $code
		);
		$this->assertStringContainsString(
			"\$session->set('dataType_VDM_IMPORTINTO', 'article');", $code
		);
	}

	/**
	 * A view with its own import scripting is sent to its own import view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithItsOwnScriptsIsSentToItsOwnImportView(): void
	{
		$shared = $this->method('JoomlaSix');
		$custom = $this->method('JoomlaSix', true);

		$this->assertStringContainsString(
			"_('index.php?option=com_demo&view=import', false), \$message);", $shared
		);
		$this->assertStringContainsString(
			"_('index.php?option=com_demo&view=import_articles', false), \$message);", $custom
		);
		$this->assertStringNotContainsString('view=import_articles', $shared);
	}

	/**
	 * The prompt shown on the import screen is registered for translation.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheImportPromptIsRegisteredForTranslation(): void
	{
		$code = $this->method('JoomlaSix');

		$this->assertStringContainsString(
			"_('COM_DEMO_IMPORT_SELECT_FILE_FOR_ARTICLES');", $code
		);
		$this->assertSame(
			'Select the file to import data to articles.',
			$this->language()->get('admin', 'COM_DEMO_IMPORT_SELECT_FILE_FOR_ARTICLES')
		);
	}

	/**
	 * Falling through either guard lands back on the list with an error.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testFallingThroughEitherGuardLandsBackOnTheList(): void
	{
		$code = $this->method('JoomlaSix');

		$this->assertStringContainsString(
			"_('COM_DEMO_EXPORT_FAILED');", $code
		);
		$this->assertStringContainsString(
			"_('COM_DEMO_IMPORT_FAILED');", $code
		);
		$this->assertSame(2, substr_count(
			$code,
			"_('index.php?option=com_demo&view=articles', false), \$message, 'error');"
		));
	}

	/**
	 * Build the generated method for one input shape.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   bool    $custom   Whether the view carries custom import scripting.
	 * @param   bool    $port     Whether the view carries the export feature.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function method(string $version, bool $custom = false,
		bool $port = true): string
	{
		// only Joomla 3 takes the current user from the global factory
		$class = $this->targetClass(
			$version, 'Controller\\EximportMethod', ['JoomlaThree']
		);

		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');

		$eximportview = new EximportView();
		$eximportview->set('articles', $port);

		$importcustomscripts = new ImportCustomScripts();

		if ($custom)
		{
			$importcustomscripts->set('articles', true);
		}

		$subject = $this->renderer($class, [
			'contentone' => $contentone,
			'eximportview' => $eximportview,
			'importcustomscripts' => $importcustomscripts,
		]);

		return $subject->get('article', 'articles');
	}
}
