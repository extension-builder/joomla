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

use VDM\Joomla\Abstraction\Remote\Set;
use VDM\Joomla\Interfaces\Remote\Dependency\ResolverInterface;

/**
 * Concrete fixture exposing shared remote-set behavior and abstract hook calls.
 *
 * @since  1.0.0
 */
final class RemoteSetFixture extends Set
{
	/**
	 * Configured update result.
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	public bool $updateResult = true;

	/**
	 * Configured create result.
	 *
	 * @var    bool
	 * @since  1.0.0
	 */
	public bool $createResult = true;

	/**
	 * Calls made to abstract item hooks.
	 *
	 * @var    array<int, array<int, mixed>>
	 * @since  1.0.0
	 */
	public array $calls = [];

	/**
	 * Run the protected item-save pipeline.
	 *
	 * @param   object  $item  Raw local item.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function saveItem(object $item): bool
	{
		return $this->save($item);
	}

	/**
	 * Return repository index settings accumulated by create operations.
	 *
	 * @return  array<int|string, mixed>
	 * @since   1.0.0
	 */
	public function recordedSettings(): array
	{
		return $this->settings;
	}

	/**
	 * Expose index-setting validation.
	 *
	 * @param   mixed  $repo      Repository value.
	 * @param   mixed  $settings  Index settings.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function invalidIndexRepo(mixed $repo, mixed $settings): bool
	{
		return $this->isInvalidIndexRepo($repo, $settings);
	}

	/**
	 * Expose index merge behavior.
	 *
	 * @param   string                 $repoGuid  Repository GUID.
	 * @param   array<string, mixed>   $settings  New settings.
	 *
	 * @return  array<string, mixed>
	 * @since   1.0.0
	 */
	public function mergeSettings(string $repoGuid, array $settings): array
	{
		return $this->mergeIndexSettings($repoGuid, $settings);
	}

	/**
	 * Expose repository-file synchronization.
	 *
	 * @param   object  $repo           Repository configuration.
	 * @param   string  $path           Target path.
	 * @param   string  $content        File content.
	 * @param   string  $updateMessage  Update message.
	 * @param   string  $createMessage  Create message.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function writeRepoFile(
		object $repo,
		string $path,
		string $content,
		string $updateMessage,
		string $createMessage
	): void
	{
		$this->setMainRepoFile($repo, $path, $content, $updateMessage, $createMessage);
	}

	/**
	 * Expose placeholder merging and replacement.
	 *
	 * @param   object  $repo   Repository configuration.
	 * @param   string  $value  Template value.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function replaceForRepo(object $repo, string $value): string
	{
		$this->setRepoPlaceholders($repo);

		return $this->updatePlaceholders($value);
	}

	/**
	 * Inject an optional dependency resolver.
	 *
	 * @param   ResolverInterface  $resolver  Dependency resolver.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function resolver(ResolverInterface $resolver): void
	{
		$this->resolver = $resolver;
	}

	/**
	 * Expose dependency extraction.
	 *
	 * @param   object  $item  Mapped item.
	 *
	 * @return  array|null
	 * @since   1.0.0
	 */
	public function dependencies(object $item): ?array
	{
		return $this->getDependencies($item);
	}

	/**
	 * Expose repository identity formatting.
	 *
	 * @param   object  $repo  Repository configuration.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function repoName(object $repo): string
	{
		return $this->getRepoName($repo);
	}

	/**
	 * Expose dependency-insensitive object comparison.
	 *
	 * @param   object|null  $first   First object.
	 * @param   object|null  $second  Second object.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function objectsEqual(?object $first, ?object $second): bool
	{
		return $this->areObjectsEqual($first, $second);
	}

	/**
	 * Update an existing remote item.
	 *
	 * @param   object  $item      Mapped item.
	 * @param   object  $existing  Existing remote item.
	 * @param   object  $repo      Repository configuration.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	protected function updateItem(object $item, object $existing, object $repo): bool
	{
		$this->calls[] = ['update', $item, $existing, $repo];

		return $this->updateResult;
	}

	/**
	 * Create a new remote item.
	 *
	 * @param   object  $item  Mapped item.
	 * @param   object  $repo  Repository configuration.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	protected function createItem(object $item, object $repo): bool
	{
		$this->calls[] = ['create', $item, $repo];

		return $this->createResult;
	}

	/**
	 * Record an existing-item readme update.
	 *
	 * @param   object  $item      Mapped item.
	 * @param   object  $existing  Existing remote item.
	 * @param   object  $repo      Repository configuration.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function updateItemReadme(object $item, object $existing, object $repo): void
	{
		$this->calls[] = ['update-readme', $item, $existing, $repo];
	}

	/**
	 * Record a new-item readme creation.
	 *
	 * @param   object  $item  Mapped item.
	 * @param   object  $repo  Repository configuration.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function createItemReadme(object $item, object $repo): void
	{
		$this->calls[] = ['create-readme', $item, $repo];
	}
}
