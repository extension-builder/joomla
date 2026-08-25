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
 * Reads the edit screen a component already builds for each of its views.
 *
 * An edit screen states its own shape. The template opens one tab per section,
 * naming it and rendering its columns from layouts of the view's own folder,
 * and each of those layouts lists the fields of that column in the order they
 * are shown. Between them they say exactly what JCB stores: the tabs of the
 * view in their order, and for every field the tab it sits on, the column it
 * takes, and where in that column it stands.
 *
 * A tab whose columns are not layouts of this view is the compiler's own
 * furniture -- the Publishing section it always adds, the Permissions section
 * it renders from the rules field -- and is left out, because JCB adds those
 * back itself and storing them would give the view two of each.
 *
 * @since 6.1.8
 */
final class Screen
{
	/**
	 * The column names JCB's compiler writes, keyed by the alignment it stores.
	 *
	 * The compiler's own table, read in reverse: it turns the stored alignment
	 * into the layout's name, and this turns that name back into the alignment
	 * (Compiler\Architecture\AdminView\TabLayoutFields::$alignmentOptions).
	 *
	 * @var    array<string, int>
	 * @since  6.1.8
	 */
	private const ALIGNMENTS = [
		'left' => 1, 'right' => 2, 'fullwidth' => 3, 'above' => 4,
		'under' => 5, 'leftside' => 6, 'rightside' => 7
	];

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
	 * Read every edit screen below one root into the source registry.
	 *
	 * @param   string  $root  The resolved source root.
	 *
	 * @return  int  The number of screens read.
	 * @since   6.1.8
	 */
	public function establish(string $root): int
	{
		$read = 0;

		foreach ($this->directories($root, 'tmpl_dir') as $directory)
		{
			foreach ($this->folders($directory) as $view => $path)
			{
				if ($this->one($root, (string) $view, $path))
				{
					$read++;
				}
			}
		}

		if ($read > 0)
		{
			$this->report->set('source.screens', $read);
		}

		return $read;
	}

	/**
	 * Read one view's edit screen.
	 *
	 * @param   string  $root    The resolved source root.
	 * @param   string  $view    The view folder name.
	 * @param   string  $folder  Absolute path to the view's template folder.
	 *
	 * @return  bool  True when the screen stated tabs of its own.
	 * @since   6.1.8
	 */
	protected function one(string $root, string $view, string $folder): bool
	{
		$template = $this->template($folder);

		if ($template === null)
		{
			return false;
		}

		$code = $this->scanner->read($template);

		if ($code === null || $code === '')
		{
			return false;
		}

		$tabs = $this->tabs($code);

		if ($tabs === [])
		{
			return false;
		}

		$rendered = $this->rendered($code, $view);
		$blocks = $this->blocks($code);
		$number = 0;

		foreach ($tabs as $tab)
		{
			$columns = $this->columns($tab['key'], $rendered);

			// a tab whose columns are not this view's own layouts is the
			// compiler's furniture, not a tab the component states -- its
			// fields still count, because the compiler puts them back in the
			// section it generates for them
			if ($columns === [])
			{
				$this->standalone($root, $view, $tab, $blocks[$tab['key']] ?? '', $number);

				continue;
			}

			$this->source->set('screen.' . $view . '.tabs.' . $number . '.key', $tab['key']);
			$this->source->set('screen.' . $view . '.tabs.' . $number . '.label', $tab['label']);
			$number++;

			foreach ($columns as $layout => $alignment)
			{
				$this->place($root, $view, $tab['key'], $layout, $alignment, false);
			}
		}

		if ($number === 0)
		{
			return false;
		}

		$this->source->set('screen.' . $view . '.tab_count', $number);

		return true;
	}

	/**
	 * Read a tab that renders none of this view's column layouts.
	 *
	 * Three things look alike here and are told apart by what the tab shows.
	 * A section the compiler generates for a view's own publishing fields
	 * renders this view's layouts without a column in their name. The
	 * permissions section renders the rules field and nothing else. Anything
	 * else is a tab someone added to the view by hand, and its markup is the
	 * tab's own content, which JCB keeps beside the view rather than in it.
	 *
	 * @param   string                            $root    The resolved source root.
	 * @param   string                            $view    The view folder name.
	 * @param   array{key: string, label: string}  $tab     The tab.
	 * @param   string                            $body    The tab's own markup.
	 * @param   int                               $number  How many tabs the view states so far.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	protected function standalone(
		string $root,
		string $view,
		array $tab,
		string $body,
		int $number
	): void
	{
		if ($body === '')
		{
			return;
		}

		if ($this->rendered($body, $view) !== [])
		{
			$this->generated($root, $view, $tab['key'], $body);

			return;
		}

		// the permissions section is the rules field, which JCB adds itself
		if (str_contains($body, "getInput('rules')")
			|| str_contains($body, 'getInput("rules")'))
		{
			return;
		}

		$html = trim($body);

		if ($html === '')
		{
			return;
		}

		$index = (int) $this->source->get('screen.' . $view . '.custom_count', 0);
		$path = 'screen.' . $view . '.custom.' . $index;

		$this->source->set($path . '.key', $tab['key']);
		$this->source->set($path . '.label', $tab['label']);
		$this->source->set($path . '.after', $number);
		$this->source->set($path . '.html', $html);
		$this->source->set('screen.' . $view . '.custom_count', $index + 1);
	}

	/**
	 * Record the fields a generated section of the screen shows.
	 *
	 * @param   string  $root  The resolved source root.
	 * @param   string  $view  The view folder name.
	 * @param   string  $tab   The tab key the section stands under.
	 * @param   string  $body  The tab's own markup.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	protected function generated(string $root, string $view, string $tab, string $body): void
	{
		if ($body === '')
		{
			return;
		}

		$alignment = 0;

		foreach (array_keys($this->rendered($body, $view)) as $layout)
		{
			// the columns of a generated section are the order they are
			// rendered in, which is the only thing its markup states
			$alignment = min($alignment + 1, 3);

			$this->place($root, $view, $tab, (string) $layout, $alignment, true);
		}
	}

	/**
	 * The markup of every tab, keyed by the tab it belongs to.
	 *
	 * @param   string  $code  The template's code.
	 *
	 * @return  array<string, string>  Tab key to its own markup.
	 * @since   6.1.8
	 */
	protected function blocks(string $code): array
	{
		$pattern = '/uitab\.addTab[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]'
			. '(.*?)uitab\.endTab/s';

		if (preg_match_all($pattern, $code, $found, PREG_SET_ORDER) === false)
		{
			return [];
		}

		$blocks = [];

		foreach ($found as $match)
		{
			$key = strtolower(trim($match[1]));

			if ($key !== '' && !isset($blocks[$key]))
			{
				$blocks[$key] = (string) $match[2];
			}
		}

		return $blocks;
	}

	/**
	 * Record where one column's layout places its fields.
	 *
	 * @param   string  $root       The resolved source root.
	 * @param   string  $view       The view folder name.
	 * @param   string  $tab        The tab key the column belongs to.
	 * @param   string  $layout     The layout name.
	 * @param   int     $alignment  The alignment the column stands for.
	 * @param   bool    $generated  Whether the compiler generates this section.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	protected function place(
		string $root,
		string $view,
		string $tab,
		string $layout,
		int $alignment,
		bool $generated
	): void
	{
		$path = $this->layout($root, $view, $layout);

		if ($path === null)
		{
			return;
		}

		$code = $this->scanner->read($path);

		if ($code === null || $code === '')
		{
			return;
		}

		$order = 0;

		foreach ($this->fields($code) as $field)
		{
			$order++;
			$key = 'screen.' . $view . '.place.' . strtolower($field);

			if ((string) $this->source->get($key . '.tab', '') !== '')
			{
				continue;
			}

			$this->source->set($key . '.tab', $tab);
			$this->source->set($key . '.alignment', $alignment);
			$this->source->set($key . '.order', $order);
			$this->source->set($key . '.generated', $generated ? 1 : 0);
		}
	}

	/**
	 * Every tab one template opens, in the order it opens them.
	 *
	 * @param   string  $code  The template's code.
	 *
	 * @return  array<int, array{key: string, label: string}>  The tabs.
	 * @since   6.1.8
	 */
	protected function tabs(string $code): array
	{
		$pattern = '/uitab\.addTab[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]'
			. '\s*,\s*Text::_\(\s*[\'"]([^\'"]+)[\'"]/';

		if (preg_match_all($pattern, $code, $found, PREG_SET_ORDER) === false)
		{
			return [];
		}

		$tabs = [];
		$seen = [];

		foreach ($found as $match)
		{
			$key = strtolower(trim($match[1]));

			if ($key === '' || isset($seen[$key]))
			{
				continue;
			}

			$seen[$key] = true;
			$tabs[] = ['key' => $key, 'label' => trim($match[2])];
		}

		return $tabs;
	}

	/**
	 * Every layout of this view the template renders.
	 *
	 * @param   string  $code  The template's code.
	 * @param   string  $view  The view folder name.
	 *
	 * @return  array<string, bool>  The layout names.
	 * @since   6.1.8
	 */
	protected function rendered(string $code, string $view): array
	{
		$pattern = '/render\(\s*[\'"]' . preg_quote($view, '/')
			. '\.([A-Za-z0-9_]+)[\'"]/';

		if (preg_match_all($pattern, $code, $found) === false)
		{
			return [];
		}

		$names = [];

		foreach ((array) ($found[1] ?? []) as $name)
		{
			$names[strtolower($name)] = true;
		}

		return $names;
	}

	/**
	 * The columns one tab renders, keyed by layout name.
	 *
	 * @param   string               $tab       The tab key.
	 * @param   array<string, bool>  $rendered  The layouts the template renders.
	 *
	 * @return  array<string, int>  Layout name keyed to the alignment it stands for.
	 * @since   6.1.8
	 */
	protected function columns(string $tab, array $rendered): array
	{
		$columns = [];

		foreach (self::ALIGNMENTS as $name => $alignment)
		{
			$layout = $tab . '_' . $name;

			if (isset($rendered[$layout]))
			{
				$columns[$layout] = $alignment;
			}
		}

		return $columns;
	}

	/**
	 * The fields one column layout lists, in the order it lists them.
	 *
	 * The compiler writes the column's fields as the fallback of the method
	 * the model may override, so the array in the file is the placement the
	 * component was built with.
	 *
	 * @param   string  $code  The layout's code.
	 *
	 * @return  array<int, string>  The field names.
	 * @since   6.1.8
	 */
	protected function fields(string $code): array
	{
		$pattern = '/\?:\s*(?:array\s*\(|\[)(.*?)(?:\)|\])\s*;/s';

		if (preg_match($pattern, $code, $found) !== 1)
		{
			return [];
		}

		if (preg_match_all('/[\'"]([A-Za-z0-9_]+)[\'"]/', $found[1], $names) === false)
		{
			return [];
		}

		return array_values(array_unique((array) ($names[1] ?? [])));
	}

	/**
	 * The template file of one view folder.
	 *
	 * @param   string  $folder  Absolute path to the view's template folder.
	 *
	 * @return  string|null  Absolute path, or null when there is none.
	 * @since   6.1.8
	 */
	protected function template(string $folder): ?string
	{
		foreach (['default.php', 'tmpl/default.php', 'edit.php', 'tmpl/edit.php'] as $relative)
		{
			$path = $folder . '/' . $relative;

			if (is_file($path))
			{
				return $path;
			}
		}

		return null;
	}

	/**
	 * The layout file one view's column layout stands in.
	 *
	 * @param   string  $root    The resolved source root.
	 * @param   string  $view    The view folder name.
	 * @param   string  $layout  The layout name.
	 *
	 * @return  string|null  Absolute path, or null when there is none.
	 * @since   6.1.8
	 */
	protected function layout(string $root, string $view, string $layout): ?string
	{
		foreach ($this->directories($root, 'layouts') as $directory)
		{
			$path = $directory . '/' . $view . '/' . $layout . '.php';

			if (is_file($path))
			{
				return $path;
			}
		}

		return null;
	}

	/**
	 * Every directory one placement kind resolves to below a root.
	 *
	 * @param   string  $root  The resolved source root.
	 * @param   string  $kind  The placement kind.
	 *
	 * @return  array<string>  Absolute directory paths.
	 * @since   6.1.8
	 */
	protected function directories(string $root, string $kind): array
	{
		$found = [];
		$layout = $this->selector->layout();
		$tokens = ['option' => (string) $this->source->get('code_name', ''), 'tag' => 'en-GB'];

		foreach ($layout->candidates($kind, $tokens) as $relative)
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
	 * Every view folder directly inside one directory.
	 *
	 * @param   string  $directory  The directory to list.
	 *
	 * @return  array<string, string>  View name keyed to its absolute path.
	 * @since   6.1.8
	 */
	protected function folders(string $directory): array
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

			if (is_dir($path))
			{
				$found[strtolower($entry)] = $path;
			}
		}

		closedir($handle);
		ksort($found);

		return $found;
	}
}
