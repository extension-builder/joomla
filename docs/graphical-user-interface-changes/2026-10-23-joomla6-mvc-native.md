# Joomla 6 model, table and helper native contracts

## Change identity

- **Date first changed:** 2026-10-23
- **Author/implementer:** Codex, mvc_native task
- **Task/issue/PR:** [PR #60](https://github.com/extension-builder/joomla/pull/60), Joomla 6 deprecation completion following merged PR #59
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active user request: “Joomla 6 will be the cutoff point ... model table controller view error handling ... just 6.”
- **Authorized paths:** Existing `admin/compiler/joomla_6/` compiler templates listed below.
- **Authorized outcome:** Native Joomla 6 identity, database and error handling while preserving Joomla 3, 4 and 5 outputs.
- **Permission summary:** Modify the established Joomla 6 template set, with no parallel template architecture or installed administrator application changes.

## Purpose and rationale

Generated models resolve identity too early, before the controller injects it. Generated tables override native database access with a static cache that can mix connections. Generated helper/table/model error drains use deprecated error storage. These templates are the authoritative compiler inputs, so library changes alone cannot correct emitted classes.

Joomla 6.1.2 core FormView/ListView deliberately call `setUseExceptions(true)` before reading their models. The same supported-six transition is used at generated view/API/helper read boundaries. Ordinary form saves retain Joomla core's false/message/session recovery contract; no core save/validation methods are forked. Core-owned deprecated internals cannot be removed here without changing Joomla itself.

## Affected paths

### Created

- None

### Modified

- `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/SITE_VIEW_MODEL.php`
- `admin/compiler/joomla_6/SITE_VIEWS_MODEL.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_MODEL.php`
- `admin/compiler/joomla_6/DASH_MODEL.php`
- `admin/compiler/joomla_6/ADMIN_VIEW_TABLE.php`
- `admin/compiler/joomla_6/ADMIN_HELPER_CLASS.php`
- `admin/compiler/joomla_6/SITE_HELPER_CLASS.php`
- `admin/compiler/joomla_6/API_VIEW_CONTROLLER.php`
- `admin/compiler/joomla_6/SITE_DISPLAY_CONTROLLER.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/compiler/joomla_6/SITE_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable locations and changes:** Constructor, setCurrentUser and getItem: initialise identity after controller/helper injection, refresh identity-derived ACL state and item cache, and propagate retrieval exceptions.
- **Why:** Remove generated deprecated calls while retaining native Joomla 6 behavior and existing compiler placeholders.
- **Related paths:** Existing Joomla 6 settings.json and architecture services.

### `admin/compiler/joomla_6/SITE_VIEWS_MODEL.php`

- **Change type:** Modified
- **Stable locations and changes:** Constructor and setCurrentUser: initialise identity after injection and refresh identity-derived ACL state and list query/cache.
- **Why:** Remove generated deprecated calls while retaining native Joomla 6 behavior and existing compiler placeholders.
- **Related paths:** Existing Joomla 6 settings.json and architecture services.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable locations and changes:** Constructor, setCurrentUser and getItem: initialise identity after injection, refresh item cache and propagate retrieval exceptions.
- **Why:** Remove generated deprecated calls while retaining native Joomla 6 behavior and existing compiler placeholders.
- **Related paths:** Existing Joomla 6 settings.json and architecture services.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_MODEL.php`

- **Change type:** Modified
- **Stable locations and changes:** Constructor and setCurrentUser: initialise identity after injection and refresh list query/cache.
- **Why:** Remove generated deprecated calls while retaining native Joomla 6 behavior and existing compiler placeholders.
- **Related paths:** Existing Joomla 6 settings.json and architecture services.

### `admin/compiler/joomla_6/DASH_MODEL.php`

- **Change type:** Modified
- **Stable locations and changes:** Constructor and setCurrentUser: defer dashboard identity until controller/helper injection.
- **Why:** Remove generated deprecated calls while retaining native Joomla 6 behavior and existing compiler placeholders.
- **Related paths:** Existing Joomla 6 settings.json and architecture services.

### `admin/compiler/joomla_6/ADMIN_VIEW_TABLE.php`

- **Change type:** Modified
- **Stable locations and changes:** store, check and getDatabase: format SQL dates with the injected database, propagate own validation failures and inherit the native instance database accessor, removing the static cross-instance cache.
- **Why:** Remove generated deprecated calls while retaining native Joomla 6 behavior and existing compiler placeholders.
- **Related paths:** Existing Joomla 6 settings.json and architecture services.

### `admin/compiler/joomla_6/ADMIN_HELPER_CLASS.php`

- **Change type:** Modified
- **Stable locations and changes:** getModel and setAsset: inject current user into factory-created models; construct core assets with the container database and preserve warning/false failure handling using exceptions.
- **Why:** Remove generated deprecated calls while retaining native Joomla 6 behavior and existing compiler placeholders.
- **Related paths:** Existing Joomla 6 settings.json and architecture services.

### `admin/compiler/joomla_6/SITE_HELPER_CLASS.php`

- **Change type:** Modified
- **Stable locations and changes:** getModel and setAsset: inject current user into factory-created models; construct core assets with the container database and preserve warning/false failure handling using exceptions.
- **Why:** Remove generated deprecated calls while retaining native Joomla 6 behavior and existing compiler placeholders.
- **Related paths:** Existing Joomla 6 settings.json and architecture services.

### `admin/compiler/joomla_6/API_VIEW_CONTROLLER.php`

- **Change type:** Modified
- **Stable locations and changes:** delete: enable supported Joomla 6 exception handling before table/model calls; preserve missing/forbidden/conflict statuses.
- **Why:** Remove generated deprecated calls while retaining native Joomla 6 behavior and existing compiler placeholders.
- **Related paths:** Existing Joomla 6 settings.json and architecture services.

### `admin/compiler/joomla_6/SITE_DISPLAY_CONTROLLER.php`

- **Change type:** Modified
- **Stable locations and changes:** display: read the native user id property.
- **Why:** Remove generated deprecated calls while retaining native Joomla 6 behavior and existing compiler placeholders.
- **Related paths:** Existing Joomla 6 settings.json and architecture services.

### `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable locations and changes:** prepareTable passes the model's injected database to SQL date formatting.
- **Why:** Date::toSql() otherwise resolves the deprecated global database and can format against a different connection.
- **Related paths:** Native Joomla Date and BaseDatabaseModel.

### `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable locations and changes:** prepareTable passes the model's injected database to SQL date formatting.
- **Why:** Date::toSql() otherwise resolves the deprecated global database and can format against a different connection.
- **Related paths:** Native Joomla Date and BaseDatabaseModel.

## Impact

- **Behavioral impact:** Injected identities govern access checks; table instances retain their own database; failures reach the established exception or message boundary.
- **Visual impact:** None; no installed JCB administrator interface or layout changes.
- **Accessibility impact:** None; no rendered markup changes.
- **Compatibility impact:** Generated Joomla 6 only. Earlier template sets remain unchanged.
- **Generated-output impact:** The listed Joomla 6 classes use supported native identity/database access and explicit error contracts. Power placeholders still resolve through the existing injector.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `phpunit .../Component/JoomlaSix/MvcTemplatesTest.php` | PHP 8.3.6, pinned Joomla 6.1.2 | Pass — 12 tests, 191 assertions |
| `PhpStyleChecker::inspectFile` on new MVC test and two native renderer declarations | Repository test harness | Pass — no findings; parent runs complete committed-diff gates |
| `phpunit` focused MVC templates, renderers, native lifecycle and version selection (six test files) | PHP 8.3.6, pinned Joomla 6.1.2 | Pass — 184 tests, 2004 assertions |

### Manual scenarios

- Not run: installed Joomla/browser checks require the parent task's integration harness.

### GUI test coverage

- **Spec files added/updated:** None. These are compiler PHP templates, not installed JCB GUI behavior. Generated MVC runtime tests exercise identity injection, isolated database access and failure paths; parent integration/golden workflows verify emitted extensions.

### Checks not performed

- Complete workflow and golden master execution are coordinated by the parent task.

### Native Joomla source contract

The pinned 6.1.2 source in the test runtime supplies this compatibility boundary:

- `libraries/src/MVC/View/FormView.php` and `ListView.php` enable exception mode before fetching model data.
- `libraries/src/Object/LegacyErrorHandlingTrait.php` marks `setUseExceptions` for future Joomla 7 removal; the legacy getter/setter methods were deprecated in earlier major versions.
- `libraries/src/MVC/Model/AdminModel.php` catches table failures into its current false/message contract.
- `libraries/src/MVC/Controller/FormController.php` preserves submitted form data and edit redirects when that model returns false. New regression tests exercise bind exceptions, check exceptions/false, plugin veto and store false through this actual controller/model lifecycle.

## Risks, limitations, and rollback

- **Known risks:** Custom code constructing generated models directly must inject its user, matching Joomla's native current-user contract. Helper-created models are handled explicitly.
- **Known limitations:** Joomla 6's inherited save lifecycle still owns transitional legacy error internals; native supported-six exception mode is used only at compatible read/helper/API boundaries.
- **Rollback:** Revert this record and the listed template/library changes together.

## Authoritative JCB source reconciliation

The user identified `admin/compiler` as the authoritative template location. Every changed file is that existing generation source; no generated installed application file is edited.

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_6/SITE_VIEW_MODEL.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |
| `admin/compiler/joomla_6/SITE_VIEWS_MODEL.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_MODEL.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_MODEL.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |
| `admin/compiler/joomla_6/DASH_MODEL.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |
| `admin/compiler/joomla_6/ADMIN_VIEW_TABLE.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |
| `admin/compiler/joomla_6/ADMIN_HELPER_CLASS.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |
| `admin/compiler/joomla_6/SITE_HELPER_CLASS.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |
| `admin/compiler/joomla_6/API_VIEW_CONTROLLER.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |
| `admin/compiler/joomla_6/SITE_DISPLAY_CONTROLLER.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |
| `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |
| `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php` | Same compiler template | No | Not applicable — owner confirmed | Active user instruction identifies admin/compiler as the source; no external definition transfer. |

## Final consistency check

- [x] Affected paths match the MVC portion of the final diff.
- [x] Every path has stable locations and change rationale.
- [x] Impact and compatibility boundary are explicit.
- [x] Focused verification results recorded; parent integration checks explicitly pending.
- [x] Authoritative source mappings are recorded.
- [x] No installed GUI behavior is changed.
