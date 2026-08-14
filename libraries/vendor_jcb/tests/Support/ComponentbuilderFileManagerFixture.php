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


use Joomla\CMS\User\User;
use VDM\Joomla\Componentbuilder\File\Image;
use VDM\Joomla\Componentbuilder\File\Manager;
use VDM\Joomla\Componentbuilder\File\Type;
use VDM\Joomla\Componentbuilder\Interfaces\File\TypeDefinitionInterface;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Interfaces\Data\ItemsInterface;
use VDM\Joomla\Interfaces\File\AgentInterface;
use VDM\Joomla\Interfaces\File\DefinitionInterface;


/**
 * File-manager fixture exposing deterministic protected policies.
 *
 * @since  6.1.6
 */
final class ComponentbuilderFileManagerFixture extends Manager
{
	/**
	 * Inject every boundary, including the normally global Joomla identity.
	 *
	 * @since  6.1.6
	 */
	public function __construct(
		ItemInterface $item,
		ItemsInterface $items,
		Type $type,
		AgentInterface $agent,
		Image $image,
		User $user
	)
	{
		$this->item = $item;
		$this->items = $items;
		$this->type = $type;
		$this->agent = $agent;
		$this->image = $image;
		$this->user = $user;
	}

	/**
	 * Expose file-name normalization.
	 *
	 * @since  6.1.6
	 */
	public function fileName(DefinitionInterface $file, string $entity): string
	{
		return $this->getFileName($file, $entity);
	}

	/**
	 * Expose append-only crop numbering.
	 *
	 * @since  6.1.6
	 */
	public function fileNumber(TypeDefinitionInterface $type, string $entity): int
	{
		return $this->getFileNumber($type, $entity);
	}

	/**
	 * Expose persistence modelling.
	 *
	 * @since  6.1.6
	 */
	public function model(
		DefinitionInterface $file,
		string $guid,
		string $entity,
		string $target,
		TypeDefinitionInterface $type
	): object
	{
		return $this->modelFileDefinition($file, $guid, $entity, $target, $type);
	}

	/**
	 * Expose oldest-file selection.
	 *
	 * @param   array<int, object>  $files  Candidate files.
	 *
	 * @return  array<int, object>
	 * @since   6.1.6
	 */
	public function oldest(array $files, int $quantity): array
	{
		return $this->extractOldestFiles($files, $quantity);
	}

	/**
	 * Expose quantity-limit enforcement.
	 *
	 * @since  6.1.6
	 */
	public function enforce(TypeDefinitionInterface $type, string $guid, string $entity, string $target): void
	{
		$this->limitFileType($type, $guid, $entity, $target);
	}
}
