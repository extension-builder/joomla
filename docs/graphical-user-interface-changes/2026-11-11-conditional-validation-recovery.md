# Readable generated conditional validation

## Change identity

- **Date first changed:** 2026-11-11
- **Author/implementer:** ChatGPT, at the maintainer's request
- **Task/issue/PR:** [PR #68](https://github.com/extension-builder/joomla/pull/68),
  conditional validation recovery from `2dab8eb3`
- **Change record status:** Draft

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

## Purpose and rationale

Generated condition data belongs with model properties. The existing validation
placeholder is among methods, so the four model templates need a dedicated
property placeholder. Runtime logic belongs in the shared generated rule.

## Affected paths

### Created

- None

### Modified

- `admin/compiler/joomla_3/AdminModel.php`
- `admin/compiler/joomla_3/AdminModel_site.php`
- `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODEL.php`

### Moved or renamed

- None

### Deleted

- None

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

## Impact

- **Behavioral impact:** Conditional validation retains the native Joomla flow.
- **Visual impact:** No layout change is planned by the property integration.
- **Accessibility impact:** No control labels or accessibility attributes are
  changed by the property integration.
- **Compatibility impact:** Generated Joomla 3, 4, 5 and 6 model templates.
- **Generated-output impact:** Multiline model properties and a smaller
  validation wrapper; views without conditions retain no extra validation code.

## Verification

### Automated checks

| Check | Environment | Result |
| --- | --- | --- |
| `phpunit ValidationFixTest.php EditViewTest.php --no-coverage` using their mirrored Architecture paths | PHP 8.3.6, PHPUnit 12.5.33, Joomla 6.1.2 | Pass: 83 tests, 791 assertions |
| `git diff --check` | Recovery worktree | Pass |

The focused tests execute emitted rules through native form validation and
cover all four target majors, both model locations and empty substitution.

### Manual scenarios

Not yet performed.

### GUI test coverage

No installed GUI behavior has changed in this template-only stage. Native
validation tests and generated template-placement tests cover the changed
output. Browser behavior verification is being developed with the later
condition-generation corrections and will be recorded separately here.

### Checks not performed

- Full JCB self-compilation: the complete maintenance definition is unavailable
  in the checkout or public fixture packages.

## Risks, limitations, and rollback

- **Known risks:** Changed generated code must preserve native lifecycle and
  condition semantics across supported targets.
- **Known limitations:** Custom JavaScript-only business rules require an
  equivalent authored server definition; the generic compiler cannot infer them.
- **Rollback:** Revert this patch's generated-validation commits together.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_3/AdminModel.php` | J3 administrator model template, populated by `EditView::build()` / `ValidationFix::getProperties()` | Yes | Source identified | Eight target/location tests cover producer placement; maintainer must reconcile any external template source. |
| `admin/compiler/joomla_3/AdminModel_site.php` | J3 site-edit model template, same producer | Yes | Source identified | Same generation tests; external template-source transfer remains for the maintainer. |
| `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php` | Shared J4/J5/J6 administrator template, same producer | Yes | Source identified | Same generation tests; external template-source transfer remains for the maintainer. |
| `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODEL.php` | Shared J4/J5/J6 site-edit template, same producer | Yes | Source identified | Same generation tests; external template-source transfer remains for the maintainer. |

External JCB maintenance definitions remain unavailable. No external
reconciliation or JCB self-compilation is claimed. Downstream custom copies of
these templates also need the new placeholder in their property section.

## Final consistency check

The current inventory covers the four template edits. Later protected changes
must update this inventory in their own commit. Final acceptance and generated
output verification remain in progress.
