<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    2nd October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Contract;


use Joomla\CMS\Language\Language;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use VDM\Component\Componentbuilder\Administrator\Model\AjaxModel;
use VDM\Tests\Support\JoomlaTestCase;


require_once dirname(__DIR__, 4) . '/admin/src/Model/AjaxModel.php';


/**
 * Protect the actual AJAX import permission and its pre-engine input guards.
 *
 * The generated administrator model is outside the vendor coverage filter.
 * Only the native User authorization boundary is mocked; the method under test
 * executes unchanged without booting Joomla or constructing its database model.
 *
 * @since  6.2.1
 */
#[CoversNothing]
final class ExtrusionImportAccessTest extends JoomlaTestCase
{
	/**
	 * View access reaches normal validation without querying an extra action.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testViewAccessAloneReachesConfigurationValidation(): void
	{
		$this->assertSame(
			['error' => 'The extrusion configuration could not be read.'],
			$this->model(true)->extrusionImport('malformed-json', '{}')
		);
	}

	/**
	 * The permission correction does not bypass review of a durable import.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testViewAccessStillRequiresAnApprovedPlanForWrites(): void
	{
		$this->assertSame(
			['error' => 'Review the current write plan before importing.'],
			$this->model(true)->extrusionImport('{"dry_run":0}', '{}')
		);
	}

	/**
	 * A denied user never reaches configuration, dry-run or plan processing.
	 *
	 * @param   string  $configuration  Attempted import configuration.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('deniedInputs')]
	public function testDeniedViewAccessBlocksEveryImportEntry(string $configuration): void
	{
		$this->assertSame(
			['error' => 'You do not have permission to import with the extrusion tool.'],
			$this->model(false)->extrusionImport($configuration, '{}')
		);
	}

	/**
	 * Attempts cannot substitute a dry run or approved-plan token for access.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.2.1
	 */
	public static function deniedInputs(): array
	{
		return [
			'malformed input' => ['malformed-json'],
			'dry run' => ['{"dry_run":1}'],
			'approved durable plan' => ['{"dry_run":0,"approved_plan":"' . str_repeat('a', 64) . '"}'],
		];
	}

	/**
	 * Bind the real model to a user whose only reviewed import action is access.
	 *
	 * @param   bool  $allowed  Whether the view access permission is granted.
	 *
	 * @return  AjaxModel
	 * @since   6.2.1
	 */
	private function model(bool $allowed): AjaxModel
	{
		$language = new Language('en-GB');
		$this->assertTrue($language->load('com_componentbuilder', dirname(__DIR__, 4) . '/admin', 'en-GB', true, false));
		$this->setJoomlaFactoryProperty('language', $language);
		$user = $this->getMockBuilder(User::class)->disableOriginalConstructor()
			->onlyMethods(['authorise'])->getMock();
		$user->expects($this->once())->method('authorise')
			->with('extrusion.access', 'com_componentbuilder')->willReturn($allowed);
		$model = (new ReflectionClass(AjaxModel::class))->newInstanceWithoutConstructor();
		$model->setCurrentUser($user);

		return $model;
	}
}
