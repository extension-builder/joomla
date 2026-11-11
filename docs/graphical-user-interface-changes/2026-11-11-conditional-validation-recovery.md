# Readable generated conditional validation

## Change identity

- **Date first changed:** 2026-11-11
- **Author/implementer:** ChatGPT, at the maintainer's request
- **Task/issue/PR:** Conditional validation recovery from `2dab8eb3`
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

Implementation in progress; the final changed-path inventory will be recorded
with each protected-path commit.

### Moved or renamed

- None

### Deleted

- None

## Implementation details

Template integration is being prepared at each model's property section.
No protected-path implementation is claimed by this initial record.

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

Implementation and verification are in progress; no checks are yet claimed.

### Manual scenarios

Not yet performed.

### GUI test coverage

The final record will identify the relevant view specs and actual results.

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

The compiler templates and library emitters identify the generation source.
The final inventory will map each protected path separately. The external JCB
maintenance definition remains unavailable; no external reconciliation or
self-compilation is claimed.

## Final consistency check

Draft: exact path inventory, implementation details, source mappings and actual
verification results will be completed alongside the implementation.
