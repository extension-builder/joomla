# Bound unique-field generation when copying compiled records

## Change identity

- Date first changed: 2026-10-30
- Implementer: Codex
- Task: upstream Component-Builder issue #1328; review in extension-builder/joomla.
- Status: Ready for review; the 2026-10-31 follow-up repairs all 30 previously failing blocking-suite cases.

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
| `vendor/bin/phpunit --filter 'GenerateUniqueTest\|ModelAliasTitleFixTest\|VersionedModelBatchTest\|VersionedViewGuardsTest' --exclude-group known-defect --no-coverage` | Final focused validation: 122 tests, 1,928 assertions pass |
| `vendor/bin/phpunit VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/GenerateUniqueTest.php --no-coverage` | 66 tests, 1,758 assertions pass; includes actual emitted conditional GUID factory |
| Actual baseline helper bodies, bounded table adapter | All six still collide through 105 queries; only the test safety guard stops them at query 106 |
| Final helpers plus emitted schema/GUID methods against disposable MariaDB 10.11.14 | 66 checks pass (11 per template); original row unchanged, database dropped and daemon stopped afterward |
| `composer test` (complete PHPUnit suite, excluding documented known-defect group) | Follow-up: 5,338 tests, 66,353 assertions pass; zero errors or failures |
| Initial diagnostic baseline `a8d89f35fdffad8275fc158d175bd37911bb867b`, same dependencies and PHP | 5,246 tests, 64,249 assertions; the exact same 14 errors and 16 failures by test/dataset identity; zero candidate-only failures |
| `php bin/check-php-style.php --base=a8d89f35fdffad8275fc158d175bd37911bb867b` | Pass |
| `php bin/check-test-ownership.php --base=a8d89f35fdffad8275fc158d175bd37911bb867b` | Pass: all 1,546 eligible declarations owned |
| `php bin/check-moved-conditions.php --base=a8d89f35fdffad8275fc158d175bd37911bb867b` | Pass: no moved legacy conditions |
| `php bin/check-container-keys.php` | Pass |
| Changed PHP syntax; `git diff --check` | Pass; runtime tests also parse and execute the generated method bodies |
| Hosted Vendor JCB workflow on repaired source `d56b48c` | Pass: PHP 8.3 and 8.4 each pass 5,338 tests / 66,353 assertions; Chromium footer tests pass 5/5; run `41180271140` |
| Hosted GUI workflow on repaired source `d56b48c` | Pass: 32 browser tests, 19 provenance tests and 5 JavaScript tests; run `41180271169`, including the expanded translation-filter journey |
| Hosted API workflow on repaired source `d56b48c` | Pass: 222 checks across two view variants; run `41180271115` |
| Hosted complete compilation for targets J3/J4/J5/J6 | Run `41180271242`: J3/J4/J5 pass unchanged expectations; J6 emits the intended missing-PATCH-record exception and requires the additionally reviewed expectation in `.github/golden-master/expected/README.md` |

The full-tree review covers 1,369 / 291 / 296 / 252 files, respectively. Each
target changes only six expected files: two model files, two language files,
and the two generated READMEs. All eight changed generated PHP models lint;
all language files parse correctly. Baseline and candidate Power snapshots are
identical and all 1,736 installed-source hashes verify on each side. The hosted
rerun additionally exercises the installed discovery probes after accepting
these reviewed output changes; its final status is reported on the PR. The
2026-10-31 J6 expectation also includes the missing-PATCH-record 404 exception;
its original source snapshot had incorrectly bypassed that existing version hook.

The initial baseline comparison identified 30 failing cases: privacy fixture
lookup (14), translation filters (6), ItemSave contracts (5), plugin headers
(3), compiler emission style (1), and SFTP (1). The 2026-10-31 follow-up fixes
all of them in this PR. No assertion is weakened, skipped or moved to the
known-defect group. The existing recovery audit also passes: none of its 84 separately documented
known-defect cases is a clean recovery left outside the blocking suite.

The compiler follow-up uses configured indentation and comment origins for
API PATCH snapshots, invokes the existing Joomla 6 missing-record exception
hook, and protects plugin namespace placeholders during self-compilation.
Privacy tests select the actual shipped definition. Administrator cache and
SFTP message changes have their own records:
`2026-10-31-language-filter-ci-regressions.md` and
`2026-10-31-sftp-key-errors.md`.

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

## Backport and deployment sequence

1. Port `Architecture/Model/UniqueFields.php`, `AliasTitleFix.php`,
   `BatchCopy.php` and `Service/ArchitectureModel.php` together under
   `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/`. Keep the
   new constructor injection and translated error registrations together.
2. Port the appropriate administrator and site templates: the `joomla_4`
   pair supplies J4/J5, and the `joomla_6` pair supplies J6. Keep the J3 pair
   only where that generation target remains in the source catalogue.
3. Carry the regression tests and run them in each maintained branch. Retain
   the target-specific Joomla Power/import spelling; the underlying behavior
   and error propagation are shared.
4. Recompile and reinstall affected extensions. A compiler update does not
   rewrite PHP that was already generated and installed. Self-compile JCB
   from its authoritative definition to synchronize its installed helpers.
   Review custom `generateUnique()` callers (including `LibraryModel::save`)
   so they also handle the new strict false failure result.
5. Verify Save as Copy with unchanged UNIQUE INT 3881 (clear error, no new
   row), an unused INT (preserved), numeric-looking VARCHAR (suffix), and
   Batch Copy (same outcomes) in an installed generated extension.

No upstream core branch, maintained component definition, or deployed Joomla
site is changed by this PR.

## Final consistency check

- The six protected paths above match the compiler-template portion of the final diff; the two administrator follow-up paths have separate records linked above.
- Every compiler-template edit targets `generateUnique()` and has source reconciliation recorded.
- Behavior, visual impact, actual checks and unperformed installed-copy UI coverage are explicit.
- Upstream source transfer remains pending maintainer review.
- The compiler fix and subsequent CI repairs remain separately reviewable in the PR.
