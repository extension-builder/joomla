<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\CanDo;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\ComHelperClass\UserPermissionCheckAccess;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\SetAccessControl;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\UniqueFields;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\ValidationFix;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueGuid;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueKeys;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DynamicButtons;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ValidationFix as ValidationFixRegistry;


/**
 * Generated view guard and button contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedViewGuardsTest extends ArchitectureTestCase
{
	/**
	 * The button controller a modern target writes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_BUTTON_MODERN = <<<'GEN'


	public function redirectToDothing()
	{
		// Check for request forgeries
		Joomla___5ba38513_5c4f_4b0d_935e_49e986a6bce8___Power::checkToken() or die(Text::_('JINVALID_TOKEN'));
		// check if export is allowed for this user.
		$user = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->getIdentity();
		if ($user->authorise('dothing.access', 'com_demo'))
		{
			// Get the input
			$input = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->input;
			$pks = $input->post->get('cid', array(), 'array');
			// Sanitize the input
			$pks = ArrayHelper::toInteger($pks);
			// convert to string
			$ids = implode('_', $pks);
			$this->setRedirect(Joomla___d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_demo&view=dothing&cid='.$ids, false));
			return;
		}
		// Redirect to the list screen with error.
		$message = Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_ACCESS_TO_Do Thing_FAILED');
		$this->setRedirect(Joomla___d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_demo&view=demos', false), $message, 'error');
		return;
	}
GEN;

	/**
	 * The button controller Joomla 3 writes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_BUTTON_J3 = <<<'GEN'


	public function redirectToDothing()
	{
		// Check for request forgeries
		Joomla___5ba38513_5c4f_4b0d_935e_49e986a6bce8___Power::checkToken() or die(Text::_('JINVALID_TOKEN'));
		// check if export is allowed for this user.
		$user = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getUser();
		if ($user->authorise('dothing.access', 'com_demo'))
		{
			// Get the input
			$input = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->input;
			$pks = $input->post->get('cid', array(), 'array');
			// Sanitize the input
			$pks = ArrayHelper::toInteger($pks);
			// convert to string
			$ids = implode('_', $pks);
			$this->setRedirect(Joomla___d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_demo&view=dothing&cid='.$ids, false));
			return;
		}
		// Redirect to the list screen with error.
		$message = Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_ACCESS_TO_Do Thing_FAILED');
		$this->setRedirect(Joomla___d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_demo&view=demos', false), $message, 'error');
		return;
	}
GEN;

	/**
	 * The validation method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_VALIDATION = <<<'GEN'


	/**
	 * Method to validate the form data.
	 *
	 * @param   Form   $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 * @since   12.2
	 */
	public function validate($form, $data, $group = null)
	{
		// check if the not_required field is set
		if (isset($data['not_required']) && Super___1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check($data['not_required']))
		{
			$requiredFields = (array) explode(',',(string) $data['not_required']);
			$requiredFields = array_unique($requiredFields);
			// now change the required field attributes value
			foreach ($requiredFields as $requiredField)
			{
				// make sure there is a string value
				if (Super___1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check($requiredField))
				{
					// change to false
					$form->setFieldAttribute($requiredField, 'required', 'false');
					// also clear the data set
					unset($data[$requiredField]);
				}
			}
		}
		return parent::validate($form, $data, $group);
	}
GEN;

	/**
	 * The unique fields method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_UNIQUE = <<<'GEN'


	/**
	 * Method to get the unique fields of this table.
	 *
	 * @return  mixed  An array of field names, boolean false if none is set.
	 *
	 * @since   3.0
	 */
	protected function getUniqueFields()
	{
		return array('name','alias', 'guid');
	}
GEN;

	/**
	 * The access control fieldset this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_FIELDSET = <<<'GEN'
<!-- Access Control Fields. -->
	<fieldset name="accesscontrol">
		<!-- Asset Id Field. Type: Hidden (joomla) -->
		<field
			name="asset_id"
			type="hidden"
			filter="unset"
		/>
		<!-- Rules Field. Type: Rules (joomla) -->
		<field
			name="rules"
			type="rules"
			label="Permissions in relation to this demo"
			translate_label="false"
			filter="rules"
			validate="rules"
			class="inputbox"
			component="com_demo"
			section="demo"
		/>
	</fieldset>
GEN;

	/**
	 * The unique fields method of a view with none, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_UNIQUE_NONE = <<<'GEN'


	/**
	 * Method to get the unique fields of this table.
	 *
	 * @return  mixed  An array of field names, boolean false if none is set.
	 *
	 * @since   3.0
	 */
	protected function getUniqueFields()
	{
		return false;
	}
GEN;

	/**
	 * The access check that turns a user away to the default view, captured
	 * from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ACCESS_DEFAULT_VIEW = <<<'GEN'

		// check if this user has permission to access item
		if (!$user->authorise('site.demo.access', 'com_demo'))
		{
			$app = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication();
			$app->enqueueMessage(Text::_('COM_DEMO_NOT_AUTHORISED_TO_VIEW_DEMO'), 'error');
			// redirect away to the default view if no access allowed.
			$app->redirect(Joomla___d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_demo&view=looker'));
			return false;
		}
GEN;

	/**
	 * The same check written for a view that reads the user off itself,
	 * captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ACCESS_OWN_USER = <<<'GEN'

		// check if this user has permission to access item
		if (!$this->user->authorise('site.demo.access', 'com_demo'))
		{
			$app = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication();
			$app->enqueueMessage(Text::_('COM_DEMO_NOT_AUTHORISED_TO_VIEW_DEMO'), 'error');
			// redirect away to the default view if no access allowed.
			$app->redirect(Joomla___d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_demo&view=looker'));
			return false;
		}
GEN;

	/**
	 * The access check that turns a user away to the home page, captured from
	 * the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ACCESS_HOME = <<<'GEN'

		// check if this user has permission to access item
		if (!$user->authorise('site.demo.access', 'com_demo'))
		{
			$app = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication();
			$app->enqueueMessage(Text::_('COM_DEMO_NOT_AUTHORISED_TO_VIEW_DEMO'), 'error');
			// redirect away to the home page if no access allowed.
			$app->redirect(Joomla___eecc143e_b5cf_4c33_ba4d_97da1df61422___Power::root());
			return false;
		}
GEN;

	/**
	 * The permission object of a list view, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_CAN_DO = <<<'GEN'

		$this->canEdit = $this->canDo->get('core.edit');
		$this->canState = $this->canDo->get('core.edit.state');
		$this->canCreate = $this->canDo->get('core.create');
		$this->canDelete = $this->canDo->get('core.delete');
		$this->canBatch = $this->canDo->get('core.batch');
GEN;

	/**
	 * The targets that read the user off the application.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function modernVersions(): array
	{
		return [
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
			'Joomla 6' => ['JoomlaSix'],
		];
	}

	/**
	 * One dynamic button, as the compiler collected it.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function button(): array
	{
		return [
			'NAME' => 'Do Thing',
			'name' => 'Do Thing',
			'link' => 'dothing',
			'type' => 'controller',
			'target' => 'demo',
			'method' => 'doThing',
			'redirect' => 'demos',
		];
	}

	/**
	 * Build the button controller writer of a target.
	 *
	 * @param   string  $version     Target namespace segment.
	 * @param   bool    $hasButtons  Whether the view was given any buttons.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function buttons(string $version, bool $hasButtons = true): object
	{
		$registry = new DynamicButtons();
		if ($hasButtons)
		{
			$registry->set('demos', [$this->button()]);
		}

		return $this->renderer(
			$this->targetClass($version, 'Controller\\CustomAdminDynamicButton', ['JoomlaThree']),
			['dynamicbuttons' => $registry]
		);
	}

	/**
	 * A view given no dynamic buttons is given no controller methods.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutButtonsIsGivenNoControllerMethods(): void
	{
		$this->assertSame('', $this->buttons('JoomlaSix', false)->get('demos'));
	}

	/**
	 * A modern target reads the user the button was pressed by off the
	 * application.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernTargetReadsTheUserOffTheApplication(string $version): void
	{
		$this->assertSame(self::EXPECTED_BUTTON_MODERN, $this->buttons($version)->get('demos'));
	}

	/**
	 * Joomla 3 asks the factory for the user directly.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeAsksTheFactoryForTheUser(): void
	{
		$this->assertSame(self::EXPECTED_BUTTON_J3, $this->buttons('JoomlaThree')->get('demos'));
	}

	/**
	 * A view the compiler found no fixes for is given no validation method.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithNoFixesIsGivenNoValidationMethod(): void
	{
		$subject = $this->renderer(ValidationFix::class, [
			'validationfix' => new ValidationFixRegistry(),
		]);

		$this->assertSame('', $subject->get('demo', 'Demo'));
	}

	/**
	 * A view the compiler found fixes for is given the method that applies them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithFixesIsGivenTheValidationMethod(): void
	{
		$fixes = new ValidationFixRegistry();
		$fixes->set('demo', ["\$data['name'] = trim(\$data['name']);"]);

		$subject = $this->renderer(ValidationFix::class, ['validationfix' => $fixes]);

		$this->assertSame(self::EXPECTED_VALIDATION, $subject->get('demo', 'Demo'));
	}

	/**
	 * A view with nothing to keep unique still gets the method, saying so.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithNothingUniqueIsToldItHasNone(): void
	{
		$subject = $this->renderer(UniqueFields::class, [
			'databaseuniqueguid' => new DatabaseUniqueGuid(),
			'databaseuniquekeys' => new DatabaseUniqueKeys(),
		]);
		$view = 'demo';

		$this->assertSame(self::EXPECTED_UNIQUE_NONE, $subject->get($view));
	}

	/**
	 * A view with fields to keep unique is given the method that names them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithUniqueFieldsIsGivenTheMethod(): void
	{
		$guid = new DatabaseUniqueGuid();
		$guid->set('demo', ['guid']);
		$keys = new DatabaseUniqueKeys();
		$keys->set('demo', ['name', 'alias']);

		$subject = $this->renderer(UniqueFields::class, [
			'databaseuniqueguid' => $guid,
			'databaseuniquekeys' => $keys,
		]);
		$view = 'demo';

		$this->assertSame(self::EXPECTED_UNIQUE, $subject->get($view));
	}

	/**
	 * Every view is given the access control fieldset.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryViewIsGivenTheAccessControlFieldset(): void
	{
		$subject = $this->renderer(SetAccessControl::class);
		$view = 'demo';

		$this->assertSame(self::EXPECTED_FIELDSET, $subject->get($view));
	}

	/**
	 * A view the component was told to guard is given the access check.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAGuardedViewIsGivenItsAccessCheck(): void
	{
		$subject = $this->renderer(UserPermissionCheckAccess::class, [
			'contentone' => $this->siteDefaultView('looker'),
		]);
		$view = $this->guardedView();

		$this->assertSame(
			self::EXPECTED_ACCESS_DEFAULT_VIEW, $subject->get($view, 2)
		);
	}

	/**
	 * A view that holds its own user is checked against that user.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatHoldsItsUserIsCheckedAgainstIt(): void
	{
		$subject = $this->renderer(UserPermissionCheckAccess::class, [
			'contentone' => $this->siteDefaultView('looker'),
		]);
		$view = $this->guardedView();

		$this->assertSame(
			self::EXPECTED_ACCESS_OWN_USER, $subject->get($view, 1)
		);
	}

	/**
	 * The view the site opens on sends a turned away user to the home page.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheDefaultViewSendsATurnedAwayUserHome(): void
	{
		$subject = $this->renderer(UserPermissionCheckAccess::class, [
			'contentone' => $this->siteDefaultView('demo'),
		]);
		$view = $this->guardedView();

		$this->assertSame(self::EXPECTED_ACCESS_HOME, $subject->get($view, 2));
	}

	/**
	 * A view the component was not told to guard is given no access check.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnUnguardedViewIsGivenNoAccessCheck(): void
	{
		$subject = $this->renderer(UserPermissionCheckAccess::class, [
			'contentone' => $this->siteDefaultView('looker'),
		]);
		$view = ['settings' => (object) ['code' => 'demo']];

		$this->assertSame('', $subject->get($view, 2));
	}

	/**
	 * The permission object names the actions the component checks.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testThePermissionObjectNamesTheActionsItChecks(): void
	{
		$subject = $this->renderer(CanDo::class);

		$this->assertSame(self::EXPECTED_CAN_DO, $subject->get('demo', 'demos'));
	}

	/**
	 * The view the site opens on.
	 *
	 * @param   string  $code  The view the site opens on.
	 *
	 * @return  ContentOne
	 * @since   6.1.7
	 */
	private function siteDefaultView(string $code): ContentOne
	{
		$contentone = new ContentOne();
		$contentone->set('SITE_DEFAULT_VIEW', $code);

		return $contentone;
	}

	/**
	 * A view the component was told to guard.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function guardedView(): array
	{
		$settings = new stdClass();
		$settings->code = 'demo';

		return ['access' => 1, 'settings' => $settings];
	}
}
