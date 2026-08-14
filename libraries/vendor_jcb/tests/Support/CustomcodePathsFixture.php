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


use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Extractor\Paths;


/**
 * Isolates installed-path discovery from association database lookups.
 *
 * @since  1.0.0
 */
final class CustomcodePathsFixture extends Paths
{
	/**
	 * Module IDs returned to the parent discovery algorithm.
	 *
	 * @var   array<int, int>|false
	 * @since 1.0.0
	 */
	private array|false $moduleIds;

	/**
	 * Module paths keyed by ID.
	 *
	 * @var   array<int, string|false>
	 * @since 1.0.0
	 */
	private array $modulePaths;

	/**
	 * Construct a deterministic path-discovery fixture.
	 *
	 * @param   Config                    $config       Compiler configuration.
	 * @param   array<int, int>|false     $moduleIds    Associated module IDs.
	 * @param   array<int, string|false>  $modulePaths  Resolved module paths.
	 *
	 * @since   1.0.0
	 */
	public function __construct(Config $config, array|false $moduleIds, array $modulePaths)
	{
		$this->config = $config;
		$this->moduleIds = $moduleIds;
		$this->modulePaths = $modulePaths;
	}

	/**
	 * Run the parent's installed-path discovery.
	 *
	 * @return  array<string, string>
	 * @since   1.0.0
	 */
	public function discover(): array
	{
		$this->active = [];
		$this->load();

		return $this->active;
	}

	/**
	 * Return deterministic module associations.
	 *
	 * @return  array<int, int>|false
	 * @since   1.0.0
	 */
	protected function getModuleIDs(): array|false
	{
		return $this->moduleIds;
	}

	/**
	 * Resolve one deterministic module path.
	 *
	 * @param   mixed  $id  Module identifier.
	 *
	 * @return  string|false
	 * @since   1.0.0
	 */
	protected function getModulePath($id): string|false
	{
		return $this->modulePaths[(int) $id] ?? false;
	}

	/**
	 * Exclude plugin associations from this path-focused fixture.
	 *
	 * @return  false
	 * @since   1.0.0
	 */
	protected function getPluginIDs(): false
	{
		return false;
	}
}
