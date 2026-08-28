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
namespace VDM\Component\Componentbuilder\Administrator\View\Extrusion;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper as Html;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\User\User;
use Joomla\CMS\Form\Form;
use VDM\Component\Componentbuilder\Administrator\Helper\ComponentbuilderHelper;
use VDM\Joomla\Componentbuilder\Utilities\Permitted\Actions;
use VDM\Joomla\Utilities\FormHelper;
use VDM\Joomla\Utilities\StringHelper;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\CMS\Toolbar\Toolbar;

// No direct access to this file
\defined('_JEXEC') or die;

/**
 * Componentbuilder Html View class for the Extrusion
 *
 * One page carries the whole journey: point the tool at a source, harvest it
 * into an approval tree, pair every candidate with what the target component
 * already has, and only then import. The form built here is the first step;
 * the rest of the journey runs over the AJAX pipeline.
 *
 * Language note: every user-facing string in this view stack is a natural
 * string inside Text::_() -- never a language constant, and never added to
 * the language files. JCB detects and manages these strings when the code
 * is imported, so constants here would only get in its way.
 *
 * @since  6.1.7
 */
#[\AllowDynamicProperties]
class HtmlView extends BaseHtmlView
{
	/**
	 * The app class
	 *
	 * @var    CMSApplicationInterface
	 * @since  6.1.7
	 */
	public CMSApplicationInterface $app;

	/**
	 * The input class
	 *
	 * @var    Input
	 * @since  6.1.7
	 */
	public Input $input;

	/**
	 * The params registry
	 *
	 * @var    Registry
	 * @since  6.1.7
	 */
	public Registry $params;

	/**
	 * The user object.
	 *
	 * @var    User
	 * @since  6.1.7
	 */
	public User $user;

	/**
	 * The styles url array
	 *
	 * @var    array
	 * @since  6.1.7
	 */
	protected array $styles;

	/**
	 * The scripts url array
	 *
	 * @var    array
	 * @since  6.1.7
	 */
	protected array $scripts;

	/**
	 * The actions object
	 *
	 * @var    object
	 * @since  6.1.7
	 */
	public object $canDo;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 * @throws \Exception
	 * @since  6.1.7
	 */
	public function display($tpl = null): void
	{
		// get the application
		$this->app ??= Factory::getApplication();
		// get input
		$this->input ??= method_exists($this->app, 'getInput') ? $this->app->getInput() : $this->app->input;
		// get component params
		$this->params ??= method_exists($this->app, 'getParams')
			? $this->app->getParams()
			: ComponentHelper::getParams('com_componentbuilder');
		// get the user object
		$this->user ??= $this->getCurrentUser();

		// get the permitted actions the current user can do.
		$this->canDo = Actions::get('extrusion');

		// Load module values
		$model = $this->getModel();
		$this->styles = $model->getStyles() ?? [];
		$this->scripts = $model->getScripts() ?? [];
		// Initialise variables.
		$this->items = $model->getItems();

		// get active components
		$this->Components = $model->getComponents();

		// set the "dankie" state
		$this->dankie = $this->rotativeRandom();

		// get the needed form fields
		$this->form = $this->getDynamicForm();

		// just get it on the page for now....
		ToolbarHelper::inlinehelp();

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			// add the tool bar
			$this->addToolBar();
		}

		// Check for errors.
		if (count($errors = $model->getErrors()))
		{
			throw new \Exception(implode(PHP_EOL, $errors), 500);
		}

		// Set the html view document stuff
		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Get the dynamic build form fields needed on the page
	 *
	 * Three fieldsets carry the whole configuration surface of the two
	 * extrusion engines: the source (where to read), the switches (what to
	 * take), and the advanced options (how carefully to take it).
	 *
	 * @return  Form|null  The form fields
	 *
	 * @since   6.1.7
	 */
	public function getDynamicForm(): ?Form
	{
		// start the form
		$form = new Form('Extrusion');

		$form->load('<form
			addruleprefix="VDM\Component\Componentbuilder\Administrator\Rule"
			addfieldprefix="VDM\Component\Componentbuilder\Administrator\Field">
				<config><inlinehelp button="show"/></config>
				<fieldset name="source"></fieldset>
				<fieldset name="switches"></fieldset>
				<fieldset name="advanced"></fieldset></form>');

		// the yes/no options every switch shares
		$yesno = [
			'1' => Text::_('Yes'),
			'0' => Text::_('No')];

		// admin folder attributes
		$this->field($form, 'source', [
			'type' => 'text',
			'name' => 'admin_path',
			'label' => Text::_('Admin folder'),
			'size' => '60',
			'hint' => 'administrator/components/com_component',
			'description' => Text::_('The administrator folder of the component, selected from the site root. Everything inside is discovered on its own, including the install SQL the folder carries.')]);

		// site folder attributes
		$this->field($form, 'source', [
			'type' => 'text',
			'name' => 'site_path',
			'label' => Text::_('Site folder'),
			'size' => '60',
			'hint' => 'components/com_component',
			'description' => Text::_('The site folder of the component, selected from the site root, for the site views, templates and layouts it holds.')]);

		// library folders attributes
		$this->field($form, 'source', [
			'type' => 'textarea',
			'name' => 'libraries',
			'label' => Text::_('Library folders to harvest as powers'),
			'rows' => '3',
			'cols' => '80',
			'description' => Text::_('One folder per line, selected from the site root. Every PHP class, interface and trait found in these folders is harvested as a power. Leave this empty to only pull in the component.')]);

		// component attributes
		$attributes = [
			'type' => 'list',
			'name' => 'component_id',
			'label' => Text::_('Target component'),
			'class' => 'list_class',
			'description' => Text::_('The JCB component the harvest is paired against. Leave it on detection and the tool will recognise a component JCB already knows by its code name.')];
		// start the component options
		$options = [];
		$options[''] = Text::_('Detect from the source');
		$options['0'] = Text::_('None - everything is created new');
		// load component options from array
		if (!empty($this->Components))
		{
			foreach($this->Components as $component)
			{
				$options[(int) $component->id] = $this->escape($component->system_name ?? $component->name);
			}
		}
		$this->field($form, 'source', $attributes, $options);

		// component code name attributes
		$this->field($form, 'source', [
			'type' => 'text',
			'name' => 'component_code',
			'label' => Text::_('Component code name'),
			'size' => '40',
			'hint' => 'com_component',
			'description' => Text::_('The component the harvested classes belong to, when everything is created new and no target component is selected. This name is what the component namespace placeholder stands on, so every class keeps the placeholder instead of a hard-coded segment.')]);

		// mode attributes
		$this->field($form, 'switches', [
			'type' => 'radio',
			'name' => 'mode',
			'label' => Text::_('Mode'),
			'class' => 'btn-group btn-group-yesno',
			'default' => 'create',
			'description' => Text::_('In create mode the harvest proposes new definitions wherever nothing matches. In update mode only what already exists in JCB is touched.')],
			['create' => Text::_('Create'), 'update' => Text::_('Update')]);

		// on existing attributes
		$this->field($form, 'switches', [
			'type' => 'radio',
			'name' => 'on_existing',
			'label' => Text::_('When a definition already exists'),
			'class' => 'btn-group btn-group-yesno',
			'default' => 'update',
			'description' => Text::_('Skip leaves the existing definition untouched and only mentions it. Update refreshes it with what was harvested. Replace overwrites it completely.')],
			['skip' => Text::_('Skip'), 'update' => Text::_('Update'), 'replace' => Text::_('Replace')]);

		// the scope switches, each one engine scope
		$scopes = [
			'scope_admin' => [Text::_('Admin views'), '1', Text::_('Harvest the admin views of the component.')],
			'scope_site' => [Text::_('Site code'), '0', Text::_('Harvest the site area of the component.')],
			'scope_site_views' => [Text::_('Site views'), '1', Text::_('Harvest the site views, templates and layouts.')],
			'scope_tabs' => [Text::_('Tabs'), '1', Text::_('Carry the field groupings over as admin view tabs.')],
			'scope_conditions' => [Text::_('Conditions'), '1', Text::_('Carry the field show-on rules over as conditions.')],
			'scope_language' => [Text::_('Language strings'), '1', Text::_('Resolve labels and descriptions through the language files of the source.')],
			'scope_translations' => [Text::_('Translations'), '0', Text::_('Also import the translated language strings of the source.')],
			'scope_relations' => [Text::_('Relations'), '1', Text::_('Link the harvested views, fields and powers to the target component.')],
			'scope_component_details' => [Text::_('Component details'), '1', Text::_('Also harvest the component manifest details -- name, author, version and description.')]];
		foreach ($scopes as $name => [$label, $default, $description])
		{
			$this->field($form, 'switches', [
				'type' => 'radio',
				'name' => $name,
				'label' => $label,
				'class' => 'btn-group btn-group-yesno',
				'default' => $default,
				'description' => $description], $yesno);
		}

		// Advanced Options
		$this->field($form, 'switches', [
			'type' => 'radio',
			'name' => 'show_advanced_options',
			'label' => Text::_('Show advanced options'),
			'class' => 'btn-group btn-group-yesno',
			'default' => '0',
			'description' => Text::_('Would you like to see the advanced extrusion options?')], $yesno);

		// Advanced Options note attributes
		$this->field($form, 'advanced', [
			'type' => 'note',
			'name' => 'show_advanced_options_note',
			'label' => Text::_('Advanced options'),
			'heading' => 'h3',
			'showon' => 'show_advanced_options:1']);

		// layout attributes
		$this->field($form, 'advanced', [
			'type' => 'list',
			'name' => 'layout',
			'label' => Text::_('Source layout convention'),
			'class' => 'list_class',
			'default' => 'auto',
			'showon' => 'show_advanced_options:1',
			'description' => Text::_('Which Joomla folder convention the source follows. Leave it on detection unless the tool reads the wrong folders.')],
			['auto' => Text::_('Detect'),
				'j3' => Text::_('Joomla 3'),
				'j4' => Text::_('Joomla 4'),
				'j5' => Text::_('Joomla 5'),
				'j6' => Text::_('Joomla 6')]);

		// language tag attributes
		$this->field($form, 'advanced', [
			'type' => 'text',
			'name' => 'language_tag',
			'label' => Text::_('Language tag'),
			'default' => 'en-GB',
			'size' => '10',
			'showon' => 'show_advanced_options:1',
			'description' => Text::_('The language of the source the labels and descriptions are resolved from.')]);

		// table class attributes
		$this->field($form, 'advanced', [
			'type' => 'radio',
			'name' => 'table_class',
			'label' => Text::_('Table class analysis'),
			'class' => 'btn-group btn-group-yesno',
			'default' => 'auto',
			'showon' => 'show_advanced_options:1',
			'description' => Text::_('Whether the table classes of the source are read to strengthen the field resolution.')],
			['auto' => Text::_('Detect'), 'off' => Text::_('Off')]);

		// dry run attributes
		$this->field($form, 'advanced', [
			'type' => 'radio',
			'name' => 'dry_run',
			'label' => Text::_('Dry run'),
			'class' => 'btn-group btn-group-yesno',
			'default' => '0',
			'showon' => 'show_advanced_options:1',
			'description' => Text::_('A dry run walks the whole import and reports every step, but writes nothing to the database.')], $yesno);

		// strict attributes
		$this->field($form, 'advanced', [
			'type' => 'radio',
			'name' => 'strict',
			'label' => Text::_('Strict'),
			'class' => 'btn-group btn-group-yesno',
			'default' => '0',
			'showon' => 'show_advanced_options:1',
			'description' => Text::_('In strict mode any failure stops the run instead of being reported and skipped.')], $yesno);

		// depth attributes
		$this->field($form, 'advanced', [
			'type' => 'number',
			'name' => 'depth',
			'label' => Text::_('Folder depth limit'),
			'default' => '12',
			'min' => '1',
			'showon' => 'show_advanced_options:1',
			'description' => Text::_('How deep the folder scan may walk into the source.')]);

		// max files attributes
		$this->field($form, 'advanced', [
			'type' => 'number',
			'name' => 'max_files',
			'label' => Text::_('File count limit'),
			'default' => '20000',
			'min' => '1',
			'showon' => 'show_advanced_options:1',
			'description' => Text::_('The most files one scan may read, as a guard against aiming the tool at a folder far larger than one extension.')]);

		// return the form array
		return $form;
	}

	/**
	 * Add one field to the dynamic form
	 *
	 * @param   Form         $form        The form being built
	 * @param   string       $fieldset    The fieldset to add the field to
	 * @param   array        $attributes  The field attributes
	 * @param   array|null   $options     The field options
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function field(Form $form, string $fieldset, array $attributes, ?array $options = null): void
	{
		$xml = $options === null
			? FormHelper::xml($attributes)
			: FormHelper::xml($attributes, $options);

		if ($xml instanceof \SimpleXMLElement)
		{
			$form->setField($xml, null, true, $fieldset);
		}
	}

	/**
	 * Rotative Random Number Generator (1 or 2) - In-Memory
	 *
	 * This version uses a static variable to remember the last value
	 * during the lifetime of the PHP process. No files or sessions needed.
	 *
	 * @return int  Either 1 or 2
	 * @since  6.1.7
	 */
	protected function rotativeRandom(): int
	{
		static $lastValue = null;

		if ($lastValue === 1) {
			// 70% chance to flip to 2, 30% chance to stay on 1
			$value = (mt_rand(1, 100) <= 70) ? 2 : 1;
		} elseif ($lastValue === 2) {
			// 70% chance to flip to 1, 30% chance to stay on 2
			$value = (mt_rand(1, 100) <= 70) ? 1 : 2;
		} else {
			// First run: pick random
			$value = mt_rand(1, 2);
		}

		$lastValue = $value;
		return $value;
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 * @throws  \Exception
	 * @since   6.1.7
	 */
	protected function addToolbar(): void
	{
		$this->input->set('hidemainmenu', true);

		/** @var Toolbar $toolbar */
		$toolbar = $this->getDocument()->getToolbar();

		// add title to the page
		ToolbarHelper::title(Text::_('Extrusion'), 'shuffle');
		// add cpanel button
		ToolbarHelper::custom('extrusion.dashboard', 'grid-2', '', 'COM_COMPONENTBUILDER_DASH', false);
		// set help url for this view if found
		$this->help_url = ComponentbuilderHelper::getHelpUrl('extrusion');
		if (StringHelper::check($this->help_url))
		{
			$toolbar->help('COM_COMPONENTBUILDER_HELP_MANAGER', false, $this->help_url);
		}

		// add the options comp button
		if ($this->canDo->get('core.admin') || $this->canDo->get('core.options'))
		{
			$toolbar->preferences('com_componentbuilder');
		}
	}

	/**
	 * Prepare some document related stuff.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function _prepareDocument(): void
	{
		// Only load jQuery if needed. (default is true)
		if ($this->params->get('add_jquery_framework', 1) == 1)
		{
			Html::_('jquery.framework');
		}

		// Add View JavaScript File
		Html::_('script', 'administrator/components/com_componentbuilder/assets/js/extrusion.js', ['version' => 'auto']);
		// add styles
		foreach ($this->styles as $style)
		{
			Html::_('stylesheet', $style, ['version' => 'auto']);
		}
		// add scripts
		foreach ($this->scripts as $script)
		{
			Html::_('script', $script, ['version' => 'auto']);
		}
	}

	/**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var     The output to escape.
	 * @param   bool   $shorten The switch to shorten.
	 * @param   int    $length  The shorting length.
	 *
	 * @return  mixed  The escaped value.
	 * @since   6.1.7
	 */
	public function escape($var, bool $shorten = false, int $length = 40)
	{
		if (!is_string($var))
		{
			return $var;
		}

		return StringHelper::html($var, $this->_charset ?? 'UTF-8', $shorten, $length);
	}
}
