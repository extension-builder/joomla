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


use VDM\Joomla\Abstraction\PHPConfigurationChecker;


/**
 * PHP configuration checker fixture with minimal accepted requirements.
 *
 * @since  1.0.0
 */
final class PHPConfigurationCheckerFixture extends PHPConfigurationChecker
{
	/** @var string @since 1.0.0 */
	protected string $upload_max_filesize = '0K';

	/** @var string @since 1.0.0 */
	protected string $post_max_size = '0K';

	/** @var int @since 1.0.0 */
	protected int $max_execution_time = 0;

	/** @var int @since 1.0.0 */
	protected int $max_input_vars = 0;

	/** @var int @since 1.0.0 */
	protected int $max_input_time = -1;

	/** @var string @since 1.0.0 */
	protected string $memory_limit = '0K';

	/**
	 * Convert one INI size into bytes.
	 *
	 * @param   string  $value  INI size.
	 *
	 * @return  int  Converted bytes.
	 * @since   1.0.0
	 */
	public function bytes(string $value): int
	{
		return $this->convertToBytes($value);
	}
}
