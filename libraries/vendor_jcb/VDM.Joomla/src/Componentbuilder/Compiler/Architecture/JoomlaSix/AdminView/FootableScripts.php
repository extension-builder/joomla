<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    23rd October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminView;

use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminView\FootableScripts as SharedFootableScripts;

/**
 * Joomla six Footable assets and their explicit dependencies.
 *
 * @since 6.2.0
 */
final class FootableScripts extends SharedFootableScripts
{
	/**
	 * Build the Footable two asset graph.
	 *
	 * @param   bool  $init  Whether to initialise tables.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	protected function getVersionTwo(bool $init): string
	{
		$root = 'media/com_' . $this->config->component_code_name . '/footable-v2/';
		$code = $this->asset('style', $root . 'css/footable.core.min.css');
		$code .= PHP_EOL . "\t\tif (!isset(\$this->fooTableStyle) || 0 == \$this->fooTableStyle)\n\t\t{";
		$code .= $this->asset('style', $root . 'css/footable.metro.min.css');
		$code .= PHP_EOL . "\t\t}\n\t\telseif (1 == \$this->fooTableStyle)\n\t\t{";
		$code .= $this->asset('style', $root . 'css/footable.standalone.min.css');
		$code .= PHP_EOL . "\t\t}";
		$dependencies = ['jquery', 'jquery-noconflict'];

		foreach (['footable.js', 'footable.sort.js', 'footable.filter.js', 'footable.paginate.js'] as $file)
		{
			$uri = $root . 'js/' . $file;
			$code .= $this->asset('script', $uri, $dependencies);
			$dependencies[] = 'jcb.script.' . hash('sha256', $uri);
		}

		if ($init)
		{
			$code .= PHP_EOL . '\t\t$footable = "jQuery(document).ready(function() { jQuery(\'.footable\').footable(); jQuery(\'.nav-tabs\').on(\'click\', \'li\', function() { setTimeout(tableFix, 10); }); }); function tableFix() { jQuery(\'.footable\').trigger(\'footable_resize\'); }";';
			$code .= $this->inline($dependencies);
		}

		return str_replace('\\t', "\t", $code);
	}

	/**
	 * Build the Footable three asset graph.
	 *
	 * @param   bool  $init  Whether to initialise tables.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	protected function getVersionThree(bool $init): string
	{
		$root = 'media/com_' . $this->config->component_code_name . '/footable-v3/';
		$code = $this->asset('style', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css');
		$code .= $this->asset('style', $root . 'css/footable.standalone.min.css');
		$uri = $root . 'js/footable.min.js';
		$code .= $this->asset('script', $uri, ['jquery', 'jquery-noconflict']);

		if ($init)
		{
			$code .= PHP_EOL . '\t\t$footable = "jQuery(document).ready(function() { jQuery(\'.footable\').footable(); });";';
			$code .= $this->inline(['jcb.script.' . hash('sha256', $uri)]);
		}

		return str_replace('\\t', "\t", $code);
	}

	/**
	 * Register an asset once, then activate it.
	 *
	 * @param   string  $type          Asset type.
	 * @param   string  $uri           Asset URI.
	 * @param   array   $dependencies  Required asset names.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	protected function asset(string $type, string $uri, array $dependencies = []): string
	{
		$name = var_export('jcb.' . $type . '.' . hash('sha256', $uri), true);
		$path = var_export($uri, true);
		$requires = var_export($dependencies, true);
		$method = ucfirst($type);

		return PHP_EOL . "\t\t\$jcbFootableAssets = \$this->getDocument()->getWebAssetManager();"
			. PHP_EOL . "\t\tif (!\$jcbFootableAssets->assetExists('{$type}', {$name}))"
			. PHP_EOL . "\t\t{"
			. PHP_EOL . "\t\t\t\$jcbFootableAssets->register{$method}({$name}, {$path}, ['version' => 'auto'], [], {$requires});"
			. PHP_EOL . "\t\t}"
			. PHP_EOL . "\t\t\$jcbFootableAssets->use{$method}({$name});";
	}

	/**
	 * Initialise only after every required library has loaded.
	 *
	 * @param   array  $dependencies  Required script names.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	protected function inline(array $dependencies): string
	{
		$name = var_export('com_' . $this->config->component_code_name . '.footable.init', true);

		return PHP_EOL . "\t\t\$this->getDocument()->getWebAssetManager()->addInlineScript(\$footable, ['name' => {$name}], [], "
			. var_export($dependencies, true) . ');' . PHP_EOL;
	}
}
