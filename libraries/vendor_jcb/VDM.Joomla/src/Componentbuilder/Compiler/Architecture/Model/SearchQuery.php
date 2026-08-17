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

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\Model;


use VDM\Joomla\Componentbuilder\Compiler\Builder\Search;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Utilities\ArrayHelper;


/**
 * Model Search Query Class.
 *
 * Builds the search clause a list model applies when the user types into the
 * search box: one LIKE test per searchable field, plus the text column of any
 * custom field that is joined into the list.
 *
 * The clause reads the same on every Joomla target, so this is one class.
 *
 * @since  6.1.7
 */
final class SearchQuery
{
	/**
	 * The Search Class.
	 *
	 * @var   Search
	 * @since 6.1.7
	 */
	protected Search $search;

	/**
	 * Constructor.
	 *
	 * @param Search  $search  The Search Class.
	 *
	 * @since 6.1.7
	 */
	public function __construct(Search $search)
	{
		$this->search = $search;
	}

	/**
	 * Build the search clause of a list model.
	 *
	 * @param   string  $nameListCode  The list view code name.
	 *
	 * @return  string
	 *
	 * @since   6.1.7
	 */
	public function get($nameListCode)
	{
		if ($this->search->exists($nameListCode))
		{
			// setup the searh options
			$search = "'(";
			foreach ($this->search->get($nameListCode) as $nr => $array)
			{
				// array( 'type' => $typeName, 'code' => $name, 'custom' => $custom, 'list' => $field['list']);
				if ($nr == 0)
				{
					$search .= "a." . $array['code'] . " LIKE '.\$search.'";
					if (ArrayHelper::check($array['custom'])
						&& 1 == $array['list'])
					{
						$search .= " OR " . $array['custom']['db'] . "."
							. $array['custom']['text'] . " LIKE '.\$search.'";
					}
				}
				else
				{
					$search .= " OR a." . $array['code'] . " LIKE '.\$search.'";
					if (ArrayHelper::check($array['custom'])
						&& 1 == $array['list'])
					{
						$search .= " OR " . $array['custom']['db'] . "."
							. $array['custom']['text'] . " LIKE '.\$search.'";
					}
				}
			}
			$search .= ")'";
			// now setup query
			$query = PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " Filter by search.";
			$query .= PHP_EOL . Indent::_(2)
				. "\$search = \$this->getState('filter.search');";
			$query .= PHP_EOL . Indent::_(2) . "if (!empty(\$search))";
			$query .= PHP_EOL . Indent::_(2) . "{";
			$query .= PHP_EOL . Indent::_(3)
				. "if (stripos(\$search, 'id:') === 0)";
			$query .= PHP_EOL . Indent::_(3) . "{";
			$query .= PHP_EOL . Indent::_(4)
				. "\$query->where('a.id = ' . (int) substr(\$search, 3));";
			$query .= PHP_EOL . Indent::_(3) . "}";
			$query .= PHP_EOL . Indent::_(3) . "else";
			$query .= PHP_EOL . Indent::_(3) . "{";
			$query .= PHP_EOL . Indent::_(4)
				. "\$search = \$db->quote('%' . \$db->escape(\$search) . '%');";
			$query .= PHP_EOL . Indent::_(4) . "\$query->where(" . $search
				. ");";
			$query .= PHP_EOL . Indent::_(3) . "}";
			$query .= PHP_EOL . Indent::_(2) . "}";
			$query .= PHP_EOL;

			return $query;
		}

		return '';
	}
}
