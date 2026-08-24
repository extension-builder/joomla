<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    24th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Reader\View;


use VDM\Joomla\Componentbuilder\Extrusion\Interfaces\ReaderInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View as ViewRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Text;


/**
 * Reads an administrator view's own template as a custom admin view candidate.
 *
 * An administrator view whose default template belongs to a table is compiled
 * by JCB from the view's field set, so that file is generated output. But a
 * component's administrator is rarely tables alone: an import screen, a
 * dashboard, a wizard -- views that JCB models as custom admin views, whose
 * whole body is exactly this template. Every administrator main template is
 * therefore read as a candidate, and the writer keeps only the views that no
 * resolved table view answers for.
 *
 * The split is the same as a site view's: the HTML after the final closing
 * tag is the view's default column and the PHP above it its php_view. Both
 * columns declare store: base64 and the Data pipeline applies that itself.
 *
 * @since 6.1.8
 */
final class CustomAdminView implements ReaderInterface
{
	/**
	 * The View Registry.
	 *
	 * @var    ViewRegistry
	 * @since  6.1.8
	 */
	protected ViewRegistry $view;

	/**
	 * The PHP and HTML splitter.
	 *
	 * @var    Split
	 * @since  6.1.8
	 */
	protected Split $split;

	/**
	 * The Text Resolver.
	 *
	 * @var    Text
	 * @since  6.1.8
	 */
	protected Text $text;

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
	 * @param   ViewRegistry  $view    The classified view layer registry.
	 * @param   Split         $split   The PHP and HTML splitter.
	 * @param   Text          $text    The readable text resolver.
	 * @param   Report        $report  The run report registry.
	 *
	 * @since   6.1.8
	 */
	public function __construct(
		ViewRegistry $view,
		Split $split,
		Text $text,
		Report $report
	)
	{
		$this->view = $view;
		$this->split = $split;
		$this->text = $text;
		$this->report = $report;
	}

	/**
	 * Read one administrator template as a custom admin view candidate.
	 *
	 * @param   string       $path  Absolute path to the default template.
	 * @param   string|null  $name  The view name, derived from the folder when null.
	 *
	 * @return  bool  True when the file yielded a body.
	 * @since   6.1.8
	 */
	public function read(string $path, ?string $name = null): bool
	{
		$raw = trim((string) $name);

		if ($raw === '')
		{
			$raw = $this->folder($path);
		}

		$key = $this->key($raw);
		$base = 'custom_admin_view.' . $key;
		$this->report->set($base . '.path', $path);

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
		$readable = $this->text->humanise($raw);

		$this->view->set($base . '.name', $raw);
		$this->view->set($base . '.codename', $raw);
		$this->view->set($base . '.system_name', $readable);
		$this->view->set($base . '.description', $readable);
		$this->view->set($base . '.default', $parts['html']);
		$this->view->set($base . '.php_view', $parts['php']);
		$this->view->set($base . '.add_php_view', $php);
		$this->view->set($base . '.path', $path);

		$this->report->set($base . '.add_php_view', $php);
		$this->report->set($base . '.php_view', strlen($parts['php']));
		$this->report->set($base . '.default', strlen($parts['html']));

		return true;
	}

	/**
	 * The view name the file's own folder states.
	 *
	 * @param   string  $path  Absolute path to the default template.
	 *
	 * @return  string  The lower-case view name.
	 * @since   6.1.8
	 */
	public function folder(string $path): string
	{
		$directory = strtolower(basename(dirname($path)));

		if ($directory === 'tmpl')
		{
			$directory = strtolower(basename(dirname($path, 2)));
		}

		return $directory;
	}

	/**
	 * Sanitise one value into a safe registry path segment.
	 *
	 * @param   string  $value  The raw value.
	 *
	 * @return  string  A safe single path segment.
	 * @since   6.1.8
	 */
	public function key(string $value): string
	{
		$key = preg_replace('/[^A-Za-z0-9_]+/', '_', trim($value));

		return $key === null || $key === '' ? 'unknown' : $key;
	}

	/**
	 * Read the file without allowing a failure to surface as a warning.
	 *
	 * @param   string  $path  Absolute path to the default template.
	 *
	 * @return  string|null  The content, or null when it is unusable.
	 * @since   6.1.8
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
