# See what an extrusion would change, on the pairing board, before importing

## Change identity

- **Date first changed:** 2026-09-03
- **Author/implementer:** lemuelvdm
- **Task/issue/PR:** Branch `claude/joomla-extrusion-preview-diff`
- **Change record status:** Ready for review (revised after review)

## Explicit permission

- **Permission reference:** The owner's request in the active session: "on
  each view, we can... see the diff. On each field... on each power... Then
  when we have successfully harvested something, we then automatically lands
  on the pairing page. That is exactly when I want to see the [diff] as well
  of what would have been changed. Before we apply the changes", followed by
  "you can start open one PR, make three commits... start with the first the
  the engine... and then the GUI changes. You can go ahead."

  Revision: the owner's follow-up in the review session of this pull
  request: "permission was given that you can push a commit to that branch
  with all your proposed fixes", covering the same five paths.
- **Authorized paths:** `admin/assets/js/extrusion.js`,
  `admin/assets/css/extrusion.css`, `admin/tmpl/extrusion/default.php`,
  `admin/src/Model/AjaxModel.php`, `admin/src/Controller/AjaxController.php`
- **Authorized outcome:** The pairing step shows, per row, how many lines an
  import would add and remove, opens that change read-only side by side on
  demand, and marks a row with nothing to change as "no change". After a
  decision the rows it moved are weighed again, so every badge answers for
  the pairing the board has now.
- **Permission summary:** The owner asked for the change to be visible in the
  pairing step before any import, loaded one row at a time rather than all at
  once, read-only, with no server-side store of the diffs. The owner also
  asked that a row with nothing to change be set to ignore; that part is
  implemented as the "no change" badge alone, and the reason is recorded
  under Known limitations.

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
  `AjaxModel::extrusionWeigh()`, `AjaxModel::extrusionDiff()`,
  `AjaxModel::extrusionProposals()`, `AjaxModel::extrusionRecords()`
- **What changed:** The harvest response gained a `changes` key holding what
  every board row would change, taken after the harvest's own report and
  messages are captured so the account of the harvest is unchanged. The
  weighing is a run of its own, from the source up: resolving a second time
  over a harvest that has already resolved settles the shared fields
  differently, and the board would answer for a run the import never makes.
  It is aimed at the component the board pairs against -- the one detected
  from the source, or the one chosen -- exactly as the import is, and a
  weighing that fails leaves the harvest standing with an empty `changes`
  and a `weighing` message rather than failing the harvest. A new
  `extrusionWeigh` endpoint answers the whole board again under the
  decisions as they stand, and a new `extrusionDiff` endpoint reads the
  source and composes it again under the same verdicts and answers one row:
  its changed records, their changed columns, and the hunks of each. All
  three share one run (`extrusionProposals()`) with writing suppressed.
- **Why:** The board needs the counts when it appears and after every
  decision, and the lines only when a person asks for them; nothing is
  stored between the calls, so a diff is always read against the records as
  they stand at that second.
- **Related paths/symbols:** `VDM\Joomla\Componentbuilder\Extrusion\Registry\Proposal`,
  `VDM\Joomla\Componentbuilder\Extrusion\Resolver\Diff`

### `admin/src/Controller/AjaxController.php`

- **Change type:** Modified
- **Stable location(s):** `AjaxController::__construct()` task registration;
  the `extrusionWeigh` and `extrusionDiff` cases in the `ajax` dispatch
- **What changed:** Registered and dispatched the `extrusionWeigh` task,
  reading `config` and `decisions`, and the `extrusionDiff` task, reading
  `config`, `decisions` and `row`, in the same shape as the extrusion tasks
  beside them.
- **Why:** The gateway is how the board reaches the model.
- **Related paths/symbols:** `AjaxModel::extrusionDiff()`

### `admin/assets/js/extrusion.js`

- **Change type:** Modified
- **Stable location(s):** `state`, `harvest()`, `runConfig()`,
  `weight()`, `unweigh()`, `scheduleWeighing()`, `reweigh()`, `moved()`,
  `changeBadge()`, `toggleDiff()`, `fetchDiff()`, `closeDiff()`,
  `renderDiff()`, `diffRows()`, `diffRow()`, `row()`, `renderBoard()`,
  `decide()`, `runImport()`, `kindSection()`, the board click handler and
  the component-select handler
- **What changed:** The board keeps the weights, puts a `+N −M` badge on
  every row that would change and a "no change" badge on every row that
  would not. Clicking a badge fetches that one row's diff, renders it
  read-only side by side under the row, and clicking again drops it from
  memory. A decision marks its own row and the rows tied to it (a view's
  fields, a field's view) as being weighed, and the whole board is weighed
  again under the decisions as they stand, once per burst of decisions;
  only the latest answer lands, and an open diff whose weight moved is read
  again. Choosing another component weighs every row again. The weighing,
  the diff and the import all run under one configuration
  (`runConfig()`: the setup as harvested, aimed at the component selected
  on the board) and one set of verdicts (`buildDecisions()`), so what the
  board shows is the run the import makes. Disclosures remember whether
  they are open, so a re-draw leaves the board where a person was reading
  it.
- **Why:** This is the interface the owner asked for: the change visible at the
  pairing step, one row at a time, without editing.
- **Related paths/symbols:** `AjaxModel::extrusionDiff()`, the `changes` key of
  the harvest response

### `admin/assets/css/extrusion.css`

- **Change type:** Modified
- **Stable location(s):** `.extrusion-row`; new rules from
  `.extrusion-change` through `.extrusion-diff-gap`
- **What changed:** Styled the badge, the "no change" and weighing states, and
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
  would change, and after every decision the rows it moved are weighed again.
  A row with nothing to change says so; the engine writes nothing for it
  either way. A badge opens one row's diff on demand; closing it discards it.
  The import itself now leaves a record alone when the write would change
  nothing a person could read (the same text with other line endings, a
  subform saved in another key order or with numbers posted as text), and
  the number it announces is the number of records it wrote.
- **Visual impact:** A badge at the right of every row, before the decision
  buttons, and an inline read-only diff panel under a row while it is open.
- **Accessibility impact:** The badge is a real `button` for a row that would
  change, carrying `aria-expanded` for the panel it opens, and a plain
  `span` where there is nothing to open, both carrying a `title` that says
  what they mean; the panel is text in a table and holds no control. Colour is not the only signal -- the `+` and `−` counts and the
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
| `php vendor/bin/phpunit --testsuite=VDM.Joomla --filter Extrusion` | PHP 8.4.19, PHPUnit 12.5.33 | Pass — 414 tests, 7920 assertions |
| `php vendor/bin/phpunit --configuration phpunit.xml.dist --exclude-group known-defect` | PHP 8.4.19, PHPUnit 12.5.33 | Pass — the complete suite |
| `php bin/check-php-style.php --base=<merge-base>` | PHP 8.4.19 | Pass — 33 files checked |
| `php bin/check-container-keys.php` | PHP 8.4.19 | Pass — 933 keys registered |
| `php bin/check-test-ownership.php --base=<merge-base>` | PHP 8.4.19 | Pass — 1478 production files, all owned |
| `node --check admin/assets/js/extrusion.js` | Node 22.22.2 | Pass |
| `php -l` on each changed PHP file | PHP 8.4.19 | Pass |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Harvest the Demo component in update mode against its own compiled source, through `AjaxModel::extrusionHarvest()`, then open one row through `AjaxModel::extrusionDiff()` | Joomla 6 + JCB, MariaDB, PHP 8.4.19 | Pass — 84 rows weighed, 23 changed, harvest 0.50s, one row's diff 0.30s and 603 bytes; the harvest report carries no weighing entries |
| Open every changed row's diff and compare its totals with the badge the harvest gave that row | Joomla 6 + JCB, MariaDB | Pass — 23 of 23 rows match exactly, and none opens to an empty diff |
| Compare the board's rows with the rows the weighing accounts for | Joomla 6 + JCB, MariaDB | Pass — every row is accounted for except the shared members, whose field is written and weighed on the row of the view that owns it, and `address.state` (see Known limitations) |
| Weigh a proposed empty list against a standing power that never set those columns | Joomla 6 + JCB, MariaDB | Pass — no change reported, where before it reported three columns each gaining `[]` |
| Drive the real `extrusion.js` and `extrusion.css` in Chromium against a stubbed gateway: harvest, read the badges, open a diff, close it | Chromium 141, Playwright | Pass — `+4 −1` on the view, `+1 −0` on a changed field, `no change` on an unchanged field with Ignore set and resettable, `+2 −2` on a power; the diff opened side by side with the added line marked, and closing it left no panel on the page |
| Import the same source twice and compare what was written | Joomla 6 + JCB, MariaDB | Pass — the first run wrote 24 records and left 83 unchanged; the identical second run wrote nothing |

### GUI test coverage

- **Spec files added/updated:**
  `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js` — "says what every
  row would change, and shows it line by line": the badges stand on the board
  after a harvest, nothing of the diff is on the page until asked for, one
  opens side by side with its additions, holds no editable control, closing
  it removes it, ignoring a row takes its weight off the board through a
  real weighing round trip, and putting the row back weighs it again.

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
  the row of the view that owns it, not on every view that links it; the
  member rows already say who owns the field. A record two rows both compose
  is now weighed on both rows. Rows the board does not show -- the component
  record and its link tables -- are weighed and named under a `component`
  row that no board row displays yet. The powers vendor writer's three
  records (the global component-namespace placeholder, the component's
  namespace prefix and its placeholder overrides) are written outside the
  weighing boundary and carry no weight on the board. Weighing the board
  again after a decision costs one dry run of the engine per burst of
  decisions; on a very large source that run takes a few seconds, during
  which the moved rows read "weighing...".

  A row with nothing to change is **not** set to an ignore verdict, though
  the owner asked for that. Ignoring a row takes it out of the run
  altogether, and measurement showed that doing so cancels work the import
  should still do: on the Demo component, ignoring the unchanged fields of
  the `file_type` view removed an entire `admin_fields_conditions` record
  (+44 lines) that the import would otherwise write. The engine already
  writes nothing for a record that would change nothing, so the badge alone
  delivers what the ignore was for, without that cost.
- **Rollback:** Revert the commits of this branch. Reverting only the GUI
  commits leaves the endpoints in place and unused, which is harmless.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Model/AjaxModel.php` | JCB custom admin view `extrusion` — its AJAX methods in the JCB maintenance system | Yes | Reconciliation in progress | The extrusion AJAX methods beside it were transferred the same way; the owner transfers `extrusionWeigh`, `extrusionDiff`, `extrusionProposals`, `extrusionRecords` and the `changes` and `weighing` keys of `extrusionHarvest` into the JCB definition of this view. |
| `admin/src/Controller/AjaxController.php` | JCB `ajax_input` declarations of the `extrusion` view | Yes | Reconciliation in progress | The `extrusionWeigh` task needs its `config` and `decisions` inputs, and the `extrusionDiff` task its `config`, `decisions` and `row` inputs, declared in JCB beside the existing extrusion tasks. |
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
