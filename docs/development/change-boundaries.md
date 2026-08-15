# Repository change boundaries

## Purpose

This document is the authoritative path-ownership and change-permission policy
for JCB work in this repository. It applies to humans, coding agents,
automation, reviewers, and maintainers. A technically correct change is still
non-conforming when it crosses one of these boundaries.

A path-local instruction may impose stricter requirements, but it cannot
weaken this policy or make a prohibited path editable.

These rules distinguish four different states:

- **in scope** — JCB-owned code that may be changed when the active task calls
  for that change and the repository's other architecture and testing rules
  are satisfied;
- **read-only by default** — code that may be inspected and discussed, but may
  be changed only after explicit, task-specific permission;
- **conditionally editable** — the one JCB-maintained media subtree, which is
  still read-only until explicit, task-specific permission is given; and
- **prohibited** — externally maintained or generated distribution content
  that must never be edited in this repository.

## Authoritative path boundaries

| Path | Ownership and default state | Mutation rule | Additional obligation |
| --- | --- | --- | --- |
| `libraries/vendor_jcb/**` | JCB-owned; in scope | May be changed when required by the active task, subject to the architecture, code-style, and testing contracts | Preserve compiler execution, generated-output, provider, registry, event, and version-axis contracts |
| `libraries/phpseclib3/**` | Externally maintained dependency; prohibited | Never add, modify, move, rename, format, regenerate, or delete files here | Report the required change to the dependency's authoritative upstream project; do not perform dependency maintenance in this repository |
| `libraries/phpspreadsheet/**` | Externally maintained PhpSpreadsheet dependency; prohibited | Never add, modify, move, rename, format, regenerate, or delete files here | Report the required change to the dependency's authoritative upstream project; do not perform dependency maintenance in this repository |
| `admin/**` | Generated, stable JCB administrator application; read-only by default | Change only when the active task grants explicit, task-specific permission for the affected path and purpose | Add or update a change record under `docs/graphical-user-interface-changes/` in the same change |
| `media/js/**` | JCB-maintained client-side source; conditionally editable | Change only when the active task grants explicit, task-specific permission for the affected path and purpose | Add or update a change record under `docs/graphical-user-interface-changes/` in the same change |
| `media/**` except `media/js/**` | Externally maintained, imported, or generated assets; prohibited | Never add, modify, move, rename, format, regenerate, or delete files here | Change the authoritative external or generating source instead |

The repository spells the PhpSpreadsheet directory
`libraries/phpspreadsheet` in lowercase. References to "phpSpreadsheet" or
"PhpSpreadsheet" in a request refer to that same protected directory; a case
variation does not create a second editable path.

Permission to work somewhere under `libraries/` is permission to work only in
`libraries/vendor_jcb/`. It never grants permission for either dependency
tree. Likewise, permission for `media/js/` never extends to a sibling under
`media/`.

## What qualifies as explicit permission

Explicit permission must be present in the active task and must identify both:

1. the protected area or concrete path to be changed; and
2. the requested outcome that requires the mutation.

Permission is task-specific and path-specific. It is not inferred from:

- a broad request to refactor, modernize, clean up, fix tests, or improve JCB;
- permission granted in an earlier task, branch, pull request, or
  conversation;
- the fact that a protected file appears related to an in-scope change;
- the ability to write to the path;
- a generated diff, formatter result, dependency update, or test failure; or
- permission for a neighboring or parent directory.

Permission for one `admin/` or `media/js/` change does not authorize another.
When the necessary path or purpose expands beyond the permission given, stop
and request an explicit extension before editing. No permission granted in
this repository can authorize direct edits to the prohibited dependency or
media paths; those changes belong in their authoritative source systems.

Reading, auditing, and proposing a patch for a read-only-by-default area are
allowed. A proposal must remain non-mutating until permission is granted.

## Required workflow

Before writing any file, classify every intended path using the decision
matrix above.

1. For a prohibited path, stop. Identify the authoritative dependency,
   asset, or generating source and report that the local path cannot be
   changed.
2. For `admin/**` or `media/js/**`, locate the explicit permission in the
   active task. If it is absent or narrower than the intended diff, stop and
   ask for permission.
3. When permission exists, create the required change record from
   [`change-record-template.md`](../graphical-user-interface-changes/change-record-template.md)
   before or with the first protected-path mutation.
4. Keep the mutation within the authorized paths and purpose. Do not include
   incidental formatting, generated drift, or nearby cleanup.
5. Update the record as the implementation changes. It must describe the
   final diff, not the initial plan.
6. Stage and commit the record with the protected-path change. Follow-up
   commits that alter the protected diff must update the same record.
7. Verify the code and the record before review. The record is part of the
   acceptance criteria, not a post-merge note.

The record must make every permitted `admin/**` and `media/js/**` mutation
portable back to JCB's authoritative maintenance system. It therefore must
name exact created, modified, and deleted paths; stable symbols or structural
locations; what changed; why it changed; behavioral and visual impact;
verification performed; and the reconciliation status for each affected
path. See the [GUI change-record rules](../graphical-user-interface-changes/README.md)
for the complete schema and naming convention.

## Stop conditions

Stop before mutation, or stop immediately when discovered, if any of the
following is true:

- a planned or generated diff touches `libraries/phpseclib3/**` or
  `libraries/phpspreadsheet/**`;
- a planned or generated diff touches anything under `media/**` other than
  `media/js/**`;
- explicit permission for an `admin/**` or `media/js/**` mutation cannot be
  cited from the active task;
- the permitted path, purpose, or expected impact is ambiguous;
- the protected-path diff expands beyond the permission given;
- the authoritative JCB source or reconciliation route cannot be identified
  and the uncertainty could make a direct edit unsafe;
- a required change record is absent, incomplete, stale, or cannot account for
  every protected-path diff; or
- a generated or bulk operation produces unrelated protected-path changes.

Do not solve a stop condition by weakening this policy, silently excluding a
file from the diff, or describing an unmade change as complete. Preserve any
legitimate in-scope work, report the exact blocked path and reason, and obtain
direction where required.

## Review contract

Reviewers must compare the complete changed-path list against this policy.
Reject the change when:

- either dependency tree or a prohibited media path is mutated;
- protected-path permission is missing or does not cover the final diff;
- an `admin/**` or `media/js/**` diff has no matching change record;
- a record groups unrelated work or omits a changed path, stable location,
  rationale, impact, verification result, or reconciliation status; or
- the record claims reconciliation without evidence that the authoritative
  JCB source was updated and the result was verified.

Suggestions for protected areas are welcome. They become implementation work
only after the required permission, documentation, and reconciliation path
are explicit.
