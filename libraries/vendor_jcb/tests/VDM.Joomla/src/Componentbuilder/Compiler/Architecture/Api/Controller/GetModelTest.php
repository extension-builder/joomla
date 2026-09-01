<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    1st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Controller;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\GetModel;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The explicit model mapping of the API controllers.
 *
 * @since 6.1.7
 */
#[CoversClass(GetModel::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class GetModelTest extends ArchitectureTestCase
{
	/**
	 * The get model body of a view named demo and demos.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED = <<<'GEN'

		// The model names of this view are explicit, the content type is never inflected.
		if ($name !== '' && strtolower((string) $name) === $this->contentType)
		{
			$name = 'demos';
		}
		else
		{
			$name = 'demo';
		}

		return parent::getModel($name, $prefix, $config);
GEN;

	/**
	 * The list name maps to the list model and everything else to the item model.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheListNameMapsToTheListModelAndEverythingElseToTheItemModel(): void
	{
		$subject = $this->renderer(GetModel::class);

		$this->assertSame(self::EXPECTED, $subject->get('demo', 'demos'));
	}

	/**
	 * The names are taken as given and never inflected.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheNamesAreTakenAsGivenAndNeverInflected(): void
	{
		$subject = $this->renderer(GetModel::class);
		$code = $subject->get('person', 'people');

		$this->assertStringContainsString("\$name = 'people';", $code);
		$this->assertStringContainsString("\$name = 'person';", $code);
		$this->assertStringNotContainsString('singular', $code);
	}
}
