<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    25th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Discovery;


use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;


/**
 * Reads how the component's own controllers relate its screens.
 *
 * A Joomla component states the relationship between its screens in its
 * controllers, and states it the same way whoever wrote it: the controller of
 * a list screen proxies getModel() to the model of the screen that edits one
 * record, while a screen that stands on its own names itself. So
 * "admins_fields" answering with the model "admin_fields" is that view's list,
 * whatever it is called -- and "compiler" answering with "compiler" is a
 * screen of its own.
 *
 * That single reading settles two things nothing else can: the real plural
 * name of every view (no plural rule invents "admin_fieldses" where the
 * component says "admins_fields"), and which folders are a table view's own
 * generated output rather than a custom admin view.
 *
 * @since 6.1.8
 */
final class Mvc
{
	/**
	 * The Scanner Class.
	 *
	 * @var    Scanner
	 * @since  6.1.8
	 */
	protected Scanner $scanner;

	/**
	 * The Selector Class.
	 *
	 * @var    Selector
	 * @since  6.1.8
	 */
	protected Selector $selector;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.8
	 */
	protected Source $source;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.8
	 */
	protected Report $report;

	/**
	 * Constructor.
	 *
	 * @param   Scanner   $scanner   The source tree scanner.
	 * @param   Selector  $selector  The layout selector.
	 * @param   Source    $source    The source identity registry.
	 * @param   Report    $report    The run report registry.
	 *
	 * @since   6.1.8
	 */
	public function __construct(
		Scanner $scanner,
		Selector $selector,
		Source $source,
		Report $report
	)
	{
		$this->scanner = $scanner;
		$this->selector = $selector;
		$this->source = $source;
		$this->report = $report;
	}

	/**
	 * Read every controller below one root into the source registry.
	 *
	 * @param   string  $root  The resolved source root.
	 *
	 * @return  int  The number of controllers read.
	 * @since   6.1.8
	 */
	public function establish(string $root): int
	{
		$read = 0;

		foreach ($this->directories($root) as $directory)
		{
			foreach ($this->files($directory) as $path)
			{
				if ($this->one($path))
				{
					$read++;
				}
			}
		}

		if ($read > 0)
		{
			$this->report->set('source.controllers', (int) $this->source->get('mvc_count', 0));
		}

		return $read;
	}

	/**
	 * Read one controller file.
	 *
	 * @param   string  $path  Absolute path to the controller.
	 *
	 * @return  bool  True when the file named a view.
	 * @since   6.1.8
	 */
	protected function one(string $path): bool
	{
		$code = $this->scanner->read($path);

		if ($code === null || $code === '')
		{
			return false;
		}

		$class = $this->className($code);

		if ($class === '')
		{
			return false;
		}

		$view = $this->viewOf($class);

		if ($view === '')
		{
			return false;
		}

		$model = $this->modelOf($code);
		$base = $this->baseOf($code, $class);

		$this->source->set('mvc.' . $view . '.class', $class);
		$this->source->set('mvc.' . $view . '.extends', $base);
		$this->source->set('mvc.' . $view . '.model', $model);
		$this->source->set('mvc_count', (int) $this->source->get('mvc_count', 0) + 1);

		// a controller answering with another view's model is that view's list
		// screen: the component says so itself, in the only place it can
		if ($model !== '' && $model !== $view)
		{
			$standing = (string) $this->source->get('mvc_list.' . $model, '');

			if ($standing === '')
			{
				$this->source->set('mvc_list.' . $model, $view);
			}
			elseif ($standing !== $view)
			{
				$this->report->set(
					'source.mvc.contested.' . $model,
					$standing . ' and ' . $view . ' both answer for this view'
				);
			}

			$this->source->set('mvc_of.' . $view, $model);
		}

		return true;
	}

	/**
	 * Every controller directory below one root.
	 *
	 * @param   string  $root  The resolved source root.
	 *
	 * @return  array<string>  Absolute directory paths.
	 * @since   6.1.8
	 */
	protected function directories(string $root): array
	{
		$found = [];
		$layout = $this->selector->layout();
		$tokens = ['option' => (string) $this->source->get('code_name', ''), 'tag' => 'en-GB'];

		foreach ($layout->candidates('controller_dir', $tokens) as $relative)
		{
			$path = $this->scanner->resolve($root, $relative);

			if ($path !== null && is_dir($path))
			{
				$found[$path] = true;
			}
		}

		return array_keys($found);
	}

	/**
	 * Every PHP file directly inside one directory.
	 *
	 * @param   string  $directory  The directory to list.
	 *
	 * @return  array<string>  Absolute file paths.
	 * @since   6.1.8
	 */
	protected function files(string $directory): array
	{
		$found = [];
		$handle = @opendir($directory);

		if ($handle === false)
		{
			return $found;
		}

		while (($entry = readdir($handle)) !== false)
		{
			if ($entry === '.' || $entry === '..')
			{
				continue;
			}

			$path = $directory . '/' . $entry;

			if (is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php')
			{
				$found[] = $path;
			}
		}

		closedir($handle);
		sort($found);

		return $found;
	}

	/**
	 * The class one controller file declares.
	 *
	 * @param   string  $code  The file's code.
	 *
	 * @return  string  The class name, or an empty string.
	 * @since   6.1.8
	 */
	protected function className(string $code): string
	{
		if (preg_match('/\bclass\s+([A-Za-z_][A-Za-z0-9_]*)/', $code, $found) === 1)
		{
			return $found[1];
		}

		return '';
	}

	/**
	 * The view a controller class name names.
	 *
	 * Modern components name the class for the view and suffix it; the legacy
	 * naming prefixes the component and the word Controller instead. Both say
	 * the same thing, and the word Controller is the marker in each.
	 *
	 * @param   string  $class  The controller class name.
	 *
	 * @return  string  The lower-case view name, or an empty string.
	 * @since   6.1.8
	 */
	protected function viewOf(string $class): string
	{
		if (str_ends_with($class, 'Controller'))
		{
			return strtolower(substr($class, 0, -10));
		}

		$position = strrpos($class, 'Controller');

		if ($position === false)
		{
			return '';
		}

		return strtolower(substr($class, $position + 10));
	}

	/**
	 * The model a controller answers with by default.
	 *
	 * @param   string  $code  The file's code.
	 *
	 * @return  string  The lower-case model name, or an empty string.
	 * @since   6.1.8
	 */
	protected function modelOf(string $code): string
	{
		$pattern = '/function\s+getModel\s*\(\s*\$[A-Za-z_][A-Za-z0-9_]*\s*=\s*'
			. '[\'"]([A-Za-z0-9_]+)[\'"]/';

		if (preg_match($pattern, $code, $found) === 1)
		{
			return strtolower($found[1]);
		}

		return '';
	}

	/**
	 * The base class a controller extends.
	 *
	 * @param   string  $code   The file's code.
	 * @param   string  $class  The controller class name.
	 *
	 * @return  string  The base class short name, or an empty string.
	 * @since   6.1.8
	 */
	protected function baseOf(string $code, string $class): string
	{
		$pattern = '/\bclass\s+' . preg_quote($class, '/')
			. '\s+extends\s+\\\\?([A-Za-z_][A-Za-z0-9_\\\\]*)/';

		if (preg_match($pattern, $code, $found) !== 1)
		{
			return '';
		}

		$base = $found[1];
		$position = strrpos($base, '\\');

		return $position === false ? $base : substr($base, $position + 1);
	}
}
