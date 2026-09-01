<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    1st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller;


use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueGuid;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueKeys;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;


/**
 * Api Controller Record Id Class.
 *
 * Builds the getRecordId method of the item API controller: the primary key
 * when the request carries it, else the record resolved through the first
 * unique key of the table the request carries.
 *
 * @since 6.1.7
 */
final class RecordId
{
	/**
	 * The Database Unique Keys Builder Class.
	 *
	 * @var   DatabaseUniqueKeys
	 * @since 6.1.7
	 */
	protected DatabaseUniqueKeys $databaseuniquekeys;

	/**
	 * The Database Unique Guid Builder Class.
	 *
	 * @var   DatabaseUniqueGuid
	 * @since 6.1.7
	 */
	protected DatabaseUniqueGuid $databaseuniqueguid;

	/**
	 * Constructor.
	 *
	 * @param DatabaseUniqueKeys   $databaseuniquekeys   The Database Unique Keys Builder Class.
	 * @param DatabaseUniqueGuid   $databaseuniqueguid   The Database Unique Guid Builder Class.
	 *
	 * @since 6.1.7
	 */
	public function __construct(DatabaseUniqueKeys $databaseuniquekeys,
		DatabaseUniqueGuid $databaseuniqueguid)
	{
		$this->databaseuniquekeys = $databaseuniquekeys;
		$this->databaseuniqueguid = $databaseuniqueguid;
	}

	/**
	 * Get the record id resolution code of the item API controller.
	 *
	 * @param   string  $nameSingleCode  The single code name of the view.
	 *
	 * @return  string  The get record id method body.
	 * @since   6.1.7
	 */
	public function get(string $nameSingleCode): string
	{
		$code = [];

		$code[] = PHP_EOL . Indent::_(2) . "//" . Line::_(__LINE__, __CLASS__)
			. " Take the primary key when the request carries it.";
		$code[] = Indent::_(2) . "\$id = \$this->input->getInt('id', 0);";
		$code[] = PHP_EOL . Indent::_(2) . "if (\$id > 0)";
		$code[] = Indent::_(2) . "{";
		$code[] = Indent::_(3) . "return \$id;";
		$code[] = Indent::_(2) . "}";

		$keys = $this->keys($nameSingleCode);

		if ($keys !== [])
		{
			$code[] = PHP_EOL . Indent::_(2) . "//" . Line::_(__LINE__, __CLASS__)
				. " Resolve the record through the first unique key the request carries.";
			$code[] = Indent::_(2) . "foreach (['" . implode("', '", $keys)
				. "'] as \$key)";
			$code[] = Indent::_(2) . "{";
			$code[] = Indent::_(3) . "\$value = \$this->input->getString(\$key, '');";
			$code[] = PHP_EOL . Indent::_(3) . "if (\$value === '')";
			$code[] = Indent::_(3) . "{";
			$code[] = Indent::_(4) . "continue;";
			$code[] = Indent::_(3) . "}";
			$code[] = PHP_EOL . Indent::_(3) . "\$table = \$this->getModel()->getTable();";
			$code[] = PHP_EOL . Indent::_(3) . "if (\$table->load([\$key => \$value]))";
			$code[] = Indent::_(3) . "{";
			$code[] = Indent::_(4) . "return (int) \$table->id;";
			$code[] = Indent::_(3) . "}";
			$code[] = PHP_EOL . Indent::_(3) . "return 0;";
			$code[] = Indent::_(2) . "}";
		}

		$code[] = PHP_EOL . Indent::_(2) . "return 0;";

		return implode(PHP_EOL, $code);
	}

	/**
	 * The unique keys of the view's table a record can be resolved through.
	 *
	 * The guid comes first when the table has one, then every column that
	 * carries a unique index, in the order the view declares them.
	 *
	 * @param   string  $nameSingleCode  The single code name of the view.
	 *
	 * @return  array  The key column names.
	 * @since   6.1.7
	 */
	private function keys(string $nameSingleCode): array
	{
		$keys = [];
		$unique = $this->databaseuniquekeys->get($nameSingleCode);

		if (!is_array($unique))
		{
			$unique = [];
		}

		if ($this->databaseuniqueguid->exists($nameSingleCode)
			|| in_array('guid', $unique, true))
		{
			$keys[] = 'guid';
		}

		foreach ($unique as $key)
		{
			$key = (string) $key;

			if ($key !== 'id' && !in_array($key, $keys, true))
			{
				$keys[] = $key;
			}
		}

		return $keys;
	}
}
