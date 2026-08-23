<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    23rd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace VDM\Component\Componentbuilder\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\User\User;
use Joomla\Input\Input;
use VDM\Joomla\Utilities\ArrayHelper as UtilitiesArrayHelper;

// No direct access to this file
\defined('_JEXEC') or die;

/**
 * Componentbuilder List Model for Extrusion
 *
 * The extrusion dashboard is a two-step tool: harvest a source into an
 * approval tree, then import what was approved. Both steps run through the
 * AJAX pipeline, so this model only feeds the page itself -- the component
 * list the form offers as targets, and the assets the view loads.
 *
 * Language note: every user-facing string in this view stack is written as
 * Text::_('The natural string') on purpose -- never as a language constant,
 * and never added to the language files by hand. JCB detects these natural
 * strings when the code is imported and moves them into its own language
 * management, so transforming them here would only create double work. The
 * same rule applies to the JavaScript: user-facing strings reach it through
 * the Text map printed by the template, not as constants.
 *
 * @since  6.1.7
 */
class ExtrusionModel extends ListModel
{
	/**
	 * The application object.
	 *
	 * @var   CMSApplicationInterface  The application instance.
	 * @since 6.1.7
	 */
	protected CMSApplicationInterface $app;

	/**
	 * The input object, providing access to the request data.
	 *
	 * @var   Input  The input object.
	 * @since 6.1.7
	 */
	protected Input $input;

	/**
	 * Represents the current user object.
	 *
	 * @var   User  The user object representing the current user.
	 * @since 6.1.7
	 */
	protected User $user;

	/**
	 * The styles array.
	 *
	 * @var    array
	 * @since  6.1.7
	 */
	protected array $styles = [
		'administrator/components/com_componentbuilder/assets/css/admin.css',
		'administrator/components/com_componentbuilder/assets/css/extrusion.css'
 	];

	/**
	 * The scripts array.
	 *
	 * @var    array
	 * @since  6.1.7
	 */
	protected array $scripts = [
		'administrator/components/com_componentbuilder/assets/js/admin.js'
 	];

	/**
	 * Constructor
	 *
	 * @param   array                 $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
	 * @param   ?MVCFactoryInterface  $factory  The factory.
	 *
	 * @since   6.1.7
	 * @throws  \Exception
	 */
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		$this->app ??= Factory::getApplication();
		$this->input ??= $this->app->getInput();
		$this->user ??= $this->getCurrentUser();
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return  string  An SQL query
	 * @since   6.1.7
	 */
	protected function getListQuery()
	{
		// Make sure all records load, since no pagination allowed.
		$this->setState('list.limit', 0);
		// Get a db connection.
		$db = $this->getDatabase();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Get from #__componentbuilder_joomla_component as a
		$query->select($db->quoteName(
			['a.id', 'a.guid', 'a.system_name', 'a.name', 'a.name_code', 'a.modified', 'a.created'],
			['id', 'guid', 'system_name', 'name', 'name_code', 'modified', 'created']));
		$query->from($db->quoteName('#__componentbuilder_joomla_component', 'a'));
		// Get where a.published is 1
		$query->where('a.published = 1');
		$query->order('a.modified DESC');
		$query->order('a.created DESC');

		// return the query object
		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 * @since   6.1.7
	 */
	public function getItems()
	{
		// check if this user has permission to access this view
		if (!$this->user->authorise('extrusion.access', 'com_componentbuilder'))
		{
			$this->app->enqueueMessage(Text::_('Not authorised!'), 'error');
			// redirect away if not authorised to be here
			$this->app->redirect('index.php?option=com_componentbuilder');
			return false;
		}

		// load parent items
		$items = parent::getItems();

		return UtilitiesArrayHelper::check($items) ? $items : [];
	}

	/**
	 * Method to get the published components the form offers as targets.
	 *
	 * @return  array|null  The components, or null when there are none.
	 * @since   6.1.7
	 */
	public function getComponents(): ?array
	{
		$items = $this->getItems();

		return UtilitiesArrayHelper::check($items) ? $items : null;
	}

	/**
	 * Method to get the styles that have to be included on the view
	 *
	 * @return  array    styles files
	 * @since   6.1.7
	 */
	public function getStyles(): array
	{
		return $this->styles;
	}

	/**
	 * Method to set the styles that have to be included on the view
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function setStyles(string $path): void
	{
		$this->styles[] = $path;
	}

	/**
	 * Method to get the script that have to be included on the view
	 *
	 * @return  array    script files
	 * @since   6.1.7
	 */
	public function getScripts(): array
	{
		return $this->scripts;
	}

	/**
	 * Method to set the script that have to be included on the view
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function setScript(string $path): void
	{
		$this->scripts[] = $path;
	}
}
