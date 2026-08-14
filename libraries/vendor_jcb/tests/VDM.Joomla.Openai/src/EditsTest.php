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
use VDM\Joomla\Openai\Edits;
use VDM\Joomla\Openai\Tests\Support\OpenaiTestCase;


/**
 * OpenAI edits endpoint test.
 *
 * @since  6.1.6
 */
#[CoversClass(Edits::class)]
final class EditsTest extends OpenaiTestCase
{
	/**
	 * Map every edit option and preserve zero-valued controls.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateSendsCompleteEditPayload(): void
	{
		/** @var Edits $subject */
		[$subject, $transport] = $this->createEndpoint(Edits::class);

		$subject->create('edit-model', 'Correct the grammar', '', 0, 0.0, 1.0);

		$this->assertOpenaiRequest($transport, 'POST', '/edits');
		$this->assertSame([
			'model' => 'edit-model',
			'instruction' => 'Correct the grammar',
			'input' => '',
			'n' => 0,
			'temperature' => 0,
			'top_p' => 1
		], $this->jsonRequest($transport));
	}

	/**
	 * Exclude optional fields when only the required contract is supplied.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateOmitsNullOptionalFields(): void
	{
		/** @var Edits $subject */
		[$subject, $transport] = $this->createEndpoint(Edits::class);

		$subject->create('edit-model', 'Rewrite');

		$this->assertSame([
			'model' => 'edit-model',
			'instruction' => 'Rewrite'
		], $this->jsonRequest($transport));
	}
}
