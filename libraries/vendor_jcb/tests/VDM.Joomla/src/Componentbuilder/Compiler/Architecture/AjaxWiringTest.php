<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    20th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Controller\AjaxTasks;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\AjaxMethods;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;


/**
 * What the ajax controller and model of a target are wired with.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class AjaxWiringTest extends ArchitectureTestCase
{
	/**
	 * The methods a target's ajax model carries, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_METHODS = <<<'GEN'


	// Used in demo
public function getDemoAjax() {}

	// Used in looker
public function getLookerAjax() {}
GEN;

	/**
	 * The tasks a target's ajax controller registers, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_TASKS = <<<'GEN'

		$this->registerTask('getDemo', 'ajax');
		$this->registerTask('getLooker', 'ajax');
GEN;

	/**
	 * Every view that was given an ajax method has it, and says so.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryViewGivenAnAjaxMethodHasIt(): void
	{
		$subject = $this->renderer(AjaxMethods::class, [
			'dispenser' => $this->dispenser([
				'ajax_model' => [
					'demo' => 'public function getDemoAjax() {}',
					'looker' => 'public function getLookerAjax() {}'
				]
			])
		]);

		$this->assertSame(self::EXPECTED_METHODS, $subject->get('admin'));
	}

	/**
	 * A target no view was given ajax for carries no methods.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATargetWithNoAjaxCarriesNoMethods(): void
	{
		$subject = $this->renderer(AjaxMethods::class, [
			'dispenser' => $this->dispenser([])
		]);

		$this->assertSame('', $subject->get('admin'));
	}

	/**
	 * Every task the views were given is registered.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryTaskTheViewsWereGivenIsRegistered(): void
	{
		$subject = $this->renderer(AjaxTasks::class, [
			'dispenser' => $this->dispenser([
				'ajax_controller' => [
					'demo' => [['task_name' => 'getDemo']],
					'looker' => [['task_name' => 'getLooker']]
				]
			])
		]);

		$this->assertSame(self::EXPECTED_TASKS, $subject->get('admin'));
	}

	/**
	 * A task two views asked for is registered once.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATaskTwoViewsAskedForIsRegisteredOnce(): void
	{
		$subject = $this->renderer(AjaxTasks::class, [
			'dispenser' => $this->dispenser([
				'ajax_controller' => [
					'demo' => [['task_name' => 'shared']],
					'looker' => [['task_name' => 'shared']]
				]
			])
		]);

		$this->assertSame(
			PHP_EOL . "\t\t\$this->registerTask('shared', 'ajax');",
			$subject->get('admin')
		);
	}

	/**
	 * A target no view was given ajax for registers no tasks.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATargetWithNoAjaxRegistersNoTasks(): void
	{
		$subject = $this->renderer(AjaxTasks::class, [
			'dispenser' => $this->dispenser([])
		]);

		$this->assertSame('', $subject->get('admin'));
	}

	/**
	 * A dispenser holding the given custom code for the admin target.
	 *
	 * @param   array  $held  What the compiler collected.
	 *
	 * @return  Dispenser
	 * @since   6.1.7
	 */
	private function dispenser(array $held): Dispenser
	{
		$dispenser = (new ReflectionClass(Dispenser::class))->newInstanceWithoutConstructor();
		$dispenser->hub = $held === [] ? [] : ['admin' => $held];

		return $dispenser;
	}
}
