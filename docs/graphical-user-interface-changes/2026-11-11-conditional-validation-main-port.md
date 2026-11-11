# Conditional validation port to main

## Change identity

- **Date first changed:** 2026-11-11
- **Author/implementer:** ChatGPT, at the maintainer's request
- **Task/issue/PR:** Port recovery [PR #68](https://github.com/extension-builder/joomla/pull/68) to main `86b1900a`; second PR link follows creation.
- **Change record status:** Draft; external maintenance-source transfer remains required

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

The active follow-up explicitly authorizes porting these compiler and generated-preview changes to main after the recovery branch is stable. Main's existing Joomla 6 asset paths, loading overlay, footer timing and native API behavior must remain.

## Purpose and rationale

Generated condition data belongs with model properties. The existing validation
placeholder is among methods, so the six model templates need a dedicated
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
- `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php`
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
- `media/js/admin/field.js`

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
- **Why:** Joomla 4 and 5 share this template; main retains its separate Joomla 6 template.
- **Related symbols:** The shared EditView producer and ValidationFix emitter.

### `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable location:** Immediately after the `typeAlias` property.
- **What changed:** Added the property placeholder for modern site-edit models.
- **Why:** Keep modern site-edit validation aligned with administrator models.
- **Related symbols:** The shared EditView producer and ValidationFix emitter.

### `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable location:** Immediately after the typeAlias property.
- **What changed:** Add VALIDATION_PROPERTIES to main's dedicated Joomla 6 model template.
- **Why:** Retain main's target-native model and API/PATCH code while placing readable condition data with properties.
- **Related symbols:** EditView::build() and ValidationFix::getProperties().

### `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable location:** Immediately after the typeAlias property.
- **What changed:** Add VALIDATION_PROPERTIES to main's dedicated Joomla 6 model template.
- **Why:** Retain main's target-native model and API/PATCH code while placing readable condition data with properties.
- **Related symbols:** EditView::build() and ValidationFix::getProperties().

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
- **Why:** Encapsulate reusable validation and temporary state outside every generated model. All nine callers are updated together. The live Form's optional/disabled/unset targets cap conditional requirements; the rule performs no additional ACL lookup.
- **Related source:** `Architecture/Model/ConditionalRule.php`; `regenerate-conditional-model-preview.php`.

### `admin/tmpl/field/default.php`

- **Change type:** Modified
- **Stable location:** Generated field-condition listener block before the authored form-listener initialization.
- **What changed:** Replaced per-condition listeners with aggregate `updateFieldConditions` watchers, including Field Type.
- **Why:** Every change must recompute all ordered conditions consistently.
- **Related source:** `Architecture/AdminView/ViewScript.php`; `regenerate-conditional-browser-preview.php`.

### `media/js/admin/field.js`

- **Change type:** Modified
- **Stable locations:** Generated condition prefix; `dbChecker()`; `getFieldTypeProperties()`.
- **What changed:** Regenerated the prefix from the six required groups and existing optional editor groups. Authored code retains value clearing/notices, refreshes before and after resets and after AJAX defaults, ignores stale type responses, and accepts numeric zero defaults for store/indexes. Removed duplicated database visibility/required handling and retired exemption-list calls.
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
| Pristine main normal unit suite | PHP 8.3.6, PHPUnit 12.5.33, pinned Joomla 6.1.2, main's exact lock | Pass: 5,425 tests / 67,104 assertions; zero errors, failures or skips |
| Port-specific unit, generated-script, GUI, API and full compiler checks | Main target/runtime contracts | Pending implementation and verification |

### Manual scenarios

No manual browser execution is claimed.

### GUI test coverage

Retain main's existing Field save/reopen tests and record cleanup. Port recovery's
Field transitions, stale AJAX response and the eight other conditional-view
specs in the same change. Final exact paths/results will be recorded before review.

### Checks not performed

- Full JCB self-compilation: the complete maintenance definition package is unavailable. Normalized previews and full public fixture compilation are distinguished from a JCB self-build.

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
| `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php` | Dedicated main Joomla 6 template / EditView producer | Yes | Source identified | Reconcile maintained template source and regenerate this target. |
| `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php` | Dedicated main Joomla 6 template / EditView producer | Yes | Source identified | Reconcile maintained template source and regenerate this target. |
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
| `media/js/admin/field.js` | Field conditions plus authored `dbChecker()` and `getFieldTypeProperties()` custom code | Yes | Source identified | Transfer the final condition and custom reset/default refresh hooks, stale-response guard and zero-default checks into Field's maintained JavaScript, then regenerate. |

External JCB maintenance definitions remain unavailable. No external
reconciliation or JCB self-compilation is claimed. Downstream custom copies of
these templates also need the new placeholder in their property section.

## Final consistency check

Draft inventory covers nineteen anticipated protected paths, including both
paths of the shared-rule replacement. The exact final diff and port verification
will be checked before review. Every source mapping remains transfer-required;
no external maintenance reconciliation is claimed.
