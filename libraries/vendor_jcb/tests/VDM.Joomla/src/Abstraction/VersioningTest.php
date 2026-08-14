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

namespace VDM\Joomla\Tests\Abstraction;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Table\ContentHistory;
use Joomla\CMS\Table\ContentType;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use VDM\Joomla\Abstraction\Versioning;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\JoomlaTestCase;
use VDM\Tests\Support\VersioningFixture;

/**
 * Shared Joomla content-history configuration and entity resolution tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Versioning::class)]
final class VersioningTest extends JoomlaTestCase
{
	/**
	 * Previous component option.
	 *
	 * @var    string|null
	 * @since  6.1.6
	 */
	private ?string $previousOption = null;

	/**
	 * Previous component manifest cache.
	 *
	 * @var    array<string, mixed>
	 * @since  6.1.6
	 */
	private array $previousManifest = [];

	/**
	 * Previous component parameter cache.
	 *
	 * @var    array<string, Registry>
	 * @since  6.1.6
	 */
	private array $previousParams = [];

	/**
	 * Install deterministic component metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->previousOption = Helper::$option;
		$this->previousManifest = Helper::$manifest;
		$helper = new ReflectionClass(Helper::class);
		$params = $helper->getProperty('params');
		$this->previousParams = $params->getValue();

		Helper::setOption('com_fixture');
		Helper::$manifest['com_fixture'] = (object) [
			'namespace' => 'Fixture\\Component\\Fixture',
		];
		$params->setValue(
			null,
			[
				'com_fixture' => new Registry(
					['save_history' => 1, 'history_limit' => 9]
				),
			]
		);
	}

	/**
	 * Restore shared component metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		Helper::setOption($this->previousOption);
		Helper::$manifest = $this->previousManifest;
		(new ReflectionClass(Helper::class))
			->getProperty('params')
			->setValue(null, $this->previousParams);

		parent::tearDown();
	}

	/**
	 * Toggle history explicitly and restore the component default with null.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testHistoryToggleRestoresConfiguredDefault(): void
	{
		$subject = $this->subject();

		$this->assertSame(1, $subject->historyState());
		$this->assertSame($subject, $subject->history(0));
		$this->assertSame(0, $subject->historyState());
		$this->assertSame($subject, $subject->history());
		$this->assertSame(1, $subject->historyState());
	}

	/**
	 * Strip only the current component prefix from database table names.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableEntityNameProtectsComponentBoundary(): void
	{
		$subject = $this->subject();

		$this->assertSame('article', $subject->tableEntityName('article'));
		$this->assertSame('article', $subject->tableEntityName('#__fixture_article'));
		$this->assertNull($subject->tableEntityName('#__other_article'));
	}

	/**
	 * Skip history persistence when the generated Joomla table class is absent.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMissingTableClassPreventsSingleAndBatchHistoryWrites(): void
	{
		$subject = $this->subject();
		$subject->entity('missing');

		$this->assertNull($subject->tableClass());
		$this->assertFalse($subject->saveHistory(7));
		$this->assertSame(0, $subject->saveMultipleHistory([7, 8]));
	}

	/**
	 * Build a versioning fixture with isolated Joomla boundaries.
	 *
	 * @return  VersioningFixture
	 * @since   6.1.6
	 */
	private function subject(): VersioningFixture
	{
		$application = $this->createStub(CMSApplicationInterface::class);
		$application->method('getIdentity')->willReturn(null);

		return new VersioningFixture(
			$this->createStub(DatabaseInterface::class),
			$application,
			$this->createStub(ContentHistory::class),
			$this->createStub(ContentType::class)
		);
	}
}
