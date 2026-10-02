# Generated API item reads use read permissions

## Change identity

- **Date first changed:** 2026-10-02
- **Author/implementer:** Codex, API read permission task
- **Task/issue/PR:** `fix/generated-api-contracts-and-extrusion`
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active user request: repair the system that compiles the API in `extension-builder/joomla`, never generated API files, after completing the MCP changes.
- **Authorized paths:** `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php`.
- **Authorized outcome:** Make API reads respect native read permissions and return API errors while retaining administrator edit guards.
- **Permission summary:** The compiler template repair is within the user's explicitly authorized API generator work.

## Purpose and rationale

The generated administrator item model also serves API requests. Its existing edit check rejects users allowed to read but not edit, redirecting them to an administrator URL with HTTP 303. A compiler renderer change alone cannot replace the template's unconditional edit guard.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-10-02-generated-api-item-read-permissions.md`
- `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api/Controller/GeneratedItemModelFixture.php`
- `libraries/vendor_jcb/tests/api/read-permissions.php`
- `libraries/vendor_jcb/tests/api/seed-model-role.php`
- `libraries/vendor_jcb/tests/api/seed-read-roles.php`

### Modified

- `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/EditView.php`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListView.php`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api/Controller/AllowView.php`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api/Controller/GetModel.php`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureApi.php`
- `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/EditViewTest.php`
- `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListViewTest.php`
- `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api/Controller/AllowViewTest.php`
- `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api/Controller/GetModelTest.php`
- `libraries/vendor_jcb/tests/api/scenarios.php`
- `.github/api-tests/run.sh`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable location(s):** Generated `<View>Model::getItem()` read/edit authorization block.
- **What changed:** The fully populated `ADMIN_VIEW_MODEL_ITEM_ACCESS` fragment preserves the original complete block byte for byte when the view has no API. For API-enabled views, API requests use the existing mapped entity/component access action and native view-level policy; refusals throw HTTP 403. Other clients retain the edit check, message, redirect, and false return.
- **Why:** Read permission and edit permission are independent, and API clients require an API denial rather than an administrator redirect.
- **Related paths/symbols:** `Architecture/Api/Controller/AllowView::getItemGuard()` / `getModelGuard()`, `Architecture/AdminViews/EditView::build()`, `Service/ArchitectureApi::getControllerAllowView()`.

### Renderer, test and harness paths

- `Api/Controller/GetModel::get()` selects the explicit item/list model by controller role; `EditView::build()` and `ListView::build()` pass that role. `EditView` always fills `ADMIN_VIEW_MODEL_ITEM_ACCESS`, preserving template materialization for API-disabled views.
- `Api/Controller/AllowView::getItemGuard()` emits the complete template block; `getModelGuard()` uses native mapped access and viewing-access policy. Access denials throw native `Joomla\CMS\Access\Exception\NotAllowed`, which Joomla's JSON:API error handler maps to HTTP 403. A generic `RuntimeException` with code 403 would instead reach the HTTP 500 fallback. `Service/ArchitectureApi::getControllerAllowView()` injects the existing access-switch builder rather than adding a parallel permission map. The same switch governs customized access fields; field-name registration never suppresses a native view-level restriction.
- The mirrored `EditViewTest`, `ListViewTest`, `GetModelTest` and `AllowViewTest`, together with `GeneratedItemModelFixture`, execute and inspect the real generated fragments. They protect controller model roles, provider wiring, read denials and unchanged administrator behavior. Generated model refusals also run through the real `JsonApiView::displayItem()` and native JSON:API error-handler chain for entity, component and view-level denials; a generic exception control proves the 500 fallback remains distinct.
- `api/seed-read-roles.php` adds isolated native API roles; `api/read-permissions.php` exercises their real numeric/GUID reads and denied writes. `api/seed-model-role.php` changes only the guarded shipped Demo view's native names before a second compilation, retaining its GUID and field links.
- `api/scenarios.php` now remembers created records and performs native API cleanup even after an interrupted scenario. `.github/api-tests/run.sh` creates the private disposable-site marker, compiles/installs both source-definition fixtures and runs the HTTP scenarios with the native token plugin.

## Impact

- **Behavioral impact:** A permitted API reader can retrieve an existing item without edit permission. Denied access remains denied with HTTP 403.
- **Visual impact:** None; the changed template emits a model and introduces no browser markup or controls.
- **Accessibility impact:** None; no interactive controls or browser text change.
- **Compatibility impact:** Shared Joomla 4/5/6 compiler template. Existing controller edit/delete checks remain active; permission names still come from `Creator/Permission`.
- **Generated-output impact:** Only an API-enabled administrator item model gains the client/read branch. API-disabled template materialization matches the original bytes, including the public HelloWorld fixture. Native view-level configuration applies to customized access fields as in the list model; plain custom columns named `access` remain data when that configuration is disabled.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php -d memory_limit=1G vendor/bin/phpunit --configuration phpunit.xml.dist --exclude-group known-defect --no-coverage --filter 'AllowViewTest\|EditViewTest\|ProviderCatalogTest'` | PHP 8.3.6, PHPUnit 12.5.33, Joomla 6.1.2 source runtime | Pass — 120 tests, 16,442 assertions; executes the actual template method for mapped access, denied access, native view levels/options bypass, customized native access fields, plain access data without view-level configuration, permissionless views, disabled API and administrator denial/edit behavior. Includes native `JsonApiView` and error-handler classification for all three read denials. |
| Focused `PhpStyleChecker::inspectFile()` on eight affected renderer/provider/test/API fixture files | PHP 8.3.6 | Pass — actual working files inspected before commit. |
| Focused `PhpStyleChecker::inspectFile()` on the updated read renderer, its regression test and generated model fixture | PHP 8.3.6 | Pass — native exception follow-up files inspected. |
| Whole-file template materialization compared with `git show 22bbe87:admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php`, using actual `AllowView::getItemGuard('greeting', false)` | Public HelloWorld names, PHP 8.3.6 | Pass — entire API-disabled template matches baseline bytes exactly. No golden comparison was weakened or baseline changed. |
| `php bin/check-test-ownership.php --base=22bbe87` | PHP 8.3.6 | Pass — 1,487 production declarations, 1,487 owned, no baseline debt. |
| `composer test` | PHP 8.3.6, PHPUnit 12.5.33, Joomla 6.1.2 source runtime | Pass — 4,399 tests, 47,730 assertions, including the native exception follow-up. |
| `bash -n .github/api-tests/run.sh`, PHP lint on all three API seed/permission scripts, `git diff --check` | Local checkout | Pass. |
| `.github/api-tests/run.sh` at `9c708f`, CI run `37038740696` | Real Joomla API harness | Original Demo CRUD passed 50 checks; read-only numeric/GUID reads and mapped-access denials passed. Native view-level denial returned 500 rather than the required 403. Corrected the compiler exception type and added a native boundary regression; the subsequent installed acceptance passed below. |
| `.github/api-tests/run.sh` at `b9987e2`, [CI run `37043968495`](https://github.com/extension-builder/joomla/actions/runs/37043968495) | Real compiled/installed Joomla API and MySQL | Passed: both `looks` and `libraries_config` complete 50 CRUD/GUID/cleanup checks and 11 permission checks each (122 checks total). Read-only and denied ID/GUID reads, denied PATCH without mutation and native view-level denial all returned their expected HTTP responses. |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| None | No browser interface change | Not applicable |

### GUI test coverage

- **Spec files added/updated:** None. This protected compiler template change does not alter administrator UI selectors or controls. Generated-template runtime tests preserve the administrator denial behavior. `libraries/vendor_jcb/tests/api/read-permissions.php`, `seed-read-roles.php`, and `.github/api-tests/run.sh` supply installed compiled API coverage: read-only numeric/GUID GET, denied GET with JSON:API 403, denied PATCH with no mutation, native view-level denial and API cleanup, including the second native model-name fixture.

### Checks not performed

- The installed acceptance above uses native Demo definitions, including the irregular `libraries_config` name; it does not recompile the maintainer's absent JCB blueprint or reproduce all four historical JCB failures. Browser GUI tests are not relevant to this model/client change and no browser selector changed.

## Risks, limitations, and rollback

- **Known risks:** The generated API uses administrator models; guards must use the same mapped access action and native `core.options` view-level bypass as the existing list model. Mutation fixtures require CLI execution, explicit disposable environment opt-in and the harness-owned site marker; HTTP acceptance only targets loopback Demo resources.
- **Known limitations:** This does not add API operations to arbitrary custom admin views.
- **Rollback:** Revert this template change and its renderer/wiring/tests, then recompile and reinstall affected components. Do not patch installed generated API files.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php` | Maintained compiler template in this repository; maintenance definition identity to be confirmed | Yes | Pending source identification | User directs fixes through this compiler repository. Maintainer should confirm whether a separate JCB maintenance definition owns this template, then transfer if needed. |

## Final consistency check

- [x] The affected-path lists match the final diff exactly.
- [x] Every path has a stable location and exact what/why details.
- [x] Behavioral and visual impact are explicit.
- [x] Verification records actual local and installed API results and their scope.
- [x] Every path has an authoritative-source mapping and reconciliation status.
- [x] `Transfer required` is `Yes` until source reconciliation is confirmed.
- [x] The record explains why there is no browser GUI spec.
- [x] The implementation remains within the cited permission.
