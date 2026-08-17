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
		TemplateReader $template
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

			if ($role === 'template' || $role === 'main')
			{
				$read += $this->template->read($entry['path'], $entry['name']) ? 1 : 0;
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
			$entries[] = [
				'path' => $path,
				'name' => is_string($name) && $name !== '' ? $name : null,
				'role' => (string) $this->inventory->get($kind . '.' . $index . '.role', '')
			];
		}

		return $entries;
	}
}
