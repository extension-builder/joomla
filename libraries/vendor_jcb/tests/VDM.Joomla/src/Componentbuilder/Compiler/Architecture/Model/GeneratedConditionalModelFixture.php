<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    7th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Model;


use Joomla\CMS\MVC\Model\FormModel;
use Joomla\Event\Dispatcher;
use Joomla\Registry\Registry;


/**
 * Keep native validation/events/errors while isolating only record persistence.
 *
 * @since  6.2.0
 */
class GeneratedConditionalModelFixture extends FormModel
{
	/**
	 * Decoded values at the model persistence boundary.
	 *
	 * @var    array|false
	 * @since  6.2.0
	 */
	private array|false $stored;

	/**
	 * Set deterministic model state without opening a database.
	 *
	 * @param   array|false  $stored      Decoded record or failed load.
	 * @param   Dispatcher   $dispatcher  The real validation event dispatcher.
	 * @since   6.2.0
	 */
	public function __construct(array|false $stored, Dispatcher $dispatcher)
	{
		$this->stored = $stored;
		$this->name = 'record';
		$this->state = new Registry(['record.id' => $stored['id'] ?? 0]);
		$this->__state_set = true;
		$this->events_map = ['validate' => 'jcb_fixture'];
		$this->setDispatcher($dispatcher);
	}

	/**
	 * Return decoded model data at the storage boundary.
	 *
	 * @param   int  $id  The requested record.
	 *
	 * @return  object|false
	 * @since   6.2.0
	 */
	public function getItem(int $id): object|false
	{
		return $this->stored === false ? false : (object) $this->stored;
	}

	/**
	 * The tests pass native Form instances directly to validate().
	 *
	 * @param   array  $data      Initial form data.
	 * @param   bool   $loadData  Whether stored data should be loaded.
	 *
	 * @return  false
	 * @since   6.2.0
	 */
	public function getForm($data = [], $loadData = true)
	{
		return false;
	}
}
