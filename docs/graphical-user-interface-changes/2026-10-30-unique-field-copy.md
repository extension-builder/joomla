# Bound unique-field generation when copying compiled records

## Change identity

- Date first changed: 2026-10-30
- Implementer: Codex
- Task: upstream Component-Builder issue #1328; review in extension-builder/joomla.
- Status: Draft; validation in progress.

## Explicit permission

The active request explicitly asks to fix the compiler, create a separate
branch and PR, and applies the attached `JCB_Issue_1328_Agent_Handover.md`.
The handover identifies compiler templates under `admin/compiler` and requires
all affected generation paths to be inspected. This authorizes the six model
template updates below for the unique-field copy fix.

## Purpose and rationale

The compiler currently emits an unlimited string-increment loop for every
unique SQL column. Numeric comparisons can keep matching the original row
even as the string suffix changes. The static method bodies live in compiler
templates, so library-only edits cannot fix emitted extensions.

## Affected paths and implementation

Modified paths (no creations, renames or deletions under protected trees):

- `admin/compiler/joomla_3/AdminModel.php`
- `admin/compiler/joomla_3/AdminModel_site.php`
- `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php`

In every path, `generateUnique()` uses compiled SQL field settings, rejects
non-text collisions, respects character lengths and nullable NULL, generates
valid GUID replacements, detects repeated matched primary keys, and limits
collision lookups to 100. It reports translated model errors and returns false
on failure. Related library emitters propagate strict false before writing.

## Impact

Behavior: Save as Copy and Batch Copy fail promptly for numeric collisions;
available numeric values are preserved and text suffix generation remains.
There is no layout or accessibility change. Frontend edit templates receive
the same protection. Joomla 4/5 share the J4 templates; J6 has its own pair.
J3's pair stays consistent with the shared emitters and target catalogue.

## Verification

Pending focused generated-code execution tests, renderer contracts, syntax,
style, ownership and full PHPUnit gates; results will be recorded before handoff.

GUI specs: none added. These are compiler source templates, not installed JCB
views. The regression checks execute the emitted helpers and copy call sites.
An installed generated-extension browser test and full-tree golden comparison
require a disposable Joomla/database environment; any unrun checks are disclosed.

## Risks, limitations and rollback

Existing installed components must be recompiled and reinstalled. The installed
JCB models in this checkout configure only GUID unique fields or no unique
fields, and are not a reproduction of the reported numeric-column failure.
They are left for authoritative self-compilation rather than duplicating this
compiler change across 51 generated models. Batch processing retains its
existing sequential semantics: earlier successful copies are not rolled back
when a later record fails. SQL UNIQUE constraints remain the final concurrency
guard. Roll back by reverting this PR's commits and recompiling affected
extensions with the previous compiler.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source | Transfer required | Status | Next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_3/AdminModel.php` | Corresponding core compiler template | Yes | Source identified | Maintainer ports template with shared emitters if retaining J3 target |
| `admin/compiler/joomla_3/AdminModel_site.php` | Corresponding core compiler template | Yes | Source identified | Maintainer ports template with shared emitters if retaining J3 target |
| `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php` | Corresponding J4/J5 core compiler template | Yes | Source identified | Maintainer ports after review and recompiles |
| `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODEL.php` | Corresponding J4/J5 core compiler template | Yes | Source identified | Maintainer ports after review and recompiles |
| `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php` | Corresponding J6 core compiler template | Yes | Source identified | Maintainer ports after review and recompiles |
| `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php` | Corresponding J6 core compiler template | Yes | Source identified | Maintainer ports after review and recompiles |

Upstream core and maintained JCB definitions are not modified by this PR.
