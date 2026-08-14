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

use VDM\Joomla\Abstraction\Grep;

/**
 * Concrete Grep fixture exposing metadata enrichment and diagnostics.
 *
 * @since  1.0.0
 */
final class GrepFixture extends Grep
{
	/**
	 * Remote index diagnostics.
	 *
	 * @var    array<int, array<int, string|null>>
	 * @since  1.0.0
	 */
	public array $remoteMessages = [];

	/**
	 * Select the network target exposed by the Grep instance.
	 *
	 * @param   string|null  $target  Network target.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function networkTarget(?string $target): void
	{
		$this->target = $target;
	}

	/**
	 * Resolve the active branch for a repository.
	 *
	 * @param   object  $repo  Repository configuration.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	public function branchName(object $repo): ?string
	{
		return $this->getBranchName($repo);
	}

	/**
	 * Inject a repository metadata SHA into an item.
	 *
	 * @param   object  $item       Item to mutate.
	 * @param   object  $path       Repository path.
	 * @param   string  $targetPath  Target file path.
	 * @param   string  $branch      Target branch.
	 * @param   string  $sourceKey   Source-map key.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function addRepositorySha(
		object &$item,
		object $path,
		string $targetPath,
		string $branch,
		string $sourceKey
	): void
	{
		$this->setRepoItemSha($item, $path, $targetPath, $branch, $sourceKey);
	}

	/**
	 * Record a failed remote index load.
	 *
	 * @param   string       $message       Failure message.
	 * @param   string       $path          Repository path.
	 * @param   string       $repository    Repository name.
	 * @param   string       $organisation  Repository organisation.
	 * @param   string|null  $base          API base URL.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setRemoteIndexMessage(
		string $message,
		string $path,
		string $repository,
		string $organisation,
		?string $base
	): void
	{
		$this->remoteMessages[] = [$message, $path, $repository, $organisation, $base];
	}
}
