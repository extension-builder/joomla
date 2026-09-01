<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    4th September 2022
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this JCB template file (EVER)
defined('_JCB_TEMPLATE') or die;
?>
###BOM###
namespace ###NAMESPACEPREFIX###\Component\###ComponentNamespace###\Api\Controller;

###API_VIEW_CONTROLLER_HEADER###

// No direct access to this file
\defined('_JEXEC') or die;

/**
 * ###View### Api Controller
 *
 * The item resource of the ###view### view: read, create, update and delete
 * a record by its id or by any unique key of its table.
 *
 * @since  4.0.0
 */
class ###View###Controller extends ApiController
{
	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $contentType = '###views###';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  3.0
	 */
	protected $default_view = '###view###';

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel|boolean  Model object on success; otherwise false on failure.
	 *
	 * @since   4.0.0
	 */
	public function getModel($name = '', $prefix = '', $config = [])
	{###API_VIEW_CONTROLLER_GETMODEL###
	}

	/**
	 * Basic display of an item view
	 *
	 * @param   integer  $id  The primary key to display. Leave empty if you want to retrieve data from the request
	 *
	 * @return  static  A \JControllerLegacy object to support chaining.
	 *
	 * @since   4.0.0
	 */
	public function displayItem($id = null)
	{
		if ($id === null)
		{
			$id = $this->getRecordId();
		}

		if ($id > 0 && !$this->allowView((int) $id))
		{
			throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		return parent::displayItem($id);
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @return  static  A \JControllerLegacy object to support chaining.
	 *
	 * @since   4.0.0
	 */
	public function edit()
	{
		// resolve the record by its id or by any unique key of the table
		$this->input->set('id', $this->getRecordId());

		return parent::edit();
	}

	/**
	 * Removes an item.
	 *
	 * @param   integer  $id  The primary key to delete item.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function delete($id = null)
	{
		if (!$this->allowDelete())
		{
			throw new NotAllowed(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 403);
		}

		if ($id === null)
		{
			$id = $this->getRecordId();
		}

		$id = (int) $id;
		$model = $this->getModel();
		$table = $model->getTable();

		if ($id < 1 || !$table->load($id))
		{
			throw new ResourceNotFound(Text::_('JLIB_APPLICATION_ERROR_RECORD'), 404);
		}

		$pks = [$id];

		if (!$model->delete($pks))
		{
			$session = $this->app->getSession();

			if ($session->get('http_status_code_404', false))
			{
				$session->clear('http_status_code_404');

				throw new ResourceNotFound(Text::_('JLIB_APPLICATION_ERROR_RECORD'), 404);
			}

			if ($session->get('http_status_code_409', false))
			{
				$session->clear('http_status_code_409');

				throw new \RuntimeException('Resource not in state that can be deleted, must be trashed before it can be deleted', 409);
			}

			$error = $model->getError();

			if ($error)
			{
				throw new \RuntimeException($error, 500);
			}

			throw new NotAllowed(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 403);
		}

		$this->app->setHeader('status', 204);
	}

	/**
	 * Get the id of the record the request targets.
	 *
	 * The primary key is taken when the request carries it, else the record
	 * is resolved through the first unique key of the table the request carries.
	 *
	 * @return  integer  The record id, or 0 when no record matches.
	 *
	 * @since   4.0.0
	 */
	protected function getRecordId(): int
	{###API_VIEW_CONTROLLER_RECORDID###
	}

	/**
	 * Method to check if you can view a record.
	 *
	 * @param   integer  $id  The record id.
	 *
	 * @return  boolean
	 *
	 * @since   4.0.0
	 */
	protected function allowView(int $id): bool
	{###API_VIEW_CONTROLLER_ALLOWVIEW###
	}

	/**
	 * Method override to check if you can add a new record.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowAdd($data = [])
	{###JCONTROLLERFORM_ALLOWADD###
	}

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowEdit($data = [], $key = 'id')
	{###JCONTROLLERFORM_ALLOWEDIT###
	}

	/**
	 * Method to check if it's allowed to delete a record.
	 *
	 * @return  boolean
	 *
	 * @since   4.0.0
	 */
	protected function allowDelete(): bool
	{###API_VIEW_CONTROLLER_ALLOWDELETE###
	}
}
