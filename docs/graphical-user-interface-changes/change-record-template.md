# [Concise change title]

## Change identity

- **Date first changed:** YYYY-MM-DD
- **Author/implementer:** [Name or agent identity]
- **Task/issue/PR:** [Stable reference]
- **Change record status:** Draft | Ready for review | Complete

## Explicit permission

- **Permission reference:** [Link, task identifier, or precise location in the
  active request]
- **Authorized paths:** [`admin/...` and/or `media/js/...` paths or bounded
  subtree]
- **Authorized outcome:** [Exact outcome for which mutation was permitted]
- **Permission summary:** [Concise faithful summary; do not broaden the scope]

## Purpose and rationale

[Explain the problem, why a protected-path change is required, and why an
in-scope `libraries/vendor_jcb/**` change alone cannot deliver the outcome.]

## Affected paths

### Created

- None

### Modified

- None

### Moved or renamed

- None

### Deleted

- None

Replace `None` only with exact repository-relative paths. For a move or rename,
use `old/path -> new/path`.

## Implementation details

Add one subsection for every affected path. Repeat this block as needed.

### `[exact/repository-relative/path]`

- **Change type:** Created | Modified | Moved | Renamed | Deleted
- **Stable location(s):** [Class and method, JavaScript function, template or
  layout, selector, asset entry point, or uniquely named structural block]
- **What changed:** [Exact implementation change]
- **Why:** [Reason this implementation is necessary]
- **Related paths/symbols:** [Dependencies or consumers, or `None`]

## Impact

- **Behavioral impact:** [Observed change or `None` with reason]
- **Visual impact:** [Observed UI change or `None` with reason]
- **Accessibility impact:** [Impact and checks, or `None` with reason]
- **Compatibility impact:** [Host/target Joomla versions, browsers, APIs, or
  `None` with reason]
- **Generated-output impact:** [Output change, no change, or not applicable,
  with reason]

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `[exact command or check]` | [Versions/configuration] | Pass/Fail — [actual evidence] |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| [Steps and expected behavior] | [Joomla/browser/configuration] | Pass/Fail — [actual observation] |

### Checks not performed

- None

Replace `None` with every omitted relevant check and the exact reason it was
not performed. Do not present an unrun check as passed.

## Risks, limitations, and rollback

- **Known risks:** [Risks or `None` with reason]
- **Known limitations:** [Limitations or `None` with reason]
- **Rollback:** [Exact safe reversal procedure, including affected paths]

## Authoritative JCB source reconciliation

Provide one row for every affected protected path. Do not combine paths whose
authoritative sources or statuses differ.

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `[admin/... or media/js/...]` | [JCB definition, generator input, or maintained source] | Yes/No | [Allowed status from README] | [Reference/evidence and concrete next step] |

`Transfer required` must be `Yes` for every status except
`Not applicable — owner confirmed`. `No` is valid only with that status and
its required owner evidence.

Allowed statuses are `Pending source identification`, `Source identified`,
`Reconciliation in progress`, `Reconciled`, and
`Not applicable — owner confirmed`. Consult [the ledger rules](README.md)
before assigning a status.

## Final consistency check

- [ ] The affected-path lists match the final diff exactly.
- [ ] Every path has a stable location and exact what/why details.
- [ ] Behavioral and visual impact are explicit.
- [ ] Verification records actual results and identifies skipped checks.
- [ ] Every path has an authoritative-source mapping and reconciliation status.
- [ ] `Transfer required` is `No` only for an owner-confirmed `Not applicable`
  path; it is `Yes` for every other status.
- [ ] The implementation remains within the cited permission.
