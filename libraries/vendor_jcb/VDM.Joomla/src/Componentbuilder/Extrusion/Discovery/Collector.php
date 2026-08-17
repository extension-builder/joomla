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

namespace VDM\Joomla\Componentbuilder\Extrusion\Discovery;


use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Interfaces\LocatorInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Inventory;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Message;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;


/**
 * Establishes identity, then runs every locator into the inventory.
 *
 * Collection stops before interpretation: nothing here parses a file or writes a
 * definition. That separation is what makes the whole discovery layer testable
 * against a fixture tree with no database and no Joomla application.
 *
 * @since 6.1.6
 */
final class Collector
{
	/**
	 * The Config Class.
	 *
	 * @var    Config
	 * @since  6.1.6
	 */
	protected Config $config;

	/**
	 * The Scanner Class.
	 *
	 * @var    Scanner
	 * @since  6.1.6
	 */
	protected Scanner $scanner;

	/**
	 * The Manifest Class.
	 *
	 * @var    Manifest
	 * @since  6.1.6
	 */
	protected Manifest $manifest;

	/**
	 * The Inventory Registry.
	 *
	 * @var    Inventory
	 * @since  6.1.6
	 */
	protected Inventory $inventory;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.6
	 */
	protected Source $source;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	protected Report $report;

	/**
	 * The Message Bus.
	 *
	 * @var    Message
	 * @since  6.1.6
	 */
	protected Message $message;

	/**
	 * The Schema Locator.
	 *
	 * @var    LocatorInterface
	 * @since  6.1.6
	 */
	protected LocatorInterface $schema;

	/**
	 * The Form Locator.
	 *
	 * @var    LocatorInterface
	 * @since  6.1.6
	 */
	protected LocatorInterface $form;

	/**
	 * The Language Locator.
	 *
	 * @var    LocatorInterface
	 * @since  6.1.6
	 */
	protected LocatorInterface $language;

	/**
	 * The Table Class Locator.
	 *
	 * @var    LocatorInterface
	 * @since  6.1.6
	 */
	protected LocatorInterface $table;

	/**
	 * The View Locator.
	 *
	 * @var    LocatorInterface
	 * @since  6.1.6
	 */
	protected LocatorInterface $view;

	/**
	 * Constructor.
	 *
	 * @param   Config            $config     The extrusion configuration.
	 * @param   Scanner           $scanner    The bounded source scanner.
	 * @param   Manifest          $manifest   The component identity resolver.
	 * @param   Inventory         $inventory  The located artifact registry.
	 * @param   Source            $source     The source identity registry.
	 * @param   Report            $report     The run report registry.
	 * @param   Message           $message    The message bus.
	 * @param   LocatorInterface  $schema     The schema locator.
	 * @param   LocatorInterface  $form       The form locator.
	 * @param   LocatorInterface  $language   The language locator.
	 * @param   LocatorInterface  $table      The table class locator.
	 * @param   LocatorInterface  $view       The view locator.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Scanner $scanner,
		Manifest $manifest,
		Inventory $inventory,
		Source $source,
		Report $report,
		Message $message,
		LocatorInterface $schema,
		LocatorInterface $form,
		LocatorInterface $language,
		LocatorInterface $table,
		LocatorInterface $view
	)
	{
		$this->config = $config;
		$this->scanner = $scanner;
		$this->manifest = $manifest;
		$this->inventory = $inventory;
		$this->source = $source;
		$this->report = $report;
		$this->message = $message;
		$this->schema = $schema;
		$this->form = $form;
		$this->language = $language;
		$this->table = $table;
		$this->view = $view;
	}

	/**
	 * Collect the inventory of one source root.
	 *
	 * @param   string  $path  The requested source root.
	 *
	 * @return  bool  True when the root was usable and at least a schema was found.
	 * @since   6.1.6
	 */
	public function collect(string $path): bool
	{
		$root = $this->scanner->root($path);

		if ($root === null)
		{
			$this->report->set('failed.root', 'not a readable directory: ' . $path);
			$this->message->error(
				'The given component source is not a readable directory, so nothing '
				. 'could be read from it.',
				$path
			);

			return false;
		}

		$this->identify();
		$this->manifest->establish($root);

		foreach ($this->locators() as $locator)
		{
			$this->store($locator);
		}

		return $this->assess();
	}

	/**
	 * Establish the identity that needs no source tree.
	 *
	 * A pasted schema dump has no root to search and no manifest to read, but the
	 * caller supplying it already knows the component it belongs to. Stripping the
	 * table prefix depends entirely on knowing that name, so a dump-only run has to
	 * be able to establish this much without a folder — it is the one thing the
	 * original dump-driven extruder never got wrong, because the component form
	 * told it.
	 *
	 * @return  bool  True when a component code name is known.
	 * @since   6.1.6
	 */
	public function identify(): bool
	{
		$this->source->set('tag', (string) $this->config->get('languageTag', 'en-GB'));

		$supplied = $this->manifest->supplied();

		if ($supplied !== '')
		{
			$this->source->set('code_name', $supplied);
		}

		return $supplied !== '';
	}

	/**
	 * Judge what the source gave us and say so.
	 *
	 * An extrusion works with whatever it finds. A missing artifact makes the
	 * outcome thinner, not impossible, so only the complete absence of anything
	 * describing fields is fatal. Everything else is a warning the caller can show,
	 * which is how a run explains a modest result instead of appearing to succeed
	 * completely.
	 *
	 * @return  bool  True when at least one field-bearing source was found.
	 * @since   6.1.6
	 */
	protected function assess(): bool
	{
		$found = [];

		foreach (['schema', 'table_class', 'form', 'language', 'view'] as $kind)
		{
			$count = (int) $this->inventory->get($kind . '_count', 0);
			$found[$kind] = $count;
			$this->report->set('found.' . $kind, $count);
		}

		if ($found['schema'] === 0 && $found['table_class'] === 0 && $found['form'] === 0)
		{
			$this->message->error(
				'No schema, table definition class or form XML was found, so there is '
				. 'nothing to describe any field with.',
				(string) $this->source->get('path', '')
			);

			return false;
		}

		if ($found['schema'] === 0)
		{
			$this->message->warning(
				'No install schema was found, so column types, sizes and defaults '
				. 'cannot be recovered and will be derived from the form instead.'
			);
		}

		if ($found['table_class'] === 0)
		{
			$this->message->notice(
				'No JCB table definition class was found, which is normal for a '
				. 'component JCB did not build. Relationships, storage encodings and '
				. 'stated field roles cannot be recovered from anything else.'
			);
		}

		if ($found['form'] === 0)
		{
			$this->message->warning(
				'No form XML was found, so field types, options and dependencies '
				. 'cannot be recovered and will be derived from the column types.'
			);
		}

		if ($found['language'] === 0)
		{
			$this->message->warning(
				'No language file was found, so labels will be derived from column '
				. 'names rather than read as real text.'
			);
		}

		if ($found['view'] === 0)
		{
			$this->message->notice(
				'No templates or layouts were found, so only the administrator data '
				. 'structure will be extruded.'
			);
		}

		return true;
	}

	/**
	 * Every locator, in the order the inventory should be filled.
	 *
	 * @return  array<int, LocatorInterface>  The locators.
	 * @since   6.1.6
	 */
	public function locators(): array
	{
		$locators = [$this->schema, $this->form, $this->language];

		if ((string) $this->config->get('tableClass', 'auto') !== 'off')
		{
			$locators[] = $this->table;
		}

		if ($this->config->get('code', false) || $this->config->get('admin', true))
		{
			$locators[] = $this->view;
		}

		return $locators;
	}

	/**
	 * Run one locator and record what it found.
	 *
	 * @param   LocatorInterface  $locator  The locator to run.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function store(LocatorInterface $locator): void
	{
		$root = (string) $this->source->get('path', '');

		if ($root === '')
		{
			return;
		}

		$kind = $locator->kind();
		$index = 0;

		foreach ($locator->locate($root) as $entry)
		{
			if (!is_array($entry) || !isset($entry['path']))
			{
				continue;
			}

			$path = $kind . '.' . $index;
			$this->inventory->set($path . '.path', (string) $entry['path']);
			$this->inventory->set($path . '.tier', (string) ($entry['tier'] ?? 'scan'));
			$this->inventory->set($path . '.name', $entry['name'] ?? null);

			if (isset($entry['role']))
			{
				$this->inventory->set($path . '.role', (string) $entry['role']);
			}

			$index++;
		}

		$this->inventory->set($kind . '_count', $index);
	}
}
