<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    4th September, 2022
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Helper;


use Joomla\CMS\Factory;
use VDM\Component\Componentbuilder\Administrator\Helper\ComponentbuilderHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\ObjectHelper;
use VDM\Joomla\Utilities\String\NamespaceHelper;
use VDM\Joomla\Componentbuilder\Compiler\Factory as CFactory;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Minify;
use VDM\Joomla\Componentbuilder\Compiler\Helper\Interpretation;


/**
 * Infusion class
 *
 * @since 3.2.0
 * @deprecated 3.3
 */
class Infusion extends Interpretation
{
	/**
	 * The language files the compiler wrote
	 *
	 * @var     array
	 * @since   3.2.0
	 */
	public $langFiles = [];

	/**
	 * The admin views whose edit body is built on the second run
	 *
	 * @var     array
	 * @since   3.2.0
	 */
	public $secondRunAdmin;

	/**
	 * Constructor
	 *
	 * @since   3.2.0
	 */
	public function __construct()
	{
		// first we run the perent constructor
		if (parent::__construct())
		{
			// infuse the data into the structure
			return $this->buildFileContent();
		}

		return false;
	}

	/**
	 * Build the content for the structure
	 *
	 * @return  boolean  on success
	 *
	 * @since   3.2.0
	 */
	protected function buildFileContent()
	{
		if (CFactory::_('Component')->isArray('admin_views'))
		{
			// Trigger Event: jcb_ce_onBeforeBuildFilesContent
			CFactory::_('Event')->trigger(
				'jcb_ce_onBeforeBuildFilesContent'
			);

			// everything the component says about itself, before any view is built
			CFactory::_('Architecture.Component.Details')->set();

			// reset view array
			$viewarray            = [];
			$site_edit_view_array = [];
			// start dynamic build
			foreach (CFactory::_('Component')->get('admin_views') as $view)
			{
				// set the target
				CFactory::_('Config')->build_target = 'admin';
				CFactory::_('Config')->lang_target = 'admin';

				// set local names
				$nameSingleCode = $view['settings']->name_single_code;
				$nameListCode   = $view['settings']->name_list_code;

				// set the view placeholders
				$this->setViewPlaceholders($view['settings']);

				// set site edit view array
				if (isset($view['edit_create_site_view'])
					&& is_numeric(
						$view['edit_create_site_view']
					)
					&& $view['edit_create_site_view'] > 0)
				{
					$site_edit_view_array[$nameSingleCode] = $nameListCode;
					CFactory::_('Config')->lang_target = 'both';
					// insure site view does not get removed
					CFactory::_('Config')->remove_site_edit_folder = false;
				}

				// check if help is being loaded
				CFactory::_('Compiler.Creator.Helper')->set($nameSingleCode);

				// set custom admin view list links
				$this->setCustomAdminViewListLink(
					$view, $nameListCode
				);

				// set view array
				$viewarray[] = Indent::_(4) . "'"
					. $nameSingleCode . "' => '"
					. $nameListCode . "'";
				// the edit view of this admin view
				CFactory::_('Architecture.AdminViews.EditView')->build(
					$view, $nameSingleCode, $nameListCode
				);

				// the list view of this admin view
				CFactory::_('Architecture.AdminViews.ListView')->build(
					$view, $nameSingleCode, $nameListCode
				);

				// set u fields used in batch
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|UNIQUEFIELDS',
					$this->setUniqueFields(
						$nameSingleCode
					)
				);

				// TITLEALIASFIX <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|TITLEALIASFIX',
					$this->setAliasTitleFix(
						$nameSingleCode
					)
				);

				// GENERATENEWTITLE <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|GENERATENEWTITLE',
					$this->setGenerateNewTitle(
						$nameSingleCode
					)
				);

				// GENERATENEWALIAS <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|GENERATENEWALIAS',
					$this->setGenerateNewAlias(
						$nameSingleCode
					)
				);

				// MODEL_BATCH_COPY <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|MODEL_BATCH_COPY',
					$this->setBatchCopy($nameSingleCode)
				);

				// MODEL_BATCH_MOVE <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|MODEL_BATCH_MOVE',
					$this->setBatchMove($nameSingleCode)
				);

				// BATCH_ONCLICK_CANCEL_SCRIPT <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameListCode . '|BATCH_ONCLICK_CANCEL_SCRIPT', ''); // TODO <-- must still be build

				// JCONTROLLERFORM_ALLOWADD <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|JCONTROLLERFORM_ALLOWADD',
					CFactory::_('Architecture.Controller.AllowAdd')->get(
						$nameSingleCode,
					)
				);

				// JCONTROLLERFORM_BEFORECANCEL <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|JCONTROLLERFORM_BEFORECANCEL',
					CFactory::_('Customcode.Dispenser')->get(
						'php_before_cancel', $nameSingleCode,
						PHP_EOL, null, false,
						''
					)
				);

				// JCONTROLLERFORM_AFTERCANCEL <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|JCONTROLLERFORM_AFTERCANCEL',
					CFactory::_('Customcode.Dispenser')->get(
						'php_after_cancel', $nameSingleCode,
						PHP_EOL, null, false,
						''
					)
				);

				// JCONTROLLERFORM_ALLOWEDIT <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|JCONTROLLERFORM_ALLOWEDIT',
					CFactory::_('Architecture.Controller.AllowEdit')->get(
						$nameSingleCode,
						$nameListCode
					)
				);

				// JMODELADMIN_GETFORM <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|JMODELADMIN_GETFORM',
					$this->setJmodelAdminGetForm(
						$nameSingleCode,
						$nameListCode
					)
				);

				// JMODELADMIN_ALLOWEDIT <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|JMODELADMIN_ALLOWEDIT',
					CFactory::_('Architecture.Model.AllowEdit')->get(
						$nameSingleCode,
						$nameListCode
					)
				);

				// JMODELADMIN_CANDELETE <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|JMODELADMIN_CANDELETE',
					CFactory::_('Architecture.Model.CanDelete')->get(
						$nameSingleCode
					)
				);

				// JMODELADMIN_CANEDITSTATE <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameSingleCode . '|JMODELADMIN_CANEDITSTATE',
					CFactory::_('Architecture.Model.CanEditState')->get(
						$nameSingleCode
					)
				);

				// set custom admin view Toolbare buttons
				// CUSTOM_ADMIN_DYNAMIC_BUTTONS  <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameListCode . '|CUSTOM_ADMIN_DYNAMIC_BUTTONS',
					CFactory::_('Architecture.DynamicButtons')->get(
						$nameListCode
					)
				);
				// CUSTOM_ADMIN_DYNAMIC_BUTTONS_CONTROLLER  <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameListCode . '|CUSTOM_ADMIN_DYNAMIC_BUTTONS_CONTROLLER',
					$this->setCustomAdminDynamicButtonController(
						$nameListCode
					)
				);

				// ADDTOOLBAR <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($nameListCode . '|ADDTOOLBAR',
					CFactory::_('Architecture.AdminViews.AddToolBar')->get($view)
				);

				// set helper router
				CFactory::_('Compiler.Builder.Content.One')->add('ROUTEHELPER',
					$this->setRouterHelp(
					$nameSingleCode,
					$nameListCode
				));

				if (isset($view['edit_create_site_view'])
					&& is_numeric(
						$view['edit_create_site_view']
					)
					&& $view['edit_create_site_view'] > 0)
				{
					// add needed router stuff for front edit views
					CFactory::_('Compiler.Builder.Content.One')->add('ROUTER_PARSE_SWITCH',
						$this->routerParseSwitch(
						$nameSingleCode, null, false
					));
					CFactory::_('Compiler.Builder.Content.One')->add('ROUTER_BUILD_VIEWS',
						$this->routerBuildViews(
						$nameSingleCode
					));
				}

				// ACCESS_SECTIONS
				CFactory::_('Compiler.Builder.Content.One')->add('ACCESS_SECTIONS',
					CFactory::_('Compiler.Creator.Access.Sections.Category')->get(
						$nameSingleCode,
						$nameListCode
					)
				);
				// set the Joomla Fields ACCESS section if needed
				if (isset($view['joomla_fields'])
					&& $view['joomla_fields'] == 1)
				{
					CFactory::_('Compiler.Builder.Content.One')->add('ACCESS_SECTIONS',
						CFactory::_('Compiler.Creator.Access.Sections.Joomla.Fields')->get()
					);
				}

				// Trigger Event: jcb_ce_onAfterBuildAdminViewContent
				CFactory::_('Event')->trigger(
					'jcb_ce_onAfterBuildAdminViewContent', [&$view, &$nameSingleCode, &$nameListCode]
				);
			}

			// setup the layouts
			$this->setCustomViewLayouts();

			// setup custom_admin_views and all needed stuff for the site
			if (CFactory::_('Component')->isArray('custom_admin_views'))
			{
				CFactory::_('Config')->build_target = 'custom_admin';
				CFactory::_('Config')->lang_target = 'admin';
				// start dynamic build
				foreach (CFactory::_('Component')->get('custom_admin_views') as $view)
				{
					// for single views
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SView', $view['settings']->Code);
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|sview', $view['settings']->code);
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SVIEW', $view['settings']->CODE);
					// for list views
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SViews', $view['settings']->Code);
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|sviews', $view['settings']->code);
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SVIEWS', $view['settings']->CODE);
					// add to lang array
					CFactory::_('Language')->set(
						CFactory::_('Config')->lang_target,
						CFactory::_('Config')->lang_prefix . '_' . $view['settings']->CODE,
						$view['settings']->name
					);
					CFactory::_('Language')->set(
						CFactory::_('Config')->lang_target,
						CFactory::_('Config')->lang_prefix . '_' . $view['settings']->CODE
						. '_DESC', $view['settings']->description
					);
					// ICOMOON <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|ICOMOON', $view['icomoon']);

					// set placeholders
					CFactory::_('Placeholder')->set('SView', $view['settings']->Code);
					CFactory::_('Placeholder')->set('sview', $view['settings']->code);
					CFactory::_('Placeholder')->set('SVIEW', $view['settings']->CODE);

					CFactory::_('Placeholder')->set('SViews', $view['settings']->Code);
					CFactory::_('Placeholder')->set('sviews', $view['settings']->code);
					CFactory::_('Placeholder')->set('SVIEWS', $view['settings']->CODE);

					// Trigger Event: jcb_ce_onBeforeBuildCustomAdminViewContent
					CFactory::_('Event')->trigger(
						'jcb_ce_onBeforeBuildCustomAdminViewContent', [&$view, &$view['settings']->code]
					);

					// set license per view if needed
					$this->setLockLicensePer(
						$view['settings']->code, CFactory::_('Config')->build_target
					);

					// check if this custom admin view is the default view
					if (CFactory::_('Registry')->get('build.dashboard.type', '') === 'custom_admin_views'
						&& CFactory::_('Registry')->get('build.dashboard', '') === $view['settings']->code)
					{
						// HIDEMAINMENU <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|HIDEMAINMENU', '');
					}
					else
					{
						if (CFactory::_('Config')->get('joomla_version', 3) == 3)
						{
							// HIDEMAINMENU <<<DYNAMIC>>>
							CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|HIDEMAINMENU',
								PHP_EOL . Indent::_(2) . '//' . Line::_(
									__LINE__,__CLASS__
								) . " hide the main menu"
								. PHP_EOL . Indent::_(2)
								. "\$this->app->input->set('hidemainmenu', true);"
							);
						}
						else
						{
							// HIDEMAINMENU <<<DYNAMIC>>>
							CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|HIDEMAINMENU',
								PHP_EOL . Indent::_(2) . '//' . Line::_(
									__LINE__,__CLASS__
								) . " hide the main menu"
								. PHP_EOL . Indent::_(2)
								. "\$this->input->set('hidemainmenu', true);"
							);
						}
					}

					if ($view['settings']->main_get->gettype == 1)
					{
						// CUSTOM_ADMIN_BEFORE_GET_ITEM <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_BEFORE_GET_ITEM',
							CFactory::_('Customcode.Dispenser')->get(
								CFactory::_('Config')->build_target . '_php_before_getitem',
								$view['settings']->code, '', null, true
							)
						);

						// CUSTOM_ADMIN_GET_ITEM <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_GET_ITEM',
							CFactory::_('Dynamicget.GetItem')->get(
								$view['settings']->main_get,
								$view['settings']->code, Indent::_(2)
							)
						);

						// CUSTOM_ADMIN_AFTER_GET_ITEM <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_AFTER_GET_ITEM',
							CFactory::_('Customcode.Dispenser')->get(
								CFactory::_('Config')->build_target . '_php_after_getitem',
								$view['settings']->code, '', null, true
							)
						);
					}
					elseif ($view['settings']->main_get->gettype == 2)
					{
						// CUSTOM_ADMIN_GET_LIST_QUERY <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_GET_LIST_QUERY',
							CFactory::_('Dynamicget.ListQuery')->get(
								$view['settings']->main_get, $view['settings']->code
							)
						);

						// CUSTOM_ADMIN_CUSTOM_BEFORE_LIST_QUERY <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_CUSTOM_BEFORE_LIST_QUERY',
							CFactory::_('Customcode.Dispenser')->get(
								CFactory::_('Config')->build_target . '_php_getlistquery',
								$view['settings']->code, PHP_EOL, null, true
							)
						);

						// CUSTOM_ADMIN_BEFORE_GET_ITEMS <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_BEFORE_GET_ITEMS',
							CFactory::_('Customcode.Dispenser')->get(
								CFactory::_('Config')->build_target . '_php_before_getitems',
									$view['settings']->code, PHP_EOL, null, true
							)
						);

						// CUSTOM_ADMIN_GET_ITEMS <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_GET_ITEMS',
							CFactory::_('Dynamicget.GetItems')->get(
								$view['settings']->main_get, $view['settings']->code
							)
						);

						// CUSTOM_ADMIN_AFTER_GET_ITEMS <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_AFTER_GET_ITEMS',
							CFactory::_('Customcode.Dispenser')->get(
								CFactory::_('Config')->build_target . '_php_after_getitems',
								$view['settings']->code, PHP_EOL, null, true
							)
						);
					}

					// CUSTOM_ADMIN_CUSTOM_METHODS <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_CUSTOM_METHODS',
						CFactory::_('Dynamicget.CustomGetMethods')->get(
							$view['settings']->main_get, $view['settings']->code
						)
					);
					CFactory::_('Compiler.Builder.Content.Multi')->add($view['settings']->code . '|CUSTOM_ADMIN_CUSTOM_METHODS',
						CFactory::_('Dynamicget.Methods')->get(
							$view, $view['settings']->code
						). false
					);
					// CUSTOM_ADMIN_DIPLAY_METHOD <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_DIPLAY_METHOD',
						$this->setCustomViewDisplayMethod($view)
					);
					// set document details
					$this->setPrepareDocument($view);
					// CUSTOM_ADMIN_EXTRA_DIPLAY_METHODS <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_EXTRA_DIPLAY_METHODS',
						$this->setCustomViewExtraDisplayMethods($view)
					);
					// CUSTOM_ADMIN_CODE_BODY <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_CODE_BODY',
						$this->setCustomViewCodeBody($view)
					);
					// CUSTOM_ADMIN_BODY <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_BODY',
						$this->setCustomViewBody($view)
					);
					// CUSTOM_ADMIN_SUBMITBUTTON_SCRIPT <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_SUBMITBUTTON_SCRIPT',
						$this->setCustomViewSubmitButtonScript($view)
					);

					// setup the templates
					$this->setCustomViewTemplateBody($view);

					// set the site form if needed
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_TOP_FORM',
						$this->setCustomViewForm(
							$view['settings']->code,
							$view['settings']->main_get->gettype, 1
						)
					);
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_BOTTOM_FORM',
						$this->setCustomViewForm(
							$view['settings']->code,
							$view['settings']->main_get->gettype, 2
						)
					);

					// set headers based on the main get type
					if ($view['settings']->main_get->gettype == 1)
					{
						// CUSTOM_ADMIN_VIEW_CONTROLLER_HEADER <<<DYNAMIC>>> add the header details for the controller
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_VIEW_CONTROLLER_HEADER',
							CFactory::_('Header')->get(
								'custom.admin.view.controller',
								$view['settings']->code
							)
						);
						// CUSTOM_ADMIN_VIEW_MODEL_HEADER <<<DYNAMIC>>> add the header details for the model
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_VIEW_MODEL_HEADER',
							CFactory::_('Header')->get(
								'custom.admin.view.model', $view['settings']->code
							)
						);
						// CUSTOM_ADMIN_VIEW_HTML_HEADER <<<DYNAMIC>>> add the header details for the view
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_VIEW_HTML_HEADER',
							CFactory::_('Header')->get(
								'custom.admin.view.html', $view['settings']->code
							)
						);
						// CUSTOM_ADMIN_VIEW_HEADER <<<DYNAMIC>>> add the header details for the view
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_VIEW_HEADER',
							CFactory::_('Header')->get(
								'custom.admin.view', $view['settings']->code
							)
						);
						// CUSTOM_ADMIN_ADDTOOLBAR <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_ADDTOOLBAR',
							CFactory::_('Architecture.CustomAdminView.AddToolBar')->get($view)
						);
					}
					elseif ($view['settings']->main_get->gettype == 2)
					{
						// CUSTOM_ADMIN_VIEWS_CONTROLLER_HEADER <<<DYNAMIC>>> add the header details for the controller
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_VIEWS_CONTROLLER_HEADER',
							CFactory::_('Header')->get(
								'custom.admin.views.controller',
								$view['settings']->code
							)
						);
						// CUSTOM_ADMIN_VIEWS_MODEL_HEADER <<<DYNAMIC>>> add the header details for the model
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_VIEWS_MODEL_HEADER',
							CFactory::_('Header')->get(
								'custom.admin.views.model', $view['settings']->code
							)
						);
						// CUSTOM_ADMIN_VIEWS_HTML_HEADER <<<DYNAMIC>>> add the header details for the view
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_VIEWS_HTML_HEADER',
							CFactory::_('Header')->get(
								'custom.admin.views.html', $view['settings']->code
							)
						);
						// CUSTOM_ADMIN_VIEWS_HEADER <<<DYNAMIC>>> add the header details for the view
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_VIEWS_HEADER',
							CFactory::_('Header')->get(
								'custom.admin.views', $view['settings']->code
							)
						);
						// CUSTOM_ADMIN_ADDTOOLBAR <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|CUSTOM_ADMIN_ADDTOOLBAR',
							CFactory::_('Architecture.CustomAdminViews.AddToolBar')->get($view)
						);
					}

					// Trigger Event: jcb_ce_onAfterBuildCustomAdminViewContent
					CFactory::_('Event')->trigger(
						'jcb_ce_onAfterBuildCustomAdminViewContent', [&$view, &$view['settings']->code]
					);
				}

				// setup the layouts
				$this->setCustomViewLayouts();
			}

			// ADMIN_HELPER_CLASS_HEADER
			CFactory::_('Compiler.Builder.Content.One')->set('ADMIN_HELPER_CLASS_HEADER',
				CFactory::_('Header')->get(
				'admin.helper', 'admin'
			));

			// ADMIN_COMPONENT_HEADER
			CFactory::_('Compiler.Builder.Content.One')->set('ADMIN_COMPONENT_HEADER',
				CFactory::_('Header')->get(
				'admin.component', 'admin'
			));

			// SITE_HELPER_CLASS_HEADER
			CFactory::_('Compiler.Builder.Content.One')->set('SITE_HELPER_CLASS_HEADER',
				CFactory::_('Header')->get(
				'site.helper', 'site'
			));

			// SITE_COMPONENT_HEADER
			CFactory::_('Compiler.Builder.Content.One')->set('SITE_COMPONENT_HEADER',
				CFactory::_('Header')->get(
				'site.component', 'site'
			));

			// SITE_ROUTER_HEADER (Joomla 4 and above)
			CFactory::_('Compiler.Builder.Content.One')->set('SITE_ROUTER_HEADER',
				CFactory::_('Header')->get(
					'site.router', 'site'
			));

			// HELPER_EXEL
			CFactory::_('Compiler.Builder.Content.One')->set('HELPER_EXEL', $this->setHelperExelMethods());

			// VIEWARRAY
			CFactory::_('Compiler.Builder.Content.One')->set('VIEWARRAY',
				PHP_EOL . implode("," . PHP_EOL, $viewarray)
			);

			// SITE_EDIT_VIEW_ARRAY (Joomla3 only)
			CFactory::_('Compiler.Builder.Content.One')->set('SITE_EDIT_VIEW_ARRAY',
				PHP_EOL . Indent::_(4) . "'" . implode("'," . PHP_EOL . Indent::_(4) . "'", array_keys($site_edit_view_array)) . "'"
			);

			// SITE_ALLOW_EDIT_VIEWS_ARRAY
			CFactory::_('Compiler.Builder.Content.One')->set('SITE_ALLOW_EDIT_VIEWS_ARRAY',
				CFactory::_('Architecture.Controller.AllowEditViews')->getArray($site_edit_view_array)
			);

			// SITE_ALLOW_EDIT_VIEWS_FUNCTIONS
			CFactory::_('Compiler.Builder.Content.One')->set('SITE_ALLOW_EDIT_VIEWS_FUNCTIONS',
				CFactory::_('Architecture.Controller.AllowEditViews')->getFunctions($site_edit_view_array)
			);

			// MAINMENUS
			CFactory::_('Compiler.Builder.Content.One')->set('MAINMENUS', $this->setMainMenus());

			// SUBMENU
			CFactory::_('Compiler.Builder.Content.One')->set('SUBMENU', $this->setSubMenus());

			// GET_CRYPT_KEY
			CFactory::_('Compiler.Builder.Content.One')->set('GET_CRYPT_KEY', $this->setGetCryptKey());

			// set the license locker
			$this->setLockLicense();

			// CONTRIBUTORS
			CFactory::_('Compiler.Builder.Content.One')->set('CONTRIBUTORS',
				CFactory::_('Compiler.Builder.Contributors')->get('bom', '')
			);

			// INSTALL
			CFactory::_('Compiler.Builder.Content.One')->set('INSTALL', $this->setInstall());

			// UNINSTALL
			CFactory::_('Compiler.Builder.Content.One')->set('UNINSTALL', $this->setUninstall());

			// UPDATE_VERSION_MYSQL
			$this->setVersionController();

			// only set these if default dashboard it used
			if (!CFactory::_('Registry')->get('build.dashboard'))
			{
				// DASHBOARDVIEW
				CFactory::_('Compiler.Builder.Content.One')->set('DASHBOARDVIEW',
					CFactory::_('Config')->component_code_name
				);

				// DASHBOARDICONS
				CFactory::_('Compiler.Builder.Content.Multi')->set(CFactory::_('Config')->component_code_name . '|DASHBOARDICONS',
					$this->setDashboardIcons()
				);

				// DASHBOARDICONACCESS
				CFactory::_('Compiler.Builder.Content.Multi')->set(CFactory::_('Config')->component_code_name . '|DASHBOARDICONACCESS',
					CFactory::_('Compiler.Builder.Permission.Dashboard')->build()
				);

				// DASH_MODEL_METHODS
				CFactory::_('Compiler.Builder.Content.Multi')->set(CFactory::_('Config')->component_code_name . '|DASH_MODEL_METHODS',
					$this->setDashboardModelMethods()
				);

				// DASH_GET_CUSTOM_DATA
				CFactory::_('Compiler.Builder.Content.Multi')->set(CFactory::_('Config')->component_code_name . '|DASH_GET_CUSTOM_DATA',
					$this->setDashboardGetCustomData()
				);

				// DASH_DISPLAY_DATA
				CFactory::_('Compiler.Builder.Content.Multi')->set(CFactory::_('Config')->component_code_name . '|DASH_DISPLAY_DATA',
					CFactory::_('Architecture.Dashboard.View')->get()
				);

				// DASH_VIEW_HEADER
				CFactory::_('Compiler.Builder.Content.Multi')->set(CFactory::_('Config')->component_code_name . '|DASH_VIEW_HEADER',
					CFactory::_('Header')->get('dashboard.view', 'dashboard')
				);
				// DASH_VIEW_HTML_HEADER
				CFactory::_('Compiler.Builder.Content.Multi')->set(CFactory::_('Config')->component_code_name . '|DASH_VIEW_HTML_HEADER',
					CFactory::_('Header')->get('dashboard.view.html', 'dashboard')
				);
				// DASH_MODEL_HEADER
				CFactory::_('Compiler.Builder.Content.Multi')->set(CFactory::_('Config')->component_code_name . '|DASH_MODEL_HEADER',
					CFactory::_('Header')->get('dashboard.model', 'dashboard')
				);
				// DASH_CONTROLLER_HEADER
				CFactory::_('Compiler.Builder.Content.Multi')->set(CFactory::_('Config')->component_code_name . '|DASH_CONTROLLER_HEADER',
					CFactory::_('Header')->get('dashboard.controller', 'dashboard')
				);
			}
			else
			{
				// DASHBOARDVIEW
				CFactory::_('Compiler.Builder.Content.One')->set('DASHBOARDVIEW',
					CFactory::_('Registry')->get('build.dashboard')
				);
			}

			// add import
			if (CFactory::_('Config')->get('add_eximport', false))
			{
				// setup import files
				$target = array('admin' => 'import');
				CFactory::_('Utilities.Structure')->build($target, 'import');
				// IMPORT_EXT_METHOD <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set('import' . '|IMPORT_EXT_METHOD',
					PHP_EOL . PHP_EOL . CFactory::_('Placeholder')->update_(
						ComponentbuilderHelper::getDynamicScripts('ext')
					)
				);
				// IMPORT_SETDATA_METHOD <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set('import' . '|IMPORT_SETDATA_METHOD',
					PHP_EOL . PHP_EOL . CFactory::_('Placeholder')->update_(
						ComponentbuilderHelper::getDynamicScripts('setdata')
					)
				);
				// IMPORT_SAVE_METHOD <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set('import' . '|IMPORT_SAVE_METHOD',
					PHP_EOL . PHP_EOL . CFactory::_('Placeholder')->update_(
						ComponentbuilderHelper::getDynamicScripts('save')
					)
				);
				// IMPORT_CONTROLLER_HEADER <<<DYNAMIC>>> add the header details for the controller
				CFactory::_('Compiler.Builder.Content.Multi')->set('import' . '|IMPORT_CONTROLLER_HEADER',
					CFactory::_('Header')->get(
						'import.controller', 'import'
					)
				);
				// IMPORT_MODEL_HEADER <<<DYNAMIC>>> add the header details for the model
				CFactory::_('Compiler.Builder.Content.Multi')->set('import' . '|IMPORT_MODEL_HEADER',
					CFactory::_('Header')->get(
						'import.model', 'import'
					)
				);
			}

			// ensure that the ajax model and controller is set if needed
			if (CFactory::_('Config')->get('add_ajax', false))
			{
				// setup Ajax files
				$target = array('admin' => 'ajax');
				CFactory::_('Utilities.Structure')->build($target, 'ajax');
				// set the controller
				CFactory::_('Compiler.Builder.Content.Multi')->set('ajax' . '|REGISTER_AJAX_TASK',
					$this->setRegisterAjaxTask('admin')
				);
				CFactory::_('Compiler.Builder.Content.Multi')->set('ajax' . '|AJAX_INPUT_RETURN',
					$this->setAjaxInputReturn('admin')
				);
				// set the model header
				CFactory::_('Compiler.Builder.Content.Multi')->set('ajax' . '|AJAX_ADMIN_MODEL_HEADER',
					CFactory::_('Header')->get('ajax.admin.model', 'ajax')
				);
				// set the module
				CFactory::_('Compiler.Builder.Content.Multi')->set('ajax' . '|AJAX_MODEL_METHODS',
					$this->setAjaxModelMethods('admin')
				);
			}

			// ensure that the site ajax model and controller is set if needed
			if (CFactory::_('Config')->get('add_site_ajax', false))
			{
				// setup Ajax files
				$target = array('site' => 'ajax');
				CFactory::_('Utilities.Structure')->build($target, 'ajax');
				// set the controller
				CFactory::_('Compiler.Builder.Content.Multi')->set('ajax' . '|REGISTER_SITE_AJAX_TASK',
					$this->setRegisterAjaxTask('site')
				);
				CFactory::_('Compiler.Builder.Content.Multi')->set('ajax' . '|AJAX_SITE_INPUT_RETURN',
					$this->setAjaxInputReturn('site')
				);
				// set the model header
				CFactory::_('Compiler.Builder.Content.Multi')->set('ajax' . '|AJAX_SITE_MODEL_HEADER',
					CFactory::_('Header')->get('ajax.site.model', 'ajax')
				);
				// set the module
				CFactory::_('Compiler.Builder.Content.Multi')->set('ajax' . '|AJAX_SITE_MODEL_METHODS',
					$this->setAjaxModelMethods('site')
				);
			}

			// build the validation rules
			if (($validationRules = CFactory::_('Registry')->_('validation.rules')) !== null)
			{
				foreach ($validationRules as $rule => $_php)
				{
					// setup rule file
					$target = array('admin' => 'a_rule_zi');
					CFactory::_('Utilities.Structure')->build($target, 'rule', $rule);
					// set the JFormRule Name
					CFactory::_('Compiler.Builder.Content.Multi')->set('a_rule_zi_' . $rule . '|Name',
						ucfirst((string) $rule)
					);
					// set the JFormRule PHP
					CFactory::_('Compiler.Builder.Content.Multi')->set('a_rule_zi_' . $rule . '|VALIDATION_RULE_METHODS',
						PHP_EOL . $_php
					);
				}
			}

			// run the second run if needed
			$secondRunAdmin = CFactory::_('Compiler.Builder.Second.Run.Admin')
				->allActive() + (array) $this->secondRunAdmin;
			if (ArrayHelper::check($secondRunAdmin))
			{
				// start dynamic build
				foreach ($secondRunAdmin as $function => $arrays)
				{
					if (ArrayHelper::check($arrays)
						&& StringHelper::check($function))
					{
						foreach ($arrays as $array)
						{
							$this->{$function}($array);
						}
					}
				}
			}

			// CONFIG_FIELDSETS
			$keepLang   = CFactory::_('Config')->lang_target;
			CFactory::_('Config')->lang_target = 'admin';
			// run field sets for second time
			CFactory::_('Compiler.Creator.Config.Fieldsets')->set(2);
			CFactory::_('Config')->lang_target = $keepLang;

			// setup front-views and all needed stuff for the site
			if (CFactory::_('Component')->isArray('site_views'))
			{
				CFactory::_('Config')->build_target = 'site';
				// start dynamic build
				foreach (CFactory::_('Component')->get('site_views') as $view)
				{
					// for list views
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SViews',
						$view['settings']->Code
					);
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|sviews',
						$view['settings']->code
					);
					// for single views
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SView',
						$view['settings']->Code
					);
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|sview',
						$view['settings']->code
					);

					// set placeholders
					CFactory::_('Placeholder')->set('SView', $view['settings']->Code);
					CFactory::_('Placeholder')->set('sview', $view['settings']->code);
					CFactory::_('Placeholder')->set('SVIEW', $view['settings']->CODE);

					CFactory::_('Placeholder')->set('SViews', $view['settings']->Code);
					CFactory::_('Placeholder')->set('sviews', $view['settings']->code);
					CFactory::_('Placeholder')->set('SVIEWS', $view['settings']->CODE);

					// Trigger Event: jcb_ce_onBeforeBuildSiteViewContent
					CFactory::_('Event')->trigger(
						'jcb_ce_onBeforeBuildSiteViewContent', [&$view, &$view['settings']->code]
					);

					// set license per view if needed
					$this->setLockLicensePer(
						$view['settings']->code, CFactory::_('Config')->build_target
					);

					// set the site default view
					if (isset($view['default_view'])
						&& $view['default_view'] == 1)
					{
						CFactory::_('Compiler.Builder.Content.One')->set('SITE_DEFAULT_VIEW',
							$view['settings']->code
						);
					}
					// add site menu
					if (isset($view['menu']) && $view['menu'] == 1)
					{
						// SITE_MENU_XML <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_MENU_XML',
							$this->setCustomViewMenu($view)
						);
					}

					// insure the needed route helper is loaded
					CFactory::_('Compiler.Builder.Content.One')->add('ROUTEHELPER',
						$this->setRouterHelp(
						$view['settings']->code, $view['settings']->code, true
					));
					// build route details
					CFactory::_('Compiler.Builder.Content.One')->add('ROUTER_PARSE_SWITCH',
						$this->routerParseSwitch(
						$view['settings']->code, $view
					));
					CFactory::_('Compiler.Builder.Content.One')->add('ROUTER_BUILD_VIEWS',
						$this->routerBuildViews($view['settings']->code)
					);

					if ($view['settings']->main_get->gettype == 1)
					{
						// set user permission access check USER_PERMISSION_CHECK_ACCESS <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|USER_PERMISSION_CHECK_ACCESS',
							$this->setUserPermissionCheckAccess($view, 1)
						);

						// SITE_BEFORE_GET_ITEM <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_BEFORE_GET_ITEM',
							CFactory::_('Customcode.Dispenser')->get(
								CFactory::_('Config')->build_target . '_php_before_getitem',
								$view['settings']->code, '', null, true
							)
						);

						// SITE_GET_ITEM <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_GET_ITEM',
							CFactory::_('Dynamicget.GetItem')->get(
								$view['settings']->main_get,
								$view['settings']->code, Indent::_(2)
							)
						);

						// SITE_AFTER_GET_ITEM <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_AFTER_GET_ITEM',
							CFactory::_('Customcode.Dispenser')->get(
								CFactory::_('Config')->build_target . '_php_after_getitem',
								$view['settings']->code, '', null, true
							)
						);
					}
					elseif ($view['settings']->main_get->gettype == 2)
					{
						// set user permission access check USER_PERMISSION_CHECK_ACCESS <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|USER_PERMISSION_CHECK_ACCESS',
							$this->setUserPermissionCheckAccess($view, 2)
						);
						// SITE_GET_LIST_QUERY <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_GET_LIST_QUERY',
							CFactory::_('Dynamicget.ListQuery')->get(
								$view['settings']->main_get, $view['settings']->code
							)
						);

						// SITE_BEFORE_GET_ITEMS <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_BEFORE_GET_ITEMS', CFactory::_('Customcode.Dispenser')->get(
							CFactory::_('Config')->build_target . '_php_before_getitems',
							$view['settings']->code, PHP_EOL, null, true
						));

						// SITE_GET_ITEMS <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_GET_ITEMS',
							CFactory::_('Dynamicget.GetItems')->get(
								$view['settings']->main_get, $view['settings']->code
							)
						);

						// SITE_AFTER_GET_ITEMS <<<DYNAMIC>>>
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_AFTER_GET_ITEMS',
							CFactory::_('Customcode.Dispenser')->get(
								CFactory::_('Config')->build_target . '_php_after_getitems',
								$view['settings']->code, PHP_EOL, null, true
							)
						);
					}
					// add to lang array
					CFactory::_('Language')->set(
						'site',
						CFactory::_('Config')->lang_prefix . '_' . $view['settings']->CODE,
						$view['settings']->name
					);
					CFactory::_('Language')->set(
						'site',
						CFactory::_('Config')->lang_prefix . '_' . $view['settings']->CODE
						. '_DESC', $view['settings']->description
					);
					// SITE_CUSTOM_METHODS <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_CUSTOM_METHODS',
						CFactory::_('Dynamicget.CustomGetMethods')->get(
							$view['settings']->main_get, $view['settings']->code
						)
					);
					CFactory::_('Compiler.Builder.Content.Multi')->add($view['settings']->code . '|SITE_CUSTOM_METHODS',
						CFactory::_('Dynamicget.Methods')->get(
							$view, $view['settings']->code
						), false
					);
					// SITE_DIPLAY_METHOD <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_DIPLAY_METHOD',
						$this->setCustomViewDisplayMethod($view)
					);
					// set document details
					$this->setPrepareDocument($view);
					// SITE_EXTRA_DIPLAY_METHODS <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_EXTRA_DIPLAY_METHODS',
						$this->setCustomViewExtraDisplayMethods($view)
					);
					// SITE_CODE_BODY <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_CODE_BODY',
						$this->setCustomViewCodeBody($view)
					);
					// SITE_BODY <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_BODY',
						$this->setCustomViewBody($view)
					);
					// SITE_ADDTOOLBAR <<<DYNAMIC>>>
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_ADDTOOLBAR',
						CFactory::_('Architecture.SiteView.AddToolBar')->get($view)
					);

					// setup the templates
					$this->setCustomViewTemplateBody($view);

					// set the site form if needed
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_TOP_FORM',
						$this->setCustomViewForm(
							$view['settings']->code,
							$view['settings']->main_get->gettype, 1
						)
					);
					CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_BOTTOM_FORM',
						$this->setCustomViewForm(
							$view['settings']->code,
							$view['settings']->main_get->gettype, 2
						)
					);

					// set headers based on the main get type
					if ($view['settings']->main_get->gettype == 1)
					{
						// insure the controller headers are added
						if (StringHelper::check(
								$view['settings']->php_controller
							)
							&& $view['settings']->php_controller != '//')
						{
							// SITE_VIEW_CONTROLLER_HEADER <<<DYNAMIC>>> add the header details for the model
							CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_VIEW_CONTROLLER_HEADER',
								CFactory::_('Header')->get(
									'site.view.controller', $view['settings']->code
								)
							);
						}
						// SITE_VIEW_MODEL_HEADER <<<DYNAMIC>>> add the header details for the model
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_VIEW_MODEL_HEADER',
							CFactory::_('Header')->get(
								'site.view.model', $view['settings']->code
							)
						);
						// SITE_VIEW_HTML_HEADER <<<DYNAMIC>>> add the header details for the view
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_VIEW_HTML_HEADER',
							CFactory::_('Header')->get(
								'site.view.html', $view['settings']->code
							)
						);
						// SITE_VIEW_HEADER <<<DYNAMIC>>> add the header details for the view
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_VIEW_HEADER',
							CFactory::_('Header')->get(
								'site.view', $view['settings']->code
							)
						);
					}
					elseif ($view['settings']->main_get->gettype == 2)
					{
						// insure the controller headers are added
						if (StringHelper::check(
								$view['settings']->php_controller
							)
							&& $view['settings']->php_controller != '//')
						{
							// SITE_VIEW_CONTROLLER_HEADER <<<DYNAMIC>>> add the header details for the model
							CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_VIEW_CONTROLLER_HEADER',
								CFactory::_('Header')->get(
									'site.views.controller', $view['settings']->code
								)
							);
						}
						// SITE_VIEWS_MODEL_HEADER <<<DYNAMIC>>> add the header details for the model
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_VIEWS_MODEL_HEADER',
							CFactory::_('Header')->get(
								'site.views.model', $view['settings']->code
							)
						);
						// SITE_VIEWS_HTML_HEADER <<<DYNAMIC>>> add the header details for the view
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_VIEWS_HTML_HEADER',
							CFactory::_('Header')->get(
								'site.views.html', $view['settings']->code
							)
						);
						// SITE_VIEWS_HEADER <<<DYNAMIC>>> add the header details for the view
						CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_VIEWS_HEADER',
							CFactory::_('Header')->get(
								'site.views', $view['settings']->code
							)
						);
					}

					// Trigger Event: jcb_ce_onAfterBuildSiteViewContent
					CFactory::_('Event')->trigger(
						'jcb_ce_onAfterBuildSiteViewContent', [&$view, &$view['settings']->code]
					);
				}

				// setup the layouts
				$this->setCustomViewLayouts();
			}
			else
			{
				// clear all site folder since none is needed
				CFactory::_('Config')->remove_site_folder = true;
			}
			// load the site statics
			if (!CFactory::_('Config')->remove_site_folder || !CFactory::_('Config')->remove_site_edit_folder)
			{
				CFactory::_('Config')->build_target = 'site';
				// if no default site view was set, the redirect to root
				if (!CFactory::_('Compiler.Builder.Content.One')->exists('SITE_DEFAULT_VIEW'))
				{
					CFactory::_('Compiler.Builder.Content.One')->set('SITE_DEFAULT_VIEW', '');
				}
				// set site custom script to helper class
				// SITE_CUSTOM_HELPER_SCRIPT
				CFactory::_('Compiler.Builder.Content.One')->set('SITE_CUSTOM_HELPER_SCRIPT',
					CFactory::_('Placeholder')->update_(
					CFactory::_('Customcode.Dispenser')->hub['component_php_helper_site']
				));
				// SITE_GLOBAL_EVENT_HELPER
				if (!CFactory::_('Compiler.Builder.Content.One')->exists('SITE_GLOBAL_EVENT'))
				{
					CFactory::_('Compiler.Builder.Content.One')->set('SITE_GLOBAL_EVENT', '');
				}
				if (!CFactory::_('Compiler.Builder.Content.One')->exists('SITE_GLOBAL_EVENT_HELPER'))
				{
					CFactory::_('Compiler.Builder.Content.One')->set('SITE_GLOBAL_EVENT_HELPER', '');
				}
				// now load the data for the global event if needed
				if (CFactory::_('Component')->get('add_site_event', 0) == 1)
				{
					CFactory::_('Compiler.Builder.Content.One')->add('SITE_GLOBAL_EVENT', PHP_EOL . PHP_EOL . "//" . Line::_(
							__LINE__,__CLASS__
						) . "Trigger the Global Site Event");
					CFactory::_('Compiler.Builder.Content.One')->add('SITE_GLOBAL_EVENT',
						PHP_EOL . CFactory::_('Compiler.Builder.Content.One')->get('Component')
						. 'Helper::globalEvent(Factory::getDocument());');
					// SITE_GLOBAL_EVENT_HELPER
					CFactory::_('Compiler.Builder.Content.One')->add('SITE_GLOBAL_EVENT_HELPER',
						PHP_EOL . PHP_EOL . Indent::_(1) . '/**'
					);
					CFactory::_('Compiler.Builder.Content.One')->add('SITE_GLOBAL_EVENT_HELPER',
						PHP_EOL . Indent::_(1)
						. '*	The Global Site Event Method.');
					CFactory::_('Compiler.Builder.Content.One')->add('SITE_GLOBAL_EVENT_HELPER',
						PHP_EOL . Indent::_(1) . '**/'
					);
					CFactory::_('Compiler.Builder.Content.One')->add('SITE_GLOBAL_EVENT_HELPER',
						PHP_EOL . Indent::_(1)
						. 'public static function globalEvent($document)');
					CFactory::_('Compiler.Builder.Content.One')->add('SITE_GLOBAL_EVENT_HELPER',
						PHP_EOL . Indent::_(1) . '{'
					);
					CFactory::_('Compiler.Builder.Content.One')->add('SITE_GLOBAL_EVENT_HELPER',
						PHP_EOL . CFactory::_('Placeholder')->update_(
							CFactory::_('Customcode.Dispenser')->hub['component_php_site_event']
						));
					CFactory::_('Compiler.Builder.Content.One')->add('SITE_GLOBAL_EVENT_HELPER',
						PHP_EOL . Indent::_(1) . '}'
					);
				}
			}

			// PREINSTALLSCRIPT
			CFactory::_('Compiler.Builder.Content.One')->add('PREINSTALLSCRIPT',
				CFactory::_('Customcode.Dispenser')->get(
				'php_preflight', 'install', PHP_EOL, null, true
			));

			// PREUPDATESCRIPT
			CFactory::_('Compiler.Builder.Content.One')->add('PREUPDATESCRIPT',
				CFactory::_('Customcode.Dispenser')->get(
				'php_preflight', 'update', PHP_EOL, null, true
			));

			// POSTINSTALLSCRIPT
			CFactory::_('Compiler.Builder.Content.One')->add('POSTINSTALLSCRIPT', $this->setPostInstallScript());

			// POSTUPDATESCRIPT
			CFactory::_('Compiler.Builder.Content.One')->add('POSTUPDATESCRIPT', $this->setPostUpdateScript());

			// UNINSTALLSCRIPT
			CFactory::_('Compiler.Builder.Content.One')->add('UNINSTALLSCRIPT', $this->setUninstallScript());

			// INSTALLERMETHODS
			CFactory::_('Compiler.Builder.Content.One')->add('INSTALLERMETHODS', CFactory::_('Customcode.Dispenser')->get(
				'php_method', 'install', PHP_EOL
			));

			// MOVEFOLDERSSCRIPT
			CFactory::_('Compiler.Builder.Content.One')->set('MOVEFOLDERSSCRIPT', $this->setMoveFolderScript());

			// INSTALLERMETHODS2
			CFactory::_('Compiler.Builder.Content.One')->add('INSTALLERMETHODS', $this->setMoveFolderMethod());

			// HELPER_UIKIT
			CFactory::_('Compiler.Builder.Content.One')->set('HELPER_UIKIT', $this->setUikitHelperMethods());

			// CONFIG_FIELDSETS
			CFactory::_('Compiler.Builder.Content.One')->set('CONFIG_FIELDSETS',
				implode(PHP_EOL,
					CFactory::_('Compiler.Builder.Config.Fieldsets')->get('component', [])
				)
			);

			// check if this has been set
			if (!CFactory::_('Compiler.Builder.Content.One')->exists('ROUTER_BUILD_VIEWS')
				|| !StringHelper::check(
					CFactory::_('Compiler.Builder.Content.One')->get('ROUTER_BUILD_VIEWS')
				))
			{
				CFactory::_('Compiler.Builder.Content.One')->set('ROUTER_BUILD_VIEWS', 0);
			}
			else
			{
				CFactory::_('Compiler.Builder.Content.One')->set('ROUTER_BUILD_VIEWS',
					'(' . CFactory::_('Compiler.Builder.Content.One')->get('ROUTER_BUILD_VIEWS') . ')'
				);
			}

			// README
			if (CFactory::_('Component')->get('addreadme'))
			{
				CFactory::_('Compiler.Builder.Content.One')->set('README',
					CFactory::_('Component')->get('readme')
				);
			}

			// CHANGELOG
			if (($changelog = CFactory::_('Component')->get('changelog')) !== null)
			{
				CFactory::_('Compiler.Builder.Content.One')->set('CHANGELOG', $changelog);
			}

			// ROUTER
			if (CFactory::_('Config')->get('joomla_version', 3) != 3)
			{
				// build route constructor before parent call
				CFactory::_('Compiler.Builder.Content.One')->set('SITE_ROUTER_CONSTRUCTOR_BEFORE_PARENT',
					CFactory::_('Compiler.Creator.Router')->getConstructor()
				);
				// build route constructor after parent call
				CFactory::_('Compiler.Builder.Content.One')->set('SITE_ROUTER_CONSTRUCTOR_AFTER_PARENT',
					CFactory::_('Compiler.Creator.Router')->getConstructorAfterParent()
				);
				// build route methods
				CFactory::_('Compiler.Builder.Content.One')->set('SITE_ROUTER_METHODS',
					CFactory::_('Compiler.Creator.Router')->getMethods()
				);
			}

			// all fields stored in database
			CFactory::_('Compiler.Builder.Content.One')->set('ALL_COMPONENT_FIELDS',
				CFactory::_('Compiler.Builder.Component.Fields')->varExport(null, 1)
			);

			// set the autoloader for Powers
			CFactory::_('Power.Autoloader')->setFiles();

			// tweak system to set stuff to the module domain
			$_backup_target     = CFactory::_('Config')->build_target;
			$_backup_lang       = CFactory::_('Config')->lang_target;
			$_backup_langPrefix = CFactory::_('Config')->lang_prefix;

			// infuse module data if set
			CFactory::_('Joomlamodule.Infusion')->set();

			// infuse plugin data if set
			CFactory::_('Joomlaplugin.Infusion')->set();

			// rest globals
			CFactory::_('Config')->build_target = $_backup_target;
			CFactory::_('Config')->lang_target = $_backup_lang;
			CFactory::_('Config')->set('lang_prefix', $_backup_langPrefix);

			// Trigger Event: jcb_ce_onAfterBuildFilesContent
			CFactory::_('Event')->trigger(
				'jcb_ce_onAfterBuildFilesContent'
			);
			return true;
		}

		return false;
	}

	/**
	 * Name one view to everything built for it.
	 *
	 * @param   object  $view  The view being built.
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.Placeholders instead.
	 */
	protected function setViewPlaceholders(&$view)
	{
		CFactory::_('Architecture.View.Placeholders')->set($view);
	}

	/**
	 * Build the language values and insert into file
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.Language.Files instead.
	 */
	public function setLangFileData(): void
	{
		CFactory::_('Architecture.Language.Files')->build();
	}
}
