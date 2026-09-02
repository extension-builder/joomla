# API templates for the read-only resources of site views and custom admin views

## Change identity

- **Date first changed:** 2026-09-02
- **Author/implementer:** Claude Code (agent), for Llewellyn van der Merwe
- **Task/issue/PR:** branch `claude/jcb-api-dynamic-views-z0qcoe`,
  "add the site view and custom admin view API endpoints"
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** the task request in this session: "we wanna
  create now an API, basically, advanced layer that gives us the ability to
  access that result sets via an API ... add the site view and the custom
  admin view API endpoints as well", and "we first wanna just build the
  compiler side of everything".
- **Authorized paths:** `admin/compiler/joomla_4/API_DYNAMIC_VIEW_CONTROLLER.php`,
  `admin/compiler/joomla_4/API_DYNAMIC_VIEW_JSON.php`,
  `admin/compiler/joomla_4/API_DYNAMIC_VIEWS_CONTROLLER.php`,
  `admin/compiler/joomla_4/API_DYNAMIC_VIEWS_JSON.php`,
  `admin/compiler/joomla_4/settings.json`
- **Authorized outcome:** four new compiler templates and their
  `settings.json` mapping, so that a component with an admin API gets a
  read-only API controller and JSON view for every site view and custom
  admin view, served by the view's own model.
- **Permission summary:** new templates and one data mapping; no other
  `admin/**` path and no `media/js/**` path is touched. No GUI field is
  added: the task deferred the per-view API switch to a later change.

## Purpose and rationale

The admin view API classes read admin tables through a generated field map.
A site view or custom admin view has no such map: its data is whatever its
dynamic get selects, joins, filters and post-processes, so its resource must
be served by the view's own generated model. These templates carry the
static parts of that resource (the model call, the permission gate, the
read-only guards, the runtime discovery of the attributes) and the
placeholders the renderers under
`libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api/Dynamic`
fill per view. See section 8 of
[docs/architecture/api-generation.md](../architecture/api-generation.md).

## Affected paths

### Created

- `admin/compiler/joomla_4/API_DYNAMIC_VIEW_CONTROLLER.php`
- `admin/compiler/joomla_4/API_DYNAMIC_VIEW_JSON.php`
- `admin/compiler/joomla_4/API_DYNAMIC_VIEWS_CONTROLLER.php`
- `admin/compiler/joomla_4/API_DYNAMIC_VIEWS_JSON.php`

### Modified

- `admin/compiler/joomla_4/settings.json`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/compiler/joomla_4/API_DYNAMIC_VIEW_CONTROLLER.php`

- **Change type:** Created
- **Stable location(s):** class `###ApiName###Controller`
- **What changed:** the item resource controller of a site view or custom
  admin view whose main get is an item get: `getModel()`
  (`###API_DYNAMIC_VIEW_CONTROLLER_GETMODEL###`), `displayItem()` gated by
  `allowView()` (`###API_DYNAMIC_VIEW_CONTROLLER_ALLOWVIEW###`) with the
  documented expectations (`###API_DYNAMIC_VIEW_CONTROLLER_EXPECTATIONS###`),
  and 405 guards on the list, create, update and delete tasks.
- **Why:** the view's model resolves the item from its own dynamic get, the
  API must refuse before the model redirects, and the resource is read-only.
- **Related paths/symbols:** `Compiler\Architecture\Api\Dynamic\*`,
  `Compiler\Component\Structuremultiple::buildDynamicApi()`,
  `Compiler\Joomla*\Header` (`api.dynamic.view.controller`).

### `admin/compiler/joomla_4/API_DYNAMIC_VIEWS_CONTROLLER.php`

- **Change type:** Created
- **Stable location(s):** class `###ApiName###Controller`
- **What changed:** the list resource controller of a view whose main get
  is a list get: `getModel()`, `displayList()` gated by `allowView()` with
  the documented expectations, and 405 guards on the other tasks.
- **Why:** as above, for the list; pagination is Joomla's own when the get
  paginates, and the model forces every record otherwise.
- **Related paths/symbols:** as above, `api.dynamic.views.controller`.

### `admin/compiler/joomla_4/API_DYNAMIC_VIEW_JSON.php`

- **Change type:** Created
- **Stable location(s):** class `JsonapiView` (item)
- **What changed:** `displayItem()` takes the attributes from the keys of
  the item the model returned, and `prepareItem()` carries
  `###API_DYNAMIC_VIEW_JSON_PREPAREITEM###` (the id guard, the multi-row
  joins, the custom gets).
- **Why:** the dynamic get decides the shape; there is no compile-time
  field map for these views.
- **Related paths/symbols:** `Compiler\Architecture\Api\Dynamic\PrepareItem`,
  `api.dynamic.view.json`.

### `admin/compiler/joomla_4/API_DYNAMIC_VIEWS_JSON.php`

- **Change type:** Created
- **Stable location(s):** class `JsonapiView` (list)
- **What changed:** `displayList()` takes the attributes from the union of
  the row keys, carries `###API_DYNAMIC_VIEWS_JSON_META###` (the custom gets
  as document meta), and `prepareItem()` carries
  `###API_DYNAMIC_VIEWS_JSON_PREPAREITEM###`; a `$position` counter ids rows
  without one.
- **Why:** as above, for the list.
- **Related paths/symbols:** `Compiler\Architecture\Api\Dynamic\Meta`,
  `api.dynamic.views.json`.

### `admin/compiler/joomla_4/settings.json`

- **Change type:** Modified
- **Stable location(s):** `move.dynamic.api`
- **What changed:** the four templates are mapped to
  `c0mp0n3nt/api/src/Controller/[[[Name]]]Controller.php` and
  `c0mp0n3nt/api/src/View/[[[Name]]]/JsonapiView.php` with the build types
  `dynamic_single` and `dynamic_list`.
- **Why:** `Structuremultiple::buildDynamicApi()` builds one pair per view
  by the main get's type, under the API name the resources map resolved.
- **Related paths/symbols:** `Compiler\Architecture\Api\Resources`,
  `Compiler\Utilities\Structure::build()`.

## Impact

- **Behavioral impact:** a component whose admin views ask for an API now
  also gets API classes for its site views and custom admin views. A
  component without an admin API is unchanged.
- **Visual impact:** None; these are compiler templates, not interface files.
- **Accessibility impact:** None; no interface change.
- **Compatibility impact:** the generated code targets Joomla 4, 5 and 6;
  Joomla 3 never builds an API. The generated site and custom admin models
  gain one guard in their empty-result fail-safe (a 404 under the API
  client instead of a redirect), rendered by
  `Compiler\Dynamicget\GetItem`, which is a library change and not a
  template change.
- **Generated-output impact:** two new `api/src/...` files per site view and
  custom admin view of a component with an admin API; the `getItem()`
  fail-safe of every site and custom admin item model gains the API guard.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `composer test` in `libraries/vendor_jcb/tests` | PHP 8.4.19, PHPUnit 12.5, Joomla 6.1.2 checkout | See the pull request description for the recorded run. |
| `php -l` on the four templates rendered with the real renderers for a site item view, a site list view and a custom admin view | PHP 8.4.19 | Passed in the session (scratch render). |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Compile a component with an admin API, a site view and a custom admin view and read the generated `api/src` files | not available in this session (no Joomla runtime with a database) | Not performed; see "Checks not performed". |

### GUI test coverage

- **Spec files added/updated:** None. The templates are compiler inputs with
  no browser-facing behaviour; the change is covered by the renderer unit
  tests under `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api`
  and the structure, site views and custom admin views tests.

### Checks not performed

- A full compile against a Joomla installation and an HTTP round trip
  against the generated endpoints; no Joomla runtime with a database is
  available in this session.

## Risks, limitations, and rollback

- **Known risks:** the resource attributes are discovered at runtime from
  the model's result, so a dynamic get that selects a sensitive column
  exposes it to every caller the permissions admit, exactly as the HTML
  view's template could.
- **Known limitations:** no per-view API switch until the GUI carries one;
  a main get with custom SQL gets no resource; the category, tags and date
  filter types are not built by the compiler in any target.
- **Rollback:** delete the four templates and their `settings.json`
  entries; the renderers then write placeholders no template carries, which
  is harmless.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_4/API_DYNAMIC_VIEW_CONTROLLER.php` | Itself | No | Not applicable — owner confirmed | Compiler template input, not generated output: `Compiler\Utilities\Structure::build()` copies it from `Paths::template_path`. The owner asked for these templates in this session. No next action. |
| `admin/compiler/joomla_4/API_DYNAMIC_VIEW_JSON.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/API_DYNAMIC_VIEWS_CONTROLLER.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/API_DYNAMIC_VIEWS_JSON.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/settings.json` | Itself | No | Not applicable — owner confirmed | Compiler input data (see the 2026-09-01 API templates record); the owner asked for the endpoints in this session. No next action. |

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
