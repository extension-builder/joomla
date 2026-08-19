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


use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use VDM\Component\Componentbuilder\Administrator\Helper\ComponentbuilderHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\ObjectHelper;
use VDM\Joomla\Utilities\GetHelper;
use VDM\Joomla\Utilities\FileHelper;
use VDM\Joomla\Utilities\MathHelper;
use VDM\Joomla\Componentbuilder\Compiler\Factory as CFactory;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Minify;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Unique;
use VDM\Joomla\Componentbuilder\Compiler\Helper\Fields;
use Joomla\CMS\Form\Form;


/**
 * Interpretation class
 * 
 * @deprecated 3.3
 */
class Interpretation extends Fields
{
	/**
	 * The Import & Export View
	 *
	 * @var      array
	 */
	public $eximportView = [];

	/**
	 * The Import & Export Custom Script
	 *
	 * @var      array
	 */
	public $importCustomScripts = [];

	/**
	 * The contributors
	 *
	 * @var    string
	 */
	public $theContributors = '';

	/**
	 * The unistall script builder
	 *
	 * @var    array
	 */
	public $uninstallScriptBuilder = [];

	/**
	 * The unistall script fields
	 *
	 * @var    array
	 */
	public $uninstallScriptFields = [];

	/**
	 * The unistall script content
	 *
	 * @var    array
	 */
	public $uninstallScriptContent = [];

	/**
	 * The last update url
	 *
	 * @var    array
	 */
	public $lastupdateURL;

	/**
	 * The List Column Builder
	 *
	 * @var    array
	 */
	public $listColnrBuilder = [];

	/**
	 * The customs field builder
	 *
	 * @var    array
	 */
	public $customFieldBuilder = [];

	/**
	 * The category builder
	 *
	 * @var    array
	 */
	public $buildCategories = [];

	/**
	 * The icon builder
	 *
	 * @var    array
	 */
	public $iconBuilder = [];

	/**
	 * The validation fix builder
	 *
	 * @var    array
	 */
	public $validationFixBuilder = [];

	/**
	 * The view script builder
	 *
	 * @var    array
	 */
	public $viewScriptBuilder = [];

	/**
	 * The target relation control
	 *
	 * @var    array
	 */
	public $targetRelationControl = [];

	/**
	 * The target control script checker
	 *
	 * @var    array
	 */
	public $targetControlsScriptChecker = [];

	/**
	 * The router helper
	 *
	 * @var    array
	 */
	public $setRouterHelpDone = [];

	/**
	 * The other where
	 *
	 * @var    array
	 */
	public $otherWhere = [];

	/**
	 * The dashboard get custom data
	 *
	 * @var    array
	 */
	public $DashboardGetCustomData = [];

	/**
	 * The custom admin added
	 *
	 * @var    array
	 */
	public $customAdminAdded = [];

	/**
	 * Custom Admin View List Link
	 *
	 * @var    array
	 */
	protected $customAdminViewListLink = [];

	/**
	 * Custom Admin View List Id
	 *
	 * @var    array
	 * @since  6.1.7
	 */
	protected $customAdminViewListId = [];

	/**
	 * load Tracker of fields to fix
	 *
	 * @var    array
	 */
	protected $loadTracker = [];

	/**
	 * alignment names
	 *
	 * @var    array
	 */
	protected $alignmentOptions
		= array(1 => 'left', 2 => 'right', 3 => 'fullwidth', 4 => 'above',
			5 => 'under', 6 => 'leftside', 7 => 'rightside');

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// first we run the parent constructor
		if (parent::__construct())
		{
			return true;
		}

		return false;
	}

	/**
	 * set the lock license
	 *
	 * @deprecated 6.1.7 Use the Architecture.Component.LicenseLock service.
	 */
	public function setLockLicense()
	{
		CFactory::_('Architecture.Component.LicenseLock')->set();
	}

	/**
	 * set Lock License Per
	 *
	 * @param   string  $view
	 * @param   string  $target
	 *
	 * @deprecated 6.1.7 Use the Architecture.Component.LicenseLock service.
	 */
	public function setLockLicensePer(&$view, $target)
	{
		CFactory::_('Architecture.Component.LicenseLock')->setView($view);
	}

	/**
	 * Check statment license locked
	 *
	 * @param   type  $boolMethod
	 * @param   type  $thIIS
	 *
	 * @return string
	 */
	public function checkStatmentLicenseLocked($boolMethod, $thIIS = '$this')
	{
		return CFactory::_('Architecture.Component.LicenseLock')
			->checkStatement($boolMethod, $thIIS);
	}

	/**
	 * set Bool License Lock
	 *
	 * @param   type  $boolMethod
	 * @param   type  $globalbool
	 *
	 * @return string
	 */
	public function setBoolLicenseLock($boolMethod, $globalbool)
	{
		return CFactory::_('Architecture.Component.LicenseLock')
			->boolMethod($boolMethod, $globalbool);
	}

	/**
	 * set Helper License Lock
	 *
	 * @param   type  $_WHMCS
	 * @param   type  $target
	 *
	 * @return string
	 */
	public function setHelperLicenseLock($_WHMCS, $target)
	{
		return CFactory::_('Architecture.Component.LicenseLock')
			->helperMethod();
	}

	/**
	 * set Init License Lock
	 *
	 * @param   type  $_WHMCS
	 *
	 * @return string
	 */
	public function setInitLicenseLock($_WHMCS)
	{
		return CFactory::_('Architecture.Component.LicenseLock')
			->initLock($_WHMCS);
	}

	/**
	 * set WHMCS Cryption
	 *
	 * @return string
	 */
	public function setWHMCSCryption()
	{
		return CFactory::_('Architecture.Component.Whmcs')->get();
	}

	/**
	 * set Get Crypt Key
	 *
	 * @return string
	 */
	public function setGetCryptKey()
	{
		return CFactory::_('Architecture.ComHelperClass.CryptKey')->get();
	}

	/**
	 * set Version Controller
	 */
	public function setVersionController()
	{
		$versionUpdate = CFactory::_('Extension.VersionUpdate');
		$versionUpdate->setLastUpdateUrl(
			is_string($this->lastupdateURL) ? $this->lastupdateURL : null
		);
		$versionUpdate->set();
		$this->lastupdateURL = $versionUpdate->getLastUpdateUrl();
	}

	/**
	 * set Dynamic Update XML SQL
	 *
	 * @param   array  $updateXML
	 * @param   bool   $current_version
	 */
	public function setDynamicUpdateXMLSQL(&$updateXML, $current_version = false)
	{
		$versionUpdate = CFactory::_('Extension.VersionUpdate');
		$versionUpdate->setLastUpdateUrl(
			is_string($this->lastupdateURL) ? $this->lastupdateURL : null
		);
		$versionUpdate->setDynamicUpdateXmlSql($updateXML, (bool) $current_version);
		$this->lastupdateURL = $versionUpdate->getLastUpdateUrl();
	}

	/**
	 * set Update XML SQL
	 *
	 * @param   array    $update
	 * @param   array    $updateXML
	 * @param   boolean  $addDynamicSQL
	 */
	public function setUpdateXMLSQL(&$update, &$updateXML, &$addDynamicSQL)
	{
		$versionUpdate = CFactory::_('Extension.VersionUpdate');
		$versionUpdate->setLastUpdateUrl(
			is_string($this->lastupdateURL) ? $this->lastupdateURL : null
		);
		$versionUpdate->setUpdateXmlSql($update, $updateXML, $addDynamicSQL);
		$this->lastupdateURL = $versionUpdate->getLastUpdateUrl();
	}

	/**
	 * set the helper excel methods
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.ComHelperClass.ExcelMethods service.
	 */
	public function setHelperExelMethods()
	{
		return CFactory::_('Architecture.ComHelperClass.ExcelMethods')->get();
	}

	/**
	 * set the admin view site menu xml
	 *
	 * @param   string  $nameSingleCode
	 * @param   array   $view
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Menu.AdminView service.
	 */
	public function setAdminViewMenu(&$nameSingleCode, &$view)
	{
		return CFactory::_('Architecture.Menu.AdminView')
			->get($nameSingleCode, $view);
	}

	/**
	 * set the custom view menu xml
	 *
	 * @param   array  $view
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Menu.CustomView service.
	 */
	public function setCustomViewMenu(&$view)
	{
		return CFactory::_('Architecture.Menu.CustomView')->get($view);
	}

	/**
	 * setup the frontend param fields
	 *
	 * @param   array   $params
	 * @param   string  $view
	 *
	 * @return  array
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Menu.CustomView service.
	 */
	public function setupFrontendParamFields($params, $view)
	{
		return CFactory::_('Architecture.Menu.CustomView')
			->params($params, $view);
	}

	/**
	 * @param   type  $view
	 * @param   type  $type
	 */
	public function setUserPermissionCheckAccess($view, $type)
	{
		if (isset($view['access']) && $view['access'] == 1)
		{
			switch ($type)
			{
				case 1:
					$userString = '$this->user';
					break;
				default:
					$userString = '$user';
					break;
			}
			// check that the default and the redirect page is not the same
			if (CFactory::_('Compiler.Builder.Content.One')->exists('SITE_DEFAULT_VIEW')
				&& CFactory::_('Compiler.Builder.Content.One')->get('SITE_DEFAULT_VIEW') != $view['settings']->code)
			{
				$redirectMessage = Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					)
					. " redirect away to the default view if no access allowed.";
				$redirectString  = "Joomla__"."_d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_"
					. CFactory::_('Config')->component_code_name . "&view="
					. CFactory::_('Compiler.Builder.Content.One')->get('SITE_DEFAULT_VIEW') . "')";
			}
			else
			{
				$redirectMessage = Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " redirect away to the home page if no access allowed.";
				$redirectString  = 'Joomla__'.'_eecc143e_b5cf_4c33_ba4d_97da1df61422___Power::root()';
			}
			$accessCheck[] = PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " check if this user has permission to access item";
			$accessCheck[] = Indent::_(2) . "if (!" . $userString
				. "->authorise('site." . $view['settings']->code
				. ".access', 'com_" . CFactory::_('Config')->component_code_name . "'))";
			$accessCheck[] = Indent::_(2) . "{";
			$accessCheck[] = Indent::_(3)
				. "\$app = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication();";
			// set lang
			$langKeyWord = CFactory::_('Config')->lang_prefix . '_'
				. StringHelper::safe(
					'Not authorised to view ' . $view['settings']->code . '!',
					'U'
				);
			CFactory::_('Language')->set(
				'site', $langKeyWord,
				'Not authorised to view ' . $view['settings']->code . '!'
			);
			$accessCheck[] = Indent::_(3) . "\$app->enqueueMessage(Text:"
				. ":_('" . $langKeyWord . "'), 'error');";
			$accessCheck[] = $redirectMessage;
			$accessCheck[] = Indent::_(3) . "\$app->redirect(" . $redirectString
				. ");";
			$accessCheck[] = Indent::_(3) . "return false;";
			$accessCheck[] = Indent::_(2) . "}";

			// return the access check
			return implode(PHP_EOL, $accessCheck);
		}

		return '';
	}

	/**
	 * set the uikit helper methods
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.ComHelperClass.UikitMethods service.
	 */
	public function setUikitHelperMethods()
	{
		return CFactory::_('Architecture.ComHelperClass.UikitMethods')->get();
	}

	/**
	 * build code for the admin view display method
	 *
	 * @param   string  $nameListCode  The list view name
	 *
	 * @return  string The php to place in view.html.php
	 *
	 */
	public function setAdminViewDisplayMethod($nameListCode)
	{
		return CFactory::_('Architecture.AdminViews.DisplayMethod')
			->get($nameListCode);
	}

	/**
	 * build code for the custom view display method
	 *
	 * @param   array  $view  The view data
	 *
	 * @return  string The php to place in the view display method
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.CustomView.DisplayMethod service.
	 */
	public function setCustomViewDisplayMethod(&$view)
	{
		return CFactory::_('Architecture.CustomView.DisplayMethod')->get($view);
	}

	/**
	 * Set the prepare document method of a view.
	 *
	 * @param   array  $view  The view definition.
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.PrepareDocument instead.
	 */
	public function setPrepareDocument(&$view)
	{
		CFactory::_('Architecture.View.PrepareDocument')->set($view);
	}

	/**
	 * Set the module loader of a view.
	 *
	 * @param   array   $view    The view definition.
	 * @param   string  $TARGET  The upper case build target of the view.
	 *
	 * @return  string  The generated module loader.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.GetModules instead.
	 */
	public function setGetModules($view, $TARGET)
	{
		return CFactory::_('Architecture.View.GetModules')->get($view, $TARGET);
	}

	/**
	 * Set the custom php a view runs when it prepares its document.
	 *
	 * @param   array  $view  The view definition.
	 *
	 * @return  string  The generated php.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.DocumentCustomPHP instead.
	 */
	public function setDocumentCustomPHP(&$view)
	{
		return CFactory::_('Architecture.View.DocumentCustomPHP')->get($view);
	}

	/**
	 * Set the stylesheet of a view.
	 *
	 * @param   array  $view  The view definition.
	 *
	 * @return  string  The generated stylesheet.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.CustomCSS instead.
	 */
	public function setCustomCSS(&$view)
	{
		return CFactory::_('Architecture.View.CustomCSS')->get($view);
	}

	/**
	 * Set the inline stylesheet a view adds to its document.
	 *
	 * @param   array  $view  The view definition.
	 *
	 * @return  string  The generated statement.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.DocumentInlineAssets instead.
	 */
	public function setDocumentCustomCSS(&$view)
	{
		return CFactory::_('Architecture.View.DocumentInlineAssets')->css($view);
	}

	/**
	 * Set the script file of a view.
	 *
	 * @param   array   $view    The view definition.
	 * @param   string  $TARGET  The upper case build target of the view.
	 *
	 * @return  string  The generated statement.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.JavaScriptFile instead.
	 */
	public function setJavaScriptFile(&$view, $TARGET)
	{
		return CFactory::_('Architecture.View.JavaScriptFile')->get($view, $TARGET);
	}

	/**
	 * Set the inline script a view adds to its document.
	 *
	 * @param   array  $view  The view definition.
	 *
	 * @return  string  The generated statement.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.DocumentInlineAssets instead.
	 */
	public function setDocumentCustomJS(&$view)
	{
		return CFactory::_('Architecture.View.DocumentInlineAssets')->js($view);
	}

	/**
	 * Set the footable scripts a view loads.
	 *
	 * @param   array  $view  The view definition.
	 *
	 * @return  string  The generated statements.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.FootableScriptsLoader instead.
	 */
	public function setFootableScriptsLoader(&$view)
	{
		return CFactory::_('Architecture.View.FootableScriptsLoader')->get($view);
	}

	/**
	 * Set the document metadata of a view.
	 *
	 * @param   array  $view  The view definition.
	 *
	 * @return  string  The generated metadata statements.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.DocumentMetadata instead.
	 */
	public function setDocumentMetadata(&$view)
	{
		return CFactory::_('Architecture.View.DocumentMetadata')->get($view);
	}

	/**
	 * Set the google chart assets a view loads.
	 *
	 * @param   array  $view  The view definition.
	 *
	 * @return  string  The generated statements.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.GoogleChartLoader instead.
	 */
	public function setGoogleChartLoader(&$view)
	{
		return CFactory::_('Architecture.View.GoogleChartLoader')->get($view);
	}

	/**
	 * Set the libraries a view loads.
	 *
	 * @param   mixed  $view  The view definition, or the module asking for them.
	 *
	 * @return  string  The generated statements.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.LibrariesLoader instead.
	 */
	public function setLibrariesLoader($view)
	{
		return CFactory::_('Architecture.View.LibrariesLoader')->get($view);
	}

	/**
	 * Build the statements that load the uikit assets a view needs.
	 *
	 * @param   array  $view  The view being built.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.View.UikitLoader service.
	 */
	public function setUikitLoader(&$view)
	{
		return CFactory::_('Architecture.View.UikitLoader')->get($view);
	}

	public function setCustomViewExtraDisplayMethods(&$view)
	{
		if ($view['settings']->add_php_jview == 1)
		{
			return PHP_EOL . PHP_EOL . CFactory::_('Placeholder')->update_(
					$view['settings']->php_jview
				);
		}

		return '';
	}

	public function setCustomViewBody(&$view)
	{
		if (StringHelper::check($view['settings']->default))
		{
			if ($view['settings']->main_get->gettype == 2
				&& $view['settings']->main_get->pagination == 1)
			{
				// does this view have a custom limitbox position
				$has_limitbox = (strpos(
						(string) $view['settings']->default,
						(string) Placefix::_('LIMITBOX')
					) !== false);
				// does this view have a custom pages counter position
				$has_pagescounter = (strpos(
						(string) $view['settings']->default,
						(string) Placefix::_('PAGESCOUNTER')
					) !== false);
				// does this view have a custom pages links position
				$has_pageslinks = (strpos(
						(string) $view['settings']->default,
						(string) Placefix::_('PAGESLINKS')
					) !== false);
				// does this view have a custom pagination start position
				$has_pagination_start = (strpos(
						(string) $view['settings']->default,
						(string) Placefix::_('PAGINATIONSTART')
					) !== false);
				// does this view have a custom pagination end position
				$has_pagination_end = (strpos(
						(string) $view['settings']->default,
						(string) Placefix::_('PAGINATIONEND')
					) !== false);
				// if both page link and limit box is on the page, and page counter we don't need to add START and END stuff
				$has_pagination = ($has_limitbox && $has_pagescounter && $has_pageslinks);

				// add pagination start
				CFactory::_('Placeholder')->add_('PAGINATIONSTART', PHP_EOL
					. '<?php if (isset($this->items) && isset($this->pagination) && isset($this->pagination->pagesTotal) && $this->pagination->pagesTotal > 1): ?>');
				CFactory::_('Placeholder')->add_('PAGINATIONSTART',
					PHP_EOL . Indent::_(1) . '<div class="pagination">');
				CFactory::_('Placeholder')->add_('PAGINATIONSTART',
					PHP_EOL . Indent::_(2)
					. '<?php if ($this->params->def(\'show_pagination_results\', 1)) : ?>');

				// add pagination end
				CFactory::_('Placeholder')->set_('PAGINATIONEND',
						Indent::_(2) . '<?php endif; ?>');

				// only add if no custom page link is found
				if (!$has_pageslinks)
				{
					if (CFactory::_('Config')->build_target === 'custom_admin')
					{
						CFactory::_('Placeholder')->add_('PAGINATIONEND',
							PHP_EOL . Indent::_(2)
							. '<?php echo $this->pagination->getListFooter(); ?>');
					}
					else
					{
						CFactory::_('Placeholder')->add_('PAGINATIONEND',
							PHP_EOL . Indent::_(2)
							. '<?php echo $this->pagination->getPagesLinks(); ?>');
					}
				}

				CFactory::_('Placeholder')->add_('PAGINATIONEND',
					PHP_EOL . Indent::_(1) . '</div>');
				CFactory::_('Placeholder')->add_('PAGINATIONEND',
					PHP_EOL . '<?php endif; ?>');

				// add limit box
				CFactory::_('Placeholder')->set_('LIMITBOX',
					'<?php echo $this->pagination->getLimitBox(); ?>');

				// add pages counter
				CFactory::_('Placeholder')->set_('PAGESCOUNTER',
					'<?php echo $this->pagination->getPagesCounter(); ?>');

				// add pages links
				if (CFactory::_('Config')->build_target === 'custom_admin')
				{
					CFactory::_('Placeholder')->set_('PAGESLINKS',
						'<?php echo $this->pagination->getListFooter(); ?>');
				}
				else
				{
					CFactory::_('Placeholder')->set_('PAGESLINKS',
						'<?php echo $this->pagination->getPagesLinks(); ?>');
				}

				// build body
				$body = [];
				// Load the default values to the body
				$body[] = CFactory::_('Placeholder')->update_(
					$view['settings']->default
				);

				// add pagination start
				if (!$has_pagination && !$has_pagination_start)
				{
					$body[] = CFactory::_('Placeholder')->get_('PAGINATIONSTART');
				}

				if (!$has_limitbox && !$has_pagescounter)
				{
					$body[] = Indent::_(3)
						. '<p class="counter pull-right"> <?php echo $this->pagination->getPagesCounter(); ?> <?php echo $this->pagination->getLimitBox(); ?></p>';
				}
				elseif (!$has_limitbox)
				{
					$body[] = Indent::_(3)
						. '<p class="counter pull-right"> <?php echo $this->pagination->getLimitBox(); ?></p>';
				}
				elseif (!$has_pagescounter)
				{
					$body[] = Indent::_(3)
						. '<p class="counter pull-right"> <?php echo $this->pagination->getPagesCounter(); ?> </p>';
				}
				// add pagination end
				if (!$has_pagination && !$has_pagination_end)
				{
					$body[] = CFactory::_('Placeholder')->get_('PAGINATIONEND');
				}

				// lets clear the placeholders just in case
				CFactory::_('Placeholder')->remove_('LIMITBOX');
				CFactory::_('Placeholder')->remove_('PAGESCOUNTER');
				CFactory::_('Placeholder')->remove_('PAGESLINKS');
				CFactory::_('Placeholder')->remove_('PAGINATIONSTART');
				CFactory::_('Placeholder')->remove_('PAGINATIONEND');

				// insure the form is added (only if no form exist)
				if (strpos((string) $view['settings']->default, '<form') === false)
				{
					CFactory::_('Compiler.Builder.Custom.Form')->set(CFactory::_('Config')->build_target . "." . $view['settings']->code, true);
				}

				// return the body
				return implode(PHP_EOL, $body);
			}
			else
			{
				// insure the form is added (only if no form exist)
				if ('site' !== CFactory::_('Config')->build_target
					&& strpos((string) $view['settings']->default, '<form') === false)
				{
					CFactory::_('Compiler.Builder.Custom.Form')->set(CFactory::_('Config')->build_target . "." . $view['settings']->code, true);
				}

				return PHP_EOL . CFactory::_('Placeholder')->update_(
						$view['settings']->default
					);
			}
		}

		return '';
	}

	public function setCustomViewForm(&$view, &$gettype, $type)
	{
		if (CFactory::_('Compiler.Builder.Custom.Form')->exists(CFactory::_('Config')->build_target . "." . $view))
		{
			switch ($type)
			{
				case 1:
					// top
					if ('site' === CFactory::_('Config')->build_target)
					{
						if (CFactory::_('Config')->get('joomla_version', 3) >= 6)
						{
							return '<form action="<?php echo Joomla__'.'_d4c76099_4c32_408a_8701_d0a724484dfd___Power::_(\'index.php'
								. '\'); ?>" method="post" name="adminForm" id="adminForm">'
								. PHP_EOL; // yes we only need index.php
						}
						else
						{
							return '<form action="<?php echo Joomla__'.'_d4c76099_4c32_408a_8701_d0a724484dfd___Power::_(\'index.php?option=com_'
								. CFactory::_('Config')->component_code_name
								. '\'); ?>" method="post" name="adminForm" id="adminForm">'
								. PHP_EOL;
						}
					}
					else
					{
						if ($gettype == 2)
						{
							return '<form action="<?php echo Joomla__'.'_d4c76099_4c32_408a_8701_d0a724484dfd___Power::_(\'index.php?option=com_'
								. CFactory::_('Config')->component_code_name . '&view=' . $view
								. '\'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">'
								. PHP_EOL;
						}
						else
						{
							return '<form action="<?php echo Joomla__'.'_d4c76099_4c32_408a_8701_d0a724484dfd___Power::_(\'index.php?option=com_'
								. CFactory::_('Config')->component_code_name . '&view=' . $view
								. '\' . $urlId); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">'
								. PHP_EOL;
						}
					}
					break;
				case 2:
					// bottom
					$input = '';
					if ('admin' === CFactory::_('Config')->build_target
						&& isset($this->customAdminViewListId[$view]))
					{
						$input = PHP_EOL . Indent::_(1)
							. '<input type="hidden" name="id" value="<?php echo $this->app->getInput()->getInt(\'id\', 0); ?>" />';
					}

					return $input . PHP_EOL
						. '<input type="hidden" name="task" value="" />'
						. PHP_EOL . "<?php echo Html::_('form.token'); ?>"
						. PHP_EOL . '</form>';
					break;
			}
		}

		return '';
	}

	public function setCustomViewSubmitButtonScript(&$view)
	{
		if (StringHelper::check($view['settings']->default))
		{
			// add the script only if there is none set
			if (strpos(
					(string) $view['settings']->default,
					'Joomla.submitbutton = function('
				) === false)
			{
				$script   = [];
				$script[] = PHP_EOL . "<script type=\"text/javascript\">";
				$script[] = Indent::_(1)
					. "Joomla.submitbutton = function(task) {";
				$script[] = Indent::_(2) . "if (task === '"
					. $view['settings']->code . ".back') {";
				$script[] = Indent::_(3) . "parent.history.back();";
				$script[] = Indent::_(3) . "return false;";
				$script[] = Indent::_(2) . "} else {";
				$script[] = Indent::_(3)
					. "var form = document.getElementById('adminForm');";
				$script[] = Indent::_(3) . "form.task.value = task;";
				$script[] = Indent::_(3) . "form.submit();";
				$script[] = Indent::_(2) . "}";
				$script[] = Indent::_(1) . "}";
				$script[] = "</script>";

				return implode(PHP_EOL, $script);
			}
		}

		return '';
	}

	public function setCustomViewCodeBody(&$view)
	{
		if ($view['settings']->add_php_view == 1)
		{
			$view['settings']->php_view = (array) explode(
				PHP_EOL, (string) $view['settings']->php_view
			);
			if (ArrayHelper::check($view['settings']->php_view))
			{
				$_tmp = PHP_EOL . PHP_EOL . implode(
						PHP_EOL, $view['settings']->php_view
					);

				return CFactory::_('Placeholder')->update_($_tmp);
			}
		}

		return '';
	}

	public function setCustomViewTemplateBody(&$view)
	{
		if (($data_ = CFactory::_('Compiler.Builder.Template.Data')->
			get(CFactory::_('Config')->build_target . '.' . $view['settings']->code)) !== null)
		{
			$created  = CFactory::_('Model.Createdate')->get($view);
			$modified = CFactory::_('Model.Modifieddate')->get($view);
			foreach ($data_ as $template => $data)
			{
				// build the file
				$target = [
					CFactory::_('Config')->build_target => $view['settings']->code
				];
				$config = [
					Placefix::_h('CREATIONDATE') => $created,
					Placefix::_h('BUILDDATE') => $modified,
					Placefix::_h('VERSION') => $view['settings']->version
				];
				CFactory::_('Utilities.Structure')->build($target, 'template', $template, $config);
				// set the file data
				$TARGET = StringHelper::safe(
					CFactory::_('Config')->build_target, 'U'
				);
				if (!isset($data['html']) || $data['html'] === null)
				{
					echo '<pre>';
					var_dump($data);
					exit;
				}
				// SITE_TEMPLATE_BODY <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '_'
					. $template . '|' . $TARGET . '_TEMPLATE_BODY', PHP_EOL . CFactory::_('Placeholder')->update_(
						$data['html']
					));
				if (!isset($data['php_view']) || $data['php_view'] === null)
				{
					echo '<pre>';
					var_dump($data);
					exit;
				}
				// SITE_TEMPLATE_CODE_BODY <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '_'
					. $template . '|' . $TARGET . '_TEMPLATE_CODE_BODY',
					$this->setTemplateCode($data['php_view'])
				);
			}
		}
	}

	public function setTemplateCode(&$php)
	{
		if (StringHelper::check($php))
		{
			$php_view = (array) explode(PHP_EOL, (string) $php);
			if (ArrayHelper::check($php_view))
			{
				$php_view = PHP_EOL . PHP_EOL . implode(PHP_EOL, $php_view);

				return CFactory::_('Placeholder')->update_($php_view);
			}
		}

		return '';
	}

	public function setCustomViewLayouts()
	{
		if (($data_ = CFactory::_('Compiler.Builder.Layout.Data')->
			get(CFactory::_('Config')->build_target)) !== null)
		{
			foreach ($data_ as $layout => $data)
			{
				// build the file
				$target = array(CFactory::_('Config')->build_target => $layout);
				CFactory::_('Utilities.Structure')->build($target, 'layout');
				// set the file data
				$TARGET = StringHelper::safe(
					CFactory::_('Config')->build_target, 'U'
				);
				// SITE_LAYOUT_CODE <<<DYNAMIC>>>
				$php_view = (array) explode(PHP_EOL, (string) $data['php_view']);
				if (ArrayHelper::check($php_view))
				{
					$php_view = PHP_EOL . PHP_EOL . implode(PHP_EOL, $php_view);
					CFactory::_('Compiler.Builder.Content.Multi')->set($layout . '|' . $TARGET . '_LAYOUT_CODE',
						CFactory::_('Placeholder')->update_(
							$php_view
						)
					);
				}
				else
				{
					CFactory::_('Compiler.Builder.Content.Multi')->set($layout . '|' . $TARGET
						. '_LAYOUT_CODE',  '');
				}
				// SITE_LAYOUT_BODY <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($layout . '|' . $TARGET . '_LAYOUT_BODY',
					PHP_EOL . CFactory::_('Placeholder')->update_(
						$data['html']
					)
				);
				// SITE_LAYOUT_HEADER <<<DYNAMIC>>>
				CFactory::_('Compiler.Builder.Content.Multi')->set($layout . '|' . $TARGET . '_LAYOUT_HEADER',
					(($header = CFactory::_('Header')->get(
							str_replace('_', '.', (string) CFactory::_('Config')->build_target) . '.layout',
							$layout, false)) !== false) ? PHP_EOL . PHP_EOL . $header : ''
				);
			}
		}
	}

	public function getReplacementNames()
	{
		foreach (CFactory::_('Utilities.Files')->toArray() as $type => $files)
		{
			foreach ($files as $view => $file)
			{
				if (isset($file['path'])
					&& ArrayHelper::check(
						$file
					))
				{
					if (@file_exists($file['path']))
					{
						$string            = FileHelper::getContent(
							$file['path']
						);
						$buket['static'][] = $this->getInbetweenStrings(
							$string
						);
					}
				}
				elseif (ArrayHelper::check($file))
				{
					foreach ($file as $nr => $doc)
					{
						if (ArrayHelper::check($doc))
						{
							if (@file_exists($doc['path']))
							{
								$string
									= FileHelper::getContent(
									$doc['path']
								);
								$buket[$view][] = $this->getInbetweenStrings(
									$string
								);
							}
						}
					}
				}
			}
		}
		foreach ($buket as $type => $array)
		{
			foreach ($array as $replacments)
			{
				$replacments = array_unique($replacments);
				foreach ($replacments as $replacment)
				{
					if ($type !== 'static')
					{
						$echos[$replacment] = "#" . "#" . "#" . $replacment
							. "#" . "#" . "#<br />";
					}
					elseif ($type === 'static')
					{
						$echos[$replacment] = "#" . "#" . "#" . $replacment
							. "#" . "#" . "#<br />";
					}
				}
			}
		}

		foreach ($echos as $echo)
		{
			echo $echo . '<br />';
		}
	}

	public function setMethodGetItem(&$view)
	{
		$script = '';
		// get the component name
		$Component = CFactory::_('Compiler.Builder.Content.One')->get('Component');
		$component = CFactory::_('Compiler.Builder.Content.One')->get('component');
		// go from base64 to string
		if (CFactory::_('Compiler.Builder.Base.Six.Four')->exists($view))
		{
			foreach (CFactory::_('Compiler.Builder.Base.Six.Four')->get($view) as $baseString)
			{
				$script .= PHP_EOL . PHP_EOL . Indent::_(3)
					. "if (!empty(\$item->" . $baseString
					. "))"; // TODO && base64_encode(base64_decode(\$item->".$baseString.", true)) === \$item->".$baseString.")";
				$script .= PHP_EOL . Indent::_(3) . "{";
				$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " base64 Decode " . $baseString . ".";
				$script .= PHP_EOL . Indent::_(4) . "\$item->" . $baseString
					. " = base64_decode(\$item->" . $baseString . ");";
				$script .= PHP_EOL . Indent::_(3) . "}";
			}
		}
		// decryption
		foreach (CFactory::_('Config')->cryption_types as $cryptionType)
		{
			if (CFactory::_('Compiler.Builder.Model.' . ucfirst($cryptionType).  '.Field')->exists($view))
			{
				if ('expert' !== $cryptionType)
				{
					$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//"
						. Line::_(__Line__, __Class__) . " Get the " . $cryptionType
						. " encryption.";
					$script .= PHP_EOL . Indent::_(3) . "\$" . $cryptionType
						. "key = " . $Component . "Helper::getCryptKey('"
						. $cryptionType . "');";
					$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Get the encryption object.";
					$script .= PHP_EOL . Indent::_(3) . "\$" . $cryptionType
						. " = new Super_" . "__99175f6d_dba8_4086_8a65_5c4ec175e61d___Power(\$" . $cryptionType . "key);";
					foreach (CFactory::_('Compiler.Builder.Model.' . ucfirst($cryptionType).  '.Field')->get($view) as $baseString)
					{
						$script .= PHP_EOL . PHP_EOL . Indent::_(3)
							. "if (!empty(\$item->" . $baseString . ") && \$"
							. $cryptionType . "key && !is_numeric(\$item->"
							. $baseString . ") && \$item->" . $baseString
							. " === base64_encode(base64_decode(\$item->"
							. $baseString . ", true)))";
						$script .= PHP_EOL . Indent::_(3) . "{";
						$script .= PHP_EOL . Indent::_(4) . "//"
							. Line::_(__Line__, __Class__) . " " . $cryptionType
							. " decrypt data " . $baseString . ".";
						$script .= PHP_EOL . Indent::_(4) . "\$item->"
							. $baseString . " = rtrim(\$" . $cryptionType
							. "->decryptString(\$item->" . $baseString . "), "
							. '"\0"' . ");";
						$script .= PHP_EOL . Indent::_(3) . "}";
					}
				}
				else
				{
					if (CFactory::_('Compiler.Builder.Model.' . ucfirst($cryptionType).  '.Field.Initiator')->
						exists("{$view}.get"))
					{
						foreach (CFactory::_('Compiler.Builder.Model.' . ucfirst($cryptionType).  '.Field.Initiator')->
							get("{$view}.get") as $block
						)
						{
							$script .= PHP_EOL . Indent::_(3) . implode(
								PHP_EOL . Indent::_(3), $block
							);
						}
					}
					// set the expert script
					foreach (CFactory::_('Compiler.Builder.Model.' . ucfirst($cryptionType).  '.Field')->
						get($view) as $baseString => $opener_)
					{
						$_placeholder_for_field = array('[[[field]]]' => '$item->' . $baseString);
						$script .= CFactory::_('Placeholder')->update(
							PHP_EOL . Indent::_(3) . implode(
								PHP_EOL . Indent::_(3), $opener_['get']
							), $_placeholder_for_field
						);
					}
				}
			}
		}
		// go from json to array
		if (CFactory::_('Compiler.Builder.Json.Item')->exists($view))
		{
			foreach (CFactory::_('Compiler.Builder.Json.Item')->get($view) as $jsonItem)
			{
				$script .= PHP_EOL . PHP_EOL . Indent::_(3)
					. "if (!empty(\$item->" . $jsonItem . "))";
				$script .= PHP_EOL . Indent::_(3) . "{";
				$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Convert the " . $jsonItem . " field to an array.";
				$script .= PHP_EOL . Indent::_(4) . "\$" . $jsonItem
					. " = new Registry;";
				$script .= PHP_EOL . Indent::_(4) . "\$" . $jsonItem
					. "->loadString(\$item->" . $jsonItem . ");";
				$script .= PHP_EOL . Indent::_(4) . "\$item->" . $jsonItem
					. " = \$" . $jsonItem . "->toArray();";
				$script .= PHP_EOL . Indent::_(3) . "}";
			}
		}
		// go from json to string
		if (CFactory::_('Compiler.Builder.Json.String')->exists($view))
		{
			$makeArray = '';
			foreach (CFactory::_('Compiler.Builder.Json.String')->get($view) as $jsonString)
			{
				$script .= PHP_EOL . PHP_EOL . Indent::_(3)
					. "if (!empty(\$item->" . $jsonString . "))";
				$script .= PHP_EOL . Indent::_(3) . "{";
				$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " JSON Decode " . $jsonString . ".";
				if (CFactory::_('Compiler.Builder.Json.Item.Array')->inArray($jsonString, $view) ||
					strpos((string) $jsonString, 'group') !== false)
				{
					$makeArray = ',true';
				}
				$script .= PHP_EOL . Indent::_(4) . "\$item->" . $jsonString
					. " = json_decode(\$item->" . $jsonString . $makeArray
					. ");";
				$script .= PHP_EOL . Indent::_(3) . "}";
			}
		}
		// add the tag get options
		if (CFactory::_('Compiler.Builder.Tags')->exists($view))
		{
			$script .= PHP_EOL . PHP_EOL . Indent::_(3)
				. "if (!empty(\$item->id))";
			$script .= PHP_EOL . Indent::_(3) . "{";
			$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Get Tag IDs.";
			$script .= PHP_EOL . Indent::_(4) . "\$item->tags"
				. " = new TagsHelper;";
			$script .= PHP_EOL . Indent::_(4)
				. "\$item->tags->getTagIds(\$item->id, 'com_$component.$view');";
			$script .= PHP_EOL . Indent::_(3) . "}";
		}
		// add custom php to getitem method
		$script .= CFactory::_('Customcode.Dispenser')->get(
			'php_getitem', $view, PHP_EOL . PHP_EOL
		);

		return $script;
	}

	public function setCheckboxSave(&$view)
	{
		$script = '';
		if (CFactory::_('Compiler.Builder.Check.Box')->exists($view))
		{
			foreach (CFactory::_('Compiler.Builder.Check.Box')->get($view) as $checkbox)
			{
				$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
					. Line::_(__Line__, __Class__) . " Set the empty " . $checkbox
					. " item to data";
				$script .= PHP_EOL . Indent::_(2) . "if (!isset(\$data['"
					. $checkbox . "']))";
				$script .= PHP_EOL . Indent::_(2) . "{";
				$script .= PHP_EOL . Indent::_(3) . "\$data['" . $checkbox
					. "'] = '';";
				$script .= PHP_EOL . Indent::_(2) . "}";
			}
		}

		return $script;
	}

	/**
	 * build the save method of an admin edit view model
	 *
	 * @param   string  $view  The single view name
	 *
	 * @return  string The php to place in the model
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.ItemSave service.
	 */
	public function setMethodItemSave(&$view)
	{
		return CFactory::_('Architecture.Model.ItemSave')->get($view);
	}

	public function setJtableConstructor(&$view)
	{
		// reset
		$oserver = "";
		// set component name
		$component = CFactory::_('Config')->component_code_name;
		// add the tags observer
		if (CFactory::_('Compiler.Builder.Tags')->exists($view))
		{
			$oserver .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
				. Line::_(__Line__, __Class__) . " Adding Tag Options";
			$oserver .= PHP_EOL . Indent::_(2)
				. "Joomla__"."_fe63add8_0a40_4b3d_b548_f735fa6072fb___Power::createObserver(\$this, array('typeAlias' => 'com_"
				. $component . "." . $view . "'));";
		}
		// add the history/version observer
		if (CFactory::_('Compiler.Builder.History')->exists($view))
		{
			$oserver .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
				. Line::_(__Line__, __Class__) . " Adding History Options";
			$oserver .= PHP_EOL . Indent::_(2)
				. "Joomla__"."_9ac794c2_f96d_4522_8acf_b8d48c4f51c5___Power::createObserver(\$this, array('typeAlias' => 'com_"
				. $component . "." . $view . "'));";
		}

		return $oserver;
	}

	public function setJtableAliasCategory(&$view)
	{
		// only add Observers if both title, alias and category is available in view
		$code = CFactory::_('Compiler.Builder.Category.Code')->get("{$view}.code");
		if ($code !== null)
		{
			return ", '" . $code . "' => \$this->" . $code;
		}

		return '';
	}

	/**
	 * Build the content type declarations of every admin view that needs one.
	 *
	 * @param   string  $action  Whether the component is installing or updating.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Component.ContentTypes service.
	 */
	public function setComponentToContentTypes($action)
	{
		$script = CFactory::_('Architecture.Component.ContentTypes')->get((string) $action);

		// the uninstall script the helper still builds reads these off the properties
		$this->uninstallScriptBuilder = CFactory::_('Compiler.Builder.Uninstall.Script.Context')
			->allActive() + $this->uninstallScriptBuilder;
		$this->uninstallScriptContent = CFactory::_('Compiler.Builder.Uninstall.Script.Content')
			->allActive() + $this->uninstallScriptContent;

		return $script;
	}

	public function setPostInstallScript()
	{
		// reset script
		$script = $this->setComponentToContentTypes('install');

		// add the Intelligent Fix script if needed
		$script .= $this->getAssetsTableIntelligentInstall();

		if (CFactory::_('Config')->get('joomla_version', 3) == 3)
		{
			$script .= $this->setPostInstallScriptJ3();
		}
		else
		{
			$script .= $this->setPostInstallScriptJ4();
		}

		// add the custom script
		$script .= CFactory::_('Customcode.Dispenser')->get(
			'php_postflight', 'install', PHP_EOL . PHP_EOL, null, true
		);

		// add the component installation notice
		if (StringHelper::check($script))
		{
			$script .= PHP_EOL . PHP_EOL . Indent::_(3)
				. 'echo \'<div style="background-color: #fff;" class="alert alert-info"><a target="_blank" href="'
				. CFactory::_('Compiler.Builder.Content.One')->get('AUTHORWEBSITE') . '" title="'
				. CFactory::_('Compiler.Builder.Content.One')->get('Component_name') . '">';
			$script .= PHP_EOL . Indent::_(4) . '<img src="components/com_'
				. CFactory::_('Config')->component_code_name . '/assets/images/vdm-component.'
				. CFactory::_('Architecture.Component.ImageType')->get() . '"/>';
			$script .= PHP_EOL . Indent::_(4) . '</a></div>\';';

			return $script;
		}

		return PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
			. " noting to install.";
	}

	public function setPostInstallScriptJ3()
	{
		// reset script
		$script = '';

		// set the component name
		$component = CFactory::_('Config')->component_code_name;

		// add the assets table update for permissions rules
		if (CFactory::_('Compiler.Builder.Assets.Rules')->isArray('site'))
		{
			if (StringHelper::check($script))
			{
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Install the global extenstion assets permission.";
			}
			else
			{
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Install the global extension assets permission.";
				$script .= PHP_EOL . Indent::_(3)
					. "\$db = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getDbo();";
			}
			$script .= PHP_EOL . Indent::_(3)
				. "\$query = \$db->getQuery(true);";
			$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " Field to update.";
			$script .= PHP_EOL . Indent::_(3) . "\$fields = array(";
			$script .= PHP_EOL . Indent::_(4)
				. "\$db->quoteName('rules') . ' = ' . \$db->quote('{" . implode(
					',', CFactory::_('Compiler.Builder.Assets.Rules')->get('site')
				) . "}'),";
			$script .= PHP_EOL . Indent::_(3) . ");";
			$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " Condition.";
			$script .= PHP_EOL . Indent::_(3) . "\$conditions = array(";
			$script .= PHP_EOL . Indent::_(4)
				. "\$db->quoteName('name') . ' = ' . \$db->quote('com_"
				. $component . "')";
			$script .= PHP_EOL . Indent::_(3) . ");";
			$script .= PHP_EOL . Indent::_(3)
				. "\$query->update(\$db->quoteName('#__assets'))->set(\$fields)->where(\$conditions);";
			$script .= PHP_EOL . Indent::_(3) . "\$db->setQuery(\$query);";
			$script .= PHP_EOL . Indent::_(3) . "\$allDone = \$db->execute();"
				. PHP_EOL;
		}

		// add the global params for the component global settings
		if (CFactory::_('Compiler.Builder.Extensions.Params')->isArray('component'))
		{
			if (StringHelper::check($script))
			{
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Install the global extension params.";
			}
			else
			{
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Install the global extension params.";
				$script .= PHP_EOL . Indent::_(3)
					. "\$db = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getDbo();";
			}
			$script .= PHP_EOL . Indent::_(3)
				. "\$query = \$db->getQuery(true);";
			$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " Field to update.";
			$script .= PHP_EOL . Indent::_(3) . "\$fields = array(";
			$script .= PHP_EOL . Indent::_(4)
				. "\$db->quoteName('params') . ' = ' . \$db->quote('{"
				. implode(',', CFactory::_('Compiler.Builder.Extensions.Params')->get('component')) . "}'),";
			$script .= PHP_EOL . Indent::_(3) . ");";
			$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " Condition.";
			$script .= PHP_EOL . Indent::_(3) . "\$conditions = array(";
			$script .= PHP_EOL . Indent::_(4)
				. "\$db->quoteName('element') . ' = ' . \$db->quote('com_"
				. $component . "')";
			$script .= PHP_EOL . Indent::_(3) . ");";
			$script .= PHP_EOL . Indent::_(3)
				. "\$query->update(\$db->quoteName('#__extensions'))->set(\$fields)->where(\$conditions);";
			$script .= PHP_EOL . Indent::_(3) . "\$db->setQuery(\$query);";
			$script .= PHP_EOL . Indent::_(3) . "\$allDone = \$db->execute();"
				. PHP_EOL;
		}

		return $script;
	}

	public function setPostInstallScriptJ4()
	{
		// reset script
		$script = '';

		// add the assets table update for permissions rules
		if (CFactory::_('Compiler.Builder.Assets.Rules')->isArray('site'))
		{
			$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Install the global extension assets permission.";
			$script .= PHP_EOL . Indent::_(3) . "\$this->setAssetsRules(";
			$script .= PHP_EOL . Indent::_(4) . "'{" . implode(
					',', CFactory::_('Compiler.Builder.Assets.Rules')->get('site')
				) . "}'";
			$script .= PHP_EOL . Indent::_(3) . ");" . PHP_EOL;
		}

		// add the global params for the component global settings
		if (CFactory::_('Compiler.Builder.Extensions.Params')->isArray('component'))
		{
			$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Install the global extension params.";
			$script .= PHP_EOL . Indent::_(3) . "\$this->setExtensionsParams(";
			$script .= PHP_EOL . Indent::_(4) . "'{"
				. implode(',', CFactory::_('Compiler.Builder.Extensions.Params')->get('component')
				) . "}'";
			$script .= PHP_EOL . Indent::_(3) . ");" . PHP_EOL;
		}

		return $script;
	}

	public function setPostUpdateScript()
	{
		// reset script
		$script = $this->setComponentToContentTypes('update');
		// add the custom script
		$script .= CFactory::_('Customcode.Dispenser')->get(
			'php_postflight', 'update', PHP_EOL . PHP_EOL, null, true
		);
		if (CFactory::_('Component')->isArray('admin_views'))
		{
			$script .= PHP_EOL . PHP_EOL . Indent::_(3)
				. 'echo \'<div style="background-color: #fff;" class="alert alert-info"><a target="_blank" href="'
				. CFactory::_('Compiler.Builder.Content.One')->get('AUTHORWEBSITE') . '" title="'
				. CFactory::_('Compiler.Builder.Content.One')->get('Component_name') . '">';
			$script .= PHP_EOL . Indent::_(4) . '<img src="components/com_'
				. CFactory::_('Config')->component_code_name . '/assets/images/vdm-component.'
				. CFactory::_('Architecture.Component.ImageType')->get() . '"/>';
			$script .= PHP_EOL . Indent::_(4) . '</a>';
			$script .= PHP_EOL . Indent::_(4) . "<h3>Upgrade to Version "
				. CFactory::_('Compiler.Builder.Content.One')->get('ACTUALVERSION')
				. " Was Successful! Let us know if anything is not working as expected.</h3></div>';";
		}

		if (StringHelper::check($script))
		{
			return $script;
		}

		return PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
			. " noting to update.";
	}

	/**
	 * Build the uninstall method body of the script.php for the build target.
	 *
	 * @return  string  The generated uninstall script.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Component.UninstallScript service.
	 */
	public function setUninstallScript(): string
	{
		return CFactory::_('Architecture.Component.UninstallScript')->get(
			$this->uninstallScriptBuilder, $this->uninstallScriptFields
		);
	}

	/**
	 * Build the Joomla 3 uninstall method body of the script.php.
	 *
	 * @return  string  The generated uninstall script.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Component.J3.UninstallScript service.
	 */
	public function setUninstallScriptJ3(): string
	{
		return CFactory::_('Architecture.Component.J3.UninstallScript')->get(
			$this->uninstallScriptBuilder, $this->uninstallScriptFields
		);
	}

	/**
	 * Build the Joomla 4+ uninstall method body of the script.php.
	 *
	 * @return  string  The generated uninstall script.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Component.Shared.UninstallScript service.
	 */
	public function setUninstallScriptJ4(): string
	{
		return CFactory::_('Architecture.Component.Shared.UninstallScript')->get(
			$this->uninstallScriptBuilder, $this->uninstallScriptFields
		);
	}

	/**
	 * build code for the assets table script intelligent fix
	 *
	 * @return  string The php to place in script.php
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Component.AssetsTable service.
	 */
	protected function getAssetsTableIntelligentInstall(): string
	{
		return CFactory::_('Architecture.Component.AssetsTable')->install();
	}

	/**
	 * build code for the assets table script intelligent reversal
	 *
	 * @return  string The php to place in script.php
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Component.AssetsTable service.
	 */
	protected function getAssetsTableIntelligentUninstall(): string
	{
		return CFactory::_('Architecture.Component.AssetsTable')->uninstall();
	}

	/**
	 * Build the folder moving code the install script needs.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setMoveFolderScript()
	{
		if (CFactory::_('Registry')->get('set_move_folders_install_script'))
		{
			$function = 'setDynamicF0ld3rs($app, $parent)';
			if (CFactory::_('Config')->get('joomla_version', 3) != 3)
			{
				$function = 'moveFolders($adapter)';
			}
			// reset script
			$script   = [];
			$script[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " We check if we have dynamic folders to copy";
			$script[] = Indent::_(2)
				. "\$this->{$function};";

			// done
			return PHP_EOL . implode(PHP_EOL, $script);
		}

		return '';
	}

	/**
	 * Build the folder moving method the install script calls.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setMoveFolderMethod()
	{
		if (CFactory::_('Registry')->get('set_move_folders_install_script'))
		{
			// reset script
			$script   = [];
			if (CFactory::_('Config')->get('joomla_version', 3) != 3)
			{
				$script[] = Indent::_(1) . "/**";
				$script[] = Indent::_(1)
					. " * Method to move folders into place.";
				$script[] = Indent::_(1) . " *";
				$script[] = Indent::_(1) . " * @param   InstallerAdapter  \$adapter  The adapter calling this method";
				$script[] = Indent::_(1) . " *";
				$script[] = Indent::_(1) . " * @return void";
				$script[] = Indent::_(1) . " * @since 4.4.2";
				$script[] = Indent::_(1) . " */";
				$script[] = Indent::_(1)
					. "protected function moveFolders(InstallerAdapter \$adapter): void";
				$script[] = Indent::_(1) . "{";
				$script[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
					. " get the installation path";
				$script[] = Indent::_(2) . "\$installer = \$adapter->getParent();";
			}
			else
			{
				$script[] = Indent::_(1) . "/**";
				$script[] = Indent::_(1)
					. " * Method to set/copy dynamic folders into place (use with caution)";
				$script[] = Indent::_(1) . " *";
				$script[] = Indent::_(1) . " * @return void";
				$script[] = Indent::_(1) . " */";
				$script[] = Indent::_(1)
					. "protected function setDynamicF0ld3rs(\$app, \$parent)";
				$script[] = Indent::_(1) . "{";
				$script[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
					. " get the installation path";
				$script[] = Indent::_(2) . "\$installer = \$parent->getParent();";
			}

			$script[] = Indent::_(2)
				. "\$installPath = \$installer->getPath('source');";
			$script[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " get all the folders";
			$script[] = Indent::_(2)
				. "\$folders = Folder::folders(\$installPath);";
			$script[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " check if we have folders we may want to copy";
			$script[] = Indent::_(2)
				. "\$doNotCopy = ['media','admin','site']; // Joomla already deals with these";
			$script[] = Indent::_(2) . "if (count((array) \$folders) > 1)";
			$script[] = Indent::_(2) . "{";
			$script[] = Indent::_(3) . "foreach (\$folders as \$folder)";
			$script[] = Indent::_(3) . "{";
			$script[] = Indent::_(4) . "//" . Line::_(__Line__, __Class__)
				. " Only copy if not a standard folders";
			$script[] = Indent::_(4) . "if (!in_array(\$folder, \$doNotCopy))";
			$script[] = Indent::_(4) . "{";
			$script[] = Indent::_(5) . "//" . Line::_(__Line__, __Class__)
				. " set the source path";
			$script[] = Indent::_(5) . "\$src = \$installPath.'/'.\$folder;";
			$script[] = Indent::_(5) . "//" . Line::_(__Line__, __Class__)
				. " set the destination path";
			$script[] = Indent::_(5) . "\$dest = JPATH_ROOT.'/'.\$folder;";
			$script[] = Indent::_(5) . "//" . Line::_(__Line__, __Class__)
				. " now try to copy the folder";
			$script[] = Indent::_(5)
				. "if (!Folder::copy(\$src, \$dest, '', true))";
			$script[] = Indent::_(5) . "{";

			if (CFactory::_('Config')->get('joomla_version', 3) != 3)
			{
				$script[] = Indent::_(6)
				. "\$this->app->enqueueMessage('Could not copy '.\$folder.' folder into place, please make sure destination is writable!', 'error');";
			}
			else
			{
				$script[] = Indent::_(6)
					. "\$app->enqueueMessage('Could not copy '.\$folder.' folder into place, please make sure destination is writable!', 'error');";
			}

			$script[] = Indent::_(5) . "}";
			$script[] = Indent::_(4) . "}";
			$script[] = Indent::_(3) . "}";
			$script[] = Indent::_(2) . "}";
			$script[] = Indent::_(1) . "}";

			// done
			return PHP_EOL . PHP_EOL . implode(PHP_EOL, $script);
		}

		return '';
	}

	/**
	 * Build one admin view's content type declaration.
	 *
	 * @param   string  $view       The single view code name.
	 * @param   string  $component  The component code name.
	 *
	 * @return  array|false
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Component.ContentTypes service.
	 */
	public function getContentType($view, $component)
	{
		$type = CFactory::_('Architecture.Component.ContentTypes')->contentType(
			(string) $view, (string) $component
		);

		// the uninstall script the helper still builds reads these off the properties
		$this->uninstallScriptBuilder = CFactory::_('Compiler.Builder.Uninstall.Script.Context')
			->allActive() + $this->uninstallScriptBuilder;
		$this->uninstallScriptContent = CFactory::_('Compiler.Builder.Uninstall.Script.Content')
			->allActive() + $this->uninstallScriptContent;

		return $type;
	}

	/**
	 * Build the content type declaration of one view's own category.
	 *
	 * @param   string  $view       The single view code name.
	 * @param   string  $views      The list view code name.
	 * @param   string  $component  The component code name.
	 *
	 * @return  array
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Component.ContentTypes service.
	 */
	public function getCategoryContentType($view, $views, $component)
	{
		$type = CFactory::_('Architecture.Component.ContentTypes')->categoryContentType(
			(string) $view, (string) $views, (string) $component
		);

		// the uninstall script the helper still builds reads these off the properties
		$this->uninstallScriptBuilder = CFactory::_('Compiler.Builder.Uninstall.Script.Context')
			->allActive() + $this->uninstallScriptBuilder;
		$this->uninstallScriptContent = CFactory::_('Compiler.Builder.Uninstall.Script.Content')
			->allActive() + $this->uninstallScriptContent;

		return $type;
	}

	/**
	 * Build the route method one site view offers.
	 *
	 * @param   string  $nameSingleCode  The single view code name.
	 * @param   string  $nameListCode    The list view code name.
	 * @param   bool    $front           Whether this is a front item view.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Router.RouteHelper service.
	 */
	public function setRouterHelp($nameSingleCode, $nameListCode, $front = false)
	{
		return CFactory::_('Architecture.Router.RouteHelper')->get(
			(string) $nameSingleCode, (string) $nameListCode, (bool) $front
		);
	}

	/**
	 * Build one view's case in the router's parse switch.
	 *
	 * @param   string  $view       The view code name.
	 * @param   mixed   $viewArray  The view being built.
	 * @param   bool    $aliasView  Whether the view is reached by an alias.
	 * @param   bool    $idView     Whether the view is reached by an id.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Router.SiteRouter service.
	 */
	public function routerParseSwitch(&$view, $viewArray = null,
		$aliasView = true, $idView = true
	)
	{
		return CFactory::_('Architecture.Router.SiteRouter')->parseSwitch(
			$view, $viewArray, $aliasView, $idView
		);
	}

	/**
	 * Build the test that says a view is one this router builds.
	 *
	 * @param   string  $view  The view code name.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Router.SiteRouter service.
	 */
	public function routerBuildViews(&$view)
	{
		return CFactory::_('Architecture.Router.SiteRouter')->buildViews((string) $view);
	}

	/**
	 * build the batchMove method of an admin model
	 *
	 * @param   string  $nameSingleCode  The single view name
	 *
	 * @return  string The php to place in the model
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.BatchMove service.
	 */
	public function setBatchMove($nameSingleCode)
	{
		return CFactory::_('Architecture.Model.BatchMove')->get($nameSingleCode);
	}

	/**
	 * build the batchCopy method of an admin model
	 *
	 * @param   string  $nameSingleCode  The single view name
	 *
	 * @return  string The php to place in the model
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.BatchCopy service.
	 */
	public function setBatchCopy($nameSingleCode)
	{
		return CFactory::_('Architecture.Model.BatchCopy')->get($nameSingleCode);
	}

	/**
	 * build the title and alias uniqueness fix of a model
	 *
	 * @param   string  $nameSingleCode  The single view name
	 *
	 * @return  string The php to place in the model
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.AliasTitleFix service.
	 */
	public function setAliasTitleFix($nameSingleCode)
	{
		return CFactory::_('Architecture.Model.AliasTitleFix')->get($nameSingleCode);
	}

	/**
	 * Build the generated model's title generator.
	 *
	 * @param   string  $nameSingleCode  The single view name
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setGenerateNewTitle($nameSingleCode)
	{
		// if category is added to this view then do nothing
		if (CFactory::_('Compiler.Builder.Alias')->exists($nameSingleCode)
			&& (CFactory::_('Compiler.Builder.Title')->exists($nameSingleCode)
				|| CFactory::_('Compiler.Builder.Custom.Alias')->exists($nameSingleCode)))
		{
			// get component name
			$Component = CFactory::_('Compiler.Builder.Content.One')->get('Component');
			// rest the new function
			$newFunction   = [];
			$newFunction[] = PHP_EOL . PHP_EOL . Indent::_(1) . "/**";
			$newFunction[] = Indent::_(1)
				. " * Method to change the title/s & alias.";
			$newFunction[] = Indent::_(1) . " *";
			$newFunction[] = Indent::_(1)
				. " * @param   string         \$alias        The alias.";
			$newFunction[] = Indent::_(1)
				. " * @param   string/array   \$title        The title.";
			$newFunction[] = Indent::_(1) . " *";
			$newFunction[] = Indent::_(1)
				. " * @return	array/string  Contains the modified title/s and/or alias.";
			$newFunction[] = Indent::_(1) . " *";
			$newFunction[] = Indent::_(1) . " */";
			$newFunction[] = Indent::_(1)
				. "protected function _generateNewTitle(\$alias, \$title = null)";
			$newFunction[] = Indent::_(1) . "{";
			$newFunction[] = PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Alter the title/s & alias";
			$newFunction[] = Indent::_(2) . "\$table = \$this->getTable();";
			$newFunction[] = PHP_EOL . Indent::_(2)
				. "while (\$table->load(['alias' => \$alias]))";
			$newFunction[] = Indent::_(2) . "{";
			$newFunction[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " Check if this is an array of titles";
			$newFunction[] = Indent::_(3) . "if ("
				. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$title))";
			$newFunction[] = Indent::_(3) . "{";
			$newFunction[] = Indent::_(4)
				. "foreach(\$title as \$nr => &\$_title)";
			$newFunction[] = Indent::_(4) . "{";
			$newFunction[] = Indent::_(5)
				. "\$_title = StringHelper::increment(\$_title);";
			$newFunction[] = Indent::_(4) . "}";
			$newFunction[] = Indent::_(3) . "}";
			$newFunction[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " Make sure we have a title";
			$newFunction[] = Indent::_(3) . "elseif (\$title)";
			$newFunction[] = Indent::_(3) . "{";
			$newFunction[] = Indent::_(4)
				. "\$title = StringHelper::increment(\$title);";
			$newFunction[] = Indent::_(3) . "}";
			$newFunction[] = Indent::_(3)
				. "\$alias = StringHelper::increment(\$alias, 'dash');";
			$newFunction[] = Indent::_(2) . "}";
			$newFunction[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " Check if this is an array of titles";
			$newFunction[] = Indent::_(2) . "if ("
				. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$title))";
			$newFunction[] = Indent::_(2) . "{";
			$newFunction[] = Indent::_(3) . "\$title[] = \$alias;";
			$newFunction[] = Indent::_(3) . "return \$title;";
			$newFunction[] = Indent::_(2) . "}";
			$newFunction[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " Make sure we have a title";
			$newFunction[] = Indent::_(2) . "elseif (\$title)";
			$newFunction[] = Indent::_(2) . "{";
			$newFunction[] = Indent::_(3) . "return array(\$title, \$alias);";
			$newFunction[] = Indent::_(2) . "}";
			$newFunction[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " We only had an alias";
			$newFunction[] = Indent::_(2) . "return \$alias;";
			$newFunction[] = Indent::_(1) . "}";

			return implode(PHP_EOL, $newFunction);
		}
		elseif (CFactory::_('Compiler.Builder.Title')->exists($nameSingleCode))
		{
			$newFunction   = [];
			$newFunction[] = PHP_EOL . PHP_EOL . Indent::_(1) . "/**";
			$newFunction[] = Indent::_(1) . " * Method to change the title";
			$newFunction[] = Indent::_(1) . " *";
			$newFunction[] = Indent::_(1)
				. " * @param   string   \$title   The title.";
			$newFunction[] = Indent::_(1) . " *";
			$newFunction[] = Indent::_(1)
				. " * @return	array  Contains the modified title and alias.";
			$newFunction[] = Indent::_(1) . " *";
			$newFunction[] = Indent::_(1) . " */";
			$newFunction[] = Indent::_(1)
				. "protected function _generateNewTitle(\$title)";
			$newFunction[] = Indent::_(1) . "{";
			$newFunction[] = PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Alter the title";
			$newFunction[] = Indent::_(2) . "\$table = \$this->getTable();";
			$newFunction[] = PHP_EOL . Indent::_(2)
				. "while (\$table->load(['title' => \$title]))";
			$newFunction[] = Indent::_(2) . "{";
			$newFunction[] = Indent::_(3)
				. "\$title = StringHelper::increment(\$title);";
			$newFunction[] = Indent::_(2) . "}";
			$newFunction[] = PHP_EOL . Indent::_(2) . "return \$title;";
			$newFunction[] = Indent::_(1) . "}";

			return implode(PHP_EOL, $newFunction);
		}

		return '';
	}

	/**
	 * Build the generated model's alias generator.
	 *
	 * @param   string  $nameSingleCode  The single view name
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setGenerateNewAlias($nameSingleCode)
	{
		// make sure this view has an alias
		if (CFactory::_('Compiler.Builder.Alias')->exists($nameSingleCode))
		{
			// set the title stuff
			if (($customAliasBuilder = CFactory::_('Compiler.Builder.Custom.Alias')->get($nameSingleCode)) !== null)
			{
				$titles = array_values(
					$customAliasBuilder
				);
			}
			elseif (CFactory::_('Compiler.Builder.Title')->exists($nameSingleCode))
			{
				$titles = [CFactory::_('Compiler.Builder.Title')->get($nameSingleCode)];
			}
			// reset the bucket
			$titleData = [];
			// load the dynamic title builder
			if (isset($titles) && ArrayHelper::check($titles))
			{
				foreach ($titles as $title)
				{
					$titleData[] = "\$this->" . $title;
				}
			}
			else
			{
				$titleData
					= array("'-'"); // just encase some mad man does not set a title/customAlias (we fall back on the date)
			}
			// rest the new function
			$newFunction   = [];
			$newFunction[] = PHP_EOL . PHP_EOL . Indent::_(1) . "/**";
			$newFunction[] = Indent::_(1)
				. " * Generate a valid alias from title / date.";
			$newFunction[] = Indent::_(1)
				. " * Remains public to be able to check for duplicated alias before saving";
			$newFunction[] = Indent::_(1) . " *";
			$newFunction[] = Indent::_(1) . " * @return  string";
			$newFunction[] = Indent::_(1) . " */";
			$newFunction[] = Indent::_(1) . "public function generateAlias()";
			$newFunction[] = Indent::_(1) . "{";
			$newFunction[] = Indent::_(2) . "if (empty(\$this->alias))";
			$newFunction[] = Indent::_(2) . "{";
			$newFunction[] = Indent::_(3) . "\$this->alias = " . implode(
					".' '.", $titleData
				) . ';';
			$newFunction[] = Indent::_(2) . "}";
			$newFunction[] = PHP_EOL . Indent::_(2)
				. "\$this->alias = ApplicationHelper::stringURLSafe(\$this->alias);";
			$newFunction[] = PHP_EOL . Indent::_(2)
				. "if (trim(str_replace('-', '', \$this->alias)) == '')";
			$newFunction[] = Indent::_(2) . "{";
			$newFunction[] = Indent::_(3)
				. "\$this->alias = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getDate()->format('Y-m-d-H-i-s');";
			$newFunction[] = Indent::_(2) . "}";
			$newFunction[] = PHP_EOL . Indent::_(2) . "return \$this->alias;";
			$newFunction[] = Indent::_(1) . "}";

			return implode(PHP_EOL, $newFunction);
		}
		// rest the new function
		$newFunction   = [];
		$newFunction[] = PHP_EOL . PHP_EOL . Indent::_(1) . "/**";
		$newFunction[] = Indent::_(1)
			. " * This view does not actually have an alias";
		$newFunction[] = Indent::_(1) . " *";
		$newFunction[] = Indent::_(1) . " * @return  bool";
		$newFunction[] = Indent::_(1) . " */";
		$newFunction[] = Indent::_(1) . "public function generateAlias()";
		$newFunction[] = Indent::_(1) . "{";
		$newFunction[] = Indent::_(2) . "return false;";
		$newFunction[] = Indent::_(1) . "}";

		return implode(PHP_EOL, $newFunction);
	}

	/**
	 * Build the install.sql content of the component.
	 *
	 * @return  string  The generated install sql.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Component.InstallSql service.
	 */
	public function setInstall(): string
	{
		return CFactory::_('Architecture.Component.InstallSql')->get();
	}

	/**
	 * Build the uninstall.sql content of the component.
	 *
	 * @return  string  The generated uninstall sql.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Component.UninstallSql service.
	 */
	public function setUninstall(): string
	{
		return CFactory::_('Architecture.Component.UninstallSql')->get();
	}

	/**
	 * Register every language string the administrator side needs.
	 *
	 * @param   string  $componentName  The component name.
	 *
	 * @return  bool
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Language.Admin service.
	 */
	public function setLangAdmin(string $componentName): bool
	{
		return CFactory::_('Architecture.Language.Admin')->get($componentName);
	}

	/**
	 * Build the site language files of the component.
	 *
	 * @param   string  $componentName  The component code name
	 *
	 * @return  bool
	 *
	 * @since   3.2.0
	 */
	public function setLangSite(string $componentName): bool
	{
		// Trigger Event: jcb_ce_onBeforeBuildSiteLang
		CFactory::_('Event')->trigger(
			'jcb_ce_onBeforeBuildSiteLang'
		);

		// add final list of needed lang strings
		CFactory::_('Language')->set('site', CFactory::_('Config')->lang_prefix, $componentName);
		// some more defaults
		CFactory::_('Language')->set('site', 'JTOOLBAR_APPLY', "Save");
		CFactory::_('Language')->set('site', 'JTOOLBAR_SAVE_AS_COPY', "Save as Copy");
		CFactory::_('Language')->set('site', 'JTOOLBAR_SAVE', "Save & Close");
		CFactory::_('Language')->set('site', 'JTOOLBAR_SAVE_AND_NEW', "Save & New");
		CFactory::_('Language')->set('site', 'JTOOLBAR_CANCEL', "Cancel");
		CFactory::_('Language')->set('site', 'JTOOLBAR_CLOSE', "Close");
		CFactory::_('Language')->set('site', 'JTOOLBAR_HELP', "Help");
		CFactory::_('Language')->set('site', 'JGLOBAL_FIELD_ID_LABEL', "ID");
		CFactory::_('Language')->set(
			'site', 'JGLOBAL_FIELD_ID_DESC', "Record number in the database."
		);
		CFactory::_('Language')->set(
			'site', 'JGLOBAL_FIELD_MODIFIED_LABEL', "Modified Date"
		);
		CFactory::_('Language')->set(
			'site', 'COM_CONTENT_FIELD_MODIFIED_DESC',
			"The last date this item was modified."
		);
		CFactory::_('Language')->set(
			'site', 'JGLOBAL_FIELD_MODIFIED_BY_LABEL', "Modified By"
		);
		CFactory::_('Language')->set(
			'site', 'JGLOBAL_FIELD_MODIFIED_BY_DESC',
			"The user who did the last modification."
		);
		CFactory::_('Language')->set('site', CFactory::_('Config')->lang_prefix . '_NEW', "New");
		CFactory::_('Language')->set(
			'site', CFactory::_('Config')->lang_prefix . '_CREATE_NEW_S', "Create New %s"
		);
		CFactory::_('Language')->set('site', CFactory::_('Config')->lang_prefix . '_EDIT_S', "Edit %s");
		CFactory::_('Language')->set(
			'site', CFactory::_('Config')->lang_prefix . '_NO_ACCESS_GRANTED',
			"No Access Granted!"
		);
		CFactory::_('Language')->set(
			'site', CFactory::_('Config')->lang_prefix . '_NOT_FOUND_OR_ACCESS_DENIED',
			"Not found or access denied!"
		);

		// check if the both array is set
		if (CFactory::_('Language')->exist('both'))
		{
			foreach (CFactory::_('Language')->getTarget('both') as $keylang => $langval)
			{
				CFactory::_('Language')->set('site', $keylang, $langval);
			}
		}

		// check if the both site array is set
		if (CFactory::_('Language')->exist('bothsite'))
		{
			foreach (CFactory::_('Language')->getTarget('bothsite') as $keylang => $langval)
			{
				CFactory::_('Language')->set('site', $keylang, $langval);
			}
		}

		if (CFactory::_('Language')->exist('site'))
		{
			// Trigger Event: jcb_ce_onAfterBuildSiteLang
			CFactory::_('Event')->trigger(
				'jcb_ce_onAfterBuildSiteLang'
			);

			// Get the site language content
			$langContent = CFactory::_('Language')->getTarget('site');
			// sort the strings
			ksort($langContent);
			// load to global languages
			$langTag = CFactory::_('Config')->get('lang_tag', 'en-GB');
			CFactory::_('Compiler.Builder.Languages')->set(
				"components.{$langTag}.site",
				$langContent
			);
			// remove tmp array
			CFactory::_('Language')->setTarget('site', null);

			return true;
		}

		return false;
	}

	/**
	 * Build the site system language files of the component.
	 *
	 * @param   string  $componentName  The component name.
	 *
	 * @return  bool  True when a language target was built.
	 *
	 * @since   3.2.0
	 */
	public function setLangSiteSys(string $componentName): bool
	{
		// Trigger Event: jcb_ce_onBeforeBuildSiteSysLang
		CFactory::_('Event')->trigger(
			'jcb_ce_onBeforeBuildSiteSysLang'
		);

		// add final list of needed lang strings
		CFactory::_('Language')->set('sitesys', CFactory::_('Config')->lang_prefix, $componentName);
		CFactory::_('Language')->set(
			'sitesys', CFactory::_('Config')->lang_prefix . '_NO_ACCESS_GRANTED',
			"No Access Granted!"
		);
		CFactory::_('Language')->set(
			'sitesys', CFactory::_('Config')->lang_prefix . '_NOT_FOUND_OR_ACCESS_DENIED',
			"Not found or access denied!"
		);

		// check if the both site array is set
		if (CFactory::_('Language')->exist('bothsite'))
		{
			foreach (CFactory::_('Language')->getTarget('bothsite') as $keylang => $langval)
			{
				CFactory::_('Language')->set('sitesys', $keylang, $langval);
			}
		}
		if (CFactory::_('Language')->exist('sitesys'))
		{
			// Trigger Event: jcb_ce_onAfterBuildSiteSysLang
			CFactory::_('Event')->trigger(
				'jcb_ce_onAfterBuildSiteSysLang'
			);
			// get site system language content
			$langContent = CFactory::_('Language')->getTarget('sitesys');
			// sort strings
			ksort($langContent);
			// load to global languages
			$langTag = CFactory::_('Config')->get('lang_tag', 'en-GB');
			CFactory::_('Compiler.Builder.Languages')->set(
				"components.{$langTag}.sitesys",
				$langContent
			);
			// remove tmp array
			CFactory::_('Language')->setTarget('sitesys', null);

			return true;
		}

		return false;
	}

	/**
	 * Build the admin system language files of the component.
	 *
	 * @return  bool  True when a language target was built.
	 *
	 * @since   3.2.0
	 */
	public function setLangAdminSys(): bool
	{
		// Trigger Event: jcb_ce_onBeforeBuildAdminSysLang
		CFactory::_('Event')->trigger(
			'jcb_ce_onBeforeBuildAdminSysLang'
		);

		// check if the both admin array is set
		if (CFactory::_('Language')->exist('bothadmin'))
		{
			foreach (CFactory::_('Language')->getTarget('bothadmin') as $keylang => $langval)
			{
				CFactory::_('Language')->set('adminsys', $keylang, $langval);
			}
		}
		if (CFactory::_('Language')->exist('adminsys'))
		{
			// Trigger Event: jcb_ce_onAfterBuildAdminSysLang
			CFactory::_('Event')->trigger(
				'jcb_ce_onAfterBuildAdminSysLang'
			);
			// get admin system langauge content
			$langContent = CFactory::_('Language')->getTarget('adminsys');
			// sort strings
			ksort($langContent);
			// load to global languages
			$langTag = CFactory::_('Config')->get('lang_tag', 'en-GB');
			CFactory::_('Compiler.Builder.Languages')->set(
				"components.{$langTag}.adminsys",
				$langContent
			);
			// remove tmp array
			CFactory::_('Language')->setTarget('adminsys', null);

			return true;
		}

		return false;
	}

	/**
	 * set the custom admin view list links
	 *
	 * @param   array   $view
	 * @param   string  $nameListCode
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.AdminViews.ListLink service.
	 */
	public function setCustomAdminViewListLink($view, $nameListCode)
	{
		CFactory::_('Architecture.AdminViews.ListLink')
			->set($view, $nameListCode);

		// keep the legacy public state in step with the focused builders
		$this->syncCustomAdminState($nameListCode);
	}

	/**
	 * copy the custom admin builder state onto the legacy helper properties
	 *
	 * @param   string  $nameListCode
	 *
	 * @return  void
	 *
	 * @since   6.1.7
	 * @deprecated 6.1.7 Read the Compiler.Builder.Custom.Admin.* registries.
	 */
	protected function syncCustomAdminState($nameListCode)
	{
		$links = CFactory::_('Compiler.Builder.Custom.Admin.View.List.Link')
			->get($nameListCode);
		if ($links !== null)
		{
			$this->customAdminViewListLink[$nameListCode] = $links;
		}

		$this->customAdminViewListId = CFactory::_('Compiler.Builder.Custom.Admin.View.List.Id')
			->allActive();
		$this->customAdminAdded = CFactory::_('Compiler.Builder.Custom.Admin.Added')
			->allActive();
	}

	/**
	 * set the list body
	 *
	 * @param   string  $nameSingleCode
	 * @param   string  $nameListCode
	 *
	 * @return string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.AdminViews.ListBody service.
	 */
	public function setListBody($nameSingleCode, $nameListCode)
	{
		return CFactory::_('Architecture.AdminViews.ListBody')
			->get($nameSingleCode, $nameListCode);
	}

	/**
	 * Get the list field class
	 *
	 * @param   string  $name          The field code name
	 * @param   string  $nameListCode  The list view code name
	 * @param   string  $default       The default
	 *
	 * @return  string  The list field class
	 *
	 * @since   3.2.0
	 */
	protected function getListFieldClass($name, $nameListCode, $default = '')
	{
		return CFactory::_('Compiler.Builder.List.Field.Class')->get($nameListCode . '.' . $name, $default);
	}

	/**
	 * Get the custom admin view buttons
	 *
	 * @param   string  $nameListCode  The list view code name
	 * @param   string  $ref           The link referral string
	 *
	 * @return  string of the custom admin view buttons
	 *
	 * @since   3.2.0
	 */
	protected function getCustomAdminViewButtons($nameListCode, $ref = '')
	{
		return CFactory::_('Architecture.AdminViews.ListLink')
			->getButtons($nameListCode, $ref);
	}

	/**
	 * set the default views body
	 *
	 * @param   string  $nameSingleCode
	 * @param   string  $nameListCode
	 *
	 * @return string
	 *
	 * @since   3.2.0
	 */
	public function setDefaultViewsBody(string $nameSingleCode, string $nameListCode): string
	{
		return CFactory::_('Architecture.AdminViews.ViewBody')
			->getDefault($nameSingleCode, $nameListCode);
	}

	/**
	 * set the modal views body
	 *
	 * @param   string  $nameSingleCode
	 * @param   string  $nameListCode
	 *
	 * @return string
	 *
	 * @since   3.2.0
	 */
	public function setModalViewsBody(string $nameSingleCode, string $nameListCode): string
	{
		return CFactory::_('Architecture.AdminViews.ViewBody')
			->getModal($nameSingleCode, $nameListCode);
	}

	/**
	 * set the list body table head
	 *
	 * @param   string  $nameSingleCode
	 * @param   string  $nameListCode
	 *
	 * @return string
	 */
	/**
	 * set the admin list view table head
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.AdminViews.ListHead service.
	 */
	public function setListHead($nameSingleCode, $nameListCode)
	{
		$head = CFactory::_('Architecture.AdminViews.ListHead')
			->get($nameSingleCode, $nameListCode);

		// keep the legacy public column counter in step with the builder
		$columns = CFactory::_('Compiler.Builder.List.Column.Number')
			->get($nameListCode);
		if ($columns !== null)
		{
			$this->listColnrBuilder[$nameListCode] = $columns;
		}

		return $head;
	}

	/**
	 * get the admin list view column count
	 *
	 * @param   string  $nameListCode  The list view name
	 *
	 * @return  int|string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Compiler.Builder.List.Column.Number registry.
	 */
	public function setListColnr($nameListCode)
	{
		$columns = CFactory::_('Compiler.Builder.List.Column.Number')
			->get($nameListCode);

		if ($columns !== null)
		{
			return $columns;
		}

		return '';
	}

	/**
	 * set Tabs Layouts Fields Array
	 *
	 * @param   string  $nameSingleCode  The single view name
	 *
	 * @return  string   The array
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.AdminView.TabLayoutFields service.
	 */
	public function getTabLayoutFieldsArray($nameSingleCode)
	{
		return CFactory::_('Architecture.AdminView.TabLayoutFields')
			->get($nameSingleCode);
	}

	/**
	 * set Edit Body
	 *
	 * @param   array  $view  The view data
	 *
	 * @return  string   The edit body
	 *
	 * @deprecated 6.1.7 Use the Architecture.AdminView.EditBody service.
	 *
	 * @since   3.2.0
	 */
	public function setEditBody(&$view)
	{
		$body = CFactory::_('Architecture.AdminView.EditBody')->get($view);

		// keep the legacy deferred queue in step with the builder
		$queued = CFactory::_('Compiler.Builder.Second.Run.Admin')->allActive();
		if (ArrayHelper::check($queued))
		{
			$this->secondRunAdmin = $queued;
		}

		return $body;
	}





	/**
	 * Add the custom tabs of a view.
	 *
	 * @param   int     $nr           The tab number to start from.
	 * @param   string  $name_single  The single view name.
	 * @param   string  $target       The build target of the view.
	 *
	 * @return  string  The generated custom tabs.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.AdminView.CustomTabs service.
	 */
	protected function addCustomTabs($nr, $name_single, $target)
	{
		return CFactory::_('Architecture.AdminView.CustomTabs')
			->get($nr, $name_single, $target);
	}

	/**
	 * set the view fade in effect
	 *
	 * @param   array  $view
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.AdminView.FadeInEffect service.
	 */
	public function setFadeInEfect(&$view)
	{
		return CFactory::_('Architecture.AdminView.FadeInEffect')->get($view);
	}

	/**
	 * Build one layout of a view.
	 *
	 * @param   string  $nameSingleCode  The single view name.
	 * @param   string  $layoutName      The layout name.
	 * @param   string  $items           The generated layout items.
	 * @param   string  $type            The structure type of the layout file.
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Layout.View service.
	 */
	public function setLayout($nameSingleCode, $layoutName, $items, $type)
	{
		CFactory::_('Architecture.Layout.View')
			->set($nameSingleCode, $layoutName, $items, $type);
	}

	/**
	 * Build one linked view of a parent view.
	 *
	 * @param   array  $args  The linked view definition queued by the edit body.
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.LinkedView.Builder service.
	 */
	public function setLinkedView($args)
	{
		CFactory::_('Architecture.LinkedView.Builder')->set($args);
	}

	/**
	 * @param   bool  $init
	 *
	 * @return string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.AdminView.FootableScripts service.
	 */
	public function setFootableScripts($init = true)
	{
		return CFactory::_('Architecture.AdminView.FootableScripts')
			->get((bool) $init);
	}

	/**
	 * set the list body of the linked admin view
	 *
	 * @param   string  $nameSingleCode
	 * @param   string  $nameListCode
	 * @param   string  $refview
	 *
	 * @return string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.LinkedView.ListBody service.
	 */
	public function setListBodyLinked($nameSingleCode, $nameListCode, $refview)
	{
		return CFactory::_('Architecture.LinkedView.ListBody')->get(
			$nameSingleCode, $nameListCode, $refview
		);
	}

	/**
	 * set the list body table head linked admin view
	 *
	 * @param   string  $nameSingleCode
	 * @param   string  $nameListCode
	 * @param   bool    $addNewButon
	 * @param   string  $refview
	 *
	 * @return string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.LinkedView.ListHead service.
	 */
	public function setListHeadLinked($nameSingleCode, $nameListCode,
		$addNewButon, $refview
	)
	{
		return CFactory::_('Architecture.LinkedView.ListHead')->get(
			$nameSingleCode, $nameListCode, $addNewButon, $refview
		);
	}

	/**
	 * @param $nameSingleCode
	 * @param $nameListCode
	 * @param $functionName
	 * @param $key
	 * @param $_key
	 * @param $parentKey
	 * @param $parent_key
	 * @param $globalKey
	 *
	 * @return string
	 */
	/**
	 * Add the linked view getter to a model.
	 *
	 * @param   string  $nameSingleCode  The single view code name.
	 * @param   string  $nameListCode    The list view code name.
	 * @param   string  $functionName    The generated method name suffix.
	 * @param   string  $key             The key of the linked view.
	 * @param   string  $_key            The plain key column.
	 * @param   string  $parentKey       The key of the parent view.
	 * @param   string  $parent_key      The plain parent key column.
	 * @param   mixed   $globalKey       The property the parent exposes the key on.
	 *
	 * @return  string  The generated getter.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.LinkedView.ListQuery service.
	 */
	public function setListQueryLinked($nameSingleCode, $nameListCode,
		$functionName, $key, $_key, $parentKey, $parent_key, $globalKey)
	{
		return CFactory::_('Architecture.LinkedView.ListQuery')->get(
			$nameSingleCode, $nameListCode, $functionName, $key, $_key,
			$parentKey, $parent_key, $globalKey
		);
	}

	/**
	 * @param $nameListCode
	 *
	 * @return array|string
	 *
	 * @since   3.2.0
	 */
	public function setCustomAdminDynamicButtonController($nameListCode)
	{
		$method = '';
		if (CFactory::_('Compiler.Builder.Dynamic.Buttons')->isArray($nameListCode))
		{
			$method = [];
			foreach (CFactory::_('Compiler.Builder.Dynamic.Buttons')->get($nameListCode) as $custom_button)
			{
				// add the custom redirect method
				$method[] = PHP_EOL . PHP_EOL . Indent::_(1)
					. "public function redirectTo"
					. StringHelper::safe(
						$custom_button['link'], 'F'
					) . "()";
				$method[] = Indent::_(1) . "{";
				$method[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
					. " Check for request forgeries";
				$method[] = Indent::_(2)
					. "Joomla__"."_5ba38513_5c4f_4b0d_935e_49e986a6bce8___Power::checkToken() or die(Text:"
					. ":_('JINVALID_TOKEN'));";
				$method[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
					. " check if export is allowed for this user.";
				if (CFactory::_('Config')->get('joomla_version', 3) == 3)
				{
					$method[] = Indent::_(2) . "\$user = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getUser();";
				}
				else
				{
					$method[] = Indent::_(2) . "\$user = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->getIdentity();";
				}
				$method[] = Indent::_(2) . "if (\$user->authorise('"
					. $custom_button['link'] . ".access', 'com_"
					. CFactory::_('Config')->component_code_name . "'))";
				$method[] = Indent::_(2) . "{";
				$method[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
					. " Get the input";
				$method[] = Indent::_(3)
					. "\$input = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->input;";
				$method[] = Indent::_(3)
					. "\$pks = \$input->post->get('cid', array(), 'array');";
				$method[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
					. " Sanitize the input";
				$method[] = Indent::_(3)
					. "\$pks = ArrayHelper::toInteger(\$pks);";
				$method[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
					. " convert to string";
				$method[] = Indent::_(3) . "\$ids = implode('_', \$pks);";
				$method[] = Indent::_(3)
					. "\$this->setRedirect(Joomla__"."_d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_"
					. CFactory::_('Config')->component_code_name . "&view="
					. $custom_button['link'] . "&cid='.\$ids, false));";
				$method[] = Indent::_(3) . "return;";
				$method[] = Indent::_(2) . "}";
				$method[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
					. " Redirect to the list screen with error.";
				$method[] = Indent::_(2) . "\$message = Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('"
					. CFactory::_('Config')->lang_prefix . "_ACCESS_TO_" . $custom_button['NAME']
					. "_FAILED');";
				$method[] = Indent::_(2)
					. "\$this->setRedirect(Joomla__"."_d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_"
					. CFactory::_('Config')->component_code_name . "&view=" . $nameListCode
					. "', false), \$message, 'error');";
				$method[] = Indent::_(2) . "return;";
				$method[] = Indent::_(1) . "}";
				// add to lang array
				$lankey = CFactory::_('Config')->lang_prefix . "_ACCESS_TO_"
					. $custom_button['NAME'] . "_FAILED";
				CFactory::_('Language')->set(
					CFactory::_('Config')->lang_target, $lankey,
					'Access to ' . $custom_button['link'] . ' was denied.'
				);
			}

			return implode(PHP_EOL, $method);
		}

		return $method;
	}

	/**
	 * A function that builds get Items Method for model
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 * @param   array   $config          The config details to adapt the method being build
	 *
	 * @return string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.ItemsMethod service.
	 */
	public function setGetItemsModelMethod(&$nameSingleCode, &$nameListCode,
		$config = array('functionName' => 'getExportData',
			'docDesc'      => 'Method to get list export data.',
			'type'         => 'export')
	)
	{
		// Infusion still sets these flags directly on this helper, so they are
		// carried over to the registry the service reads.
		foreach ($this->eximportView as $view => $active)
		{
			CFactory::_('Compiler.Builder.Eximport.View')->set($view, $active);
		}

		return CFactory::_('Architecture.Model.ItemsMethod')
			->get($nameSingleCode, $nameListCode, $config);
	}

	/**
	 * Build the exportData and importData methods of an admin list controller.
	 *
	 * @param   string  $nameSingleCode  The single view code name.
	 * @param   string  $nameListCode    The list view code name.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Controller.EximportMethod service.
	 */
	public function setControllerEximportMethod($nameSingleCode,
		$nameListCode)
	{
		// Infusion still sets these flags directly on this helper, so they are
		// carried over to the registries the service reads.
		foreach ($this->eximportView as $view => $active)
		{
			CFactory::_('Compiler.Builder.Eximport.View')->set($view, $active);
		}

		foreach ($this->importCustomScripts as $view => $active)
		{
			CFactory::_('Compiler.Builder.Import.Custom.Scripts')->set($view, $active);
		}

		return CFactory::_('Architecture.Controller.EximportMethod')
			->get($nameSingleCode, $nameListCode);
	}

	/**
	 * Build the export button of a list view that allows export.
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setExportButton($nameSingleCode, $nameListCode)
	{
		$button = '';
		if (isset($this->eximportView[$nameListCode])
			&& $this->eximportView[$nameListCode]
			&& CFactory::_('Config')->get('joomla_version', 3) == 3) // needs fixing for Joomla 4 and above
		{
			// main lang prefix
			$langExport = CFactory::_('Config')->lang_prefix . '_'
				. StringHelper::safe('Export Data', 'U');
			// add to lang array
			CFactory::_('Language')->set(CFactory::_('Config')->lang_target, $langExport, 'Export Data');
			$button   = [];
			$button[] = PHP_EOL . PHP_EOL . Indent::_(3)
				. "if (\$this->canDo->get('core.export') && \$this->canDo->get('"
				. $nameSingleCode . ".export'))";
			$button[] = Indent::_(3) . "{";
			$button[] = Indent::_(4) . "Joomla__"."_0c1a176a_304f_433a_8233_37d01ff87815___Power::custom('"
				. $nameListCode . ".exportData', 'download', '', '"
				. $langExport . "', true);";
			$button[] = Indent::_(3) . "}";

			return implode(PHP_EOL, $button);
		}

		return $button;
	}

	/**
	 * Build the import button of a list view that allows import.
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setImportButton($nameSingleCode, $nameListCode)
	{
		$button = '';
		if (isset($this->eximportView[$nameListCode])
			&& $this->eximportView[$nameListCode]
			&& CFactory::_('Config')->get('joomla_version', 3) == 3) // needs fixing for Joomla 4 and above
		{
			// main lang prefix
			$langImport = CFactory::_('Config')->lang_prefix . '_'
				. StringHelper::safe('Import Data', 'U');
			// add to lang array
			CFactory::_('Language')->set(CFactory::_('Config')->lang_target, $langImport, 'Import Data');
			$button   = [];
			$button[] = PHP_EOL . PHP_EOL . Indent::_(2)
				. "if (\$this->canDo->get('core.import') && \$this->canDo->get('"
				. $nameSingleCode . ".import'))";
			$button[] = Indent::_(2) . "{";
			$button[] = Indent::_(3) . "Joomla__"."_0c1a176a_304f_433a_8233_37d01ff87815___Power::custom('"
				. $nameListCode . ".importData', 'upload', '', '"
				. $langImport
				. "', false);";
			$button[] = Indent::_(2) . "}";

			return implode(PHP_EOL, $button);
		}

		return $button;
	}

	/**
	 * Build the custom import scripts of a list view that allows import.
	 *
	 * @param   string  $nameListCode  The list view name
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setImportCustomScripts($nameListCode)
	{
		// setup Ajax files
		$target = array('admin' => 'import_' . $nameListCode);
		CFactory::_('Utilities.Structure')->build($target, 'customimport');
		// load the custom script to the files
		// IMPORT_EXT_METHOD <<<DYNAMIC>>>
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|IMPORT_EXT_METHOD', CFactory::_('Customcode.Dispenser')->get(
			'php_import_ext', 'import_' . $nameListCode, PHP_EOL, null,
			true
		));
		// IMPORT_DISPLAY_METHOD_CUSTOM <<<DYNAMIC>>>
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|IMPORT_DISPLAY_METHOD_CUSTOM', CFactory::_('Customcode.Dispenser')->get(
			'php_import_display', 'import_' . $nameListCode, PHP_EOL,
			null,
			true
		));
		// IMPORT_SETDATA_METHOD <<<DYNAMIC>>>
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|IMPORT_SETDATA_METHOD', CFactory::_('Customcode.Dispenser')->get(
			'php_import_setdata', 'import_' . $nameListCode, PHP_EOL,
			null,
			true
		));
		// IMPORT_METHOD_CUSTOM <<<DYNAMIC>>>
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|IMPORT_METHOD_CUSTOM', CFactory::_('Customcode.Dispenser')->get(
			'php_import', 'import_' . $nameListCode, PHP_EOL, null,
			true
		));
		// IMPORT_SAVE_METHOD <<<DYNAMIC>>>
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|IMPORT_SAVE_METHOD', CFactory::_('Customcode.Dispenser')->get(
			'php_import_save', 'import_' . $nameListCode, PHP_EOL,
			null,
			true
		));
		// IMPORT_DEFAULT_VIEW_CUSTOM <<<DYNAMIC>>>
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|IMPORT_DEFAULT_VIEW_CUSTOM', CFactory::_('Customcode.Dispenser')->get(
			'html_import_view', 'import_' . $nameListCode, PHP_EOL,
			null,
			true
		));

		// insure we have the view placeholders setup
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|VIEW', 'IMPORT_' . CFactory::_('Placeholder')->get_h('VIEWS'));
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|View', 'Import_' . CFactory::_('Placeholder')->get_h('views'));
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|view', 'import_' . CFactory::_('Placeholder')->get_h('views'));
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|VIEWS', 'IMPORT_' . CFactory::_('Placeholder')->get_h('VIEWS'));
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|Views', 'Import_' . CFactory::_('Placeholder')->get_h('views'));
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|views', 'import_' . CFactory::_('Placeholder')->get_h('views'));

		// IMPORT_CUSTOM_CONTROLLER_HEADER <<<DYNAMIC>>> add the header details for the controller
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|IMPORT_CUSTOM_CONTROLLER_HEADER', CFactory::_('Header')->get(
			'import.custom.controller',
			$nameListCode
		));

		// IMPORT_CUSTOM_MODEL_HEADER <<<DYNAMIC>>> add the header details for the model
		CFactory::_('Compiler.Builder.Content.Multi')->set('import_' . $nameListCode . '|IMPORT_CUSTOM_MODEL_HEADER', CFactory::_('Header')->get(
			'import.custom.model',
			$nameListCode
		));
	}

	/**
	 * build the getListQuery method of an admin list view model
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string The php to place in the model
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.ListQuery service.
	 */
	public function setListQuery(&$nameSingleCode, &$nameListCode)
	{
		return CFactory::_('Architecture.Model.ListQuery')
			->get($nameSingleCode, $nameListCode);
	}

	/**
	 * build search query
	 *
	 * @param   string  $nameListCode  The list view name
	 *
	 * @return  string The php to place in model
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.SearchQuery service.
	 */
	public function setSearchQuery($nameListCode)
	{
		return CFactory::_('Architecture.Model.SearchQuery')->get($nameListCode);
	}

	/**
	 * Add the custom field selects and joins to a list query.
	 *
	 * @param   string  $nameListCode    The list view code name.
	 * @param   string  $nameSingleCode  The single view code name.
	 * @param   string  $tab             Extra indentation of the generated lines.
	 * @param   bool    $just_text       Select the display text without its id alias.
	 *
	 * @return  string  The generated query lines.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.CustomQuery service.
	 */
	public function setCustomQuery($nameListCode, $nameSingleCode,
		$tab = '',
		$just_text = false
	)
	{
		return CFactory::_('Architecture.Model.CustomQuery')->get(
			$nameListCode, $nameSingleCode, (string) $tab, (bool) $just_text
		);
	}

	/**
	 * build model filter per/field in the list view
	 *
	 * @param   string  $nameListCode  The list view name
	 *
	 * @return  string The php to place in model to filter
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.FilterQuery service.
	 */
	public function setFilterQuery($nameListCode)
	{
		return CFactory::_('Architecture.Model.FilterQuery')->get($nameListCode);
	}

	/**
	 * build single filter query
	 *
	 * @param   array   $filter  The field/filter
	 * @param   string  $Helper  The helper name of the component being build
	 * @param   string  $a       The db table target name (a)
	 *
	 * @return  string The php to place in model to filter this field
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.FilterQuery service.
	 */
	protected function setSingleFilterQuery($filter, $Helper, $a = "a")
	{
		return CFactory::_('Architecture.Model.FilterQuery')
			->getSingleFilterQuery($filter, $Helper, $a);
	}

	/**
	 * build multi filter query
	 *
	 * @param   array   $filter  The field/filter
	 * @param   string  $Helper  The helper name of the component being build
	 * @param   string  $a       The db table target name (a)
	 *
	 * @return  string The php to place in model to filter this field
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.FilterQuery service.
	 */
	protected function setMultiFilterQuery($filter, $Helper, $a = "a")
	{
		return CFactory::_('Architecture.Model.FilterQuery')
			->getMultiFilterQuery($filter, $Helper, $a);
	}

	/**
	 * Build the javascript this admin view carries.
	 *
	 * @param   array  $viewArray  The admin view, as the component data carries it.
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.AdminView.ViewScript service.
	 */
	public function buildTheViewScript($viewArray)
	{
		CFactory::_('Architecture.AdminView.ViewScript')->get((array) $viewArray);

		// the methods still on this helper read the fixes off this property
		$this->validationFixBuilder = CFactory::_('Compiler.Builder.Validation.Fix')
			->allActive();
	}

	/**
	 * Build the statements that read every watched value and call one function.
	 *
	 * @param   string  $function   The name of the function to call.
	 * @param   array   $matchKeys  The keys of the values the function takes.
	 * @param   array   $getValue   The read statement of every key.
	 *
	 * @return  array
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.AdminView.ViewScript service.
	 */
	public function buildFunctionCall($function, $matchKeys, $getValue)
	{
		return CFactory::_('Architecture.AdminView.ViewScript')->functionCall(
			(string) $function, (array) $matchKeys, (array) $getValue
		);
	}

	/**
	 * Find the conditions of this view that steer the same target fields.
	 *
	 * @param   array   $relations  Every condition the view declares.
	 * @param   array   $condition  The condition being chained.
	 * @param   string  $view       The single view code name.
	 *
	 * @return  array
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Field.TargetRelationScript service.
	 */
	public function getTargetRelationScript($relations, $condition, $view)
	{
		return CFactory::_('Architecture.Field.TargetRelationScript')->get(
			(array) $relations, (array) $condition, (string) $view
		);
	}

	/**
	 * Test whether this target may still be claimed by this pair of matches.
	 *
	 * @param   string  $targetName          The name of the target field.
	 * @param   mixed   $relationMatchName   The name the chained condition matches on.
	 * @param   mixed   $conditionMatchName  The name the condition being chained matches on.
	 * @param   string  $view                The single view code name.
	 *
	 * @return  bool
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Field.TargetRelationScript service.
	 */
	public function checkRelationControl($targetName, $relationMatchName,
		$conditionMatchName, $view
	)
	{
		return CFactory::_('Architecture.Field.TargetRelationScript')->checkControl(
			(string) $targetName, $relationMatchName, $conditionMatchName,
			(string) $view
		);
	}

	/**
	 * Build the show, hide and required statements for every target field.
	 *
	 * @param   bool    $toggleSwitch    Whether the required attribute is toggled rather than set once.
	 * @param   mixed   $targets         The target fields.
	 * @param   string  $targetBehavior  The jQuery call that reveals a target.
	 * @param   string  $targetDefault   The jQuery call that returns a target to its default.
	 * @param   string  $uniqueVar       The unique key of the condition being built.
	 * @param   string  $nameSingleCode  The single view code name.
	 *
	 * @return  array
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Field.TargetControlsScript service.
	 */
	public function setTargetControlsScript($toggleSwitch, $targets,
		$targetBehavior, $targetDefault, $uniqueVar, $nameSingleCode)
	{
		$bucket = CFactory::_('Architecture.Field.TargetControlsScript')->get(
			(bool) $toggleSwitch, $targets, (string) $targetBehavior,
			(string) $targetDefault, (string) $uniqueVar,
			(string) $nameSingleCode
		);

		// the methods still on this helper read the fixes off this property
		$this->validationFixBuilder = CFactory::_('Compiler.Builder.Validation.Fix')
			->allActive();

		return $bucket;
	}

	/**
	 * Build the javascript test one form condition runs.
	 *
	 * @param   string  $value     The javascript variable holding the watched value.
	 * @param   mixed   $behavior  The match behaviour the condition declares.
	 * @param   mixed   $type      The type of the field being watched.
	 * @param   mixed   $options   The options the field offers.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Field.IfValueScript service.
	 */
	public function ifValueScript($value, $behavior, $type, $options)
	{
		return CFactory::_('Architecture.Field.IfValueScript')->get(
			(string) $value, $behavior, $type, $options
		);
	}

	/**
	 * Read the options a watched field declares.
	 *
	 * @param   mixed  $type     The type of the field being watched.
	 * @param   mixed  $options  The options the field declares.
	 *
	 * @return  array
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Field.OptionsScript service.
	 */
	public function getOptionsScript($type, $options)
	{
		return CFactory::_('Architecture.Field.OptionsScript')->get(
			$type, $options
		);
	}

	/**
	 * Build the javascript that reads the watched field's value.
	 *
	 * @param   mixed   $type     The type of the field being watched.
	 * @param   string  $name     The name of the field being watched.
	 * @param   mixed   $extends  The type the field extends, when it is a custom field.
	 * @param   string  $unique   The unique key of the condition being built.
	 *
	 * @return  array
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Field.ValueScript service.
	 */
	public function getValueScript($type, $name, $extends, $unique)
	{
		return CFactory::_('Architecture.Field.ValueScript')->get(
			$type, (string) $name, $extends, (string) $unique
		);
	}

	/**
	 * Build the javascript that clears the watched field's value.
	 *
	 * @param   string  $type    The type of the field being watched
	 * @param   string  $name    The name of the field being watched
	 * @param   string  $unique  The unique key of the condition being built
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function clearValueScript($type, $name, $unique)
	{
		$clear   = '';
		$isArray = false;
		$keyName = $name . '_' . $unique;
		if ($type === 'text' || $type === 'password' || $type === 'textarea')
		{
			$clear = "jQuery('#jform_" . $name . "').value = '';";
		}
		elseif ($type === 'radio')
		{
			$clear = "jQuery('#jform_" . $name . "').checked = false;";
		}
		elseif ($type === 'checkboxes' || $type === 'checkbox'
			|| $type === 'checkbox')
		{
			$clear = "jQuery('#jform_" . $name . "').selectedIndex = -1;";
		}

		return $clear;
	}

	/**
	 * Read back one of the scripts an admin view was given.
	 *
	 * @param   string  $view  The view code name.
	 * @param   string  $type  Which script: fileScript, footerScript or list_fileScript.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.AdminView.ViewScript service.
	 */
	public function setViewScript(&$view, $type)
	{
		return CFactory::_('Architecture.AdminView.ViewScript')->script(
			(string) $view, (string) $type
		);
	}

	/**
	 * Build the form validation override a view with switched fields needs.
	 *
	 * @param   string  $view       The single view name
	 * @param   mixed   $Component  The component being built
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setValidationFix($view, $Component)
	{
		$fix = '';
		if (isset($this->validationFixBuilder[$view])
			&& ArrayHelper::check(
				$this->validationFixBuilder[$view]
			))
		{
			$fix .= PHP_EOL . PHP_EOL . Indent::_(1) . "/**";
			$fix .= PHP_EOL . Indent::_(1)
				. " * Method to validate the form data.";
			$fix .= PHP_EOL . Indent::_(1) . " *";
			$fix .= PHP_EOL . Indent::_(1)
				. " * @param   Form   \$form   The form to validate against.";
			$fix .= PHP_EOL . Indent::_(1)
				. " * @param   array   \$data   The data to validate.";
			$fix .= PHP_EOL . Indent::_(1)
				. " * @param   string  \$group  The name of the field group to validate.";
			$fix .= PHP_EOL . Indent::_(1) . " *";
			$fix .= PHP_EOL . Indent::_(1)
				. " * @return  mixed  Array of filtered data if valid, false otherwise.";
			$fix .= PHP_EOL . Indent::_(1) . " *";
			$fix .= PHP_EOL . Indent::_(1) . " * @see     JFormRule";
			$fix .= PHP_EOL . Indent::_(1) . " * @see     JFilterInput";
			$fix .= PHP_EOL . Indent::_(1) . " * @since   12.2";
			$fix .= PHP_EOL . Indent::_(1) . " */";
			$fix .= PHP_EOL . Indent::_(1)
				. "public function validate(\$form, \$data, \$group = null)";
			$fix .= PHP_EOL . Indent::_(1) . "{";
			$fix .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " check if the not_required field is set";
			$fix .= PHP_EOL . Indent::_(2)
				. "if (isset(\$data['not_required']) && "
				. "Super_" . "__1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check(\$data['not_required']))";
			$fix .= PHP_EOL . Indent::_(2) . "{";
			$fix .= PHP_EOL . Indent::_(3)
				. "\$requiredFields = (array) explode(',',(string) \$data['not_required']);";
			$fix .= PHP_EOL . Indent::_(3)
				. "\$requiredFields = array_unique(\$requiredFields);";
			$fix .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " now change the required field attributes value";
			$fix .= PHP_EOL . Indent::_(3)
				. "foreach (\$requiredFields as \$requiredField)";
			$fix .= PHP_EOL . Indent::_(3) . "{";
			$fix .= PHP_EOL . Indent::_(4) . "//" . Line::_(__Line__, __Class__)
				. " make sure there is a string value";
			$fix .= PHP_EOL . Indent::_(4) . "if ("
				. "Super_" . "__1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check(\$requiredField))";
			$fix .= PHP_EOL . Indent::_(4) . "{";
			$fix .= PHP_EOL . Indent::_(5) . "//" . Line::_(__Line__, __Class__)
				. " change to false";
			$fix .= PHP_EOL . Indent::_(5)
				. "\$form->setFieldAttribute(\$requiredField, 'required', 'false');";
			$fix .= PHP_EOL . Indent::_(5) . "//" . Line::_(__Line__, __Class__)
				. " also clear the data set";
			$fix .= PHP_EOL . Indent::_(5) . "unset(\$data[\$requiredField]);";
			$fix .= PHP_EOL . Indent::_(4) . "}";
			$fix .= PHP_EOL . Indent::_(3) . "}";
			$fix .= PHP_EOL . Indent::_(2) . "}";
			$fix .= PHP_EOL . Indent::_(2)
				. "return parent::validate(\$form, \$data, \$group);";
			$fix .= PHP_EOL . Indent::_(1) . "}";
		}

		return $fix;
	}

	/**
	 * Build the ajax token declaration a view with ajax makes.
	 *
	 * @param   string  $view  The view name
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.View.AjaxToken instead.
	 */
	public function setAjaxToke(&$view)
	{
		return CFactory::_('Architecture.View.AjaxToken')->get($view);
	}

	/**
	 * Build the task registration of the ajax controller of one target.
	 *
	 * @param   string  $target  The build target the tasks belong to.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setRegisterAjaxTask($target)
	{
		$tasks = '';
		if (isset(CFactory::_('Customcode.Dispenser')->hub[$target]['ajax_controller'])
			&& ArrayHelper::check(
				CFactory::_('Customcode.Dispenser')->hub[$target]['ajax_controller']
			))
		{
			$taskArray = [];
			foreach (
				CFactory::_('Customcode.Dispenser')->hub[$target]['ajax_controller'] as $view
			)
			{
				foreach ($view as $task)
				{
					$taskArray[$task['task_name']] = $task['task_name'];
				}
			}
			if (ArrayHelper::check($taskArray))
			{
				foreach ($taskArray as $name)
				{
					$tasks .= PHP_EOL . Indent::_(2) . "\$this->registerTask('"
						. $name . "', 'ajax');";
				}
			}
		}

		return $tasks;
	}

	/**
	 * Build the ajax controller cases one build target declares.
	 *
	 * @param   string  $target  The build target, site or administrator
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.Controller.AjaxCases instead.
	 */
	public function setAjaxInputReturn($target)
	{
		return CFactory::_('Architecture.Controller.AjaxCases')->get($target);
	}

	/**
	 * Build the ajax model methods one build target declares.
	 *
	 * @param   string  $target  The build target, site or administrator
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setAjaxModelMethods($target)
	{
		$methods = '';
		if (isset(CFactory::_('Customcode.Dispenser')->hub[$target]['ajax_model'])
			&& ArrayHelper::check(
				CFactory::_('Customcode.Dispenser')->hub[$target]['ajax_model']
			))
		{
			foreach (
				CFactory::_('Customcode.Dispenser')->hub[$target]['ajax_model'] as $view =>
				$method
			)
			{
				$methods .= PHP_EOL . PHP_EOL . Indent::_(1) . "//"
					. Line::_(__Line__, __Class__) . " Used in " . $view . PHP_EOL;
				$methods .= CFactory::_('Placeholder')->update_(
					$method
				);
			}
		}

		return $methods;
	}

	/**
	 * Build the jQuery framework load the generated view makes.
	 *
	 * @param   array  $view  The view being built
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setJquery(&$view)
	{
		$addJQuery = '';
		if (true) // TODO we just add it everywhere for now.
		{
			$addJQuery .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " Load jQuery";
			$addJQuery .= PHP_EOL . Indent::_(2) . "Html::_('jquery.framework');";
		}

		return $addJQuery;
	}

	/**
	 * build filter functions
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string The php to place in view.html.php
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.AdminViews.FilterFieldHelper service.
	 */
	public function setFilterFieldHelper(&$nameSingleCode, &$nameListCode)
	{
		return CFactory::_('Architecture.AdminViews.FilterFieldHelper')
			->get($nameSingleCode, $nameListCode);
	}

	/**
	 * Build the generated table's unique field method.
	 *
	 * @param   array  $view  The view being built
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setUniqueFields(&$view)
	{
		$fields   = [];
		$fields[] = PHP_EOL . PHP_EOL . Indent::_(1) . "/**";
		$fields[] = Indent::_(1)
			. " * Method to get the unique fields of this table.";
		$fields[] = Indent::_(1) . " *";
		$fields[] = Indent::_(1)
			. " * @return  mixed  An array of field names, boolean false if none is set.";
		$fields[] = Indent::_(1) . " *";
		$fields[] = Indent::_(1) . " * @since   3.0";
		$fields[] = Indent::_(1) . " */";
		$fields[] = Indent::_(1) . "protected function getUniqueFields()";
		$fields[] = Indent::_(1) . "{";
		if (CFactory::_('Compiler.Builder.Database.Unique.Keys')->exists($view))
		{
			// if guid should also be added
			if (CFactory::_('Compiler.Builder.Database.Unique.Guid')->exists($view))
			{
				$fields[] = Indent::_(2) . "return array('" . implode(
						"','", CFactory::_('Compiler.Builder.Database.Unique.Keys')->get($view)
					) . "', 'guid');";
			}
			else
			{
				$fields[] = Indent::_(2) . "return array('" . implode(
						"','", CFactory::_('Compiler.Builder.Database.Unique.Keys')->get($view)
					) . "');";
			}
		}
		// if only GUID is found
		elseif (CFactory::_('Compiler.Builder.Database.Unique.Guid')->exists($view))
		{
			$fields[] = Indent::_(2) . "return array('guid');";
		}
		else
		{
			$fields[] = Indent::_(2) . "return false;";
		}
		$fields[] = Indent::_(1) . "}";

		// return the unique fields
		return implode(PHP_EOL, $fields);
	}

	/**
	 * build sidebar filter loading scripts
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string The php to place in view.html.php
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.AdminViews.SidebarFilters instead.
	 */
	public function setFilterFieldSidebarDisplayHelper(&$nameSingleCode, &$nameListCode)
	{
		return CFactory::_('Architecture.AdminViews.SidebarFilters')
			->get($nameSingleCode, $nameListCode);
	}





	/**
	 * build batch loading helper scripts
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string The php to place in view.html.php
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use Architecture.AdminViews.BatchOptions instead.
	 */
	public function setBatchDisplayHelper(&$nameSingleCode, &$nameListCode)
	{
		return CFactory::_('Architecture.AdminViews.BatchOptions')
			->get($nameSingleCode, $nameListCode);
	}





	/**
	 * Build the map entry from a category extension to the view that owns it.
	 *
	 * @param   string  $nameSingleCode  The single view code name.
	 * @param   string  $nameListCode    The list view code name.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Router.SiteRouter service.
	 */
	public function setRouterCategoryViews($nameSingleCode, $nameListCode)
	{
		return CFactory::_('Architecture.Router.SiteRouter')->categoryViews(
			(string) $nameSingleCode, (string) $nameListCode
		);
	}

	/**
	 * build the getForm method of an admin edit view model
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string The php to place in the model
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.GetForm service.
	 */
	public function setJmodelAdminGetForm($nameSingleCode, $nameListCode)
	{
		return CFactory::_('Architecture.Model.GetForm')
			->get($nameSingleCode, $nameListCode);
	}

	/**
	 * Add the edit permission guard of one field.
	 *
	 * @param   array   $allow            The guard lines being built
	 * @param   string  $nameSingleCode   The single view name
	 * @param   string  $fieldName        The field code name
	 * @param   string  $fieldType        The field type
	 * @param   string  $component        The component code name
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.GetForm service.
	 */
	protected function setPermissionEditFields(&$allow, $nameSingleCode, $fieldName, $fieldType, $component)
	{
		CFactory::_('Architecture.Model.GetForm')
			->setPermissionEditFields($allow, $nameSingleCode, $fieldName, $fieldType, $component);
	}

	/**
	 * Add the access permission guard of one field.
	 *
	 * @param   array   $allow            The guard lines being built
	 * @param   string  $nameSingleCode   The single view name
	 * @param   string  $fieldName        The field code name
	 * @param   string  $fieldType        The field type
	 * @param   string  $component        The component code name
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.GetForm service.
	 */
	protected function setPermissionAccessFields(&$allow, $nameSingleCode, $fieldName, $fieldType, $component)
	{
		CFactory::_('Architecture.Model.GetForm')
			->setPermissionAccessFields($allow, $nameSingleCode, $fieldName, $fieldType, $component);
	}

	/**
	 * Add the view permission guard of one field.
	 *
	 * @param   array   $allow            The guard lines being built
	 * @param   string  $nameSingleCode   The single view name
	 * @param   string  $fieldName        The field code name
	 * @param   string  $fieldType        The field type
	 * @param   string  $component        The component code name
	 *
	 * @return  void
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.GetForm service.
	 */
	protected function setPermissionViewFields(&$allow, $nameSingleCode, $fieldName, $fieldType, $component)
	{
		CFactory::_('Architecture.Model.GetForm')
			->setPermissionViewFields($allow, $nameSingleCode, $fieldName, $fieldType, $component);
	}

	/**
	 * Build the permission object the generated list view checks against.
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setJviewListCanDo($nameSingleCode, $nameListCode)
	{
		$allow = [];
		// set component name
		$component = CFactory::_('Config')->component_code_name;
		// check if the item has permissions for edit.
		$allow[] = PHP_EOL . Indent::_(2)
			. "\$this->canEdit = \$this->canDo->get('"
			. CFactory::_('Compiler.Creator.Permission')->getGlobal($nameSingleCode, 'core.edit')
			. "');";
		// check if the item has permissions for edit state.
		$allow[] = Indent::_(2) . "\$this->canState = \$this->canDo->get('"
			. CFactory::_('Compiler.Creator.Permission')->getGlobal($nameSingleCode, 'core.edit.state')
			. "');";
		// check if the item has permissions for create.
		$allow[] = Indent::_(2) . "\$this->canCreate = \$this->canDo->get('"
			. CFactory::_('Compiler.Creator.Permission')->getGlobal($nameSingleCode, 'core.create') . "');";
		// check if the item has permissions for delete.
		$allow[] = Indent::_(2) . "\$this->canDelete = \$this->canDo->get('"
			. CFactory::_('Compiler.Creator.Permission')->getGlobal($nameSingleCode, 'core.delete') . "');";
		// check if the item has permissions for batch.
		if (CFactory::_('Compiler.Creator.Permission')->globalExist($nameSingleCode, 'core.batch'))
		{
			$allow[] = Indent::_(2) . "\$this->canBatch = (\$this->canDo->get('"
				. CFactory::_('Compiler.Creator.Permission')->getGlobal($nameSingleCode, 'core.batch')
				. "') && \$this->canDo->get('core.batch'));";
		}
		else
		{
			$allow[] = Indent::_(2)
				. "\$this->canBatch = \$this->canDo->get('core.batch');";
		}

		return implode(PHP_EOL, $allow);
	}

	/**
	 * Build the access control fieldset of a view that has one.
	 *
	 * @param   string  $view  The view name
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 */
	public function setFieldSetAccessControl(&$view)
	{
		$access = '';
		if ($view != 'component')
		{
			// set component name
			$component = CFactory::_('Config')->component_code_name;
			// set label
			$label = 'Permissions in relation to this ' . $view;
			// set the access fieldset
			$access = "<!--" . Line::_(__Line__, __Class__)
				. " Access Control Fields. -->";
			$access .= PHP_EOL . Indent::_(1)
				. '<fieldset name="accesscontrol">';
			$access .= PHP_EOL . Indent::_(2) . "<!--" . Line::_(
					__LINE__,__CLASS__
				) . " Asset Id Field. Type: Hidden (joomla) -->";
			$access .= PHP_EOL . Indent::_(2) . '<field';
			$access .= PHP_EOL . Indent::_(3) . 'name="asset_id"';
			$access .= PHP_EOL . Indent::_(3) . 'type="hidden"';
			$access .= PHP_EOL . Indent::_(3) . 'filter="unset"';
			$access .= PHP_EOL . Indent::_(2) . '/>';
			$access .= PHP_EOL . Indent::_(2) . "<!--" . Line::_(
					__LINE__,__CLASS__
				) . " Rules Field. Type: Rules (joomla) -->";
			$access .= PHP_EOL . Indent::_(2) . '<field';
			$access .= PHP_EOL . Indent::_(3) . 'name="rules"';
			$access .= PHP_EOL . Indent::_(3) . 'type="rules"';
			$access .= PHP_EOL . Indent::_(3) . 'label="' . $label . '"';
			$access .= PHP_EOL . Indent::_(3) . 'translate_label="false"';
			$access .= PHP_EOL . Indent::_(3) . 'filter="rules"';
			$access .= PHP_EOL . Indent::_(3) . 'validate="rules"';
			$access .= PHP_EOL . Indent::_(3) . 'class="inputbox"';
			$access .= PHP_EOL . Indent::_(3) . 'component="com_' . $component
				. '"';
			$access .= PHP_EOL . Indent::_(3) . 'section="' . $view . '"';
			$access .= PHP_EOL . Indent::_(2) . '/>';
			$access .= PHP_EOL . Indent::_(1) . '</fieldset>';
		}

		// return access field set
		return $access;
	}

	/**
	 * set the filter fields
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string The code for the filter fields array
	 *
	 *
	 * @since   3.2.0
	 */
	public function setFilterFieldsArray(&$nameSingleCode, &$nameListCode)
	{
		// keep track of all fields already added
		$donelist = array('id'         => true, 'search' => true,
			'published'  => true, 'access' => true,
			'created_by' => true, 'modified_by' => true);
		// default filter fields
		$fields = "'a.id','id'";
		$fields .= "," . PHP_EOL . Indent::_(4) . "'a.published','published'";
		if (CFactory::_('Compiler.Builder.Access.Switch')->exists($nameSingleCode))
		{
			$fields .= "," . PHP_EOL . Indent::_(4) . "'a.access','access'";
		}
		$fields .= "," . PHP_EOL . Indent::_(4) . "'a.ordering','ordering'";
		$fields .= "," . PHP_EOL . Indent::_(4) . "'a.created_by','created_by'";
		$fields .= "," . PHP_EOL . Indent::_(4)
			. "'a.modified_by','modified_by'";

		// add the rest of the set filters
		if (CFactory::_('Compiler.Builder.Filter')->exists($nameListCode))
		{
			foreach (CFactory::_('Compiler.Builder.Filter')->get($nameListCode) as $filter)
			{
				if (!isset($donelist[$filter['code']]))
				{
					$fields                    .= $this->getFilterFieldCode(
						$filter
					);
					$donelist[$filter['code']] = true;
				}
			}
		}
		// add the rest of the set filters
		if (CFactory::_('Compiler.Builder.Sort')->exists($nameListCode))
		{
			foreach (CFactory::_('Compiler.Builder.Sort')->get($nameListCode) as $filter)
			{
				if (!isset($donelist[$filter['code']]))
				{
					$fields .= $this->getFilterFieldCode(
						$filter
					);
					$donelist[$filter['code']] = true;
				}
			}
		}

		return $fields;
	}

	/**
	 * Add the code of the filter field array
	 *
	 * @param   array  $filter  The field/filter array
	 *
	 * @return  string    The code for the filter array
	 *
	 *
	 * @since   3.2.0
	 */
	protected function getFilterFieldCode(&$filter)
	{
		// add the category stuff (may still remove these) TODO
		if ($filter['type'] === 'category')
		{
			$field = "," . PHP_EOL . Indent::_(4)
				. "'c.title','category_title'";
			$field .= "," . PHP_EOL . Indent::_(4)
				. "'c.id', 'category_id'";
			if ($filter['code'] != 'category')
			{
				$field .= "," . PHP_EOL . Indent::_(4) . "'a."
					. $filter['code'] . "','" . $filter['code']
					. "'";
			}
		}
		else
		{
			// check if custom field is set
			if (ArrayHelper::check(
					$filter['custom']
				)
				&& isset($filter['custom']['db'])
				&& StringHelper::check(
					$filter['custom']['db']
				)
				&& isset($filter['custom']['text'])
				&& StringHelper::check(
					$filter['custom']['text']
				))
			{
				$field = "," . PHP_EOL . Indent::_(4) . "'"
					. $filter['custom']['db'] . "."
					. $filter['custom']['text'] . "','" . $filter['code']
					. "'";
			}
			else
			{
				$field = "," . PHP_EOL . Indent::_(4) . "'a."
					. $filter['code'] . "','" . $filter['code']
					. "'";
			}
		}

		return $field;
	}

	/**
	 * set the sotred ids
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string The code for the populate state
	 *
	 *
	 * @since   3.2.0
	 */
	public function setStoredId(&$nameSingleCode, &$nameListCode)
	{
		// set component name
		$Component = ucwords((string) CFactory::_('Config')->component_code_name);
		// keep track of all fields already added
		$donelist = array('id'         => true, 'search' => true,
			'published'  => true, 'access' => true,
			'created_by' => true, 'modified_by' => true);
		// set the defaults first
		$stored = "//" . Line::_(__Line__, __Class__) . " Compile the store id.";
		$stored .= PHP_EOL . Indent::_(2)
			. "\$id .= ':' . \$this->getState('filter.id');";
		$stored .= PHP_EOL . Indent::_(2)
			. "\$id .= ':' . \$this->getState('filter.search');";
		// add this if not already added
		if (!CFactory::_('Compiler.Builder.Field.Names')->isString($nameSingleCode . '.published'))
		{
			$stored .= PHP_EOL . Indent::_(2)
				. "\$id .= ':' . \$this->getState('filter.published');";
		}
		// add if view calls for it, and not already added
		if (CFactory::_('Compiler.Builder.Access.Switch')->exists($nameSingleCode)
			&& !CFactory::_('Compiler.Builder.Field.Names')->isString($nameSingleCode . '.access'))
		{
			// the side bar option is single
			if (CFactory::_('Compiler.Builder.Admin.Filter.Type')->get($nameListCode, 1) == 1)
			{
				$stored .= PHP_EOL . Indent::_(2)
					. "\$id .= ':' . \$this->getState('filter.access');";
			}
			else
			{
				// top bar selection can result in
				// an array due to multi selection
				$stored .= $this->getStoredIdCodeMulti('access', $Component);
			}
		}
		$stored .= PHP_EOL . Indent::_(2)
			. "\$id .= ':' . \$this->getState('filter.ordering');";
		// add this if not already added
		if (!CFactory::_('Compiler.Builder.Field.Names')->isString($nameSingleCode . '.created_by'))
		{
			$stored .= PHP_EOL . Indent::_(2)
				. "\$id .= ':' . \$this->getState('filter.created_by');";
		}
		// add this if not already added
		if (!CFactory::_('Compiler.Builder.Field.Names')->isString($nameSingleCode . '.modified_by'))
		{
			$stored .= PHP_EOL . Indent::_(2)
				. "\$id .= ':' . \$this->getState('filter.modified_by');";
		}
		// add the rest of the set filters
		if (CFactory::_('Compiler.Builder.Filter')->exists($nameListCode))
		{
			foreach (CFactory::_('Compiler.Builder.Filter')->get($nameListCode) as $filter)
			{
				if (!isset($donelist[$filter['code']]))
				{
					$stored .= $this->getStoredIdCode(
						$filter, $nameListCode, $Component
					);
					$donelist[$filter['code']] = true;
				}
			}
		}
		// add the rest of the set filters
		if (CFactory::_('Compiler.Builder.Sort')->exists($nameListCode))
		{
			foreach (CFactory::_('Compiler.Builder.Sort')->get($nameListCode) as $filter)
			{
				if (!isset($donelist[$filter['code']]))
				{
					$stored .= $this->getStoredIdCode(
						$filter, $nameListCode, $Component
					);
					$donelist[$filter['code']] = true;
				}
			}
		}

		return $stored;
	}

	/**
	 * Add the code of the stored ids
	 *
	 * @param   array   $filter        The field/filter array
	 * @param   string  $nameListCode  The list view name
	 * @param   string  $Component     The Component name
	 *
	 * @return  string    The code for the stored IDs
	 *
	 */
	protected function getStoredIdCode(&$filter, &$nameListCode, &$Component)
	{
		if ($filter['type'] === 'category')
		{
			// the side bar option is single (1 = sidebar)
			if (CFactory::_('Compiler.Builder.Admin.Filter.Type')->get($nameListCode, 1) == 1)
			{
				$stored = PHP_EOL . Indent::_(2)
					. "\$id .= ':' . \$this->getState('filter.category');";
				$stored .= PHP_EOL . Indent::_(2)
					. "\$id .= ':' . \$this->getState('filter.category_id');";
				if ($filter['code'] != 'category')
				{
					$stored .= PHP_EOL . Indent::_(2)
						. "\$id .= ':' . \$this->getState('filter."
						. $filter['code'] . "');";
				}
			}
			else
			{
				$stored = $this->getStoredIdCodeMulti('category', $Component);
				$stored .= $this->getStoredIdCodeMulti(
					'category_id', $Component
				);
				if ($filter['code'] != 'category')
				{
					$stored .= $this->getStoredIdCodeMulti(
						$filter['code'], $Component
					);
				}
			}
		}
		else
		{
			// check if this is the topbar filter, and multi option (2 = topbar)
			if (isset($filter['multi']) && $filter['multi'] == 2
				&& CFactory::_('Compiler.Builder.Admin.Filter.Type')->get($nameListCode, 1) == 2)
			{
				// top bar selection can result in
				// an array due to multi selection
				$stored = $this->getStoredIdCodeMulti(
					$filter['code'], $Component
				);
			}
			else
			{
				$stored = PHP_EOL . Indent::_(2)
					. "\$id .= ':' . \$this->getState('filter."
					. $filter['code'] . "');";
			}
		}

		return $stored;
	}

	/**
	 * Add the code of the stored multi ids
	 *
	 * @param   string  $key        The key field name
	 * @param   string  $Component  The Component name
	 *
	 * @return  string    The code for the stored IDs
	 *
	 */
	protected function getStoredIdCodeMulti($key, &$Component)
	{
		// top bar selection can result in
		// an array due to multi selection
		$stored = PHP_EOL . Indent::_(2)
			. "//" . Line::_(__Line__, __Class__)
			. " Check if the value is an array";
		$stored .= PHP_EOL . Indent::_(2)
			. "\$_" . $key . " = \$this->getState('filter."
			. $key . "');";
		$stored .= PHP_EOL . Indent::_(2)
			. "if (Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$_"
			. $key . "))";
		$stored .= PHP_EOL . Indent::_(2)
			. "{";
		$stored .= PHP_EOL . Indent::_(3)
			. "\$id .= ':' . implode(':', \$_" . $key . ");";
		$stored .= PHP_EOL . Indent::_(2)
			. "}";
		$stored .= PHP_EOL . Indent::_(2)
			. "//" . Line::_(__Line__, __Class__)
			. " Check if this is only an number or string";
		$stored .= PHP_EOL . Indent::_(2)
			. "elseif (is_numeric(\$_" . $key . ")";
		$stored .= PHP_EOL . Indent::_(2)
			. " || Super_" . "__1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check(\$_" . $key . "))";
		$stored .= PHP_EOL . Indent::_(2)
			. "{";
		$stored .= PHP_EOL . Indent::_(3)
			. "\$id .= ':' . \$_" . $key . ";";
		$stored .= PHP_EOL . Indent::_(2)
			. "}";

		return $stored;
	}

	/**
	 * set the populate state code
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string The code for the populate state
	 *
	 */
	public function setPopulateState(&$nameSingleCode, &$nameListCode)
	{
		// reset bucket
		$state = '';
		// keep track of all fields already added
		$donelist = [];
		// we must add the formSubmited code if new above filters is used (2 = topbar)
		$new_filter = false;
		if (CFactory::_('Compiler.Builder.Admin.Filter.Type')->get($nameListCode, 1) == 2)
		{
			$state      .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
				. Line::_(__Line__, __Class__) . " Check if the form was submitted";
			$state      .= PHP_EOL . Indent::_(2) . "\$formSubmited"
				. " = \$input->post->get('form_submited');";
			$new_filter = true;
		}
		// add the default populate states (this must be added first)
		$state .= $this->setDefaultPopulateState($nameSingleCode, $new_filter);
		// add the filters
		if (CFactory::_('Compiler.Builder.Filter')->exists($nameListCode))
		{
			foreach (CFactory::_('Compiler.Builder.Filter')->get($nameListCode) as $filter)
			{
				if (!isset($donelist[$filter['code']]))
				{
					$state                     .= $this->getPopulateStateFilterCode(
						$filter, $new_filter
					);
					$donelist[$filter['code']] = true;
				}
			}
		}
		// add the rest of the set filters
		if (CFactory::_('Compiler.Builder.Sort')->exists($nameListCode))
		{
			foreach (CFactory::_('Compiler.Builder.Sort')->get($nameListCode) as $filter)
			{
				if (!isset($donelist[$filter['code']]))
				{
					$state .= $this->getPopulateStateFilterCode(
						$filter, $new_filter
					);
					$donelist[$filter['code']] = true;
				}
			}
		}

		return $state;
	}

	/**
	 * Add the code of the filter in the populate state
	 *
	 * @param   array   $filter     The field/filter array
	 * @param   bool    $newFilter  The switch to use the new filter
	 * @param   string  $extra      The defaults/extra options of the filter
	 *
	 * @return  string    The code for the populate state
	 *
	 */
	protected function getPopulateStateFilterCode(&$filter, $newFilter,
	                                              $extra = ''
	)
	{
		$state = '';
		// add category stuff (may still remove these) TODO
		if (isset($filter['type']) && $filter['type'] === 'category')
		{
			$state .= PHP_EOL . PHP_EOL . Indent::_(2)
				. "\$category = \$app->getUserStateFromRequest(\$this->context . '.filter.category', 'filter_category');";
			$state .= PHP_EOL . Indent::_(2)
				. "\$this->setState('filter.category', \$category);";
			$state .= PHP_EOL . PHP_EOL . Indent::_(2)
				. "\$categoryId = \$this->getUserStateFromRequest(\$this->context . '.filter.category_id', 'filter_category_id');";
			$state .= PHP_EOL . Indent::_(2)
				. "\$this->setState('filter.category_id', \$categoryId);";
		}
		// always add the default filter
		$state .= PHP_EOL . PHP_EOL . Indent::_(2) . "\$" . $filter['code']
			. " = \$this->getUserStateFromRequest(\$this->context . '.filter."
			. $filter['code'] . "', 'filter_" . $filter['code']
			. "'" . $extra . ");";
		if ($newFilter)
		{
			// add the new filter option
			$state .= PHP_EOL . Indent::_(2)
				. "if (\$formSubmited)";
			$state .= PHP_EOL . Indent::_(2) . "{";
			$state .= PHP_EOL . Indent::_(3) . "\$" . $filter['code']
				. " = \$input->post->get('" . $filter['code'] . "');";
			$state .= PHP_EOL . Indent::_(3)
				. "\$this->setState('filter." . $filter['code']
				. "', \$" . $filter['code'] . ");";
			$state .= PHP_EOL . Indent::_(2) . "}";
		}
		else
		{
			// the old filter option
			$state .= PHP_EOL . Indent::_(2)
				. "\$this->setState('filter." . $filter['code']
				. "', \$" . $filter['code'] . ");";
		}

		return $state;
	}

	/**
	 * set the default populate state code
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   bool    $newFilter       The switch to use the new filter
	 *
	 * @return  string The state code added
	 *
	 */
	protected function setDefaultPopulateState(&$nameSingleCode, $newFilter)
	{
		$state = '';
		// start filter
		$filter = array('type' => 'text');
		// if access is not set add its default filter here
		if (!CFactory::_('Compiler.Builder.Field.Names')->isString($nameSingleCode . '.access'))
		{
			$filter['code'] = "access";
			$state          .= $this->getPopulateStateFilterCode(
				$filter, $newFilter, ", 0, 'int'"
			);
		}
		// if published is not set add its default filter here
		if (!CFactory::_('Compiler.Builder.Field.Names')->isString($nameSingleCode . '.published'))
		{
			$filter['code'] = "published";
			$state          .= $this->getPopulateStateFilterCode(
				$filter, false, ", ''"
			);
		}
		// if created_by is not set add its default filter here
		if (!CFactory::_('Compiler.Builder.Field.Names')->isString($nameSingleCode . '.created_by'))
		{
			$filter['code'] = "created_by";
			$state          .= $this->getPopulateStateFilterCode(
				$filter, false, ", ''"
			);
		}
		// if created is not set add its default filter here
		if (!CFactory::_('Compiler.Builder.Field.Names')->isString($nameSingleCode . '.created'))
		{
			$filter['code'] = "created";
			$state          .= $this->getPopulateStateFilterCode(
				$filter, false
			);
		}

		// the sorting defaults are always added
		$filter['code'] = "sorting";
		$state          .= $this->getPopulateStateFilterCode(
			$filter, false, ", 0, 'int'"
		);
		// the search defaults are always added
		$filter['code'] = "search";
		$state          .= $this->getPopulateStateFilterCode($filter, false);

		return $state;
	}

	/**
	 * set the sorted field array for the getSortFields method
	 *
	 * @param   string  $nameSingleCode  The single view name
	 *
	 * @return  string The array/string of fields to add to the getSortFields method
	 *
	 */
	public function setSortFields(&$nameListCode)
	{
		// keep track of all fields already added
		$donelist = array('ordering', 'published');
		// set the default first
		$fields = "return array(";
		$fields .= PHP_EOL . Indent::_(3) . "'a.ordering' => Text:"
			. ":_('JGRID_HEADING_ORDERING')";
		$fields .= "," . PHP_EOL . Indent::_(3) . "'a.published' => Text:"
			. ":_('JSTATUS')";

		// add the rest of the set filters
		if (CFactory::_('Compiler.Builder.Sort')->exists($nameListCode))
		{
			foreach (CFactory::_('Compiler.Builder.Sort')->get($nameListCode) as $filter)
			{
				if (!in_array($filter['code'], $donelist))
				{
					if ($filter['type'] === 'category')
					{
						$fields .= "," . PHP_EOL . Indent::_(3)
							. "'category_title' => Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('"
							. $filter['lang'] . "')";
					}
					elseif (ArrayHelper::check(
						$filter['custom']
					))
					{
						$fields .= "," . PHP_EOL . Indent::_(3) . "'"
							. $filter['custom']['db'] . "."
							. $filter['custom']['text'] . "' => Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('"
							. $filter['lang'] . "')";
					}
					else
					{
						$fields .= "," . PHP_EOL . Indent::_(3) . "'a."
							. $filter['code'] . "' => Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('"
							. $filter['lang'] . "')";
					}
				}
			}
		}
		$fields .= "," . PHP_EOL . Indent::_(3) . "'a.id' => Text:"
			. ":_('JGRID_HEADING_ID')";
		$fields .= PHP_EOL . Indent::_(2) . ");";

		// return fields
		return $fields;
	}

	/**
	 * Add the item fixes a list model applies after loading.
	 *
	 * @param   string  $nameSingleCode  The single view code name.
	 * @param   string  $nameListCode    The list view code name.
	 * @param   string  $Component       The component code name.
	 * @param   string  $tab             Extra indentation of the generated lines.
	 * @param   bool    $export          Build for an export rather than a list.
	 * @param   bool    $all             Include every field, not only listed ones.
	 *
	 * @return  string  The generated fixes.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.ItemsStringFix service.
	 */
	public function setGetItemsMethodStringFix($nameSingleCode, $nameListCode,
		$Component, $tab = '', $export = false, $all = false)
	{
		return CFactory::_('Architecture.Model.ItemsStringFix')->get(
			$nameSingleCode, $nameListCode, $Component, $tab, $export, $all
		);
	}

	/**
	 * Get the relation statement of one field.
	 *
	 * @param   array   $item          The field definition.
	 * @param   string  $nameListCode  The list view code name.
	 * @param   string  $tab           Extra indentation of the generated lines.
	 *
	 * @return  string  The generated statement.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.FieldRelation service.
	 */
	protected function setModelFieldRelation($item, $nameListCode, $tab)
	{
		return CFactory::_('Architecture.Model.FieldRelation')
			->get((array) $item, (string) $nameListCode, (string) $tab);
	}

	/**
	 * Add the selection translation loop to a list model.
	 *
	 * @param   string  $views      The list view code name.
	 * @param   string  $Component  Unused, kept for the legacy signature.
	 * @param   string  $tab        Extra indentation of the generated lines.
	 *
	 * @return  string  The generated loop.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.SelectionTranslation service.
	 */
	public function setSelectionTranslationFix($views, $Component, $tab = '')
	{
		return CFactory::_('Architecture.Model.SelectionTranslation')
			->get($views, (string) $tab);
	}

	/**
	 * Add the selection translation method to a list model.
	 *
	 * @param   string  $views      The list view code name.
	 * @param   string  $Component  Unused, kept for the legacy signature.
	 *
	 * @return  string  The generated method.
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Model.SelectionTranslationMethod service.
	 */
	public function setSelectionTranslationFixFunc($views, $Component)
	{
		return CFactory::_('Architecture.Model.SelectionTranslationMethod')
			->get($views);
	}

	/**
	 * Build one view's case in the router's own parse switch.
	 *
	 * @param   string  $viewsCodeName  The list view code name.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Router.SiteRouter service.
	 */
	public function setRouterCase($viewsCodeName)
	{
		return CFactory::_('Architecture.Router.SiteRouter')->parseCase(
			(string) $viewsCodeName
		);
	}

	public function setDashboardIconAccess()
	{
		return CFactory::_('Compiler.Builder.Permission.Dashboard')->build();
	}

	/**
	 * build the dashboard icons of the component
	 *
	 * @return  string The dashboard icon array
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Dashboard.Icons service.
	 */
	public function setDashboardIcons()
	{
		// the compiler caches these on this helper, so they are carried over
		return CFactory::_('Architecture.Dashboard.Icons')
			->get($this->customAdminAdded);
	}

	public function setDashboardModelMethods()
	{
		if (CFactory::_('Component')->isString('php_dashboard_methods'))
		{
			// get hte value
			$php_dashboard_methods = CFactory::_('Component')->get('php_dashboard_methods');
			// get all the mothods that should load date to the view
			$this->DashboardGetCustomData
				= GetHelper::allBetween(
				$php_dashboard_methods,
				'public function get', '()'
			);

			// return the methods
			return PHP_EOL . PHP_EOL . CFactory::_('Placeholder')->update_(
					$php_dashboard_methods
				);
		}

		return '';
	}

	public function setDashboardGetCustomData()
	{
		if (isset($this->DashboardGetCustomData)
			&& ArrayHelper::check(
				$this->DashboardGetCustomData
			))
		{
			// gets array reset
			$gets = [];
			// set dashboard gets
			foreach ($this->DashboardGetCustomData as $get)
			{
				$string = StringHelper::safe($get);
				$gets[] = "\$this->" . $string . " = \$this->get('" . $get
					. "');";
			}

			// return the gets
			return PHP_EOL . Indent::_(2) . implode(
					PHP_EOL . Indent::_(2), $gets
				);
		}

		return '';
	}

	/**
	 * add the dashboard icons of one custom admin view
	 *
	 * @param   object  $view     The custom admin view
	 * @param   int     $counter  The icon counter
	 *
	 * @return  string The dashboard icons
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Dashboard.Icons service.
	 */
	public function addCustomDashboardIcons(&$view, &$counter)
	{
		// the compiler caches these on this helper, so they are carried over
		return CFactory::_('Architecture.Dashboard.Icons')
			->getCustomIcons($view, $counter, $this->customAdminAdded);
	}

	/**
	 * Build the component's administrator sub menu.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Menu.SubMenus service.
	 */
	public function setSubMenus()
	{
		$menus = CFactory::_('Architecture.Menu.SubMenus')->get();

		// the uninstall script the helper still builds reads these off the properties
		$this->uninstallScriptBuilder = CFactory::_('Compiler.Builder.Uninstall.Script.Context')
			->allActive() + $this->uninstallScriptBuilder;
		$this->uninstallScriptFields = CFactory::_('Compiler.Builder.Uninstall.Script.Fields')
			->allActive() + $this->uninstallScriptFields;

		return $menus;
	}

	/**
	 * Build the custom sub menu entries that sit beside the given admin view.
	 *
	 * @param   array   $view      The admin view being walked.
	 * @param   string  $codeName  The component code name.
	 * @param   string  $lang      The menu language prefix.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Menu.CustomSubMenu service.
	 */
	public function addCustomSubMenu(&$view, &$codeName, &$lang)
	{
		$service = CFactory::_('Architecture.Menu.CustomSubMenu');
		$custom = $service->get(
			$view, (string) $codeName, (string) $lang, $this->customAdminAdded
		);

		// the caller reads these off this property once every view is walked,
		// and unsets it, so they are handed over rather than mirrored
		foreach ($service->takeDeferred() as $nr => $deferred)
		{
			$this->lastCustomSubMenu[$nr] = $deferred;
		}

		return $custom;
	}

	/**
	 * Build the component's administrator main menu.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Menu.MainMenus service.
	 */
	public function setMainMenus()
	{
		return CFactory::_('Architecture.Menu.MainMenus')->get();
	}

	/**
	 * Build the custom menu entries that sit before the given admin view.
	 *
	 * @param   array   $view      The admin view being walked.
	 * @param   string  $codeName  The component code name.
	 * @param   string  $lang      The menu language prefix.
	 *
	 * @return  string
	 *
	 * @since   3.2.0
	 * @deprecated 6.1.7 Use the Architecture.Menu.CustomMainMenu service.
	 */
	public function addCustomMainMenu(&$view, &$codeName, &$lang)
	{
		$service = CFactory::_('Architecture.Menu.CustomMainMenu');
		$customMenu = $service->get(
			$view, (string) $codeName, (string) $lang, $this->customAdminAdded
		);

		// the caller reads these off this property once every view is walked,
		// and unsets it, so they are handed over rather than mirrored
		foreach ($service->takeDeferred() as $nr => $deferred)
		{
			$this->lastCustomMainMenu[$nr] = $deferred;
		}

		return $customMenu;
	}

	public function getInbetweenStrings($str, $start = '#' . '#' . '#', $end = '#' . '#' . '#')
	{
		$matches = [];
		$regex   = "/$start([a-zA-Z0-9_]*)$end/";
		preg_match_all($regex, (string) $str, $matches);

		return $matches[1];
	}
}
