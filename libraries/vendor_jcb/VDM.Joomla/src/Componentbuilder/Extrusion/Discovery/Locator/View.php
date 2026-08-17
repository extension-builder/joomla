<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Discovery\Locator;


use VDM\Joomla\Componentbuilder\Extrusion\Abstraction\Locator;


/**
 * Finds and classifies the component's templates and layouts.
 *
 * The placement map decides what a file is: a layouts folder holds layouts, a
 * view's template folder holds templates, and the main default file is the
 * view's own template rather than a reusable one. Two things the map cannot
 * settle -- list against edit, and admin against custom admin -- are left to the
 * resolver, so this locator only reports what it can prove.
 *
 * @since 6.1.6
 */
final class View extends Locator
{
	/**
	 * The artifact kind this locator is responsible for.
	 *
	 * @return  string  The artifact kind key.
	 * @since   6.1.6
	 */
	public function kind(): string
	{
		return 'view';
	}

	/**
	 * The file extensions this locator's artifact uses.
	 *
	 * @return  array<string>  Lower-case extensions without the dot.
	 * @since   6.1.6
	 */
	protected function extensions(): array
	{
		return ['php'];
	}

	/**
	 * Locate every template and layout below a source root.
	 *
	 * @param   string  $root  The resolved source root.
	 *
	 * @return  array<int, array{path: string, tier: string, name: string|null}>  Located artifacts.
	 * @since   6.1.6
	 */
	public function locate(string $root): array
	{
		$found = [];

		foreach (['layouts', 'site_layouts'] as $kind)
		{
			foreach ($this->mapped($root, $kind) as $directory)
			{
				foreach ($this->php($root, $directory) as $path)
				{
					$found[$path] = $this->entry($path, 'map', $this->name($path));
					$found[$path]['role'] = 'layout';
				}
			}
		}

		foreach (['tmpl_dir', 'site_tmpl_dir'] as $kind)
		{
			foreach ($this->mapped($root, $kind) as $directory)
			{
				foreach ($this->templates($root, $directory) as $path => $role)
				{
					$found[$path] = $this->entry($path, 'map', $this->name($path));
					$found[$path]['role'] = $role;
				}
			}
		}

		return $this->recorded(array_values($found));
	}

	/**
	 * The artifact name a view file is stored under.
	 *
	 * @param   string  $path  Absolute path to the file.
	 *
	 * @return  string  The lower-case artifact name.
	 * @since   6.1.6
	 */
	public function name(string $path): string
	{
		return strtolower(pathinfo($path, PATHINFO_FILENAME));
	}

	/**
	 * Every contained PHP file directly inside one directory.
	 *
	 * @param   string  $root       The resolved source root.
	 * @param   string  $directory  The directory to list.
	 *
	 * @return  array<string>  Absolute contained paths.
	 * @since   6.1.6
	 */
	protected function php(string $root, string $directory): array
	{
		$found = [];

		foreach ((array) @glob($directory . '/*.php') as $path)
		{
			$contained = is_string($path) ? $this->scanner->contain($root, $path) : null;

			if ($contained !== null)
			{
				$found[] = $contained;
			}
		}

		return $found;
	}

	/**
	 * Classify the template files below a templates root.
	 *
	 * A default_<x>.php file beside a view's default.php is a reusable JCB
	 * template; default.php itself is the view's own main template.
	 *
	 * @param   string  $root       The resolved source root.
	 * @param   string  $directory  The templates root.
	 *
	 * @return  array<string, string>  Absolute path keyed to its role.
	 * @since   6.1.6
	 */
	protected function templates(string $root, string $directory): array
	{
		$found = [];

		foreach ((array) @glob($directory . '/*', GLOB_ONLYDIR) as $viewDirectory)
		{
			if (!is_string($viewDirectory))
			{
				continue;
			}

			foreach ($this->php($root, $viewDirectory) as $path)
			{
				$base = strtolower(basename($path));

				if ($base === 'default.php')
				{
					$found[$path] = 'main';

					continue;
				}

				if (str_starts_with($base, 'default_'))
				{
					$found[$path] = 'template';

					continue;
				}

				$found[$path] = 'view';
			}

			foreach ((array) @glob($viewDirectory . '/tmpl/*.php') as $nested)
			{
				$contained = is_string($nested) ? $this->scanner->contain($root, $nested) : null;

				if ($contained === null)
				{
					continue;
				}

				$base = strtolower(basename($contained));
				$found[$contained] = $base === 'default.php'
					? 'main'
					: (str_starts_with($base, 'default_') ? 'template' : 'view');
			}
		}

		return $found;
	}
}
