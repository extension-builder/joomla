# See what an extrusion would change, on the pairing board, before importing

## Change identity

- **Date first changed:** 2026-09-03
- **Author/implementer:** lemuelvdm
- **Task/issue/PR:** Branch `claude/joomla-extrusion-preview-diff`
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** The owner's request in the active session: "on
  each view, we can... see the diff. On each field... on each power... Then
  when we have successfully harvested something, we then automatically lands
  on the pairing page. That is exactly when I want to see the [diff] as well
  of what would have been changed. Before we apply the changes", followed by
  "you can start open one PR, make three commits... start with the first the
  the engine... and then the GUI changes. You can go ahead."
- **Authorized paths:** `admin/assets/js/extrusion.js`,
  `admin/assets/css/extrusion.css`, `admin/tmpl/extrusion/default.php`,
  `admin/src/Model/AjaxModel.php`, `admin/src/Controller/AjaxController.php`
- **Authorized outcome:** The pairing step shows, per row, how many lines an
  import would add and remove, opens that change read-only side by side on
  demand, and marks a row with nothing to change as "no change" and sets it
  to ignore.
- **Permission summary:** The owner asked for the change to be visible in the
  pairing step before any import, loaded one row at a time rather than all at
  once, read-only, with no server-side store of the diffs.

## Purpose and rationale

Before this, an import was taken on trust: the pairing step said which record
each candidate would land on, never what would change inside it, so the only
way to learn was to import and read the compiled output afterwards.

The weighing itself is engine work and lives in
`libraries/vendor_jcb/**` (`Extrusion\Resolver\Delta`, `Extrusion\Resolver\Diff`,
`Extrusion\Registry\Proposal`, and the write seam in
`Extrusion\Abstraction\Writer`). A protected-path change is still required
because the answer has to reach a person: the pairing board is the interface
that asks for it, renders the badge, and opens the diff, and the AJAX gateway
is what carries it. No change confined to the library could put a badge on a
board row.

## Affected paths

### Created

- None

### Modified

- `admin/src/Model/AjaxModel.php`
- `admin/src/Controller/AjaxController.php`
- `admin/assets/js/extrusion.js`
- `admin/assets/css/extrusion.css`
- `admin/tmpl/extrusion/default.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/src/Model/AjaxModel.php`

- **Change type:** Modified
- **Stable location(s):** `AjaxModel::extrusionHarvest()`,
  `AjaxModel::extrusionDiff()`, `AjaxModel::extrusionWeigh()`,
  `AjaxModel::extrusionRecords()`
- **What changed:** The harvest response gained a `changes` key holding what
  every board row would change, taken after the harvest's own report and
  messages are captured so the account of the harvest is unchanged. A new
  `extrusionDiff` endpoint reads the source and composes it again under the
  supplied verdicts and answers one row: its changed records, their changed
  columns, and the hunks of each. Both weigh with writing suppressed.
- **Why:** The board needs the counts when it appears and the lines only when
  a person asks for them; nothing is stored between the two calls, so a diff
  is always read against the records as they stand at that second.
- **Related paths/symbols:** `VDM\Joomla\Componentbuilder\Extrusion\Registry\Proposal`,
  `VDM\Joomla\Componentbuilder\Extrusion\Resolver\Diff`

### `admin/src/Controller/AjaxController.php`

- **Change type:** Modified
- **Stable location(s):** `AjaxController::__construct()` task registration;
  the `extrusionDiff` case in the `ajax` dispatch
- **What changed:** Registered and dispatched the `extrusionDiff` task,
  reading `config`, `decisions` and `row`, in the same shape as the
  extrusion tasks beside it.
- **Why:** The gateway is how the board reaches the model.
- **Related paths/symbols:** `AjaxModel::extrusionDiff()`

### `admin/assets/js/extrusion.js`

- **Change type:** Modified
- **Stable location(s):** `state`, `harvest()`, `standDownUnchanged()`,
  `weight()`, `changeBadge()`, `toggleDiff()`, `closeDiff()`, `renderDiff()`,
  `diffRows()`, `diffRow()`, `row()`, `renderBoard()`, `decide()`,
  `kindSection()`, the board click handler and the component-select handler
- **What changed:** The board keeps the harvest's weights, puts a `+N −M`
  badge on every row that would change and a "no change" badge on every row
  that would not, and sets the unchanged rows to ignore. Clicking a badge
  fetches that one row's diff, renders it read-only side by side under the
  row, and clicking again drops it from memory. A decision marks its own row's
  badge stale rather than showing a number read under a pairing that has since
  moved; choosing another component marks them all. Disclosures now remember
  whether they are open, so a re-draw leaves the board where a person was
  reading it.
- **Why:** This is the interface the owner asked for: the change visible at the
  pairing step, one row at a time, without editing.
- **Related paths/symbols:** `AjaxModel::extrusionDiff()`, the `changes` key of
  the harvest response

### `admin/assets/css/extrusion.css`

- **Change type:** Modified
- **Stable location(s):** `.extrusion-row`; new rules from
  `.extrusion-change` through `.extrusion-diff-gap`
- **What changed:** Styled the badge, the "no change" and stale states, and
  the two-column diff with its line numbers, added and removed lines. The row
  wraps so the diff opens beneath it at full width.
- **Why:** A diff has to read like a diff: additions green, deletions red,
  the two sides side by side.
- **Related paths/symbols:** the markup `renderDiff()` writes

### `admin/tmpl/extrusion/default.php`

- **Change type:** Modified
- **Stable location(s):** the `window.JCBExtrusion.text` bootstrap
- **What changed:** Added the nine strings the badge and the diff panel show.
- **Why:** The page's strings are declared here, never in the script.
- **Related paths/symbols:** `admin/assets/js/extrusion.js`

## Impact

- **Behavioral impact:** After a harvest the pairing board shows what every row
  would change. A row with nothing to change is set to ignore, so the import
  skips it. A badge opens one row's diff on demand; closing it discards it.
  Nothing about the import itself changed.
- **Visual impact:** A badge at the right of every row, before the decision
  buttons, and an inline read-only diff panel under a row while it is open.
- **Accessibility impact:** The badge is a real `button` for a row that would
  change and a plain `span` where there is nothing to open, both carrying a
  `title` that says what they mean; the panel is text in a table and holds no
  control. Colour is not the only signal -- the `+` and `−` counts and the
  line numbers carry the same information.
- **Compatibility impact:** None beyond the extrusion view: Joomla 5 and 6
  administrator, the same browsers the rest of the board supports. The new
  AJAX task is additive.
- **Generated-output impact:** None. The preview writes nothing; what an
  import writes is unchanged except that a record with nothing to change is
  no longer written at all.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php vendor/bin/phpunit --testsuite=VDM.Joomla --filter Extrusion` | PHP 8.4.19, PHPUnit 12.5.33 | Pass — 405 tests, 7874 assertions |
| `php bin/check-php-style.php --base=<merge-base>` | PHP 8.4.19 | Pass — 38 files checked |
| `php bin/check-container-keys.php` | PHP 8.4.19 | Pass — 933 keys registered |
| `php bin/check-test-ownership.php --base=<merge-base>` | PHP 8.4.19 | Pass — 1478 production files, all owned |
| `node --check admin/assets/js/extrusion.js` | Node 22.22.2 | Pass |
| `php -l` on each changed PHP file | PHP 8.4.19 | Pass |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Harvest the Demo component in update mode against its own compiled source, through `AjaxModel::extrusionHarvest()`, then open one row through `AjaxModel::extrusionDiff()` | Joomla 6 + JCB, MariaDB, PHP 8.4.19 | Pass — 84 rows weighed, 25 changed, harvest 0.50s, one row's diff 0.30s and 603 bytes; the harvest report carries no weighing entries |
| Drive the real `extrusion.js` and `extrusion.css` in Chromium against a stubbed gateway: harvest, read the badges, open a diff, close it | Chromium 141, Playwright | Pass — `+4 −1` on the view, `+1 −0` on a changed field, `no change` on an unchanged field with Ignore set and resettable, `+2 −2` on a power; the diff opened side by side with the added line marked, and closing it left no panel on the page |
| Import the same source twice and compare what was written | Joomla 6 + JCB, MariaDB | Pass — the first run wrote 24 records and left 83 unchanged; the identical second run wrote nothing |

### GUI test coverage

- **Spec files added/updated:**
  `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js` — "says what every
  row would change, and shows it line by line": the badges stand on the board
  after a harvest, nothing of the diff is on the page until asked for, one
  opens side by side with its additions, holds no editable control, and
  closing it removes it.

### Checks not performed

- The Playwright suite was not run in this environment: it needs the
  container the harness stands up (`.github/gui-tests/run.sh`), and the local
  Joomla will not serve under PHP's built-in server. The new spec was written
  against the same selectors the browser run above exercised, and CI runs it.

## Risks, limitations, and rollback

- **Known risks:** A diff is read by running the pipeline again, so a source
  folder that has changed since the harvest will answer for the source as it
  is now, not as it was harvested. That is the honest answer, and the same one
  the import would act on.
- **Known limitations:** A field shared by several views shows its change on
  the row of the view that owns it, not on every view that links it. Rows the
  board does not show -- the component record and its link tables -- are
  weighed and named under a `component` row that no board row displays yet.
- **Rollback:** Revert the three commits of this branch. Reverting only the
  GUI commit leaves the endpoints in place and unused, which is harmless.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Model/AjaxModel.php` | JCB custom admin view `extrusion` — its AJAX methods in the JCB maintenance system | Yes | Reconciliation in progress | The extrusion AJAX methods beside it were transferred the same way; the owner transfers `extrusionDiff`, `extrusionWeigh`, `extrusionRecords` and the `changes` key of `extrusionHarvest` into the JCB definition of this view. |
| `admin/src/Controller/AjaxController.php` | JCB `ajax_input` declarations of the `extrusion` view | Yes | Reconciliation in progress | The `extrusionDiff` task needs its `config`, `decisions` and `row` inputs declared in JCB beside the existing extrusion tasks. |
| `admin/assets/js/extrusion.js` | JCB custom admin view `extrusion`, JavaScript file | Yes | Reconciliation in progress | Whole-file transfer, as with the earlier extrusion board changes. |
| `admin/assets/css/extrusion.css` | JCB custom admin view `extrusion`, CSS file | Yes | Reconciliation in progress | Whole-file transfer. |
| `admin/tmpl/extrusion/default.php` | JCB custom admin view `extrusion`, default template | Yes | Reconciliation in progress | The nine added strings belong in the template held in JCB. |

## Final consistency check

- [x] The affected-path lists match the final diff exactly.
- [x] Every path has a stable location and exact what/why details.
- [x] Behavioral and visual impact are explicit.
- [x] Verification records actual results and identifies skipped checks.
- [x] Every path has an authoritative-source mapping and reconciliation status.
- [x] `Transfer required` is `No` only for an owner-confirmed `Not applicable`
  path; it is `Yes` for every other status.
- [x] The changed interface behavior has GUI test coverage, or the record
  says plainly why not.
- [x] The implementation remains within the cited permission.
