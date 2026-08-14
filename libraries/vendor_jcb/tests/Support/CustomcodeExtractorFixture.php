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


use VDM\Joomla\Componentbuilder\Compiler\Customcode\Extractor;


/**
 * Exposes the extractor's deterministic marker-parsing seam.
 *
 * @since  1.0.0
 */
final class CustomcodeExtractorFixture extends Extractor
{
	/**
	 * Avoid the filesystem and Joomla application graph for parser contracts.
	 *
	 * @since  1.0.0
	 */
	public function __construct()
	{
	}

	/**
	 * Append the stored identifier using the selected comment syntax.
	 *
	 * @param   int     $id            Stored custom-code identifier.
	 * @param   int     $commentType   Marker comment type.
	 * @param   string  $startReplace  Marker prefix.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function startReplace(int $id, int $commentType, string $startReplace): string
	{
		return $this->setStartReplace($id, $commentType, $startReplace);
	}

	/**
	 * Return content adjacent to a marker when it belongs to the requested side.
	 *
	 * @param   string  $replaceKey  Marker text.
	 * @param   int     $type        Marker side.
	 * @param   string  $content     Source line.
	 *
	 * @return  bool|int|string
	 * @since   1.0.0
	 */
	public function lineContent(string $replaceKey, int $type, string $content): bool|int|string
	{
		return $this->addLineChecker($replaceKey, $type, $content);
	}

	/**
	 * Extract a stored identifier from one compiler marker line.
	 *
	 * @param   string  $content       Marker line.
	 * @param   array   $placeholders  Comment marker prefixes.
	 * @param   int     $commentType   Marker comment type.
	 *
	 * @return  mixed
	 * @since   1.0.0
	 */
	public function systemId(string $content, array $placeholders, int $commentType): mixed
	{
		return $this->getSystemID($content, $placeholders, $commentType);
	}
}
