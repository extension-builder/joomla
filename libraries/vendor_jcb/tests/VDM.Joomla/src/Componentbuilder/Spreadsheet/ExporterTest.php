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

namespace VDM\Joomla\Tests\Componentbuilder\Spreadsheet;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\User\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionMethod;
use VDM\Joomla\Componentbuilder\Spreadsheet\Exporter;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Spreadsheet document, worksheet, style, and export-mode contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Exporter::class)]
final class ExporterTest extends JoomlaTestCase
{
	/**
	 * Construct a spreadsheet with deterministic user identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$user = $this->createStub(User::class);
		$user->name = 'Release Editor';
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())->method('getIdentity')->willReturn($user);
		$this->setJoomlaApplication($app);
	}

	/**
	 * Initialize the workbook and reviewed style palettes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorInitializesWorkbookAndStylePolicies(): void
	{
		$subject = new Exporter();
		$reflection = new ReflectionClass(Exporter::class);

		$this->assertInstanceOf(Spreadsheet::class, $reflection->getProperty('spreadsheet')->getValue($subject));
		$this->assertSame(
			['font' => ['bold' => true, 'color' => ['rgb' => '1171A3'], 'size' => 13, 'name' => 'Verdana']],
			$reflection->getProperty('headerStyles')->getValue($subject)
		);
		$this->assertSame('444444', $reflection->getProperty('sideStyles')->getValue($subject)['font']['color']['rgb']);
		$this->assertSame(11, $reflection->getProperty('normalStyles')->getValue($subject)['font']['size']);
	}

	/**
	 * Switch style and writer modes at the exact reviewed row thresholds.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDetermineModeUsesXlsAndCsvThresholds(): void
	{
		$subject = new Exporter();
		$reflection = new ReflectionClass(Exporter::class);
		$reflection->getProperty('fileType')->setValue($subject, 'Xls');

		$this->assertSame(1, $this->invoke($subject, 'determineXlsMode', [2000]));
		$this->assertSame('Xls', $reflection->getProperty('fileType')->getValue($subject));
		$this->assertSame(2, $this->invoke($subject, 'determineXlsMode', [2001]));
		$this->assertSame('Xls', $reflection->getProperty('fileType')->getValue($subject));
		$this->assertSame(3, $this->invoke($subject, 'determineXlsMode', [3001]));
		$this->assertSame('Csv', $reflection->getProperty('fileType')->getValue($subject));
	}

	/**
	 * Populate cells and apply header, side, and normal style policies.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPopulateSpreadsheetWritesValuesTitlesAndStyles(): void
	{
		$subject = new Exporter();
		$reflection = new ReflectionClass(Exporter::class);
		$reflection->getProperty('fileType')->setValue($subject, 'Xls');
		$reflection->getProperty('subjectTab')->setValue($subject, 'Components');
		$this->invoke($subject, 'populateSpreadsheet', [
			[
				['Name', 'Version'],
				['JCB', '6.x'],
				['Demo', '1.0'],
			],
		]);
		/** @var Spreadsheet $spreadsheet */
		$spreadsheet = $reflection->getProperty('spreadsheet')->getValue($subject);
		$sheet = $spreadsheet->getActiveSheet();

		$this->assertSame('Components', $sheet->getTitle());
		$this->assertSame('Name', $sheet->getCell('A1')->getValue());
		$this->assertSame('Version', $sheet->getCell('B1')->getValue());
		$this->assertSame('JCB', $sheet->getCell('A2')->getValue());
		$this->assertSame(1.0, $sheet->getCell('B3')->getValue());
		$this->assertTrue($sheet->getColumnDimension('A')->getAutoSize());
		$this->assertSame(18, $sheet->getRowDimension(1)->getRowHeight());
		$this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
		$this->assertSame('FF1171A3', $sheet->getStyle('A1')->getFont()->getColor()->getARGB());
		$this->assertTrue($sheet->getStyle('A2')->getFont()->getBold());
		$this->assertFalse($sheet->getStyle('B2')->getFont()->getBold());
	}

	/**
	 * Set all document metadata while defaulting the modifier to active identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDocumentPropertiesUseExplicitMetadataAndIdentityFallback(): void
	{
		$subject = new Exporter();
		$reflection = new ReflectionClass(Exporter::class);
		$reflection->getProperty('subjectTab')->setValue($subject, 'Releases');
		$this->invoke($subject, 'setDocumentProperties', [
			'Build System',
			'Component Releases',
			'Generated release inventory',
			'Engineering',
			'joomla component builder',
			null,
		]);
		/** @var Spreadsheet $spreadsheet */
		$spreadsheet = $reflection->getProperty('spreadsheet')->getValue($subject);
		$properties = $spreadsheet->getProperties();

		$this->assertSame('Build System', $properties->getCreator());
		$this->assertSame('Vast Development Method', $properties->getCompany());
		$this->assertSame('Release Editor', $properties->getLastModifiedBy());
		$this->assertSame('Component Releases', $properties->getTitle());
		$this->assertSame('Releases', $properties->getSubject());
		$this->assertSame('Generated release inventory', $properties->getDescription());
		$this->assertSame('Engineering', $properties->getCategory());
		$this->assertSame('joomla component builder', $properties->getKeywords());
	}

	/**
	 * Invoke a private exporter policy without triggering response output or exit.
	 *
	 * @param   array<int, mixed>  $arguments  Method arguments.
	 *
	 * @return  mixed
	 * @since   6.1.6
	 */
	private function invoke(Exporter $subject, string $method, array $arguments): mixed
	{
		return (new ReflectionMethod($subject, $method))->invokeArgs($subject, $arguments);
	}
}
