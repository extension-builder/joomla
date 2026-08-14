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


use VDM\Joomla\Abstraction\Remote\Config;


/**
 * Remote configuration fixture with reviewed non-empty optional metadata.
 *
 * @since  1.0.0
 */
final class RemoteConfigFixture extends Config
{
	/** @var string @since 1.0.0 */
	protected string $prefix_key = 'prefix-';

	/** @var string @since 1.0.0 */
	protected string $suffix_key = '-suffix';

	/** @var array<string, string> @since 1.0.0 */
	protected array $placeholders = ['[[TYPE]]' => 'power'];

	/** @var array<int, string> @since 1.0.0 */
	protected array $files = ['source_file'];

	/** @var array<int, string> @since 1.0.0 */
	protected array $folders = ['source_folder'];

	/** @var array<int, string> @since 1.0.0 */
	protected array $children = ['class_property'];

	/** @var string|null @since 1.0.0 */
	protected ?string $guid_helper_field = 'system_name';
}
