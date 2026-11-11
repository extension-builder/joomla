# Readable generated conditional validation

## Change identity

- **Date first changed:** 2026-11-11
- **Author/implementer:** ChatGPT, at the maintainer's request
- **Task/issue/PR:** [PR #68](https://github.com/extension-builder/joomla/pull/68),
  conditional validation recovery from `2dab8eb3`
- **Change record status:** Ready for review; external maintenance-source transfer remains required

## Explicit permission

- **Permission reference:** The active request asks for a branch and PR against
  `jcb-recovery-version`, compiler changes for every component using conditions,
  and administrator output showing how generated validation changes.
- **Authorized paths:** Conditional-validation compiler templates under
  `admin/compiler/`; relevant generated model/rule and Field behavior previews
  under `admin/` and `media/js/` when required by the accepted implementation.
- **Authorized outcome:** Readable, reusable server conditional validation,
  retained condition features, and reviewable generated output.
- **Permission summary:** Improve compiler generation and demonstrate the
  resulting administrator behavior; unrelated administrator changes are excluded.
  The naming follow-up explicitly requests generic model, rule and browser
  symbols throughout the compiler and its previews. It authorizes renaming the
  generated rule file together with its callers.
  The subsequent ACL clarification requires conditional validation to honour
  the same Form after getForm changes, without repeating permission checks.

## Purpose and rationale

Generated condition data belongs with model properties. The existing validation
placeholder is among methods, so the four model templates need a dedicated
property placeholder. Runtime logic belongs in the shared generated rule.
The installed preview must regenerate all nine models that use that shared
rule; updating Field alone would leave eight callers on the obsolete API.
Field additionally needs its missing Note/Spacer policy expressed as an ordinary
condition, and its authored AJAX/default handling must refresh those conditions.

## Affected paths

### Created

- `admin/src/Rule/ConditionalrequiredRule.php`

### Modified

- `admin/compiler/joomla_3/AdminModel.php`
- `admin/compiler/joomla_3/AdminModel_site.php`
- `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODEL.php`
- `admin/src/Model/Admin_viewModel.php`
- `admin/src/Model/Custom_codeModel.php`
- `admin/src/Model/Dynamic_getModel.php`
- `admin/src/Model/FieldModel.php`
- `admin/src/Model/FieldtypeModel.php`
- `admin/src/Model/Help_documentModel.php`
- `admin/src/Model/Joomla_componentModel.php`
- `admin/src/Model/LibraryModel.php`
- `admin/src/Model/ServerModel.php`
- `admin/tmpl/field/default.php`
- `media/js/field.js`

### Moved or renamed

- The shared rule becomes `ConditionalrequiredRule`; its file replacement is
  represented by the created and deleted paths in this inventory.

### Deleted

- `admin/src/Rule/JcbconditionalrequiredRule.php`

## Implementation details

### `admin/compiler/joomla_3/AdminModel.php`

- **Change type:** Modified
- **Stable location:** Immediately after the `typeAlias` property.
- **What changed:** Added `###VALIDATION_PROPERTIES###` before the first method.
- **Why:** Place readable condition definitions with model properties.
- **Related symbols:** `Architecture\AdminViews\EditView::build()` and
  `Architecture\Model\ValidationFix::getProperties()`.

### `admin/compiler/joomla_3/AdminModel_site.php`

- **Change type:** Modified
- **Stable location:** Immediately after the `typeAlias` property.
- **What changed:** Added the same property placeholder for site-edit models.
- **Why:** Site editing must use the same conditional-validation declarations.
- **Related symbols:** The shared EditView producer and ValidationFix emitter.

### `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable location:** Immediately after the `typeAlias` property.
- **What changed:** Added the property placeholder for modern admin models.
- **Why:** Joomla 4, 5 and 6 share this model template.
- **Related symbols:** The shared EditView producer and ValidationFix emitter.

### `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable location:** Immediately after the `typeAlias` property.
- **What changed:** Added the property placeholder for modern site-edit models.
- **Why:** Keep modern site-edit validation aligned with administrator models.
- **Related symbols:** The shared EditView producer and ValidationFix emitter.

### `admin/src/Model/Admin_viewModel.php`

- **Change type:** Modified
- **Stable locations:** After `typeAlias`; `validate()` and `resolveConditionalData()`.
- **What changed:** Regenerated readable property and named validation methods from its one existing group.
- **Why:** Keep this caller compatible with the shared rule, preserving its SQL condition.
- **Related source:** `admin_view-input.json` and the production ValidationFix emitter.

### `admin/src/Model/Custom_codeModel.php`

- **Change type:** Modified
- **Stable locations:** After `typeAlias`; `validate()` and `resolveConditionalData()`.
- **What changed:** Regenerated the same members from its two unchanged groups.
- **Why:** Preserve custom-code requirements with the new shared runtime interface.
- **Related source:** `custom_code-input.json` and the production ValidationFix emitter.

### `admin/src/Model/Dynamic_getModel.php`

- **Change type:** Modified
- **Stable locations:** After `typeAlias`; `validate()` and `resolveConditionalData()`.
- **What changed:** Regenerated the same members from its nine unchanged groups.
- **Why:** Preserve source, getter and related code requirements with the shared runtime.
- **Related source:** `dynamic_get-input.json` and the production ValidationFix emitter.

### `admin/src/Model/FieldModel.php`

- **Change type:** Modified
- **Stable locations:** After `typeAlias`; `validate()` and `resolveConditionalData()`.
- **What changed:** Regenerated the same members from the original five groups plus a final positive Note/Spacer Hide Only group.
- **Why:** A display-only field needs no database settings, including stale Other/Expert selections. Its ordinary required fields remain enforced.
- **Related source:** `field-input.json`; production ValidationFix and ConditionalRule emitters; the authored Field conditions still require maintenance-source transfer.

### `admin/src/Model/FieldtypeModel.php`

- **Change type:** Modified
- **Stable locations:** After `typeAlias`; `validate()` and `resolveConditionalData()`.
- **What changed:** Regenerated the same members from its seven unchanged groups.
- **Why:** Preserve field-type default and custom-type requirements with the shared runtime.
- **Related source:** `fieldtype-input.json` and the production ValidationFix emitter.

### `admin/src/Model/Help_documentModel.php`

- **Change type:** Modified
- **Stable locations:** After `typeAlias`; `validate()` and `resolveConditionalData()`.
- **What changed:** Regenerated the same members from its five unchanged groups.
- **Why:** Preserve help-document location, content and target requirements with the shared runtime.
- **Related source:** `help_document-input.json` and the production ValidationFix emitter.

### `admin/src/Model/Joomla_componentModel.php`

- **Change type:** Modified
- **Stable locations:** After `typeAlias`; `validate()` and `resolveConditionalData()`.
- **What changed:** Regenerated the same members from its three unchanged groups.
- **Why:** Preserve update/changelog/distribution requirements with the shared runtime.
- **Related source:** `joomla_component-input.json` and the production ValidationFix emitter.

### `admin/src/Model/LibraryModel.php`

- **Change type:** Modified
- **Stable locations:** After `typeAlias`; `validate()` and `resolveConditionalData()`.
- **What changed:** Regenerated the same members from its two unchanged groups.
- **Why:** Preserve library-target and loading requirements with the shared runtime.
- **Related source:** `library-input.json` and the production ValidationFix emitter.

### `admin/src/Model/ServerModel.php`

- **Change type:** Modified
- **Stable locations:** After `typeAlias`; `validate()` and `resolveConditionalData()`.
- **What changed:** Regenerated the same members from its five unchanged groups.
- **Why:** Preserve protocol/authentication requirements with the shared runtime.
- **Related source:** `server-input.json` and the production ValidationFix emitter.

### `admin/src/Rule/ConditionalrequiredRule.php`

- **Change type:** Created, replacing `admin/src/Rule/JcbconditionalrequiredRule.php`.
- **Stable locations:** Class body, lifecycle methods and named matchers.
- **What changed:** Replaced the callback dispatcher with exact production ConditionalRule output: prepare, evaluate, complete and release, plus focused match methods. The class, file and form-rule identifier use the generic `ConditionalrequiredRule` / `conditionalrequired` names.
- **Why:** Encapsulate reusable validation and temporary state outside every generated model. All nine callers are updated together. The live Form's optional/disabled/unset targets cap conditional requirements; the rule performs no additional ACL lookup. The same native field metadata rejects malformed collection values for single-value selectors before a condition can waive requirements.
- **Related source:** `Architecture/Model/ConditionalRule.php`; `regenerate-conditional-model-preview.php`.

### `admin/tmpl/field/default.php`

- **Change type:** Modified
- **Stable location:** Generated field-condition listener block before the authored form-listener initialization.
- **What changed:** Replaced per-condition listeners with aggregate `updateFieldConditions` watchers, including Field Type.
- **Why:** Every change must recompute all ordered conditions consistently.
- **Related source:** `Architecture/AdminView/ViewScript.php`; `regenerate-conditional-browser-preview.php`.

### `media/js/field.js`

- **Change type:** Modified
- **Stable locations:** Generated condition prefix; `dbChecker()`; `getFieldTypeProperties()`.
- **What changed:** Regenerated the prefix from the six required groups and existing optional editor groups. The shared refresh captures rendered required/disabled state and caps the final required attributes against it. Authored code retains value clearing/notices, refreshes before and after resets and after AJAX defaults, ignores stale type responses, and accepts numeric zero defaults for store/indexes. Removed duplicated database visibility/required handling and retired exemption-list calls.
- **Why:** Browser behavior must follow the same definitions as the server, including repeated type changes and asynchronous defaults.
- **Related source:** `field-input.json`, production ViewScript emitter, and Field's authored custom JavaScript in the maintenance package.

All normalized inputs above live in
`libraries/vendor_jcb/tests/Fixtures/Compiler/ConditionalValidation/`. They
record exact baseline extraction and the one proposed Field policy addition.
Token-based regeneration preserves unrelated model members byte for byte.

## Impact

- **Behavioral impact:** Note/Spacer waive all database requirements through a
  declarative condition; ordinary fields still require applicable values.
  Native Joomla filtering, plugins and validation retain their order. Other
  installed models retain their condition definitions and use the shared API.
- **Visual impact:** Field's database controls follow ordered conditions after
  every selector/default change. No page layout or control labels are changed.
- **Accessibility impact:** `required`, `aria-required` and the required class
  follow the same browser decision; GUI assertions cover the metadata changes.
- **Compatibility impact:** Generated Joomla 3, 4, 5 and 6 model templates;
  installed modern JCB previews; one-way browser conditions now use current
  values and source order rather than previous event history.
- **Generated-output impact:** Multiline model properties and a smaller
  validation wrapper; views without conditions retain no extra validation code.

## Verification

### Automated checks

| Check | Environment | Result |
| --- | --- | --- |
| Core, template and browser-emitter PHPUnit filters described in the development record | PHP 8.3.6, PHPUnit 12.5.33, Joomla 6.1.2 | Pass: 194 tests, 1,113 assertions |
| `php vendor/bin/phpunit VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ConditionalPreviewTest.php --no-coverage` | Same native form runtime | Pass: 11 tests, 494 assertions |
| `node bin/check-conditional-browser.mjs` | Node with the same PHP runtime | Pass: 46 fixtures, 142 transitions |
| Both `bin/regenerate-conditional-*-preview.php` commands, without `--write` | Production emitters and normalized fixtures | Pass: exact generated-member parity |
| PHP lint for nine installed models, the rule and Field template; `node --check media/js/field.js` | PHP 8.3.6 and Node | Pass |
| Complete normal PHPUnit suite with JUnit identity comparison | Same locked runtime; pristine recovery versus candidate | 4,852 tests / 61,338 assertions; zero new failures. Three errors and 33 failures also occur on the pristine baseline. |
| Full native conditional-fixture compilation and complete output comparison | Same restored snapshot; recovery baseline versus implementation tree `0eec4b16`; Joomla 3, 4, 5 and 6 | Pass: nine explained changed files per target, no added/removed files; Power completion identical |
| All PHP and JavaScript in those four generated candidate packages | PHP 8.3.6 and Node | Pass: 1,355 PHP files and 70 JavaScript files |
| [Installed GUI workflow](https://github.com/extension-builder/joomla/actions/runs/43081242615) | Native JCB installation; merge `a1156962` incorporating implementation head `2adeebb4` | Pass: 40 tests, zero failed/skipped/flaky; all 4,951 installed-file checks pass |
| [API workflow](https://github.com/extension-builder/joomla/actions/runs/43081242638) and [standard compiler golden workflow](https://github.com/extension-builder/joomla/actions/runs/43081242690) | Same implementation head; golden targets Joomla 3, 4, 5 and 6 | Pass |
| PHP style, declaration ownership, container keys, moved-conditions and golden-harness self-tests | Recovery base; locked test runtime | Pass: 18 style-checked files, 1,487 declarations owned, 945 keys, 40 self-tests |
| `git diff --check` | Recovery worktree | Pass |

The native tests execute emitted rules through Joomla 6.1.2 form validation.
Separate generation/placement checks cover all four target majors, both model
locations and empty substitution; full compilation also covers Joomla 3–6.
Field tests use actual XML field definitions, with database-backed catalogue
options supplied at the fixture boundary. They show the original five groups
fail Note/Spacer and the proposed six groups pass them, while Text datatype,
Other values and ordinary required fields remain enforced.

### Manual scenarios

No manual browser claims. Installed automated GUI execution passes as recorded
above. The native and browser checks do not persist test records.

### GUI test coverage

The following view-specific specs are added or updated under
`libraries/vendor_jcb/tests/gui/specs/`:

- `admin_view.spec.js`
- `custom_code.spec.js`
- `dynamic_get.spec.js`
- `field.spec.js`
- `fieldtype.spec.js`
- `help_document.spec.js`
- `joomla_component.spec.js`
- `library.spec.js`
- `server.spec.js`

The eight unchanged-definition views check conditional transitions without
saving. Field covers expert storage, Note/Spacer to Text and back, Other/Expert
children, valid zero defaults and delayed real AJAX responses. The shared
helper is `libraries/vendor_jcb/tests/gui/helpers/conditions.js`. All twelve
new/updated scenarios pass as part of the 40-test installed GUI run. Its HTTP
installed-site proof succeeds, and the artifact's hashes match all nine
conditional models, the shared rule, Field template and Field JavaScript
exactly. Local Chromium execution is unavailable because Unix socket creation
is denied; the supported GitHub runner supplies the actual browser evidence.

The [development record](../development/conditional-validation-recovery.md)
and its linked compilation evidence give complete source identities, fixture
inputs and output comparisons. Native tests provide server-validation proof;
the full compiler comparisons verify administrator and site-edit output for
all four targets.

### Checks not performed

- Full JCB self-compilation: the complete maintenance definition is unavailable
  in the checkout or public fixture packages.
- CI PHPUnit execution: the unchanged locked dependency audit stops the unit
  workflow before PHPUnit. Eight advisories affect four existing dependencies.
  The local full-suite comparison above was completed separately; no audit
  gate, test or assertion was disabled to obtain a passing result.

### Naming and live-form follow-up

All generated model, rule and browser names use the requested generic names.
The initial evidence above retains its original tested-source identities; the
naming follow-up has separate production-emitter preview checks. Browser
parity remains green at 46 fixtures / 142 transitions, and the renamed browser
emitter tests pass 24 tests / 61 assertions.

The live-form regressions execute generated GetForm permission guards against
native Joomla forms before conditional validation. They cover removed targets,
disabled targets with and without an existing value, hidden/unset targets,
forged values and ordinary required hidden/readonly fields. Native required
flag representations and exact cleanup are also checked. The focused model,
preview, template and GetForm suites pass 185 tests / 2,053 assertions. No ACL
logic is repeated in the rule; it respects metadata on the same Form object.

The main-port review exposed a separate malformed-input boundary: Joomla retains
an array submitted to a single-select field unless its own filter or rule rejects
that shape. The shared rule now checks the effective filtered or stored selector
against the native `FormField::multiple` property before checkbox boolean
coercion. A malformed shape fails validation before any conditional waiver is
applied. Genuine multiselect and checkbox collections retain their existing
matching behavior, and removed selectors continue to use trusted stored values.
Nine focused native cases cover indexed and associative single-select attacks,
checkbox coercion, empty and nested collections, supported multiselects, implicit
checkboxes and removed selectors. The runtime and exact generated shared-rule
preview suites pass **124 tests / 1,566 assertions**. These hostile request shapes
are covered in native PHP tests because ordinary browser controls cannot submit
them; this follow-up does not change the rendered interface.

### Rendered-form browser cap follow-up

The generic `ViewScript::refreshScript()` and regenerated `media/js/field.js`
now capture each target control's original required property, required attribute
or required class, together with its native disabled state. The same initial
capture still records each display container once. After replaying all conditions
in source order, the refresh removes required, ARIA-required and required-class
flags from originally optional or disabled controls. Required radio/checkbox
wrappers remain supported when their requirement is expressed through an
attribute or class instead of a native input property. No permission check is
repeated in JavaScript.

This prevents a schema-required target from becoming browser-required when the
rendered form relaxed or disabled it. It also avoids Joomla's existing validator
edge where a bare disabled attribute on a required text input is not treated the
same as `disabled="disabled"`. Ordinary required targets retain their ordered
show/hide toggles, and each generated view retains its own refresh function.

The recovery browser/PHP comparison passes **50 fixtures / 156 transitions**,
including rendered-optional text, disabled text, a class-required radio wrapper,
repeated refreshes and explicitly declared multiselect metadata. The focused
browser-emitter PHPUnit suite passes **60 tests / 128 assertions**. The Field
browser preview reproducer matches the exact production emitter; its authored
custom JavaScript and footer listeners are unchanged by this follow-up. Existing
Field GUI scenarios continue to cover normal required-state transitions; their
earlier hosted run identities above are historical evidence, not a claim that
this later follow-up has already run in that job.

The complete local recovery suite after both the selector-shape guard and
rendered-form baseline cap passes **4,880 tests / 59,320 assertions**, with zero
errors, failures or warnings. Exact generated model/rule and browser preview
checks pass. The same implementation is green in hosted PHP 8.3/8.4 unit jobs, all 40 GUI
tests, API integration and the Joomla 3/4/5/6 compiler checks. The final native
conditional compile separately verifies 5,407 deployed source hashes and all
four complete packages; 1,355 PHP and 70 JavaScript files pass syntax checks.
The [updated development record](../development/conditional-validation-recovery.md)
links exact workflow runs and the separate final compilation evidence. Earlier
counts in this record remain historical verification of the earlier revisions.

## Risks, limitations, and rollback

- **Known risks:** Changed generated code must preserve native lifecycle and
  condition semantics across supported targets. Custom AJAX code that assigns
  watched values without events should call the generated refresh function.
- **Known limitations:** Custom JavaScript-only business rules require an
  equivalent authored server definition; the generic compiler cannot infer them.
- **Rollback:** Revert this patch's compiler, model templates, all nine installed
  models, shared rule and Field JavaScript/template changes together. Do not
  roll back only one side of the shared rule/model API.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_3/AdminModel.php` | J3 administrator model template, populated by `EditView::build()` / `ValidationFix::getProperties()` | Yes | Source identified | Eight target/location tests cover producer placement; maintainer must reconcile any external template source. |
| `admin/compiler/joomla_3/AdminModel_site.php` | J3 site-edit model template, same producer | Yes | Source identified | Same generation tests; external template-source transfer remains for the maintainer. |
| `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php` | Shared J4/J5/J6 administrator template, same producer | Yes | Source identified | Same generation tests; external template-source transfer remains for the maintainer. |
| `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODEL.php` | Shared J4/J5/J6 site-edit template, same producer | Yes | Source identified | Same generation tests; external template-source transfer remains for the maintainer. |
| `admin/src/Model/Admin_viewModel.php` | Admin View definition; normalized baseline `admin_view-input.json` | Yes | Source identified | Exact production-emitter parity; maintainer should regenerate from the maintained definition. |
| `admin/src/Model/Custom_codeModel.php` | Custom Code definition; `custom_code-input.json` | Yes | Source identified | Same parity and native contract checks; regenerate maintained source. |
| `admin/src/Model/Dynamic_getModel.php` | Dynamic Get definition; `dynamic_get-input.json` | Yes | Source identified | Same parity and native contract checks; regenerate maintained source. |
| `admin/src/Model/FieldModel.php` | Field definition; `field-input.json` documents the proposed final Note/Spacer group | Yes | Source identified | Maintainer must add the final ordinary condition to the actual Field definition, then regenerate. No authoritative source export is claimed. |
| `admin/src/Model/FieldtypeModel.php` | Field Type definition; `fieldtype-input.json` | Yes | Source identified | Exact parity and native contract checks; regenerate maintained source. |
| `admin/src/Model/Help_documentModel.php` | Help Document definition; `help_document-input.json` | Yes | Source identified | Exact parity and native contract checks; regenerate maintained source. |
| `admin/src/Model/Joomla_componentModel.php` | Joomla Component definition; `joomla_component-input.json` | Yes | Source identified | Exact parity and native contract checks; regenerate maintained source. |
| `admin/src/Model/LibraryModel.php` | Library definition; `library-input.json` | Yes | Source identified | Exact parity and native contract checks; regenerate maintained source. |
| `admin/src/Model/ServerModel.php` | Server definition; `server-input.json` | Yes | Source identified | Exact parity and native contract checks; regenerate maintained source. |
| `admin/src/Rule/ConditionalrequiredRule.php` | Production `Architecture/Model/ConditionalRule.php` emitter | Yes | Source identified | Exact emitted body verified; rebuild together with all model callers in the maintained source. |
| `admin/src/Rule/JcbconditionalrequiredRule.php` (deleted) | Former generated shared rule | Yes | Source identified | Replace with `ConditionalrequiredRule.php` and regenerate all callers together; no legacy rule alias is emitted. |
| `admin/tmpl/field/default.php` | Field condition listeners produced by ViewScript | Yes | Source identified | Browser preview reproducer verifies listeners; regenerate after transferring the additional Field group. |
| `media/js/field.js` | Field conditions plus authored `dbChecker()` and `getFieldTypeProperties()` custom code | Yes | Source identified | Transfer the final condition and custom reset/default refresh hooks, stale-response guard and zero-default checks into Field's maintained JavaScript, then regenerate. |

External JCB maintenance definitions remain unavailable. No external
reconciliation or JCB self-compilation is claimed. Downstream custom copies of
these templates also need the new placeholder in their property section.

## Final consistency check

The inventory covers all seventeen protected paths in the patch, including
both paths of the shared-rule replacement. Every source
mapping remains transfer-required; no external maintenance reconciliation is
claimed. Complete-suite comparison, installed GUI behavior and conditional
full-build evidence are recorded above. The PR is ready for maintainer review
and testing against the recovery branch; the outstanding source-transfer and
dependency-audit findings are explicit and are not presented as completed.
