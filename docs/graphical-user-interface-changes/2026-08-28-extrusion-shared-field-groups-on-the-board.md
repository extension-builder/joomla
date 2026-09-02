# Extrusion pairing board: shared field groups

## Change identity

- **Date first changed:** 2026-08-28
- **Author/implementer:** Claude (coding agent), for lemuel@vdm.to
- **Task/issue/PR:** Branch `claude/joomla-extrusion-field-sharing` (PR #43),
  the field-identity work: one field per stated identity
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active session: the owner re-tested the sharing
  engine through the extrusion GUI and still received one identical field per
  admin view. A live reproduction proved the board itself manufactures a
  verdict for every untouched row, which detaches every shared member before
  the writers run, and that the results pane shows none of the sharing
  diagnostics. The owner directed: match fields by a hash of their
  properties, reuse the field wherever the hashes align, on both the fresh
  creation and the update of an existing component -- and gave the go-ahead
  to implement all the fixes. Standing permission to branch, commit in the
  owner's name, and open a pull request.
- **Authorized paths:** The extrusion view's own `admin/assets/js/extrusion.js`
  and `admin/tmpl/extrusion/default.php`, alongside the engine work in
  `libraries/vendor_jcb/**`.
- **Authorized outcome:** The board never turns its own proposals into
  verdicts, renders a shared group as one field with the views it serves,
  lets a person decide the group as one unit or detach one view, and shows
  the sharing, adoption and consolidation the import performed.
- **Permission summary:** Covers the extrusion board's decision payload,
  the shared-row rendering, and the results sections only. No other admin
  view, no compiler templates.

## Purpose and rationale

The sharing resolver settles, before anything is written, which harvested
columns are one field. The board defeated it: `buildDecisions()` shipped a
verdict for **every** row -- untouched update proposals as explicit
`update` verdicts and untouched name-lookalikes as explicit `create`
verdicts -- and the settle step reads any explicit verdict as "a person
named this field", detaching every member from its group. On a clean site
the manufactured `create` verdicts salted one fresh field per view; on a
site carrying older duplicates the manufactured `update` verdicts re-blessed
each view's own old copy. Both were proven in live reproduction. The board
also never rendered the `shared` marker the server already sent, and the
results pane dropped every sharing diagnostic, so a person could neither
see a group before the import nor confirm sharing after it. Only the
board's own files can fix what the board sends and shows.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-08-28-extrusion-shared-field-groups-on-the-board.md`

### Modified

- `admin/assets/js/extrusion.js`
- `admin/tmpl/extrusion/default.php`
- `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/assets/js/extrusion.js`

- **Change type:** Modified
- **Stable location(s):** `buildDecisions()`, `row()`, `rematch()`,
  `counts()`, `wireBoard()`, `renderResults()`
- **What changed:** `buildDecisions()` now ships **only rows the person
  explicitly decided** -- an untouched row sends nothing, so the engine's
  own settle and reuse defaults govern it. A decision on a group owner's
  row travels under the new `field_group` kind, which the settle step
  applies to the whole group; a decision on a detached member stays a
  per-view `field` verdict. `rematch()` leaves shared members unpaired
  instead of re-matching them client-side, and labels a name answering
  inside the component's own linked views as `scoped` (`scopedLabel()`),
  so the board proposes the very update the server-side reuse defaults
  will perform. `row()` renders a shared member as a non-actionable row --
  its label, a badge naming the owning column, and a single Detach action
  -- and renders the owner's row with a badge counting the views that link
  its one field. Detaching records an explicit create decision at once, so
  what the detached row shows is what the import will do; its reset
  restores the row to its group and drops it from the bulk selection.
  `bulk()` skips shared members still standing in their group. `counts()`
  adds the shared tally to each section summary. `renderResults()` renders
  the new report sections -- shared, adopted, consolidated, reused, kept
  -- beside written, skipped and failed.
- **Why:** The manufactured verdicts were the proven cause of the per-view
  duplicate fields, and the board must show a shared group as what the
  import will actually write: one field, linked by every view that states
  it.
- **Related paths/symbols:**
  `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Sharing.php`
  (consumes `field_group` verdicts, writes the `shared` markers),
  `Resolver/Candidates.php` (sends `shared` and `shared_by` on field
  candidates), `Resolver/Reuse.php` (server-side defaults for untouched
  rows), `admin/src/Model/AjaxModel.php` (unchanged transport).

### `admin/tmpl/extrusion/default.php`

- **Change type:** Modified
- **Stable location(s):** the `window.JCBExtrusion.text` bootstrap map
- **What changed:** Added the natural-language strings the new rendering
  reads: shared/sharedWith/detach/detachHint/detached/oneField/views and
  the results section labels (Shared, Adopted, Consolidated, Reused, Kept).
  All strings are natural strings inside `Text::_()` per this view's
  language rule; none were added to language files by hand.
- **Why:** The JavaScript takes every user-facing string from this map.
- **Related paths/symbols:** `admin/assets/js/extrusion.js`.

### `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`

- **Change type:** Modified
- **Stable location(s):** the `harvests the installed component against the
  real schema` spec
- **What changed:** The spec now also asserts the shared-group rendering on
  the real harvest: at least one shared member row stands in the admin view
  fields, it offers Detach instead of the three decisions, detaching turns
  it actionable, and reset returns it to its group.
- **Why:** The board's new behavior must be covered by the GUI suite that
  drives the real page, per the GUI testing governance.
- **Related paths/symbols:** `admin/assets/js/extrusion.js` `row()`.

## Impact

- **Behavioral impact:** An untouched board now imports under the engine's
  own defaults: shared groups keep one field and matched candidates update
  what stands. Previously every untouched row travelled as an explicit
  verdict, which created or re-blessed one field per view. A person's
  explicit choices travel exactly as before.
- **Visual impact:** Shared member rows render as informational rows with a
  badge and a Detach action; owner rows carry a views-count badge; section
  summaries add a shared tally; the results pane gains collapsible Shared,
  Adopted, Consolidated, Reused and Kept sections.
- **Accessibility impact:** All new controls are native `button` elements
  with visible text labels and `title` hints, matching the existing rows;
  no pointer-only interaction was added.
- **Compatibility impact:** None beyond the page itself: the AJAX contract
  is unchanged (the decisions payload is a subset of what was sent before,
  plus the `field_group` kind the server now reads), and the JavaScript
  ships with the same page it serves.
- **Generated-output impact:** None directly -- this page writes JCB
  records through the same import pipeline; the records themselves are the
  engine change, recorded in the PR alongside this file.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php vendor/bin/phpunit --testsuite=VDM.Joomla --filter Extrusion` | PHP 8.4.19, tests container | Pass — 360 tests, 7508 assertions |
| Adversarial review (four lenses over the full diff, every finding re-verified against the code) | Same container | 19 confirmed findings fixed and re-tested — group adoption, decision-key seams, verdict-carrying members, shrunken-group verdicts, malformed stated guids, merge placement preservation, detach semantics |
| `php vendor/bin/phpunit --testsuite=VDM.Joomla` | PHP 8.4.19 | No new failures — the 59 failing tests are byte-identical on a clean HEAD worktree in this container (date/crypt environment artifacts), verified by diffing the two failure lists |
| `php bin/check-php-style.php` | tests container | Pass — 495 files |
| `php bin/check-test-ownership.php` | tests container | Pass — 1455 entries |
| `php bin/check-container-keys.php` | tests container | Pass — 911 keys |
| `node --check admin/assets/js/extrusion.js` | Node 20 | Pass |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Fresh import of a two-view component whose views state identical name/guid/state fields, decisions untouched | Local Joomla 6 + JCB lab, MySQL, CLI mirror of the AJAX flow | Pass — one field per identity, both views link it, `counts.fields_shared` reported; previously three per-view duplicates were salted |
| Re-import over a database already carrying per-view duplicates from the pre-sharing engine | Same lab, duplicates seeded by the era engine | Pass — the standing record is adopted, the second view's old link is consolidated onto it, no new field records; the orphaned duplicates are named in the report |
| Harvest of an installed JCB-built component whose table definition class lives under the site's `libraries/` | Same lab, a JCB-built component installed on the site | Pass — the class is located centrally, its stated guids group every view's guid column into one field |

### GUI test coverage

- **Spec files added/updated:** `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`
  (shared-group rendering asserted inside the real-component harvest spec)

### Checks not performed

- The Playwright GUI suite was not executed locally — this environment does
  not serve the administrator over HTTP; the suite runs in CI on the branch,
  as for every prior extrusion board change.

## Risks, limitations, and rollback

- **Known risks:** A person who relied on the old board silently forcing
  `create` on every name-lookalike now gets the engine default (a fresh
  derived identity) instead of a salted one; identities of *new* fields are
  therefore stable across re-runs, which is the intended behavior.
- **Known limitations:** At harvest time with auto-detect, the group badges
  show the pre-adoption identity; the import settles against the finally
  selected component, so the written identity can differ from the badge
  when the person switches targets after harvesting.
- **Rollback:** Revert the three modified files and delete this record;
  the engine keeps working (the board then merely re-manufactures verdicts,
  restoring the duplicate behavior this change removes).

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/assets/js/extrusion.js` | JCB component definition (extrusion view asset), maintained in this repository as the source of truth for the extrusion view | Yes | Reconciliation in progress | Same ledger row as 2026-08-23/24 records: the extrusion view is new JCB-managed GUI; owner imports it into JCB with the component sources when the feature lands |
| `admin/tmpl/extrusion/default.php` | JCB component definition (extrusion view template), same ledger as above | Yes | Reconciliation in progress | As above — travels with the extrusion view into JCB |

## Final consistency check

The affected paths, implementation details, impact and verification above
were re-read against the final diff of this branch before the record was
committed; every path named is in the diff and no diff path touching
`admin/**` is missing here.
