# Bound unique-field generation when copying compiled records

## Change identity

- Date first changed: 2026-10-30
- Implementer: Codex
- Task: upstream Component-Builder issue #1328; review in extension-builder/joomla.
- Status: Draft PR; focused validation complete, hosted output review in progress.

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
The GUID candidate method is emitted only for views with a GUID column, so
ordinary components do not gain an unused Power dependency.

## Impact

Behavior: Save as Copy and Batch Copy fail promptly for numeric collisions;
available numeric values are preserved and text suffix generation remains.
There is no layout or accessibility change. Frontend edit templates receive
the same protection. Joomla 4/5 share the J4 templates; J6 has its own pair.
J3's pair stays consistent with the shared emitters and target catalogue.

## Verification

PHP 8.3.6, Composer 2.10.2, pinned Joomla CMS 6.1.2 and locked test dependencies.
Commands below run from `libraries/vendor_jcb/tests` unless otherwise noted.

| Check | Result |
| --- | --- |
| `vendor/bin/phpunit --filter 'ModelAliasTitleFixTest\|VersionedModelBatchTest\|VersionedViewGuardsTest' --exclude-group known-defect --no-coverage` | Initial renderer validation: 55 tests, 160 assertions pass; conditional-GUID assertions added afterward |
| `vendor/bin/phpunit VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/GenerateUniqueTest.php --no-coverage` | 66 tests, 1,752 assertions pass before the conditional-GUID refinement; final totals recorded in the PR |
| Actual baseline helper bodies, bounded table adapter | All six still collide through 105 queries; only the test safety guard stops them at query 106 |
| Actual patched helper bodies against disposable MariaDB 10.11.14 | 66 checks pass (11 per template); original row unchanged, database dropped and daemon stopped afterward |
| Complete PHPUnit suite, excluding documented known-defect group | 5,325 tests, 66,038 assertions; 14 errors and 16 failures |
| Untouched baseline `a8d89f35fdffad8275fc158d175bd37911bb867b`, same dependencies and PHP | 5,246 tests, 64,249 assertions; the exact same 14 errors and 16 failures by test/dataset identity; zero candidate-only failures |
| `php bin/check-php-style.php --base=a8d89f35fdffad8275fc158d175bd37911bb867b` | Pass |
| `php bin/check-test-ownership.php --base=a8d89f35fdffad8275fc158d175bd37911bb867b` | Pass: all 1,546 eligible declarations owned |
| `php bin/check-moved-conditions.php --base=a8d89f35fdffad8275fc158d175bd37911bb867b` | Pass: no moved legacy conditions |
| `php bin/check-container-keys.php` | Pass |
| Changed PHP syntax; `git diff --check` | Pass; runtime tests also parse and execute the generated method bodies |

The 30 shared suite problems are: missing distributed privacy definition (14),
translation filters (6), ItemSave contracts (5), plugin headers (3), compiler
emission style in unrelated ItemSave code (1), and SFTP (1). These are not
quarantined, weakened or fixed as part of this issue.

The live SQL reproduction confirms `3881 (2)` and `3881 (3)` still match
`INT 3881`, while the suffixed value does not match `VARCHAR '3881'`. BIGINT
and DECIMAL show the same numeric coercion under `STRICT_TRANS_TABLES`.

GUI specs: none added. These are compiler source templates, not installed JCB
views. The regression checks execute the emitted helpers and copy call sites.
The live helper/database checks do not constitute an installed Joomla browser
test. Hosted complete-compilation and GUI/API workflows are reviewed on the PR;
their final results and any remaining limitations are recorded there.

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
The 100-query bound applies to `generateUnique()`; pre-existing title/alias and
record-key GUID-generation routines are outside this focused fix. Compiled
metadata assumes the installed SQL schema matches the component definition.

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
