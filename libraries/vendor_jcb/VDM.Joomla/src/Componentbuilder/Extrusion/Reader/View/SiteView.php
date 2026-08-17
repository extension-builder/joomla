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
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Text;


/**
 * Reads a site view's own default template into the View registry.
 *
 * A site view is the one thing in the front end that JCB does not generate from a
 * database table, and its body is the default template of its folder. That makes
 * site/tmpl/<view>/default.php the site view itself rather than a reusable
 * template: the HTML after the final closing tag is the view's default column and
 * the PHP above it is its php_view.
 *
 * The distinction is only meaningful on the site side. An administrator view's
 * default.php is compiled from the view's own field set, so it is generated output
 * and belongs to nothing; the dispatcher is what keeps the two apart, using the
 * scope the locator recorded.
 *
 * Both columns the split lands in declare store: base64, and the Data pipeline
 * applies that itself, so the parts are stored exactly as they were read. The
 * source is never included, required, or evaluated.
 *
 * @since 6.1.6
 */
final class SiteView implements ReaderInterface
{
	/**
	 * What a front end view that edits a record always contains.
	 *
	 * A site view that renders a form is an administrator view moved to the front:
	 * it has fields, a token and a task, and recovering it would mean recovering the
	 * model and the field set behind it. That is a different and much larger job, so
	 * such a view is passed over rather than half extruded into something that would
	 * compile into a form with nothing in it.
	 *
	 * Each marker is Joomla's own convention for rendering a bound form, so a view
	 * matching one is not a guess about intent.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const EDITING = [
		'$this->form->',
		'name="adminForm"',
		"name='adminForm'",
		'JHtml::_(\'form.token\')',
		'HTMLHelper::_(\'form.token\')'
	];

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
	 * The Text Resolver.
	 *
	 * @var    Text
	 * @since  6.1.6
	 */
	protected Text $text;

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
	 * @param   Text          $text    The readable text resolver.
	 * @param   Report        $report  The run report registry.
	 *
	 * @since   6.1.6
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
	 * Read one site view's default template into the View registry.
	 *
	 * @param   string       $path  Absolute path to the default template.
	 * @param   string|null  $name  The view name, derived from the folder when null.
	 *
	 * @return  bool  True when the file yielded a body.
	 * @since   6.1.6
	 */
	public function read(string $path, ?string $name = null): bool
	{
		$raw = trim((string) $name);

		if ($raw === '')
		{
			$raw = $this->folder($path);
		}

		$key = $this->key($raw);
		$base = 'site_view.' . $key;
		$this->report->set($base . '.path', $path);

		$content = $this->content($path);

		if ($content === null)
		{
			$this->report->set($base . '.error', 'the file could not be read');

			return false;
		}

		if ($this->editing($content))
		{
			$this->report->set(
				$base . '.skipped',
				'a front end view that edits a record, which needs the model and field '
				. 'set behind it and is not extruded'
			);

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
		$this->view->set($base . '.context', $raw);
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
	 * Whether a site view renders a bound form rather than displaying content.
	 *
	 * @param   string  $content  The template source.
	 *
	 * @return  bool  True when the view edits a record.
	 * @since   6.1.6
	 */
	public function editing(string $content): bool
	{
		foreach (self::EDITING as $marker)
		{
			if (str_contains($content, $marker))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * The view name the file's own folder states.
	 *
	 * @param   string  $path  Absolute path to the default template.
	 *
	 * @return  string  The lower-case view name.
	 * @since   6.1.6
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
	 * @param   string  $path  Absolute path to the default template.
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
