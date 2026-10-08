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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use Closure;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Event\DispatcherInterface;


/**
 * External XML, table and cache boundaries for a generated native edit model.
 *
 * Native model state, item loading, validation, binding and save events execute.
 *
 * @since  6.2.0
 */
abstract class GeneratedLifecycleModelFixture extends AdminModel
{
	/**
	 * Fresh table factory, matching the generated model's table lifecycle.
	 *
	 * @var    Closure
	 * @since  6.2.0
	 */
	private Closure $tableFactory;

	/**
	 * Component XML form supplied at the loading boundary.
	 *
	 * @var    Form
	 * @since  6.2.0
	 */
	private Form $fixtureForm;

	/**
	 * Native event dispatcher with explicit test listeners.
	 *
	 * @var    DispatcherInterface
	 * @since  6.2.0
	 */
	private DispatcherInterface $fixtureDispatcher;

	/**
	 * Observable storage and cache boundary state.
	 *
	 * @var    object
	 * @since  6.2.0
	 */
	private object $storage;

	/**
	 * Supply isolated boundaries without an installed component factory.
	 *
	 * @param   Closure              $tableFactory  Fresh table factory.
	 * @param   Form                 $form          Loaded XML form.
	 * @param   DispatcherInterface  $dispatcher    Native event dispatcher.
	 * @param   object               $storage       Boundary observations.
	 *
	 * @since   6.2.0
	 */
	public function __construct(Closure $tableFactory, Form $form, DispatcherInterface $dispatcher, object $storage)
	{
		$this->tableFactory = $tableFactory;
		$this->fixtureForm = $form;
		$this->fixtureDispatcher = $dispatcher;
		$this->storage = $storage;
		$this->name = 'article';
		$this->option = 'com_demo';
		$this->__state_set = true;
		$this->events_map = ['validate' => 'content', 'save' => 'content'];
		$this->event_before_save = 'onContentBeforeSave';
		$this->event_after_save = 'onContentAfterSave';
	}

	/**
	 * Create a separate table for each native model operation.
	 *
	 * @param   string  $name     Table name.
	 * @param   string  $prefix   Table namespace.
	 * @param   array   $options  Table options.
	 *
	 * @return  Table
	 * @since   6.2.0
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return ($this->tableFactory)();
	}

	/**
	 * Return component XML without filesystem or installation discovery.
	 *
	 * @param   string        $name     Form name.
	 * @param   string        $source   Form source.
	 * @param   array         $options  Loading options.
	 * @param   bool          $clear    Clear cached forms.
	 * @param   string|false  $xpath    XML selector.
	 *
	 * @return  Form
	 * @since   6.2.0
	 */
	protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = false)
	{
		return $this->fixtureForm;
	}

	/**
	 * Execute native events against the isolated dispatcher.
	 *
	 * @return  DispatcherInterface
	 * @since   6.2.0
	 */
	public function getDispatcher()
	{
		return $this->fixtureDispatcher;
	}

	/**
	 * Observe native cache invalidation without touching installed caches.
	 *
	 * @param   string|null  $group  Cache group.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function cleanCache($group = null)
	{
		$this->storage->events[] = 'cache';
	}
}
