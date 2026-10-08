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


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Form\Form;


/**
 * The application and form-loading boundaries of a generated getForm() model.
 *
 * @since  6.2.0
 */
class GeneratedFormModelFixture
{
	/**
	 * Model state shared between form construction and persistence.
	 *
	 * @var    array<string, mixed>
	 * @since  6.2.0
	 */
	public array $state = [];

	/**
	 * The real Joomla validation form supplied by the test.
	 *
	 * @var    Form
	 * @since  6.2.0
	 */
	private Form $form;

	/**
	 * The bounded request application.
	 *
	 * @var    CMSApplication
	 * @since  6.2.0
	 */
	private CMSApplication $app;

	/**
	 * Supply the request and form without loading an installed component.
	 *
	 * @param   Form            $form  The real Joomla validation form.
	 * @param   CMSApplication  $app   The bounded request application.
	 * @since   6.2.0
	 */
	public function __construct(Form $form, CMSApplication $app)
	{
		$this->form = $form;
		$this->app = $app;
	}

	/**
	 * Retain the form reference across the native validation lifecycle.
	 *
	 * @param   string  $key    State name.
	 * @param   mixed   $value  State value.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function setState(string $key, mixed $value): void
	{
		$this->state[$key] = $value;
	}

	/**
	 * Resolve the application at the generated Joomla Power boundary.
	 *
	 * @return  CMSApplication  The bounded request application.
	 * @since   6.2.0
	 */
	protected function getApplication(): CMSApplication
	{
		return $this->app;
	}

	/**
	 * Return the form at the model's XML loading boundary.
	 *
	 * @param   string        $name     The component form name.
	 * @param   string        $source   The model form name.
	 * @param   array         $options  The form loading options.
	 * @param   bool          $clear    Whether to discard a cached form.
	 * @param   string|false  $xpath    The optional form selector.
	 *
	 * @return  Form  The fixture form for the generated method to modify.
	 * @since   6.2.0
	 */
	protected function loadForm(string $name, string $source, array $options, bool $clear, string|false $xpath): Form
	{
		return $this->form;
	}
}
