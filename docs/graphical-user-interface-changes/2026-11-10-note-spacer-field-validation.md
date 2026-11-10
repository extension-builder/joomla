# Restore Note/Spacer saves and authored conditional requirements

## Change identity

- **Date first changed:** 2026-11-10
- **Author/implementer:** ChatGPT, at the user's request
- **Task/issue/PR:** [PR #67](https://github.com/extension-builder/joomla/pull/67), branch `fix/note-spacer-field-validation`
- **Change record status:** Ready for review; the pull request's readiness is gated by all applicable current-head workflows described below.

## Explicit permission

- **Permission reference:** The active request first authorized a branch and PR fixing Note/Spacer saves. The latest instruction explicitly retains the corrected Field model and adds the architecture model and compiler PHP classes, with all workflows passing before review.
- **Authorized protected path:** `admin/src/Model/FieldModel.php`, bounded to the investigated validation correction. The existing correction is retained without further changes in the compiler revision.
- **Authorized outcome:** Save new and existing Note and Spacer fields without requiring hidden database settings, and repair the compiler contract used by authored requirement switches.
- **Permission summary:** Retain the installed-model repair, correct the generator, provide regression coverage, and finish the PR only after all applicable checks pass.

## Purpose and rationale

The browser's `dbChecker()` correctly clears and hides database settings for Note and Spacer fields. It calls `updateFieldRequired()` for `datatype`, `null_switch`, and `store`. These authored calls update the hidden `not_required` field; they are separate from JCB's structured view-condition definitions.

[Commit `ea9db9b5`](https://github.com/extension-builder/joomla/commit/ea9db9b58277b8be7ff6cfeaf46e2b107d397aa4), introduced through [PR #58](https://github.com/extension-builder/joomla/pull/58), replaced the generated model's browser-list handling with server evaluation of structured conditions. It did not provide a compatibility path for authored requirement switches. [Commit `2dab8eb3`](https://github.com/extension-builder/joomla/commit/2dab8eb3249185d18b11a55694adb559c349addc) propagated the generated validator into the stable 6.2.0 application. The Field view's authored Note/Spacer decision was then absent from server validation, leaving the XML requirement on `datatype` active.

The compiler repair records literal targets in authored edit-form JavaScript and generates a bounded compatibility path for those targets. Structured conditions keep precedence. The installed Field model also retains the trusted field-type metadata repair explicitly requested by the user. Updating the compiler alone would not update this already-generated model.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-11-10-note-spacer-field-validation.md`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Customcode/RequiredFields.php`
- `libraries/vendor_jcb/tests/Contract/FieldValidationTest.php`
- `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Customcode/RequiredFieldsTest.php`

### Modified

- `admin/src/Model/FieldModel.php`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminView/ViewScript.php`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ValidationFix.php`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Builder/ValidationFix.php`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureView.php`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Service/Customcode.php`
- `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViewScriptTest.php`
- `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ValidationFixTest.php`
- `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php`
- `libraries/vendor_jcb/tests/gui/specs/field.spec.js`
- `libraries/vendor_jcb/tests/test-ownership.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/src/Model/FieldModel.php`

- **Change type:** Modified; retained from the initial repair.
- **Stable location(s):** `FieldModel::validate()`, inside the existing post-filter callback before the required-attribute snapshot/application loop.
- **What changed:** Resolve the effective `fieldtype` from filtered input, stored values for protected selectors, or stored values for an omitted API PATCH selector. Query trusted field-type metadata by validated GUID or positive legacy ID. Only Note/Spacer names disable the requirements for `datatype`, `datalenght`, `datalenght_other`, `datadefault_other`, `indexes`, `null_switch`, `store`, `on_get_model_field`, and `on_save_model_field`. Existing `finally` restoration covers every changed attribute.
- **Why:** Correct the installed editor immediately, including records retaining stale database settings and API validation. Client-supplied type labels, XML, and `not_required` cannot grant this metadata exception.
- **Related paths/symbols:** `admin/forms/field.xml`, `media/js/admin/field.js::dbChecker()`, `admin/src/Rule/JcbconditionalrequiredRule.php`.

### `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Customcode/RequiredFields.php`

- **Change type:** Created.
- **Stable location(s):** `Compiler\Customcode\RequiredFields::get()`.
- **What changed:** Collect unique literal field-name arguments from unqualified `updateFieldRequired()` calls in authored JavaScript. Exclude code-like text in literals/comments; skip unsupported fragments conservatively and reset lexical state between calls. The unchanged full Field script and its `dbChecker()` remain supported.
- **Why:** The custom-code namespace owns interpretation of authored source. A dedicated JCB class keeps discovery separate from script rendering and model emission without modifying the upstream minifier.
- **Related paths/symbols:** `VDM\Minify\JavaScript`, `Customcode.RequiredFields`, `Architecture\AdminView\ViewScript`.

### `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Builder/ValidationFix.php`

- **Change type:** Modified.
- **Stable location(s):** `$customFields`, `setCustomFields()`, `getCustomFields()`.
- **What changed:** Store deduplicated, valid custom target names separately for each view, independently of the structured-condition registry.
- **Why:** Generated condition calls must not become browser-authorized exceptions. The setter also provides a compiler registration seam for targets that cannot be inferred literally.
- **Related paths/symbols:** The script collector and model emitter share this existing builder service.

### `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminView/ViewScript.php`

- **Change type:** Modified.
- **Stable location(s):** Constructor dependency, `get()`, `validationScript()`.
- **What changed:** Collect targets from authored edit-form file/footer source after placeholder substitution and before minification. Reset declarations for each rebuilt view. Emit existing requirement helpers for custom-only views as well as structured-condition views. Append hoisted helpers so an authored file's leading strict-mode directive remains effective.
- **Why:** Custom-only views previously received neither model compatibility data nor their generated browser helper. List-view source and generated condition calls are not declarations for this purpose.
- **Related paths/symbols:** `Customcode\RequiredFields`, `Builder\ValidationFix`, all four Joomla target subclasses.

### `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ValidationFix.php`

- **Change type:** Modified.
- **Stable location(s):** `get()`, `conditions()`, `requirementPolicy()`, `customRequirements()`.
- **What changed:** Generate the native validation callback for custom-only views. Outside API requests, read filtered, group-scoped string `not_required` and make only registered custom targets optional. Structured targets retain priority, including unevaluable conditions. Removed, disabled, or `filter="unset"` fields/list controls and internal callback controls cannot grant exceptions.
- **Why:** Restore authored browser requirements without restoring unrestricted trust in arbitrary posted names. Reuse Joomla's filter/validate/post-process lifecycle, native rules, plugin changes, value preservation, and `finally` restoration. Views without custom targets retain existing model/rule output.
- **Related paths/symbols:** `Builder\ValidationFix`, generated `JcbconditionalrequiredRule`, Joomla `Form`/`FormModel`.

### `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Service/Customcode.php`

- **Change type:** Modified.
- **Stable location(s):** `register()`, `getRequiredFields()`.
- **What changed:** Register the class alias and shared protected service `Customcode.RequiredFields`.
- **Why:** Inject the collector through the established container rather than instantiate it inside the renderer.
- **Related paths/symbols:** `RequiredFields`, `Service\ArchitectureView`.

### `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureView.php`

- **Change type:** Modified.
- **Stable location(s):** `getJ3AdminViewScript()`, `getJ4AdminViewScript()`, `getJ5AdminViewScript()`, `getJ6AdminViewScript()`.
- **What changed:** Supply the collector dependency to every supported target renderer.
- **Why:** This repair follows the Joomla component target axis, without host-version branching.
- **Related paths/symbols:** `Customcode.RequiredFields`, Joomla Three/Four/Five/Six `AdminView\ViewScript` classes.

### `libraries/vendor_jcb/tests/Contract/FieldValidationTest.php`

- **Change type:** Created; retained from the initial repair.
- **Stable location(s):** Actual administrator Field-model contract using Joomla's native Form lifecycle.
- **What changed:** Cover Note/Spacer saves, normal requirements, trusted selector resolution, API PATCH, and restoration, with database/stored-record boundaries isolated.
- **Why:** Exercise the installed model rather than a copied evaluator.
- **Related paths/symbols:** `FieldModel::validate()`, actual `JcbconditionalrequiredRule`.

### `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Customcode/RequiredFieldsTest.php`

- **Change type:** Created.
- **Stable location(s):** `scriptProvider()`, service-state isolation contract.
- **What changed:** Cover literal targets, lexical lookalikes, division/regex ambiguity, property/identifier boundaries, unsupported source forms, and reuse.
- **Why:** A false declaration could authorize an unrelated exception; missing a real declaration would preserve the regression.
- **Related paths/symbols:** `RequiredFields::get()`.

### `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViewScriptTest.php`

- **Change type:** Modified.
- **Stable location(s):** Custom-target discovery, helper emission, isolation, minification, and strict-mode contracts.
- **What changed:** Exercise all four target subclasses; custom-only and mixed views; collection before minification; authored source preservation; repeated-view reset; and one helper definition. Isolate build-scoped minifier tests in separate processes.
- **Why:** Prove generation supplies browser behavior and accurate declarations without contaminating other views or tests.
- **Related paths/symbols:** `ViewScript`, `Builder\ValidationFix`, `RequiredFields`.

### `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ValidationFixTest.php`

- **Change type:** Modified.
- **Stable location(s):** Composed `dbChecker()` regression and native-Form custom requirement contracts.
- **What changed:** Pass unchanged repository `dbChecker()` source through the real script collector and model emitter, then validate new/existing Note and Spacer data with native Joomla Form. Cover structured precedence, ordinary fields, filtered/grouped/malformed lists, protected controls, API POST/PATCH exclusion, plugins, native validators, post-processing, exceptions, and restoration.
- **Why:** The retained installed-model fix must not mask a broken generator. This composed test never loads the corrected administrator Field model.
- **Related paths/symbols:** `media/js/admin/field.js`, both compiler renderers, `Builder\ValidationFix`, Joomla Form.

### `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php`

- **Change type:** Modified.
- **Stable location(s):** Custom-code provider's captured catalog fingerprint.
- **What changed:** Update the reviewed catalog from eight to nine aliases/services for the single collector registration.
- **Why:** Preserve exact provider-registration coverage; existing wiring contracts exercise constructor injection.
- **Related paths/symbols:** `Service\Customcode`, `Contract/ProviderWiringTest.php`.

### `libraries/vendor_jcb/tests/gui/specs/field.spec.js`

- **Change type:** Modified; retained from the initial repair.
- **Stable location(s):** Note/Spacer create/edit saves; Note-to-Text transition; owned-record cleanup.
- **What changed:** Save each type in installed Joomla, reopen/save again, inspect empty database settings and reloaded XML, and remove only uniquely named fixture records through authenticated controllers. Retain expert-storage coverage.
- **Why:** The old GUI test checked visibility without submitting forms, so it could not catch server rejection.
- **Related paths/symbols:** Field form/list, type-properties AJAX, normal save/delete controllers.

### `libraries/vendor_jcb/tests/test-ownership.php`

- **Change type:** Modified.
- **Stable location(s):** `Compiler/Customcode/RequiredFields.php` declaration entry.
- **What changed:** Assign the new production class to its mirrored unit test.
- **Why:** Keep every production declaration in exactly one explicit ownership state.
- **Related paths/symbols:** `RequiredFieldsTest`, test-ownership guard.

### `docs/graphical-user-interface-changes/2026-11-10-note-spacer-field-validation.md`

- **Change type:** Created.
- **Stable location(s):** This change record.
- **What changed:** Record permission, regression history, installed-model and compiler scope, evidence, and remaining exact self-generation reconciliation.
- **Why:** Distinguish the generic compiler repair from the retained Field model's stricter metadata rule.
- **Related paths/symbols:** Protected-path transfer ledger and PR #67.

## Impact

- **Behavioral impact:** Installed Note/Spacer saves accept empty database settings. Future compiled browser forms honor custom switches for declared fields. Ordinary and structured requirements retain their rules and values.
- **Visual impact:** None; existing Note/Spacer hiding and notices remain correct.
- **Accessibility impact:** No markup, label, or focus changes; existing requirement-helper behavior is retained.
- **Compatibility impact:** Shared renderers cover Joomla targets 3/4/5/6. The retained model supports GUID/positive legacy IDs, protected selectors, and omitted API PATCH selectors. Generic custom browser exemptions are not used by API clients.
- **Generated-output impact:** Authored-target views gain bounded model compatibility; custom-only views also gain the browser helper. No-custom model/rule output is unchanged. Debug-enabled JavaScript source-location comments may shift with renderer line numbers. No golden expectations are preemptively changed.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `composer test` and `composer test:coverage` | PHP 8.4.27, PCOV 1.0.12, pinned Joomla 6.1.2, locked dependencies | Both pass; 5,477 tests and 67,424 assertions in each run, with no test warnings/errors. Clover report generated. |
| `php bin/check-php-style.php`; ownership and container-key guards; `git diff --check` | Revised local checkout | Pass; 583 test PHP files, 1,547 owned production files, 970 container keys. Complete new collector files also pass style inspection. |
| `composer validate --strict`, `composer check-platform-reqs`, `composer audit --locked` | Composer 2.10.2 / PHP 8.4.27 | Pass; no locked vulnerability advisories. |
| `node --test libraries/vendor_jcb/tests/javascript/*.test.cjs`; `bash .github/golden-master/selftest.sh` | Local Node/Bash runtimes | Pass; five generated-JavaScript tests and 40 golden-harness checks. |
| Known-defect lane and `check-known-defect-recoveries.php` | PHP 8.4.27 | Documented nonblocking lane selects 84 cases; blocking recovery audit passes with no clean quarantined passes. |
| `Customcode/RequiredFieldsTest.php` | PHP 8.4.27, locked test project | Pass; 37 tests, 47 assertions, including the unchanged full Field script and all three `dbChecker()` targets. Independent re-review also passed all 14 reported lexical probes and both shipped-script checks. |
| `Contract/FieldValidationTest.php` | PHP 8.4.27, pinned Joomla 6.1.2, locked Composer project | Pass; 39 tests, 304 assertions for the retained correction. |
| Eight Note/Spacer positive cases replayed against unpatched installed model | Scratch-only adapted contract | All eight reproduce `validate() === false`; all pass with the retained correction. |
| Four custom-only/composed compiler contracts replayed against pre-repair emitter | PHP 8.4.27; old emitter only, new collector/registry/tests | Four failures, 33 assertions; repaired emitter passes the same four cases with 128 assertions. Failures concern missing generated behavior, not bootstrap/dependency errors. |
| No-custom model/rule compatibility matrix | Old/new emitters; structured, empty, legacy registry states; Joomla 3/4/6, debug on/off, custom indentation | All 18 generated pairs byte-identical. |
| [Earlier installed-editor GUI run](https://github.com/extension-builder/joomla/actions/runs/42847675131) | joomengine/Chromium, pre-compiler revision `5062112b` | 35/35 passed, no retries, including new/existing Note/Spacer saves and Note-to-Text restoration. Historical evidence only; expanded revision requires fresh workflows. |

The expanded revision requires the unit matrix (PHP 8.3 full suite, PHP 8.4 coverage/Chromium, PHP 8.5 compatibility), installed GUI suite, compiled API suite, and compiler golden jobs for Joomla 3/4/5/6. Current-head results are recorded in [PR #67's checks](https://github.com/extension-builder/joomla/pull/67/checks). The PR remains draft until all nine jobs pass. API and golden workflows now apply because this revision changes compiler classes.

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Save new/existing Note without database type before correction | Cloud Browser, installed JCB 6.2.1-alpha2 | Reproduced Data Type required rejection. Inspected stable 6.2.0 package contains the same missing server rule. |

### GUI test coverage

- **Spec updated:** `libraries/vendor_jcb/tests/gui/specs/field.spec.js`.

### Checks not performed

- Local installed GUI/API/golden runs require the workflow's Docker/joomengine setup; hosted workflows provide those gates. Local Chromium socket restrictions prevent footer-browser execution; the PHP 8.4 workflow runs it.
- Full JCB self-compilation requires the authoritative maintenance definitions, unavailable in this checkout. Compiling the public demo is not evidence of JCB self-generation reconciliation. The composed regression executes unchanged `dbChecker()` through the compiler into native Form validation.

## Risks, limitations, and rollback

- **Custom JavaScript authority:** The allowlist bounds which authored fields the browser can make optional; it cannot evaluate arbitrary JavaScript predicates. Structured conditions keep priority and API requests ignore the list. A registered custom field without a structured condition still relies on its authored browser decision, as the legacy compatibility contract did.
- **Discovery scope:** Automatic discovery is name-based recognition of unqualified calls with plain quoted ASCII field-name literals, not lexical binding analysis. Dynamic names and property calls are excluded. A fragment containing PHP markers, complex/nested template interpolation, escaped identifiers, or unresolved ambiguous slash syntax produces no declarations, even if it also contains a valid call. Simple template substitutions remain opaque and supported. Compiler integrations can register trusted targets with `Builder\ValidationFix::setCustomFields()` after script collection and before model emission. This conservative limit avoids inferring exceptions from quoted examples or PHP-generated expressions.
- **Installed-model lookup:** One bounded metadata lookup is added when a valid selected type exists. Invalid/missing types do not relax requirements; database exceptions keep existing behavior.
- **Exact self-generation:** The generic compiler repair preserves the browser Note/Spacer contract on regeneration. Reproducing the retained model's additional metadata-based API/stale-record behavior exactly still requires reconciliation with the authoritative Field definition/customization below.
- **Rollback:** Revert the PR as a whole, including tests and record. No schema/data migration is involved.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Model/FieldModel.php` | Field authored `dbChecker()` and condition definitions; validator via `Compiler/Architecture/Model/ValidationFix.php`, `AdminViews/EditView.php`, `###VALIDATIONFIX###` | Yes | Reconciliation in progress | Generic compiler/custom-script contract repaired and tested with unchanged `dbChecker()`. Maintainer must identify the authoritative maintenance Field record and transfer the retained metadata rule for exact API/stale-record parity, then self-compile JCB and rerun actual-model/GUI tests. Maintenance package unavailable here; no dedicated `php_validate` hook exists in this checkout. |

## Final consistency check

- [x] Affected-path lists cover the complete PR scope.
- [x] Every path has stable locations and exact what/why details.
- [x] Behavioral, visual, and generated-output impacts are explicit.
- [x] Verification distinguishes historical evidence, local proof, and current-head hosted gates.
- [x] Protected path has an authoritative-source mapping and honest reconciliation status.
- [x] Transfer remains required for exact metadata-rule self-generation parity.
- [x] Changed interface behavior has GUI coverage.
- [x] Implementation follows the latest permission.
