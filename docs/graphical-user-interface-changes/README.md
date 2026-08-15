# Graphical User Interface Changes

## Purpose

This directory is the mandatory transfer ledger for every explicitly
authorized change under `admin/**` or `media/js/**`. Those paths contain stable
generated application code or JCB-maintained browser code whose authoritative
source may live in JCB's maintenance system. A local fix is incomplete until a
record explains exactly how to reproduce or reconcile it there.

This ledger supplements, and does not grant, permission. The authoritative
[repository change-boundary policy](../development/change-boundaries.md) still
requires explicit, task-specific permission before either protected area is
mutated.

## When a record is required

A record is required for every permitted creation, modification, move,
rename, or deletion in:

- `admin/**`; and
- `media/js/**`.

Changes elsewhere do not belong here unless they are necessary context for a
protected-path change. A record cannot make a prohibited change permissible.
In particular, nothing under `libraries/phpseclib3/**`,
`libraries/phpspreadsheet/**`, or `media/**` outside `media/js/**` may be
changed directly.

Use one record for one coherent authorized outcome. A single record may cover
both `admin/**` and `media/js/**` only when they implement the same behavior.
Use separate records for unrelated fixes or features, even when they share a
branch or pull request.

## Required filename

Name a record:

```text
YYYY-MM-DD-short-kebab-summary.md
```

Use the date of the first protected-path mutation and a concise lowercase
ASCII summary. For example:

```text
2026-08-15-compiler-progress-percentage.md
```

If that name already exists for a different change, append a two-digit
sequence (`-02`, `-03`, and so on) before `.md`. Do not rename a record merely
because later work changes its title. Create records from
[`change-record-template.md`](change-record-template.md), but do not edit the
template to describe a specific change.

## Required lifecycle

1. Cite the explicit permission and define the authorized scope.
2. Create the record before or with the first `admin/**` or `media/js/**`
   mutation.
3. Record the exact final paths and implementation locations as work proceeds.
4. Commit the record with the protected-path change. If a follow-up commit
   changes that diff, update the same record in that commit.
5. Complete verification and record commands, environments, results, and any
   unperformed checks with reasons.
6. Track each affected path until its authoritative JCB source is reconciled
   or an owner explicitly confirms that reconciliation is not applicable.

The final record must describe what is actually in the branch. Planned paths,
stale line numbers, and generic statements such as "updated the UI" are not
sufficient.

## Mandatory content

Every record must contain all sections in the template and provide:

- the task, issue, or pull-request identity;
- a citation or precise summary of the explicit permission, including the
  permitted protected paths and outcome;
- exact repository-relative created, modified, moved/renamed, and deleted
  paths, with `None` stated for empty categories;
- for each path, a stable implementation location such as a PHP class and
  method, JavaScript function, layout/template name, asset entry point,
  selector, or uniquely named code block;
- what changed at each location and why the change was necessary;
- user-visible, behavioral, accessibility, compatibility, generated-output,
  and no-impact conclusions as applicable;
- verification commands or manual scenarios, their environment, and their
  actual results;
- risks, limitations, and rollback instructions; and
- an authoritative-source mapping and reconciliation status for every
  affected protected path.

Line numbers may be included as orientation, but they are not stable locations
and cannot replace a symbol or structural anchor. A moved or renamed file must
list both its old and new paths. A deleted file must remain listed with the
symbol or responsibility that was removed.

## Reconciliation statuses

Assign one of these values to every affected `admin/**` or `media/js/**` path:

| Status | Meaning | Required evidence |
| --- | --- | --- |
| `Pending source identification` | The authoritative JCB definition, generator input, or maintained source has not yet been located | Owner and next concrete investigation step |
| `Source identified` | The authoritative source is known, but has not yet received the change | Exact source identity/path and planned transfer action |
| `Reconciliation in progress` | Transfer to the authoritative source has started but is not verified | Work reference and remaining verification |
| `Reconciled` | The authoritative source contains the change and its generated or synchronized result has been checked | Source change reference plus verification result |
| `Not applicable — owner confirmed` | An authorized owner has confirmed that this path is itself authoritative or intentionally hand-maintained | Owner decision reference and rationale |

`Transfer required` must be `Yes` for every status except
`Not applicable — owner confirmed`. `No` is valid only with that status and
its required owner evidence.

`Reconciled` is not a synonym for "committed in this repository." It may be
used only after the authoritative JCB source has been updated and verified.
`Not applicable — owner confirmed` must not be selected by inference.

## Reviewer checklist

- [ ] The active task explicitly permits every changed `admin/**` and
  `media/js/**` path and the implemented outcome.
- [ ] No prohibited dependency or media path is changed.
- [ ] The record filename and scope follow this document.
- [ ] Created, modified, moved/renamed, and deleted lists match the final diff
  exactly.
- [ ] Every path has stable locations, an exact what/why explanation, impact,
  verification, and reconciliation status.
- [ ] Verification results are factual; skipped checks are identified rather
  than implied to have passed.
- [ ] The authoritative-source mapping is sufficient for another maintainer to
  transfer the change without reverse-engineering the diff.
- [ ] `Transfer required` and reconciliation status are consistent; `No` is
  used only for an owner-confirmed `Not applicable` path.
- [ ] Reconciliation claims include evidence.

An incomplete or missing record blocks approval of the protected-path change.
