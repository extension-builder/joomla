# Restore Language Translations filter cache identities

## Change identity

- **Date first changed:** 2026-10-31
- **Author/implementer:** Codex, for Lemuel van der Merwe
- **Task/issue/PR:** [PR #64](https://github.com/extension-builder/joomla/pull/64), follow-up request to fix every failing workflow.
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** The active PR #64 follow-up requires all workflows to pass, including existing failures on main, and directs completion of the necessary fixes.
- **Authorized paths:** `admin/src/Model/Language_translationsModel.php`, limited to the failing custom-filter cache contract.
- **Authorized outcome:** Restore distinct cached lists and totals for custom language and extension selections.
- **Permission summary:** Repair the production regression responsible for the blocking Language Translations cache test as part of the requested complete CI repair.

## Purpose and rationale

Release commit `a8d89f35f` removed the custom-filter cache tuple introduced in
`1cd84b2e8` for issue #1309. The model still applied those filters to its SQL,
but `getStoreId()` no longer distinguished their selections. Reusing a model
after changing one of these filters could therefore return a previous cached
list or total. A library-only change cannot restore this model's overridden
cache identity.

Five additional contract failures came from test scaffolding: the regenerated
model uses `DatabaseDriver::createQuery()`, while its test still configured
`getQuery()`. The test now mocks the actual database factory method and requires
one call. Its empty-query and returned-query assertions are retained.

## Affected paths

### Created

- None under protected paths.

### Modified

- `admin/src/Model/Language_translationsModel.php`

### Moved or renamed

- None.

### Deleted

- None.

## Implementation details

### `admin/src/Model/Language_translationsModel.php`

- **Change type:** Modified.
- **Stable location:** `Language_translationsModel::getStoreId()`.
- **What changed:** Restore the JSON tuple of `filter.extension`, `filter.translated` and `filter.not_translated` in the cache identity, preserving the existing parent and built-in filter identities.
- **Why:** Each custom selection affects the SQL result and must also affect cached list and total identity.
- **Related paths:** `libraries/vendor_jcb/tests/Contract/LanguageTranslationsFilterTest.php`; `libraries/vendor_jcb/tests/gui/specs/language_translations.spec.js`.

## Impact

- **Behavioral impact:** Changes to any of the three custom filters invalidate the previous cached list and total; clearing the filters restores the original cache identity.
- **Visual impact:** No control, label or layout changes.
- **Accessibility impact:** No markup or accessible-name changes.
- **Compatibility impact:** Administrator host behavior; no new target-version branch or database feature.
- **Generated-output impact:** No user-component compiler output change. JCB's maintained definition must preserve this customization when regenerating its own model.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php -d memory_limit=1G vendor/bin/phpunit --filter LanguageTranslationsFilterTest --exclude-group known-defect --no-coverage` | PHP 8.3.6, pinned Joomla 6.1.2 test runtime | Pass: 6 tests, 23 assertions |
| `php -l` on the model and contract test; `node --check` on the GUI spec; scoped `git diff --check` | PHP 8.3.6 and available Node runtime | Pass |
| Hosted GUI workflow | Disposable installed Joomla and Chromium | Pending execution |

### Manual scenarios

No separate manual browser session performed.

### GUI test coverage

- **Spec updated:** `libraries/vendor_jcb/tests/gui/specs/language_translations.spec.js`.
- The combined-selection journey now clears each custom selection in turn and verifies restoration of the complete original result set. Existing journeys also cover pagination, exact translated/untranslated selections and contradictory filters.
- The PHPUnit contract directly checks same-model cache identity for both list and total operations; separate browser requests alone cannot demonstrate that internal cache boundary.

### Checks not performed

- Local disposable browser harness requires Docker, unavailable in this execution environment; hosted CI supplies the installed-browser check.
- Authoritative JCB self-compilation is unavailable because its maintenance dataset is not included in this checkout.

## Risks, limitations, and rollback

- **Known risks:** The cache fix can be overwritten again by self-compilation until the authoritative definition is reconciled.
- **Known limitations:** No maintenance database or schema mutation is performed.
- **Rollback:** Revert this model's tuple, corresponding test changes and this record together; no data migration is required.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Model/Language_translationsModel.php` | JCB Language Translation admin-view definition and list-model custom code; exact owning record/GUID remains absent from the checkout | Yes | Pending source identification | JCB maintainer must locate the customization generating `getStoreId()`, add the three-filter tuple, self-compile and verify the retained cache contract. Prior record: `2026-10-27-language-translation-filters.md`. |

## Final consistency check

- [x] The sole protected path and stable symbol match the bounded restoration.
- [x] Behavioral impact, original regression and test-boundary correction are distinguished.
- [x] GUI coverage and the stronger same-model unit contract are identified.
- [x] Source transfer remains explicitly required; reconciliation is not claimed.
- [ ] Final local and hosted verification results are recorded.
