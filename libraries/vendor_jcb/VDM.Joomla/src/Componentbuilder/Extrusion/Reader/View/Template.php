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

namespace VDM\Joomla\Componentbuilder\Extrusion\Reader\View;


use VDM\Joomla\Componentbuilder\Extrusion\Interfaces\ReaderInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View as ViewRegistry;


/**
 * Reads one template source file into the View registry.
 *
 * JCB's template table is the layout table under another name: php_view carries
 * the PHP above the closing tag, template carries the HTML after it, and
 * add_php_view says whether the PHP part exists at all. Which of the two tables
 * a file belongs to is a placement question answered upstream by the layout map,
 * so this reader only records what it is handed, under its own prefix.
 *
 * Both columns declare store: base64 and the Data pipeline applies that encoding
 * itself, so the parts are stored exactly as they were read. Encoding them here
 * would double encode and the compiler would write base64 into the file. The
 * source is never included, required, or evaluated.
 *
 * @since 6.1.6
 */
final class Template implements ReaderInterface
{
	/**
	 * The View Registry.
	 *
	 * @var    ViewRegistry
	 * @since  6.1.6
	 */
	protected ViewRegistry $view;

	/**
	 * The PHP and HTML splitter.
	 *
	 * @var    Split
	 * @since  6.1.6
	 */
	protected Split $split;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	protected Report $report;

	/**
	 * Constructor.
	 *
	 * @param   ViewRegistry  $view    The classified view layer registry.
	 * @param   Split         $split   The PHP and HTML splitter.
	 * @param   Report        $report  The run report registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(ViewRegistry $view, Split $split, Report $report)
	{
		$this->view = $view;
		$this->split = $split;
		$this->report = $report;
	}

	/**
	 * The JCB template code name a generated file name carries.
	 *
	 * JCB writes a template whose code name is x into a view's folder as
	 * default_x.php, so removing that prefix recovers the code name exactly rather
	 * than by resemblance. It also collapses the same template used by several views
	 * onto one record, which is correct: they are one template, materialised once per
	 * view that asked for it.
	 *
	 * A file with no such prefix keeps its own name, because there is nothing to
	 * recover and the file name is the best code name available.
	 *
	 * @param   string  $name  The file name without its extension.
	 *
	 * @return  string  The template code name.
	 * @since   6.1.6
	 */
	public function alias(string $name): string
	{
		$stripped = preg_replace('/^default_/i', '', trim($name)) ?? $name;

		return trim($stripped) === '' ? trim($name) : $stripped;
	}

	/**
	 * Read one template source file into the View registry.
	 *
	 * @param   string       $path  Absolute path to the template file.
	 * @param   string|null  $name  The template name, derived from the file name when null.
	 *
	 * @return  bool  True when the file yielded a PHP part, an HTML part, or both.
	 * @since   6.1.6
	 */
	public function read(string $path, ?string $name = null): bool
	{
		$raw = $this->alias(trim($name ?? pathinfo($path, PATHINFO_FILENAME)));
		$key = $this->key($raw);
		$base = 'template.' . $key;
		$this->report->set($base . '.path', $path);

		if ($raw !== $key)
		{
			$this->report->set($base . '.renamed', $raw);
		}

		$content = $this->content($path);

		if ($content === null)
		{
			$this->report->set($base . '.error', 'the file could not be read');

			return false;
		}

		$parts = $this->split->split($content);

		if ($parts['php'] === '' && $parts['html'] === '')
		{
			$this->report->set($base . '.error', 'the file held no php and no html');

			return false;
		}

		$php = $parts['add_php'] ? 1 : 0;
		$held = $this->view->get($base . '.template');

		if (is_string($held) && $held !== $parts['html'])
		{
			// One code name, two different bodies. A JCB template code name is unique,
			// so the second cannot simply take the first's place: whichever lost would
			// vanish with nothing said about it. The first claim stands and the
			// disagreement is named, which is the one honest thing to do until a caller
			// tells us which of the two it wants.
			$this->report->set(
				'template.collision.' . $key . '.' . md5($path),
				$path . ' differs from ' . (string) $this->view->get($base . '.path', '')
			);

			return false;
		}

		$this->view->set($base . '.name', $key);
		$this->view->set($base . '.php_view', $parts['php']);
		$this->view->set($base . '.template', $parts['html']);
		$this->view->set($base . '.add_php_view', $php);
		$this->view->set($base . '.path', $path);

		$this->report->set($base . '.add_php_view', $php);
		$this->report->set($base . '.php_view', strlen($parts['php']));
		$this->report->set($base . '.template', strlen($parts['html']));

		return true;
	}

	/**
	 * Sanitise one value into a safe registry path segment.
	 *
	 * The registry addresses state by a dot separated path and discards empty
	 * segments, so anything that is not a plain word character collapses to an
	 * underscore and an empty result becomes a stable placeholder.
	 *
	 * @param   string  $value  The raw value.
	 *
	 * @return  string  A safe single path segment.
	 * @since   6.1.6
	 */
	public function key(string $value): string
	{
		$key = preg_replace('/[^A-Za-z0-9_]+/', '_', trim($value));

		return $key === null || $key === '' ? 'unknown' : $key;
	}

	/**
	 * Read the file without allowing a failure to surface as a warning.
	 *
	 * @param   string  $path  Absolute path to the template file.
	 *
	 * @return  string|null  The content, or null when it is unusable.
	 * @since   6.1.6
	 */
	protected function content(string $path): ?string
	{
		if ($path === '' || !is_file($path))
		{
			return null;
		}

		$content = @file_get_contents($path);

		return $content === false || trim($content) === '' ? null : $content;
	}
}
