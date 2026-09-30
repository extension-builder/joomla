# Compact extrusion review, filters and group selection

## Change identity

- **Date first changed:** 2026-09-30
- **Author/implementer:** Codex
- **Task/issue/PR:** https://github.com/extension-builder/joomla/pull/54
- **Change record status:** Draft

## Explicit permission

- **Permission reference:** The maintainer's 30 September 2026 review of PR #54,
  accompanied by five screenshots, explicitly requests changes to the extrusion
  administrator view before importing it into JCB's custom administrator view.
- **Authorized paths:** `admin/assets/js/extrusion.js`,
  `admin/assets/css/extrusion.css`, `admin/tmpl/extrusion/default.php`.
- **Authorized outcome:** Compact inline status badges, dropdown filters,
  group selection, action buttons beneath each row, simplified target details
  and ambiguity guidance, and acknowledgement when Import is clicked.
- **Permission summary:** Implement this bounded extrusion UI refinement and
  its tests; retain server-side identity, approval and stale-review safeguards.

## Purpose and rationale

Full-width status badges, repeated scope metadata and right-aligned actions
make a large Power review harder to scan. Text search alone cannot distinguish
matching state from the operation that would actually be performed. Group
selection reduces repeated checkbox clicks. Import-time confirmation replaces
the persistent acknowledgement panel without weakening its approval contract.

These are administrator presentation and interaction changes; changes confined
to the extrusion engine would not deliver the requested interface.

## Affected paths

### Created

- None

### Modified

- `admin/assets/js/extrusion.js`
- `admin/assets/css/extrusion.css`
- `admin/tmpl/extrusion/default.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/assets/js/extrusion.js`

- **Change type:** Modified
- **Stable location(s):** `matchingStatus`, `plannedChange`, `rowHeading`,
  `powerDetails`, `groupCheck`, `selectableRows`, `applyFilter`, `refreshBulkBar`,
  `wireBoard`, `bulk`, `renderBoard`, `renderReview`, `openConfirmation`,
  `closeConfirmation`, `confirmImport`, `runImport`, `runConfig`,
  `invalidateReview`.
- **What changed:** Render small inline statuses and actual target details;
  remove consumer/scope lines and alternative-candidate scope suffixes. Combine
  type, matching status, validated planned change and text filters; update
  visible/total counts and hide empty groups. Index candidates once per render.
  Group checkboxes select matching nested decision rows even when collapsed,
  and reflect partial selection. Filtering clears hidden checkbox selections
  while retaining explicit decisions already made; bulk actions use visible
  selected rows. Import confirmation snapshots the current fingerprint and
  required approvals, sends them only after acknowledgement, and discards them
  on cancellation, changed review or completed/interrupted request. Ambiguity
  has a short notice with a direct filter button; other blockers retain a
  collapsed explanation.
- **Why:** Make matching and planned changes independently filterable, make
  batch decisions usable and present acknowledgement at the import action.
- **Related paths/symbols:** The extrusion stylesheet and template text map.

### `admin/assets/css/extrusion.css`

- **Change type:** Modified
- **Stable location(s):** `.extrusion-row`, `.extrusion-identity`,
  `.extrusion-row-heading`, `.extrusion-match-status`, `.extrusion-actions`,
  `.extrusion-power-evidence`, `.extrusion-filters`, `.extrusion-group-check`,
  `.extrusion-confirm-actions`, `.extrusion-diff`.
- **What changed:** Grid rows keep selection beside the identity and actions
  beneath it at every width. Inline badges do not stretch; source and target
  namespaces wrap. Filters and buttons wrap at narrow widths, and diff panels
  still span the whole row. Evidence styles move out of the template into the
  view stylesheet. Below 768px, the filter toolbar stays in document flow and
  the board uses its natural height, preventing stacked filters from obscuring
  rows inside a short scrolling panel.
- **Why:** Preserve a readable hierarchy at both wide and narrow viewports.
- **Related paths/symbols:** `row` and group/toolbar markup.

### `admin/tmpl/extrusion/default.php`

- **Change type:** Modified
- **Stable location(s):** Board toolbar, review controls and
  `window.JCBExtrusion.text`.
- **What changed:** Add labelled Entity type, Matching status and Planned change
  dropdowns, retain text search, show result counts and empty-filter feedback,
  and add a Show ambiguous items button. Replace the persistent scope checkbox
  with a labelled confirmation dialog using short system-impact wording and a
  separate dry-run notice. Visible strings remain natural `Text::_()` inputs.
  Filter labels explicitly reference sibling controls so option text is not
  included in their accessible names.
- **Why:** Keep visible labels accessible and ready for JCB's language import.
- **Related paths/symbols:** Extrusion JavaScript filter and review helpers.

## Impact

- **Behavioral impact:** Filters and group selection support focused batch
  decisions. Import confirmation retains fingerprint-bound acknowledgement for
  plans requiring approval, including dry-run reviews; unscoped creation/no-op
  flows retain the direct import action. Shared field members remain governed
  by their owner unless individually detached.
- **Visual impact:** Status beside the title, actions below details, and only
  actual target name, GUID and namespace shown for matched Powers.
- **Accessibility impact:** Controls receive visible or accessible labels;
  partial group selection uses the native indeterminate checkbox state. The
  confirmation initially focuses Cancel, traps Tab, closes on Escape and
  restores focus to Import.
- **Compatibility impact:** Existing supported Joomla/browser environment and
  AJAX contract; no new engine or compiler behavior is intended.
- **Generated-output impact:** Administrator markup/styles/scripts change;
  compiled extension output is unaffected.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `node --check admin/assets/js/extrusion.js`, GUI spec syntax, `php -l admin/tmpl/extrusion/default.php`, `git diff --check` | Local Node.js/PHP 8.3.6 | Pass |
| `composer test` | PHP 8.3.6, restored required GD extension, locked dependencies | Pass — 4,280 tests / 47,011 assertions |
| Unit and quality workflow [36718963656](https://github.com/extension-builder/joomla/actions/runs/36718963656) | PHP 8.3.35 and 8.4.26 | Pass — 4,280 tests / 47,011 assertions each; locked security audit, style, ownership, platform and cleanup gates pass |
| Compiler matrix [36718963670](https://github.com/extension-builder/joomla/actions/runs/36718963670) | Actual Joomla targets 3/4/5/6 | Pass — all four comparisons |
| Compiled API [36718963635](https://github.com/extension-builder/joomla/actions/runs/36718963635) | Installed Joomla and compiled fixture | Pass |
| Installed extrusion browser suite [36718963748](https://github.com/extension-builder/joomla/actions/runs/36718963748) | GitHub Actions, Joomla, Chromium, `39d23a9f` | 20 passed / 1 failed; only the no-op fixture assertion remains under correction |
| Installed Playwright label-resolution probe | Local selector helpers | Pass after separating labels from dropdown option text |

The no-op fixture's manually seeded Power bodies included a final newline that
the actual class reader removes. Its zero-addition/one-deletion response was a
real normalization change. The fixture now seeds the canonical stored bodies;
the browser assertion still requires `changed: false` and both matched records
under No change. No production comparison or assertion was relaxed.

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Five supplied screenshot review | Maintainer's installed extrusion view | Reviewed; layout requirements identified |
| Desktop/narrow browser screenshots | Chromium, 1366px and 390px, run 36718963748 | Desktop badge/actions verified; narrow sticky-toolbar overlap identified and corrected, awaiting rerun |

### GUI test coverage

- **Spec files added/updated:**
  `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`.
- Coverage accompanies the implementation for wide/narrow layout, combined
  filters, nested/partial group selection, hidden-row batch protection,
  matched update versus effective no-op, ambiguity filtering and import
  confirmation/cancellation. Tests inspect real
  AJAX payloads and leave fixture definitions unchanged through dry runs.

### Checks not performed

- Installed browser execution awaits the corrected-label checkpoint;
  Docker is unavailable in the local workspace, so the existing CI harness is
  the authoritative runtime check.
- Rebuilding the authoritative custom administrator view is the maintainer's
  stated next step after this UI refinement; it has not been performed here.

## Risks, limitations, and rollback

- **Known risks:** Filters must not cause hidden items to receive a batch
  decision unexpectedly; changed previews must invalidate prior approval.
- **Known limitations:** The exact authoritative custom-view record GUID is
  not present in this checkout.
- **Rollback:** Revert these three protected files and their corresponding
  GUI test changes together, retaining the earlier engine safety fixes.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/assets/js/extrusion.js` | Extrusion custom administrator view JavaScript; exact record GUID not supplied | Yes | Pending source identification | Maintainer explicitly plans to build/import the custom administrator view after reviewing this UI. Transfer the complete revised script and regenerate this path. |
| `admin/assets/css/extrusion.css` | Extrusion custom administrator view CSS; exact record GUID not supplied | Yes | Pending source identification | Maintainer to transfer the revised styles with the view and verify the regenerated layout. |
| `admin/tmpl/extrusion/default.php` | Extrusion custom administrator view default template; exact record GUID not supplied | Yes | Pending source identification | Maintainer to transfer toolbar/review markup and natural text map, then compare regenerated output. |

## Final consistency check

- [x] Affected paths and stable locations match the protected diff.
- [ ] Behavioral, visual and accessibility impact verified.
- [ ] Verification reports actual outcomes and skipped checks.
- [x] Every protected path has a source mapping and honest transfer status.
- [ ] Changed behavior has passing browser coverage.
- [x] Implementation scope follows the maintainer's explicit UI request.
