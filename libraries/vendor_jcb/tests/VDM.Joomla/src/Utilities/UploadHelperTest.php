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

namespace VDM\Joomla\Tests\Utilities;


use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Language;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Joomla\Utilities\MimeHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Joomla\Utilities\UploadHelper;
use VDM\Tests\Support\FilesystemTestCase;
use VDM\Tests\Support\UploadHelperFixture;


/**
 * Upload validation, cleanup, and error-state tests.
 *
 * @since  6.1.6
 */
#[CoversClass(UploadHelper::class)]
#[UsesClass(Helper::class)]
#[UsesClass(MimeHelper::class)]
#[UsesClass(StringHelper::class)]
final class UploadHelperTest extends FilesystemTestCase
{
	/**
	 * Original upload-helper and component-helper static state.
	 *
	 * @var    array<string, mixed>
	 * @since  6.1.6
	 */
	private array $originalState = [];

	/**
	 * Isolate upload policy, errors, and component parameters.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$upload = new ReflectionClass(UploadHelper::class);
		$component = new ReflectionClass(Helper::class);
		$this->originalState = [
			'useStreams' => UploadHelper::$useStreams,
			'allowUnsafe' => UploadHelper::$allowUnsafe,
			'safeFileOptions' => UploadHelper::$safeFileOptions,
			'enqueueError' => UploadHelper::$enqueueError,
			'legalFormats' => UploadHelper::$legalFormats,
			'errors' => $upload->getProperty('errors')->getValue(),
			'option' => Helper::$option,
			'params' => $component->getProperty('params')->getValue()
		];

		UploadHelper::$useStreams = false;
		UploadHelper::$allowUnsafe = false;
		UploadHelper::$safeFileOptions = [];
		UploadHelper::$enqueueError = false;
		UploadHelper::$legalFormats = [];
		$upload->getProperty('errors')->setValue(null, []);
		Helper::$option = 'com_componentbuilder';
		$component->getProperty('params')->setValue(
			null,
			['com_componentbuilder' => new Registry()]
		);
	}

	/**
	 * Restore upload policy, errors, and component parameters.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		$upload = new ReflectionClass(UploadHelper::class);
		$component = new ReflectionClass(Helper::class);
		UploadHelper::$useStreams = $this->originalState['useStreams'];
		UploadHelper::$allowUnsafe = $this->originalState['allowUnsafe'];
		UploadHelper::$safeFileOptions = $this->originalState['safeFileOptions'];
		UploadHelper::$enqueueError = $this->originalState['enqueueError'];
		UploadHelper::$legalFormats = $this->originalState['legalFormats'];
		$upload->getProperty('errors')->setValue(null, $this->originalState['errors']);
		Helper::$option = $this->originalState['option'];
		$component->getProperty('params')->setValue(null, $this->originalState['params']);

		parent::tearDown();
	}

	/**
	 * Accept a configured extension and expose its verified MIME metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValidateAcceptsConfiguredFormatAndMime(): void
	{
		$file = $this->writeTemporaryFile('upload/readme.txt', 'Joomla Component Builder');
		UploadHelper::$legalFormats = ['txt'];

		$result = UploadHelperFixture::validate(
			[
				'name' => 'readme.txt',
				'type' => MimeHelper::mimeType($file),
				'full_path' => $file
			],
			'file'
		);

		$this->assertSame('txt', $result['extension']);
		$this->assertSame(MimeHelper::mimeType($file), $result['mime']);
		$this->assertArrayNotHasKey('type', $result);
		$this->assertFileExists($file);
		$this->assertSame([], UploadHelper::getError());
	}

	/**
	 * Reject an unconfigured extension, remove the file, and record the reason.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValidateRejectsIllegalFormatAndRemovesFile(): void
	{
		$file = $this->writeTemporaryFile('upload/tool.exe', 'not an executable');

		$this->assertNull(
			UploadHelperFixture::validate(
				[
					'name' => 'tool.exe',
					'type' => 'application/octet-stream',
					'full_path' => $file
				],
				'file'
			)
		);
		$this->assertFileDoesNotExist($file);
		$this->assertContains('COM_COMPONENTBUILDER_UPLOAD_IS_NOT_A_VALID_TYPE', UploadHelper::getError());
		$this->assertStringContainsString(
			'COM_COMPONENTBUILDER_UPLOAD_IS_NOT_A_VALID_TYPE',
			UploadHelper::getError(true)
		);
	}

	/**
	 * Remove an existing temporary file and reject missing paths.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDiscardReportsExistingAndMissingPaths(): void
	{
		$file = $this->writeTemporaryFile('discard.txt', 'temporary');

		$this->assertTrue(UploadHelperFixture::discard($file));
		$this->assertFileDoesNotExist($file);
		$this->assertFalse(UploadHelperFixture::discard($file));
	}

	/**
	 * Report a missing HTTP upload without consulting filesystem destinations.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetReportsMissingUploadAndRawFilterPolicy(): void
	{
		$language = $this->createStub(Language::class);
		$language->method('_')->willReturnArgument(0);
		$this->setJoomlaFactoryProperty('language', $language);
		$application = new class
		{
			/**
			 * Return request input with an empty file collection.
			 *
			 * @return  Input
			 * @since   6.1.6
			 */
			public function getInput(): Input
			{
				return new Input();
			}
		};
		$this->setJoomlaFactoryProperty('application', $application);

		$this->assertNull(UploadHelper::get('missing-upload', 'file', 'raw'));
		$this->assertTrue(UploadHelper::$allowUnsafe);
		$this->assertContains('COM_COMPONENTBUILDER_NO_UPLOAD_SELECTED', UploadHelper::getError());
	}
}
