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
	 * @return  string  The generated prepare document method.
	 *
	 * @since   3.2.0
	 */
	public function setPrepareDocument(&$view)
	{
		// fix just incase we missed it somewhere
		$tmp = CFactory::_('Config')->lang_target;
		if ('site' === CFactory::_('Config')->build_target)
		{
			CFactory::_('Config')->lang_target = 'site';
		}
		else
		{
			CFactory::_('Config')->lang_target = 'admin';
		}

		// ensure correct target is set
		$TARGET = StringHelper::safe(CFactory::_('Config')->build_target, 'U');

		// set libraries $TARGET.'_LIBRARIES_LOADER
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET . '_LIBRARIES_LOADER',
			$this->setLibrariesLoader($view)
		);

		// set uikit $TARGET.'_UIKIT_LOADER
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET . '_UIKIT_LOADER',
			$this->setUikitLoader($view)
		);

		// set Google Charts $TARGET.'_GOOGLECHART_LOADER
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' .$TARGET . '_GOOGLECHART_LOADER',
			$this->setGoogleChartLoader($view)
		);

		// set Footable FOOTABLE_LOADER
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET . '_FOOTABLE_LOADER',
			$this->setFootableScriptsLoader($view)
		);

		// set metadata DOCUMENT_METADATA
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET . '_DOCUMENT_METADATA',
			$this->setDocumentMetadata($view)
		);

		// set custom php scripting DOCUMENT_CUSTOM_PHP
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET . '_DOCUMENT_CUSTOM_PHP',
			$this->setDocumentCustomPHP($view)
		);

		// set custom css DOCUMENT_CUSTOM_CSS
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' .$TARGET . '_DOCUMENT_CUSTOM_CSS',
			$this->setDocumentCustomCSS($view)
		);

		// set custom javascript DOCUMENT_CUSTOM_JS
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET . '_DOCUMENT_CUSTOM_JS',
			$this->setDocumentCustomJS($view)
		);

		// set custom css file VIEWCSS
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET . '_VIEWCSS',
			$this->setCustomCSS($view)
		);

		// incase no buttons are found
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|SITE_JAVASCRIPT_FOR_BUTTONS', '');

		// set the custom buttons CUSTOM_BUTTONS
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET . '_CUSTOM_BUTTONS',
			CFactory::_('Architecture.CustomButtons')->get($view)
		);

		// see if we should add get modules to the view.html
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET . '_GET_MODULE',
			$this->setGetModules($view, $TARGET)
		);

		// set a JavaScript file if needed
		CFactory::_('Compiler.Builder.Content.Multi')->add($view['settings']->code . '|' . $TARGET . '_LIBRARIES_LOADER',
			$this->setJavaScriptFile($view, $TARGET), false
		);
		// fix just incase we missed it somewhere
		CFactory::_('Config')->lang_target = $tmp;
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
	 */
	public function setGetModules($view, $TARGET)
	{
		if (CFactory::_('Compiler.Builder.Get.Module')->
			exists(CFactory::_('Config')->build_target . '.' . $view['settings']->code))
		{
			$addModule   = [];
			$addModule[] = PHP_EOL . PHP_EOL . Indent::_(1) . "/**";
			$addModule[] = Indent::_(1)
				. " * Get the modules published in a position";
			$addModule[] = Indent::_(1) . " */";
			$addModule[] = Indent::_(1)
				. "public function getModules(\$position, \$seperator = '', \$class = '')";
			$addModule[] = Indent::_(1) . "{";
			$addModule[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " set default";
			$addModule[] = Indent::_(2) . "\$found = false;";
			$addModule[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " check if we aleady have these modules loaded";
			$addModule[] = Indent::_(2)
				. "if (isset(\$this->setModules[\$position]))";
			$addModule[] = Indent::_(2) . "{";
			$addModule[] = Indent::_(3) . "\$found = true;";
			$addModule[] = Indent::_(2) . "}";
			$addModule[] = Indent::_(2) . "else";
			$addModule[] = Indent::_(2) . "{";
			$addModule[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " this is where you want to load your module position";
			$addModule[] = Indent::_(3)
				. "\$modules = Joomla__"."_f15d556d_33dd_4ee3_a0f7_0653e4a7a1e4___Power::getModules(\$position);";
			$addModule[] = Indent::_(3) . "if ("
				. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$modules, true))";
			$addModule[] = Indent::_(3) . "{";
			$addModule[] = Indent::_(4) . "//" . Line::_(__Line__, __Class__)
				. " set the place holder";
			$addModule[] = Indent::_(4)
				. "\$this->setModules[\$position] = [];";
			$addModule[] = Indent::_(4) . "foreach(\$modules as \$module)";
			$addModule[] = Indent::_(4) . "{";
			$addModule[] = Indent::_(5)
				. "\$this->setModules[\$position][] = Joomla__"."_f15d556d_33dd_4ee3_a0f7_0653e4a7a1e4___Power::renderModule(\$module);";
			$addModule[] = Indent::_(4) . "}";
			$addModule[] = Indent::_(4) . "\$found = true;";
			$addModule[] = Indent::_(3) . "}";
			$addModule[] = Indent::_(2) . "}";
			$addModule[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " check if modules were found";
			$addModule[] = Indent::_(2)
				. "if (\$found && isset(\$this->setModules[\$position]) && "
				. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$this->setModules[\$position]))";
			$addModule[] = Indent::_(2) . "{";
			$addModule[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " set class";
			$addModule[] = Indent::_(3) . "if ("
				. "Super_" . "__1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check(\$class))";
			$addModule[] = Indent::_(3) . "{";
			$addModule[] = Indent::_(4)
				. "\$class = ' class=\"'.\$class.'\" ';";
			$addModule[] = Indent::_(3) . "}";
			$addModule[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " set seperating return values";
			$addModule[] = Indent::_(3) . "switch(\$seperator)";
			$addModule[] = Indent::_(3) . "{";
			$addModule[] = Indent::_(4) . "case 'none':";
			$addModule[] = Indent::_(5)
				. "return implode('', \$this->setModules[\$position]);";
			$addModule[] = Indent::_(5) . "break;";
			$addModule[] = Indent::_(4) . "case 'div':";
			$addModule[] = Indent::_(5)
				. "return '<div'.\$class.'>'.implode('</div><div'.\$class.'>', \$this->setModules[\$position]).'</div>';";
			$addModule[] = Indent::_(5) . "break;";
			$addModule[] = Indent::_(4) . "case 'list':";
			$addModule[] = Indent::_(5)
				. "return '<ul'.\$class.'><li>'.implode('</li><li>', \$this->setModules[\$position]).'</li></ul>';";
			$addModule[] = Indent::_(5) . "break;";
			$addModule[] = Indent::_(4) . "case 'array':";
			$addModule[] = Indent::_(4) . "case 'Array':";
			$addModule[] = Indent::_(5)
				. "return \$this->setModules[\$position];";
			$addModule[] = Indent::_(5) . "break;";
			$addModule[] = Indent::_(4) . "default:";
			$addModule[] = Indent::_(5)
				. "return implode('<br />', \$this->setModules[\$position]);";
			$addModule[] = Indent::_(5) . "break;";
			$addModule[] = Indent::_(3) . "}";
			$addModule[] = Indent::_(2) . "}";
			$addModule[] = Indent::_(2) . "return false;";
			$addModule[] = Indent::_(1) . "}";

			CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET . '_GET_MODULE_JIMPORT',
				PHP_EOL . "use Joomla\CMS\Helper\ModuleHelper;"
			);

			return implode(PHP_EOL, $addModule);
		}
		CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET . '_GET_MODULE_JIMPORT', '');

		return '';
	}

	public function setDocumentCustomPHP(&$view)
	{
		if ($view['settings']->add_php_document == 1)
		{
			$view['settings']->php_document = (array) explode(
				PHP_EOL, (string) $view['settings']->php_document
			);
			if (ArrayHelper::check(
				$view['settings']->php_document
			))
			{
				$_tmp = PHP_EOL . Indent::_(2) . implode(
					PHP_EOL . Indent::_(2), $view['settings']->php_document
				);

				return CFactory::_('Placeholder')->update_($_tmp);
			}
		}

		return '';
	}

	public function setCustomCSS(&$view)
	{
		if ($view['settings']->add_css == 1)
		{
			if (StringHelper::check($view['settings']->css))
			{
				return CFactory::_('Placeholder')->update_(
					$view['settings']->css
				);
			}
		}

		return '';
	}

	public function setDocumentCustomCSS(&$view)
	{
		if ($view['settings']->add_css_document == 1)
		{
			$view['settings']->css_document = (array) explode(
				PHP_EOL, (string) $view['settings']->css_document
			);
			if (ArrayHelper::check(
				$view['settings']->css_document
			))
			{
				if (CFactory::_('Config')->get('joomla_version', 3) == 3)
				{
					$script      = PHP_EOL . Indent::_(2) . "//" . Line::_(
						__LINE__,__CLASS__
						) . " Set the Custom CSS script to view" . PHP_EOL
						. Indent::_(2) . '$this->document->addStyleDeclaration("';
				}
				else
				{
					$script = PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Set the Custom JS script to view" . PHP_EOL
						. Indent::_(2) . '$this->getDocument()->getWebAssetManager()->addInlineStyle("';
				}

				$cssDocument = PHP_EOL . Indent::_(3) . str_replace(
					'"', '\"', implode(
						PHP_EOL . Indent::_(3),
						$view['settings']->css_document
					)
				);

				return $script . CFactory::_('Placeholder')->update_(
					$cssDocument
				) . PHP_EOL . Indent::_(2) . '");';
			}
		}

		return '';
	}

	public function setJavaScriptFile(&$view, $TARGET)
	{
		if ($view['settings']->add_javascript_file == 1
			&& StringHelper::check(
				$view['settings']->javascript_file
			))
		{
			// get dates
			$created  = CFactory::_('Model.Createdate')->get($view);
			$modified = CFactory::_('Model.Modifieddate')->get($view);
			// add file to view
			$target = array(CFactory::_('Config')->build_target => $view['settings']->code);
			$config = array(Placefix::_h('CREATIONDATE')                          => $created,
				Placefix::_h('BUILDDATE') => $modified,
				Placefix::_h('VERSION')                          => $view['settings']->version);
			CFactory::_('Utilities.Structure')->build($target, 'javascript_file', false, $config);
			// set path
			if ('site' === CFactory::_('Config')->build_target)
			{
				$path = '/components/com_' . CFactory::_('Config')->component_code_name
					. '/assets/js/' . $view['settings']->code . '.js';
			}
			else
			{
				$path = '/administrator/components/com_'
					. CFactory::_('Config')->component_code_name . '/assets/js/'
					. $view['settings']->code . '.js';
			}
			// add script to file
			CFactory::_('Compiler.Builder.Content.Multi')->set($view['settings']->code . '|' . $TARGET
				. '_JAVASCRIPT_FILE', CFactory::_('Placeholder')->update_(
				$view['settings']->javascript_file
			));

			// add script to view
			return PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Add View JavaScript File" . PHP_EOL . Indent::_(2)
				. CFactory::_('Library.IncludeHelper')->get($path);
		}

		return '';
	}

	public function setDocumentCustomJS(&$view)
	{
		if ($view['settings']->add_js_document == 1)
		{
			$view['settings']->js_document = (array) explode(
				PHP_EOL, (string) $view['settings']->js_document
			);
			if (ArrayHelper::check(
				$view['settings']->js_document
			))
			{
				if (CFactory::_('Config')->get('joomla_version', 3) == 3)
				{
					$script = PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Set the Custom JS script to view" . PHP_EOL
						. Indent::_(2) . '$this->getDocument()->addScriptDeclaration("';
				}
				else
				{
					$script = PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Set the Custom JS script to view" . PHP_EOL
						. Indent::_(2) . '$this->getDocument()->getWebAssetManager()->addInlineScript("';
				}

				$jsDocument = PHP_EOL . Indent::_(3) . str_replace(
						'"', '\"', implode(
							PHP_EOL . Indent::_(3),
							$view['settings']->js_document
						)
					);

				return $script . CFactory::_('Placeholder')->update_(
						$jsDocument
					) . PHP_EOL . Indent::_(2) . '");';
			}
		}

		return '';
	}

	public function setFootableScriptsLoader(&$view)
	{
		if (CFactory::_('Compiler.Builder.Footable.Scripts')->
			exists(CFactory::_('Config')->build_target . '.' . $view['settings']->code))
		{
			return $this->setFootableScripts(false);
		}

		return '';
	}

	public function setDocumentMetadata(&$view)
	{
		if ($view['settings']->main_get->gettype == 1
			&& isset($view['metadata'])
			&& $view['metadata'] == 1)
		{
			return $this->setMetadataItem();
		}
		elseif (isset($view['metadata']) && $view['metadata'] == 1)
		{
			// lets check if we have a custom get method that has the same name as the view
			// if we do then it posibly can be that the metadata is loaded via that method
			// and we can load the full metadata structure with its vars
			if (isset($view['settings']->custom_get)
				&& ArrayHelper::check(
					$view['settings']->custom_get
				))
			{
				$found     = false;
				$searchFor = 'get' . $view['settings']->Code;
				foreach ($view['settings']->custom_get as $custom_get)
				{
					if ($searchFor == $custom_get->getcustom)
					{
						$found = true;
						break;
					}
				}
				// now lets see
				if ($found)
				{
					return $this->setMetadataItem($view['settings']->code);
				}
				else
				{
					return $this->setMetadataList();
				}
			}
			else
			{
				return $this->setMetadataList();
			}
		}

		return '';
	}

	public function setMetadataItem($item = 'item')
	{
		if (CFactory::_('Config')->get('joomla_version', 3) == 3)
		{
			return $this->setMetadataItemJ3($item);
		}
		return $this->setMetadataItemJ4($item);
	}

	public function setMetadataList()
	{
		if (CFactory::_('Config')->get('joomla_version', 3) == 3)
		{
			return $this->setMetadataListJ3();
		}
		return $this->setMetadataListJ4();
	}

	public function setMetadataItemJ3($item = 'item')
	{
		$meta   = [];
		$meta[] = PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " load the meta description";
		$meta[] = Indent::_(2) . "if (isset(\$this->" . $item
			. "->metadesc) && \$this->" . $item . "->metadesc)";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3) . "\$this->document->setDescription(\$this->"
			. $item . "->metadesc);";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2)
			. "elseif (\$this->params->get('menu-meta_description'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->document->setDescription(\$this->params->get('menu-meta_description'));";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " load the key words if set";
		$meta[] = Indent::_(2) . "if (isset(\$this->" . $item
			. "->metakey) && \$this->" . $item . "->metakey)";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->document->setMetadata('keywords', \$this->" . $item
			. "->metakey);";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2)
			. "elseif (\$this->params->get('menu-meta_keywords'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->document->setMetadata('keywords', \$this->params->get('menu-meta_keywords'));";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " check the robot params";
		$meta[] = Indent::_(2) . "if (isset(\$this->" . $item
			. "->robots) && \$this->" . $item . "->robots)";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->document->setMetadata('robots', \$this->" . $item
			. "->robots);";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "elseif (\$this->params->get('robots'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->document->setMetadata('robots', \$this->params->get('robots'));";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " check if autor is to be set";
		$meta[] = Indent::_(2) . "if (isset(\$this->" . $item
			. "->created_by) && \$this->params->get('MetaAuthor') == '1')";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->document->setMetaData('author', \$this->" . $item
			. "->created_by);";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " check if metadata is available";
		$meta[] = Indent::_(2) . "if (isset(\$this->" . $item
			. "->metadata) && \$this->" . $item . "->metadata)";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3) . "\$mdata = json_decode(\$this->" . $item
			. "->metadata,true);";
		$meta[] = Indent::_(3) . "foreach (\$mdata as \$k => \$v)";
		$meta[] = Indent::_(3) . "{";
		$meta[] = Indent::_(4) . "if (\$v)";
		$meta[] = Indent::_(4) . "{";
		$meta[] = Indent::_(5) . "\$this->document->setMetadata(\$k, \$v);";
		$meta[] = Indent::_(4) . "}";
		$meta[] = Indent::_(3) . "}";
		$meta[] = Indent::_(2) . "}";

		return implode(PHP_EOL, $meta);
	}

	public function setMetadataListJ3()
	{
		$meta   = [];
		$meta[] = PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " load the meta description";
		$meta[] = Indent::_(2)
			. "if (\$this->params->get('menu-meta_description'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->document->setDescription(\$this->params->get('menu-meta_description'));";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " load the key words if set";
		$meta[] = Indent::_(2)
			. "if (\$this->params->get('menu-meta_keywords'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->document->setMetadata('keywords', \$this->params->get('menu-meta_keywords'));";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " check the robot params";
		$meta[] = Indent::_(2) . "if (\$this->params->get('robots'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->document->setMetadata('robots', \$this->params->get('robots'));";
		$meta[] = Indent::_(2) . "}";

		return implode(PHP_EOL, $meta);
	}

	public function setMetadataItemJ4($item = 'item')
	{
		$meta   = [];
		$meta[] = PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " load the meta description";
		$meta[] = Indent::_(2) . "if (isset(\$this->" . $item
			. "->metadesc) && \$this->" . $item . "->metadesc)";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3) . "\$this->setDocumentTitle(\$this->"
			. $item . "->metadesc);";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2)
			. "elseif (\$this->params->get('menu-meta_description'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->setDocumentTitle(\$this->params->get('menu-meta_description'));";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " load the key words if set";
		$meta[] = Indent::_(2) . "if (isset(\$this->" . $item
			. "->metakey) && \$this->" . $item . "->metakey)";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->getDocument()->setMetadata('keywords', \$this->" . $item
			. "->metakey);";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2)
			. "elseif (\$this->params->get('menu-meta_keywords'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->getDocument()->setMetadata('keywords', \$this->params->get('menu-meta_keywords'));";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " check the robot params";
		$meta[] = Indent::_(2) . "if (isset(\$this->" . $item
			. "->robots) && \$this->" . $item . "->robots)";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->getDocument()->setMetadata('robots', \$this->" . $item
			. "->robots);";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "elseif (\$this->params->get('robots'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->getDocument()->setMetadata('robots', \$this->params->get('robots'));";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " check if autor is to be set";
		$meta[] = Indent::_(2) . "if (isset(\$this->" . $item
			. "->created_by) && \$this->params->get('MetaAuthor') == '1')";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->getDocument()->setMetaData('author', \$this->" . $item
			. "->created_by);";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " check if metadata is available";
		$meta[] = Indent::_(2) . "if (isset(\$this->" . $item
			. "->metadata) && \$this->" . $item . "->metadata)";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3) . "\$mdata = json_decode(\$this->" . $item
			. "->metadata,true);";
		$meta[] = Indent::_(3) . "foreach (\$mdata as \$k => \$v)";
		$meta[] = Indent::_(3) . "{";
		$meta[] = Indent::_(4) . "if (\$v)";
		$meta[] = Indent::_(4) . "{";
		$meta[] = Indent::_(5) . "\$this->getDocument()->setMetadata(\$k, \$v);";
		$meta[] = Indent::_(4) . "}";
		$meta[] = Indent::_(3) . "}";
		$meta[] = Indent::_(2) . "}";

		return implode(PHP_EOL, $meta);
	}

	public function setMetadataListJ4()
	{
		$meta   = [];
		$meta[] = PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " load the meta description";
		$meta[] = Indent::_(2)
			. "if (\$this->params->get('menu-meta_description'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->getDocument()->setDescription(\$this->params->get('menu-meta_description'));";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " load the key words if set";
		$meta[] = Indent::_(2)
			. "if (\$this->params->get('menu-meta_keywords'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->getDocument()->setMetadata('keywords', \$this->params->get('menu-meta_keywords'));";
		$meta[] = Indent::_(2) . "}";
		$meta[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
			. " check the robot params";
		$meta[] = Indent::_(2) . "if (\$this->params->get('robots'))";
		$meta[] = Indent::_(2) . "{";
		$meta[] = Indent::_(3)
			. "\$this->getDocument()->setMetadata('robots', \$this->params->get('robots'));";
		$meta[] = Indent::_(2) . "}";

		return implode(PHP_EOL, $meta);
	}

	public function setGoogleChartLoader(&$view)
	{
		if (CFactory::_('Compiler.Builder.Google.Chart')->
			exists(CFactory::_('Config')->build_target . '.' . $view['settings']->code))
		{
			$chart   = [];
			$chart[] = PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " add the google chart builder class.";
			$chart[] = Indent::_(2)
				. "require_once JPATH_ADMINISTRATOR . '/components/com_" . CFactory::_('Config')->component_code_name . "/helpers/chartbuilder.php';";
			$chart[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " load the google chart js.";
			$chart[] = Indent::_(2)
				. "Html::_('script', 'media/com_"
				. CFactory::_('Config')->component_code_name . "/js/google.jsapi.js', ['version' => 'auto']);";
			$chart[] = Indent::_(2)
				. "Html::_('script', 'https://canvg.googlecode.com/svn/trunk/rgbcolor.js', ['version' => 'auto']);";
			$chart[] = Indent::_(2)
				. "Html::_('script', 'https://canvg.googlecode.com/svn/trunk/canvg.js', ['version' => 'auto']);";

			return implode(PHP_EOL, $chart);
		}

		return '';
	}

	public function setLibrariesLoader($view)
	{
		// check call sig
		if (isset($view['settings']) && isset($view['settings']->code))
		{
			$code        = $view['settings']->code;
			$view_active = true;
		}
		elseif (isset($view->code_name))
		{
			$code        = $view->code_name;
			$view_active = false;
		}
		// reset bucket
		$setter = '';
		// always load these in
		if ($view_active)
		{
			$setter .= PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Only load jQuery if needed. (default is true)";
			$setter .= PHP_EOL . Indent::_(2) . "if (\$this->params->get('add_jquery_framework', 1) == 1)";
			$setter .= PHP_EOL . Indent::_(2) . "{";
			$setter .= PHP_EOL . Indent::_(3) . "Html::_('jquery.framework');";
			$setter .= PHP_EOL . Indent::_(2) . "}";
			$setter .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " Load the header checker class.";

			if (CFactory::_('Config')->get('joomla_version', 3) == 3)
			{
				if (CFactory::_('Config')->build_target === 'site')
				{
					$setter .= PHP_EOL . Indent::_(2)
						. "require_once( JPATH_SITE . '/components/com_" . CFactory::_('Config')->component_code_name . "/helpers/headercheck.php' );";
				}
				else
				{
					$setter .= PHP_EOL . Indent::_(2)
						. "require_once( JPATH_ADMINISTRATOR . '/components/com_" . CFactory::_('Config')->component_code_name . "/helpers/headercheck.php' );";
				}
				$setter .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
					. " Initialize the header checker.";
				$setter .= PHP_EOL . Indent::_(2) . "\$HeaderCheck = new "
					. CFactory::_('Config')->component_code_name . "HeaderCheck();";
			}
			else
			{
				$setter .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
					. " Initialize the header checker.";
				$setter .= PHP_EOL . Indent::_(2) . "\$HeaderCheck = new HeaderCheck();";
			}
		}
		// check if this view should get libraries
		if (($data = CFactory::_('Compiler.Builder.Library.Manager')->
			get(CFactory::_('Config')->build_target . '.' . $code)) !== null)
		{
			foreach ($data as $id => $data_item)
			{
				// get the library
				$library = CFactory::_('Registry')->get("builder.libraries.$id", null);
				if (is_object($library) && isset($library->document)
					&& StringHelper::check($library->document))
				{
					$setter .= PHP_EOL . PHP_EOL . CFactory::_('Placeholder')->update_(
							str_replace(
								[
									'$document->',
									'$this->document->'
								],
								'$this->getDocument()->',
								(string) $library->document
							)
						);
				}
				elseif (is_object($library)
					&& isset($library->how))
				{
					$setter .= CFactory::_('Library.Document')->get($id);
				}
			}
		}
		// convert back to $document if module call (oops :)
		if (!$view_active)
		{
			return str_replace(['$this->getDocument()->', '$this->document->'], '$document->', $setter);
		}

		return $setter;
	}

	public function setUikitLoader(&$view)
	{
		// we do not load this for Joomla 6+ (use the libraries to add it if you still need it)
		if ((int) CFactory::_('Config')->get('joomla_version', 3) === 6)
		{
			return '';
		}

		// reset setter
		$setter = '';
		// load the defaults needed
		if (CFactory::_('Config')->uikit > 0)
		{
			$setter .= PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Load uikit options.";
			$setter .= PHP_EOL . Indent::_(2)
				. "\$uikit = \$this->params->get('uikit_load');";
			$setter .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " Set script size.";
			$setter .= PHP_EOL . Indent::_(2)
				. "\$size = \$this->params->get('uikit_min');";
			$tabV   = "";
			// if both versions should be loaded then add some more logic
			if (2 == CFactory::_('Config')->uikit)
			{
				$setter .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
					. Line::_(__Line__, __Class__) . " Load uikit version.";
				$setter .= PHP_EOL . Indent::_(2)
					. "\$this->uikitVersion = \$this->params->get('uikit_version', 2);";
				$setter .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
					. Line::_(__Line__, __Class__) . " Use Uikit Version 2";
				$setter .= PHP_EOL . Indent::_(2)
					. "if (2 == \$this->uikitVersion)";
				$setter .= PHP_EOL . Indent::_(2) . "{";
				$tabV   = Indent::_(1);
			}
		}
		// load the defaults needed
		if (2 == CFactory::_('Config')->uikit || 1 == CFactory::_('Config')->uikit)
		{
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Set css style.";
			$setter .= PHP_EOL . $tabV . Indent::_(2)
				. "\$style = \$this->params->get('uikit_style');";

			$setter .= PHP_EOL . PHP_EOL . $tabV . Indent::_(2) . "//"
				. Line::_(__Line__, __Class__) . " The uikit css.";
			$setter .= PHP_EOL . $tabV . Indent::_(2)
				. "if ((!\$HeaderCheck->css_loaded('uikit.min') || \$uikit == 1) && \$uikit != 2 && \$uikit != 3)";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(3)
				. "Html::_('stylesheet', 'media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v2/css/uikit'.\$style.\$size.'.css', ['version' => 'auto']);";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "}";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " The uikit js.";
			$setter .= PHP_EOL . $tabV . Indent::_(2)
				. "if ((!\$HeaderCheck->js_loaded('uikit.min') || \$uikit == 1) && \$uikit != 2 && \$uikit != 3)";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(3)
				. "Html::_('script', 'media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v2/js/uikit'.\$size.'.js', ['version' => 'auto']);";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "}";
		}
		// load the components need
		if ((2 == CFactory::_('Config')->uikit || 1 == CFactory::_('Config')->uikit)
			&& ($data_ = CFactory::_('Compiler.Builder.Uikit.Comp')->get($view['settings']->code)) !== null)
		{
			$setter .= PHP_EOL . PHP_EOL . $tabV . Indent::_(2) . "//"
				. Line::_(__Line__, __Class__)
				. " Load the script to find all uikit components needed.";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "if (\$uikit != 2)";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(3) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Set the default uikit components in this view.";
			$setter .= PHP_EOL . $tabV . Indent::_(3)
				. "\$uikitComp = [];";
			foreach ($data_ as $class)
			{
				$setter .= PHP_EOL . $tabV . Indent::_(3) . "\$uikitComp[] = '"
					. $class . "';";
			}
			// check content for more needed components
			if (CFactory::_('Compiler.Builder.Site.Field.Data')->exists('uikit.' . $view['settings']->code))
			{
				$setter .= PHP_EOL . PHP_EOL . $tabV . Indent::_(3) . "//"
					. Line::_(__Line__, __Class__)
					. " Get field uikit components needed in this view.";
				$setter .= PHP_EOL . $tabV . Indent::_(3)
					. "\$uikitFieldComp = \$this->get('UikitComp');";
				$setter .= PHP_EOL . $tabV . Indent::_(3)
					. "if (isset(\$uikitFieldComp) && "
					. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$uikitFieldComp))";
				$setter .= PHP_EOL . $tabV . Indent::_(3) . "{";
				$setter .= PHP_EOL . $tabV . Indent::_(4)
					. "if (isset(\$uikitComp) && "
					. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$uikitComp))";
				$setter .= PHP_EOL . $tabV . Indent::_(4) . "{";
				$setter .= PHP_EOL . $tabV . Indent::_(5)
					. "\$uikitComp = array_merge(\$uikitComp, \$uikitFieldComp);";
				$setter .= PHP_EOL . $tabV . Indent::_(5)
					. "\$uikitComp = array_unique(\$uikitComp);";
				$setter .= PHP_EOL . $tabV . Indent::_(4) . "}";
				$setter .= PHP_EOL . $tabV . Indent::_(4) . "else";
				$setter .= PHP_EOL . $tabV . Indent::_(4) . "{";
				$setter .= PHP_EOL . $tabV . Indent::_(5)
					. "\$uikitComp = \$uikitFieldComp;";
				$setter .= PHP_EOL . $tabV . Indent::_(4) . "}";
				$setter .= PHP_EOL . $tabV . Indent::_(3) . "}";
			}
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "}";
			$setter .= PHP_EOL . PHP_EOL . $tabV . Indent::_(2) . "//"
				. Line::_(__Line__, __Class__)
				. " Load the needed uikit components in this view.";
			$setter .= PHP_EOL . $tabV . Indent::_(2)
				. "if (\$uikit != 2 && isset(\$uikitComp) && "
				. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$uikitComp))";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(3) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " loading...";
			$setter .= PHP_EOL . $tabV . Indent::_(3)
				. "foreach (\$uikitComp as \$class)";
			$setter .= PHP_EOL . $tabV . Indent::_(3) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(4) . "foreach ("
				. CFactory::_('Compiler.Builder.Content.One')->get('Component') . "Helper::\$uk_components[\$class] as \$name)";
			$setter .= PHP_EOL . $tabV . Indent::_(4) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " check if the CSS file exists.";
			$setter .= PHP_EOL . $tabV . Indent::_(5)
				. "if (@file_exists(JPATH_ROOT.'/media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v2/css/components/'.\$name.\$style.\$size.'.css'))";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(6) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " load the css.";
			$setter .= PHP_EOL . $tabV . Indent::_(6)
				. "Html::_('stylesheet', 'media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v2/css/components/'.\$name.\$style.\$size.'.css', ['version' => 'auto']);";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "}";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " check if the JavaScript file exists.";
			$setter .= PHP_EOL . $tabV . Indent::_(5)
				. "if (@file_exists(JPATH_ROOT.'/media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v2/js/components/'.\$name.\$size.'.js'))";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(6) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " load the js.";
			$setter .= PHP_EOL . $tabV . Indent::_(6)
				. "Html::_('script', 'media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v2/js/components/'.\$name.\$size.'.js', ['version' => 'auto'], ['type' => 'text/javascript', 'async' => 'async']);";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "}";
			$setter .= PHP_EOL . $tabV . Indent::_(4) . "}";
			$setter .= PHP_EOL . $tabV . Indent::_(3) . "}";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "}";
		}
		elseif ((2 == CFactory::_('Config')->uikit || 1 == CFactory::_('Config')->uikit)
			&& CFactory::_('Compiler.Builder.Site.Field.Data')->exists('uikit.' . $view['settings']->code))
		{
			$setter .= PHP_EOL . PHP_EOL . $tabV . Indent::_(2) . "//"
				. Line::_(__Line__, __Class__)
				. " Load the needed uikit components in this view.";
			$setter .= PHP_EOL . $tabV . Indent::_(2)
				. "\$uikitComp = \$this->get('UikitComp');";
			$setter .= PHP_EOL . $tabV . Indent::_(2)
				. "if (\$uikit != 2 && isset(\$uikitComp) && "
				. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$uikitComp))";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(3) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " loading...";
			$setter .= PHP_EOL . $tabV . Indent::_(3)
				. "foreach (\$uikitComp as \$class)";
			$setter .= PHP_EOL . $tabV . Indent::_(3) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(4) . "foreach ("
				. CFactory::_('Compiler.Builder.Content.One')->get('Component') . "Helper::\$uk_components[\$class] as \$name)";
			$setter .= PHP_EOL . $tabV . Indent::_(4) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " check if the CSS file exists.";
			$setter .= PHP_EOL . $tabV . Indent::_(5)
				. "if (@file_exists(JPATH_ROOT.'/media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v2/css/components/'.\$name.\$style.\$size.'.css'))";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(6) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " load the css.";
			$setter .= PHP_EOL . $tabV . Indent::_(6)
				. "Html::_('stylesheet', 'media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v2/css/components/'.\$name.\$style.\$size.'.css', ['version' => 'auto']);";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "}";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " check if the JavaScript file exists.";
			$setter .= PHP_EOL . $tabV . Indent::_(5)
				. "if (@file_exists(JPATH_ROOT.'/media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v2/js/components/'.\$name.\$size.'.js'))";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(6) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " load the js.";
			$setter .= PHP_EOL . $tabV . Indent::_(6)
				. "Html::_('script', 'media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v2/js/components/'.\$name.\$size.'.js', ['version' => 'auto'], ['type' => 'text/javascript', 'async' => 'async']);";
			$setter .= PHP_EOL . $tabV . Indent::_(5) . "}";
			$setter .= PHP_EOL . $tabV . Indent::_(4) . "}";
			$setter .= PHP_EOL . $tabV . Indent::_(3) . "}";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "}";
		}
		// now set the version 3
		if (2 == CFactory::_('Config')->uikit || 3 == CFactory::_('Config')->uikit)
		{
			if (2 == CFactory::_('Config')->uikit)
			{
				$setter .= PHP_EOL . Indent::_(2) . "}";
				$setter .= PHP_EOL . Indent::_(2) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Use Uikit Version 3";
				$setter .= PHP_EOL . Indent::_(2)
					. "elseif (3 == \$this->uikitVersion)";
				$setter .= PHP_EOL . Indent::_(2) . "{";
			}
			// add version 3 fiels to page
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " The uikit css.";
			$setter .= PHP_EOL . $tabV . Indent::_(2)
				. "if ((!\$HeaderCheck->css_loaded('uikit.min') || \$uikit == 1) && \$uikit != 2 && \$uikit != 3)";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(3)
				. "Html::_('stylesheet', 'media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v3/css/uikit'.\$size.'.css', ['version' => 'auto']);";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "}";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " The uikit js.";
			$setter .= PHP_EOL . $tabV . Indent::_(2)
				. "if ((!\$HeaderCheck->js_loaded('uikit.min') || \$uikit == 1) && \$uikit != 2 && \$uikit != 3)";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "{";
			$setter .= PHP_EOL . $tabV . Indent::_(3)
				. "Html::_('script', 'media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v3/js/uikit'.\$size.'.js', ['version' => 'auto']);";
			$setter .= PHP_EOL . $tabV . Indent::_(3)
				. "Html::_('script', 'media/com_"
				. CFactory::_('Config')->component_code_name
				. "/uikit-v3/js/uikit-icons'.\$size.'.js', ['version' => 'auto']);";
			$setter .= PHP_EOL . $tabV . Indent::_(2) . "}";
			if (2 == CFactory::_('Config')->uikit)
			{
				$setter .= PHP_EOL . Indent::_(2) . "}";
			}
		}

		return $setter;
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

	public function setComponentToContentTypes($action)
	{
		if (CFactory::_('Component')->isArray('admin_views'))
		{
			// set component name
			$component = CFactory::_('Config')->component_code_name;
			// reset
			$dbStuff = [];
			// start loading the content type data
			foreach (CFactory::_('Component')->get('admin_views') as $viewData)
			{
				// set main keys
				$view = StringHelper::safe(
					$viewData['settings']->name_single
				);
				// set list view keys
				$views = StringHelper::safe(
					$viewData['settings']->name_list
				);
				// get this views content type data
				$dbStuff[$view] = $this->getContentType($view, $component);
				// get the correct views name
				$checkViews = CFactory::_('Compiler.Builder.Category.Code')->getString("{$view}.views", $views);
				if (ArrayHelper::check($dbStuff[$view])
					&& CFactory::_('Compiler.Builder.Category.Code')->exists($view)
					&& ($checkViews == $views))
				{
					$dbStuff[$view . ' category']
						= $this->getCategoryContentType(
						$view, $views, $component
					);
				}
				elseif (!isset($dbStuff[$view])
					|| !ArrayHelper::check($dbStuff[$view]))
				{
					// remove if not array
					unset($dbStuff[$view]);
				}
			}

			if (CFactory::_('Config')->get('joomla_version', 3) == 3)
			{
				return $this->setComponentToContentTypesJ3($action, $dbStuff);
			}

			return $this->setComponentToContentTypesJ4($action, $dbStuff);
		}

		return '';
	}

	protected function setComponentToContentTypesJ3($action, $dbStuff)
	{
		// build the db insert query
		if (ArrayHelper::check($dbStuff))
		{
			$script = '';
			$taabb = '';
			if ($action === 'update')
			{
				$taabb = Indent::_(1);
			}
			$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//"
				. Line::_(__Line__, __Class__) . " Get The Database object";
			$script .= PHP_EOL . Indent::_(3)
				. "\$db = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getDbo();";
			foreach ($dbStuff as $name => $tables)
			{
				if (ArrayHelper::check($tables))
				{
					$code   = StringHelper::safe($name);
					$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//"
						. Line::_(__Line__, __Class__) . " Create the " . $name
						. " content type object.";
					$script .= PHP_EOL . Indent::_(3) . "\$" . $code
						. " = new \stdClass();";
					foreach ($tables as $table => $data)
					{
						$script .= PHP_EOL . Indent::_(3) . "\$" . $code
							. "->" . $table . " = '" . $data . "';";
					}
					if ($action === 'update')
					{
						// we first load script to check if data exist
						$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//"
							. Line::_(__Line__, __Class__) . " Check if "
							. $name
							. " type is already in content_type DB.";
						$script .= PHP_EOL . Indent::_(3) . "\$" . $code
							. "_id = null;";
						$script .= PHP_EOL . Indent::_(3)
							. "\$query = \$db->getQuery(true);";
						$script .= PHP_EOL . Indent::_(3)
							. "\$query->select(\$db->quoteName(array('type_id')));";
						$script .= PHP_EOL . Indent::_(3)
							. "\$query->from(\$db->quoteName('#__content_types'));";
						$script .= PHP_EOL . Indent::_(3)
							. "\$query->where(\$db->quoteName('type_alias') . ' LIKE '. \$db->quote($"
							. $code . "->type_alias));";
						$script .= PHP_EOL . Indent::_(3)
							. "\$db->setQuery(\$query);";
						$script .= PHP_EOL . Indent::_(3)
							. "\$db->execute();";
					}
					$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//"
						. Line::_(__Line__, __Class__)
						. " Set the object into the content types table.";
					if ($action === 'update')
					{
						$script .= PHP_EOL . Indent::_(3)
							. "if (\$db->getNumRows())";
						$script .= PHP_EOL . Indent::_(3) . "{";
						$script .= PHP_EOL . Indent::_(4) . "\$" . $code
							. "->type_id = \$db->loadResult();";
						$script .= PHP_EOL . Indent::_(4) . "\$" . $code
							. "_Updated = \$db->updateObject('#__content_types', \$"
							. $code . ", 'type_id');";
						$script .= PHP_EOL . Indent::_(3) . "}";
						$script .= PHP_EOL . Indent::_(3) . "else";
						$script .= PHP_EOL . Indent::_(3) . "{";
					}
					$script .= PHP_EOL . Indent::_(3) . $taabb . "\$"
						. $code
						. "_Inserted = \$db->insertObject('#__content_types', \$"
						. $code . ");";
					if ($action === 'update')
					{
						$script .= PHP_EOL . Indent::_(3) . "}";
					}
				}
			}

			$script .= PHP_EOL . PHP_EOL;
			return $script;
		}

		return '';
	}

	protected function setComponentToContentTypesJ4($action, $dbStuff)
	{
		// build the db insert query
		if (ArrayHelper::check($dbStuff))
		{
			$script = PHP_EOL;
			foreach ($dbStuff as $name => $columns)
			{
				if (ArrayHelper::check($columns))
				{
					$script .= PHP_EOL . Indent::_(3) . "//"
						. Line::_(__Line__, __Class__) . " "
						. StringHelper::safe($action, 'Ww') . " "
						. StringHelper::safe($name, 'Ww') . " Content Types.";

					$script .= PHP_EOL . Indent::_(3) .
						'$this->setContentType(';
					$script .= PHP_EOL . Indent::_(4) .
						"//" . Line::_(__Line__, __Class__) . " typeTitle";
					$script .= PHP_EOL . Indent::_(4) .
						"'{$columns['type_title']}',";
					$script .= PHP_EOL . Indent::_(4) .
						"//" . Line::_(__Line__, __Class__) . " typeAlias";
					$script .= PHP_EOL . Indent::_(4) .
						"'{$columns['type_alias']}',";
					$script .= PHP_EOL . Indent::_(4) .
						"//" . Line::_(__Line__, __Class__) . " table";
					$script .= PHP_EOL . Indent::_(4) .
						"'{$columns['table']}',";
					$script .= PHP_EOL . Indent::_(4) .
						"//" . Line::_(__Line__, __Class__) . " rules";
					$script .= PHP_EOL . Indent::_(4) .
						"'{$columns['rules']}',";
					$script .= PHP_EOL . Indent::_(4) .
						"//" . Line::_(__Line__, __Class__) . " fieldMappings";
					$script .= PHP_EOL . Indent::_(4) .
						"'{$columns['field_mappings']}',";
					$script .= PHP_EOL . Indent::_(4) .
						"//" . Line::_(__Line__, __Class__) . " router";
					$script .= PHP_EOL . Indent::_(4) .
						"'{$columns['router']}',";
					$script .= PHP_EOL . Indent::_(4) .
						"//" . Line::_(__Line__, __Class__) . " contentHistoryOptions";
					$script .= PHP_EOL . Indent::_(4) .
						"'{$columns['content_history_options']}'";
					$script .= PHP_EOL . Indent::_(3) .
						');';

				}
			}
			$script .= PHP_EOL . PHP_EOL;
			return $script;
		}

		return '';
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

	public function setUninstallScript()
	{
		if (CFactory::_('Config')->get('joomla_version', 3) == 3)
		{
			return $this->setUninstallScriptJ3();
		}

		return $this->setUninstallScriptJ4();
	}

	public function setUninstallScriptJ3()
	{
		// reset script
		$script = '';
		if (isset($this->uninstallScriptBuilder)
			&& ArrayHelper::check(
				$this->uninstallScriptBuilder
			))
		{
			$component = CFactory::_('Config')->component_code_name;
			// start loading the data to delete
			$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " Get Application object";
			$script .= PHP_EOL . Indent::_(2)
				. "\$app = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication();";
			$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Get The Database object";
			$script .= PHP_EOL . Indent::_(2) . "\$db = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getDbo();";

			foreach (
				$this->uninstallScriptBuilder as $viewsCodeName => $typeAlias
			)
			{
				// set a var value
				$view = StringHelper::safe($viewsCodeName);

				// check if it has field relations
				if (isset($this->uninstallScriptFields)
					&& isset($this->uninstallScriptFields[$viewsCodeName]))
				{
					// First check if data is till in table
					$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
						. Line::_(__Line__, __Class__)
						. " Create a new query object.";
					$script .= PHP_EOL . Indent::_(2)
						. "\$query = \$db->getQuery(true);";
					$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Select ids from fields";
					$script .= PHP_EOL . Indent::_(2)
						. "\$query->select(\$db->quoteName('id'));";
					$script .= PHP_EOL . Indent::_(2)
						. "\$query->from(\$db->quoteName('#__fields'));";
					$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Where " . $viewsCodeName . " context is found";
					$script .= PHP_EOL . Indent::_(2)
						. "\$query->where( \$db->quoteName('context') . ' = '. \$db->quote('"
						. $typeAlias . "') );";
					$script .= PHP_EOL . Indent::_(2)
						. "\$db->setQuery(\$query);";
					$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Execute query to see if context is found";
					$script .= PHP_EOL . Indent::_(2) . "\$db->execute();";
					$script .= PHP_EOL . Indent::_(2) . "\$" . $view
						. "_found = \$db->getNumRows();";
					$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Now check if there were any rows";
					$script .= PHP_EOL . Indent::_(2) . "if (\$" . $view
						. "_found)";
					$script .= PHP_EOL . Indent::_(2) . "{";
					$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Since there are load the needed  " . $view
						. " field ids";
					$script .= PHP_EOL . Indent::_(3) . "\$" . $view
						. "_field_ids = \$db->loadColumn();";

					// Now remove the actual type entry
					$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Remove " . $viewsCodeName
						. " from the field table";
					$script .= PHP_EOL . Indent::_(3) . "\$" . $view
						. "_condition = array( \$db->quoteName('context') . ' = '. \$db->quote('"
						. $typeAlias . "') );";
					$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Create a new query object.";
					$script .= PHP_EOL . Indent::_(3)
						. "\$query = \$db->getQuery(true);";
					$script .= PHP_EOL . Indent::_(3)
						. "\$query->delete(\$db->quoteName('#__fields'));";
					$script .= PHP_EOL . Indent::_(3) . "\$query->where(\$"
						. $view . "_condition);";
					$script .= PHP_EOL . Indent::_(3)
						. "\$db->setQuery(\$query);";
					$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Execute the query to remove " . $viewsCodeName
						. " items";
					$script .= PHP_EOL . Indent::_(3) . "\$" . $view
						. "_done = \$db->execute();";
					$script .= PHP_EOL . Indent::_(3) . "if (\$" . $view
						. "_done)";
					$script .= PHP_EOL . Indent::_(3) . "{";
					$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " If successfully remove " . $viewsCodeName
						. " add queued success message.";
					// TODO lang is not translated
					$script .= PHP_EOL . Indent::_(4)
						. "\$app->enqueueMessage(Text:"
						. ":_('The fields with type (" . $typeAlias
						. ") context was removed from the <b>#__fields</b> table'));";
					$script .= PHP_EOL . Indent::_(3) . "}";
					$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Also Remove " . $viewsCodeName . " field values";
					$script .= PHP_EOL . Indent::_(3) . "\$" . $view
						. "_condition = array( \$db->quoteName('field_id') . ' IN ('. implode(',', \$"
						. $view . "_field_ids) .')');";
					$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Create a new query object.";
					$script .= PHP_EOL . Indent::_(3)
						. "\$query = \$db->getQuery(true);";
					$script .= PHP_EOL . Indent::_(3)
						. "\$query->delete(\$db->quoteName('#__fields_values'));";
					$script .= PHP_EOL . Indent::_(3) . "\$query->where(\$"
						. $view . "_condition);";
					$script .= PHP_EOL . Indent::_(3)
						. "\$db->setQuery(\$query);";
					$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Execute the query to remove " . $viewsCodeName
						. " field values";
					$script .= PHP_EOL . Indent::_(3) . "\$" . $view
						. "_done = \$db->execute();";
					$script .= PHP_EOL . Indent::_(3) . "if (\$" . $view
						. "_done)";
					$script .= PHP_EOL . Indent::_(3) . "{";
					$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " If successfully remove " . $viewsCodeName
						. " add queued success message.";
					// TODO lang is not translated
					$script .= PHP_EOL . Indent::_(4)
						. "\$app->enqueueMessage(Text:"
						. ":_('The fields values for " . $viewsCodeName
						. " was removed from the <b>#__fields_values</b> table'));";
					$script .= PHP_EOL . Indent::_(3) . "}";
					$script .= PHP_EOL . Indent::_(2) . "}";

					// First check if data is till in table
					$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
						. Line::_(__Line__, __Class__)
						. " Create a new query object.";
					$script .= PHP_EOL . Indent::_(2)
						. "\$query = \$db->getQuery(true);";
					$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Select ids from field groups";
					$script .= PHP_EOL . Indent::_(2)
						. "\$query->select(\$db->quoteName('id'));";
					$script .= PHP_EOL . Indent::_(2)
						. "\$query->from(\$db->quoteName('#__fields_groups'));";
					$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Where " . $viewsCodeName . " context is found";
					$script .= PHP_EOL . Indent::_(2)
						. "\$query->where( \$db->quoteName('context') . ' = '. \$db->quote('"
						. $typeAlias . "') );";
					$script .= PHP_EOL . Indent::_(2)
						. "\$db->setQuery(\$query);";
					$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Execute query to see if context is found";
					$script .= PHP_EOL . Indent::_(2) . "\$db->execute();";
					$script .= PHP_EOL . Indent::_(2) . "\$" . $view
						. "_found = \$db->getNumRows();";
					$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Now check if there were any rows";
					$script .= PHP_EOL . Indent::_(2) . "if (\$" . $view
						. "_found)";
					$script .= PHP_EOL . Indent::_(2) . "{";

					// Now remove the actual type entry
					$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Remove " . $viewsCodeName
						. " from the field groups table";
					$script .= PHP_EOL . Indent::_(3) . "\$" . $view
						. "_condition = array( \$db->quoteName('context') . ' = '. \$db->quote('"
						. $typeAlias . "') );";
					$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Create a new query object.";
					$script .= PHP_EOL . Indent::_(3)
						. "\$query = \$db->getQuery(true);";
					$script .= PHP_EOL . Indent::_(3)
						. "\$query->delete(\$db->quoteName('#__fields_groups'));";
					$script .= PHP_EOL . Indent::_(3) . "\$query->where(\$"
						. $view . "_condition);";
					$script .= PHP_EOL . Indent::_(3)
						. "\$db->setQuery(\$query);";
					$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Execute the query to remove " . $viewsCodeName
						. " items";
					$script .= PHP_EOL . Indent::_(3) . "\$" . $view
						. "_done = \$db->execute();";
					$script .= PHP_EOL . Indent::_(3) . "if (\$" . $view
						. "_done)";
					$script .= PHP_EOL . Indent::_(3) . "{";
					$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " If successfully remove " . $viewsCodeName
						. " add queued success message.";
					// TODO lang is not translated
					$script .= PHP_EOL . Indent::_(4)
						. "\$app->enqueueMessage(Text:"
						. ":_('The field groups with type (" . $typeAlias
						. ") context was removed from the <b>#__fields_groups</b> table'));";
					$script .= PHP_EOL . Indent::_(3) . "}";
					$script .= PHP_EOL . Indent::_(2) . "}";
				}
				// First check if data is till in table
				$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
					. Line::_(__Line__, __Class__) . " Create a new query object.";
				$script .= PHP_EOL . Indent::_(2)
					. "\$query = \$db->getQuery(true);";
				$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Select id from content type table";
				$script .= PHP_EOL . Indent::_(2)
					. "\$query->select(\$db->quoteName('type_id'));";
				$script .= PHP_EOL . Indent::_(2)
					. "\$query->from(\$db->quoteName('#__content_types'));";
				$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Where " . $viewsCodeName . " alias is found";
				$script .= PHP_EOL . Indent::_(2)
					. "\$query->where( \$db->quoteName('type_alias') . ' = '. \$db->quote('"
					. $typeAlias . "') );";
				$script .= PHP_EOL . Indent::_(2) . "\$db->setQuery(\$query);";
				$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Execute query to see if alias is found";
				$script .= PHP_EOL . Indent::_(2) . "\$db->execute();";
				$script .= PHP_EOL . Indent::_(2) . "\$" . $view
					. "_found = \$db->getNumRows();";
				$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Now check if there were any rows";
				$script .= PHP_EOL . Indent::_(2) . "if (\$" . $view
					. "_found)";
				$script .= PHP_EOL . Indent::_(2) . "{";
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Since there are load the needed  " . $view
					. " type ids";
				$script .= PHP_EOL . Indent::_(3) . "\$" . $view
					. "_ids = \$db->loadColumn();";

				// Now remove the actual type entry
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Remove " . $viewsCodeName
					. " from the content type table";
				$script .= PHP_EOL . Indent::_(3) . "\$" . $view
					. "_condition = array( \$db->quoteName('type_alias') . ' = '. \$db->quote('"
					. $typeAlias . "') );";
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Create a new query object.";
				$script .= PHP_EOL . Indent::_(3)
					. "\$query = \$db->getQuery(true);";
				$script .= PHP_EOL . Indent::_(3)
					. "\$query->delete(\$db->quoteName('#__content_types'));";
				$script .= PHP_EOL . Indent::_(3) . "\$query->where(\$" . $view
					. "_condition);";
				$script .= PHP_EOL . Indent::_(3) . "\$db->setQuery(\$query);";
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Execute the query to remove " . $viewsCodeName
					. " items";
				$script .= PHP_EOL . Indent::_(3) . "\$" . $view
					. "_done = \$db->execute();";
				$script .= PHP_EOL . Indent::_(3) . "if (\$" . $view . "_done)";
				$script .= PHP_EOL . Indent::_(3) . "{";
				$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " If successfully remove " . $viewsCodeName
					. " add queued success message.";
				// TODO lang is not translated
				$script .= PHP_EOL . Indent::_(4)
					. "\$app->enqueueMessage(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('The (" . $typeAlias
					. ") type alias was removed from the <b>#__content_type</b> table'));";
				$script .= PHP_EOL . Indent::_(3) . "}";

				// Now remove the related items from contentitem tag map table
				$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//"
					. Line::_(__Line__, __Class__) . " Remove " . $viewsCodeName
					. " items from the contentitem tag map table";
				$script .= PHP_EOL . Indent::_(3) . "\$" . $view
					. "_condition = array( \$db->quoteName('type_alias') . ' = '. \$db->quote('"
					. $typeAlias . "') );";
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Create a new query object.";
				$script .= PHP_EOL . Indent::_(3)
					. "\$query = \$db->getQuery(true);";
				$script .= PHP_EOL . Indent::_(3)
					. "\$query->delete(\$db->quoteName('#__contentitem_tag_map'));";
				$script .= PHP_EOL . Indent::_(3) . "\$query->where(\$" . $view
					. "_condition);";
				$script .= PHP_EOL . Indent::_(3) . "\$db->setQuery(\$query);";
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Execute the query to remove " . $viewsCodeName
					. " items";
				$script .= PHP_EOL . Indent::_(3) . "\$" . $view
					. "_done = \$db->execute();";
				$script .= PHP_EOL . Indent::_(3) . "if (\$" . $view . "_done)";
				$script .= PHP_EOL . Indent::_(3) . "{";
				$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " If successfully remove " . $viewsCodeName
					. " add queued success message.";
				// TODO lang is not translated
				$script .= PHP_EOL . Indent::_(4)
					. "\$app->enqueueMessage(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('The (" . $typeAlias
					. ") type alias was removed from the <b>#__contentitem_tag_map</b> table'));";
				$script .= PHP_EOL . Indent::_(3) . "}";

				// Now remove the related items from ucm content table
				$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//"
					. Line::_(__Line__, __Class__) . " Remove " . $viewsCodeName
					. " items from the ucm content table";
				$script .= PHP_EOL . Indent::_(3) . "\$" . $view
					. "_condition = array( \$db->quoteName('core_type_alias') . ' = ' . \$db->quote('"
					. $typeAlias . "') );";
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Create a new query object.";
				$script .= PHP_EOL . Indent::_(3)
					. "\$query = \$db->getQuery(true);";
				$script .= PHP_EOL . Indent::_(3)
					. "\$query->delete(\$db->quoteName('#__ucm_content'));";
				$script .= PHP_EOL . Indent::_(3) . "\$query->where(\$" . $view
					. "_condition);";
				$script .= PHP_EOL . Indent::_(3) . "\$db->setQuery(\$query);";
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Execute the query to remove " . $viewsCodeName
					. " items";
				$script .= PHP_EOL . Indent::_(3) . "\$" . $view
					. "_done = \$db->execute();";
				$script .= PHP_EOL . Indent::_(3) . "if (\$" . $view . "_done)";
				$script .= PHP_EOL . Indent::_(3) . "{";
				$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " If successfully removed " . $viewsCodeName
					. " add queued success message.";
				// TODO lang is not translated
				$script .= PHP_EOL . Indent::_(4)
					. "\$app->enqueueMessage(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('The (" . $typeAlias
					. ") type alias was removed from the <b>#__ucm_content</b> table'));";
				$script .= PHP_EOL . Indent::_(3) . "}";

				// setup the foreach loop of ids
				$script .= PHP_EOL . PHP_EOL . Indent::_(3) . "//"
					. Line::_(__Line__, __Class__) . " Make sure that all the "
					. $viewsCodeName . " items are cleared from DB";
				$script .= PHP_EOL . Indent::_(3) . "foreach (\$" . $view
					. "_ids as \$" . $view . "_id)";
				$script .= PHP_EOL . Indent::_(3) . "{";

				// Now remove the related items from ucm base table
				$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Remove " . $viewsCodeName
					. " items from the ucm base table";
				$script .= PHP_EOL . Indent::_(4) . "\$" . $view
					. "_condition = array( \$db->quoteName('ucm_type_id') . ' = ' . \$"
					. $view . "_id);";
				$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Create a new query object.";
				$script .= PHP_EOL . Indent::_(4)
					. "\$query = \$db->getQuery(true);";
				$script .= PHP_EOL . Indent::_(4)
					. "\$query->delete(\$db->quoteName('#__ucm_base'));";
				$script .= PHP_EOL . Indent::_(4) . "\$query->where(\$" . $view
					. "_condition);";
				$script .= PHP_EOL . Indent::_(4) . "\$db->setQuery(\$query);";
				$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Execute the query to remove " . $viewsCodeName
					. " items";
				$script .= PHP_EOL . Indent::_(4) . "\$db->execute();";

				// Now remove the related items from ucm history table
				$script .= PHP_EOL . PHP_EOL . Indent::_(4) . "//"
					. Line::_(__Line__, __Class__) . " Remove " . $viewsCodeName
					. " items from the ucm history table";
				$script .= PHP_EOL . Indent::_(4) . "\$" . $view
					. "_condition = array( \$db->quoteName('ucm_type_id') . ' = ' . \$"
					. $view . "_id);";
				$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Create a new query object.";
				$script .= PHP_EOL . Indent::_(4)
					. "\$query = \$db->getQuery(true);";
				$script .= PHP_EOL . Indent::_(4)
					. "\$query->delete(\$db->quoteName('#__ucm_history'));";
				$script .= PHP_EOL . Indent::_(4) . "\$query->where(\$" . $view
					. "_condition);";
				$script .= PHP_EOL . Indent::_(4) . "\$db->setQuery(\$query);";
				$script .= PHP_EOL . Indent::_(4) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Execute the query to remove " . $viewsCodeName
					. " items";
				$script .= PHP_EOL . Indent::_(4) . "\$db->execute();";

				$script .= PHP_EOL . Indent::_(3) . "}";

				$script .= PHP_EOL . Indent::_(2) . "}";
			}

			$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " If All related items was removed queued success message.";
			// TODO lang is not translated
			$script .= PHP_EOL . Indent::_(2) . "\$app->enqueueMessage(Text:"
				. ":_('All related items was removed from the <b>#__ucm_base</b> table'));";
			$script .= PHP_EOL . Indent::_(2) . "\$app->enqueueMessage(Text:"
				. ":_('All related items was removed from the <b>#__ucm_history</b> table'));";
			// finaly remove the assets from the assets table
			$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Remove " . $component . " assets from the assets table";
			$script .= PHP_EOL . Indent::_(2) . "\$" . $component
				. "_condition = array( \$db->quoteName('name') . ' LIKE ' . \$db->quote('com_"
				. $component . "%') );";
			$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Create a new query object.";
			$script .= PHP_EOL . Indent::_(2)
				. "\$query = \$db->getQuery(true);";
			$script .= PHP_EOL . Indent::_(2)
				. "\$query->delete(\$db->quoteName('#__assets'));";
			$script .= PHP_EOL . Indent::_(2) . "\$query->where(\$" . $component
				. "_condition);";
			$script .= PHP_EOL . Indent::_(2) . "\$db->setQuery(\$query);";
			$script .= PHP_EOL . Indent::_(2) . "\$" . $view
				. "_done = \$db->execute();";
			$script .= PHP_EOL . Indent::_(2) . "if (\$" . $view . "_done)";
			$script .= PHP_EOL . Indent::_(2) . "{";
			$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " If successfully removed " . $component
				. " add queued success message.";
			// TODO lang is not translated
			$script .= PHP_EOL . Indent::_(3) . "\$app->enqueueMessage(Text:"
				. ":_('All related items was removed from the <b>#__assets</b> table'));";
			$script .= PHP_EOL . Indent::_(2) . "}";
			// done
			$script .= PHP_EOL;
		}
		elseif (CFactory::_('Config')->add_assets_table_fix == 2)
		{
			// start loading the data to delete (WE NEED THIS)
			$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " Get Application object";
			$script .= PHP_EOL . Indent::_(2)
				. "\$app = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication();";
			$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Get The Database object";
			$script .= PHP_EOL . Indent::_(2) . "\$db = Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getDbo();";
		}
		// add the Intelligent Reversal script if needed
		$script .= $this->getAssetsTableIntelligentUninstall();
		// add the custom uninstall script
		$script .= CFactory::_('Customcode.Dispenser')->get(
			'php_method', 'uninstall', "", null, true, null, PHP_EOL
		);

		return $script;
	}

	public function setUninstallScriptJ4()
	{
		// reset script
		$script = '';
		if (isset($this->uninstallScriptBuilder)
			&& ArrayHelper::check(
				$this->uninstallScriptBuilder
			))
		{
			// start loading the data to delete
			$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " Remove Related Component Data.";
			foreach ($this->uninstallScriptBuilder as $viewsCodeName => $context)
			{
				// set a var value
				$View = StringHelper::safe($viewsCodeName, 'Ww');
				// First check if data is till in table
				$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
					. Line::_(__Line__, __Class__)
					. " Remove $View Data";
				$field = '';
				// check if it has field relations
				if (isset($this->uninstallScriptFields)
					&& isset($this->uninstallScriptFields[$viewsCodeName]))
				{
					$field = ', true';
				}
				// First check if data is till in table
				$script .= PHP_EOL . Indent::_(2) . "\$this->removeViewData(\"$context\"$field);";
			}

			$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Remove Asset Data.";
			$script .= PHP_EOL . Indent::_(2) . "\$this->removeAssetData();";
			// done
			$script .= PHP_EOL;
		}

		// add the Intelligent Reversal script if needed
		$script .= $this->getAssetsTableIntelligentUninstall();

		// add the custom uninstallation script
		$script .= CFactory::_('Customcode.Dispenser')->get(
			'php_method', 'uninstall', "", null, true, null, PHP_EOL
		);

		return $script;
	}

	/**
	 * build code for the assets table script intelligent fix
	 *
	 * @return  string The php to place in script.php
	 *
	 */
	protected function getAssetsTableIntelligentInstall()
	{
		// WHY DO WE NEED AN ASSET TABLE FIX?
		// https://www.mysqltutorial.org/mysql-varchar/
		// https://stackoverflow.com/a/15227917/1429677
		// https://forums.mysql.com/read.php?24,105964,105964
		// https://git.vdm.dev/joomla/Component-Builder/issues/616#issuecomment-12085
		// 30 actions each +-20 characters with 8 groups
		// that makes 4800 characters and the current Joomla
		// column size is varchar(5120)

		// check if we should add the intelligent fix treatment for the assets table
		if (CFactory::_('Config')->add_assets_table_fix == 2)
		{
			// get worse case
			$access_worse_case = CFactory::_('Config')->get('access_worse_case', 0);
			// get the type we will convert to
			$data_type = ($access_worse_case > 64000) ? "MEDIUMTEXT"
				: "TEXT";

			if (CFactory::_('Config')->get('joomla_version', 3) != 3)
			{
				$script   = [];
				$script[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
					. " Fix the assets table rules column size.";
				$script[] = Indent::_(3) . '$this->setDatabaseAssetsRulesFix('
					. (int) $access_worse_case . ', "' . $data_type . '");';

				return PHP_EOL . implode(PHP_EOL, $script);
			}

			// the if statement about $rule_length
			$codeIF = "\$rule_length <= " . $access_worse_case;
			// fix column size
			$script   = [];
			$script[] = Indent::_(5) . "//" . Line::_(__Line__, __Class__)
				. " Fix the assets table rules column size";
			$script[] = Indent::_(5)
				. '$fix_rules_size = "ALTER TABLE `#__assets` CHANGE `rules` `rules` '
				. $data_type
				. ' NOT NULL COMMENT \'JSON encoded access control. Enlarged to '
				. $data_type . ' by JCB\';";';
			$script[] = Indent::_(5) . "\$db->setQuery(\$fix_rules_size);";
			$script[] = Indent::_(5) . "\$db->execute();";
			$codeA    = implode(PHP_EOL, $script);
			// fixed message
			$messageA = Indent::_(5)
				. "\$app->enqueueMessage(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('The <b>#__assets</b> table rules column was resized to the "
				. $data_type
				. " datatype for the components possible large permission rules.'));";
			// do nothing
			$codeB = "";
			// fix not needed so ignore
			$messageB = "";

			// done
			return $this->getAssetsTableIntelligentCode(
				$codeIF, $codeA, $codeB, $messageA, $messageB, 2
			);
		}

		return '';
	}

	/**
	 * build code for the assets table script intelligent reversal
	 *
	 * @return  string The php to place in script.php
	 *
	 */
	protected function getAssetsTableIntelligentUninstall()
	{
		// check if we should add the intelligent uninstall treatment for the assets table
		if (CFactory::_('Config')->add_assets_table_fix == 2)
		{
			if (CFactory::_('Config')->get('joomla_version', 3) != 3)
			{
				$script   = [];
				$script[] = Indent::_(2) . "//" . Line::_(__Line__, __Class__)
					. " Revert the assets table rules column back to the default.";
				$script[] = Indent::_(2) . '$this->removeDatabaseAssetsRulesFix();';

				return PHP_EOL . implode(PHP_EOL, $script);
			}
			// the if statement about $rule_length
			$codeIF = "\$rule_length < 5120";
			// reverse column size
			$script   = [];
			$script[] = Indent::_(4) . "//" . Line::_(__Line__, __Class__)
				. " Revert the assets table rules column back to the default";
			$script[] = Indent::_(4)
				. '$revert_rule = "ALTER TABLE `#__assets` CHANGE `rules` `rules` varchar(5120) NOT NULL COMMENT \'JSON encoded access control.\';";';
			$script[] = Indent::_(4) . "\$db->setQuery(\$revert_rule);";
			$script[] = Indent::_(4) . "\$db->execute();";
			$codeA    = implode(PHP_EOL, $script);
			// reverted message
			$messageA = Indent::_(4)
				. "\$app->enqueueMessage(Text::_('COM_COMPONENTBUILDER_REVERTED_THE_B_ASSETSB_TABLE_RULES_COLUMN_BACK_TO_ITS_DEFAULT_SIZE_OF_VARCHARFIVE_THOUSAND_ONE_HUNDRED_AND_TWENTY'));";
			// do nothing
			$codeB = "";
			// not reverted message
			$messageB = Indent::_(4)
				. "\$app->enqueueMessage(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('Could not revert the <b>#__assets</b> table rules column back to its default size of varchar(5120), since there is still one or more components that still requires the column to be larger.'));";

			// done
			return $this->getAssetsTableIntelligentCode(
				$codeIF, $codeA, $codeB, $messageA, $messageB
			);
		}

		return '';
	}

	/**
	 * set code for both install, update and uninstall
	 *
	 * @param   string  $codeIF    The IF code to fix this issue
	 * @param   string  $codeA     The a code to fix this issue
	 * @param   string  $codeB     The b code to fix this issue
	 * @param   string  $messageA  The fix a message
	 * @param   string  $messageB  The fix b message
	 *
	 * @return  string
	 *
	 */
	protected function getAssetsTableIntelligentCode($codeIF, $codeA, $codeB,
	                                                 $messageA, $messageB, $tab = 1
	)
	{
		// reset script
		$script   = [];
		$script[] = Indent::_($tab) . Indent::_(1) . "//" . Line::_(
				__LINE__,__CLASS__
			)
			. " Get the biggest rule column in the assets table at this point.";
		$script[] = Indent::_($tab) . Indent::_(1)
			. '$get_rule_length = "SELECT CHAR_LENGTH(`rules`) as rule_size FROM #__assets ORDER BY rule_size DESC LIMIT 1";';
		$script[] = Indent::_($tab) . Indent::_(1)
			. "\$db->setQuery(\$get_rule_length);";
		$script[] = Indent::_($tab) . Indent::_(1) . "if (\$db->execute())";
		$script[] = Indent::_($tab) . Indent::_(1) . "{";
		$script[] = Indent::_($tab) . Indent::_(2)
			. "\$rule_length = \$db->loadResult();";
		// https://github.com/joomla/joomla-cms/blob/3.10.0-alpha3/installation/sql/mysql/joomla.sql#L22
		// Checked 1st December 2020 (let us know if this changes)
		$script[] = Indent::_($tab) . Indent::_(2) . "//" . Line::_(
				__LINE__,__CLASS__
			)
			. " Check the size of the rules column";
		$script[] = Indent::_($tab) . Indent::_(2) . "if (" . $codeIF . ")";
		$script[] = Indent::_($tab) . Indent::_(2) . "{";
		$script[] = $codeA;
		$script[] = $messageA;
		$script[] = Indent::_($tab) . Indent::_(2) . "}";
		// only ad this if there is a B part
		if (StringHelper::check($codeB)
			|| StringHelper::check($messageB))
		{
			$script[] = Indent::_($tab) . Indent::_(2) . "else";
			$script[] = Indent::_($tab) . Indent::_(2) . "{";
			$script[] = $codeB;
			$script[] = $messageB;
			$script[] = Indent::_($tab) . Indent::_(2) . "}";
		}
		$script[] = Indent::_($tab) . Indent::_(1) . "}";

		// done
		return PHP_EOL . implode(PHP_EOL, $script);
	}

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

	public function getContentType($view, $component)
	{
		// add if history is to be kept or if tags is added
		if (CFactory::_('Compiler.Builder.History')->exists($view)
			|| CFactory::_('Compiler.Builder.Tags')->exists($view))
		{
			// reset array
			$array = [];
			// set needed defaults
			$alias            = CFactory::_('Compiler.Builder.Alias')->get($view, 'null');
			$title            = CFactory::_('Compiler.Builder.Title')->get($view, 'null');
			$category         = CFactory::_('Compiler.Builder.Category.Code')->getString("{$view}.code", 'null');
			$categoryHistory  = (CFactory::_('Compiler.Builder.Category.Code')->exists($view))
				?
				'{"sourceColumn": "' . $category
				. '","targetTable": "#__categories","targetColumn": "id","displayColumn": "title"},'
				: '';
			$Component        = StringHelper::safe(
				$component, 'F'
			);
			$View             = StringHelper::safe($view, 'F');
			$maintext         = CFactory::_('Compiler.Builder.Main.Text.Field')->get($view, 'null');
			$hiddenFields     = CFactory::_('Compiler.Builder.Hidden.Fields')->pathToString($view, '');
			$dynamicfields    = CFactory::_('Compiler.Builder.Dynamic.Fields')->pathToString($view, ',');
			$intFields        = CFactory::_('Compiler.Builder.Integer.Fields')->pathToString($view, '');
			$customfieldlinks = CFactory::_('Compiler.Builder.Custom.Field.Links')->pathToString($view, '');
			// build uninstall script for content types
			$this->uninstallScriptBuilder[$View] = 'com_' . $component . '.' . $view;
			$this->uninstallScriptContent[$view] = $view;
			// check if this view has metadata
			if (CFactory::_('Compiler.Builder.Meta.Data')->isString($view))
			{
				$core_metadata = 'metadata';
				$core_metakey  = 'metakey';
				$core_metadesc = 'metadesc';
			}
			else
			{
				$core_metadata = 'null';
				$core_metakey  = 'null';
				$core_metadesc = 'null';
			}
			// check if view has access
			if (CFactory::_('Compiler.Builder.Access.Switch')->exists($view))
			{
				$core_access = 'access';
				$accessHistory
					= ',{"sourceColumn": "access","targetTable": "#__viewlevels","targetColumn": "id","displayColumn": "title"}';
			}
			else
			{
				$core_access   = 'null';
				$accessHistory = '';
			}
			// set the title
			$array['type_title'] = $Component . ' ' . $View;
			// set the alias
			$array['type_alias'] = 'com_' . $component . '.' . $view;
			if (CFactory::_('Config')->get('joomla_version', 3) == 3)
			{
				// set the table
				$array['table'] = '{"special": {"dbtable": "#__' . $component . '_'
					. $view . '","key": "id","type": "' . $View . '","prefix": "'
					. $component
					. 'Table","config": "array()"},"common": {"dbtable": "#__ucm_content","key": "ucm_id","type": "Corecontent","prefix": "JTable","config": "array()"}}';
			}
			else
			{
				// set the table
				$array['table'] = '{"special": {"dbtable": "#__' . $component . '_'
					. $view . '","key": "id","type": "' . $View . 'Table","prefix": "' . CFactory::_('Config')->namespace_prefix
					. '\\Component\\' . CFactory::_('Compiler.Builder.Content.One')->get('ComponentNamespace')
					. '\\Administrator\\Table"}}';

				// set rules field
				$array['rules'] = '';
			}

			// set field map
			$array['field_mappings']
				= '{"common": {"core_content_item_id": "id","core_title": "'
				. $title . '","core_state": "published","core_alias": "'
				. $alias
				. '","core_created_time": "created","core_modified_time": "modified","core_body": "'
				. $maintext
				. '","core_hits": "hits","core_publish_up": "null","core_publish_down": "null","core_access": "'
				. $core_access
				. '","core_params": "params","core_featured": "null","core_metadata": "'
				. $core_metadata
				. '","core_language": "null","core_images": "null","core_urls": "null","core_version": "version","core_ordering": "ordering","core_metakey": "'
				. $core_metakey . '","core_metadesc": "' . $core_metadesc
				. '","core_catid": "' . $category
				. '","core_xreference": "null","asset_id": "asset_id"},"special": {'
				. $dynamicfields . '}}';

			if (CFactory::_('Config')->get('joomla_version', 3) == 3)
			{
				// set the router class method
				$array['router'] = $Component . 'HelperRoute::get' . $View
					. 'Route';
			}
			else
			{
				// set the router class method
				$array['router'] = '';
			}

			if (CFactory::_('Config')->get('joomla_version', 3) == 3)
			{
				// set content history
				$array['content_history_options']
					= '{"formFile": "administrator/components/com_' . $component
					. '/models/forms/' . $view
					. '.xml","hideFields": ["asset_id","checked_out","checked_out_time","version"'
					. $hiddenFields
					. '],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering","version","hits"'
					. $intFields . '],"displayLookup": [' . $categoryHistory
					. '{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"}'
					. $accessHistory
					. ',{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"}'
					. $customfieldlinks . ']}';
			}
			else
			{
				// set content history
				$array['content_history_options']
					= '{"formFile": "administrator/components/com_' . $component
					. '/forms/' . $view
					. '.xml","hideFields": ["asset_id","checked_out","checked_out_time"'
					. $hiddenFields
					. '],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering","version","hits"'
					. $intFields . '],"displayLookup": [' . $categoryHistory
					. '{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"}'
					. $accessHistory
					. ',{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"}'
					. $customfieldlinks . ']}';
			}

			return $array;
		}

		return false;
	}

	public function getCategoryContentType($view, $views, $component)
	{
		// get the other view
		$otherView = CFactory::_('Compiler.Builder.Category.Code')->getString("{$view}.view", 'error');
		$category  = CFactory::_('Compiler.Builder.Category.Code')->getString("{$view}.code", 'error');
		$Component = StringHelper::safe($component, 'F');
		$View      = StringHelper::safe($view, 'F');
		// build uninstall script for content types
		$this->uninstallScriptBuilder[$View . ' ' . $category] = 'com_'
			. $component . '.' . $otherView . '.category';
		$this->uninstallScriptContent[$View . ' ' . $category] = $View . ' '
			. $category;
		// set the title
		$array['type_title'] = $Component . ' ' . $View . ' '
			. StringHelper::safe($category, 'F');
		// set the alias
		$array['type_alias'] = 'com_' . $component . '.' . $otherView
			. '.category';
		// set the table
		$array['table']
			= '{"special":{"dbtable":"#__categories","key":"id","type":"Category","prefix":"JTable","config":"array()"},"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"JTable","config":"array()"}}';
		if (CFactory::_('Config')->get('joomla_version', 3) != 3)
		{
			// set rules field
			$array['rules'] = '';
		}
		// set field map
		$array['field_mappings']
			= '{"common":{"core_content_item_id":"id","core_title":"title","core_state":"published","core_alias":"alias","core_created_time":"created_time","core_modified_time":"modified_time","core_body":"description", "core_hits":"hits","core_publish_up":"null","core_publish_down":"null","core_access":"access", "core_params":"params", "core_featured":"null", "core_metadata":"metadata", "core_language":"language", "core_images":"null", "core_urls":"null", "core_version":"version", "core_ordering":"null", "core_metakey":"metakey", "core_metadesc":"metadesc", "core_catid":"parent_id", "core_xreference":"null", "asset_id":"asset_id"}, "special":{"parent_id":"parent_id","lft":"lft","rgt":"rgt","level":"level","path":"path","extension":"extension","note":"note"}}';

		if (CFactory::_('Config')->get('joomla_version', 3) == 3)
		{
			// set the router class method
			$array['router'] = $Component . 'HelperRoute::getCategoryRoute';
			// set content history
			$array['content_history_options']
				= '{"formFile":"administrator\/components\/com_categories\/models\/forms\/category.xml", "hideFields":["asset_id","checked_out","checked_out_time","version","lft","rgt","level","path","extension"], "ignoreChanges":["modified_user_id", "modified_time", "checked_out", "checked_out_time", "version", "hits", "path"],"convertToInt":["publish_up", "publish_down"], "displayLookup":[{"sourceColumn":"created_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"modified_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"parent_id","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}]}';
		}
		else
		{
			// set the router class method
			$array['router'] = '';
			// set content history
			$array['content_history_options']
				= '{"formFile":"administrator\/components\/com_categories\/forms\/category.xml", "hideFields":["asset_id","checked_out","checked_out_time","version","lft","rgt","level","path","extension"], "ignoreChanges":["modified_user_id", "modified_time", "checked_out", "checked_out_time", "version", "hits", "path"],"convertToInt":["publish_up", "publish_down"], "displayLookup":[{"sourceColumn":"created_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"modified_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"parent_id","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}]}';
		}

		return $array;
	}

	public function setRouterHelp($nameSingleCode, $nameListCode, $front = false)
	{
		// add if tags is added, also for all front item views
		if ((CFactory::_('Compiler.Builder.Tags')->exists($nameSingleCode) || $front)
			&& (!in_array($nameSingleCode, $this->setRouterHelpDone)))
		{
			// insure we load a view only once
			$this->setRouterHelpDone[] = $nameSingleCode;
			// build view route helper
			$View = StringHelper::safe(
				$nameSingleCode, 'F'
			);

			$hasCategory = (CFactory::_('Compiler.Builder.Category.Code')->exists($nameSingleCode) &&
				'category' !== $nameSingleCode && 'categories' !== $nameSingleCode);

			$routeHelper   = [];
			$routeHelper[] = PHP_EOL . PHP_EOL . Indent::_(1) . "/**";
			$routeHelper[] = Indent::_(1) . " * Get the URL route for {$nameSingleCode}";
			$routeHelper[] = Indent::_(1) . " *";
			$routeHelper[] = Indent::_(1) . " * @param   integer  \$id     The id of the {$nameSingleCode}";

			if ($hasCategory)
			{
				$routeHelper[] = Indent::_(1) . " * @param   integer  \$catid  The id of the {$nameSingleCode}'s category";
				$routeHelper[] = Indent::_(1) . " *";
				$routeHelper[] = Indent::_(1) . " * @return  string  The link to the {$nameSingleCode}";
				$routeHelper[] = Indent::_(1) . " *";
				$routeHelper[] = Indent::_(1) . " * @since   1.5";
				$routeHelper[] = Indent::_(1) . " */";
				$routeHelper[] = Indent::_(1) . "public static function get" . $View . "Route(\$id = 0, \$catid = 0): string";
			}
			else
			{
				$routeHelper[] = Indent::_(1) . " *";
				$routeHelper[] = Indent::_(1) . " * @return  string  The link to the {$nameSingleCode}";
				$routeHelper[] = Indent::_(1) . " *";
				$routeHelper[] = Indent::_(1) . " * @since   1.5";
				$routeHelper[] = Indent::_(1) . " */";
				$routeHelper[] = Indent::_(1) . "public static function get" . $View . "Route(\$id = 0): string";
			}

			$routeHelper[] = Indent::_(1) . "{";
			$routeHelper[] = Indent::_(2) . "if (\$id > 0)";
			$routeHelper[] = Indent::_(2) . "{";

			if (CFactory::_('Config')->get('joomla_version', 3) == 3)
			{
				$routeHelper[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
					. " Initialize the needel array.";
				$routeHelper[] = Indent::_(3) . "\$needles = array(";
				$routeHelper[] = Indent::_(4) . "'" . $nameSingleCode
					. "'  => array((int) \$id)";
				$routeHelper[] = Indent::_(3) . ");";
			}

			$routeHelper[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " Create the link";
			$routeHelper[] = Indent::_(3) . "\$link = 'index.php?option=com_"
				. CFactory::_('Config')->component_code_name . "&view=" . $nameSingleCode
				. "&id='. \$id;";
			$routeHelper[] = Indent::_(2) . "}";
			$routeHelper[] = Indent::_(2) . "else";
			$routeHelper[] = Indent::_(2) . "{";

			if (CFactory::_('Config')->get('joomla_version', 3) == 3)
			{
				$routeHelper[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
					. " Initialize the needel array.";
				$routeHelper[] = Indent::_(3) . "\$needles = array(";
				$routeHelper[] = Indent::_(4) . "'" . $nameSingleCode
					. "'  => array()";
				$routeHelper[] = Indent::_(3) . ");";
			}

			$routeHelper[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " Create the link but don't add the id.";
			$routeHelper[] = Indent::_(3) . "\$link = 'index.php?option=com_"
				. CFactory::_('Config')->component_code_name . "&view=" . $nameSingleCode . "';";
			$routeHelper[] = Indent::_(2) . "}";

			if ($hasCategory)
			{
				$routeHelper[] = Indent::_(2) . "if (\$catid > 1)";
				$routeHelper[] = Indent::_(2) . "{";

				if (CFactory::_('Config')->get('joomla_version', 3) == 3)
				{
					$routeHelper[] = Indent::_(3)
						. "\$categories = Categories::getInstance('"
						. CFactory::_('Config')->component_code_name . "." . $nameListCode . "');";
					$routeHelper[] = Indent::_(3)
						. "\$category = \$categories->get(\$catid);";
					$routeHelper[] = Indent::_(3) . "if (\$category)";
					$routeHelper[] = Indent::_(3) . "{";
					$routeHelper[] = Indent::_(4)
						. "\$needles['category'] = array_reverse(\$category->getPath());";
					$routeHelper[] = Indent::_(4)
						. "\$needles['categories'] = \$needles['category'];";
					$routeHelper[] = Indent::_(4) . "\$link .= '&catid='.\$catid;";
					$routeHelper[] = Indent::_(3) . "}";
				}
				else
				{
					$routeHelper[] = Indent::_(3) . "\$link .= '&catid='.\$catid;";
				}

				$routeHelper[] = Indent::_(2) . "}";
			}

			if (CFactory::_('Compiler.Builder.Has.Menu.Global')->exists($nameSingleCode))
			{
				if (CFactory::_('Config')->get('joomla_version', 3) == 3)
				{
					$routeHelper[] = PHP_EOL . Indent::_(2)
						. "if (\$item = self::_findItem(\$needles, '" . $nameSingleCode . "'))";
				}
				else
				{
					$routeHelper[] = PHP_EOL . Indent::_(2)
						. "if ((\$item = self::_findItem('" . $nameSingleCode . "')) !== null)";
				}
				$routeHelper[] = Indent::_(2) . "{";
				$routeHelper[] = Indent::_(3) . "\$link .= '&Itemid='.\$item;";
				$routeHelper[] = Indent::_(2) . "}";
			}
			elseif (CFactory::_('Config')->get('joomla_version', 3) == 3)
			{
				$routeHelper[] = PHP_EOL . Indent::_(2)
					. "if (\$item = self::_findItem(\$needles))";
				$routeHelper[] = Indent::_(2) . "{";
				$routeHelper[] = Indent::_(3) . "\$link .= '&Itemid='.\$item;";
				$routeHelper[] = Indent::_(2) . "}";
			}

			$routeHelper[] = PHP_EOL . Indent::_(2) . "return \$link;";
			$routeHelper[] = Indent::_(1) . "}";

			return implode(PHP_EOL, $routeHelper);
		}

		return '';
	}

	public function routerParseSwitch(&$view, $viewArray = null,
	                                  $aliasView = true, $idView = true
	)
	{
		// reset buckets
		$routerSwitch = [];
		$isCategory   = '';
		$viewTable    = false;
		if ($viewArray && ArrayHelper::check($viewArray)
			&& isset($viewArray['settings'])
			&& isset($viewArray['settings']->main_get))
		{
			// check if we have custom script for this router parse switch case
			if (isset($viewArray['settings']->main_get->add_php_router_parse)
				&& $viewArray['settings']->main_get->add_php_router_parse == 1
				&& isset($viewArray['settings']->main_get->php_router_parse)
				&& StringHelper::check(
					$viewArray['settings']->main_get->php_router_parse
				))
			{
				// load the custom script for the switch based on dynamic get
				$routerSwitch[] = PHP_EOL . Indent::_(3) . "case '" . $view
					. "':";
				$routerSwitch[] = CFactory::_('Placeholder')->update_(
					$viewArray['settings']->main_get->php_router_parse
				);
				$routerSwitch[] = Indent::_(4) . "break;";

				return implode(PHP_EOL, $routerSwitch);
			}
			// is this a catogory
			elseif (isset($viewArray['settings']->main_get->db_table_main)
				&& $viewArray['settings']->main_get->db_table_main
				=== 'categories')
			{
				$isCategory = ', true'; // TODO we will keep an eye on this....
			}
			// get the main table name
			elseif (isset($viewArray['settings']->main_get->main_get)
				&& ArrayHelper::check(
					$viewArray['settings']->main_get->main_get
				))
			{
				foreach ($viewArray['settings']->main_get->main_get as $get)
				{
					if (isset($get['as']) && $get['as'] === 'a')
					{
						if (isset($get['selection'])
							&& ArrayHelper::check(
								$get['selection']
							)
							&& isset($get['selection']['select_gets'])
							&& ArrayHelper::check(
								$get['selection']['select_gets']
							))
						{
							if (isset($get['selection']['table']))
							{
								$viewTable = str_replace(
									'#__' . CFactory::_('Config')->component_code_name . '_', '',
									(string) $get['selection']['table']
								);
							}
						}
						break;
					}
				}
			}
		}
		// add if tags is added, also for all front item views
		if ($aliasView)
		{
			$routerSwitch[] = PHP_EOL . Indent::_(3) . "case '" . $view . "':";
			$routerSwitch[] = Indent::_(4) . "\$vars['view'] = '" . $view
				. "';";
			$routerSwitch[] = Indent::_(4)
				. "if (is_numeric(\$segments[\$count-1]))";
			$routerSwitch[] = Indent::_(4) . "{";
			$routerSwitch[] = Indent::_(5)
				. "\$vars['id'] = (int) \$segments[\$count-1];";
			$routerSwitch[] = Indent::_(4) . "}";
			$routerSwitch[] = Indent::_(4) . "elseif (\$segments[\$count-1])";
			$routerSwitch[] = Indent::_(4) . "{";
			// we need to get from the table of this views main get the alias so we need the table name
			if ($viewTable)
			{
				$routerSwitch[] = Indent::_(5) . "\$id = \$this->getVar('"
					. $viewTable . "', \$segments[\$count-1], 'alias', 'id'"
					. $isCategory . ");";
			}
			else
			{
				$routerSwitch[] = Indent::_(5) . "\$id = \$this->getVar('"
					. $view . "', \$segments[\$count-1], 'alias', 'id'"
					. $isCategory . ");";
			}
			$routerSwitch[] = Indent::_(5) . "if(\$id)";
			$routerSwitch[] = Indent::_(5) . "{";
			$routerSwitch[] = Indent::_(6) . "\$vars['id'] = \$id;";
			$routerSwitch[] = Indent::_(5) . "}";
			$routerSwitch[] = Indent::_(4) . "}";
			$routerSwitch[] = Indent::_(4) . "break;";
		}
		elseif ($idView)
		{
			$routerSwitch[] = PHP_EOL . Indent::_(3) . "case '" . $view . "':";
			$routerSwitch[] = Indent::_(4) . "\$vars['view'] = '" . $view
				. "';";
			$routerSwitch[] = Indent::_(4)
				. "if (is_numeric(\$segments[\$count-1]))";
			$routerSwitch[] = Indent::_(4) . "{";
			$routerSwitch[] = Indent::_(5)
				. "\$vars['id'] = (int) \$segments[\$count-1];";
			$routerSwitch[] = Indent::_(4) . "}";
			$routerSwitch[] = Indent::_(4) . "break;";
		}
		else
		{
			$routerSwitch[] = PHP_EOL . Indent::_(3) . "case '" . $view . "':";
			$routerSwitch[] = Indent::_(4) . "\$vars['view'] = '" . $view
				. "';";
			$routerSwitch[] = Indent::_(4) . "break;";
		}

		return implode(PHP_EOL, $routerSwitch);
	}

	public function routerBuildViews(&$view)
	{
		if (CFactory::_('Compiler.Builder.Content.One')->exists('ROUTER_BUILD_VIEWS')
			&& StringHelper::check(
				CFactory::_('Compiler.Builder.Content.One')->get('ROUTER_BUILD_VIEWS')
			))
		{
			return " || \$view === '" . $view . "'";
		}
		else
		{
			return "\$view === '" . $view . "'";
		}
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

	public function setInstall()
	{
		if (($database_tables = CFactory::_('Compiler.Builder.Database.Tables')->allActive()) !== [])
		{
			// set the main db prefix
			$component = CFactory::_('Config')->component_code_name;
			// start building the db
			$db = '';
			if (CFactory::_('Config')->get('joomla_version', 3) != 3)
			{
				$db .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL;
				$db .= 'SET time_zone = "+00:00";' . PHP_EOL . PHP_EOL;;
			}

			foreach ($database_tables as $view => $fields)
			{
				// cast the object to an array TODO we must update all to use the object
				$fields = (array) $fields;
				// build the uninstallation array
				CFactory::_('Compiler.Builder.Database.Uninstall')->add('table', "DROP TABLE IF EXISTS `#__"
					. $component . "_" . $view . "`;");

				// setup the table DB string
				$db_ = '';
				$db_ .= "CREATE TABLE IF NOT EXISTS `#__" . $component . "_"
					. $view . "` (";
				// check if the table name has changed
				if (($old_table_name = CFactory::_('Registry')->
					get('builder.update_sql.table_name.' . $view . '.old', null)) !== null)
				{
					$key_ = "RENAMETABLE`#__" . $component . "_" . $old_table_name . "`";
					$value_ = "RENAME TABLE `#__" . $component . "_" . $old_table_name . "` to `#__"
						. $component . "_" . $view . "`;";

					CFactory::_('Compiler.Builder.Update.Mysql')->set($key_, $value_);
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.id'))
				{
					$db_ .= PHP_EOL . Indent::_(1)
						. "`id` INT(11) NOT NULL AUTO_INCREMENT,";
				}
				$db_ .= PHP_EOL . Indent::_(1)
					. "`asset_id` INT(10) unsigned NULL DEFAULT 0 COMMENT 'FK to the #__assets table.',";
				ksort($fields);
				$last_name = 'asset_id';
				foreach ($fields as $field => $data)
				{
					// cast the object to an array TODO we must update all to use the object
					$data = (array) $data;
					// set default
					$default = $data['default'];
					if ($default === 'Other')
					{
						$default = $data['other'];
					}
					// to get just null value add EMPTY to other value.
					if ($default === 'EMPTY')
					{
						$default = $data['null_switch'];
					}
					elseif ($default === 'DATETIME'
						|| $default === 'CURRENT_TIMESTAMP')
					{
						$default = $data['null_switch'] . ' DEFAULT '
							. $default;
					}
					elseif (is_numeric($default))
					{
						$default = $data['null_switch'] . " DEFAULT "
							. $default;
					}
					else
					{
						$default = $data['null_switch'] . " DEFAULT '"
							. $default . "'";
					}

					// set the length (lenght) <-- TYPO :: LVDM :: DON'T TOUCH
					$length = '';
					if (isset($data['lenght']) && $data['lenght'] === 'Other'
						&& isset($data['lenght_other'])
						&& $data['lenght_other'] > 0)
					{
						$length = '(' . $data['lenght_other'] . ')';
					}
					elseif (isset($data['lenght']) && $data['lenght'] > 0)
					{
						$length = '(' . $data['lenght'] . ')';
					}
					// set the field to db
					$db_ .= PHP_EOL . Indent::_(1) . "`" . $field . "` "
						. $data['type'] . $length . " " . $default . ",";
					// check if this a new field that should be added via SQL update
					if (CFactory::_('Registry')->
						get('builder.add_sql.field.' . $view . '.' . $data['GUID'], null))
					{
						// to soon....
						// $key_ = "ALTERTABLE`#__" . $component . "_" . $view . "`ADDCOLUMNIFNOTEXISTS`" . $field . "`";
						// $value_ = "ALTER TABLE `#__" . $component . "_" . $view . "` ADD COLUMN IF NOT EXISTS `" . $field . "` " . $data['type']
						//	. length . " " . $default . " AFTER `" . $last_name . "`;";
						$key_ = "ALTERTABLE`#__" . $component . "_" . $view . "`ADD`" . $field . "`";
						$value_ = "ALTER TABLE `#__" . $component . "_" . $view . "` ADD `" . $field . "` " . $data['type']
							. $length . " " . $default . " AFTER `" . $last_name . "`;";

						CFactory::_('Compiler.Builder.Update.Mysql')->set($key_, $value_);
					}
					// check if the field has changed name and/or data type and lenght
					elseif (CFactory::_('Registry')->
						get('builder.update_sql.field.datatype.' . $view . '.' . $field, null)
						|| CFactory::_('Registry')->
						get('builder.update_sql.field.lenght.' . $view . '.' . $field, null)
						|| CFactory::_('Registry')->
						get('builder.update_sql.field.name.' . $view . '.' . $field, null))
					{
						// if the name changed
						if (($oldName = CFactory::_('Registry')->
							get('builder.update_sql.field.name.' . $view . '.' . $field . '.old', null)) === null)
						{
							$oldName = $field;
						}

						// now set the update SQL
						$key_ = "ALTERTABLE`#__" . $component . "_" . $view . "`CHANGE`" . $oldName . "``"
							. $field . "`";
						$value_ = "ALTER TABLE `#__" . $component . "_" . $view . "` CHANGE `" . $oldName . "` `"
							. $field . "` " . $data['type'] . $length . " " . $default . ";";

						CFactory::_('Compiler.Builder.Update.Mysql')->set($key_, $value_);
					}
					// be sure to track the last name used :)
					$last_name = $field;
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.params'))
				{
					$db_ .= PHP_EOL . Indent::_(1) . "`params` TEXT NULL,";
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.published'))
				{
					$db_ .= PHP_EOL . Indent::_(1)
						. "`published` TINYINT(3) NULL DEFAULT 1,";
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.created_by'))
				{
					if (CFactory::_('Config')->get('joomla_version', 3) == 3)
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`created_by` INT(10) unsigned NULL DEFAULT 0,";
					}
					else
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`created_by` INT unsigned NULL,";
					}
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.modified_by'))
				{
					if (CFactory::_('Config')->get('joomla_version', 3) == 3)
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`modified_by` INT(10) unsigned NULL DEFAULT 0,";
					}
					else
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`modified_by` INT unsigned,";
					}
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.created'))
				{
					if (CFactory::_('Config')->get('joomla_version', 3) == 3)
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`created` DATETIME NULL DEFAULT '0000-00-00 00:00:00',";
					}
					else
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`created` DATETIME DEFAULT CURRENT_TIMESTAMP,";
					}
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.modified'))
				{
					if (CFactory::_('Config')->get('joomla_version', 3) == 3)
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`modified` DATETIME NULL DEFAULT '0000-00-00 00:00:00',";
					}
					else
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`modified` DATETIME,";
					}
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.checked_out'))
				{
					if (CFactory::_('Config')->get('joomla_version', 3) == 3)
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`checked_out` int(11) unsigned NULL DEFAULT 0,";
					}
					else
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`checked_out` int unsigned,";
					}
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.checked_out_time'))
				{
					if (CFactory::_('Config')->get('joomla_version', 3) == 3)
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`checked_out_time` DATETIME NULL DEFAULT '0000-00-00 00:00:00',";
					}
					else
					{
						$db_ .= PHP_EOL . Indent::_(1)
							. "`checked_out_time` DATETIME,";
					}
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.version'))
				{
					$db_ .= PHP_EOL . Indent::_(1)
						. "`version` INT(10) unsigned NULL DEFAULT 1,";
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.hits'))
				{
					$db_ .= PHP_EOL . Indent::_(1)
						. "`hits` INT(10) unsigned NULL DEFAULT 0,";
				}
				// check if view has access
				if (CFactory::_('Compiler.Builder.Access.Switch')->exists($view)
					&& !CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.access'))
				{
					$db_ .= PHP_EOL . Indent::_(1)
						. "`access` INT(10) unsigned NULL DEFAULT 0,";
						// add to component dynamic fields
						CFactory::_('Compiler.Builder.Component.Fields')->set($view . '.access',
							[
								'name' => 'access',
								'label' => 'Access',
								'type' => 'accesslevel',
								'title' => false,
								'store' => NULL,
								'tab_name' => NULL,
								'db' => [
									'type' => 'INT(10) unsigned',
									'default' => '0',
									'key' => true,
									'null_switch' => 'NULL'
								]
							]
						);
				}
				// check if default field was overwritten
				if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.ordering'))
				{
					$db_ .= PHP_EOL . Indent::_(1)
						. "`ordering` INT(11) NULL DEFAULT 0,";
				}
				// check if metadata is added to this view
				if (CFactory::_('Compiler.Builder.Meta.Data')->isString($view))
				{
					// check if default field was overwritten
					if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.metakey'))
					{
						if (CFactory::_('Config')->get('joomla_version', 3) == 3)
						{
							$db_ .= PHP_EOL . Indent::_(1)
								. "`metakey` TEXT NULL,";
						}
						else
						{
							$db_ .= PHP_EOL . Indent::_(1)
								. "`metakey` TEXT,";
						}
					}
					// check if default field was overwritten
					if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.metadesc'))
					{
						if (CFactory::_('Config')->get('joomla_version', 3) == 3)
						{
							$db_ .= PHP_EOL . Indent::_(1)
								. "`metadesc` TEXT NULL,";
						}
						else
						{
							$db_ .= PHP_EOL . Indent::_(1)
								. "`metadesc` TEXT,";
						}
					}
					// check if default field was overwritten
					if (!CFactory::_('Compiler.Builder.Field.Names')->isString($view . '.metadata'))
					{
						if (CFactory::_('Config')->get('joomla_version', 3) == 3)
						{
							$db_ .= PHP_EOL . Indent::_(1)
								. "`metadata` TEXT NULL,";
						}
						else
						{
							$db_ .= PHP_EOL . Indent::_(1)
								. "`metadata` TEXT,";
						}
					}
					// add to component dynamic fields
					CFactory::_('Compiler.Builder.Component.Fields')->set($view . '.metakey',
						[
							'name' => 'metakey',
							'label' => 'Meta Keywords',
							'type' => 'textarea',
							'title' => false,
							'store' => NULL,
							'tab_name' => 'publishing',
							'db' => [
								'type' => 'TEXT'
							]
						]
					);
					CFactory::_('Compiler.Builder.Component.Fields')->set($view . '.metadesc',
						[
							'name' => 'metadesc',
							'label' => 'Meta Description',
							'type' => 'textarea',
							'title' => false,
							'store' => NULL,
							'tab_name' => 'publishing',
							'db' => [
								'type' => 'TEXT'
							]
						]
					);
					CFactory::_('Compiler.Builder.Component.Fields')->set($view . '.metadata',
						[
							'name' => 'metadata',
							'label' => 'Meta Data',
							'type' => NULL,
							'title' => false,
							'store' => 'json',
							'tab_name' => 'publishing',
							'db' => [
								'type' => 'TEXT'
							]
						]
					);
				}
				// TODO (we may want this to be dynamicly set)
				$db_ .= PHP_EOL . Indent::_(1) . "PRIMARY KEY  (`id`)";
				// check if a key was set for any of the default fields then we should not set it again
				$check_keys_set = [];
				if (CFactory::_('Compiler.Builder.Database.Unique.Keys')->exists($view))
				{
					foreach (CFactory::_('Compiler.Builder.Database.Unique.Keys')->get($view) as $nr => $key)
					{
						$db_ .= "," . PHP_EOL . Indent::_(1)
							. "UNIQUE KEY `idx_" . $key . "` (`" . $key . "`)";
						$check_keys_set[$key] = $key;
					}
				}
				if (CFactory::_('Compiler.Builder.Database.Keys')->exists($view))
				{
					foreach (CFactory::_('Compiler.Builder.Database.Keys')->get($view) as $nr => $key)
					{
						$db_ .= "," . PHP_EOL . Indent::_(1)
							. "KEY `idx_" . $key . "` (`" . $key . "`)";
						$check_keys_set[$key] = $key;
					}
				}
				// check if view has access
				if (!isset($check_keys_set['access'])
					&& CFactory::_('Compiler.Builder.Access.Switch')->exists($view))
				{
					$db_ .= "," . PHP_EOL . Indent::_(1)
						. "KEY `idx_access` (`access`)";
				}
				// check if default field was overwritten
				if (!isset($check_keys_set['checked_out']))
				{
					$db_ .= "," . PHP_EOL . Indent::_(1)
						. "KEY `idx_checkout` (`checked_out`)";
				}
				// check if default field was overwritten
				if (!isset($check_keys_set['created_by']))
				{
					$db_ .= "," . PHP_EOL . Indent::_(1)
						. "KEY `idx_createdby` (`created_by`)";
				}
				// check if default field was overwritten
				if (!isset($check_keys_set['modified_by']))
				{
					$db_ .= "," . PHP_EOL . Indent::_(1)
						. "KEY `idx_modifiedby` (`modified_by`)";
				}
				// check if default field was overwritten
				if (!isset($check_keys_set['published']))
				{
					$db_ .= "," . PHP_EOL . Indent::_(1)
						. "KEY `idx_state` (`published`)";
				}
				// easy bucket
				$easy = [];
				// get the mysql table settings
				foreach (
					CFactory::_('Config')->mysql_table_keys as $_mysqlTableKey => $_mysqlTableVal
				)
				{
					if (($easy[$_mysqlTableKey] = CFactory::_('Compiler.Builder.Mysql.Table.Setting')->
						get($view . '.' . $_mysqlTableKey)) === null)
					{
						$easy[$_mysqlTableKey]
							= CFactory::_('Config')->mysql_table_keys[$_mysqlTableKey]['default'];
					}
				}
				// add a little fix for the row_format
				if (StringHelper::check($easy['row_format']))
				{
					$easy['row_format'] = ' ROW_FORMAT=' . $easy['row_format'];
				}
				// now build db string
				$db_ .= PHP_EOL . ") ENGINE=" . $easy['engine']
					. " AUTO_INCREMENT=0 DEFAULT CHARSET=" . $easy['charset']
					. " DEFAULT COLLATE=" . $easy['collate']
					. $easy['row_format'] . ";";

				// check if this is a new table that should be added via update SQL
				if (CFactory::_('Registry')->
					get('builder.add_sql.adminview.' . $view, null))
				{
					// build the update array
					$key_ = "CREATETABLEIFNOTEXISTS`#__" . $component . "_" . $view . "`";
					CFactory::_('Compiler.Builder.Update.Mysql')->set($key_, $db_);
				}
				// check if the table row_format has changed
				if (StringHelper::check($easy['row_format'])
					&& CFactory::_('Registry')->
					get('builder.update_sql.table_row_format.' . $view, null))
				{
					// build the update array
					$key_ = "ALTERTABLE`#__" . $component . "_" . $view . "`" . trim((string) $easy['row_format']);
					$value_ = "ALTER TABLE `#__" . $component . "_" . $view . "`" . $easy['row_format'] . ";";
					CFactory::_('Compiler.Builder.Update.Mysql')->set($key_, $value_);
				}
				// check if the table engine has changed
				if (CFactory::_('Registry')->
					get('builder.update_sql.table_engine.' . $view, null))
				{
					// build the update array
					$key_ = "ALTERTABLE`#__" . $component . "_" . $view . "`ENGINE=" . $easy['engine'];
					$value_ = "ALTER TABLE `#__" . $component . "_" . $view . "` ENGINE = " . $easy['engine'] . ";";
					CFactory::_('Compiler.Builder.Update.Mysql')->set($key_, $value_);
				}
				// check if the table charset OR collation has changed (must be updated together)
				if (CFactory::_('Registry')->
					get('builder.update_sql.table_charset.' . $view, null)
					|| CFactory::_('Registry')->
					get('builder.update_sql.table_collate.' . $view, null))
				{
					// build the update array
					$key_ = "ALTERTABLE`#__" . $component . "_" . $view . "CONVERTTOCHARACTERSET"
						. $easy['charset'] . "COLLATE" . $easy['collate'];
					$value_ = "ALTER TABLE `#__" . $component . "_" . $view . "` CONVERT TO CHARACTER SET "
						. $easy['charset'] . " COLLATE " . $easy['collate'] . ";";

					CFactory::_('Compiler.Builder.Update.Mysql')->set($key_, $value_);
				}

				// add to main DB string
				$db .= $db_ . PHP_EOL . PHP_EOL;
			}

			// add custom sql dump to the file
			if (isset(CFactory::_('Customcode.Dispenser')->hub['sql'])
				&& ArrayHelper::check(
					CFactory::_('Customcode.Dispenser')->hub['sql']
				))
			{
				foreach (CFactory::_('Customcode.Dispenser')->hub['sql'] as $for => $customSql)
				{
					$placeholders = [
						Placefix::_('component') => $component,
						Placefix::_('view') => $for
					]; // dont change this just use ###view### or componentbuilder (took you a while to get here right :)

					$db .= CFactory::_('Placeholder')->update(
						$customSql, $placeholders
					) . PHP_EOL . PHP_EOL;
				}

				unset(CFactory::_('Customcode.Dispenser')->hub['sql']);
			}

			// WHY DO WE NEED AN ASSET TABLE FIX?
			// https://www.mysqltutorial.org/mysql-varchar/
			// https://stackoverflow.com/a/15227917/1429677
			// https://forums.mysql.com/read.php?24,105964,105964
			// https://github.com/vdm-io/Joomla-Component-Builder/issues/616#issuecomment-741502980
			// 30 actions each +-20 characters with 8 groups
			// that makes 4800 characters and the current Joomla
			// column size is varchar(5120)

			// just a little event tracking in classes
			// count actions = setAccessSections
			//                 around line206 (infusion call)
			//                 around line26454 (interpretation function)
			// first fix = setInstall
			//                 around line1600 (infusion call)
			//                 around line10063 (interpretation function)
			// second fix = setUninstallScript
			//                 around line2161 (infusion call)
			//                 around line8030 (interpretation function)

			// check if this component needs larger rules
			// also check if the developer will allow this
			// the access actions length must be checked before this
			// only add this option if set to SQL fix
			if (CFactory::_('Config')->add_assets_table_fix == 1)
			{
				// 400 actions worse case is larger the 65535 characters
				if (CFactory::_('Utilities.Counter')->accessSize > 400)
				{
					$db .= PHP_EOL;
					$db .= PHP_EOL . '--';
					$db .= PHP_EOL
						. '--' . Line::_(
							__LINE__,__CLASS__
						)
						. ' Always insure this column rules is large enough for all the access control values.';
					$db .= PHP_EOL . '--';
					$db .= PHP_EOL
						. "ALTER TABLE `#__assets` CHANGE `rules` `rules` MEDIUMTEXT NOT NULL COMMENT 'JSON encoded access control. Enlarged to MEDIUMTEXT by JCB';";
				}
				// smaller then 400 makes TEXT large enough
				elseif (CFactory::_('Config')->add_assets_table_fix == 1)
				{
					$db .= PHP_EOL;
					$db .= PHP_EOL . '--';
					$db .= PHP_EOL
						. '--' . Line::_(
							__LINE__,__CLASS__
						)
						. ' Always insure this column rules is large enough for all the access control values.';
					$db .= PHP_EOL . '--';
					$db .= PHP_EOL
						. "ALTER TABLE `#__assets` CHANGE `rules` `rules` TEXT NOT NULL COMMENT 'JSON encoded access control. Enlarged to TEXT by JCB';";
				}
			}

			// check if this component needs larger names
			// also check if the developer will allow this
			// the config length must be checked before this
			// only add this option if set to SQL fix
			if (CFactory::_('Config')->add_assets_table_fix && CFactory::_('Config')->add_assets_table_name_fix)
			{
				$db .= PHP_EOL;
				$db .= PHP_EOL . '--';
				$db .= PHP_EOL
					. '--' . Line::_(
						__LINE__,__CLASS__
					)
					. ' Always insure this column name is large enough for long component and view names.';
				$db .= PHP_EOL . '--';
				$db .= PHP_EOL
					. "ALTER TABLE `#__assets` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The unique name for the asset.';";
			}

			return $db;
		}

		return '';
	}

	public function setUninstall()
	{
		$db = '';
		if (CFactory::_('Compiler.Builder.Database.Uninstall')->isArray('table'))
		{
			$db .= implode(PHP_EOL, CFactory::_('Compiler.Builder.Database.Uninstall')->get('table')) . PHP_EOL;
		}
		// add custom sql uninstall dump to the file
		if (isset(CFactory::_('Customcode.Dispenser')->hub['sql_uninstall'])
			&& StringHelper::check(
				CFactory::_('Customcode.Dispenser')->hub['sql_uninstall']
			))
		{
			$db .= CFactory::_('Placeholder')->update_(
					CFactory::_('Customcode.Dispenser')->hub['sql_uninstall']
				) . PHP_EOL;
			unset(CFactory::_('Customcode.Dispenser')->hub['sql_uninstall']);
		}

		// check if this component used larger rules
		// now revert them back on uninstall
		// only add this option if set to SQL fix
		if (CFactory::_('Config')->add_assets_table_fix == 1)
		{
			// https://github.com/joomla/joomla-cms/blob/3.10.0-alpha3/installation/sql/mysql/joomla.sql#L22
			// Checked 1st December 2020 (let us know if this changes)
			$db .= PHP_EOL;
			$db .= PHP_EOL . '--';
			$db .= PHP_EOL
				. '--' . Line::_(
					__LINE__,__CLASS__
				)
				. ' Always insure this column rules is reversed to Joomla defaults on uninstall. (as on 1st Dec 2020)';
			$db .= PHP_EOL . '--';
			$db .= PHP_EOL
				. "ALTER TABLE `#__assets` CHANGE `rules` `rules` varchar(5120) NOT NULL COMMENT 'JSON encoded access control.';";
		}

		// check if this component used larger names
		// now revert them back on uninstall
		// only add this option if set to SQL fix
		if (CFactory::_('Config')->add_assets_table_fix == 1 && CFactory::_('Config')->add_assets_table_name_fix)
		{
			// https://github.com/joomla/joomla-cms/blob/3.10.0-alpha3/installation/sql/mysql/joomla.sql#L20
			// Checked 1st December 2020 (let us know if this changes)
			$db .= PHP_EOL;
			$db .= PHP_EOL . '--';
			$db .= PHP_EOL
				. '--' . Line::_(
					__LINE__,__CLASS__
				)
				. ' Always insure this column name is reversed to Joomla defaults on uninstall. (as on 1st Dec 2020).';
			$db .= PHP_EOL . '--';
			$db .= PHP_EOL
				. "ALTER TABLE `#__assets` CHANGE `name` `name` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The unique name for the asset.';";
		}

		return $db;
	}

	public function setLangAdmin(string $componentName): bool
	{
		// Trigger Event: jcb_ce_onBeforeBuildAdminLang
		CFactory::_('Event')->trigger(
			'jcb_ce_onBeforeBuildAdminLang'
		);

		// start loading the defaults
		CFactory::_('Language')->set('adminsys', CFactory::_('Config')->lang_prefix, $componentName);
		CFactory::_('Language')->set(
			'adminsys', CFactory::_('Config')->lang_prefix . '_CONFIGURATION',
			$componentName . ' Configuration'
		);
		CFactory::_('Language')->set('admin', CFactory::_('Config')->lang_prefix, $componentName);
		CFactory::_('Language')->set('admin', CFactory::_('Config')->lang_prefix . '_BACK', 'Back');
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_DASH', 'Dashboard'
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_VERSION', 'Version'
		);
		CFactory::_('Language')->set('admin', CFactory::_('Config')->lang_prefix . '_DATE', 'Date');
		CFactory::_('Language')->set('admin', CFactory::_('Config')->lang_prefix . '_AUTHOR', 'Author');
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_WEBSITE', 'Website'
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_LICENSE', 'License'
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_CONTRIBUTORS', 'Contributors'
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_CONTRIBUTOR', 'Contributor'
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_DASHBOARD',
			$componentName . ' Dashboard'
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_SAVE_SUCCESS',
			"Great! Item successfully saved."
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_SAVE_WARNING',
			"The value already existed so please select another."
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_HELP_MANAGER', "Help"
		);
		CFactory::_('Language')->set('admin', CFactory::_('Config')->lang_prefix . '_NEW', "New");
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_CLOSE_NEW', "Close & New"
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_CREATE_NEW_S', "Create New %s"
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_EDIT_S', "Edit %s"
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_KEEP_ORIGINAL_STATE',
			"- Keep Original State -"
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_KEEP_ORIGINAL_ACCESS',
			"- Keep Original Access -"
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_KEEP_ORIGINAL_CATEGORY',
			"- Keep Original Category -"
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_PUBLISHED', 'Published'
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_INACTIVE', 'Inactive'
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_ARCHIVED', 'Archived'
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_TRASHED', 'Trashed'
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_NO_ACCESS_GRANTED',
			"No Access Granted!"
		);
		CFactory::_('Language')->set(
			'admin', CFactory::_('Config')->lang_prefix . '_NOT_FOUND_OR_ACCESS_DENIED',
			"Not found or access denied!"
		);

		if (CFactory::_('Component')->get('add_license')
			&& CFactory::_('Component')->get('license_type') == 3)
		{
			CFactory::_('Language')->set(
				'admin', 'NIE_REG_NIE',
				"<br /><br /><center><h1>License not set for " . $componentName
				. ".</h1><p>Notify your administrator!<br />The license can be obtained from <a href='"
				. CFactory::_('Component')->get('whmcs_buy_link') . "' target='_blank'>"
				. CFactory::_('Component')->get('companyname') . "</a>.</p></center>"
			);
		}

		// add the langug files needed to import and export data
		if (CFactory::_('Config')->get('add_eximport', false))
		{
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_EXPORT_FAILED', "Export Failed"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_FAILED', "Import Failed"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_TITLE', "Data Importer"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_NO_IMPORT_TYPE_FOUND',
				"Import type not found."
			);
			CFactory::_('Language')->set(
				'admin',
				CFactory::_('Config')->lang_prefix . '_IMPORT_UNABLE_TO_FIND_IMPORT_PACKAGE',
				"Package to import not found."
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_ERROR', "Import error."
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_SUCCESS',
				"Great! Import successful."
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_MSG_WARNIMPORTFILE',
				"Warning, import file error."
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_MSG_NO_FILE_SELECTED',
				"No import file selected."
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_MSG_PLEASE_SELECT_A_FILE',
				"Please select a file to import."
			);
			CFactory::_('Language')->set(
				'admin',
				CFactory::_('Config')->lang_prefix . '_IMPORT_MSG_PLEASE_SELECT_ALL_COLUMNS',
				"Please link all columns."
			);
			CFactory::_('Language')->set(
				'admin',
				CFactory::_('Config')->lang_prefix . '_IMPORT_MSG_PLEASE_SELECT_A_DIRECTORY',
				"Please enter the file directory."
			);
			CFactory::_('Language')->set(
				'admin',
				CFactory::_('Config')->lang_prefix . '_IMPORT_MSG_WARNIMPORTUPLOADERROR',
				"Warning, import upload error."
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix
				. '_IMPORT_MSG_PLEASE_ENTER_A_PACKAGE_DIRECTORY',
				"Please enter the file directory."
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix
				. '_IMPORT_MSG_PATH_DOES_NOT_HAVE_A_VALID_PACKAGE',
				"Path does not have a valid file."
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix
				. '_IMPORT_MSG_DOES_NOT_HAVE_A_VALID_FILE_TYPE',
				"Does not have a valid file type."
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_MSG_ENTER_A_URL',
				"Please enter a url."
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_MSG_INVALID_URL',
				"Invalid url."
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_CONTINUE', "Continue"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_FROM_UPLOAD', "Upload"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_SELECT_FILE',
				"Select File"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_UPLOAD_BOTTON',
				"Upload File"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_FROM_DIRECTORY',
				"Directory"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_SELECT_FILE_DIRECTORY',
				"Set the path to file"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_GET_BOTTON', "Get File"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_FROM_URL', "URL"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_SELECT_FILE_URL',
				"Enter file URL"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_UPDATE_DATA',
				"Import Data"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_FORMATS_ACCEPTED',
				"formats accepted"
			);
			CFactory::_('Language')->set(
				'admin',
				CFactory::_('Config')->lang_prefix . '_IMPORT_LINK_FILE_TO_TABLE_COLUMNS',
				"Link File to Table Columns"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_TABLE_COLUMNS',
				"Table Columns"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_FILE_COLUMNS',
				"File Columns"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_PLEASE_SELECT_COLUMN',
				"-- Please Select Column --"
			);
			CFactory::_('Language')->set(
				'admin', CFactory::_('Config')->lang_prefix . '_IMPORT_IGNORE_COLUMN',
				"-- Ignore This Column --"
			);
		}

		// check if the both array is set
		if (CFactory::_('Language')->exist('both'))
		{
			foreach (CFactory::_('Language')->getTarget('both') as $keylang => $langval)
			{
				CFactory::_('Language')->set('admin', $keylang, $langval);
			}
		}

		// check if the both admin array is set
		if (CFactory::_('Language')->exist('bothadmin'))
		{
			foreach (CFactory::_('Language')->getTarget('bothadmin') as $keylang => $langval)
			{
				CFactory::_('Language')->set('admin', $keylang, $langval);
			}
		}

		if (CFactory::_('Language')->exist('admin'))
		{
			// Trigger Event: jcb_ce_onAfterBuildAdminLang
			CFactory::_('Event')->trigger(
				'jcb_ce_onAfterBuildAdminLang'
			);
			// get language content
			$langContent = CFactory::_('Language')->getTarget('admin');
			// sort the strings
			ksort($langContent);
			// load to global languages
			$langTag = CFactory::_('Config')->get('lang_tag', 'en-GB');
			CFactory::_('Compiler.Builder.Languages')->set(
				"components.{$langTag}.admin",
				$langContent
			);
			// remove tmp array
			CFactory::_('Language')->setTarget('admin', null);

			return true;
		}

		return false;
	}

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
	                                            $nameListCode
	)
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

	public function buildTheViewScript($viewArray)
	{
		// set the view name
		$nameSingleCode = $viewArray['settings']->name_single_code;
		// add conditions to this view
		if (isset($viewArray['settings']->conditions)
			&& ArrayHelper::check(
				$viewArray['settings']->conditions
			))
		{
			// reset defaults
			$getValue       = [];
			$ifValue        = [];
			$targetControls = [];
			$functions      = [];

			foreach ($viewArray['settings']->conditions as $condition)
			{
				if (isset($condition['match_name'])
					&& StringHelper::check(
						$condition['match_name']
					))
				{
					$uniqueVar      = Unique::get(7);
					$matchName      = $condition['match_name'] . '_'
						. $uniqueVar;
					$targetBehavior = ($condition['target_behavior'] == 1
						|| $condition['target_behavior'] == 3) ? 'show'
						: 'hide';
					$targetDefault  = ($condition['target_behavior'] == 1
						|| $condition['target_behavior'] == 3) ? 'hide'
						: 'show';

					// set the realtation if any
					if ($condition['target_relation'])
					{
						// chain to other items of the same target
						$relations = $this->getTargetRelationScript(
							$viewArray['settings']->conditions, $condition,
							$nameSingleCode
						);
						if (ArrayHelper::check($relations))
						{
							// set behavior and default array
							$behaviors[$matchName] = $targetBehavior;
							$defaults[$matchName]  = $targetDefault;
							$toggleSwitch[$matchName]
								= ($condition['target_behavior']
								== 1
								|| $condition['target_behavior'] == 2) ? true
								: false;
							// set the type buket
							$typeBuket[$matchName] = $condition['match_type'];
							// set function array
							$functions[$uniqueVar][0] = $matchName;
							$matchNames[$matchName]
								= $condition['match_name'];
							// get the select value
							$getValue[$matchName] = $this->getValueScript(
								$condition['match_type'],
								$condition['match_name'],
								$condition['match_extends'], $uniqueVar
							);
							// get the options
							$options = $this->getOptionsScript(
								$condition['match_type'],
								$condition['match_options']
							);
							// set the if values
							$ifValue[$matchName] = $this->ifValueScript(
								$matchName, $condition['match_behavior'],
								$condition['match_type'], $options
							);
							// set the target controls
							$targetControls[$matchName]
								= $this->setTargetControlsScript(
								$toggleSwitch[$matchName],
								$condition['target_field'], $targetBehavior,
								$targetDefault, $uniqueVar, $nameSingleCode
							);

							foreach ($relations as $relation)
							{
								if (StringHelper::check(
									$relation['match_name']
								))
								{
									$relationName = $relation['match_name']
										. '_' . $uniqueVar;
									// set the type buket
									$typeBuket[$relationName]
										= $relation['match_type'];
									// set function array
									$functions[$uniqueVar][] = $relationName;
									$matchNames[$relationName]
										= $relation['match_name'];
									// get the relation option
									$relationOptions = $this->getOptionsScript(
										$relation['match_type'],
										$relation['match_options']
									);
									$getValue[$relationName]
										= $this->getValueScript(
										$relation['match_type'],
										$relation['match_name'],
										$condition['match_extends'], $uniqueVar
									);
									$ifValue[$relationName]
										= $this->ifValueScript(
										$relationName,
										$relation['match_behavior'],
										$relation['match_type'],
										$relationOptions
									);
								}
							}
						}
					}
					else
					{
						// set behavior and default array
						$behaviors[$matchName] = $targetBehavior;
						$defaults[$matchName]  = $targetDefault;
						$toggleSwitch[$matchName]
							= ($condition['target_behavior']
							== 1
							|| $condition['target_behavior'] == 2) ? true
							: false;
						// set the type buket
						$typeBuket[$matchName] = $condition['match_type'];
						// set function array
						$functions[$uniqueVar][0] = $matchName;
						$matchNames[$matchName]   = $condition['match_name'];
						// get the select value
						$getValue[$matchName] = $this->getValueScript(
							$condition['match_type'], $condition['match_name'],
							$condition['match_extends'], $uniqueVar
						);
						// get the options
						$options = $this->getOptionsScript(
							$condition['match_type'],
							$condition['match_options']
						);
						// set the if values
						$ifValue[$matchName] = $this->ifValueScript(
							$matchName, $condition['match_behavior'],
							$condition['match_type'], $options
						);
						// set the target controls
						$targetControls[$matchName]
							= $this->setTargetControlsScript(
							$toggleSwitch[$matchName],
							$condition['target_field'], $targetBehavior,
							$targetDefault, $uniqueVar, $nameSingleCode
						);
					}
				}
			}
			// reset buckets
			$initial    = '';
			$func       = '';
			$validation = '';
			$isSet      = '';
			$listener   = '';
			if (ArrayHelper::check($functions))
			{
				// now build the initial script
				$initial .= "//" . Line::_(__Line__, __Class__) . " Initial Script"
					. PHP_EOL . "document.addEventListener('DOMContentLoaded', function()";
				$initial .= PHP_EOL . "{";
				foreach ($functions as $function => $matchKeys)
				{
					$func_call = $this->buildFunctionCall(
						$function, $matchKeys, $getValue
					);
					$initial   .= $func_call['code'];
				}
				$initial .= "});" . PHP_EOL;
				// for modal fields
				$modal = '';
				// now build the listener scripts
				foreach ($functions as $l_function => $l_matchKeys)
				{
					$funcCall = '';
					foreach ($l_matchKeys as $l_matchKey)
					{
						$name         = $matchNames[$l_matchKey];
						$matchTypeKey = $typeBuket[$l_matchKey];
						$funcCall     = $this->buildFunctionCall(
							$l_function, $l_matchKeys, $getValue
						);

						if (CFactory::_('Compiler.Builder.Script.Media.Switch')->inArray($matchTypeKey))
						{
							$modal .= $funcCall['code'];
						}
						else
						{
							if (CFactory::_('Compiler.Builder.Script.User.Switch')->inArray($matchTypeKey))
							{
								$name = $name . '_id';
							}

							$listener .= PHP_EOL . "//" . Line::_(
									__LINE__,__CLASS__
								) . " #jform_" . $name . " listeners for "
								. $l_matchKey . " function";
							$listener .= PHP_EOL . "jQuery('#jform_" . $name
								. "').on('keyup',function()";
							$listener .= PHP_EOL . "{";
							$listener .= $funcCall['code'];
							$listener .= PHP_EOL . "});";
							$listener .= PHP_EOL
								. "jQuery('#adminForm').on('change', '#jform_"
								. $name . "',function (e)";
							$listener .= PHP_EOL . "{";
							$listener .= PHP_EOL . Indent::_(1)
								. "e.preventDefault();";
							$listener .= $funcCall['code'];
							$listener .= PHP_EOL . "});" . PHP_EOL;
						}
					}
				}
				if (StringHelper::check($modal))
				{
					$listener .= PHP_EOL . "window.SqueezeBox.initialize({";
					$listener .= PHP_EOL . Indent::_(1) . "onClose:function(){";
					$listener .= $modal;
					$listener .= PHP_EOL . Indent::_(1) . "}";
					$listener .= PHP_EOL . "});" . PHP_EOL;
				}

				// now build the function
				$func = '';
				$head = '';
				foreach ($functions as $f_function => $f_matchKeys)
				{
					$map = '';
					// does this function require an array
					$addArray = false;
					$func_    = $this->buildFunctionCall(
						$f_function, $f_matchKeys, $getValue
					);
					// set array switch
					if ($func_['array'])
					{
						$addArray = true;
					}
					$func      .= PHP_EOL . "//" . Line::_(__Line__, __Class__)
						. " the " . $f_function . " function";
					$func      .= PHP_EOL . "function " . $f_function . "(";
					$fucounter = 0;
					foreach ($f_matchKeys as $fu_matchKey)
					{
						if (StringHelper::check($fu_matchKey))
						{
							if ($fucounter == 0)
							{
								$func .= $fu_matchKey;
							}
							else
							{
								$func .= ',' . $fu_matchKey;
							}
							$fucounter++;
						}
					}
					$func .= ")";
					$func .= PHP_EOL . "{";
					if ($addArray)
					{
						foreach ($f_matchKeys as $a_matchKey)
						{
							$name = $matchNames[$a_matchKey];
							$func .= PHP_EOL . Indent::_(1) . "if (isSet("
								. $a_matchKey . ") && " . $a_matchKey
								. ".constructor !== Array)" . PHP_EOL
								. Indent::_(1) . "{" . PHP_EOL . Indent::_(2)
								. "var temp_" . $f_function . " = "
								. $a_matchKey . ";" . PHP_EOL . Indent::_(2)
								. "var " . $a_matchKey . " = [];" . PHP_EOL
								. Indent::_(2) . $a_matchKey . ".push(temp_"
								. $f_function . ");" . PHP_EOL . Indent::_(1)
								. "}";
							$func .= PHP_EOL . Indent::_(1) . "else if (!isSet("
								. $a_matchKey . "))" . PHP_EOL . Indent::_(1)
								. "{";
							$func .= PHP_EOL . Indent::_(2) . "var "
								. $a_matchKey . " = [];";
							$func .= PHP_EOL . Indent::_(1) . "}";
							$func .= PHP_EOL . Indent::_(1) . "var " . $name
								. " = " . $a_matchKey . ".some(" . $a_matchKey
								. "_SomeFunc);" . PHP_EOL;

							// setup the map function
							$map .= PHP_EOL . "//" . Line::_(__Line__, __Class__)
								. " the " . $f_function . " Some function";
							$map .= PHP_EOL . "function " . $a_matchKey
								. "_SomeFunc(" . $a_matchKey . ")";
							$map .= PHP_EOL . "{";
							$map .= PHP_EOL . Indent::_(1) . "//"
								. Line::_(__Line__, __Class__)
								. " set the function logic";
							$map .= PHP_EOL . Indent::_(1) . "if (";
							$if  = $ifValue[$a_matchKey];
							if (StringHelper::check($if))
							{
								$map .= $if;
							}
							$map .= ")";
							$map .= PHP_EOL . Indent::_(1) . "{";
							$map .= PHP_EOL . Indent::_(2) . "return true;";
							$map .= PHP_EOL . Indent::_(1) . "}" . PHP_EOL
								. Indent::_(1) . "return false;";
							$map .= PHP_EOL . "}" . PHP_EOL;
						}
						$func .= PHP_EOL . PHP_EOL . Indent::_(1) . "//"
							. Line::_(__Line__, __Class__)
							. " set this function logic";
						$func .= PHP_EOL . Indent::_(1) . "if (";
						// set if counter
						$aifcounter = 0;
						foreach ($f_matchKeys as $af_matchKey)
						{
							$name = $matchNames[$af_matchKey];
							if ($aifcounter == 0)
							{
								$func .= $name;
							}
							else
							{
								$func .= ' && ' . $name;
							}
							$aifcounter++;
						}
						$func .= ")" . PHP_EOL . Indent::_(1) . "{";
					}
					else
					{
						$func .= PHP_EOL . Indent::_(1) . "//" . Line::_(
								__LINE__,__CLASS__
							) . " set the function logic";
						$func .= PHP_EOL . Indent::_(1) . "if (";
						// set if counter
						$ifcounter = 0;
						foreach ($f_matchKeys as $f_matchKey)
						{
							$if = $ifValue[$f_matchKey];
							if (StringHelper::check($if))
							{
								if ($ifcounter == 0)
								{
									$func .= $if;
								}
								else
								{
									$func .= ' && ' . $if;
								}
								$ifcounter++;
							}
						}
						$func .= ")" . PHP_EOL . Indent::_(1) . "{";
					}
					// get the controles
					$controls = $targetControls[$f_matchKeys[0]];
					// get target behavior and default
					$targetBehavior = $behaviors[$f_matchKeys[0]];
					$targetDefault  = $defaults[$f_matchKeys[0]];
					// load the target behavior
					foreach ($controls as $target => $action)
					{
						$func .= $action['behavior'];
						if (StringHelper::check(
							$action[$targetBehavior]
						))
						{
							$func .= $action[$targetBehavior];
							$head .= $action['requiredVar'];
						}
					}
					// check if this is a toggle switch
					if ($toggleSwitch[$f_matchKeys[0]])
					{
						$func .= PHP_EOL . Indent::_(1) . "}" . PHP_EOL
							. Indent::_(1) . "else" . PHP_EOL . Indent::_(1)
							. "{";
						// load the default behavior
						foreach ($controls as $target => $action)
						{
							$func .= $action['default'];
							if (StringHelper::check(
								$action[$targetDefault]
							))
							{
								$func .= $action[$targetDefault];
							}
						}
					}
					$func .= PHP_EOL . Indent::_(1) . "}" . PHP_EOL . "}"
						. PHP_EOL . $map;
				}
				// add the needed validation to file
				if (isset($this->validationFixBuilder[$nameSingleCode])
					&& ArrayHelper::check(
						$this->validationFixBuilder[$nameSingleCode]
					))
				{
					$validation .= PHP_EOL . "/**";
					$validation .= PHP_EOL . " * Update the \"not required\" field list by adding or removing a field name.";
					$validation .= PHP_EOL . " *";
					$validation .= PHP_EOL . " * Mirrors the original jQuery logic exactly but uses pure JavaScript.";
					$validation .= PHP_EOL . " *";
					$validation .= PHP_EOL . " * @param  {string}  name    The field name to add or remove.";
					$validation .= PHP_EOL . " * @param  {number}  status  1 to add as not required, 0 to remove.";
					$validation .= PHP_EOL . " *";
					$validation .= PHP_EOL . " * @return {void}";
					$validation .= PHP_EOL . " * @since  3.1.3";
					$validation .= PHP_EOL . " */";
					$validation .= PHP_EOL . "function updateFieldRequired(name, status) {";
					$validation .= PHP_EOL . Indent::_(1) . "// Check if #jform_not_required exists";
					$validation .= PHP_EOL . Indent::_(1) . "const notRequiredField = document.getElementById('jform_not_required');";
					$validation .= PHP_EOL . Indent::_(1) . "if (!notRequiredField) {";
					$validation .= PHP_EOL . Indent::_(2) . "return;";
					$validation .= PHP_EOL . Indent::_(1) . "}" . PHP_EOL;
					$validation .= PHP_EOL . Indent::_(1) . "// Split the comma-separated list into an array";
					$validation .= PHP_EOL . Indent::_(1) . "let not_required = notRequiredField.value ? notRequiredField.value.split(',') : [];" . PHP_EOL;
					$validation .= PHP_EOL . Indent::_(1) . "// Add or remove the field name from the list";
					$validation .= PHP_EOL . Indent::_(1) . "if (status == 1) {";
					$validation .= PHP_EOL . Indent::_(2) . "not_required.push(name);";
					$validation .= PHP_EOL . Indent::_(1) . "} else {";
					$validation .= PHP_EOL . Indent::_(2) . "not_required = removeFieldFromNotRequired(not_required, name);";
					$validation .= PHP_EOL . Indent::_(1) . "}" . PHP_EOL;
					$validation .= PHP_EOL . Indent::_(1) . "// Clean and deduplicate the list";
					$validation .= PHP_EOL . Indent::_(1) . "const fixedList = fixNotRequiredArray(not_required);" . PHP_EOL;
					$validation .= PHP_EOL . Indent::_(1) . "// Write back the updated comma-separated list";
					$validation .= PHP_EOL . Indent::_(1) . "notRequiredField.value = fixedList.toString();";
					$validation .= PHP_EOL . "}" . PHP_EOL;
					$validation .= PHP_EOL . "/**";
					$validation .= PHP_EOL . " * Remove a specific field name from the \"not required\" array.";
					$validation .= PHP_EOL . " *";
					$validation .= PHP_EOL . " * @param  {Array<string>} array  The list of not-required field names.";
					$validation .= PHP_EOL . " * @param  {string}        what   The field name to remove.";
					$validation .= PHP_EOL . " *";
					$validation .= PHP_EOL . " * @return {Array<string>}        The updated array.";
					$validation .= PHP_EOL . " * @since  3.1.3";
					$validation .= PHP_EOL . " */";
					$validation .= PHP_EOL . "function removeFieldFromNotRequired(array, what) {";
					$validation .= PHP_EOL . Indent::_(1) . "return array.filter(function (element) {";
					$validation .= PHP_EOL . Indent::_(2) . "return element !== what;";
					$validation .= PHP_EOL . Indent::_(1) . "});";
					$validation .= PHP_EOL . "}" . PHP_EOL;
					$validation .= PHP_EOL . "/**";
					$validation .= PHP_EOL . " * Deduplicate and clean a \"not required\" array.";
					$validation .= PHP_EOL . " *";
					$validation .= PHP_EOL . " * @param  {Array<string>} array  The array to fix.";
					$validation .= PHP_EOL . " *";
					$validation .= PHP_EOL . " * @return {Array<string>}        A cleaned, unique array.";
					$validation .= PHP_EOL . " * @since  3.1.3";
					$validation .= PHP_EOL . " */";
					$validation .= PHP_EOL . "function fixNotRequiredArray(array) {";
					$validation .= PHP_EOL . Indent::_(1) . "const seen = {};";
					$validation .= PHP_EOL . Indent::_(1) . "return removeEmptyFromNotRequiredArray(array).filter(function (item) {";
					$validation .= PHP_EOL . Indent::_(2) . "return seen.hasOwnProperty(item) ? false : (seen[item] = true);";
					$validation .= PHP_EOL . Indent::_(1) . "});";
					$validation .= PHP_EOL . "}" . PHP_EOL;
					$validation .= PHP_EOL . "/**";
					$validation .= PHP_EOL . " * Remove empty or invalid entries from a \"not required\" array.";
					$validation .= PHP_EOL . " *";
					$validation .= PHP_EOL . " * Also removes the literal '一_一' token (legacy quirk preserved for compatibility).";
					$validation .= PHP_EOL . " *";
					$validation .= PHP_EOL . " * @param  {Array<string>} array  The array to process.";
					$validation .= PHP_EOL . " *";
					$validation .= PHP_EOL . " * @return {Array<string>}        The cleaned array.";
					$validation .= PHP_EOL . " * @since  3.1.3";
					$validation .= PHP_EOL . " */";
					$validation .= PHP_EOL . "function removeEmptyFromNotRequiredArray(array) {";
					$validation .= PHP_EOL . Indent::_(1) . "return array.filter(function (el) {";
					$validation .= PHP_EOL . Indent::_(2) . "return el && el.length > 0 && el !== '一_一';";
					$validation .= PHP_EOL . Indent::_(1) . "});";
					$validation .= PHP_EOL . "}" . PHP_EOL;
				}
				// set the isSet function
				$isSet = PHP_EOL . "// the isSet function";
				$isSet .= PHP_EOL . "function isSet(val)";
				$isSet .= PHP_EOL . "{";
				$isSet .= PHP_EOL . Indent::_(1)
					. "if ((val != undefined) && (val != null) && 0 !== val.length){";
				$isSet .= PHP_EOL . Indent::_(2) . "return true;";
				$isSet .= PHP_EOL . Indent::_(1) . "}";
				$isSet .= PHP_EOL . Indent::_(1) . "return false;";
				$isSet .= PHP_EOL . "}";
			}
			// load to this buket
			$fileScript   = $initial . $func . $validation . $isSet;
			$footerScript = $listener;
		}
		// add custom script to edit form JS file
		if (!isset($fileScript))
		{
			$fileScript = '';
		}
		$fileScript .= CFactory::_('Customcode.Dispenser')->get(
			'view_file', $nameSingleCode, PHP_EOL . PHP_EOL, null, true, ''
		);
		// add custom script to footer
		if (isset(CFactory::_('Customcode.Dispenser')->hub['view_footer'][$nameSingleCode])
			&& StringHelper::check(
				CFactory::_('Customcode.Dispenser')->hub['view_footer'][$nameSingleCode]
			))
		{
			$customFooterScript = PHP_EOL . PHP_EOL . CFactory::_('Placeholder')->update_(
					CFactory::_('Customcode.Dispenser')->hub['view_footer'][$nameSingleCode]
				);
			if (strpos($customFooterScript, '<?php') === false)
			{
				// only add now if no php is added to the footer script
				if (!isset($footerScript))
				{
					$footerScript = '';
				}
				$footerScript .= $customFooterScript;
				unset($customFooterScript);
			}
		}
		// set view listname
		$nameListCode = $viewArray['settings']->name_list_code;
		// add custom script to list view JS file
		if (($list_fileScript = CFactory::_('Customcode.Dispenser')->get(
				'views_file', $nameSingleCode, PHP_EOL . PHP_EOL, null, true,
				false
			)) !== false
			&& StringHelper::check($list_fileScript))
		{
			// get dates
			$_created  = CFactory::_('Model.Createdate')->get($viewArray);
			$_modified = CFactory::_('Model.Modifieddate')->get($viewArray);
			// add file to view
			$_target = array(CFactory::_('Config')->build_target => $nameListCode);
			$_config = array(Placefix::_h('CREATIONDATE') => $_created,
				Placefix::_h('BUILDDATE') => $_modified,
				Placefix::_h('VERSION') => $viewArray['settings']->version);
			CFactory::_('Utilities.Structure')->build($_target, 'javascript_file', false, $_config);
			// set path
			$_path = '/administrator/components/com_' . CFactory::_('Config')->component_code_name
				. '/assets/js/' . $nameListCode . '.js';
			// load the file to the list view
			CFactory::_('Compiler.Builder.Content.Multi')->set($nameListCode . '|ADMIN_ADD_JAVASCRIPT_FILE', PHP_EOL . PHP_EOL . Indent::_(2) . "//" . Line::_(
					__LINE__,__CLASS__
				) . " Add List View JavaScript File" . PHP_EOL . Indent::_(2)
				. CFactory::_('Library.IncludeHelper')->get($_path)
			);
		}
		else
		{
			$list_fileScript = '';
			CFactory::_('Compiler.Builder.Content.Multi')->set($nameListCode . '|ADMIN_ADD_JAVASCRIPT_FILE', '');
		}
		// minify the script
		if (CFactory::_('Config')->get('minify', 0) && isset($list_fileScript)
			&& StringHelper::check($list_fileScript))
		{
			// minify the fileScript javascript
			$list_fileScript = Minify::js($list_fileScript);
		}
		// minify the script
		if (CFactory::_('Config')->get('minify', 0) && isset($fileScript)
			&& StringHelper::check($fileScript))
		{
			// minify the fileScript javascript
			$fileScript = Minify::js($fileScript);
		}
		// minify the script
		if (CFactory::_('Config')->get('minify', 0) && isset($footerScript)
			&& StringHelper::check($footerScript))
		{
			// minify the footerScript javascript
			$footerScript = Minify::js($footerScript);
		}
		// make sure there is script to add
		if (isset($list_fileScript)
			&& StringHelper::check(
				$list_fileScript
			))
		{
			// load the script
			$this->viewScriptBuilder[$nameListCode]['list_fileScript']
				= $list_fileScript;
		}
		// make sure there is script to add
		if (isset($fileScript)
			&& StringHelper::check(
				$fileScript
			))
		{
			// add the head script if set
			if (isset($head) && StringHelper::check($head))
			{
				$fileScript = "// Some Global Values" . PHP_EOL . $head
					. PHP_EOL . $fileScript;
			}
			// load the script
			$this->viewScriptBuilder[$nameSingleCode]['fileScript']
				= $fileScript;
		}
		// make sure to add custom footer script if php was found in it, since we canot minfy it with php
		if (isset($customFooterScript)
			&& StringHelper::check(
				$customFooterScript
			))
		{
			if (!isset($footerScript))
			{
				$footerScript = '';
			}
			$footerScript .= $customFooterScript;
		}
		// make sure there is script to add
		if (isset($footerScript)
			&& StringHelper::check(
				$footerScript
			))
		{
			// add the needed script tags
			$footerScript = PHP_EOL
				. PHP_EOL . '<script type="text/javascript">' . PHP_EOL
				. $footerScript . PHP_EOL . "</script>";
			$this->viewScriptBuilder[$nameSingleCode]['footerScript']
				= $footerScript;
		}
	}

	public function buildFunctionCall($function, $matchKeys, $getValue)
	{
		$initial  = '';
		$funcsets = [];
		$array    = false;
		foreach ($matchKeys as $matchKey)
		{
			$value = $getValue[$matchKey];
			if ($value['isArray'])
			{
				$initial    .= PHP_EOL . Indent::_(1) . $value['get'];
				$funcsets[] = $matchKey;
				$array      = true;
			}
			else
			{
				$initial    .= PHP_EOL . Indent::_(1) . $value['get'];
				$funcsets[] = $matchKey;
			}
		}

		// make sure that the function is loaded only once
		if (ArrayHelper::check($funcsets))
		{
			$initial .= PHP_EOL . Indent::_(1) . $function . "(";
			$initial .= implode(',', $funcsets);
			$initial .= ");" . PHP_EOL;
		}

		return array('code' => $initial, 'array' => $array);
	}

	public function getTargetRelationScript($relations, $condition, $view)
	{
		// reset the buket
		$buket = [];
		// convert to name array
		foreach ($condition['target_field'] as $targetField)
		{
			if (ArrayHelper::check($targetField)
				&& isset($targetField['name']))
			{
				$currentTargets[] = $targetField['name'];
			}
		}
		// start the search
		foreach ($relations as $relation)
		{
			// reset found
			$found = false;
			// chain only none matching fields
			if ($relation['match_field'] !== $condition['match_field']
				&& $relation['target_relation']) // Made this change to see if it improves the expected result (TODO)
			{
				if (ArrayHelper::check(
					$relation['target_field']
				))
				{
					foreach ($relation['target_field'] as $target)
					{
						if (ArrayHelper::check($target)
							&& $this->checkRelationControl(
								$target['name'], $relation['match_name'],
								$condition['match_name'], $view
							))
						{
							if (in_array($target['name'], $currentTargets))
							{
								$this->targetRelationControl[$view][$target['name']]
									= array($relation['match_name'],
									$condition['match_name']);
								$found = true;
								break;
							}
						}
					}
					if ($found)
					{
						$buket[] = $relation;
					}
				}
			}
		}

		return $buket;
	}

	public function checkRelationControl($targetName, $relationMatchName,
	                                     $conditionMatchName, $view
	)
	{
		if (isset($this->targetRelationControl[$view])
			&& ArrayHelper::check(
				$this->targetRelationControl[$view]
			))
		{
			if (isset($this->targetRelationControl[$view][$targetName])
				&& ArrayHelper::check(
					$this->targetRelationControl[$view][$targetName]
				))
			{
				if (!in_array(
						$relationMatchName,
						$this->targetRelationControl[$view][$targetName]
					)
					|| !in_array(
						$conditionMatchName,
						$this->targetRelationControl[$view][$targetName]
					))
				{
					return true;
				}
			}
			else
			{
				return true;
			}
		}
		elseif (!isset($this->targetRelationControl[$view])
			|| !ArrayHelper::check(
				$this->targetRelationControl[$view]
			))
		{
			return true;
		}

		return false;
	}

	public function setTargetControlsScript($toggleSwitch, $targets,
	                                        $targetBehavior, $targetDefault, $uniqueVar, $nameSingleCode
	)
	{
		$bucket = [];
		if (ArrayHelper::check($targets)
			&& !in_array(
				$uniqueVar, $this->targetControlsScriptChecker
			))
		{
			foreach ($targets as $target)
			{
				if (ArrayHelper::check($target))
				{
					// set the required var
					if ($target['required'] === 'yes')
					{
						$unique                                 = $uniqueVar
							. Unique::get(3);
						$bucket[$target['name']]['requiredVar'] = "jform_"
							. $unique . "_required = false;" . PHP_EOL;
					}
					else
					{
						$bucket[$target['name']]['requiredVar'] = '';
					}
					// set target type
					$targetTypeSufix = "";
					if (CFactory::_('Field.Groups')->check(
						$target['type'], 'spacer'
					))
					{
						// target a class if this is a note or spacer
						$targetType = ".";
					}
					elseif ($target['type'] === 'editor'
						|| $target['type'] === 'subform')
					{
						// target the label if  editor field
						$targetType = "#jform_";
						// since the id is not alway accessable we use the lable TODO (not best way)
						$targetTypeSufix = "-lbl";
					}
					else
					{
						// target an id if this is a field
						$targetType = "#jform_";
					}
					// set the target behavior
					$bucket[$target['name']]['behavior'] = PHP_EOL . Indent::_(
							2
						) . "jQuery('" . $targetType . $target['name']
						. $targetTypeSufix . "').closest('.control-group')."
						. $targetBehavior . "();";
					// set the target default
					$bucket[$target['name']]['default'] = PHP_EOL . Indent::_(2)
						. "jQuery('" . $targetType . $target['name']
						. $targetTypeSufix . "').closest('.control-group')."
						. $targetDefault . "();";
					// the hide required function
					if ($target['required'] === 'yes')
					{
						if ($toggleSwitch)
						{
							$hide                            = PHP_EOL
								. Indent::_(2) . "//" . Line::_(__Line__, __Class__)
								. " remove required attribute from "
								. $target['name'] . " field";
							$hide                            .= PHP_EOL
								. Indent::_(2) . "if (!jform_" . $unique
								. "_required)";
							$hide                            .= PHP_EOL
								. Indent::_(2) . "{";
							$hide                            .= PHP_EOL
								. Indent::_(3) . "updateFieldRequired('"
								. $target['name'] . "',1);";
							$hide                            .= PHP_EOL
								. Indent::_(3) . "jQuery('#jform_"
								. $target['name']
								. "').removeAttr('required');";
							$hide                            .= PHP_EOL
								. Indent::_(3) . "jQuery('#jform_"
								. $target['name']
								. "').removeAttr('aria-required');";
							$hide                            .= PHP_EOL
								. Indent::_(3) . "jQuery('#jform_"
								. $target['name']
								. "').removeClass('required');";
							$hide                            .= PHP_EOL
								. Indent::_(3) . "jform_" . $unique
								. "_required = true;";
							$hide                            .= PHP_EOL
								. Indent::_(2) . "}";
							$bucket[$target['name']]['hide'] = $hide;
							// the show required function
							$show                            = PHP_EOL
								. Indent::_(2) . "//" . Line::_(__Line__, __Class__)
								. " add required attribute to "
								. $target['name'] . " field";
							$show                            .= PHP_EOL
								. Indent::_(2) . "if (jform_" . $unique
								. "_required)";
							$show                            .= PHP_EOL
								. Indent::_(2) . "{";
							$show                            .= PHP_EOL
								. Indent::_(3) . "updateFieldRequired('"
								. $target['name'] . "',0);";
							$show                            .= PHP_EOL
								. Indent::_(3) . "jQuery('#jform_"
								. $target['name']
								. "').prop('required','required');";
							$show                            .= PHP_EOL
								. Indent::_(3) . "jQuery('#jform_"
								. $target['name']
								. "').attr('aria-required',true);";
							$show                            .= PHP_EOL
								. Indent::_(3) . "jQuery('#jform_"
								. $target['name'] . "').addClass('required');";
							$show                            .= PHP_EOL
								. Indent::_(3) . "jform_" . $unique
								. "_required = false;";
							$show                            .= PHP_EOL
								. Indent::_(2) . "}";
							$bucket[$target['name']]['show'] = $show;
						}
						else
						{
							$hide                            = PHP_EOL
								. Indent::_(2) . "//" . Line::_(__Line__, __Class__)
								. " remove required attribute from "
								. $target['name'] . " field";
							$hide                            .= PHP_EOL
								. Indent::_(2) . "updateFieldRequired('"
								. $target['name'] . "',1);";
							$hide                            .= PHP_EOL
								. Indent::_(2) . "jQuery('#jform_"
								. $target['name']
								. "').removeAttr('required');";
							$hide                            .= PHP_EOL
								. Indent::_(2) . "jQuery('#jform_"
								. $target['name']
								. "').removeAttr('aria-required');";
							$hide                            .= PHP_EOL
								. Indent::_(2) . "jQuery('#jform_"
								. $target['name']
								. "').removeClass('required');";
							$hide                            .= PHP_EOL
								. Indent::_(2) . "jform_" . $unique
								. "_required = true;" . PHP_EOL;
							$bucket[$target['name']]['hide'] = $hide;
							// the show required function
							$show                            = PHP_EOL
								. Indent::_(2) . "//" . Line::_(__Line__, __Class__)
								. " add required attribute to "
								. $target['name'] . " field";
							$show                            .= PHP_EOL
								. Indent::_(2) . "updateFieldRequired('"
								. $target['name'] . "',0);";
							$show                            .= PHP_EOL
								. Indent::_(2) . "jQuery('#jform_"
								. $target['name']
								. "').prop('required','required');";
							$show                            .= PHP_EOL
								. Indent::_(2) . "jQuery('#jform_"
								. $target['name']
								. "').attr('aria-required',true);";
							$show                            .= PHP_EOL
								. Indent::_(2) . "jQuery('#jform_"
								. $target['name'] . "').addClass('required');";
							$show                            .= PHP_EOL
								. Indent::_(2) . "jform_" . $unique
								. "_required = false;" . PHP_EOL;
							$bucket[$target['name']]['show'] = $show;
						}
						// make sure that the axaj and other needed things for this view is loaded
						$this->validationFixBuilder[$nameSingleCode][]
							= $target['name'];
					}
					else
					{
						$bucket[$target['name']]['hide'] = '';
						$bucket[$target['name']]['show'] = '';
					}
				}
			}
			$this->targetControlsScriptChecker[] = $uniqueVar;
		}

		return $bucket;
	}

	public function ifValueScript($value, $behavior, $type, $options)
	{
		// reset string
		$string = '';
		switch ($behavior)
		{
			case 1: // Is
				// only 4 list/radio/checkboxes
				if (CFactory::_('Field.Groups')->check($type, 'list')
					|| CFactory::_('Field.Groups')->check($type, 'dynamic')
					|| !CFactory::_('Field.Groups')->check($type))
				{
					if (ArrayHelper::check($options))
					{
						foreach ($options as $option)
						{
							if (!is_numeric($option))
							{
								if ($option != 'true' && $option != 'false')
								{
									$option = "'" . $option . "'";
								}
							}
							if (StringHelper::check($string))
							{
								$string .= ' || ' . $value . ' == ' . $option;
							}
							else
							{
								$string .= $value . ' == ' . $option;
							}
						}
					}
					else
					{
						$string .= 'isSet(' . $value . ')';
					}
				}
				break;
			case 2: // Is Not
				// only 4 list/radio/checkboxes
				if (CFactory::_('Field.Groups')->check($type, 'list')
					|| CFactory::_('Field.Groups')->check($type, 'dynamic')
					|| !CFactory::_('Field.Groups')->check($type))
				{
					if (ArrayHelper::check($options))
					{
						foreach ($options as $option)
						{
							if (!is_numeric($option))
							{
								if ($option != 'true' && $option != 'false')
								{
									$option = "'" . $option . "'";
								}
							}
							if (StringHelper::check($string))
							{
								$string .= ' || ' . $value . ' != ' . $option;
							}
							else
							{
								$string .= $value . ' != ' . $option;
							}
						}
					}
					else
					{
						$string .= '!isSet(' . $value . ')';
					}
				}
				break;
			case 3: // Any Selection
				// only 4 list/radio/checkboxes/dynamic_list
				if (CFactory::_('Field.Groups')->check($type, 'list')
					|| CFactory::_('Field.Groups')->check($type, 'dynamic')
					|| !CFactory::_('Field.Groups')->check($type))
				{
					if (ArrayHelper::check($options))
					{
						foreach ($options as $option)
						{
							if (!is_numeric($option))
							{
								if ($option != 'true' && $option != 'false')
								{
									$option = "'" . $option . "'";
								}
							}
							if (StringHelper::check($string))
							{
								$string .= ' || ' . $value . ' == ' . $option;
							}
							else
							{
								$string .= $value . ' == ' . $option;
							}
						}
					}
					else
					{
						$userFix = '';
						if (CFactory::_('Compiler.Builder.Script.User.Switch')->inArray($type))
						{
							// TODO this needs a closer look, a bit buggy
							$userFix = " && " . $value . " != 0";
						}
						$string .= 'isSet(' . $value . ')' . $userFix;
					}
				}
				break;
			case 4: // Active (not empty)
				// only 4 text_field
				if (CFactory::_('Field.Groups')->check($type, 'text'))
				{
					$string .= 'isSet(' . $value . ')';
				}
				break;
			case 5: // Unactive (empty)
				// only 4 text_field
				if (CFactory::_('Field.Groups')->check($type, 'text'))
				{
					$string .= '!isSet(' . $value . ')';
				}
				break;
			case 6: // Key Word All (case-sensitive)
				// only 4 text_field
				if (CFactory::_('Field.Groups')->check($type, 'text'))
				{
					if (ArrayHelper::check(
						$options['keywords']
					))
					{
						foreach ($options['keywords'] as $keyword)
						{
							if (StringHelper::check($string))
							{
								$string .= ' && ' . $value . '.indexOf("'
									. $keyword . '") >= 0';
							}
							else
							{
								$string .= $value . '.indexOf("' . $keyword
									. '") >= 0';
							}
						}
					}
					if (!StringHelper::check($string))
					{
						$string .= $value . ' == "error"';
					}
				}
				break;
			case 7: // Key Word Any (case-sensitive)
				// only 4 text_field
				if (CFactory::_('Field.Groups')->check($type, 'text'))
				{
					if (ArrayHelper::check(
						$options['keywords']
					))
					{
						foreach ($options['keywords'] as $keyword)
						{
							if (StringHelper::check($string))
							{
								$string .= ' || ' . $value . '.indexOf("'
									. $keyword . '") >= 0';
							}
							else
							{
								$string .= $value . '.indexOf("' . $keyword
									. '") >= 0';
							}
						}
					}
					if (!StringHelper::check($string))
					{
						$string .= $value . ' == "error"';
					}
				}
				break;
			case 8: // Key Word All (case-insensitive)
				// only 4 text_field
				if (CFactory::_('Field.Groups')->check($type, 'text'))
				{
					if (ArrayHelper::check(
						$options['keywords']
					))
					{
						foreach ($options['keywords'] as $keyword)
						{
							$keyword = StringHelper::safe(
								$keyword, 'w'
							);
							if (StringHelper::check($string))
							{
								$string .= ' && ' . $value
									. '.toLowerCase().indexOf("' . $keyword
									. '") >= 0';
							}
							else
							{
								$string .= $value . '.toLowerCase().indexOf("'
									. $keyword . '") >= 0';
							}
						}
					}
					if (!StringHelper::check($string))
					{
						$string .= $value . ' == "error"';
					}
				}
				break;
			case 9: // Key Word Any (case-insensitive)
				// only 4 text_field
				if (CFactory::_('Field.Groups')->check($type, 'text'))
				{
					if (ArrayHelper::check(
						$options['keywords']
					))
					{
						foreach ($options['keywords'] as $keyword)
						{
							$keyword = StringHelper::safe(
								$keyword, 'w'
							);
							if (StringHelper::check($string))
							{
								$string .= ' || ' . $value
									. '.toLowerCase().indexOf("' . $keyword
									. '") >= 0';
							}
							else
							{
								$string .= $value . '.toLowerCase().indexOf("'
									. $keyword . '") >= 0';
							}
						}
					}
					if (!StringHelper::check($string))
					{
						$string .= $value . ' == "error"';
					}
				}
				break;
			case 10: // Min Length
				// only 4 text_field
				if (CFactory::_('Field.Groups')->check($type, 'text'))
				{
					if (ArrayHelper::check($options))
					{
						if ($options['length'])
						{
							$string .= $value . '.length >= '
								. (int) $options['length'];
						}
					}
					if (!StringHelper::check($string))
					{
						$string .= $value . '.length >= 5';
					}
				}
				break;
			case 11: // Max Length
				// only 4 text_field
				if (CFactory::_('Field.Groups')->check($type, 'text'))
				{
					if (ArrayHelper::check($options))
					{
						if ($options['length'])
						{
							$string .= $value . '.length <= '
								. (int) $options['length'];
						}
					}
					if (!StringHelper::check($string))
					{
						$string .= $value . '.length <= 5';
					}
				}
				break;
			case 12: // Exact Length
				// only 4 text_field
				if (CFactory::_('Field.Groups')->check($type, 'text'))
				{
					if (ArrayHelper::check($options))
					{
						if ($options['length'])
						{
							$string .= $value . '.length == '
								. (int) $options['length'];
						}
					}
					if (!StringHelper::check($string))
					{
						$string .= $value . '.length == 5';
					}
				}
				break;
		}
		if (!StringHelper::check($string))
		{
			$string = 0;
		}

		return $string;
	}

	public function getOptionsScript($type, $options)
	{
		$buket = [];
		if (StringHelper::check($options))
		{
			if (CFactory::_('Field.Groups')->check($type, 'list')
				|| CFactory::_('Field.Groups')->check($type, 'dynamic')
				|| !CFactory::_('Field.Groups')->check($type))
			{
				$optionsArray = array_map(
					'trim', (array) explode(PHP_EOL, (string) $options)
				);
				if (!ArrayHelper::check($optionsArray))
				{
					$optionsArray[] = $optionsArray;
				}
				foreach ($optionsArray as $option)
				{
					if (strpos($option, '|') !== false)
					{
						list($option) = array_map(
							'trim', (array) explode('|', $option)
						);
					}
					if ($option != 'dynamic_list')
					{
						// add option to return buket
						$buket[] = $option;
					}
				}
			}
			elseif (CFactory::_('Field.Groups')->check($type, 'text'))
			{
				// check to get the key words if set
				$keywords = GetHelper::between(
					$options, 'keywords="', '"'
				);
				if (StringHelper::check($keywords))
				{
					if (strpos((string) $keywords, ',') !== false)
					{
						$keywords = array_map(
							'trim', (array) explode(',', (string) $keywords)
						);
						foreach ($keywords as $keyword)
						{
							$buket['keywords'][] = trim($keyword);
						}
					}
					else
					{
						$buket['keywords'][] = trim((string) $keywords);
					}
				}
				// check to ket string length if set
				$length = GetHelper::between(
					$options, 'length="', '"'
				);
				if (StringHelper::check($length))
				{
					$buket['length'] = $length;
				}
				else
				{
					$buket['length'] = false;
				}
			}
		}

		return $buket;
	}

	public function getValueScript($type, $name, $extends, $unique)
	{
		$select  = '';
		$isArray = false;
		$keyName = $name . '_' . $unique;
		if ($type === 'checkboxes' || $extends === 'checkboxes')
		{
			$select  = "var " . $keyName . " = [];" . PHP_EOL . Indent::_(1)
				. "jQuery('#jform_" . $name
				. " input[type=checkbox]').each(function()" . PHP_EOL
				. Indent::_(1) . "{" . PHP_EOL . Indent::_(2)
				. "if (jQuery(this).is(':checked'))" . PHP_EOL . Indent::_(2)
				. "{" . PHP_EOL . Indent::_(3) . $keyName
				. ".push(jQuery(this).prop('value'));" . PHP_EOL . Indent::_(2)
				. "}" . PHP_EOL . Indent::_(1) . "});";
			$isArray = true;
		}
		elseif ($type === 'checkbox')
		{
			$select = 'var ' . $keyName . ' = jQuery("#jform_' . $name
				. '").prop(\'checked\');';
		}
		elseif ($type === 'radio')
		{
			$select = 'var ' . $keyName . ' = jQuery("#jform_' . $name
				. ' input[type=\'radio\']:checked").val();';
		}
		elseif (CFactory::_('Compiler.Builder.Script.User.Switch')->inArray($type))
		{
			// this is only since 3.3.4
			$select = 'var ' . $keyName . ' = jQuery("#jform_' . $name
				. '_id").val();';
		}
		elseif ($type === 'list'
			|| CFactory::_('Field.Groups')->check(
				$type, 'dynamic'
			)
			|| !CFactory::_('Field.Groups')->check($type))
		{
			$select  = 'var ' . $keyName . ' = jQuery("#jform_' . $name
				. '").val();';
			$isArray = true;
		}
		elseif (CFactory::_('Field.Groups')->check($type, 'text'))
		{
			$select = 'var ' . $keyName . ' = jQuery("#jform_' . $name
				. '").val();';
		}

		return array('get' => $select, 'isArray' => $isArray);
	}

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

	public function setViewScript(&$view, $type)
	{
		if (isset($this->viewScriptBuilder[$view])
			&& isset($this->viewScriptBuilder[$view][$type]))
		{
			return $this->viewScriptBuilder[$view][$type];
		}

		return '';
	}

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

	public function setAjaxToke(&$view)
	{
		$fix = '';
		if (isset(CFactory::_('Customcode.Dispenser')->hub['token'][$view])
			&& CFactory::_('Customcode.Dispenser')->hub['token'][$view])
		{
			$fix .= PHP_EOL . Indent::_(2) . "//" . Line::_(__Line__, __Class__)
				. " Add Ajax Token";

			if (CFactory::_('Config')->get('joomla_version', 3) == 3)
			{
				$fix .= PHP_EOL . Indent::_(2)
					. "\$this->getDocument()->addScriptDeclaration(\"var token = '\" . Joomla__"."_5ba38513_5c4f_4b0d_935e_49e986a6bce8___Power::getFormToken() . \"';\");";
			}
			else
			{
				$fix .= PHP_EOL . Indent::_(2)
					. "\$this->getDocument()->getWebAssetManager()->addInlineScript(\"var token = '\" . Joomla__"."_5ba38513_5c4f_4b0d_935e_49e986a6bce8___Power::getFormToken() . \"';\");";
			}
		}

		return $fix;
	}

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

	public function setAjaxInputReturn($target)
	{
		$cases = '';
		if (isset(CFactory::_('Customcode.Dispenser')->hub[$target]['ajax_controller'])
			&& ArrayHelper::check(
				CFactory::_('Customcode.Dispenser')->hub[$target]['ajax_controller']
			))
		{
			$input      = [];
			$valueArray = [];
			$ifArray    = [];
			$getModel   = [];
			$userCheck  = [];
			$prefix     = ($target == 'site') ? 'Site':'Administrator';
			$isJoomla3  = (CFactory::_('Config')->get('joomla_version', 3) == 3);
			$failed     = "false";
			if (!$isJoomla3)
			{
				$failed = "['error' => 'There was an error! [149]']";
			}
			foreach (
				CFactory::_('Customcode.Dispenser')->hub[$target]['ajax_controller'] as $view
			)
			{
				foreach ($view as $task)
				{
					$input[$task['task_name']][]      = "\$"
						. $task['value_name'] . "Value = \$jinput->get('"
						. $task['value_name'] . "', " . $task['input_default']
						. ", '" . $task['input_filter'] . "');";
					$valueArray[$task['task_name']][] = "\$"
						. $task['value_name'] . "Value";
					$getModel[$task['task_name']] =
						"\$result = \$ajaxModule->"
						. $task['method_name'] . "(" . Placefix::_("valueArray") . ");";
					// check if null or zero is allowed
					if (!isset($task['allow_zero']) || 1 != $task['allow_zero'])
					{
						$ifArray[$task['task_name']][] = "\$"
							. $task['value_name'] . "Value";
					}
					// see user check is needed
					if (!isset($userCheck[$task['task_name']])
						&& isset($task['user_check'])
						&& 1 == $task['user_check'])
					{
						// add it since this means it was not set, and in the old method we assumed it was inplace
						// or it is set and 1 means we still want it inplace
						$ifArray[$task['task_name']][] = '$user->id != 0';
						// add it only once
						$userCheck[$task['task_name']] = true;
					}
				}
			}
			if (ArrayHelper::check($getModel))
			{
				foreach ($getModel as $task => $getMethod)
				{
					$cases .= PHP_EOL . Indent::_(4) . "case '" . $task . "':";
					$cases .= PHP_EOL . Indent::_(5) . "try";
					$cases .= PHP_EOL . Indent::_(5) . "{";
					foreach ($input[$task] as $string)
					{
						$cases .= PHP_EOL . Indent::_(6) . $string;
					}
					// set the values
					$values = implode(', ', $valueArray[$task]);
					// set the values to method
					$getMethod = str_replace(
						Placefix::_('valueArray'), $values,
						$getMethod
					);
					// check if we have some values to check
					if (isset($ifArray[$task])
						&& ArrayHelper::check($ifArray[$task]))
					{
						// set if string
						$ifvalues = implode(' && ', $ifArray[$task]);
						// add to case
						$cases .= PHP_EOL . Indent::_(6) . "if(" . $ifvalues
							. ")";
						$cases .= PHP_EOL . Indent::_(6) . "{";
						if ($isJoomla3)
						{
							$cases .= PHP_EOL . Indent::_(7) . "\$ajaxModule = \$this->getModel('ajax');";
						}
						else
						{
							$cases .= PHP_EOL . Indent::_(7) . "\$ajaxModule = \$this->getModel('ajax', '$prefix');";
						}
						$cases .= PHP_EOL . Indent::_(7) . "if (\$ajaxModule)";
						$cases .= PHP_EOL . Indent::_(7) . "{";
						$cases .= PHP_EOL . Indent::_(8) . $getMethod;
						$cases .= PHP_EOL . Indent::_(7) . "}";
						$cases .= PHP_EOL . Indent::_(7) . "else";
						$cases .= PHP_EOL . Indent::_(7) . "{";
						$cases .= PHP_EOL . Indent::_(8) . "\$result = $failed;";
						$cases .= PHP_EOL . Indent::_(7) . "}";
						$cases .= PHP_EOL . Indent::_(6) . "}";
						$cases .= PHP_EOL . Indent::_(6) . "else";
						$cases .= PHP_EOL . Indent::_(6) . "{";
						$cases .= PHP_EOL . Indent::_(7) . "\$result = $failed;";
						$cases .= PHP_EOL . Indent::_(6) . "}";
					}
					else
					{
						if ($isJoomla3)
						{
							$cases .= PHP_EOL . Indent::_(6) . "\$ajaxModule = \$this->getModel('ajax');";
						}
						else
						{
							$cases .= PHP_EOL . Indent::_(6) . "\$ajaxModule = \$this->getModel('ajax', '$prefix');";
						}
						$cases .= PHP_EOL . Indent::_(6) . "if (\$ajaxModule)";
						$cases .= PHP_EOL . Indent::_(6) . "{";
						$cases .= PHP_EOL . Indent::_(7) . $getMethod;
						$cases .= PHP_EOL . Indent::_(6) . "}";
						$cases .= PHP_EOL . Indent::_(6) . "else";
						$cases .= PHP_EOL . Indent::_(6) . "{";
						$cases .= PHP_EOL . Indent::_(7) . "\$result = $failed;";
						$cases .= PHP_EOL . Indent::_(6) . "}";
					}
					// continue the build
					$cases .= PHP_EOL . Indent::_(6)
						. "if(\$callback)";
					$cases .= PHP_EOL . Indent::_(6) . "{";
					$cases .= PHP_EOL . Indent::_(7)
						. "echo \$callback . \"(\".json_encode(\$result).\");\";";
					$cases .= PHP_EOL . Indent::_(6) . "}";
					$cases .= PHP_EOL . Indent::_(6) . "elseif(\$returnRaw)";
					$cases .= PHP_EOL . Indent::_(6) . "{";
					$cases .= PHP_EOL . Indent::_(7)
						. "echo json_encode(\$result);";
					$cases .= PHP_EOL . Indent::_(6) . "}";
					$cases .= PHP_EOL . Indent::_(6) . "else";
					$cases .= PHP_EOL . Indent::_(6) . "{";
					$cases .= PHP_EOL . Indent::_(7)
						. "echo \"(\".json_encode(\$result).\");\";";
					$cases .= PHP_EOL . Indent::_(6) . "}";
					$cases .= PHP_EOL . Indent::_(5) . "}";
					$cases .= PHP_EOL . Indent::_(5) . "catch(\Exception \$e)";
					$cases .= PHP_EOL . Indent::_(5) . "{";
					$cases .= PHP_EOL . Indent::_(6)
						. "if(\$callback)";
					$cases .= PHP_EOL . Indent::_(6) . "{";
					$cases .= PHP_EOL . Indent::_(7)
						. "echo \$callback.\"(\".json_encode(\$e).\");\";";
					$cases .= PHP_EOL . Indent::_(6) . "}";
					$cases .= PHP_EOL . Indent::_(6)
						. "elseif(\$returnRaw)";
					$cases .= PHP_EOL . Indent::_(6) . "{";
					$cases .= PHP_EOL . Indent::_(7)
						. "echo json_encode(\$e);";
					$cases .= PHP_EOL . Indent::_(6) . "}";
					$cases .= PHP_EOL . Indent::_(6) . "else";
					$cases .= PHP_EOL . Indent::_(6) . "{";
					$cases .= PHP_EOL . Indent::_(7)
						. "echo \"(\".json_encode(\$e).\");\";";
					$cases .= PHP_EOL . Indent::_(6) . "}";
					$cases .= PHP_EOL . Indent::_(5) . "}";
					$cases .= PHP_EOL . Indent::_(4) . "break;";
				}
			}
		}

		return $cases;
	}

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
	 */
	public function setFilterFieldSidebarDisplayHelper(&$nameSingleCode, &$nameListCode)
	{
		// temp fix
		if (CFactory::_('Config')->get('joomla_version', 3) != 3)
		{
			return '';
		}

		// start the filter bucket
		$fieldFilters = [];
		// add the default filter
		$this->setDefaultSidebarFilterHelper(
			$fieldFilters, $nameSingleCode, $nameListCode
		);
		// add the category filter stuff
		$this->setCategorySidebarFilterHelper($fieldFilters, $nameListCode);
		// check if filter fields are added (1 = sidebar)
		if (CFactory::_('Compiler.Builder.Admin.Filter.Type')->get($nameListCode, 1) == 1
			&& CFactory::_('Compiler.Builder.Filter')->exists($nameListCode))
		{
			// get component name
			$Component = CFactory::_('Compiler.Builder.Content.One')->get('Component');
			// load the rest of the filters
			foreach (CFactory::_('Compiler.Builder.Filter')->get($nameListCode) as $filter)
			{
				if ($filter['type'] != 'category'
					&& ArrayHelper::check($filter['custom'])
					&& $filter['custom']['extends'] !== 'user')
				{
					$CodeName       = StringHelper::safe(
						$filter['code'] . ' ' . $filter['custom']['text'], 'W'
					);
					$codeName       = $filter['code']
						. StringHelper::safe(
							$filter['custom']['text'], 'F'
						);
					$type           = StringHelper::safe(
						$filter['custom']['type'], 'F'
					);
					$fieldFilters[] = PHP_EOL . Indent::_(2) . "//"
						. Line::_(__Line__, __Class__) . " Set " . $CodeName
						. " Selection";
					$fieldFilters[] = Indent::_(2) . "\$this->" . $codeName
						. "Options = FormHelper::loadFieldType('" . $type
						. "')->options;";
					$fieldFilters[] = Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " We do some sanitation for " . $CodeName
						. " filter";
					$fieldFilters[] = Indent::_(2) . "if ("
						. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$this->" . $codeName
						. "Options) &&";
					$fieldFilters[] = Indent::_(3) . "isset(\$this->"
						. $codeName
						. "Options[0]->value) &&";
					$fieldFilters[] = Indent::_(3) . "!"
						. "Super_" . "__1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check(\$this->" . $codeName
						. "Options[0]->value))";
					$fieldFilters[] = Indent::_(2) . "{";
					$fieldFilters[] = Indent::_(3) . "unset(\$this->"
						. $codeName
						. "Options[0]);";
					$fieldFilters[] = Indent::_(2) . "}";
					$fieldFilters[] = Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Only load " . $CodeName
						. " filter if it has values";
					$fieldFilters[] = Indent::_(2) . "if ("
						. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$this->" . $codeName
						. "Options))";
					$fieldFilters[] = Indent::_(2) . "{";
					$fieldFilters[] = Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " " . $CodeName . " Filter";
					$fieldFilters[] = Indent::_(3) . "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(";
					$fieldFilters[] = Indent::_(4) . "'- Select ' . Text:"
						. ":_('" . $filter['lang'] . "') . ' -',";
					$fieldFilters[] = Indent::_(4) . "'filter_"
						. $filter['code']
						. "',";
					$fieldFilters[] = Indent::_(4)
						. "Html::_('select.options', \$this->" . $codeName
						. "Options, 'value', 'text', \$this->state->get('filter."
						. $filter['code'] . "'))";
					$fieldFilters[] = Indent::_(3) . ");";
					$fieldFilters[] = Indent::_(2) . "}";
				}
				elseif ($filter['type'] != 'category')
				{
					$Codename = StringHelper::safe(
						$filter['code'], 'W'
					);
					if (isset($filter['custom'])
						&& ArrayHelper::check($filter['custom'])
						&& $filter['custom']['extends'] === 'user')
					{
						$functionName = "\$this->getThe" . $filter['function']
							. StringHelper::safe(
								$filter['custom']['text'], 'F'
							) . "Selections();";
					}
					else
					{
						$functionName = "\$this->getThe" . $filter['function']
							. "Selections();";
					}
					$fieldFilters[] = PHP_EOL . Indent::_(2) . "//"
						. Line::_(__Line__, __Class__) . " Set " . $Codename
						. " Selection";
					$fieldFilters[] = Indent::_(2) . "\$this->"
						. $filter['code']
						. "Options = " . $functionName;
					$fieldFilters[] = Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " We do some sanitation for " . $Codename
						. " filter";
					$fieldFilters[] = Indent::_(2) . "if ("
						. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$this->" . $filter['code']
						. "Options) &&";
					$fieldFilters[] = Indent::_(3) . "isset(\$this->"
						. $filter['code'] . "Options[0]->value) &&";
					$fieldFilters[] = Indent::_(3) . "!"
						. "Super_" . "__1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check(\$this->" . $filter['code']
						. "Options[0]->value))";
					$fieldFilters[] = Indent::_(2) . "{";
					$fieldFilters[] = Indent::_(3) . "unset(\$this->"
						. $filter['code'] . "Options[0]);";
					$fieldFilters[] = Indent::_(2) . "}";
					$fieldFilters[] = Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Only load " . $Codename
						. " filter if it has values";
					$fieldFilters[] = Indent::_(2) . "if ("
						. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$this->" . $filter['code']
						. "Options))";
					$fieldFilters[] = Indent::_(2) . "{";
					$fieldFilters[] = Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " " . $Codename . " Filter";
					$fieldFilters[] = Indent::_(3) . "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(";
					$fieldFilters[] = Indent::_(4) . "'- Select '.Text:"
						. ":_('" . $filter['lang'] . "').' -',";
					$fieldFilters[] = Indent::_(4) . "'filter_"
						. $filter['code']
						. "',";
					$fieldFilters[] = Indent::_(4)
						. "Html::_('select.options', \$this->"
						. $filter['code']
						. "Options, 'value', 'text', \$this->state->get('filter."
						. $filter['code'] . "'))";
					$fieldFilters[] = Indent::_(3) . ");";

					$fieldFilters[] = Indent::_(2) . "}";
				}
			}
		}
		// did we find filters
		if (ArrayHelper::check($fieldFilters))
		{
			// return the filter
			return PHP_EOL . implode(PHP_EOL, $fieldFilters);
		}

		return '';
	}

	/**
	 * add default filter helper
	 *
	 * @param   array   $filter          The batch code array
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  void
	 *
	 */
	protected function setDefaultSidebarFilterHelper(&$filter, &$nameSingleCode,
	                                                 &$nameListCode
	)
	{
		// add the default filters if we are on the old filter paths (1 = sidebar)
		if (CFactory::_('Compiler.Builder.Admin.Filter.Type')->get($nameListCode, 1) == 1)
		{
			// set batch
			$filter[] = PHP_EOL . Indent::_(2)
				. "//" . Line::_(__Line__, __Class__)
				. " Only load publish filter if state change is allowed";
			$filter[] = Indent::_(2)
				. "if (\$this->canState)";
			$filter[] = Indent::_(2) . "{";
			$filter[] = Indent::_(3) . "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(";
			$filter[] = Indent::_(4) . "Text:"
				. ":_('JOPTION_SELECT_PUBLISHED'),";
			$filter[] = Indent::_(4) . "'filter_published',";
			$filter[] = Indent::_(4)
				. "Html::_('select.options', Html::_('jgrid.publishedOptions'), 'value', 'text', \$this->state->get('filter.published'), true)";
			$filter[] = Indent::_(3) . ");";
			$filter[] = Indent::_(2) . "}";
			// check if view has access
			if (CFactory::_('Compiler.Builder.Access.Switch')->exists($nameSingleCode)
				&& !CFactory::_('Compiler.Builder.Field.Names')->isString($nameSingleCode . '.access'))
			{
				$filter[] = PHP_EOL . Indent::_(2) . "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(";
				$filter[] = Indent::_(3) . "Text:"
					. ":_('JOPTION_SELECT_ACCESS'),";
				$filter[] = Indent::_(3) . "'filter_access',";
				$filter[] = Indent::_(3)
					. "Html::_('select.options', Html::_('access.assetgroups'), 'value', 'text', \$this->state->get('filter.access'))";
				$filter[] = Indent::_(2) . ");";
			}
		}
	}

	/**
	 * build category sidebar display filter helper
	 *
	 * @param   array   $filter        The filter code array
	 * @param   string  $nameListCode  The list view name
	 *
	 * @return  void
	 *
	 */
	protected function setCategorySidebarFilterHelper(&$filter, &$nameListCode)
	{
		// add the category filter if we are on the old filter paths (1 = sidebar)
		if (CFactory::_('Compiler.Builder.Admin.Filter.Type')->get($nameListCode, 1) == 1
			&& CFactory::_('Compiler.Builder.Category')->exists("{$nameListCode}.extension")
			&& CFactory::_('Compiler.Builder.Category')->get("{$nameListCode}.filter", 0) >= 1)
		{
			// set filter
			$filter[] = PHP_EOL . Indent::_(2) . "//"
				. Line::_(__Line__, __Class__) . " Category Filter.";
			$filter[] = Indent::_(2) . "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(";
			$filter[] = Indent::_(3) . "Text:"
				. ":_('JOPTION_SELECT_CATEGORY'),";
			$filter[] = Indent::_(3) . "'filter_category_id',";
			$filter[] = Indent::_(3)
				. "Html::_('select.options', Html::_('category.options', '"
				. CFactory::_('Compiler.Builder.Category')->get("{$nameListCode}.extension")
				. "'), 'value', 'text', \$this->state->get('filter.category_id'))";
			$filter[] = Indent::_(2) . ");";
		}
	}

	/**
	 * build batch loading helper scripts
	 *
	 * @param   string  $nameSingleCode  The single view name
	 * @param   string  $nameListCode    The list view name
	 *
	 * @return  string The php to place in view.html.php
	 *
	 */
	public function setBatchDisplayHelper(&$nameSingleCode, &$nameListCode)
	{
		// temp fix
		if (CFactory::_('Config')->get('joomla_version', 3) != 3)
		{
			return '';
		}

		// start the batch bucket
		$fieldBatch = [];
		// add the default batch
		$this->setDefaultBatchHelper($fieldBatch, $nameSingleCode);
		// add the category filter stuff
		$this->setCategoryBatchHelper($fieldBatch, $nameListCode);
		// check if we have other batch options to add
		if (CFactory::_('Compiler.Builder.Filter')->exists($nameListCode))
		{
			// check if we should add some help to get the values (2 = topbar)
			$get_values = false;
			if (CFactory::_('Compiler.Builder.Admin.Filter.Type')->get($nameListCode, 1) == 2)
			{
				// since the old path is not used, we need to add those values here
				$get_values = true;
			}
			// get component name
			$Component = CFactory::_('Compiler.Builder.Content.One')->get('Component');
			// load the rest of the batch options
			foreach (CFactory::_('Compiler.Builder.Filter')->get($nameListCode) as $filter)
			{
				if ($filter['type'] != 'category'
					&& ArrayHelper::check($filter['custom'])
					&& $filter['custom']['extends'] !== 'user')
				{
					$CodeName     = StringHelper::safe(
						$filter['code'] . ' ' . $filter['custom']['text'], 'W'
					);
					$codeName     = $filter['code']
						. StringHelper::safe(
							$filter['custom']['text'], 'F'
						);
					$fieldBatch[] = PHP_EOL . Indent::_(2)
						. "//" . Line::_(__Line__, __Class__)
						. " Only load " . $CodeName
						. " batch if create, edit, and batch is allowed";
					$fieldBatch[] = Indent::_(2)
						. "if (\$this->canBatch && \$this->canCreate && \$this->canEdit)";
					$fieldBatch[] = Indent::_(2) . "{";
					// add the get values here
					if ($get_values)
					{
						$type         = StringHelper::safe(
							$filter['custom']['type'], 'F'
						);
						$fieldBatch[] = Indent::_(3) . "//"
							. Line::_(__Line__, __Class__) . " Set " . $CodeName
							. " Selection";
						$fieldBatch[] = Indent::_(3) . "\$this->" . $codeName
							. "Options = FormHelper::loadFieldType('" . $type
							. "')->options;";
						$fieldBatch[] = Indent::_(3) . "//" . Line::_(
								__LINE__,__CLASS__
							) . " We do some sanitation for " . $CodeName
							. " filter";
						$fieldBatch[] = Indent::_(3) . "if ("
							. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$this->" . $codeName
							. "Options) &&";
						$fieldBatch[] = Indent::_(4) . "isset(\$this->"
							. $codeName
							. "Options[0]->value) &&";
						$fieldBatch[] = Indent::_(4) . "!"
							. "Super_" . "__1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check(\$this->" . $codeName
							. "Options[0]->value))";
						$fieldBatch[] = Indent::_(3) . "{";
						$fieldBatch[] = Indent::_(4) . "unset(\$this->"
							. $codeName
							. "Options[0]);";
						$fieldBatch[] = Indent::_(3) . "}";
					}
					$fieldBatch[] = Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " " . $CodeName . " Batch Selection";
					$fieldBatch[] = Indent::_(3)
						. "JHtmlBatch_::addListSelection(";
					$fieldBatch[] = Indent::_(4) . "'- Keep Original '.Text:"
						. ":_('" . $filter['lang'] . "').' -',";
					$fieldBatch[] = Indent::_(4) . "'batch[" . $filter['code']
						. "]',";
					$fieldBatch[] = Indent::_(4)
						. "Html::_('select.options', \$this->" . $codeName
						. "Options, 'value', 'text')";
					$fieldBatch[] = Indent::_(3) . ");";
					$fieldBatch[] = Indent::_(2) . "}";
				}
				elseif ($filter['type'] != 'category')
				{
					$CodeName = StringHelper::safe(
						$filter['code'], 'W'
					);

					$fieldBatch[] = PHP_EOL . Indent::_(2)
						. "//" . Line::_(__Line__, __Class__)
						. " Only load " . $CodeName
						. " batch if create, edit, and batch is allowed";
					$fieldBatch[] = Indent::_(2)
						. "if (\$this->canBatch && \$this->canCreate && \$this->canEdit)";
					$fieldBatch[] = Indent::_(2) . "{";
					// add the get values here
					if ($get_values)
					{
						$fieldBatch[] = Indent::_(3) . "//"
							. Line::_(__Line__, __Class__) . " Set " . $CodeName
							. " Selection";
						$fieldBatch[] = Indent::_(3) . "\$this->"
							. $filter['code']
							. "Options = FormHelper::loadFieldType('"
							. $filter['filter_type']
							. "')->options;";
						$fieldBatch[] = Indent::_(3) . "//" . Line::_(
								__LINE__,__CLASS__
							) . " We do some sanitation for " . $CodeName
							. " filter";
						$fieldBatch[] = Indent::_(3) . "if ("
							. "Super_" . "__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check(\$this->" . $filter['code']
							. "Options) &&";
						$fieldBatch[] = Indent::_(4) . "isset(\$this->"
							. $filter['code'] . "Options[0]->value) &&";
						$fieldBatch[] = Indent::_(4) . "!"
							. "Super_" . "__1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check(\$this->" . $filter['code']
							. "Options[0]->value))";
						$fieldBatch[] = Indent::_(3) . "{";
						$fieldBatch[] = Indent::_(4) . "unset(\$this->"
							. $filter['code'] . "Options[0]);";
						$fieldBatch[] = Indent::_(3) . "}";
					}
					$fieldBatch[] = Indent::_(3) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " " . $CodeName . " Batch Selection";
					$fieldBatch[] = Indent::_(3)
						. "JHtmlBatch_::addListSelection(";
					$fieldBatch[] = Indent::_(4) . "'- Keep Original '.Text:"
						. ":_('" . $filter['lang'] . "').' -',";
					$fieldBatch[] = Indent::_(4) . "'batch[" . $filter['code']
						. "]',";
					$fieldBatch[] = Indent::_(4)
						. "Html::_('select.options', \$this->"
						. $filter['code'] . "Options, 'value', 'text')";
					$fieldBatch[] = Indent::_(3) . ");";
					$fieldBatch[] = Indent::_(2) . "}";
				}
			}
		}
		// did we find batch options
		if (ArrayHelper::check($fieldBatch))
		{
			// return the batch
			return PHP_EOL . implode(PHP_EOL, $fieldBatch);
		}

		return '';
	}

	/**
	 * add default batch helper
	 *
	 * @param   array   $batch           The batch code array
	 * @param   string  $nameSingleCode  The single view name
	 *
	 * @return  void
	 *
	 */
	protected function setDefaultBatchHelper(&$batch, &$nameSingleCode)
	{
		// set component name
		$COPMONENT = CFactory::_('Component')->get('name_code');
		$COPMONENT = StringHelper::safe(
			$COPMONENT, 'U'
		);
		// set batch
		$batch[] = PHP_EOL . Indent::_(2)
			. "//" . Line::_(__Line__, __Class__)
			. " Only load published batch if state and batch is allowed";
		$batch[] = Indent::_(2)
			. "if (\$this->canState && \$this->canBatch)";
		$batch[] = Indent::_(2) . "{";
		$batch[] = Indent::_(3) . "JHtmlBatch_::addListSelection(";
		$batch[] = Indent::_(4) . "Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_" . $COPMONENT
			. "_KEEP_ORIGINAL_STATE'),";
		$batch[] = Indent::_(4) . "'batch[published]',";
		$batch[] = Indent::_(4)
			. "Html::_('select.options', Html::_('jgrid.publishedOptions', array('all' => false)), 'value', 'text', '', true)";
		$batch[] = Indent::_(3) . ");";
		$batch[] = Indent::_(2) . "}";
		// check if view has access
		if (CFactory::_('Compiler.Builder.Access.Switch')->exists($nameSingleCode)
			&& !CFactory::_('Compiler.Builder.Field.Names')->isString($nameSingleCode . '.access'))
		{
			$batch[] = PHP_EOL . Indent::_(2)
				. "//" . Line::_(__Line__, __Class__)
				. " Only load access batch if create, edit and batch is allowed";
			$batch[] = Indent::_(2)
				. "if (\$this->canBatch && \$this->canCreate && \$this->canEdit)";
			$batch[] = Indent::_(2) . "{";
			$batch[] = Indent::_(3) . "JHtmlBatch_::addListSelection(";
			$batch[] = Indent::_(4) . "Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_" . $COPMONENT
				. "_KEEP_ORIGINAL_ACCESS'),";
			$batch[] = Indent::_(4) . "'batch[access]',";
			$batch[] = Indent::_(4)
				. "Html::_('select.options', Html::_('access.assetgroups'), 'value', 'text')";
			$batch[] = Indent::_(3) . ");";
			$batch[] = Indent::_(2) . "}";
		}
	}

	/**
	 * build category batch helper
	 *
	 * @param   array   $batch         The batch code array
	 * @param   string  $nameListCode  The list view name
	 *
	 * @return  mixed The php to place in view.html.php
	 *
	 */
	protected function setCategoryBatchHelper(&$batch, &$nameListCode)
	{
		if (CFactory::_('Compiler.Builder.Category')->exists("{$nameListCode}.extension"))
		{
			// set component name
			$COPMONENT = CFactory::_('Component')->get('name_code');
			$COPMONENT = StringHelper::safe($COPMONENT, 'U');
			// set filter
			$batch[] = PHP_EOL . Indent::_(2)
				. "if (\$this->canBatch && \$this->canCreate && \$this->canEdit)";
			$batch[] = Indent::_(2) . "{";
			$batch[] = Indent::_(3) . "//" . Line::_(__Line__, __Class__)
				. " Category Batch selection.";
			$batch[] = Indent::_(3) . "JHtmlBatch_::addListSelection(";
			$batch[] = Indent::_(4) . "Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_" . $COPMONENT
				. "_KEEP_ORIGINAL_CATEGORY'),";
			$batch[] = Indent::_(4) . "'batch[category]',";
			$batch[] = Indent::_(4)
				. "Html::_('select.options', Html::_('category.options', '"
				. CFactory::_('Compiler.Builder.Category')->get("{$nameListCode}.extension")
				. "'), 'value', 'text')";
			$batch[] = Indent::_(3) . ");";
			$batch[] = Indent::_(2) . "}";
		}
	}

	public function setRouterCategoryViews($nameSingleCode, $nameListCode)
	{
		if (CFactory::_('Compiler.Builder.Category')->exists("{$nameListCode}.extension"))
		{
			// get the actual extension
			$_extension = CFactory::_('Compiler.Builder.Category')->get("{$nameListCode}.extension");
			$_extension = explode('.', (string) $_extension);
			// set component name
			if (ArrayHelper::check($_extension))
			{
				$component = str_replace('com_', '', $_extension[0]);
			}
			else
			{
				$component = CFactory::_('Config')->component_code_name;
			}
			// check if category has another name
			$otherViews = CFactory::_('Compiler.Builder.Category.Other.Name')->
				get($nameListCode . '.views', $nameListCode);
			$otherView  = CFactory::_('Compiler.Builder.Category.Other.Name')->
				get($nameListCode . '.view', $nameSingleCode);
			// set the OtherView value
			CFactory::_('Compiler.Builder.Content.Multi')->set('category' . $otherView . '|otherview', $otherView);
			// load the category helper details in not already loaded
			if (!CFactory::_('Compiler.Builder.Content.Multi')->exists('category' . $otherView . '|view'))
			{
				// lets also set the category helper for this view
				$target = array('site' => 'category' . $otherView);
				CFactory::_('Utilities.Structure')->build($target, 'category');
				// insure the file gets updated
				CFactory::_('Compiler.Builder.Content.Multi')->set('category' . $otherView . '|view', $otherView);
				CFactory::_('Compiler.Builder.Content.Multi')->set('category' . $otherView . '|View', ucfirst((string) $otherView));
				CFactory::_('Compiler.Builder.Content.Multi')->set('category' . $otherView . '|views', $otherViews);
				CFactory::_('Compiler.Builder.Content.Multi')->set('category' . $otherView . '|Views', ucfirst((string) $otherViews));
				// set script to global helper file
				$includeHelper   = [];
				$includeHelper[] = "\n//" . Line::_(__Line__, __Class__)
					. "Insure this view category file is loaded.";
				$includeHelper[] = "\$classname = '" . ucfirst((string) $component)
					. ucfirst((string) $otherView) . "Categories';";
				$includeHelper[] = "if (!class_exists(\$classname))";
				$includeHelper[] = "{";
				$includeHelper[] = Indent::_(1)
					. "\$path = JPATH_SITE . '/components/com_" . $component
					. "/helpers/category" . $otherView . ".php';";
				$includeHelper[] = Indent::_(1) . "if (is_file(\$path))";
				$includeHelper[] = Indent::_(1) . "{";
				$includeHelper[] = Indent::_(2) . "include_once \$path;";
				$includeHelper[] = Indent::_(1) . "}";
				$includeHelper[] = "}";
				CFactory::_('Compiler.Builder.Content.One')->add('CATEGORY_CLASS_TREES', implode("\n", $includeHelper));
			}
			// return category view string
			if (CFactory::_('Compiler.Builder.Content.One')->exists('ROUTER_CATEGORY_VIEWS')
				&& StringHelper::check(
					CFactory::_('Compiler.Builder.Content.One')->get('ROUTER_CATEGORY_VIEWS')
				))
			{
				return "," . PHP_EOL . Indent::_(3) . '"'
					. CFactory::_('Compiler.Builder.Category')->get("{$nameListCode}.extension")
					. '" => "' . $otherView . '"';
			}
			else
			{
				return PHP_EOL . Indent::_(3) . '"'
					. CFactory::_('Compiler.Builder.Category')->get("{$nameListCode}.extension")
					. '" => "' . $otherView . '"';
			}
		}

		return '';
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

	public function setRouterCase($viewsCodeName)
	{
		if (strlen((string) $viewsCodeName) > 0)
		{
			$router = PHP_EOL . Indent::_(2) . "case '" . $viewsCodeName . "':";
			$router .= PHP_EOL . Indent::_(3)
				. "\$id = explode(':', \$segments[\$count-1]);";
			$router .= PHP_EOL . Indent::_(3) . "\$vars['id'] = (int) \$id[0];";
			$router .= PHP_EOL . Indent::_(3) . "\$vars['view'] = '"
				. $viewsCodeName
				. "';";
			$router .= PHP_EOL . Indent::_(2) . "break;";

			return $router;
		}

		return '';
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

	public function setSubMenus()
	{
		if (CFactory::_('Component')->isArray('admin_views'))
		{
			$menus = '';
			// main lang prefix
			$lang = CFactory::_('Config')->lang_prefix . '_SUBMENU';
			// set the code name
			$codeName = CFactory::_('Config')->component_code_name;
			// set default dashboard
			if (!CFactory::_('Registry')->get('build.dashboard'))
			{
				$menus .= "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('" . $lang
					. "_DASHBOARD'), 'index.php?option=com_" . $codeName
					. "&view=" . $codeName . "', \$submenu === '" . $codeName
					. "');";
				CFactory::_('Language')->set(
					CFactory::_('Config')->lang_target, $lang . '_DASHBOARD', 'Dashboard'
				);
			}
			$catArray = [];
			// loop over all the admin views
			foreach (CFactory::_('Component')->get('admin_views') as $view)
			{
				// set custom menu
				$menus          .= $this->addCustomSubMenu(
					$view, $codeName, $lang
				);
				$nameSingleCode = $view['settings']->name_single_code;
				$nameListCode   = $view['settings']->name_list_code;
				$nameUpper      = StringHelper::safe(
					$view['settings']->name_list, 'U'
				);
				// check if view is set to be in the sub-menu
				if (isset($view['submenu']) && $view['submenu'] == 1)
				{
					// setup access defaults
					$tab      = "";
					$has_permissions = false;
					// check if the item has permissions.
					if (CFactory::_('Compiler.Creator.Permission')->globalExist($nameSingleCode, 'core.access'))
					{
						$menus .= PHP_EOL . Indent::_(2)
							. "if (\$user->authorise('"
							. CFactory::_('Compiler.Creator.Permission')->getGlobal($nameSingleCode, 'core.access')
							. "', 'com_" . $codeName
							. "') && \$user->authorise('" . $nameSingleCode
							. ".submenu', 'com_" . $codeName . "'))";
						$menus .= PHP_EOL . Indent::_(2) . "{";
						// add tab to lines to follow
						$tab = Indent::_(1);
						$has_permissions = true;
					}
					$menus .= PHP_EOL . Indent::_(2) . $tab
						. "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('" . $lang . "_"
						. $nameUpper . "'), 'index.php?option=com_" . $codeName
						. "&view=" . $nameListCode . "', \$submenu === '"
						. $nameListCode . "');";
					CFactory::_('Language')->set(
						CFactory::_('Config')->lang_target, $lang . "_" . $nameUpper,
						$view['settings']->name_list
					);
					// check if category has another name
					$otherViews = CFactory::_('Compiler.Builder.Category.Other.Name')->
						get($nameListCode . '.views', $nameListCode);
					// first check if category sub-menu should be added
					// then check if view has category, if true add sub-menu for it
					if ($view['settings']->add_category_submenu == 1
						&& CFactory::_('Compiler.Builder.Category')->exists("{$nameListCode}.extension")
						&& !in_array($otherViews, $catArray))
					{
						// get the extension array
						$_extension_array = (array) explode(
							'.',
							(string) CFactory::_('Compiler.Builder.Category')->get("{$nameListCode}.extension")
						);
						// set the menu selection
						if (isset($_extension_array[1]))
						{
							$_menu = "categories." . trim($_extension_array[1]);
						}
						else
						{
							$_menu = "categories";
						}
						// now load the menus
						$menus .= PHP_EOL . Indent::_(2) . $tab
							. "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('"
							. CFactory::_('Compiler.Builder.Category')->get("{$nameListCode}.name", 'error')
							. "'), 'index.php?option=com_categories&view=categories&extension="
							. CFactory::_('Compiler.Builder.Category')->get("{$nameListCode}.extension")
							. "', \$submenu === '" . $_menu . "');";
						// make sure we add a category only once
						$catArray[] = $otherViews;
					}
					// check if the item has permissions.
					if ($has_permissions)
					{
						$menus .= PHP_EOL . Indent::_(2) . "}";
					}
				}
				// set the Joomla custom fields options
				if (isset($view['joomla_fields'])
					&& $view['joomla_fields'] == 1)
				{
					$menus .= PHP_EOL . Indent::_(2)
						. "if (ComponentHelper::isEnabled('com_fields'))";
					$menus .= PHP_EOL . Indent::_(2) . "{";
					$menus .= PHP_EOL . Indent::_(3)
						. "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('" . $lang . "_"
						. $nameUpper
						. "_FIELDS'), 'index.php?option=com_fields&context=com_"
						. $codeName . "." . $nameSingleCode
						. "', \$submenu === 'fields.fields');";
					$menus .= PHP_EOL . Indent::_(3)
						. "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('" . $lang . "_"
						. $nameUpper
						. "_FIELDS_GROUPS'), 'index.php?option=com_fields&view=groups&context=com_"
						. $codeName . "." . $nameSingleCode
						. "', \$submenu === 'fields.groups');";
					$menus .= PHP_EOL . Indent::_(2) . "}";
					CFactory::_('Language')->set(
						CFactory::_('Config')->lang_target, $lang . "_" . $nameUpper . "_FIELDS",
						$view['settings']->name_list . ' Fields'
					);
					CFactory::_('Language')->set(
						CFactory::_('Config')->lang_target,
						$lang . "_" . $nameUpper . "_FIELDS_GROUPS",
						$view['settings']->name_list . ' Field Groups'
					);
					// build uninstall script for fields
					$this->uninstallScriptBuilder[$nameSingleCode] = 'com_'
						. $codeName . '.' . $nameSingleCode;
					$this->uninstallScriptFields[$nameSingleCode]
						= $nameSingleCode;
				}
			}
			if (isset($this->lastCustomSubMenu)
				&& ArrayHelper::check($this->lastCustomSubMenu))
			{
				foreach ($this->lastCustomSubMenu as $menu)
				{
					$menus .= $menu;
				}
				unset($this->lastCustomSubMenu);
			}

			return $menus;
		}

		return false;
	}

	public function addCustomSubMenu(&$view, &$codeName, &$lang)
	{
		// see if we should have custom menus
		$custom = '';
		if (CFactory::_('Component')->isArray('custom_admin_views'))
		{
			foreach (CFactory::_('Component')->get('custom_admin_views') as $nr => $menu)
			{
				if (!isset($this->customAdminAdded[$menu['settings']->code]))
				{
					if (($_custom = $this->setCustomAdminSubMenu(
							$view, $codeName, $lang, $nr, $menu, 'customView'
						)) !== false)
					{
						$custom .= $_custom;
					}
				}
			}
		}
		if (CFactory::_('Component')->isArray('custommenus'))
		{
			foreach (CFactory::_('Component')->get('custommenus') as $nr => $menu)
			{
				if (($_custom = $this->setCustomAdminSubMenu(
						$view, $codeName, $lang, $nr, $menu, 'customMenu'
					)) !== false)
				{
					$custom .= $_custom;
				}
			}
		}

		return $custom;
	}

	public function setCustomAdminSubMenu(&$view, &$codeName, &$lang, &$nr, &$menu, $type)
	{
		if ($type === 'customMenu')
		{
			$name       = $menu['name'];
			$nameSingle = StringHelper::safe($menu['name']);
			$nameList   = StringHelper::safe($menu['name']);
			$nameUpper  = StringHelper::safe(
				$menu['name'], 'U'
			);
		}
		elseif ($type === 'customView')
		{
			$name       = $menu['settings']->name;
			$nameSingle = $menu['settings']->code;
			$nameList   = $menu['settings']->code;
			$nameUpper  = $menu['settings']->CODE;
		}
		if (isset($menu['submenu']) && $menu['submenu'] == 1
			&& $view['adminview'] == $menu['before'])
		{
			// setup access defaults
			$tab = "";
			$custom = '';
			// check if the item has permissions.
			if (CFactory::_('Compiler.Creator.Permission')->globalExist($nameSingle, 'core.access'))
			{
				$custom .= PHP_EOL . Indent::_(2) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Access control (" . CFactory::_('Compiler.Creator.Permission')->getGlobal($nameSingle, 'core.access') . " && "
					. $nameSingle . ".submenu).";
				$custom .= PHP_EOL . Indent::_(2) . "if (\$user->authorise('"
					. CFactory::_('Compiler.Creator.Permission')->getGlobal($nameSingle, 'core.access') . "', 'com_" . $codeName
					. "') && \$user->authorise('" . $nameSingle
					. ".submenu', 'com_" . $codeName . "'))";
				$custom .= PHP_EOL . Indent::_(2) . "{";
				// add tab to lines to follow
				$tab = Indent::_(1);
			}
			else
			{
				$custom .= PHP_EOL . Indent::_(2) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Access control (" . $nameSingle . ".submenu).";
				$custom .= PHP_EOL . Indent::_(2) . "if (\$user->authorise('"
					. $nameSingle . ".submenu', 'com_" . $codeName . "'))";
				$custom .= PHP_EOL . Indent::_(2) . "{";
				// add tab to lines to follow
				$tab = Indent::_(1);
			}
			if (isset($menu['link'])
				&& StringHelper::check(
					$menu['link']
				))
			{

				CFactory::_('Language')->set(
					CFactory::_('Config')->lang_target, $lang . '_' . $nameUpper, $name
				);
				// add custom menu
				$custom .= PHP_EOL . Indent::_(2) . $tab
					. "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('" . $lang . "_"
					. $nameUpper . "'), '" . $menu['link']
					. "', \$submenu === '" . $nameList . "');";
			}
			else
			{
				CFactory::_('Language')->set(
					CFactory::_('Config')->lang_target, $lang . '_' . $nameUpper, $name
				);
				// add custom menu
				$custom .= PHP_EOL . Indent::_(2) . $tab
					. "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('" . $lang . "_"
					. $nameUpper . "'), 'index.php?option=com_" . $codeName
					. "&view=" . $nameList . "', \$submenu === '" . $nameList
					. "');";
			}
			// check if the item has permissions.
			$custom .= PHP_EOL . Indent::_(2) . "}";

			return $custom;
		}
		elseif (isset($menu['submenu']) && $menu['submenu'] == 1
			&& empty($menu['before']))
		{
			// setup access defaults
			$tab        = "";
			$nameSingle = StringHelper::safe($name);
			$this->lastCustomSubMenu[$nr] = '';
			// check if the item has permissions.
			if (CFactory::_('Compiler.Creator.Permission')->globalExist($nameSingle, 'core.access'))
			{
				$this->lastCustomSubMenu[$nr] .= PHP_EOL . Indent::_(2)
					. "if (\$user->authorise('" . CFactory::_('Compiler.Creator.Permission')->getGlobal($nameSingle, 'core.access')
					. "', 'com_" . $codeName . "') && \$user->authorise('"
					. $nameSingle . ".submenu', 'com_" . $codeName . "'))";
				$this->lastCustomSubMenu[$nr] .= PHP_EOL . Indent::_(2) . "{";
				// add tab to lines to follow
				$tab = Indent::_(1);
			}
			else
			{
				$this->lastCustomSubMenu[$nr] .= PHP_EOL . Indent::_(2)
					. "if (\$user->authorise('" . $nameSingle
					. ".submenu', 'com_" . $codeName . "'))";
				$this->lastCustomSubMenu[$nr] .= PHP_EOL . Indent::_(2) . "{";
				// add tab to lines to follow
				$tab = Indent::_(1);
			}
			if (isset($menu['link'])
				&& StringHelper::check(
					$menu['link']
				))
			{
				CFactory::_('Language')->set(
					CFactory::_('Config')->lang_target, $lang . '_' . $nameUpper, $name
				);
				// add custom menu
				$this->lastCustomSubMenu[$nr] .= PHP_EOL . Indent::_(2) . $tab
					. "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('" . $lang . "_"
					. $nameUpper . "'), '" . $menu['link']
					. "', \$submenu === '" . $nameList . "');";
			}
			else
			{
				CFactory::_('Language')->set(
					CFactory::_('Config')->lang_target, $lang . '_' . $nameUpper, $name
				);
				// add custom menu
				$this->lastCustomSubMenu[$nr] .= PHP_EOL . Indent::_(2) . $tab
					. "Joomla__"."_ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla__"."_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('" . $lang . "_"
					. $nameUpper . "'), 'index.php?option=com_" . $codeName
					. "&view=" . $nameList . "', \$submenu === '" . $nameList
					. "');";
			}
			// check if the item has permissions.
			$this->lastCustomSubMenu[$nr] .= PHP_EOL . Indent::_(2) . "}";
		}

		return false;
	}

	public function setMainMenus()
	{
		if (CFactory::_('Component')->isArray('admin_views'))
		{
			$menus = '';
			// main lang prefix
			$lang = CFactory::_('Config')->lang_prefix . '_MENU';
			// set the code name
			$codeName = CFactory::_('Config')->component_code_name;
			// default prefix is none
			$prefix = '';
			// check if local is set
			if (CFactory::_('Component')->isNumeric('add_menu_prefix'))
			{
				// set main menu prefix switch
				$addPrefix = CFactory::_('Component')->get('add_menu_prefix');
				if ($addPrefix == 1 && CFactory::_('Component')->isString('menu_prefix'))
				{
					$prefix = trim((string) CFactory::_('Component')->get('menu_prefix')) . ' ';
				}
			}
			else
			{
				// set main menu prefix switch
				$addPrefix = CFactory::_('Config')->get('add_menu_prefix', 1);
				if ($addPrefix == 1)
				{
					$prefix = trim((string) CFactory::_('Config')->get('menu_prefix', '&#187;'))
						. ' ';
				}
			}
			// add the prefix
			if ($addPrefix == 1)
			{
				CFactory::_('Language')->set(
					'adminsys', $lang, $prefix . CFactory::_('Component')->get('name')
				);
			}
			else
			{
				CFactory::_('Language')->set(
					'adminsys', $lang, CFactory::_('Component')->get('name')
				);
			}

			if (CFactory::_('Config')->get('joomla_version', 3) != 3
				&& CFactory::_('Registry')->get('build.dashboard', null) === null)
			{
				$menus .= PHP_EOL . Indent::_(3) . '<menu option="com_'
					. $codeName . '" view="' . $codeName . '">' . $lang
					. '_DASHBOARD</menu>';

				CFactory::_('Language')->set(
					'adminsys', $lang . '_DASHBOARD',
					'Dashboard'
				);
			}

			// loop over the admin views
			foreach (CFactory::_('Component')->get('admin_views') as $view)
			{
				// set custom menu
				$menus .= $this->addCustomMainMenu($view, $codeName, $lang);
				if (isset($view['mainmenu']) && $view['mainmenu'] == 1)
				{
					$nameList  = StringHelper::safe(
						$view['settings']->name_list
					);
					$nameUpper = StringHelper::safe(
						$view['settings']->name_list, 'U'
					);
					$menus     .= PHP_EOL . Indent::_(3) . '<menu option="com_'
						. $codeName . '" view="' . $nameList . '">' . $lang
						. '_' . $nameUpper . '</menu>';
					CFactory::_('Language')->set(
						'adminsys', $lang . '_' . $nameUpper,
						$view['settings']->name_list
					);
				}
			}
			if (isset($this->lastCustomMainMenu)
				&& ArrayHelper::check(
					$this->lastCustomMainMenu
				))
			{
				foreach ($this->lastCustomMainMenu as $menu)
				{
					$menus .= $menu;
				}
				unset($this->lastCustomMainMenu);
			}

			return $menus;
		}

		return false;
	}

	public function addCustomMainMenu(&$view, &$codeName, &$lang)
	{
		$customMenu = '';
		// see if we should have custom admin views
		if (CFactory::_('Component')->isArray('custom_admin_views'))
		{
			foreach (CFactory::_('Component')->get('custom_admin_views') as $nr => $menu)
			{
				if (!isset($this->customAdminAdded[$menu['settings']->code]))
				{
					if (isset($menu['mainmenu']) && $menu['mainmenu'] == 1
						&& $view['adminview'] == $menu['before'])
					{
						CFactory::_('Language')->set(
							'adminsys', $lang . '_' . $menu['settings']->CODE,
							$menu['settings']->name
						);
						// add custom menu
						$customMenu .= PHP_EOL . Indent::_(3)
							. '<menu option="com_' . $codeName . '" view="'
							. $menu['settings']->code . '">' . $lang . '_'
							. $menu['settings']->CODE . '</menu>';
					}
					elseif (isset($menu['mainmenu']) && $menu['mainmenu'] == 1
						&& empty($menu['before']))
					{
						CFactory::_('Language')->set(
							'adminsys', $lang . '_' . $menu['settings']->CODE,
							$menu['settings']->name
						);
						// add custom menu
						$this->lastCustomMainMenu[$nr] = PHP_EOL . Indent::_(3)
							. '<menu option="com_' . $codeName . '" view="'
							. $menu['settings']->code . '">' . $lang . '_'
							. $menu['settings']->CODE . '</menu>';
					}
				}
			}
		}
		// see if we should have custom menus
		if (CFactory::_('Component')->isArray('custommenus'))
		{
			foreach (CFactory::_('Component')->get('custommenus') as $nr => $menu)
			{
				$nr = $nr + 100;
				if (isset($menu['mainmenu']) && $menu['mainmenu'] == 1
					&& $view['adminview'] == $menu['before'])
				{
					if (isset($menu['link'])
						&& StringHelper::check($menu['link']))
					{
						$nameList  = StringHelper::safe(
							$menu['name']
						);
						$nameUpper = StringHelper::safe(
							$menu['name'], 'U'
						);
						CFactory::_('Language')->set(
							'adminsys', $lang . '_' . $nameUpper, $menu['name']
						);
						// sanitize url
						if (strpos((string) $menu['link'], 'http') === false)
						{
							$menu['link'] = str_replace(
								'/administrator/index.php?', '', (string) $menu['link']
							);
							$menu['link'] = str_replace(
								'administrator/index.php?', '', $menu['link']
							);
							// check if the index is still there
							if (strpos($menu['link'], 'index.php?') !== false)
							{
								$menu['link'] = str_replace(
									'/index.php?', '', $menu['link']
								);
								$menu['link'] = str_replace(
									'index.php?', '', $menu['link']
								);
							}
						}
						// urlencode
						$menu['link'] = htmlspecialchars(
							(string) $menu['link'], ENT_XML1, 'UTF-8'
						);
						// add custom menu
						$customMenu .= PHP_EOL . Indent::_(3) . '<menu link="'
							. $menu['link'] . '">' . $lang . '_' . $nameUpper
							. '</menu>';
					}
					else
					{
						$nameList  = StringHelper::safe(
							$menu['name_code']
						);
						$nameUpper = StringHelper::safe(
							$menu['name_code'], 'U'
						);
						CFactory::_('Language')->set(
							'adminsys', $lang . '_' . $nameUpper, $menu['name']
						);
						// add custom menu
						$customMenu .= PHP_EOL . Indent::_(3)
							. '<menu option="com_' . $codeName . '" view="'
							. $nameList . '">' . $lang . '_' . $nameUpper
							. '</menu>';
					}
				}
				elseif (isset($menu['mainmenu']) && $menu['mainmenu'] == 1
					&& empty($menu['before']))
				{
					if (isset($menu['link'])
						&& StringHelper::check($menu['link']))
					{
						$nameList  = StringHelper::safe(
							$menu['name']
						);
						$nameUpper = StringHelper::safe(
							$menu['name'], 'U'
						);
						CFactory::_('Language')->set(
							'adminsys', $lang . '_' . $nameUpper, $menu['name']
						);
						// sanitize url
						if (strpos((string) $menu['link'], 'http') === false)
						{
							$menu['link'] = str_replace(
								'/administrator/index.php?', '', (string) $menu['link']
							);
							$menu['link'] = str_replace(
								'administrator/index.php?', '', $menu['link']
							);
							// check if the index is still there
							if (strpos($menu['link'], 'index.php?') !== false)
							{
								$menu['link'] = str_replace(
									'/index.php?', '', $menu['link']
								);
								$menu['link'] = str_replace(
									'index.php?', '', $menu['link']
								);
							}
						}
						// urlencode
						$menu['link'] = htmlspecialchars(
							(string) $menu['link'], ENT_XML1, 'UTF-8'
						);
						// add custom menu
						$this->lastCustomMainMenu[$nr] = PHP_EOL . Indent::_(3)
							. '<menu link="' . $menu['link'] . '">' . $lang
							. '_' . $nameUpper . '</menu>';
					}
					else
					{
						$nameList  = StringHelper::safe(
							$menu['name_code']
						);
						$nameUpper = StringHelper::safe(
							$menu['name_code'], 'U'
						);
						CFactory::_('Language')->set(
							'adminsys', $lang . '_' . $nameUpper, $menu['name']
						);
						// add custom menu
						$this->lastCustomMainMenu[$nr] = PHP_EOL . Indent::_(3)
							. '<menu option="com_' . $codeName . '" view="'
							. $nameList . '">' . $lang . '_' . $nameUpper
							. '</menu>';
					}
				}
			}
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
