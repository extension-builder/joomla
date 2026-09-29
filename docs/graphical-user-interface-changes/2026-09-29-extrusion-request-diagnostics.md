# Safe extrusion request diagnostics and bounded Power selection

## Change identity

- **Date first changed:** 2026-09-29
- **Author/implementer:** Codex
- **Task/issue/PR:** https://github.com/extension-builder/joomla/pull/54 — W04/W07
- **Change record status:** Draft

## Explicit permission

- **Permission reference:** PR #54 records the implementation permission from
  handover version 2 section 20; the current task explicitly takes over and
  completes that same PR and all its objectives.
- **Authorized paths:** `admin/src/Model/AjaxModel.php`,
  `admin/assets/js/extrusion.js`, `admin/tmpl/extrusion/default.php`.
- **Authorized outcome:** Safe actual request diagnostics and recoverable
  extrusion progress, bounded Power selection without an automatic global
  catalogue scan, with browser coverage and preserved approval rules.
- **Permission summary:** Complete the authorized extrusion seams only.

## Purpose and rationale

The page reported every failed HTTP response as a network failure and exposed
raw parsing and server exception messages. A failed import also left its old
approval available. These behaviors occur at the administrator request boundary,
so library-only changes cannot correct them.

The pairing catalogue also loaded all Powers automatically. Its default now
contains the selected component's dependencies; the picker requests an exact
name, system name, namespace or GUID only when the person searches outside that
set. This preserves manual target selection without a cold global scan.

## Affected paths

### Created

- None

### Modified

- `admin/assets/js/extrusion.js`
- `admin/tmpl/extrusion/default.php`
- `admin/src/Model/AjaxModel.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/assets/js/extrusion.js`

- **Change type:** Modified
- **Stable location(s):** `post`, `harvest`, `runImport`, `loadCatalogue`,
  `reviewReady`, progress control helpers, folder response handling,
  `modalPool`, `searchModal`, `openModal`, `closeModal`, `planBlockers`.
- **What changed:** Classify rejected fetches, failed HTTP statuses, malformed
  JSON, and operation errors separately; render safe status/reference details;
  restore controls after completion and invalidate approvals after import;
  block overlapping harvest/import and importing without a catalogue; look up
  exact manual Power targets only on explicit search, ignoring stale responses;
  display blocker explanations instead of converting blocker records to text.
- **Why:** A person must see the real failure category, regain the controls,
  and explicitly review before another possible write.
- **Related paths/symbols:** Template text map, extrusion AJAX methods.

### `admin/tmpl/extrusion/default.php`

- **Change type:** Modified
- **Stable location(s):** `window.JCBExtrusion.text`, `extrusion-modal-search`,
  `extrusion-power-search-hint`.
- **What changed:** Natural translated strings for network, HTTP, invalid JSON,
  operation failure, and safe correlation references; an accessible description
  explaining exact lookup outside the initially linked Power set.
- **Why:** Accurate diagnostics follow JCB's existing translation workflow.
- **Related paths/symbols:** `post` in the extrusion script.

### `admin/src/Model/AjaxModel.php`

- **Change type:** Modified
- **Stable location(s):** `extrusionHarvest`, `extrusionImport`,
  `extrusionCatalogue`, `extrusionWeigh`, `extrusionDiff`, `extrusionReview`,
  `extrusionFailure`, `extrusionPublicReport`, `extrusionFailureReferences`.
- **What changed:** Catch `Throwable` at the operation boundary and return a
  safe failure reference; log operation phase, last completed phase, bounded
  counters and exception class without sending raw exceptions to the browser;
  sanitize previously caught engine preparation/commit/rollback exceptions in
  report copies while preserving business blockers and private engine evidence;
  reuse one reference across response sections; forward the optional explicit
  `power_search` input to the bounded catalogue.
- **Why:** Unexpected PHP errors require the same safe reporting as exceptions.
- **Related paths/symbols:** Existing extrusion services and Joomla logging.

## Impact

- **Behavioral impact:** Request failures recover the page without automatic
  retries; another import requires a fresh review. Global Power choices require
  an explicit exact-value lookup; local linked choices remain searchable.
- **Visual impact:** Notices distinguish failure categories and HTTP status;
  the Power picker explains how to find another definition.
- **Accessibility impact:** Existing text notices and disabled controls remain;
  running state blocks duplicate actions through the same controls.
- **Compatibility impact:** Existing fetch-capable browsers and supported Joomla
  hosts; no API parameter or compiler target changes.
- **Generated-output impact:** None; this changes administrator error handling.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `node --check admin/assets/js/extrusion.js` and `node --check libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js` | Local Node.js | Pass |
| `php -l admin/src/Model/AjaxModel.php` and `php -l admin/tmpl/extrusion/default.php` | Local PHP | Pass |
| `git diff --check` for the changed GUI paths | Current checkout | Pass |
| `vendor/bin/phpunit Contract/ExtrusionAjaxDiagnosticsTest.php --colors=never` | Local PHP 8.3.6, PHPUnit 12.5.33 | Pass — 5 tests, 52 assertions; preparation, commit, rollback, business blockers, PHP Errors, correlation logs and private evidence preservation |
| Disposable installed GUI harness | GitHub Actions | Pending |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| None | Not applicable | Browser scenarios are automated below. |

### GUI test coverage

- **Spec files added/updated:**
  `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`.
- Failure cases use real server responses and the browser's offline transport;
  no extrusion AJAX response is mocked and imports remain dry runs.
- The picker asserts the initial catalogue excludes A when B is selected,
  then resolves A by an explicit GUID lookup without changing the pairing.

### Checks not performed

- Installed GUI harness execution is pending final CI evidence.

## Risks, limitations, and rollback

- **Known risks:** An interrupted import may have reached the server; the page
  never retries it automatically and discards its approval.
- **Known limitations:** Process termination and hard memory/time limits cannot
  reliably be caught; HTTP/invalid-response reporting covers their responses.
- **Rollback:** Revert these three protected-path changes with this record and
  the corresponding GUI assertions together.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Model/AjaxModel.php` | JCB component Ajax model custom methods | Yes | Source identified | Maintainer must transfer extrusion methods and helper into its authoritative definition. |
| `admin/assets/js/extrusion.js` | Extrusion custom administrator view JavaScript | Yes | Source identified | Maintainer must import the revised script into that view. |
| `admin/tmpl/extrusion/default.php` | Extrusion custom administrator view default template | Yes | Source identified | Maintainer must transfer the text map into that template. |

## Final consistency check

- [x] The affected-path lists match the protected diff.
- [x] Every path has stable locations and what/why details.
- [x] Behavioral and visual impact are explicit.
- [ ] Verification records final results and skipped checks.
- [x] Every path has an authoritative-source mapping and reconciliation status.
- [x] Transfer remains required until the authoritative definitions are updated.
- [x] Changed behavior has corresponding GUI coverage.
- [x] Implementation remains within the cited permission.
