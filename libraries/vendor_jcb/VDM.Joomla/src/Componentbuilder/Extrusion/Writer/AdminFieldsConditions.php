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

namespace VDM\Joomla\Componentbuilder\Extrusion\Writer;


use VDM\Joomla\Componentbuilder\Extrusion\Abstraction\Writer;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Writes the field conditions a form's showon attributes described.
 *
 * A dependency between fields is expressed only in the form XML, so this is the
 * one structural signal that has no other source. Nothing is written for a view
 * whose form declared no dependency.
 *
 * @since 6.1.6
 */
final class AdminFieldsConditions extends Writer
{

	/**
	 * The Guid Resolver.
	 *
	 * @var    Guid
	 * @since  6.1.6
	 */
	protected Guid $guid;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.6
	 */
	protected Source $source;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   Resolved       $resolved  The resolved definition registry.
	 * @param   ItemInterface  $item      The JCB data item writer.
	 * @param   Report         $report    The run report registry.
	 * @param   Guid           $guid      The identity resolver.
	 * @param   Source         $source    The source identity registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report,
		Guid $guid,
		Source $source
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->guid = $guid;
		$this->source = $source;
	}

	/**
	 * The JCB table this writer persists into.
	 *
	 * @return  string  The table name without its prefix.
	 * @since   6.1.6
	 */
	protected function table(): string
	{
		return 'admin_fields_conditions';
	}

	/**
	 * Write the conditions for every resolved view.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.6
	 */
	public function write(): int
	{
		if (!$this->config->get('conditions', true))
		{
			return 0;
		}

		$written = 0;

		foreach ($this->views() as $view)
		{
			if ($this->one($view))
			{
				$written++;
			}
		}

		$this->report->set('counts.admin_fields_conditions', $written);

		return $written;
	}

	/**
	 * Write the conditions of one resolved view.
	 *
	 * @param   string  $view  The view name.
	 *
	 * @return  bool  True when a definition was written.
	 * @since   6.1.6
	 */
	protected function one(string $view): bool
	{
		$path = $this->path($view);
		$viewGuid = (string) $this->resolved->get($path . '.written.view.guid', '');
		$conditions = (array) $this->resolved->get($path . '.conditions', []);

		if ($viewGuid === '' || $conditions === [])
		{
			return false;
		}

		$subform = [];
		$number = 0;

		foreach ($conditions as $condition)
		{
			$condition = (array) $condition;
			$match = (string) ($condition['match'] ?? '');
			$matchGuid = (string) $this->resolved->get(
				$path . '.written.' . $this->key($match) . '.guid',
				''
			);

			if ($matchGuid === '')
			{
				$this->dropped($view, $match, 'its match field was not extruded as a field');

				continue;
			}

			$targets = [];

			foreach ((array) ($condition['targets'] ?? []) as $target)
			{
				$targetGuid = (string) $this->resolved->get(
					$path . '.written.' . $this->key((string) $target) . '.guid',
					''
				);

				if ($targetGuid !== '')
				{
					$targets[] = $targetGuid;

					continue;
				}

				$this->dropped($view, (string) $target, 'the target field was not extruded as a field');
			}

			if ($targets === [])
			{
				continue;
			}

			$subform['addconditions' . $number] = [
				'target_field' => $targets,
				'match_field' => $matchGuid,
				'target_behavior' => empty($condition['negate']) ? 1 : 2,
				'target_relation' => 1,
				'options' => implode(',', (array) ($condition['values'] ?? []))
			];
			$number++;
		}

		if ($subform === [])
		{
			return false;
		}

		$definition = new \stdClass();
		$definition->guid = $this->guid->derive([$this->option(), 'admin_fields_conditions', $view]);
		$definition->admin_view = $viewGuid;
		$definition->addconditions = json_encode($subform, JSON_FORCE_OBJECT);
		$definition->published = 1;

		return $this->store($definition);
	}

	/**
	 * Record a condition clause that could not be written.
	 *
	 * A real component routinely makes a field depend on a column Joomla manages
	 * itself, such as access or published. JCB generates those from its own
	 * switches rather than as extruded fields, so the dependency has nothing to
	 * point at and has to be dropped. Dropping it quietly would lose part of the
	 * source component with nothing to show for it, so every drop is named here.
	 *
	 * @param   string  $view    The view name.
	 * @param   string  $field   The field that could not be resolved.
	 * @param   string  $reason  Why the clause was dropped.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function dropped(string $view, string $field, string $reason): void
	{
		$this->report->set(
			'dropped.condition.' . $this->key($view) . '.' . $this->key($field),
			$reason
		);
	}

	/**
	 * The component option, when it is known.
	 *
	 * @return  string  The com_ prefixed option, or an empty string.
	 * @since   6.1.6
	 */
	protected function option(): string
	{
		return (string) $this->source->get('code_name', '');
	}
}
