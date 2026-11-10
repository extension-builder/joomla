# Restore Note/Spacer saves and the existing runtime requirement contract

## Change identity

- **Date first changed:** 2026-11-10
- **Author/implementer:** ChatGPT, at the user's request
- **Task/issue/PR:** [PR #67](https://github.com/extension-builder/joomla/pull/67), branch `fix/note-spacer-field-validation`
- **Change record status:** Ready for review; PR readiness is gated by all applicable current-head workflows described below.

## Explicit permission

- **Permission reference:** The user authorized the Note/Spacer fix and PR, explicitly retained the corrected Field model, then requested removal of the new `RequiredFields` class and all its connections: restore the regression without adding unnecessary guardrails.
- **Authorized protected path:** `admin/src/Model/FieldModel.php`, limited to the previously approved Note/Spacer validation correction. This revision retains that correction without further administrator-model edits.
- **Authorized outcome:** Save new and existing Note/Spacer fields without a database type and restore the existing compiler-generated `not_required` behaviour.
- **Permission summary:** Remove JavaScript discovery, custom-target allowlists and their wiring; repair the existing model generator; keep the approved installed-model fix; pass all applicable workflows before review.

## Purpose and rationale

`dbChecker()` already hides and clears the database controls for Note and Spacer. The existing JavaScript helper records the exemptions in the form's `not_required` field. The generated PHP model formerly consumed that runtime list, regardless of how JavaScript computed its contents.

[Commit `ea9db9b5`](https://github.com/extension-builder/joomla/commit/ea9db9b58277b8be7ff6cfeaf46e2b107d397aa4), introduced through [PR #58](https://github.com/extension-builder/joomla/pull/58), replaced that behaviour with server evaluation of structured conditions. Custom JavaScript decisions were not represented in those definitions, so the runtime bridge stopped working. [Commit `2dab8eb3`](https://github.com/extension-builder/joomla/commit/2dab8eb3249185d18b11a55694adb559c349addc) propagated the missing behaviour into the stable 6.2.0 Field model.

This revision restores the runtime bridge inside the existing model generator. The filtered, group-scoped `not_required` CSV is applied after structured conditions, so listed fields become optional as they did under the original contract. The compiler does not inspect the JavaScript, require literal names, register a custom allowlist, or introduce a separate API restriction. Native filtering, validation, events, post-processing, submitted values, and attribute restoration remain in the existing lifecycle.

The earlier proposed collector class, its tests, custom registry state, service registration, constructor injection and ViewScript changes are removed. Those supporting files are restored exactly to their pre-proposal contents. The affected-path lists below describe the final PR diff against its base, rather than additions that were subsequently removed within the branch.

The already-generated administrator Field model retains its separately approved trusted-type correction, because editing the compiler does not regenerate the installed application.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-11-10-note-spacer-field-validation.md`
- `libraries/vendor_jcb/tests/Contract/FieldValidationTest.php`

### Modified

- `admin/src/Model/FieldModel.php`
- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ValidationFix.php`
- `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ValidationFixTest.php`
- `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedViewGuardsTest.php`
- `libraries/vendor_jcb/tests/gui/specs/field.spec.js`

### Moved or renamed

- None

### Deleted

- None relative to the PR base. The collector and its test, introduced only in the earlier PR revision, have been removed from the branch.

## Implementation details

### `admin/src/Model/FieldModel.php`

- **Change type:** Modified; retained from the initial approved correction.
- **Stable location(s):** `FieldModel::validate()`, in its existing post-filter callback before the attribute snapshot/application loop.
- **What changed:** Resolve the effective field type from filtered input, stored values for protected selectors, or stored values for an omitted API PATCH selector. Look up trusted metadata by validated GUID or positive legacy ID. Note/Spacer metadata disables requirements for `datatype`, `datalenght`, `datalenght_other`, `datadefault_other`, `indexes`, `null_switch`, `store`, `on_get_model_field`, and `on_save_model_field`. The existing `finally` restoration covers the changed attributes.
- **Why:** Correct the installed editor immediately, including stale database settings and its metadata-based API path.
- **Related paths/symbols:** `admin/forms/field.xml`, `media/js/admin/field.js::dbChecker()`, `admin/src/Rule/JcbconditionalrequiredRule.php`.

### `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ValidationFix.php`

- **Change type:** Modified.
- **Stable location(s):** `get()`, `conditions()`, existing native lifecycle and attribute application loop.
- **What changed:** Keep the original registry-based emission gate. Every view passing that gate receives the existing lifecycle, including legacy registry entries with no structured groups. Read the filtered, group-scoped runtime string, split its names, trim/ignore empty entries, and set their requirement to false after structured evaluation. Avoid stored-record lookup when there are no structured conditions to evaluate.
- **Why:** Restore the removed runtime contract without source analysis or new target restrictions. Nonempty field values still undergo their native rules, unlisted requirements still apply, and submitted values are preserved rather than unset.
- **Related paths/symbols:** Existing `Builder\ValidationFix`, `Architecture\Model\ConditionalRule`, Joomla Form/FormModel. No service or constructor changes are needed.

### `libraries/vendor_jcb/tests/Contract/FieldValidationTest.php`

- **Change type:** Created; retained from the initial repair.
- **Stable location(s):** Actual administrator Field-model contract with native Joomla Form.
- **What changed:** Cover Note/Spacer saves, normal field requirements, trusted selector resolution, API PATCH handling, and restoration, with database/stored-record boundaries isolated.
- **Why:** Protect the retained installed-model correction through the real model and conditional rule.
- **Related paths/symbols:** `FieldModel::validate()`, actual `JcbconditionalrequiredRule`.

### `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ValidationFixTest.php`

- **Change type:** Modified.
- **Stable location(s):** Native-Form runtime-list, structured-condition, grouping, and restoration contracts.
- **What changed:** Replace tests of the discarded discovery/allowlist policy with regression coverage for the original submitted-list contract. Exercise listed and unlisted fields, Note/Spacer database controls, structured target overrides, legacy registry-only forms, native validators, value preservation, and restoration.
- **Why:** Validate the generated PHP independently of the retained administrator-model fix and without JavaScript analysis or custom target registration.
- **Related paths/symbols:** Model renderer, existing registry, native Joomla Form lifecycle.

### `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedViewGuardsTest.php`

- **Change type:** Modified.
- **Stable location(s):** Registry-gated validation method contract.
- **What changed:** Update the legacy-registry expectation to cover the restored runtime-list validator rather than a parent-only method.
- **Why:** A flagged view with no structured groups must retain its original runtime exemption behaviour. A view with no validation registry entry still emits no method.
- **Related paths/symbols:** `Architecture\Model\ValidationFix::get()`.

### `libraries/vendor_jcb/tests/gui/specs/field.spec.js`

- **Change type:** Modified; retained from the initial repair.
- **Stable location(s):** Note/Spacer new/existing saves, Note-to-Text transition, owned-record cleanup.
- **What changed:** Save each type through installed Joomla, reopen/save again, check empty database settings and persisted XML, and remove only uniquely named fixture records through authenticated controllers. Retain expert-storage coverage.
- **Why:** Visibility-only tests could not detect the server's save rejection.
- **Related paths/symbols:** Field form/list, type-properties AJAX, ordinary save/delete controllers.

### `docs/graphical-user-interface-changes/2026-11-10-note-spacer-field-validation.md`

- **Change type:** Created.
- **Stable location(s):** This change record.
- **What changed:** Record current permission, final scope, historical regression, removal of the rejected design, verification, and the retained generated model's source-transfer status.
- **Why:** Keep review evidence aligned with the requested regression-only repair.
- **Related paths/symbols:** Protected-path transfer ledger and PR #67.

## Impact

- **Behavioural impact:** Runtime `not_required` entries can make existing fields optional, including targets also present in structured conditions. Valid names computed dynamically in JavaScript work through the submitted list without compiler discovery. Fields absent from the list retain normal requirements and structured evaluation.
- **Validation impact:** The list changes the required attribute only. Filtering, nonempty-value validation, plugins, post-processing, value preservation, and restoration continue normally. Lists are read from the validated form group after native filtering.
- **Visual/accessibility impact:** None; the existing browser helper, controls, notices, labels, and focus behaviour are unchanged.
- **Compatibility impact:** The shared model renderer serves the existing Joomla targets. Its original emission gate remains unchanged. No new services, constructor arguments, custom registry, target allowlist, or JavaScript syntax restriction remains.
- **Generated-output impact:** Registry-gated validators regain runtime exemptions; registry-only forms regain a real validator. The compiler also registers the existing conditional rule for that lifecycle. Golden comparisons must run for all four supported targets.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| Exact restoration of the seven scanner-related supporting files and deletion of both collector files | Compared with pre-proposal commit `3e7f8f59` | Pass; supporting paths have zero net diff from that commit. |
| `composer test` and `composer test:coverage` | PHP 8.4.27, PCOV 1.0.12, pinned Joomla 6.1.2, locked dependencies | Both pass; 5,425 tests and 67,104 assertions in each run, with no test warnings/errors. Clover report generated. |
| Focused compiler model and view-guard tests | Same native Joomla/PHP runtime | Pass; 64 tests, 807 assertions. |
| Four unchanged runtime-list contracts replayed against exact pre-repair emitter `3e7f8f59` | Isolated scratch bootstrap and caches; only emitter substituted | Old emitter: four failures, 48 assertions, all actual native validation rejections. Repaired emitter: four passes, 59 assertions. No missing-rule/service/setup failures. |
| Retained actual-model contract | Included in the complete runs above | Pass; the approved 39-test Field model contract remains unchanged. |
| PHP contribution style, ownership, container keys, and PR-base whitespace | Revised local checkout | Pass; 582 test PHP files, 1,546 owned production declarations, and 969 container keys. |
| Locked Composer validation, platform requirements, and audit | Composer 2.10.2 / PHP 8.4.27 | Pass; no locked vulnerability advisories. |
| Generated JavaScript and golden-harness self-test | Local Node/Bash runtimes | Pass; 5 JavaScript tests and 40 harness checks. |
| Documented known-defect lane and recovery audit | PHP 8.4.27, separate cache/report | The documented nonblocking lane selects 84 cases; blocking recovery audit passes with no clean quarantined passes. |

All applicable jobs must pass for the revised head: PHP 8.3 full suite, PHP 8.4 coverage/Chromium, PHP 8.5 compatibility, installed GUI, compiled API, and compiler comparisons for Joomla 3/4/5/6. Current-head results are recorded in [PR #67's checks](https://github.com/extension-builder/joomla/pull/67/checks) and PR description. Earlier results on `78a6804b` concern the removed design and are not evidence for this revision. The PR stays draft until the new checks pass; automatic readiness reruns are also checked before handoff.

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Save new/existing Note without a database type before correction | Cloud Browser, installed JCB 6.2.1-alpha2 | Reproduced Data Type required rejection. Inspected stable 6.2.0 package contains the same missing server behaviour. |

### GUI test coverage

- **Spec updated:** `libraries/vendor_jcb/tests/gui/specs/field.spec.js`.

### Checks not performed

- Local installed GUI/API/golden runs require the workflow's Docker/joomengine setup; hosted workflows provide those integration gates. Local Chromium socket restrictions prevent footer-browser execution; the PHP 8.4 workflow runs it.
- Full JCB self-compilation requires its authoritative maintenance definitions, unavailable in this checkout. Public demo compilation is not evidence of JCB self-generation reconciliation.

## Risks, limitations, and rollback

- **Runtime contract:** The submitted list again supplies runtime requirement overrides. This intentionally restores the established browser authority; it does not introduce a separate authorization or discovery policy.
- **Generation boundary:** Views without the original validation registry entry continue to receive no override. Adding a new category of generated validators is outside this regression repair.
- **Exact self-generation:** The generic runtime bridge is repaired in the compiler. Reproducing the retained administrator model's additional metadata-based API/stale-record rule exactly still requires its authoritative Field customization.
- **Rollback:** Revert this PR as a whole, including tests and record. No schema change or data migration is involved.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Model/FieldModel.php` | Field authored `dbChecker()` and condition definitions; validator through `Compiler/Architecture/Model/ValidationFix.php`, `AdminViews/EditView.php`, and `###VALIDATIONFIX###` | Yes | Reconciliation in progress | The generic compiler runtime contract is repaired. The maintainer must identify the authoritative maintenance Field record and transfer the retained additional metadata rule for exact API/stale-record parity, then self-compile JCB and rerun actual-model/GUI tests. The maintenance definition package is unavailable here. |

## Final consistency check

- [x] The scope reflects the requested regression repair and removal of the discarded design.
- [x] Each final affected path has stable locations and exact what/why details.
- [x] Behavioural, visual, and generated-output impacts are explicit.
- [x] Historical evidence and current-head verification are distinguished.
- [x] The protected path has an authoritative-source mapping and honest reconciliation status.
- [x] The changed interface behaviour has GUI coverage.
- [x] Implementation follows the latest permission.
