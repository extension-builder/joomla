# Add an Advanced action to repair existing Power namespaces

## Change identity

- **Date first changed:** 2026-10-03
- **Author/implementer:** Codex, for Lemuel van der Merwe
- **Task/issue/PR:** [PR #57](https://github.com/extension-builder/joomla/pull/57), branch `fix/extrusion-existing-namespace-repair`
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active request accepts the proposed “repair existing Power namespaces” workflow and asks for the extra button in Advanced settings, with changes ready to copy into JCB.
- **Authorized paths:** `admin/tmpl/extrusion/default.php`, `admin/assets/js/extrusion.js`, and necessary Extrusion configuration plumbing in `admin/src/Model/AjaxModel.php`.
- **Authorized outcome:** Expose a dedicated, opt-in namespace-only repair using existing automatic identity matching, preview, selection, and import approval.
- **Permission summary:** Add a repair button under Advanced settings; keep ordinary extrusion behavior and natural language strings.

## Purpose and rationale

Earlier extrusion could store literal component namespace segments for new Powers. Ordinary Update deliberately preserves existing namespaces. The new dedicated action scans the same source, matches Powers automatically, previews only namespace changes, and applies them through the existing guarded plan/commit pipeline.

## Affected paths

### Created

- None

### Modified

- `admin/tmpl/extrusion/default.php`
- `admin/assets/js/extrusion.js`
- `admin/src/Model/AjaxModel.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/tmpl/extrusion/default.php`

- **Change type:** Modified
- **Stable location(s):** `#extrusion-namespace-repair-options`; `#extrusion-repair-namespaces-button`; pairing/import/confirmation label anchors; `window.JCBExtrusion.text`.
- **What changed:** Adds the dedicated Advanced repair action and explanatory text; provides natural-string labels for repair preview, application, confirmation, and report.
- **Why:** Makes the operation explicit and portable to JCB without generated language constants.
- **Related paths/symbols:** `admin/assets/js/extrusion.js`, repair-mode backend.

### `admin/assets/js/extrusion.js`

- **Change type:** Modified
- **Stable location(s):** `harvest`, `readConfig`, `beginRun`, `proposal`, `powerDetails`, `row`, `openConfirmation`, `runImport`, `DOMContentLoaded` wiring.
- **What changed:** Sends `repair_namespaces = 1` only for the repair action; validates an explicitly selected update target and library source folders; retains mode through review/diff/apply; hides creation actions; presents repair-specific labels and namespace proposals; resets to normal mode on ordinary Harvest. The existing scope-based confirmation policy remains unchanged.
- **Why:** Reuses matching/review safeguards while preventing the dedicated repair UI from suggesting new records or unrelated imports.
- **Related paths/symbols:** `AjaxModel::extrusionEngines`, shared `repairNamespaces` config.

### `admin/src/Model/AjaxModel.php`

- **Change type:** Modified
- **Stable location(s):** `AjaxModel::extrusionEngines`.
- **What changed:** Validates dedicated repair setup (Update mode, selected target, library source folders, and nonzero source context when supplied), adapts `repair_namespaces` to shared `repairNamespaces`, and selects existing-record update behavior.
- **Why:** Enforces the operation's target requirements at the AJAX boundary as well as in the browser.
- **Related paths/symbols:** Extrusion/Powers engine repair mode; normal harvest/weigh/diff/import endpoints.

## Impact

- **Behavioral impact:** Advanced repair operates on existing matched Powers only; namespace-only payloads retain GUIDs, relationships, code, and settings. Unmatched classes cannot create Powers in this mode. Existing approval scopes, dry-run behavior, and stale-plan checks remain active.
- **Visual impact:** Extra action below Advanced options; repair-specific pairing, apply, confirmation, and report labels.
- **Accessibility impact:** Repair button has a linked description; mode information uses a live status; existing review/confirmation keyboard behavior remains.
- **Compatibility impact:** Existing AJAX configuration gains one optional boolean; ordinary extrusion retains its default mode.
- **Generated-output impact:** Repair substitutes established namespace placeholders only when source/output equivalence is validated. No hand-generated translation keys or `.ini` entries are added.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `node --check admin/assets/js/extrusion.js` | Local Node runtime | Pass |
| `php -l admin/tmpl/extrusion/default.php` and `php -l admin/src/Model/AjaxModel.php` | PHP 8.3 local runtime | Pass — no syntax errors |
| `git diff --check` | Shared feature branch | Pass |
| Full `composer test` | PHP 8.3.6 / Joomla 6.1.2 source runtime | Pass — 4,423 tests, 47,939 assertions |
| Working-tree added-line PHP contribution style | PHP 8.3.6 | Pass — all 16 changed library/test PHP files |
| Composer validation, platform requirements and locked dependency audit | Composer 2.10.2 | Pass |
| Installed GUI and full compiler comparisons | GitHub Actions | Pass — all seven checks on `50fb7f9`; [26 Playwright tests and actual repair persistence](https://github.com/extension-builder/joomla/actions/runs/37135419394), [Joomla 3/4/5/6 compiler comparisons](https://github.com/extension-builder/joomla/actions/runs/37135419351), [PHP 8.3/8.4](https://github.com/extension-builder/joomla/actions/runs/37135419293). Final-head check results are retained on PR #57 |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Repair source scan, automatic matching, namespace-only preview, dry-run apply | Disposable Joomla GUI suite | Pass — installed GUI and real Data/Power compiler verification; retained GUI check evidence on PR #57 |

### GUI test coverage

- **Spec files added/updated:** `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`, owned by the regression-test agent in this change.

### Checks not performed

- Local Docker execution was unavailable. Hosted GUI, actual repair persistence and Joomla 3/4/5/6 compiler checks passed; execution evidence is linked above. Owner-side recompilation after copying into the authoritative JCB definition remains a separate source reconciliation step.

## Risks, limitations, and rollback

- **Known risks:** Shared or foreign Powers retain the existing explicit scope approval rules; namespace changes can affect their consumers.
- **Known limitations:** Requires an existing selected component and original library source folders; unresolved identity/namespace evidence must be reviewed.
- **Rollback:** Revert the repair UI/AJAX hunks with the backend repair implementation, regression tests, and this record. Keep the separate uploaded-layout alignment if desired.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/tmpl/extrusion/default.php` | Owner's JCB custom admin view `Extrusion`, default template | Yes | Source identified | Copy template into the existing custom admin view; compile and verify natural-string generation |
| `admin/assets/js/extrusion.js` | Owner's JCB custom admin view `Extrusion`, view JavaScript asset | Yes | Source identified | Copy view JavaScript together with the template text map; compile and run repair preview |
| `admin/src/Model/AjaxModel.php` | Owner's JCB component `Componentbuilder`, AJAX PHP implementation `extrusionEngines` | Yes | Source identified | Copy changed `extrusionEngines` method into its JCB AJAX source; compile and verify matching config on harvest/weigh/diff/import |

## Final consistency check

- [x] The affected-path lists match the final diff exactly.
- [x] Every path has a stable location and exact what/why details.
- [x] Behavioral and visual impact are explicit.
- [x] Verification records actual results and identifies skipped checks.
- [x] Every path has an authoritative-source mapping and reconciliation status.
- [x] `Transfer required` is `No` only for an owner-confirmed `Not applicable` path; it is `Yes` for every other status.
- [x] The changed interface behavior has GUI test coverage, or the record says plainly why not.
- [x] The implementation remains within the cited permission.
