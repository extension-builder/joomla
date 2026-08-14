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


use VDM\Joomla\Utilities\UploadHelper;


/**
 * Fixture exposing protected upload-validation boundaries.
 *
 * @since  1.0.0
 */
final class UploadHelperFixture extends UploadHelper
{
	/**
	 * Validate an uploaded-file descriptor.
	 *
	 * @param   array   $upload  Uploaded-file descriptor.
	 * @param   string  $type    Allowed file family.
	 *
	 * @return  array|null  Validated descriptor or null.
	 * @since   1.0.0
	 */
	public static function validate(array $upload, string $type): ?array
	{
		return parent::check($upload, $type);
	}

	/**
	 * Remove a temporary uploaded file.
	 *
	 * @param   string  $fullPath  Absolute file path.
	 *
	 * @return  bool  True when the file was removed.
	 * @since   1.0.0
	 */
	public static function discard(string $fullPath): bool
	{
		return parent::remove($fullPath);
	}
}
