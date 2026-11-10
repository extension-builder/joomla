# Save Note and Spacer fields without database requirements

## Change identity

- **Date first changed:** 2026-11-10
- **Author/implementer:** ChatGPT, at the user's request
- **Task/issue/PR:** [PR #67](https://github.com/extension-builder/joomla/pull/67), branch `fix/note-spacer-field-validation`
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active request: "open a new branch and PR ... make sure this bug gets fixed and all the workflows pass successfully", following the investigation of Note/Spacer save validation.
- **Authorized paths:** `admin/src/Model/FieldModel.php`, bounded to the investigated save-validation correction.
- **Authorized outcome:** Save new and existing Note and Spacer fields without requiring hidden database settings, while preserving normal validation for database-backed fields.
- **Permission summary:** Implement the investigated fix, regression coverage, and a pull request with passing workflows.

## Purpose and rationale

The browser's `dbChecker()` correctly clears and hides database settings for Note and Spacer fields. The server's generated conditional validator ignores the browser's `not_required` list, but its condition definitions do not include the custom field-type rule. Consequently, the XML `required="true"` attribute on `datatype` blocks both new and existing display-only fields.

The affected save path is the installed administrator Field model. A library-only change cannot correct that already-generated model. The correction derives the exception from trusted field-type metadata inside the existing post-filter validation callback, preserving the protection against forged client requirement lists.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-11-10-note-spacer-field-validation.md`
- `libraries/vendor_jcb/tests/Contract/FieldValidationTest.php`

### Modified

- `admin/src/Model/FieldModel.php`
- `libraries/vendor_jcb/tests/gui/specs/field.spec.js`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/src/Model/FieldModel.php`

- **Change type:** Modified
- **Stable location(s):** `VDM\Component\Componentbuilder\Administrator\Model\FieldModel::validate()`, the `$conditionCallback` closure between the generic requirement calculation and the attribute snapshot/application loop.
- **What changed:** Resolve the effective `fieldtype` from filtered input, stored values for protected selectors, or stored values for an omitted API PATCH selector. Query the field-type table by validated GUID or positive legacy ID. Only trusted Note/Spacer names disable the requirements for `datatype`, `datalenght`, `datalenght_other`, `datadefault_other`, `indexes`, `null_switch`, `store`, `on_get_model_field`, and `on_save_model_field`. The existing `finally` restoration covers every changed attribute.
- **Why:** Display-only field types have no database column, including when an existing record retains stale database settings. Client-supplied labels, XML, and `not_required` cannot grant the exception.
- **Related paths/symbols:** `admin/forms/field.xml`; `media/js/admin/field.js::dbChecker()`; `admin/src/Rule/JcbconditionalrequiredRule.php`; compiler `Field/Groups.php` and `Model/Fields.php`.

### `libraries/vendor_jcb/tests/Contract/FieldValidationTest.php`

- **Change type:** Created
- **Stable location(s):** Actual administrator Field-model validation contract using Joomla's native Form lifecycle.
- **What changed:** Regression cases for display-only saves, ordinary requirements, trusted selector resolution, and restoration, with database and stored-record boundaries isolated.
- **Why:** Protect the server fix without copying its evaluator or trusting browser metadata.
- **Related paths/symbols:** `FieldModel::validate()` and the real `JcbconditionalrequiredRule`.

### `libraries/vendor_jcb/tests/gui/specs/field.spec.js`

- **Change type:** Modified
- **Stable location(s):** Note/Spacer create-and-edit save cases; Note-to-Text requirement transition; owned-field fixture cleanup.
- **What changed:** Save each type through the installed Joomla administrator, reopen and save it again, assert empty hidden database settings, and clean up only uniquely named fixture records through authenticated Joomla controllers. Keep the expert-storage coverage.
- **Why:** The previous browser test checked visibility without submitting Note/Spacer forms, so it could not catch server rejection.
- **Related paths/symbols:** Field form, Fields list, AJAX type properties, normal save and delete controllers.

### `docs/graphical-user-interface-changes/2026-11-10-note-spacer-field-validation.md`

- **Change type:** Created
- **Stable location(s):** This change record.
- **What changed:** Record permission, exact scope, validation evidence, and the regeneration transfer requirement.
- **Why:** The installed administrator model is generated application code whose authoring source must receive an equivalent correction before self-compilation.
- **Related paths/symbols:** The protected-path transfer ledger.

## Impact

- **Behavioral impact:** Note and Spacer saves accept empty database settings. Ordinary types retain their original requirements. Values and other validation rules remain intact.
- **Visual impact:** None; the existing Note/Spacer hiding and notice are correct.
- **Accessibility impact:** No markup, labels, focus behavior, or browser requirement metadata changes.
- **Compatibility impact:** Supports current GUID selections and positive legacy numeric type IDs. Protected selectors and omitted API PATCH selections use stored metadata.
- **Generated-output impact:** No compiler renderer or template is modified. This changes the installed JCB authoring model; authoritative self-generation reconciliation is still required as described below.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `node --check libraries/vendor_jcb/tests/gui/specs/field.spec.js` | Local Node runtime | Pass |
| `npx playwright test specs/field.spec.js --list` | Locked GUI dependencies | Pass; setup and four Field tests discovered |
| `php -d memory_limit=1G vendor/bin/phpunit Contract/FieldValidationTest.php` | PHP 8.4.27, Joomla 6.1.2 source, locked Composer project | Pass; 39 tests, 304 assertions |
| Baseline replay of the 8 Note/Spacer positive cases against the exact unpatched model | PHP 8.4.27, scratch-only adapted contract | All 8 reproduce the original rejection (`validate()` returns `false`); the same cases pass after the correction |
| `composer test` | PHP 8.4.27, before adding the focused contract | Pass; all 5,383 existing tests, 66,677 assertions |
| `php bin/check-php-style.php` | Local PHP 8.4.27 | Pass; all 582 project-owned test PHP files checked |
| Ownership, moved-condition and container-key guards; `git diff --check` | Local checkout | Pass; 1,546 owned production files and 969 container keys |
| Locked Composer validation, platform requirements and audit | Composer 2.10.2 / PHP 8.4.27 | Pass; no locked vulnerability advisories |
| `node --test libraries/vendor_jcb/tests/javascript/*.test.cjs` | Local Node runtime | Pass; 5 tests |
| Known-defect recovery audit | PHP 8.4.27 | Pass; no clean passes among the 84 documented nonblocking cases |
| `composer test:coverage` | PHP 8.4.27 / PCOV; published source `e13a80c7` | Pass; 5,422 tests, 66,981 assertions, no warnings/errors; Clover report generated |
| Changed-file style, ownership, moved-condition and container-key guards; `git diff --check 24c4671d...HEAD` | Published commit `e13a80c7` against the PR base | Pass; new test file checked, all ownership/container contracts retained |
| [Vendor JCB unit tests](https://github.com/extension-builder/joomla/actions/runs/42847675313) | GitHub Actions, source `5062112b` | Pass; PHP 8.3 full suite, PHP 8.4 coverage/Chromium scripts, PHP 8.5 compatibility |
| [Initial GUI run](https://github.com/extension-builder/joomla/actions/runs/42845463616) | Installed joomengine / Chromium | 32 passed; three new cases stopped before saving because the description shares its element with the note heading. The test was corrected to assert the rendered note container and its contained text; the successful follow-up is recorded below. |

| [GUI regression suite](https://github.com/extension-builder/joomla/actions/runs/42847675131) | Installed joomengine / Chromium, source `5062112b` | Pass; 35/35 tests, no retries. New and existing Note/Spacer saves, XML reloads, Note-to-Text requirement restoration, and fixture cleanup all passed. Evidence artifact: `13779800146`. |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Save new and existing Note with no database type before correction | Cloud Browser, installed JCB 6.2.1-alpha2 | Reproduced Data Type required rejection; actual stable 6.2.0 package has the same missing server rule |

### GUI test coverage

- **Spec files added/updated:** `libraries/vendor_jcb/tests/gui/specs/field.spec.js`.

### Checks not performed

- API and compiler golden-master workflows were not selected by the repository path filters: this repair changes the installed Field editor, its tests, and this record, with no compiler renderer, API generator, or template changes. The applicable unit and GUI workflows both passed.
- JCB self-compilation from its authoritative authoring definitions: that authoritative maintenance definition package is not available in this checkout. The public demo compiler comparison is not evidence of JCB self-generation reconciliation.

## Risks, limitations, and rollback

- **Known risks:** One bounded metadata lookup is added to validation when a valid selected type is present. Missing or malformed types do not relax requirements. Database failures follow the existing database exception behavior.
- **Known limitations:** Self-compilation can overwrite the model correction until the authoritative Field definition/generation source is reconciled.
- **Rollback:** Revert this pull request as a whole, including its tests and change record. No schema change or data migration is needed.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Model/FieldModel.php` | JCB's Field admin view condition definitions and custom `dbChecker()` behavior; generated `validate()` comes through `Compiler/Architecture/Model/ValidationFix.php`, `AdminViews/EditView.php`, and `###VALIDATIONFIX###` | Yes | Pending source identification | Repository maintainer owns the maintenance definition package. Identify its Field view record and transfer this trusted metadata rule into the authoritative generation/customization path, then self-compile JCB and rerun the actual-model and GUI regression tests. No dedicated `php_validate` hook exists in this checkout; generic `php_model` methods alone do not insert this callback logic. |

## Final consistency check

- [x] The affected-path lists match the final diff exactly.
- [x] Every path has a stable location and exact what/why details.
- [x] Behavioral and visual impact are explicit.
- [x] Verification records actual results and identifies skipped checks.
- [x] Every protected path has an authoritative-source mapping and reconciliation status.
- [x] `Transfer required` is `Yes` for the pending authoritative source reconciliation.
- [x] The changed interface behavior has GUI test coverage.
- [x] The implementation remains within the cited permission.
