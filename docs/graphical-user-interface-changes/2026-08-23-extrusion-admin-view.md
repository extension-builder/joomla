# Extrusion admin view

## Change identity

- **Date first changed:** 2026-08-23
- **Author/implementer:** Claude (coding agent), for lemuel@vdm.to
- **Task/issue/PR:** Branch `claude/joomla-library-extrusion-518uv2`, follow-up
  to PR #34 (powers extrusion pipeline)
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active session request: "Now we need you to create
  the graphical user interface … use the same architecture [as the Compiler
  view] to now build this view with which to extrude the components …
  everything that's needed to make that entire view work."
- **Authorized paths:** The `admin/**` files needed to register and serve one
  new custom admin view named `extrusion`, plus the `componentbuilder.xml`
  manifest submenu entry that view registration requires.
- **Authorized outcome:** A working extrusion view: source and switch
  configuration, AJAX harvest, a pairing board that matches harvested items to
  existing entities with create-new/update/ignore decisions and bulk actions,
  and an import that reports its results on the page.
- **Permission summary:** Permission covers the new view's own files, the
  registration seams every view needs (access.xml, manifest submenu, dashboard
  model, helper submenu, structural language constants), and the extrusion
  cases in the existing AJAX controller/model pair. It does not extend to any
  other admin view, to `media/**`, or to generated compiler templates.

## Purpose and rationale

The extrusion engines (`libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/
Extrusion/**`) can harvest a component source and a powers library and now
accept pairing verdicts, but nothing in the application exposes them. A person
needs a place to aim the tool, see what a harvest found, decide item by item
what is created, what updates an existing definition, and what is ignored, and
then run the import and read the report. Views live under `admin/**` by
Joomla's architecture, so no `libraries/vendor_jcb/**` change alone can
deliver this outcome; the engine-side additions (harvest preview, pairing
verdicts, candidate resolver) were kept in-scope and this record covers only
the interface that drives them.

## Affected paths

### Created

- `admin/assets/css/extrusion.css`
- `admin/assets/images/icons/extrusion.png`
- `admin/assets/js/extrusion.js`
- `admin/src/Controller/ExtrusionController.php`
- `admin/src/Model/ExtrusionModel.php`
- `admin/src/View/Extrusion/HtmlView.php`
- `admin/src/View/Extrusion/index.html`
- `admin/tmpl/extrusion/default.php`
- `admin/tmpl/extrusion/index.html`
- `docs/development/user-interface-language-strings.md`
- `docs/graphical-user-interface-changes/2026-08-23-extrusion-admin-view.md`

### Modified

- `admin/access.xml`
- `admin/language/en-GB/en-GB.com_componentbuilder.ini`
- `admin/language/en-GB/en-GB.com_componentbuilder.sys.ini`
- `admin/src/Controller/AjaxController.php`
- `admin/src/Helper/ComponentbuilderHelper.php`
- `admin/src/Model/AjaxModel.php`
- `admin/src/Model/ComponentbuilderModel.php`
- `componentbuilder.xml`
- `docs/architecture/extrusion.md`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/src/View/Extrusion/HtmlView.php`

- **Change type:** Created
- **Stable location(s):** `VDM\Component\Componentbuilder\Administrator\View\
  Extrusion\HtmlView` — `display()`, `getDynamicForm()`, `field()`,
  `addToolbar()`, `_prepareDocument()`
- **What changed:** New view class following the Compiler view's shape:
  `Actions::get('extrusion')` permissions, model-provided styles/scripts,
  `getDynamicForm()` building three fieldsets (`source`, `switches`,
  `advanced`) programmatically via `FormHelper::xml()` + `Form::setField()`
  covering the full engine configuration surface (paths, libraries, SQL dump,
  target component, mode, onExisting, nine scope switches, layout, language
  tag, table class, dry run, strict, depth, max files), a `field()` helper to
  keep the builder readable, and `_prepareDocument()` loading
  `assets/js/extrusion.js` plus the model's styles/scripts.
- **Why:** The user asked for the Compiler view's architecture, including its
  view-based dynamic form building, for the extrusion configuration.
- **Related paths/symbols:** `ExtrusionModel` (styles/items/components),
  `admin/tmpl/extrusion/default.php` (renders the fieldsets),
  `VDM\Joomla\Utilities\FormHelper`,
  `VDM\Joomla\Componentbuilder\Utilities\Permitted\Actions`.

### `admin/src/Model/ExtrusionModel.php`

- **Change type:** Created
- **Stable location(s):** `ExtrusionModel` — `getListQuery()`, `getItems()`,
  `getComponents()`, `getStyles()`/`getScripts()` and their setters
- **What changed:** New `ListModel` mirroring `CompilerModel`: published
  `joomla_component` rows (id, guid, system_name, name, name_code) ordered
  newest first, an `extrusion.access` gate in `getItems()`, and the
  styles (`admin.css`, `extrusion.css`) / scripts (`admin.js`) arrays the view
  loads.
- **Why:** The view needs the target-component list for its form and the
  asset lists the Compiler architecture expects a model to provide.
- **Related paths/symbols:** `HtmlView::display()`,
  `admin/assets/css/extrusion.css`.

### `admin/src/Controller/ExtrusionController.php`

- **Change type:** Created
- **Stable location(s):** `ExtrusionController` — `getModel()`, `dashboard()`
- **What changed:** New `AdminController` with the Compiler controller's
  `getModel()` proxy and `dashboard()` redirect. No run tasks: harvest,
  catalogue and import all travel through the AJAX pipeline.
- **Why:** Joomla needs a controller for the view; the page's actions are
  AJAX by design so only the dashboard task exists.
- **Related paths/symbols:** `admin/tmpl/extrusion/default.php` (toolbar task
  `extrusion.dashboard`).

### `admin/tmpl/extrusion/default.php`

- **Change type:** Created
- **Stable location(s):** Template body — tab strip `#extrusion-tabs`, panes
  `#extrusion-pane-{setup,running,pairing,results}`, modal `#extrusion-modal`,
  bootstrap block `window.JCBExtrusion`
- **What changed:** New template: an `extrusion.access` gate; four tab panes
  (setup form with the three fieldsets and harvest button; running pane with
  the Mastodon noticeboard exactly as the compiler renders it via
  `LayoutHelper::render('jcbnoticeboard', …)` in both its plain and
  `mastodon-feed-2` forms; pairing pane with target-component select, bulk
  bar, filter box, board container and import button gated on
  `extrusion.import`; results pane); the shared searchable target-picker
  modal; and the `window.JCBExtrusion` bootstrap carrying the AJAX gateway URL
  (with form token), the `canImport` flag, and the natural-language string map
  for the JavaScript. Every user-facing string is a natural string inside
  `Text::_()` per `docs/development/user-interface-language-strings.md`.
- **Why:** This is the page itself: the requested tab-swapping UX, the
  noticeboard, and the pairing board's static scaffolding.
- **Related paths/symbols:** `admin/layouts/jcbnoticeboard.php` (existing,
  unchanged), `admin/assets/js/extrusion.js`, `Session::getFormToken()`.

### `admin/tmpl/extrusion/index.html`, `admin/src/View/Extrusion/index.html`

- **Change type:** Created
- **Stable location(s):** Whole file
- **What changed:** The repository's standard blank `index.html` copied into
  the two new folders.
- **Why:** Every admin folder in this component carries one to prevent
  directory listing.
- **Related paths/symbols:** None

### `admin/assets/js/extrusion.js`

- **Change type:** Created
- **Stable location(s):** IIFE — `readConfig()`, `harvest()`,
  `loadCatalogue()`/`rematch()`/`matchByName()`, `proposal()`/`decision()`/
  `decide()`, `renderBoard()`/`kindSection()`/`powersSection()`/`row()`,
  `modalPool()`/`openModal()`/`renderModalList()`, `bulk()`, `applyFilter()`,
  `buildDecisions()`, `runImport()`, `renderResults()`
- **What changed:** New script driving the whole journey: serialises the setup
  form, POSTs `ajax.extrusionHarvest`, renders the pairing board as dense
  nested `<details>` accordions (kinds; admin views with nested field lists;
  powers grouped by library and bundle), proposes update for name-matched
  items and create for the rest, gives every row Create new (first), Update
  (opens the one shared searchable picker fed from the catalogue pool of that
  kind), and Ignore, supports tick-based bulk create/ignore/reset and a text
  filter, re-fetches the catalogue on target-component change and re-pairs
  everything by the server's own lowercase name/system rule, builds the
  verdict payload (only overrides and proposed updates travel; untouched new
  candidates keep the engine's deterministic identity), POSTs
  `ajax.extrusionImport`, and renders the report: messages by level and the
  written/skipped/failed trees. All user-facing strings come from the
  template's `Text::_()` map; the script carries none of its own. The script
  loads in the document head, before the template's inline bootstrap defines
  `window.JCBExtrusion`, so it reads the bootstrap lazily on
  `DOMContentLoaded` rather than at evaluation time, and it treats only a
  harvest answer that says success as one -- a missing or malformed payload
  lands back on setup with a message, never on an empty pairing board.
- **Why:** The requested pairing moment, bulk actions, dense navigation for
  hundreds-to-thousands of items (one shared modal picker instead of one
  select per row), and the live import report.
- **Related paths/symbols:** `window.JCBExtrusion` (template bootstrap),
  `AjaxModel::extrusionHarvest/extrusionCatalogue/extrusionImport`.

### `admin/assets/css/extrusion.css`

- **Change type:** Created
- **Stable location(s):** Selector groups `#extrusion-tabs`,
  `#extrusion-board`, `.extrusion-row`/`.extrusion-act`,
  `#extrusion-bulk-bar`, `.extrusion-modal*`, `.extrusion-detected`
- **What changed:** New stylesheet for the board (scrollable, nested
  accordions, flex rows with active-state action buttons, explicit-decision
  highlight), the sticky bulk bar, and the shared picker modal.
- **Why:** The board and modal are new UI with no existing styles.
- **Related paths/symbols:** Loaded via `ExtrusionModel::$styles`.

### `admin/assets/images/icons/extrusion.png`

- **Change type:** Created
- **Stable location(s):** Whole file
- **What changed:** Dashboard icon for the new tile, derived from the
  existing `joomla_components.png` (the JCB product box) by recolouring only
  its saturated tool pixels from amber to teal — the box, label and shading
  are untouched, so the icon sits in the existing family while standing
  apart from the components tile beside it.
- **Why:** `ComponentbuilderModel::parseViewDefinition()` derives
  `extrusion.png` from the `png.extrusion` view-group entry, and the tile
  stands directly next to the compiler and components tiles, so it must
  match their style without duplicating either.
- **Related paths/symbols:** `ComponentbuilderModel::$viewGroups`.

### `admin/src/Controller/AjaxController.php`

- **Change type:** Modified
- **Stable location(s):** `__construct()` task registry; `ajax()` switch
- **What changed:** Registered three tasks (`extrusionHarvest`,
  `extrusionImport`, `extrusionCatalogue`) and added their switch cases in the
  exact shape of the existing cases (same token/user checks, same
  callback/raw/json output paths). `config` and `decisions` arrive as RAW JSON
  strings; `component_id` as INT.
- **Why:** The page's three operations must travel the existing AJAX pipeline,
  per the request.
- **Related paths/symbols:** `AjaxModel::extrusionHarvest/extrusionImport/
  extrusionCatalogue`.

### `admin/src/Model/AjaxModel.php`

- **Change type:** Modified
- **Stable location(s):** New import `ExtrusionFactory`; appended methods
  `extrusionHarvest()`, `extrusionImport()`, `extrusionCatalogue()`,
  `extrusionEngines()`, `extrusionPowersTree()`
- **What changed:** Three public AJAX methods and two protected helpers.
  `extrusionEngines()` resets the shared Extrusion container state once, then
  configures the component extruder (paths, dump, component, mode, onExisting,
  layout, languageTag, tableClass, dryRun, strict, limits, scopes) and the
  powers extruder (libraries, component, onExisting, dryRun, limits) from the
  page's JSON, returning null for any engine that was given nothing to read.
  `extrusionHarvest()` (gated on `extrusion.access`) harvests both pipelines
  without writing and returns the pairing payload: resolved target component,
  detected component, published components, the `Candidates` resolver's
  pre-paired candidate tree, the trimmed powers tree
  (`extrusionPowersTree()` drops class bodies before anything reaches the
  browser), the engines' messages and the report. `extrusionImport()` (gated
  on `extrusion.import` + `extrusion.access`) re-harvests server-side, loads
  the verdicts through the `Extrusion.Resolver.Pairing` service after the
  reset (reset is the run boundary), extrudes both pipelines and returns
  messages plus the full report. `extrusionCatalogue()` returns one
  component's linked definitions for the client-side re-pairing.
- **Why:** These are the server halves of the three page operations; the
  engines and resolvers live in-scope under `libraries/vendor_jcb/**` and are
  only orchestrated here.
- **Related paths/symbols:**
  `VDM\Joomla\Componentbuilder\Extrusion\Factory`, services `Extruder`,
  `Extrusion.Powers.Extruder`, `Extrusion.Resolver.Candidates`,
  `Extrusion.Resolver.Pairing`, `Extrusion.Registry.Report`.

### `admin/access.xml`

- **Change type:** Modified
- **Stable location(s):** `<section name="component">`, alphabetical slot
  between `dynamic_get.submenu` and `field.init`
- **What changed:** Four actions: `extrusion.access`,
  `extrusion.dashboard_list`, `extrusion.import`, `extrusion.submenu`, with
  `COM_COMPONENTBUILDER_EXTRUSION_*` titles/descriptions.
- **Why:** `Actions::get('extrusion')`, the dashboard tile, the submenu entry
  and the import button all check these actions.
- **Related paths/symbols:** `ComponentbuilderModel::$viewAccess`,
  `ComponentbuilderHelper::addSubmenu()`, `HtmlView`, `AjaxModel`.

### `admin/src/Model/ComponentbuilderModel.php`

- **Change type:** Modified
- **Stable location(s):** `$viewGroups['main']`; `$viewAccess`
- **What changed:** Added `'png.extrusion'` directly after `'png.compiler'`
  in the main view group, and the three `extrusion.access/submenu/
  dashboard_list` entries after the compiler entries in `$viewAccess`.
- **Why:** Puts the extrusion tile on the JCB dashboard under the same access
  rules every other tile follows.
- **Related paths/symbols:** `admin/assets/images/icons/extrusion.png`,
  `admin/access.xml`.

### `admin/src/Helper/ComponentbuilderHelper.php`

- **Change type:** Modified
- **Stable location(s):** `addSubmenu()`, directly after the compiler entry
- **What changed:** One `Sidebar::addEntry()` block for the extrusion view,
  gated on `extrusion.access` && `extrusion.submenu`, labelled by the
  structural constant `COM_COMPONENTBUILDER_SUBMENU_EXTRUSION`.
- **Why:** Puts the view in the component's sidebar submenu like every other
  view.
- **Related paths/symbols:** `admin/access.xml`, language ini.

### `componentbuilder.xml`

- **Change type:** Modified
- **Stable location(s):** `<administration><submenu>`, after the compiler menu
- **What changed:** One `<menu option="com_componentbuilder"
  view="extrusion">COM_COMPONENTBUILDER_MENU_EXTRUSION</menu>` entry.
- **Why:** Registers the view in Joomla's component menu.
- **Related paths/symbols:** `COM_COMPONENTBUILDER_MENU_EXTRUSION` (sys.ini).

### `admin/language/en-GB/en-GB.com_componentbuilder.ini`

- **Change type:** Modified
- **Stable location(s):** Alphabetical constant slots
- **What changed:** Ten structural constants only: the eight
  `COM_COMPONENTBUILDER_EXTRUSION_*` ACL titles/descriptions,
  `COM_COMPONENTBUILDER_DASHBOARD_EXTRUSION`, and
  `COM_COMPONENTBUILDER_SUBMENU_EXTRUSION`. No view string was added: the
  view's own strings are natural strings inside `Text::_()` by convention
  (see `docs/development/user-interface-language-strings.md`).
- **Why:** Joomla resolves ACL, dashboard and submenu labels by constant
  before any view runs; these are the only strings that must be constants.
- **Related paths/symbols:** `admin/access.xml`,
  `ComponentbuilderModel::parseViewDefinition()`,
  `ComponentbuilderHelper::addSubmenu()`.

### `admin/language/en-GB/en-GB.com_componentbuilder.sys.ini`

- **Change type:** Modified
- **Stable location(s):** Alphabetical constant slots
- **What changed:** Nine structural constants: the same eight ACL constants
  plus `COM_COMPONENTBUILDER_MENU_EXTRUSION`.
- **Why:** The sys file serves the installer and the admin menu, mirroring
  how the compiler's constants are placed.
- **Related paths/symbols:** `componentbuilder.xml`.

### `docs/architecture/extrusion.md`

- **Change type:** Modified
- **Stable location(s):** §5 sequencing notes, final bullet
- **What changed:** The "interface session will need those paths" bullet now
  records that the session delivered the `extrusion` view and names this
  record and the language-strings convention document.
- **Why:** Keeps the architecture document's forward reference honest.
- **Related paths/symbols:** This record.

### `docs/development/user-interface-language-strings.md`

- **Change type:** Created
- **Stable location(s):** Whole document
- **What changed:** Documents the requested convention: new hand-written view
  code uses natural strings inside `Text::_()`, untransformed and never added
  to the language files (JCB manages them at import); JavaScript receives its
  strings through a template-printed `Text::_()` map; structural constants
  (menu, submenu, dashboard, ACL) are the sole exception.
- **Why:** The user asked for this convention to be documented so future
  contributors do not "fix" the natural strings into constants.
- **Related paths/symbols:** The extrusion view stack above.

### `docs/graphical-user-interface-changes/2026-08-23-extrusion-admin-view.md`

- **Change type:** Created
- **Stable location(s):** Whole document
- **What changed:** This record.
- **Why:** Every `admin/**` change requires a same-change record.
- **Related paths/symbols:** None

## Impact

- **Behavioral impact:** One new admin view at
  `index.php?option=com_componentbuilder&view=extrusion`, a new dashboard
  tile, a new sidebar submenu entry, and three new AJAX tasks. All are gated
  on the new `extrusion.*` ACL actions (plus `extrusion.import` for writing);
  users without those permissions see no tile, no submenu entry, a
  no-access page, and permission errors from the AJAX methods. No existing
  view, task or route changes behavior.
- **Visual impact:** The new page itself; the dashboard gains one tile; the
  sidebar gains one entry. No existing screen changes.
- **Accessibility impact:** The board uses native `<details>/<summary>`
  disclosure, real `<button>` elements, labelled form fields from Joomla's
  renderer, and `aria-hidden` on decorative icons. Not audited with a screen
  reader in this change.
- **Compatibility impact:** Joomla 5/6 admin (`getDocument()->getToolbar()`,
  WebAssetManager conventions already used by the compiler view). JavaScript
  uses `fetch`, optional chaining targets none, and ES2019 features only —
  the same browser floor as the existing admin scripts. PHP: nullsafe calls
  (`?->`) and arrow functions require PHP 8.0+, matching the repository's
  floor.
- **Generated-output impact:** None. No compiler template
  (`admin/compiler/**`) changed, so generated components are unaffected.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php -l` on every created/modified PHP file | PHP 8.3 CLI | Pass — no syntax errors |
| `node --check admin/assets/js/extrusion.js` | Node 20 | Pass |
| XML well-formedness (`xml.dom.minidom`) on `admin/access.xml`, `componentbuilder.xml` | Python 3 | Pass |
| `git diff --check` | git 2.x | Pass — no whitespace errors introduced |
| Full engine test suite `vendor/bin/phpunit` (tests root) | PHP 8.3, PHPUnit 11 | 3958 tests, 1 pre-existing failure (`FieldCreatorTest::testFieldAsStringNormalizesBothRendererBackends`, fails identically on the merged base commit `62a5eed` with all this branch's work stashed — environment-dependent XML serialisation, unrelated to this change) |
| `tests/bin/check-test-ownership.php` | PHP 8.3 | Pass — admin files are not gated source roots; engine files all owned |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Render the view, run a harvest, pair, and import in a Joomla admin | Not available in this container (no Joomla installation/database) | Not performed — see below |

### GUI test coverage

- **Spec files added/updated:**
  `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js` (with
  `specs/global.setup.js`, `helpers/jcb.js`, `playwright.config.js` as the
  suite's shared foundation) — the first spec of the GUI suite, covering the
  dashboard tile placement next to the compiler, the menu link, the full
  setup surface including the advanced-options reveal, the empty-source
  error path, and the whole AJAX journey: harvest, pairing board
  (per-row decisions, explicit-mark and reset, bulk actions, filter, shared
  target picker), and a dry-run import through to the on-page report. The
  suite runs in the `GUI tests` workflow via `.github/gui-tests/run.sh`; it
  was not executed inside the authoring container (no docker), so its first
  execution evidence is that workflow's run on this change's pull request.

### Checks not performed

- In-browser exercise of the view (form render, harvest round-trip, pairing
  board interaction, import): this container has no running Joomla site or
  database. The engine halves of harvest/pairing/import are covered by the
  in-scope PHPUnit suites (`ExtruderTest` pairing tests, `PairingTest`,
  `CandidatesTest`); the AJAX and DOM halves follow the existing compiler and
  search view patterns but have not been executed against a live site.

## Risks, limitations, and rollback

- **Known risks:** The AJAX import runs the engines against admin-supplied
  filesystem paths. This is the same trust model as the compiler's
  folder-path options and is restricted by `extrusion.access` /
  `extrusion.import` ACL (deny-by-default for non-admin groups); the engines
  additionally bound their scan by depth/file-count limits and refuse
  unreadable roots.
- **Known limitations:** The pairing board's client-side re-pairing on
  component change replicates the server's name-matching rule; if that rule
  changes server-side, `matchByName()` in `extrusion.js` must follow. The
  import re-harvests server-side, so a source that changed between harvest
  and import is imported as it stands at import time.
- **Rollback:** Delete the created files listed above and revert the eight
  modified files' extrusion additions (four access.xml actions, one manifest
  menu line, `png.extrusion` + three `$viewAccess` entries, one
  `addSubmenu()` block, the `ExtrusionFactory` import + five appended
  `extrusion*` methods in `AjaxModel`, the three `registerTask` lines + three
  switch cases in `AjaxController`, and the nineteen language constants). No
  data migration is involved; the extrusion engines remain intact in-scope.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Controller/ExtrusionController.php` | JCB custom admin view `extrusion` (controller) in the JCB project definition | Yes | Pending source identification | New hand-written view intended for import into JCB (strings kept natural for that pipeline); owner to add the view to the JCB component definition. |
| `admin/src/Model/ExtrusionModel.php` | JCB custom admin view `extrusion` (model) | Yes | Pending source identification | Same as above. |
| `admin/src/View/Extrusion/HtmlView.php` | JCB custom admin view `extrusion` (view) | Yes | Pending source identification | Same as above. |
| `admin/src/View/Extrusion/index.html` | JCB folder-manifest output | Yes | Pending source identification | Standard blank index; generated by JCB's folder creation once the view is defined. |
| `admin/tmpl/extrusion/default.php` | JCB custom admin view `extrusion` (default template) | Yes | Pending source identification | Same as the view files. |
| `admin/tmpl/extrusion/index.html` | JCB folder-manifest output | Yes | Pending source identification | Same as the other index. |
| `admin/assets/js/extrusion.js` | JCB custom admin view `extrusion` (javascript file) | Yes | Pending source identification | Same as the view files. |
| `admin/assets/css/extrusion.css` | JCB custom admin view `extrusion` (css file) | Yes | Pending source identification | Same as the view files. |
| `admin/assets/images/icons/extrusion.png` | JCB dashboard icon asset | Yes | Pending source identification | Copy of `joomla_components.png`; owner may supply a distinct icon in JCB. |
| `admin/src/Controller/AjaxController.php` | JCB `ajax` controller definition (component ajax tasks) | Yes | Pending source identification | Three tasks/cases added in the generated file's own idiom; owner to add the same ajax methods to the JCB definition. |
| `admin/src/Model/AjaxModel.php` | JCB `ajax` model definition | Yes | Pending source identification | Same as the controller. |
| `admin/access.xml` | JCB component ACL definition | Yes | Pending source identification | Custom admin view + import permission actions; owner to mirror in JCB's view permissions. |
| `admin/src/Model/ComponentbuilderModel.php` | JCB dashboard/model definition | Yes | Pending source identification | `png.extrusion` + `$viewAccess` entries; owner to mirror. |
| `admin/src/Helper/ComponentbuilderHelper.php` | JCB helper definition (submenu) | Yes | Pending source identification | One submenu block; owner to mirror. |
| `componentbuilder.xml` | JCB manifest generation | Yes | Pending source identification | One submenu menu line; owner to mirror. |
| `admin/language/en-GB/en-GB.com_componentbuilder.ini` | JCB language management | Yes | Pending source identification | Structural constants only; JCB regenerates language files from its own store. |
| `admin/language/en-GB/en-GB.com_componentbuilder.sys.ini` | JCB language management | Yes | Pending source identification | Same as the ini. |

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
