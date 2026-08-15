# Legacy helper method inventory

## Scope

This inventory records the active API and hidden coupling in
[`Compiler/Helper`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Helper)
at commit `bca4a1520484f3e2c2fbd12964a5995b0d058de1`. It supports the
[refactoring playbook](helper-refactoring.md); it is not a proposed
one-class-per-method design.

## Exact class surface

### Fields — 9 public methods

```text
40  __construct
57  getCustomFieldCode
116 setFieldFilterSet
134 setFieldFilterListSet
152 setFieldFilterSetJ3
335 setFieldFilterListSetJ3
458 setFieldFilterSetJ4
667 setFieldFilterListSetJ4
785 setFilterFieldFile
```

### Infusion — 2 public and 2 protected methods

```text
47   __construct
66   protected buildFileContent
2291 protected setViewPlaceholders
2368 setLangFileData
```

### Interpretation — 146 public and 29 protected methods

```text
202   __construct
216   setLockLicense
250   setLockLicensePer
289   checkStatmentLicenseLocked
313   setBoolLicenseLock
360   setHelperLicenseLock
398   setInitLicenseLock
420   setWHMCSCryption
720   setGetCryptKey
929   setVersionController
1077  setDynamicUpdateXMLSQL
1145  setUpdateXMLSQL
1260  setHelperExelMethods
1559  setAdminViewMenu
1609  setCustomViewMenu
1752  setupFrontendParamFields
1808  setUserPermissionCheckAccess
1874  setUikitHelperMethods
2006  setAdminViewDisplayMethod
2061  setCustomViewDisplayMethod
2325  setPrepareDocument
2407  setGetModules
2506  setDocumentCustomPHP
2528  setCustomCSS
2543  setDocumentCustomCSS
2585  setJavaScriptFile
2629  setDocumentCustomJS
2671  setFootableScriptsLoader
2682  setDocumentMetadata
2729  setMetadataItem
2738  setMetadataList
2747  setMetadataItemJ3
2821  setMetadataListJ3
2851  setMetadataItemJ4
2925  setMetadataListJ4
2955  setGoogleChartLoader
2982  setLibrariesLoader
3072  setUikitLoader
3347  setCustomViewExtraDisplayMethods
3359  setCustomViewBody
3517  setCustomViewForm
3580  setCustomViewSubmitButtonScript
3614  setCustomViewCodeBody
3634  setCustomViewTemplateBody
3683  setTemplateCode
3699  setCustomViewLayouts
3745  getReplacementNames
3814  setMethodGetItem
3968  setCheckboxSave
3990  setMethodItemSave
4173  setJtableConstructor
4201  setJtableAliasCategory
4213  setComponentToContentTypes
4264  protected setComponentToContentTypesJ3
4352  protected setComponentToContentTypesJ4
4409  setPostInstallScript
4450  setPostInstallScriptJ3
4542  setPostInstallScriptJ4
4576  setPostUpdateScript
4608  setUninstallScript
4618  setUninstallScriptJ3
5104  setUninstallScriptJ4
5160  protected getAssetsTableIntelligentInstall
5230  protected getAssetsTableIntelligentUninstall
5285  protected getAssetsTableIntelligentCode
5330  setMoveFolderScript
5353  setMoveFolderMethod
5447  getContentType
5591  getCategoryContentType
5641  setRouterHelp
5785  routerParseSwitch
5912  routerBuildViews
5927  setBatchMove
6121  setBatchCopy
6460  setAliasTitleFix
6659  setGenerateNewTitle
6771  setGenerateNewAlias
6849  setInstall
7430  setUninstall
7490  setLangAdmin
7779  setLangSite
7872  setLangSiteSys
7923  setLangAdminSys
7963  setCustomAdminViewListLink
8036  setListBody
8232  protected getListFieldClass
8246  protected getCustomAdminViewButtons
8298  setDefaultViewsBody
8471  setModalViewsBody
8561  setListHead
8719  setListColnr
8737  getTabLayoutFieldsArray
8790  setEditBody
9031  protected getEditBodyLinkedAdminViewsTabs
9107  protected getEditBodyTabs
9331  protected setEditBodyTabMainCenterPositionDiv
9389  protected getEditBodyPublishMetaTabs
9721  protected addCustomTabs
9754  setFadeInEfect
9811  setLayout
9845  protected setLayoutOverride
9909  protected getLayoutOverride
9987  setLinkedView
10229 setFootableScripts
10355 setListBodyLinked
10547 setListHeadLinked
10722 setListQueryLinked
11237 setCustomAdminDynamicButtonController
11325 setGetItemsModelMethod
11593 setControllerEximportMethod
11766 setExportButton
11794 setImportButton
11823 setImportCustomScripts
11885 setListQuery
12139 setSearchQuery
12199 setCustomQuery
12299 setFilterQuery
12350 protected setSingleFilterQuery
12394 protected setMultiFilterQuery
12465 buildTheViewScript
13096 buildFunctionCall
13128 getTargetRelationScript
13183 checkRelationControl
13225 setTargetControlsScript
13416 ifValueScript
13724 getOptionsScript
13796 getValueScript
13848 clearValueScript
13870 setViewScript
13881 setValidationFix
13948 setAjaxToke
13972 setRegisterAjaxTask
14003 setAjaxInputReturn
14172 setAjaxModelMethods
14196 setJquery
14218 setFilterFieldHelper
14630 setUniqueFields
14684 setFilterFieldSidebarDisplayHelper
14860 protected setDefaultSidebarFilterHelper
14906 protected setCategorySidebarFilterHelper
14937 setBatchDisplayHelper
15107 protected setDefaultBatchHelper
15159 protected setCategoryBatchHelper
15185 setRouterCategoryViews
15258 setJmodelAdminGetForm
15531 protected setPermissionEditFields
15596 protected setPermissionAccessFields
15617 protected setPermissionViewFields
15691 setJviewListCanDo
15727 setFieldSetAccessControl
15780 setFilterFieldsArray
15838 protected getFilterFieldCode
15894 setStoredId
15987 protected getStoredIdCode
16051 protected getStoredIdCodeMulti
16096 setPopulateState
16156 protected getPopulateStateFilterCode
16211 protected setDefaultPopulateState
16269 setSortFields
16319 setGetItemsMethodStringFix
16984 protected setModelFieldRelation
17040 setSelectionTranslationFix
17073 setSelectionTranslationFixFunc
17150 setRouterCase
17169 setDashboardIconAccess
17174 setDashboardIcons
17394 setDashboardModelMethods
17416 setDashboardGetCustomData
17442 addCustomDashboardIcons
17629 setSubMenus
17782 addCustomSubMenu
17817 setCustomAdminSubMenu
17959 setMainMenus
18057 addCustomMainMenu
18232 getInbetweenStrings
```

Line numbers are navigation hints for this baseline, not stable identifiers.

Since this baseline, the license-lock, WHMCS, crypt-key, version-update,
excel-helper, and UIkit-helper methods listed in the
[extraction progress](helper-refactoring.md#extraction-progress) table have
been reduced to delegating shims; their behavior now lives in injected
compiler services.

## Infusion call root

`buildFileContent()` spans roughly lines 66–2,282 and contains 610 factory
calls. It has 111 direct `$this->…()` call sites targeting 86 methods. One
dynamic second-pass call site has `setLinkedView` as its only known queued
target at this baseline, giving 87 known targets across 112 syntactic call
sites. The target set is:

```text
buildTheViewScript
getTabLayoutFieldsArray
routerBuildViews
routerParseSwitch
setAdminViewDisplayMethod
setAdminViewMenu
setAjaxInputReturn
setAjaxModelMethods
setAjaxToke
setAliasTitleFix
setBatchCopy
setBatchDisplayHelper
setBatchMove
setCheckboxSave
setControllerEximportMethod
setCustomAdminDynamicButtonController
setCustomAdminViewListLink
setCustomViewBody
setCustomViewCodeBody
setCustomViewDisplayMethod
setCustomViewExtraDisplayMethods
setCustomViewForm
setCustomViewLayouts
setCustomViewMenu
setCustomViewSubmitButtonScript
setCustomViewTemplateBody
setDashboardGetCustomData
setDashboardIcons
setDashboardModelMethods
setDefaultViewsBody
setEditBody
setExportButton
setFadeInEfect
setFieldFilterListSet
setFieldFilterSet
setFieldSetAccessControl
setFilterFieldHelper
setFilterFieldSidebarDisplayHelper
setFilterFieldsArray
setGenerateNewAlias
setGenerateNewTitle
setGetCryptKey
setGetItemsMethodStringFix
setGetItemsModelMethod
setHelperExelMethods
setImportButton
setImportCustomScripts
setInstall
setJmodelAdminGetForm
setJquery
setJtableAliasCategory
setJtableConstructor
setJviewListCanDo
setListBody
setListColnr
setListHead
setLinkedView
setListQuery
setLockLicense
setLockLicensePer
setMainMenus
setMethodGetItem
setMethodItemSave
setModalViewsBody
setMoveFolderMethod
setMoveFolderScript
setPopulateState
setPostInstallScript
setPostUpdateScript
setPrepareDocument
setRegisterAjaxTask
setRouterCategoryViews
setRouterHelp
setSelectionTranslationFix
setSelectionTranslationFixFunc
setSortFields
setStoredId
setSubMenus
setUikitHelperMethods
setUninstall
setUninstallScript
setUniqueFields
setUserPermissionCheckAccess
setValidationFix
setVersionController
setViewPlaceholders
setViewScript
```

The list includes `setLinkedView`, which is reached through the second-pass
function-name queue rather than a normal direct call. The absence of recursive
cycles in direct `$this->method()` calls does not make
these methods independent. Coupling is dominated by mutable state, dynamic
dispatch, Config switches, and write order.

## Direct method families

| Cluster | Direct relationship |
| --- | --- |
| License | `setLockLicense` → helper/init locks; per-view lock → boolean/check helpers |
| Update | version controller → update XML/SQL and dynamic update SQL |
| Document | prepare document → libraries, UIkit, charts, Footable, metadata, PHP/CSS/JS, modules |
| Metadata | document metadata → item/list dispatcher → J3/J4 emitters |
| Installer | post-install/content-types/assets; uninstall J3/J4/assets-intelligence cluster |
| Edit/layout | edit body → tab/link/publish helpers → layout/override lookup |
| Linked views | linked view → linked head/body/query and Footable |
| Query/model | list query → search/filter/custom; filter → single/multi; GetItems reuses query/string/translation clusters |
| View JavaScript | build script → relation/control/value/options/function helpers |
| Filters | field helper calls Fields custom-code and filter-file methods; sidebar/batch split into default/category helpers |
| Stored/populate | stored ID and populate-state methods own their protected sub-generators |
| Dashboard/menu | dashboard icons and custom icons; submenus and custom submenus; main menus and custom main menus |
| Replacement parsing | replacement names → `getInbetweenStrings` |

Move each family with its private/protected state neighborhood.

## Helper-owned state

| Property | Current liveness | Refactoring implication |
| --- | --- | --- |
| `app` | set by Fields and used by Interpretation | inject the Joomla application where still needed |
| `eximportView`, `importCustomScripts` | written by Infusion and read by export/import generators | focused import/export build state |
| `theContributors` | declaration only in-tree | public BC review before removal; Contributors builder exists |
| `uninstallScriptBuilder`, `uninstallScriptFields` | active reads/writes | focused Extension uninstall builder(s) |
| `uninstallScriptContent` | written but never read in-tree | stale-data/BC characterization |
| `lastupdateURL` | active | Extension update state |
| `listColnrBuilder` | active | list-column builder |
| `customFieldBuilder`, `buildCategories`, `otherWhere`, `loadTracker` | declaration only in-tree | audit external consumers; similarly named builders exist |
| `iconBuilder`, `DashboardGetCustomData` | active | dashboard builders |
| `validationFixBuilder`, `viewScriptBuilder` | active | client-script builders |
| `targetRelationControl`, `targetControlsScriptChecker` | active | relation/control builders |
| `setRouterHelpDone` | active | router completion registry |
| `customAdminAdded`, `customAdminViewListLink` | active | custom-admin builders |
| `alignmentOptions` | immutable lookup | constant/value object, not mutable registry |
| `langFiles` | declaration only in-tree | public BC review |
| `secondRunAdmin` | written by parent, dynamically consumed by child | explicit second-pass work registry/orchestrator |

### Undeclared dynamic properties

These live, order-sensitive properties are created dynamically:

- `customAdminViewListId`;
- `lastCustomDashboardIcon`;
- `lastCustomSubMenu`; and
- `lastCustomMainMenu`.

They need explicit typed state during their cluster extraction. Merely declaring
them would remove a PHP 8.2 warning but would not fix the architectural coupling.

## Dynamic call path

`Interpretation::getEditBodyTabs()` appends `setLinkedView` work to
`secondRunAdmin['setLinkedView']`. Infusion later iterates the queue and calls
`$this->{$function}($array)`. This makes `setLinkedView()` live despite the lack
of a conventional call site and makes a parent depend on child-owned storage.

Public methods with no in-tree caller or dynamic reference at this baseline are
`getReplacementNames`, `clearValueScript`, `setRouterCase`, and
`setDashboardIconAccess`. They are only **candidates** for external/BC review.
The dashboard-access method is already a pure forwarder to the corresponding
permission builder, which Infusion also calls directly.

## Embedded target-version decisions

Fields uses explicit J3 versus J4-for-J4/J5/J6 dispatch. Interpretation methods
with live `joomla_version` decisions are:

```text
setHelperExelMethods
setCustomViewMenu
setAdminViewDisplayMethod
setCustomViewDisplayMethod
setDocumentCustomCSS
setDocumentCustomJS
setMetadataItem
setMetadataList
setLibrariesLoader
setUikitLoader
setCustomViewForm
setMethodItemSave
setComponentToContentTypes
setPostInstallScript
setUninstallScript
getAssetsTableIntelligentInstall
getAssetsTableIntelligentUninstall
setMoveFolderScript
setMoveFolderMethod
getContentType
getCategoryContentType
setRouterHelp
setBatchMove
setBatchCopy
setInstall
setListBody
setDefaultViewsBody
setListHead
setEditBody
setEditBodyTabMainCenterPositionDiv
getEditBodyPublishMetaTabs
setLinkedView
setFootableScripts
setListBodyLinked
setListQueryLinked
setCustomAdminDynamicButtonController
setGetItemsModelMethod
setControllerEximportMethod
setExportButton
setImportButton
setListQuery
setAjaxToke
setAjaxInputReturn
setFilterFieldHelper
setFilterFieldSidebarDisplayHelper
setBatchDisplayHelper
setJmodelAdminGetForm
setGetItemsMethodStringFix
setMainMenus
```

Infusion has additional target branches while seeding XML version, view output,
and later content. Every cluster needs a target-selector decision; no method in
this list should carry an inline major switch into a new generic service.

## Event surface

Interpretation emits these current event names:

```text
jcb_ce_onBeforeBuildAdminLang
jcb_ce_onAfterBuildAdminLang
jcb_ce_onBeforeBuildSiteLang
jcb_ce_onAfterBuildSiteLang
jcb_ce_onBeforeBuildSiteSysLang
jcb_ce_onAfterBuildSiteSysLang
jcb_ce_onBeforeBuildAdminSysLang
jcb_ce_onAfterBuildAdminSysLang
jcb_ce_onSetDefaultViewsBodyTop
jcb_ce_onSetDefaultViewsFormTop
jcb_ce_onSetDefaultViewsFormBottom
jcb_ce_onSetDefaultViewsBodyBottom
jcb_ce_onSetModalViewsBodyTop
jcb_ce_onSetModalViewsFormTop
jcb_ce_onSetModalViewsFormBottom
```

The modal-body bottom path currently triggers
`jcb_ce_onSetDefaultViewsBodyBottom` despite a nearby modal-name comment. Treat
the emitted name as current behavior until a separately tested fix.

Infusion emits:

```text
jcb_ce_onBeforeBuildFilesContent
jcb_ce_onBeforeBuildAdminEditViewContent
jcb_ce_onAfterBuildAdminEditViewContent
jcb_ce_onBeforeBuildAdminListViewContent
jcb_ce_onAfterBuildAdminListViewContent
jcb_ce_onAfterBuildAdminViewContent
jcb_ce_onBeforeBuildCustomAdminViewContent
jcb_ce_onAfterBuildCustomAdminViewContent
jcb_ce_onBeforeBuildSiteViewContent
jcb_ce_onAfterBuildSiteViewContent
jcb_ce_onAfterBuildFilesContent
jcb_ce_onBeforeLoadingAllLangStrings
jcb_ce_onBeforeBuildAllLangFiles
```

Names, argument arrays, by-reference behavior, and order are compatibility
seams.

## Factory dependency summary

Token-aware live calls:

| Helper | Calls | Unique key expressions |
| --- | ---: | ---: |
| Fields | 67 | 12 |
| Interpretation | 1,381 | 106 |
| Infusion | 670 | 60 |
| Chain total | 2,118 | 148 unique across files |

All 145 non-dynamic literal keys resolve to a registered source service. The
dynamic expressions select:

- Basic/Medium/Whmcs/Expert model-field builders;
- the Expert field initiator; and
- Eximport or List Items-Method string builders.

The dominant dependencies are Config, Language, Component, ContentOne,
ContentMulti, Placeholder, Customcode Dispenser, field/build registries,
Creator/Architecture generators, Event, Header, Registry, and Utilities.

Additional globals include Joomla application/filesystem/form/language/crypto
classes, static Componentbuilder helper data, and static VDM utility classes.
New services should turn these into explicit dependencies where practical.

## Characterization checkpoints

At minimum, tests for this inventory must capture:

- constructor order: Initializer completes structure setup before Infusion;
- `buildFileContent()`'s 11 events and dynamic second pass, plus
  `setLangFileData()`'s two language events;
- Interpretation's language and view-body events;
- every affected ContentOne/ContentMulti and focused Builder path;
- temporary Config mutations/restoration;
- by-reference argument mutation;
- current J3/J4-for-later dispatch quirks; and
- byte-for-byte generated fragments, including whitespace, line markers,
  placeholders, and Joomla Power identifiers.
