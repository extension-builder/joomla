# Joomla 6 compiler deprecations — implementation audit

[PR #59](https://github.com/extension-builder/joomla/pull/59) · [Implementation branch](https://github.com/extension-builder/joomla/tree/deprecation/joomla6-native-compiler) · Base: `2dab8eb3249185d18b11a55694adb559c349addc`

This replaces the broad readiness audit for this work. It records confirmed deprecations and the changes actually implemented. It does **not** claim that every generated extension is now free of deprecations or ready for Joomla 7; the three remaining migration boundaries are listed below.

Production changes are confined to compiler source, accompanied by tests and documentation. Generated administrator/application files, CSS/JavaScript loading, dormant code, feature-enabling conditions and general improvements are outside this change.

## What changed

| Area | Previous output → Joomla 6 output | Commit |
| --- | --- | --- |
| Database queries | `getQuery(true)` → `createQuery()`; check-in `setQuery($query, 0, 1)` → `$query->setLimit(1); $db->setQuery($query)` | [`eb777a7`](https://github.com/extension-builder/joomla/commit/eb777a76373c2d9d7d39116a1d561907371d1904) |
| Configuration / sessions | Factory global getters → application config/session access; single-key `clear($key)` → `remove($key)` | [`855b1a9`](https://github.com/extension-builder/joomla/commit/855b1a9e55103c4f6c9271ed5f89576235b1ab16) |
| View data | `$this->get('State')` and similar proxies → `$model = $this->getModel()` and direct model calls | [`d19398e`](https://github.com/extension-builder/joomla/commit/d19398ed6570275ce524a1d01cd3d5938ac5f9f5) |
| Plugins | Dispatcher-plus-config constructor → config-only constructor; existing application/service injection retained | [`5102e83`](https://github.com/extension-builder/joomla/commit/5102e8332eb8e9824244100598c663454913cac2) |
| Installers | Component script returns its existing native installer object. Module/plugin scripts without custom hooks return an `InstallerScriptInterface` object with all five typed lifecycle methods | [`ccd84db`](https://github.com/extension-builder/joomla/commit/ccd84db95564e984d3312a83a50910459330e86b) |

## Target isolation

Providers select the new JoomlaSix renderers from the compile target, not the host version. Existing shared implementations remain available for earlier targets. Callers continue to use the established logical aliases.

Joomla 6 uses the complete [`admin/compiler/joomla_6`](https://github.com/extension-builder/joomla/tree/deprecation/joomla6-native-compiler/admin/compiler/joomla_6) template set and its own `settings.json`. `Config::getJoomlaversions()` selects folder key `6`; the existing `Utilities/Paths`, `Component/JoomlaSix/Settings`, and structure builders load and copy those files. Joomla 3 keeps folder `3`; Joomla 4 and 5 keep folder `4`.

The earlier library template resolver, interface, registrations and overlay files have been removed. The 155-file set contains the eighteen reviewed Joomla 6 API variants, plus whitespace-only indentation corrections needed when copying seven existing templates. All other files, including the complete settings manifest, retain the Joomla 4 source. No additional source-loading mechanism or filename suffix is introduced.

Custom compiler roots use their own `joomla_6/settings.json` (or the existing `settings_<component>.json` override) and ordinary template files. The existing full/custom source rules remain unchanged.

Joomla Power tokens remain in template and generated-fragment source. The existing extractor/injector still resolves target namespaces, adds imports and handles alias collisions. Runtime fixture tests run that same injector before executing generated PHP; unresolved compiler input is not treated as invalid PHP. FOF and other legacy runtime work are outside this Joomla 6 deprecation change.

## Database locations

The first ten shared emitters below now obtain the query-construction fragment from a JoomlaSix subclass; those subclasses declare `createQuery()` at line 31. The final row is the existing JoomlaSix check-in renderer. Links point to the emitted statements.

| Compiler source | Changed statement lines |
| --- | --- |
| [Creator/RouterMethodsDefault.php:90,126](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Creator/RouterMethodsDefault.php#L90) | 90,126 |
| [Creator/RouterMethodsManual.php:90,126](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Creator/RouterMethodsManual.php#L90) | 90,126 |
| [Creator/Helper.php:212](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Creator/Helper.php#L212) | 212 |
| [Dynamicget/GetItem.php:382](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/GetItem.php#L382) | 382 |
| [Dynamicget/CustomGetMethods.php:419](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/CustomGetMethods.php#L419) | 419 |
| [Dynamicget/ListQuery.php:168](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/ListQuery.php#L168) | 168 |
| [Architecture/Model/ListQuery.php:212](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ListQuery.php#L212) | 212 |
| [Architecture/Model/ItemsMethod.php:243](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ItemsMethod.php#L243) | 243 |
| [Architecture/LinkedView/ListQuery.php:246](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/LinkedView/ListQuery.php#L246) | 246 |
| [Architecture/AdminViews/FilterFieldHelper.php:187,314](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/FilterFieldHelper.php#L187) | 187,314 |
| [Architecture/JoomlaSix/Model/CheckInNow.php:68,76–77,84](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Model/CheckInNow.php#L68) | 68,76–77,84 |

The 35 direct template substitutions are:

| Joomla 6 template | `createQuery()` lines |
| --- | --- |
| [ADMIN_HELPER_CLASS:220,238,398](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/ADMIN_HELPER_CLASS.php#L220) | 220,238,398 |
| [ADMIN_HELPER_CONTROLLER:88](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/ADMIN_HELPER_CONTROLLER.php#L88) | 88 |
| [ADMIN_IMPORT_MODEL:444](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/ADMIN_IMPORT_MODEL.php#L444) | 444 |
| [ADMIN_IMPORT_MODEL_CUSTOM:334](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/ADMIN_IMPORT_MODEL_CUSTOM.php#L334) | 334 |
| [ADMIN_VIEW_MODEL:251](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php#L251) | 251 |
| [ADMIN_VIEW_TABLE:266](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/ADMIN_VIEW_TABLE.php#L266) | 266 |
| [FORM_MODAL_SELECT_FIELD:49](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/FORM_MODAL_SELECT_FIELD.php#L49) | 49 |
| [INSTALLER_SCRIPT:438,498,540,586,624,716,742,773,796,839,866,886,926,961,991,1018,1026,1058,1088,1116](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/INSTALLER_SCRIPT.php#L438) | 438,498,540,586,624,716,742,773,796,839,866,886,926,961,991,1018,1026,1058,1088,1116 |
| [SITE_ADMIN_VIEW_MODEL:262](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php#L262) | 262 |
| [SITE_FORM_MODAL_SELECT_FIELD:49](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/SITE_FORM_MODAL_SELECT_FIELD.php#L49) | 49 |
| [SITE_HELPER_CLASS:276,501,519](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/SITE_HELPER_CLASS.php#L276) | 276,501,519 |
| [SITE_HELPER_CONTROLLER:88](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/SITE_HELPER_CONTROLLER.php#L88) | 88 |

No-argument `getQuery()` remains valid and was not replaced. Host-side compiler database access and commented/earlier-target-only emissions were not changed.

## Configuration, session and view locations

| Source | Implemented change |
| --- | --- |
| [Architecture/JoomlaSix/Model/AliasTitleFix.php:32–35](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Model/AliasTitleFix.php#L32) | Application `get('unicodeslugs')`; shared emission at `Architecture/Model/AliasTitleFix.php:215`. |
| [Architecture/JoomlaSix/Controller/EximportMethod.php:32–34](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Controller/EximportMethod.php#L32) | Controller `$this->app->getSession()`; shared emission at `Architecture/Controller/EximportMethod.php:214`. |
| [Architecture/JoomlaSix/ComHelperClass/ExcelMethods.php:32–35](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/ComHelperClass/ExcelMethods.php#L32) | Factory Power `getApplication()->getSession()`; shared emission at `Architecture/ComHelperClass/ExcelMethods.php:287`. |
| [ADMIN_IMPORT_MODEL.php:89,121–123,164,176,186,227,237–240,357,382,400](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/ADMIN_IMPORT_MODEL.php#L89) | Application session/config; nine single-key removals, retaining their original keys. |
| [ADMIN_IMPORT_MODEL_CUSTOM.php:116,126–129,246,270,290](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/ADMIN_IMPORT_MODEL_CUSTOM.php#L116) | Application session/config; three single-key removals. |
| [ADMIN_IMPORT_HTML.php:52–53,67,76,80](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/ADMIN_IMPORT_HTML.php#L52) | Direct model state/errors; application session and single-key removal. |
| [API_VIEW_CONTROLLER.php:209,216](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/API_VIEW_CONTROLLER.php#L209) | Remove only the original HTTP-status session keys. |
| [DASH_VIEW_HTML.php:95–98,108](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/DASH_VIEW_HTML.php#L95) | Direct model icons, styles, scripts and errors. Asset loading itself is unchanged. |
| [ADMIN_VIEW_HTML.php:209](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/ADMIN_VIEW_HTML.php#L209) | Direct model error retrieval; existing error branch retained. |
| [ADMIN_VIEWS_HTML.php:192](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/ADMIN_VIEWS_HTML.php#L192) | Direct model error retrieval; existing error branch retained. |
| [SITE_ADMIN_VIEW_HTML.php:199](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/SITE_ADMIN_VIEW_HTML.php#L199) | Direct model error retrieval; existing error branch retained. |

Joomla Power identifiers remain intact. No raw import substitution or user-source rewriting was introduced.

## Plugins and installers

- Plugin provider: [Architecture/JoomlaSix/Plugin/Provider.php:88–91](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Plugin/Provider.php#L88). Generated plugins receive `(array) PluginHelper::getPlugin(...)` as their sole constructor argument. **A stored custom constructor that still requires a dispatcher must be migrated in its definition** to the config-only contract; the compiler does not rewrite its body.
- Component installer: [INSTALLER_SCRIPT.php:1203](https://github.com/extension-builder/joomla/blob/deprecation/joomla6-native-compiler/admin/compiler/joomla_6/INSTALLER_SCRIPT.php#L1203). Returns the existing no-argument class, which already implements `InstallerScriptInterface`; lifecycle/custom placeholders stay in place.
- Module/plugin installer: [Extension/JoomlaSix/InstallScript.php:166–173,255–257,279–297,343–351,412–434,465–467](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Extension/JoomlaSix/InstallScript.php#L166). Native output is used when no enabled, nonempty custom constructor/lifecycle hook was collected. Empty native lifecycle methods return `true`; existing version checks and file cleanup remain. Each build resets this selection.

## Remaining migration boundaries

These are confirmed deprecations left visible, not additional general-improvement recommendations:

1. **Legacy plugin event discovery remains.** [Architecture/JoomlaSix/Plugin/Extension.php:152–174](https://github.com/extension-builder/joomla/blob/14fd71c16e64d8aaf6b60bb3adbf7f59e7bd2e61/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Plugin/Extension.php#L152) can still rely on core's deprecated discovery. Arbitrary stored handlers may expect positional arguments and legacy return aggregation. Automatically adding `SubscriberInterface`/a map would change that contract; each definition needs an explicit native event signature/map migration first.
2. **Module/plugin installers with enabled custom hooks retain their existing legacy output.** Constructor hooks can use the installer adapter, and legacy lifecycle hooks can return `null`/use bare `return`; native lifecycle methods require `bool`. No hooks were moved, parsed heuristically or rewritten. Their migration remains separate and explicit.
3. **Model error collection remains.** View proxy calls were removed, but the direct `$model->getErrors()` call is itself deprecated in the reviewed Joomla 6.2 source. Removing the error branch would lose failures while models still use `setError()`. A coordinated model/controller exception migration remains outstanding.

## Validation

- Focused template loading, source parity, installer, provider and target selection checks pass: 190 tests / 18,027 assertions. Structure/settings checks also pass.
- The 61 generated-code runtime tests pass using the real Joomla Power extractor/injector, including namespace alias collisions and import reuse.
- The test-only dependency audit passes after the SVG sanitizer/YAML security updates. No audit check or test assertion was disabled.
- The full suite has 4,876 tests and 29 failures: 19 Markdown image cases, nine search pagination cases and one generic provider-constructor policy assertion. Those test files are unchanged from source baseline `2dab8eb32`; no failing assertion was excluded or weakened. The provider assertion is not evidence of a runtime construction failure. These failures do not authorize unrelated production changes.
- [Hosted comparison 39613078343](https://github.com/extension-builder/joomla/actions/runs/39613078343) passes Joomla 3–5. Joomla 6 adds only ten whitespace-line differences across five generated files to the previously reviewed API changes; Power evidence is unchanged. The exact reviewed fixture is updated for a rerun, with evidence recorded in `.github/golden-master/expected/README.md`.
- JCB self-compilation still requires the maintainer's complete definition package; the public Hello World fixture does not prove it.

## Core evidence

Replacements were checked against pinned source, rather than inferred from API age:

| API | Core evidence |
| --- | --- |
| Query creation / limits | [Joomla Database 4.0.0 `DatabaseDriver.php:976–996`](https://github.com/joomla-framework/database/blob/2e744e7959368891fe52ccb35790e201251f6f39/src/DatabaseDriver.php#L976), [limit arguments](https://github.com/joomla-framework/database/blob/2e744e7959368891fe52ccb35790e201251f6f39/src/DatabaseDriver.php#L1699) |
| Configuration / session factory getters | [Joomla 6.2 `Factory.php:175–183`](https://github.com/joomla/joomla-cms/blob/18d95f2c4e6c8e50649737c59d6f30f98b009939/libraries/src/Factory.php#L175), [session getter](https://github.com/joomla/joomla-cms/blob/18d95f2c4e6c8e50649737c59d6f30f98b009939/libraries/src/Factory.php#L256) |
| Single-key session clearing | [Joomla 6.2 `Session.php:300–327`](https://github.com/joomla/joomla-cms/blob/18d95f2c4e6c8e50649737c59d6f30f98b009939/libraries/src/Session/Session.php#L300) |
| View proxy / model errors | [Joomla 6.2 `AbstractView.php:132–179`](https://github.com/joomla/joomla-cms/blob/18d95f2c4e6c8e50649737c59d6f30f98b009939/libraries/src/MVC/View/AbstractView.php#L132), [`LegacyErrorHandlingTrait.php:83–98`](https://github.com/joomla/joomla-cms/blob/18d95f2c4e6c8e50649737c59d6f30f98b009939/libraries/src/Object/LegacyErrorHandlingTrait.php#L83) |
| Plugin construction / discovery | [Joomla 6.2 `CMSPlugin.php:125–147`](https://github.com/joomla/joomla-cms/blob/18d95f2c4e6c8e50649737c59d6f30f98b009939/libraries/src/Plugin/CMSPlugin.php#L125), [discovery deprecation](https://github.com/joomla/joomla-cms/blob/18d95f2c4e6c8e50649737c59d6f30f98b009939/libraries/src/Plugin/CMSPlugin.php#L292) |
| Native installer loading / contract | [Joomla 6.2 `InstallerAdapter.php:971–1002`](https://github.com/joomla/joomla-cms/blob/18d95f2c4e6c8e50649737c59d6f30f98b009939/libraries/src/Installer/InstallerAdapter.php#L971), [`InstallerScriptInterface.php:32–78`](https://github.com/joomla/joomla-cms/blob/18d95f2c4e6c8e50649737c59d6f30f98b009939/libraries/src/Installer/InstallerScriptInterface.php#L32) |
