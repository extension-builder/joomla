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

namespace VDM\Joomla\Componentbuilder\Extrusion\Reader;


use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Interfaces\ReaderInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\View\Layout as LayoutReader;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\View\SiteView as SiteViewReader;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\View\Template as TemplateReader;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Inventory;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;


/**
 * Reads everything the inventory located into the registries.
 *
 * The order matters in one place only: the language catalogue is read before
 * anything that resolves a constant, so a label never has to be looked up twice.
 * Otherwise each reader is independent and a failure in one is recorded rather
 * than allowed to abort the run.
 *
 * @since 6.1.6
 */
final class Dispatcher
{
	/**
	 * The Config Class.
	 *
	 * @var    Config
	 * @since  6.1.6
	 */
	protected Config $config;

	/**
	 * The Inventory Registry.
	 *
	 * @var    Inventory
	 * @since  6.1.6
	 */
	protected Inventory $inventory;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	protected Report $report;

	/**
	 * The Language Reader.
	 *
	 * @var    ReaderInterface
	 * @since  6.1.6
	 */
	protected ReaderInterface $language;

	/**
	 * The Table Class Reader.
	 *
	 * @var    ReaderInterface
	 * @since  6.1.6
	 */
	protected ReaderInterface $table;

	/**
	 * The Schema Reader.
	 *
	 * @var    ReaderInterface
	 * @since  6.1.6
	 */
	protected ReaderInterface $schema;

	/**
	 * The Form Reader.
	 *
	 * @var    ReaderInterface
	 * @since  6.1.6
	 */
	protected ReaderInterface $form;

	/**
	 * The Layout Reader.
	 *
	 * @var    LayoutReader
	 * @since  6.1.6
	 */
	protected LayoutReader $layout;

	/**
	 * The Template Reader.
	 *
	 * @var    TemplateReader
	 * @since  6.1.6
	 */
	protected TemplateReader $template;

	/**
	 * The Site View Reader.
	 *
	 * @var    SiteViewReader
	 * @since  6.1.6
	 */
	protected SiteViewReader $siteview;

	/**
	 * Constructor.
	 *
	 * @param   Config           $config     The extrusion configuration.
	 * @param   Inventory        $inventory  The located artifact registry.
	 * @param   Report           $report     The run report registry.
	 * @param   ReaderInterface  $language   The language reader.
	 * @param   ReaderInterface  $table      The table class reader.
	 * @param   ReaderInterface  $schema     The schema reader.
	 * @param   ReaderInterface  $form       The form reader.
	 * @param   LayoutReader     $layout     The layout reader.
	 * @param   TemplateReader   $template   The template reader.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Inventory $inventory,
		Report $report,
		ReaderInterface $language,
		ReaderInterface $table,
		ReaderInterface $schema,
		ReaderInterface $form,
		LayoutReader $layout,
		TemplateReader $template,
		SiteViewReader $siteview
	)
	{
		$this->config = $config;
		$this->inventory = $inventory;
		$this->report = $report;
		$this->language = $language;
		$this->table = $table;
		$this->schema = $schema;
		$this->form = $form;
		$this->layout = $layout;
		$this->template = $template;
		$this->siteview = $siteview;
	}

	/**
	 * Read every located artifact.
	 *
	 * @return  int  The number of artifacts read successfully.
	 * @since   6.1.6
	 */
	public function dispatch(): int
	{
		$read = 0;

		if ($this->config->get('language', true))
		{
			$read += $this->each('language', $this->language);
		}

		if ((string) $this->config->get('tableClass', 'auto') !== 'off')
		{
			$read += $this->each('table_class', $this->table);
		}

		$read += $this->each('schema', $this->schema);
		$read += $this->each('form', $this->form);
		$read += $this->views();

		$this->report->set('counts.read', $read);

		return $read;
	}

	/**
	 * Record one located view file that was deliberately not read.
	 *
	 * The path is the key, because several views legitimately hold a file of the
	 * same name and keying on the name alone would collapse a dozen findings into
	 * one -- which is the very kind of quiet loss this report exists to prevent.
	 *
	 * @param   string  $reason  Why the file was passed over.
	 * @param   string  $path    Absolute path to the file.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function skipped(string $reason, string $path): void
	{
		$this->report->set('view.skipped.' . $reason . '.' . md5($path), $path);
	}

	/**
	 * Read every located artifact of one kind.
	 *
	 * @param   string           $kind    The inventory artifact kind.
	 * @param   ReaderInterface  $reader  The reader for that kind.
	 *
	 * @return  int  The number read successfully.
	 * @since   6.1.6
	 */
	protected function each(string $kind, ReaderInterface $reader): int
	{
		$read = 0;

		foreach ($this->located($kind) as $entry)
		{
			if ($reader->read($entry['path'], $entry['name']))
			{
				$read++;

				continue;
			}

			$this->report->set('failed.read.' . $kind . '.' . md5($entry['path']), $entry['path']);
		}

		return $read;
	}

	/**
	 * Read the located templates and layouts into the view registry.
	 *
	 * @return  int  The number read successfully.
	 * @since   6.1.6
	 */
	protected function views(): int
	{
		$read = 0;

		foreach ($this->located('view') as $entry)
		{
			$role = $entry['role'] ?? '';

			if ($role === 'layout')
			{
				$read += $this->layout->read($entry['path'], $entry['name']) ? 1 : 0;

				continue;
			}

			if ($role === 'template')
			{
				// JCB has no administrator templates. Everything in an administrator
				// view's own folder is written by the compiler from that view's fields,
				// so nothing there was ever a record and reading it would invent one.
				if (($entry['scope'] ?? '') === 'admin')
				{
					$this->skipped('admin_template', $entry['path']);

					continue;
				}

				if (!$this->config->templatable((string) $entry['name']))
				{
					$this->skipped('generated', $entry['path']);

					continue;
				}

				$read += $this->template->read($entry['path'], $entry['name']) ? 1 : 0;

				continue;
			}

			if ($role === 'edit')
			{
				// A view holding an edit.php edits a record rather than displaying one, and
				// it carries no default.php at all. Recovering it means recovering the model
				// and field set behind it, so it is named as seen and passed over. The file
				// name is the whole of the evidence: a front end list view renders an
				// adminForm with a token for its own filters exactly as an edit view does,
				// so nothing in the markup could tell the two apart.
				$this->skipped('edit_view', $entry['path']);

				continue;
			}

			if ($role === 'main')
			{
				// On the site side a view's default template is the site view itself: JCB
				// keeps its body in the view's own default column, so this is where a front
				// end view comes from. On the administrator side the same file is compiled
				// from the view's field set, so it is generated output belonging to nothing
				// and is recorded as passed over rather than read.
				if (($entry['scope'] ?? '') === 'site')
				{
					$read += $this->siteview->read(
						$entry['path'],
						$entry['view'] ?? null
					) ? 1 : 0;

					continue;
				}

				$this->skipped('main', $entry['path']);
			}
		}

		return $read;
	}

	/**
	 * Every located artifact of one kind.
	 *
	 * @param   string  $kind  The inventory artifact kind.
	 *
	 * @return  array<int, array{path: string, name: string|null, role: string}>  Located artifacts.
	 * @since   6.1.6
	 */
	public function located(string $kind): array
	{
		$count = (int) $this->inventory->get($kind . '_count', 0);
		$entries = [];

		for ($index = 0; $index < $count; $index++)
		{
			$path = $this->inventory->get($kind . '.' . $index . '.path');

			if (!is_string($path) || $path === '')
			{
				continue;
			}

			$name = $this->inventory->get($kind . '.' . $index . '.name');
			$view = $this->inventory->get($kind . '.' . $index . '.view');
			$entries[] = [
				'path' => $path,
				'name' => is_string($name) && $name !== '' ? $name : null,
				'role' => (string) $this->inventory->get($kind . '.' . $index . '.role', ''),
				'scope' => (string) $this->inventory->get($kind . '.' . $index . '.scope', ''),
				'view' => is_string($view) && $view !== '' ? $view : null
			];
		}

		return $entries;
	}
}
