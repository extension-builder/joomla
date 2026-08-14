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

namespace VDM\Tests\Support;


use Joomla\CMS\Session\Session;
use Throwable;


/**
 * Minimal legacy Joomla application fixture exposing a session accessor.
 *
 * @since  1.0.0
 */
final class SessionApplicationFixture
{
	/**
	 * Number of session retrievals.
	 *
	 * @var    int
	 * @since  1.0.0
	 */
	public int $requests = 0;

	/**
	 * Session result or failure to expose.
	 *
	 * @var    Session|Throwable
	 * @since  1.0.0
	 */
	private Session|Throwable $result;

	/**
	 * Create the application fixture.
	 *
	 * @param   Session|Throwable  $result  Session result or failure.
	 *
	 * @since   1.0.0
	 */
	public function __construct(Session|Throwable $result)
	{
		$this->result = $result;
	}

	/**
	 * Return the configured session or throw the configured failure.
	 *
	 * @return  Session
	 * @throws  Throwable
	 * @since   1.0.0
	 */
	public function getSession(): Session
	{
		$this->requests++;

		if ($this->result instanceof Throwable)
		{
			throw $this->result;
		}

		return $this->result;
	}
}
