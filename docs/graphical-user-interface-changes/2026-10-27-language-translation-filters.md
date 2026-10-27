# Correct Language Translations filtering

## Change identity

- **Date first changed:** 2026-10-27
- **Author/implementer:** Codex, for Lemuel van der Merwe
- **Task/issue/PR:** Component-Builder issue #1309; accepted four-issue fix proposal; [PR #63](https://github.com/extension-builder/joomla/pull/63).
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** The active task accepts implementation of the reviewed #1309 proposal, including `admin/src/Model/Language_translationsModel.php`.
- **Authorized paths:** `admin/src/Model/Language_translationsModel.php`.
- **Authorized outcome:** Correct the Language Translations filters and their cached result identity.
- **Permission summary:** Implement the reviewed model changes alongside the shared filtering helper and regression coverage.

## Purpose and rationale

The reporter clarified the primary trigger: compilation collects new source strings and inserts translation rows before anyone edits them. `Compiler/Language/Set::updateOrInsertString()` queues seven values; `Compiler/Language/Insert::execute()` previously omitted the `translation` column, whose schema default is SQL NULL. The existing single-item administrator save maps an absent translation to `''` (outside API PATCH), which explains why manually saving a row made it visible to the old untranslated filters. The old `NOT LIKE` and equality predicates excluded SQL NULL.

The fix addresses both new and existing rows. Compiler insertion now explicitly stores `''`, matching the existing manual-save representation while retaining the seven-value queue and batching contract. The shared helper also classifies legacy NULL and other empty values as untranslated, so users do not have to open and resave existing compiler-created rows. Exact language entries with nonblank text drive translated matching; incidental text containing a language tag and blank subform entries must not count as translations. The administrator model rejects malformed filter input and includes all custom filters in its store identifier. The manual-save implementation is unchanged.

## Affected paths

### Created

- None under the protected administrator or media paths.

### Modified

- `admin/src/Model/Language_translationsModel.php`

### Moved or renamed

- None.

### Deleted

- None.

## Implementation details

### `admin/src/Model/Language_translationsModel.php`

- **Change type:** Modified.
- **Stable locations:** `Language_translationsModel::getListQuery()` custom translated, untranslated and extension filters; `Language_translationsModel::getStoreId()`.
- **What changed:** Require scalar string language selections, validate the extension selection before splitting its type and GUID, and include the three custom selections in the cache identity as a JSON tuple.
- **Why:** Malformed selections must produce an empty result rather than warnings or type errors; different filter combinations must not share a cached list/total.
- **Related paths:** `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Utilities/FilterHelper.php`; `libraries/vendor_jcb/tests/Contract/LanguageTranslationsFilterTest.php`; `libraries/vendor_jcb/tests/gui/specs/language_translations.spec.js`.

### Related compiler persistence correction

- `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Language/Insert.php::execute()` adds the `translation` column and appends a quoted empty string when rendering each complete seven-value row. Components, modules and plugins share this behavior. No schema migration or manual-save change is required.
- `PersistenceTest` checks the explicit eight-column SQL contract for all three extension types, preserves the seven-value queue below the batch threshold and verifies clearing after execution. `SetAndTranslationTest` also drives the actual language collector through the actual Insert service and checks its canonical empty translation.

## Impact

- **Behavioral impact:** New compiler-created rows use the manual-save empty representation. Existing SQL NULL rows appear immediately in untranslated results. Translation matching is exact, malformed selections produce predictable empty results, and list caches distinguish custom filters.
- **Visual impact:** Existing controls and labels are retained.
- **Accessibility impact:** No markup or accessible-name changes.
- **Compatibility impact:** Administrator host behavior; no compiler target selector or SQL JSON-function dependency is introduced.
- **Generated-output impact:** The JCB administrator model must be reconciled to JCB's own maintained definitions before future self-compilation. Generated user components are unaffected.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php -d memory_limit=1G vendor/bin/phpunit --filter 'FilterHelperTest\|LanguageTranslationsFilterTest\|PersistenceTest\|SetAndTranslationTest'` from the vendor test project | PHP 8.3.6, pinned Joomla 6.1.2 source | Pass: 36 tests, 168 assertions |
| `php -l` on the helper, compiler Insert, administrator model and fixture script | PHP 8.3.6 | Pass |
| `node --check libraries/vendor_jcb/tests/gui/specs/language_translations.spec.js` | Available Node runtime | Pass |
| `bash -n .github/gui-tests/run.sh` | Bash | Pass |
| `git diff --check` | Current working diff | Pass |
| [Hosted disposable GUI run 40520663994](https://github.com/extension-builder/joomla/actions/runs/40520663994), job `Playwright against joomengine` | Installed source at `bf9a7a7371872f5999e2de50f75eae9c2ad54052`, Chromium against joomengine | Pass: 32 tests, 0 skipped, 0 failed; all three #1309 journeys passed |

### Manual scenarios

No separate manual browser session was performed. The installed-browser scenarios below ran automatically in the hosted disposable stack.

### GUI test coverage

- **Spec:** `libraries/vendor_jcb/tests/gui/specs/language_translations.spec.js`, with disposable fixture data supplied by `.github/gui-tests/language-translation-fixtures.php` through the existing harness.
- **Compiler regression:** The fixture invokes the actual `Compiler/Language/Insert` service, then checks that the new row contains `''` and a separate legacy row still contains SQL NULL. Neither row is passed through the administrator save model. The browser journey must find both in untranslated and none results immediately.
- **Other cases:** Blank JSON/subform values, a language tag appearing only inside another translation's text, valid `"0"`, published-language all, combined extension/language selections, clearing filters, contradictory selections, pagination and page clamping when the result set shrinks. The pagination case does not claim a general filter-change reset.
- **Hosted evidence:** The run above completed seeding successfully, including the real compiler insertion and strict stored-value assertions, before Playwright began. Its three #1309 cases passed: `matches exact nonblank entries and combines the extension filter`; `finds compiler-created and legacy null rows before manual save and keeps all/none distinct`; `paginates and clamps the page when the selected result set becomes smaller`. The run retained the `gui-tests` report artifact (ID `12741821921`) and exact-source provenance. The artifact allowlist now also retains the language fixture seed/cleanup logs and manifest on subsequent runs.

### Checks not performed

- The disposable Joomla/browser harness was not run locally because Docker is unavailable in this execution environment; the hosted run above completed it successfully.
- Rebuilding JCB from the owner's authoritative definitions has not run because that maintenance dataset is absent from this checkout.

## Risks, limitations, and rollback

- Translation JSON is inspected row by row through a database iterator; only matching identifiers and one decoded row are retained in PHP. Matching remains a full-table operation, as the old wildcard query was, and the model retains its existing ID-list query contract.
- Invalid or empty stored JSON is classified as having no usable translations, without rewriting data.
- Roll back the compiler insertion change, helper, model, associated tests and this record together; no data migration is required.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Model/Language_translationsModel.php` | JCB's Language Translation admin-view definition and list-model custom code; exact owning record/GUID is not available in this checkout | Yes | Pending source identification | Owner: JCB maintainer. Locate the definition producing `Language_translationsModel`; transfer the custom `getListQuery()` filter guards and the `getStoreId()` custom-filter tuple, then self-compile and compare those methods against this patch. |

The helper is an existing Power (`Componentbuilder/Utilities/FilterHelper`): synchronize its changed `translation()`, `translations()` and new private `translationLanguages()` method into the same maintained Power through the existing Power/extrusion workflow. Synchronize `Componentbuilder/Compiler/Language/Insert::execute()` into its existing maintained Power as well: preserve the seven queued values and append the empty translation only when building SQL. Do not introduce duplicate classes or alter their namespaces. For the administrator definition, `php_getlistquery` is the custom list-query insertion area; locate the maintained customization that generates the store-ID extension before transfer. The exact source records and transfers are deliberately not marked complete.

## Final consistency check

- [x] Protected paths and stable symbols are enumerated.
- [x] The implementation is within the accepted proposal.
- [x] Behavioral and visual impact are explicit.
- [x] Transfer is required and reconciliation is not claimed.
- [x] Focused results and hosted GUI evidence are recorded; the unperformed authoritative-source self-build is explicit.
