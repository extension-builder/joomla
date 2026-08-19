<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


/**
 * Authoritative data sets for Compiler Builder registry contracts.
 *
 * @since  1.0.0
 */
final class BuilderRegistryProvider
{
	/**
	 * Production Builder namespace prefix.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	public const NAMESPACE_PREFIX = 'VDM\\Joomla\\Componentbuilder\\Compiler\\Builder\\';

	/**
	 * Every concrete registry leaf registered by BuilderAJ or BuilderLZ.
	 *
	 * @var    array<string>
	 * @since  1.0.0
	 */
	private const BUILDER_NAMES = [
		'AccessSwitch',
		'AccessSwitchList',
		'AdminFilterType',
		'Alias',
		'AssetsRules',
		'BaseSixFour',
		'Category',
		'CategoryCode',
		'CategoryOtherName',
		'CheckBox',
		'ComponentFields',
		'ConfigFieldsets',
		'ConfigFieldsetsCustomfield',
		'ContentMulti',
		'ContentOne',
		'CustomAdminAdded',
		'CustomAdminViewListId',
		'CustomAdminViewListLink',
		'Contributors',
		'CustomAlias',
		'CustomField',
		'CustomFieldLinks',
		'CustomForm',
		'CustomList',
		'CustomTabs',
		'DatabaseKeys',
		'DatabaseTables',
		'DatabaseUninstall',
		'DatabaseUniqueGuid',
		'DatabaseUniqueKeys',
		'DoNotEscape',
		'DynamicButtons',
		'DynamicFields',
		'EventDispatcher',
		'EximportView',
		'ExtensionCustomFields',
		'ExtensionsParams',
		'FieldGroupControl',
		'FieldNames',
		'FieldRelations',
		'Filter',
		'FootableScripts',
		'FrontendParams',
		'GetAsLookup',
		'GetModule',
		'GoogleChart',
		'HasMenuGlobal',
		'HasPermissions',
		'HiddenFields',
		'History',
		'ImportCustomScripts',
		'IntegerFields',
		'ItemsMethodEximportString',
		'ItemsMethodListString',
		'JsonItem',
		'JsonItemArray',
		'JsonString',
		'LanguageMessages',
		'Languages',
		'Layout',
		'LayoutData',
		'LibraryManager',
		'ListColumnNumber',
		'ListFieldClass',
		'ListHeadOverride',
		'ListJoin',
		'Lists',
		'MainTextField',
		'MetaData',
		'ModelBasicField',
		'ModelExpertField',
		'ModelExpertFieldInitiator',
		'ModelMediumField',
		'ModelWhmcsField',
		'MovedPublishingFields',
		'Multilingual',
		'MysqlTableSetting',
		'NewPublishingFields',
		'OnlyFunctionButtons',
		'OrderZero',
		'OtherFilter',
		'OtherGroup',
		'OtherJoin',
		'OtherOrder',
		'OtherQuery',
		'OtherWhere',
		'PermissionAction',
		'PermissionComponent',
		'PermissionCore',
		'PermissionDashboard',
		'PermissionFields',
		'PermissionGlobalAction',
		'PermissionViews',
		'Request',
		'Router',
		'ScriptMediaSwitch',
		'ScriptUserSwitch',
		'Search',
		'SecondRunAdmin',
		'SelectionTranslation',
		'SiteDecrypt',
		'SiteDynamicGet',
		'SiteEditView',
		'SiteFieldData',
		'SiteFieldDecodeFilter',
		'SiteFields',
		'SiteMainGet',
		'Sort',
		'TabCounter',
		'Tags',
		'TemplateData',
		'Title',
		'UikitComp',
		'UpdateMysql',
		'UninstallScriptContent',
		'UninstallScriptContext',
		'ValidationFix',
		'ViewScript',
		'ViewsDefaultOrdering'
	];

	/**
	 * Builders whose paths are pipe-delimited by default.
	 *
	 * @var    array<string>
	 * @since  1.0.0
	 */
	private const PIPE_SEPARATOR_BUILDERS = [
		'ContentMulti',
		'PermissionAction',
		'PermissionComponent',
		'PermissionCore',
		'PermissionDashboard',
		'PermissionGlobalAction',
		'PermissionViews'
	];

	/**
	 * Builders whose null add policy stores array elements.
	 *
	 * @var    array<string>
	 * @since  1.0.0
	 */
	private const ARRAY_POLICY_BUILDERS = [
		'AssetsRules',
		'ConfigFieldsets',
		'DatabaseKeys',
		'DatabaseUninstall',
		'DynamicButtons',
		'ExtensionsParams',
		'Request',
		'ValidationFix'
	];

	/**
	 * Get every Builder leaf name in its canonical order.
	 *
	 * @return  array<string>
	 * @since   1.0.0
	 */
	public static function builderNames(): array
	{
		return self::BUILDER_NAMES;
	}

	/**
	 * Provide every Builder leaf with its registry policy defaults.
	 *
	 * @return  array<string, array{class-string, string, string|null, bool, bool}>
	 * @since   1.0.0
	 */
	public static function builders(): array
	{
		$cases = [];

		foreach (self::BUILDER_NAMES as $name)
		{
			$cases[$name] = [
				self::NAMESPACE_PREFIX . $name,
				$name,
				self::separatorFor($name),
				in_array($name, self::ARRAY_POLICY_BUILDERS, true),
				$name === 'DatabaseKeys'
			];
		}

		return $cases;
	}

	/**
	 * Provide the seven leaves with a class-level array-addition policy.
	 *
	 * @return  array<string, array{class-string, bool}>
	 * @since   1.0.0
	 */
	public static function arrayPolicyBuilders(): array
	{
		$cases = [];

		foreach (self::ARRAY_POLICY_BUILDERS as $name)
		{
			$cases[$name] = [
				self::NAMESPACE_PREFIX . $name,
				$name === 'DatabaseKeys'
			];
		}

		return $cases;
	}

	/**
	 * Resolve a leaf's constructor-selected path separator.
	 *
	 * @param   string  $name  Builder short class name.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private static function separatorFor(string $name): ?string
	{
		if ($name === 'ContentOne')
		{
			return null;
		}

		return in_array($name, self::PIPE_SEPARATOR_BUILDERS, true) ? '|' : '.';
	}
}
