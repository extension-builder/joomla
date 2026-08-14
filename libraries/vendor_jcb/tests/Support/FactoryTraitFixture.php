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


use VDM\Joomla\Componentbuilder\FactoryTrait;


/**
 * Fixture exposing protected entity-factory routing methods.
 *
 * @since  1.0.0
 */
final class FactoryTraitFixture
{
	use FactoryTrait;

	/**
	 * Select the fixture's primary entity.
	 *
	 * @param   string  $entity  Entity name.
	 *
	 * @return  self
	 * @since   1.0.0
	 */
	public function select(string $entity): self
	{
		return $this->setEntity($entity);
	}

	/**
	 * Return the selected primary entity.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function selected(): string
	{
		return $this->getEntity();
	}

	/**
	 * Resolve the primary entity factory.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function factory(): string
	{
		return $this->getFactory();
	}

	/**
	 * Resolve another entity's factory.
	 *
	 * @param   string  $entity  Entity name.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function factoryFor(string $entity): string
	{
		return $this->getEntityFactory($entity);
	}

	/**
	 * Resolve another entity's public area.
	 *
	 * @param   string  $entity  Entity name.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	public function areaFor(string $entity): ?string
	{
		return $this->getEntityArea($entity);
	}

	/**
	 * Expose the lazy factory cache for identity assertions.
	 *
	 * @return  array<string, string|null>
	 * @since   1.0.0
	 */
	public function cache(): array
	{
		return $this->entityFactory;
	}
}
