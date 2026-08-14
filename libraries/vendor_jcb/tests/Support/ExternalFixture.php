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


use Joomla\Database\DatabaseInterface;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\External;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;


/**
 * Exposes deterministic external-code slicing without live I/O.
 *
 * @since  1.0.0
 */
final class ExternalFixture extends External
{
	/**
	 * Constructor.
	 *
	 * @param   Placeholder        $placeholder  Compiler placeholder service.
	 * @param   DatabaseInterface  $database     Database boundary.
	 *
	 * @since   1.0.0
	 */
	public function __construct(Placeholder $placeholder, DatabaseInterface $database)
	{
		parent::__construct($placeholder, $database);
	}

	/**
	 * Cut rows from both ends of external code.
	 *
	 * @param   string  $content   External code.
	 * @param   string  $sequence  Top and bottom counts.
	 * @param   string  $key       Diagnostic key.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function slice(string $content, string $sequence, string $key): string
	{
		return $this->cut($content, $sequence, $key);
	}
}
