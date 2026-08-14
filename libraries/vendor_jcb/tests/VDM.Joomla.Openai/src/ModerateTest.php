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

namespace VDM\Joomla\Openai\Tests;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Openai\Moderate;
use VDM\Joomla\Openai\Tests\Support\OpenaiTestCase;


/**
 * OpenAI moderation endpoint test.
 *
 * @since  6.1.6
 */
#[CoversClass(Moderate::class)]
final class ModerateTest extends OpenaiTestCase
{
	/**
	 * Preserve array input and include an explicitly selected moderation model.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTextSendsArrayInputAndModel(): void
	{
		/** @var Moderate $subject */
		[$subject, $transport] = $this->createEndpoint(Moderate::class);

		$subject->text(['first', 'second'], 'moderation-model');

		$this->assertOpenaiRequest($transport, 'POST', '/moderations');
		$this->assertSame([
			'input' => ['first', 'second'],
			'model' => 'moderation-model'
		], $this->jsonRequest($transport));
	}

	/**
	 * Omit a null model from scalar moderation input.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTextOmitsNullModel(): void
	{
		/** @var Moderate $subject */
		[$subject, $transport] = $this->createEndpoint(Moderate::class);

		$subject->text('single input');

		$this->assertSame(['input' => 'single input'], $this->jsonRequest($transport));
	}
}
