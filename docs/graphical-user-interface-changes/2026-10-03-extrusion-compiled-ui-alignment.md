# Align the Extrusion template with the owner's compiled interface

## Change identity

- **Date first changed:** 2026-10-03
- **Author/implementer:** Codex, for Lemuel van der Merwe
- **Task/issue/PR:** [PR #57](https://github.com/extension-builder/joomla/pull/57), branch `fix/extrusion-existing-namespace-repair`; owner's uploaded `com_componentbuilder_v6_1_6__J6.zip`
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active request: “add the changes from my compiled version”, preserving the repository's uncompiled language strings; the owner specifically identifies button placement and `hidemainmenu = false` in the Extrusion template.
- **Authorized paths:** `admin/tmpl/extrusion/default.php` and `admin/assets/images/icons/extrusion.png` for Extrusion UI alignment.
- **Authorized outcome:** Transfer functional template differences from the uploaded component without replacing recent Power changes or importing generated language constants.
- **Permission summary:** Adopt the uploaded Extrusion layout, dashboard icon, visible Joomla menu, and toolbar Back behavior; retain repository natural `Text::_()` strings.

## Purpose and rationale

The owner maintains the Extrusion custom admin view in JCB and has adjusted its compiled template. The repository must carry these intentional UI changes so later feature code can be copied back without undoing them. Library changes cannot control template placement or Joomla's menu input.

## Affected paths

### Created

- None

### Modified

- `admin/tmpl/extrusion/default.php`
- `admin/assets/images/icons/extrusion.png`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/tmpl/extrusion/default.php`

- **Change type:** Modified
- **Stable location(s):** Template initialization; `Joomla.submitbutton`; `#extrusion-pane-setup` and `#extrusion-pane-running` support blocks.
- **What changed:** Sets `hidemainmenu` to `false`; handles toolbar `extrusion.back` with browser history; transfers uploaded support/banner placement outside the setup settings column and outside the running row.
- **Why:** Retains the owner's interface while making the template portable back into JCB.
- **Related paths/symbols:** `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`.

### `admin/assets/images/icons/extrusion.png`

- **Change type:** Modified
- **Stable location(s):** Extrusion dashboard/menu icon asset.
- **What changed:** Copies the exact uploaded extrusion-head icon bytes in place of the generic JCB box image.
- **Why:** Aligns the repository with the owner's supplied compiled visual interface.
- **Related paths/symbols:** Existing dashboard Extrusion image reference; `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`.

## Impact

- **Behavioral impact:** Joomla's main menu stays visible and the toolbar Back action returns through browser history.
- **Visual impact:** Support content and the extrusion-head dashboard icon follow the uploaded interface. Harvest/import button positions are already identical in the uploaded and repository templates and remain intact.
- **Accessibility impact:** Existing semantic buttons and labels remain; visible menu and Back navigation are covered by the view spec.
- **Compatibility impact:** Running Joomla administrator integration; no target-version compiler change.
- **Generated-output impact:** Natural language strings remain suitable for JCB import; no language `.ini` changes. Generated header, unused import, translation case changes, and comment-only JS/CSS differences are not transferred.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| Uploaded template comparison after resolving generated language keys | Python 3; supplied ZIP and current repository | Pass — only intentional functional differences selected; uploaded JS/CSS have no functional changes |
| PHP syntax validation, `php -l admin/tmpl/extrusion/default.php` | PHP 8.3 local runtime | Pass — no syntax errors |
| Exact uploaded icon comparison, SHA-256 | Uploaded PNG and repository asset | Pass — `9c92ac45de043a6567130d35898e21e73c8e36ac3ebe3aa4bd7a8d4fc05c48cf` |
| Full `composer test` | PHP 8.3.6 / Joomla 6.1.2 source runtime | Pass — 4,423 tests, 47,939 assertions |
| Working-tree added-line PHP contribution style | PHP 8.3.6 | Pass — all 16 changed library/test PHP files |
| Composer validation, platform requirements and locked dependency audit | Composer 2.10.2 | Pass |
| Installed GUI and full compiler comparisons | GitHub Actions | Pass — all seven checks on `50fb7f9`; [26 Playwright tests and actual repair persistence](https://github.com/extension-builder/joomla/actions/runs/37135419394), [Joomla 3/4/5/6 compiler comparisons](https://github.com/extension-builder/joomla/actions/runs/37135419351), [PHP 8.3/8.4](https://github.com/extension-builder/joomla/actions/runs/37135419293). Final-head check results are retained on PR #57 |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Compare uploaded and repository template support layout and button anchors | Source comparison | Pass — retained uploaded support placement and existing button anchors |

### GUI test coverage

- **Spec files added/updated:** `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`, owned by the regression-test agent in this change.

### Checks not performed

- Local Docker execution was unavailable. The hosted browser suite passed and verifies menu, Back behavior and icon loading; final-head coverage also asserts banner placement. Owner-side recompilation after copying into JCB remains a separate source reconciliation step.

## Risks, limitations, and rollback

- **Known risks:** Browser Back follows the user's history, as the supplied compiled template does.
- **Known limitations:** No live JCB definition is modified by this repository task.
- **Rollback:** Revert the alignment hunks in `admin/tmpl/extrusion/default.php` and the icon asset together with their GUI assertions and this record; retain the separate namespace-repair feature if desired.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/tmpl/extrusion/default.php` | Owner's JCB custom admin view `Extrusion`, default template; reference compiled ZIP `admin/tmpl/extrusion/default.php` | Yes | Source identified | Owner supplied compiled UI source; copy this repository template back into the Extrusion default template and recompile to verify natural-string generation |
| `admin/assets/images/icons/extrusion.png` | Owner's JCB custom admin view `Extrusion`, dashboard icon; reference compiled ZIP `admin/assets/images/icons/extrusion.png` | Yes | Source identified | Owner supplied the icon; retain the same custom admin view icon in JCB and verify its generated dashboard asset after recompilation |

## Final consistency check

- [x] The affected-path lists match the final diff exactly.
- [x] Every path has a stable location and exact what/why details.
- [x] Behavioral and visual impact are explicit.
- [x] Verification records actual results and identifies skipped checks.
- [x] Every path has an authoritative-source mapping and reconciliation status.
- [x] `Transfer required` is `No` only for an owner-confirmed `Not applicable` path; it is `Yes` for every other status.
- [x] The changed interface behavior has GUI test coverage, or the record says plainly why not.
- [x] The implementation remains within the cited permission.
