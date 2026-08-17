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
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\FieldXml;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Fieldtype;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Writes one JCB field definition per resolved column.
 *
 * The stored values are raw: the storage encoding each column declares is applied
 * by the Data pipeline, so applying it here would encode twice. The identity is
 * the GUID the source supplied where it had one, which is what lets a component
 * that came out of JCB line back up with its own definitions.
 *
 * @since 6.1.6
 */
final class Field extends Writer
{
	/**
	 * Default lengths JCB offers directly rather than as an other value.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const SIZES = ['1', '7', '10', '11', '50', '64', '100', '255', '1024', '2048'];

	/**
	 * Default values JCB offers directly rather than as an other value.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const DEFAULTS = ['', '0', '1', 'CURRENT_TIMESTAMP', 'DATETIME'];

	/**
	 * The Fieldtype Resolver.
	 *
	 * @var    Fieldtype
	 * @since  6.1.6
	 */
	protected Fieldtype $fieldtype;

	/**
	 * The FieldXml Resolver.
	 *
	 * @var    FieldXml
	 * @since  6.1.6
	 */
	protected FieldXml $fieldxml;

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
	 * @param   Config         $config     The extrusion configuration.
	 * @param   Resolved       $resolved   The resolved definition registry.
	 * @param   ItemInterface  $item       The JCB data item writer.
	 * @param   Report         $report     The run report registry.
	 * @param   Fieldtype      $fieldtype  The field type resolver.
	 * @param   FieldXml       $fieldxml   The field XML composer.
	 * @param   Guid           $guid       The identity resolver.
	 * @param   Source         $source     The source identity registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report,
		Fieldtype $fieldtype,
		FieldXml $fieldxml,
		Guid $guid,
		Source $source
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->fieldtype = $fieldtype;
		$this->fieldxml = $fieldxml;
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
		return 'field';
	}

	/**
	 * Write every resolved field.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.6
	 */
	public function write(): int
	{
		$written = 0;

		foreach ($this->views() as $view)
		{
			$fields = $this->resolved->get($this->path($view) . '.field');

			foreach ((array) $fields as $key => $properties)
			{
				$properties = (array) $properties;
				$column = (string) $this->value($properties, 'name', (string) $key);

				if ($column === '')
				{
					continue;
				}

				if ($this->one($view, $column, $properties))
				{
					$written++;
				}
			}
		}

		$this->report->set('counts.field', $written);

		return $written;
	}

	/**
	 * Write one resolved field.
	 *
	 * @param   string                $view        The view name.
	 * @param   string                $column      The source column name.
	 * @param   array<string, mixed>  $properties  The resolved properties.
	 *
	 * @return  bool  True when the definition was written.
	 * @since   6.1.6
	 */
	protected function one(string $view, string $column, array $properties): bool
	{
		$type = (string) $this->value($properties, 'xml_type', 'text');
		$fieldtype = $this->fieldtype->resolve($type);

		if ($fieldtype === null)
		{
			$this->report->set('failed.field.unresolved_type.' . $this->key($column), $type);

			return false;
		}

		$guid = $this->guid->prefer(
			$this->value($properties, 'guid'),
			[$this->option(), 'field', $view, $column]
		);
		$label = (string) $this->value($properties, 'label', $column);
		$size = (string) $this->value($properties, 'size', '');
		$default = (string) $this->value($properties, 'default', '');

		$definition = new \stdClass();
		$definition->guid = $guid;
		$definition->name = $label;
		$definition->fieldtype = $fieldtype['id'];
		$definition->datatype = (string) $this->value($properties, 'datatype', 'TEXT');
		$definition->indexes = (int) $this->value($properties, 'key', 0);
		$definition->null_switch = (string) $this->value($properties, 'null', 'NULL');
		$definition->store = $this->storeCode((string) $this->value($properties, 'store', ''));
		$definition->xml = json_encode($this->fieldxml->build($column, $properties));
		$definition->published = 1;

		$definition->datalenght = in_array($size, self::SIZES, true) || $size === ''
			? $size
			: 'Other';
		$definition->datalenght_other = $definition->datalenght === 'Other' ? $size : '';
		$definition->datadefault = in_array($default, self::DEFAULTS, true) || $default === ''
			? $default
			: 'Other';
		$definition->datadefault_other = $definition->datadefault === 'Other' ? $default : '';

		if (!$this->store($definition))
		{
			return false;
		}

		$this->resolved->set(
			$this->path($view) . '.written.' . $this->key($column) . '.guid',
			$guid
		);
		$this->resolved->set(
			$this->path($view) . '.written.' . $this->key($column) . '.fieldtype',
			$fieldtype['name']
		);

		return true;
	}

	/**
	 * The JCB store code for a declared storage encoding.
	 *
	 * @param   string  $store  The declared encoding.
	 *
	 * @return  int  The JCB store code.
	 * @since   6.1.6
	 */
	protected function storeCode(string $store): int
	{
		switch (strtolower(trim($store)))
		{
			case 'base64':
				return 1;
			case 'json':
				return 2;
			case 'basic_encryption':
				return 3;
			case 'whmcs_encryption':
				return 4;
			case 'expert_mode_encryption':
				return 5;
			default:
				return 0;
		}
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
