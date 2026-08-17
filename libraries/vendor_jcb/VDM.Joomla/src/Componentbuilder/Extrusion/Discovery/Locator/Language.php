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
 * Finds the component's language catalogue.
 *
 * The main file is ordered before the system file, because the reader lets the
 * first value for a constant win and the main catalogue is the better source.
 *
 * @since 6.1.6
 */
final class Language extends Locator
{
	/**
	 * The artifact kind this locator is responsible for.
	 *
	 * @return  string  The artifact kind key.
	 * @since   6.1.6
	 */
	public function kind(): string
	{
		return 'language';
	}

	/**
	 * The file extensions this locator's artifact uses.
	 *
	 * @return  array<string>  Lower-case extensions without the dot.
	 * @since   6.1.6
	 */
	protected function extensions(): array
	{
		return ['ini'];
	}

	/**
	 * Locate every language file below a source root.
	 *
	 * @param   string  $root  The resolved source root.
	 *
	 * @return  array<int, array{path: string, tier: string, name: string|null}>  Located artifacts.
	 * @since   6.1.6
	 */
	public function locate(string $root): array
	{
		$tag = (string) $this->source->get('tag', 'en-GB');
		$found = [];

		foreach (['language_file', 'language_sys'] as $kind)
		{
			foreach ($this->mapped($root, $kind, ['tag' => $tag]) as $path)
			{
				$found[$path] = $this->entry($path, 'map', $tag);
			}
		}

		if ($found === [])
		{
			$option = $this->option();
			$main = [];
			$system = [];

			foreach ($this->scanned($root) as $path)
			{
				if (!$this->heuristic->isLanguage($this->scanner->read($path) ?? '', $option))
				{
					continue;
				}

				if (str_contains(strtolower(basename($path)), '.sys.'))
				{
					$system[$path] = $this->entry($path, 'signature', $tag);

					continue;
				}

				$main[$path] = $this->entry($path, 'signature', $tag);
			}

			$found = $main + $system;
		}

		return $this->recorded(array_values($found));
	}
}
