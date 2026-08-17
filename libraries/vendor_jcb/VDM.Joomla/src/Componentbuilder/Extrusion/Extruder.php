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

namespace VDM\Joomla\Componentbuilder\Extrusion;


use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Collector;
use VDM\Joomla\Componentbuilder\Extrusion\Interfaces\ExtruderInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Dispatcher as ReaderDispatcher;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Scope;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Assembler;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\Dispatcher as WriterDispatcher;


/**
 * The single entry point that consumes a component source tree into JCB.
 *
 * This is resolved from the container and configured by chaining, so a caller
 * never constructs a request object. Every setter validates and writes into the
 * shared configuration, which each downstream service already holds by injection.
 *
 * The run is four ordered steps and nothing else: collect an inventory, read what
 * was found, assemble it into one resolved definition set, then write. Nothing
 * below this class enqueues a message; findings accumulate in the report and only
 * the caller decides how to present them.
 *
 * @since 6.1.6
 */
final class Extruder implements ExtruderInterface
{
	/**
	 * The Config Class.
	 *
	 * @var    Config
	 * @since  6.1.6
	 */
	protected Config $config;

	/**
	 * The Scope Class.
	 *
	 * @var    Scope
	 * @since  6.1.6
	 */
	protected Scope $scope;

	/**
	 * The Collector Class.
	 *
	 * @var    Collector
	 * @since  6.1.6
	 */
	protected Collector $collector;

	/**
	 * The Reader Dispatcher.
	 *
	 * @var    ReaderDispatcher
	 * @since  6.1.6
	 */
	protected ReaderDispatcher $readers;

	/**
	 * The Assembler Class.
	 *
	 * @var    Assembler
	 * @since  6.1.6
	 */
	protected Assembler $assembler;

	/**
	 * The Writer Dispatcher.
	 *
	 * @var    WriterDispatcher
	 * @since  6.1.6
	 */
	protected WriterDispatcher $writers;

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
	 * @param   Config            $config     The extrusion configuration.
	 * @param   Scope             $scope      The run state boundary.
	 * @param   Collector         $collector  The discovery collector.
	 * @param   ReaderDispatcher  $readers    The reader dispatcher.
	 * @param   Assembler         $assembler  The resolution assembler.
	 * @param   WriterDispatcher  $writers    The writer dispatcher.
	 * @param   Report            $report     The run report registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Scope $scope,
		Collector $collector,
		ReaderDispatcher $readers,
		Assembler $assembler,
		WriterDispatcher $writers,
		Report $report
	)
	{
		$this->config = $config;
		$this->scope = $scope;
		$this->collector = $collector;
		$this->readers = $readers;
		$this->assembler = $assembler;
		$this->writers = $writers;
		$this->report = $report;
	}

	/**
	 * Clear the configuration and every registry, starting a fresh run.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function reset(): self
	{
		$this->scope->reset();

		return $this;
	}

	/**
	 * Set the component source root to consume.
	 *
	 * @param   string  $path  Absolute path to the component source root.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function path(string $path): self
	{
		return $this->option('path', $path);
	}

	/**
	 * Set the JCB component the extruded definitions belong to.
	 *
	 * @param   int  $id  The component id.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function component(int $id): self
	{
		return $this->option('component', max(0, $id));
	}

	/**
	 * Set the component code name the source tables are prefixed with.
	 *
	 * Supplying this outranks anything inferred from the tree, and it is what
	 * lets a bare schema dump -- which carries no manifest -- still have its
	 * table prefix stripped from every view name.
	 *
	 * @param   string  $name  The component code name, with or without com_.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function codeName(string $name): self
	{
		return $this->option('codeName', trim($name));
	}

	/**
	 * Set whether this run creates a fresh set or merges into an existing one.
	 *
	 * @param   string  $mode  Either create or update.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function mode(string $mode): self
	{
		return $this->option('mode', strtolower(trim($mode)));
	}

	/**
	 * Set what happens when a derived identity already exists.
	 *
	 * @param   string  $policy  Either skip, update, or replace.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function onExisting(string $policy): self
	{
		return $this->option('onExisting', strtolower(trim($policy)));
	}

	/**
	 * Force a target layout instead of detecting one.
	 *
	 * @param   string  $layout  One of auto, j3, j4, j5, or j6.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function layout(string $layout): self
	{
		return $this->option('layout', strtolower(trim($layout)));
	}

	/**
	 * Set which translation supplies the readable strings.
	 *
	 * @param   string  $tag  The language tag, such as en-GB.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function languageTag(string $tag): self
	{
		return $this->option('languageTag', trim($tag));
	}

	/**
	 * Reorder the precedence tiers.
	 *
	 * @param   array<string>  $order  The tier names, strongest first.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function precedence(array $order): self
	{
		$clean = [];

		foreach ($order as $tier)
		{
			$tier = strtolower(trim((string) $tier));

			if (in_array($tier, Config::TIERS, true) && !in_array($tier, $clean, true))
			{
				$clean[] = $tier;
			}
		}

		foreach (Config::TIERS as $tier)
		{
			if (!in_array($tier, $clean, true))
			{
				$clean[] = $tier;
			}
		}

		return $this->option('precedence', $clean);
	}

	/**
	 * Set how the table definition class is treated.
	 *
	 * @param   string  $mode  Either auto or off.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function tableClass(string $mode): self
	{
		return $this->option('tableClass', strtolower(trim($mode)));
	}

	/**
	 * Restrict the run to the named source tables or views.
	 *
	 * @param   array<string>  $names  The names to include.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function include(array $names): self
	{
		return $this->option('include', array_values(array_map('strval', $names)));
	}

	/**
	 * Exclude the named source tables or views from the run.
	 *
	 * @param   array<string>  $names  The names to exclude.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function exclude(array $names): self
	{
		return $this->option('exclude', array_values(array_map('strval', $names)));
	}

	/**
	 * Produce the report without writing anything.
	 *
	 * @param   bool  $dryRun  Whether to suppress every write.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function dryRun(bool $dryRun = true): self
	{
		return $this->option('dryRun', $dryRun);
	}

	/**
	 * Fail instead of degrading when something cannot be resolved.
	 *
	 * @param   bool  $strict  Whether to run strictly.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function strict(bool $strict = true): self
	{
		return $this->option('strict', $strict);
	}

	/**
	 * Toggle one of the boolean scope options.
	 *
	 * @param   string  $name   The scope option name.
	 * @param   bool    $value  Whether the scope is included.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function scope(string $name, bool $value = true): self
	{
		return $this->option($name, $value);
	}

	/**
	 * Set the bounded scan caps.
	 *
	 * @param   int  $depth     The maximum directory depth.
	 * @param   int  $maxFiles  The maximum number of files to consider.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function limits(int $depth, int $maxFiles): self
	{
		return $this->option('depth', max(1, $depth))
			->option('maxFiles', max(1, $maxFiles));
	}

	/**
	 * Run the extrusion and return its report.
	 *
	 * @return  Report  What was found, resolved, written, and skipped.
	 * @since   6.1.6
	 */
	public function extrude(): Report
	{
		$path = (string) $this->config->get('path', '');

		if ($path === '')
		{
			$this->report->set('failed.path', 'no component source root was set');

			return $this->finish(false);
		}

		if (!$this->collector->collect($path))
		{
			$this->report->set('failed.discovery', 'no schema or table definition class was found');

			return $this->finish(false);
		}

		$this->report->set('counts.artifacts', $this->readers->dispatch());
		$this->report->set('counts.views', $this->assembler->assemble());
		$this->writers->dispatch();

		return $this->finish(true);
	}

	/**
	 * Validate and store one option.
	 *
	 * @param   string  $name   The option name.
	 * @param   mixed   $value  The option value.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	protected function option(string $name, $value): self
	{
		if (is_string($value) && !$this->config->permitted($name, $value))
		{
			$this->report->set(
				'failed.option.' . $name,
				'rejected "' . $value . '"; allowed: ' . implode(', ', $this->config->allowed($name))
			);

			return $this;
		}

		$this->config->set($name, $value);

		return $this;
	}

	/**
	 * Stamp the run outcome onto the report.
	 *
	 * @param   bool  $completed  Whether the run reached the writing step.
	 *
	 * @return  Report  The run report.
	 * @since   6.1.6
	 */
	protected function finish(bool $completed): Report
	{
		$this->report->set('completed', $completed);
		$this->report->set('dry_run', (bool) $this->config->get('dryRun', false));
		$this->report->set('mode', (string) $this->config->get('mode', 'create'));

		return $this->report;
	}
}
