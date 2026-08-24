# Extrusion setup rework

## Change identity

- **Date first changed:** 2026-08-24
- **Author/implementer:** Claude (coding agent), for lemuel@vdm.to
- **Task/issue/PR:** Branch `claude/joomla-library-extrusion-518uv2`, follow-up
  to PR #35 (the extrusion admin view)
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active session review of the merged view: fold the
  three source-path fields into an Admin folder and a Site folder (no
  "source" wording, no combined path), select folders by walking the site
  from its root instead of typing, drop the SQL dump field (the install SQL
  is discovered inside the admin folder), fix the "Unknown column
  a.name_single" failure, and remove the social feed while keeping the
  banners. Permission to branch, commit in the owner's name, and open a pull
  request was confirmed in the same session.
- **Authorized paths:** The extrusion view's own `admin/**` files and the
  extrusion additions in the shared AJAX controller/model.
- **Authorized outcome:** A simpler setup pane — two selected component
  folders plus the library folders, everything else discovered — that
  harvests without error against a live database.
- **Permission summary:** Covers reworking the extrusion setup surface and
  its AJAX seams only. No other admin view, no `media/**`, no compiler
  templates.

## Purpose and rationale

Testing the merged view on a live site surfaced four faults. Three source
fields confused what should be two selections; paths had to be typed; the
SQL dump field duplicated what the engine already discovers inside the admin
folder (`Discovery\Locator\Schema` maps and scans `sql/` below every source
root); and the catalogue queried `site_view.name_single`, a column the real
schema does not hold, so a component harvest failed before the pairing board
ever showed. The social feed also rendered without the script that drives it.
The engine-side fault and its regression guard are in-scope library work; this
record covers the interface half.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-08-24-extrusion-setup-rework.md`

### Modified

- `admin/assets/css/extrusion.css`
- `admin/assets/js/extrusion.js`
- `admin/src/Controller/AjaxController.php`
- `admin/src/Model/AjaxModel.php`
- `admin/src/View/Extrusion/HtmlView.php`
- `admin/tmpl/extrusion/default.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/src/View/Extrusion/HtmlView.php`

- **Change type:** Modified
- **Stable location(s):** `getDynamicForm()`, the `source` fieldset
- **What changed:** The combined component-source field and the SQL dump
  textarea are gone. The fieldset now holds exactly three inputs: **Admin
  folder** and **Site folder** (renamed from "Admin source folder"/"Site
  source folder", with descriptions saying they are selected from the site
  root and that the install SQL inside the admin folder is discovered on its
  own) and the library-folders textarea.
- **Why:** Two selections instead of three typed paths; the engine discovers
  the source layout and the SQL itself.
- **Related paths/symbols:** `Extruder::adminPath()/sitePath()`,
  `Discovery\Locator\Schema` (existing engine behavior, unchanged).

### `admin/tmpl/extrusion/default.php`

- **Change type:** Modified
- **Stable location(s):** intro paragraph; the right columns of the setup and
  running panes; the modal region; the `window.JCBExtrusion.text` map
- **What changed:** Both `LayoutHelper::render('jcbnoticeboard', …)` calls
  are gone — the Mastodon feed's script (a compiler asset) never loaded on
  this page — replaced by the banner block that layout carried
  (`jcbsupportmessage` on the support rotation, else
  `ComponentbuilderHelper::getDynamicContent('banner', '728-90')`). A folder
  picker modal (`#extrusion-folder-modal`: current path, folder list, choose
  and cancel) stands beside the target-picker modal. The text map gains the
  picker strings and a catalogue-failure message; the empty-source message
  now names the two folders. The intro paragraph now describes selecting
  folders straight from the site.
- **Why:** The feed did nothing without its script; folders are selected,
  not typed.
- **Related paths/symbols:** `admin/layouts/jcbsupportmessage.php`,
  `ComponentbuilderHelper::getDynamicContent()`.

### `admin/assets/js/extrusion.js`

- **Change type:** Modified
- **Stable location(s):** `readConfig()`, `harvest()`, `loadCatalogue()`,
  `renderBoard()`, and the new `openFolders()`/`openFolderPicker()`/
  `chooseFolder()`/`decorateFolderFields()`
- **What changed:** The configuration no longer carries `path` or `dump`.
  Every folder field gets a select button (`decorateFolderFields()`), which
  opens the picker: the server lists the folders below the site root, the
  person walks down (and back up) and chooses, and the full path is composed
  from the base the server reports — the libraries field appends a line per
  choice. A failed catalogue fetch is no longer swallowed: it sets a flag and
  the board renders a visible warning (`data-extrusion-warning="catalogue"`),
  which is also what lets the GUI suite assert schema health.
- **Why:** Selection over typing; and the silent catalogue failure is what
  let a real SQL error hide behind a working-looking board.
- **Related paths/symbols:** `AjaxModel::extrusionFolders`, the picker modal
  in the template.

### `admin/src/Model/AjaxModel.php`

- **Change type:** Modified
- **Stable location(s):** new `extrusionFolders()`
- **What changed:** One new AJAX method listing the folders below one folder
  of this site: gated on `extrusion.access`, resolved through `realpath` and
  refused unless the target stays below the site root (traversal, symlinks
  out, and files all answer with an error), returning the base, the relative
  path, the parent, and the sorted folder names.
- **Why:** The picker's server half. Bounded to the site root because that is
  the whole promise of the picker — what is chosen is a folder of this site.
- **Related paths/symbols:** `AjaxController` task `extrusionFolders`,
  `openFolders()` in extrusion.js.

### `admin/src/Controller/AjaxController.php`

- **Change type:** Modified
- **Stable location(s):** `__construct()` task registry; `ajax()` switch
- **What changed:** Registered `extrusionFolders` and added its switch case
  in the exact shape of the existing cases (`path` as RAW, resolved and
  contained server-side in the model).
- **Why:** The route to the new model method.
- **Related paths/symbols:** `AjaxModel::extrusionFolders`.

### `admin/assets/css/extrusion.css`

- **Change type:** Modified
- **Stable location(s):** appended `.extrusion-folder-path`,
  `.extrusion-folder-select`
- **What changed:** Styles for the picker's current-path line and the select
  buttons beside the folder fields.
- **Why:** The picker is new UI.
- **Related paths/symbols:** the picker modal and `decorateFolderFields()`.

## Impact

- **Behavioral impact:** The setup pane asks for two component folders and
  the libraries, all selected by walking the site from its root; typed paths
  still work in the same inputs. The SQL dump input is gone — the engine
  reads the install SQL out of the admin folder, which existing engine tests
  already prove. One new AJAX task (`extrusionFolders`) exists, gated on
  `extrusion.access` and bounded to the site root. A component harvest that
  previously failed with "Unknown column 'a.name_single'" now succeeds (the
  column fix itself is in-scope library work:
  `Extrusion/Resolver/Candidates.php` queries `site_view.name`, and the test
  fixture now refuses any column the install SQL does not define). A failed
  catalogue fetch shows a warning on the board instead of silently rendering
  a board where nothing matches.
- **Visual impact:** Fewer setup fields; select buttons beside each folder
  field; a folder-picker modal; the Mastodon feed gone from both panes with
  the banner block kept.
- **Accessibility impact:** The picker is buttons in a list — keyboard
  reachable; not audited with a screen reader.
- **Compatibility impact:** Unchanged from the view's original record; the
  folders endpoint uses `str_starts_with` (PHP 8.0+, the repository floor).
- **Generated-output impact:** None; no compiler template changed.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php -l` on every modified PHP file | PHP 8.3 CLI | Pass |
| `node --check` on extrusion.js and the spec | Node 22 | Pass |
| Full engine suite `vendor/bin/phpunit` | PHP 8.3 | Pass — 326 Extrusion tests, full suite green, including the extended `CandidatesTest` that now walks the site-view pairing path against the schema-validated fixture |
| Schema-guard regression proof | PHP 8.3 CLI | Pass — the fixture refuses `name_single` on `site_view` in both declaration and query, and passes core tables through |
| `git diff --check`, style/ownership/moved-conditions/container-keys gates | tests project | Pass |

### GUI test coverage

- **Spec files added/updated:**
  `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js` — the setup-surface
  spec asserts the new two-folder surface, the select buttons, the absence of
  the combined path, the dump, and the feed; a new picker spec walks
  root → administrator → components → com_componentbuilder and asserts the
  chosen path lands in the field; the powers journey now asserts the
  catalogue loaded without complaint; and a new heavyweight spec harvests the
  **installed component itself** (both folders, language scope off) and
  asserts the pairing board stands with the admin views and no catalogue
  warning — the exact run the original suite lacked, which is why the schema
  fault reached a live site. `.github/gui-tests/run.sh` raises the
  container's PHP execution/memory limits for that harvest.

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| In-browser exercise of the reworked pane | Not available in this container (no docker) | Not performed — the GUI workflow on the pull request is the execution evidence |

### Checks not performed

- In-browser exercise in the authoring container (no docker); the GUI
  workflow runs the full suite, picker and component harvest included, on
  the pull request.

## Risks, limitations, and rollback

- **Known risks:** `extrusionFolders` lists directory names below the site
  root to authenticated users holding `extrusion.access`; it never leaves the
  root (realpath containment) and lists no files. The component-harvest spec
  is the suite's heaviest test; its limits (300s, 1G in the container) are
  set by the harness.
- **Known limitations:** The picker walks this site's filesystem only — a
  source outside the site root must still be typed into the same fields.
- **Rollback:** Revert the six modified files' changes from this record's
  diff; the previous surface (three typed paths, dump field, noticeboard
  renders) returns. The library-side column fix should not be rolled back —
  it corrects a query against the real schema.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/View/Extrusion/HtmlView.php` | JCB custom admin view `extrusion` (view) | Yes | Pending source identification | Same view as the original record; owner to carry the reworked fieldset into the JCB definition. |
| `admin/tmpl/extrusion/default.php` | JCB custom admin view `extrusion` (default template) | Yes | Pending source identification | Same as above. |
| `admin/assets/js/extrusion.js` | JCB custom admin view `extrusion` (javascript file) | Yes | Pending source identification | Same as above. |
| `admin/assets/css/extrusion.css` | JCB custom admin view `extrusion` (css file) | Yes | Pending source identification | Same as above. |
| `admin/src/Controller/AjaxController.php` | JCB `ajax` controller definition | Yes | Pending source identification | One added task; owner to mirror. |
| `admin/src/Model/AjaxModel.php` | JCB `ajax` model definition | Yes | Pending source identification | One added method; owner to mirror. |

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
