# API templates carry the generated controller and JSON view bodies

## Change identity

- **Date first changed:** 2026-09-01
- **Author/implementer:** Claude Code (agent), for Llewellyn van der Merwe
- **Task/issue/PR:** branch `claude/jcb-api-endpoint-generation-z0qcoe`,
  "complete the generated API endpoints for admin views"
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** the task request in this session: "our main
  objective ... is to add extra placeholders to the existing templates and to
  generate all the code", and the follow-up "if we can get this implemented".
- **Authorized paths:** `admin/compiler/joomla_4/API_VIEW_CONTROLLER.php`,
  `admin/compiler/joomla_4/API_VIEW_JSON.php`,
  `admin/compiler/joomla_4/API_VIEWS_CONTROLLER.php`,
  `admin/compiler/joomla_4/API_VIEWS_JSON.php`
- **Authorized outcome:** the four API templates carry the placeholders and
  the static method bodies that make the generated API controllers and JSON
  views complete, so that a component compiled with the API option on an
  admin view gets working list, item, create, update and delete classes.
- **Permission summary:** template edits only; no other `admin/**` path and
  no `media/js/**` path is touched. `settings.json` and the file names the
  templates map to are unchanged.

## Purpose and rationale

The compiler copies these four templates whenever an admin view is linked to
a component with the API selector set, but they were shells: the JSON views
had empty class bodies, which Joomla rejects with a `BadMethodCallException`
on every request, and the controllers carried no model mapping, no key
resolution, no list state mapping and no delete permission. The new renderers
under `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api`
produce the view-specific code, but that code can only reach the generated
files through placeholders in these templates, and the parts that do not vary
per view belong in the templates the same way the existing shells do. See
[docs/architecture/api-generation.md](../architecture/api-generation.md).

## Affected paths

### Created

- None

### Modified

- `admin/compiler/joomla_4/API_VIEW_CONTROLLER.php`
- `admin/compiler/joomla_4/API_VIEW_JSON.php`
- `admin/compiler/joomla_4/API_VIEWS_CONTROLLER.php`
- `admin/compiler/joomla_4/API_VIEWS_JSON.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/compiler/joomla_4/API_VIEW_CONTROLLER.php`

- **Change type:** Modified
- **Stable location(s):** class `###View###Controller`
- **What changed:** `$contentType` is now the list code (`###views###`) so
  the JSON:API type is shared with the list resource; new methods
  `getModel()` (`###API_VIEW_CONTROLLER_GETMODEL###`), `displayItem()`,
  `edit()`, `delete()`, `getRecordId()` (`###API_VIEW_CONTROLLER_RECORDID###`),
  `allowView()` (`###API_VIEW_CONTROLLER_ALLOWVIEW###`) and `allowDelete()`
  (`###API_VIEW_CONTROLLER_ALLOWDELETE###`); `allowAdd()` and `allowEdit()`
  keep their placeholders.
- **Why:** explicit model resolution, record resolution by any unique key,
  read access and delete permissions from the view's own permission names,
  and a delete flow with 404/409 handling on every supported Joomla target.
- **Related paths/symbols:** `Compiler\Architecture\Api\Controller\*`,
  `Compiler\Architecture\AdminViews\EditView`, `Compiler\Joomla*\Header`
  (`api.view.controller` imports).

### `admin/compiler/joomla_4/API_VIEWS_CONTROLLER.php`

- **Change type:** Modified
- **Stable location(s):** class `###Views###Controller`
- **What changed:** new methods `getModel()`
  (`###API_VIEWS_CONTROLLER_GETMODEL###`), `displayList()`
  (`###API_VIEWS_CONTROLLER_DISPLAYLIST###`), read-only guards for
  `displayItem()`, `add()`, `edit()` and `delete()` (HTTP 405), and the
  `cleanFilter()` helper.
- **Why:** the list resource maps request filters and ordering onto the list
  model state and is read-only by contract.
- **Related paths/symbols:** `Compiler\Architecture\Api\Controller\DisplayList`,
  `Compiler\Architecture\AdminViews\ListView`, `Compiler\Joomla*\Header`
  (`api.views.controller` imports).

### `admin/compiler/joomla_4/API_VIEW_JSON.php`

- **Change type:** Modified
- **Stable location(s):** class `JsonapiView` (item)
- **What changed:** `$fieldsToRenderItem` (`###API_VIEW_JSON_FIELDS###`),
  `displayItem()` with `###API_VIEW_JSON_PERMISSIONS###`, and `prepareItem()`
  with `###API_VIEW_JSON_PREPAREITEM###`.
- **Why:** Joomla renders only declared fields; the field permissions and the
  tag conversion come from the view's definition.
- **Related paths/symbols:** `Compiler\Architecture\Api\View\*`,
  `Compiler\Architecture\AdminViews\EditView`.

### `admin/compiler/joomla_4/API_VIEWS_JSON.php`

- **Change type:** Modified
- **Stable location(s):** class `JsonapiView` (list)
- **What changed:** `$fieldsToRenderList` (`###API_VIEWS_JSON_FIELDS###`),
  `displayList()` with `###API_VIEWS_JSON_PERMISSIONS###`, and `prepareItem()`
  with `###API_VIEWS_JSON_PREPAREITEM###`.
- **Why:** the list renders every table column, so the values the list model
  leaves raw are decoded here and the field permissions applied.
- **Related paths/symbols:** `Compiler\Architecture\Api\View\*`,
  `Compiler\Architecture\AdminViews\ListView`.

## Impact

- **Behavioral impact:** generated components with the API option on an
  admin view now get functional API classes instead of shells. Components
  without the option are unchanged (the `api/` folder is still removed).
- **Visual impact:** None; these are compiler templates, not interface files.
- **Accessibility impact:** None; no interface change.
- **Compatibility impact:** the generated code targets Joomla 4, 5 and 6
  (all compile from this template folder); Joomla 3 never builds an API.
  The generated bodies avoid PHP features newer than the targets require.
- **Generated-output impact:** the four `api/src/...` files of every admin
  view with `add_api` set change; no other generated file changes.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `composer test` in `libraries/vendor_jcb/tests` | PHP 8.4.19, PHPUnit 12.5, Joomla 6.1.2 checkout | See the pull request description for the recorded run. |
| `php -l` on the four templates' generated form | PHP 8.4.19 | Templates are copied, not executed; the renderer tests assert the bodies they receive. |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Compile a component with `add_api` = Both on one view and read the four generated files | not available in this session (no Joomla runtime with a database) | Not performed; see "Checks not performed". |

### GUI test coverage

- **Spec files added/updated:** None. The templates are compiler inputs with
  no browser-facing behaviour; the change is covered by the renderer unit
  tests under `libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api`
  and the `EditViewTest` / `ListViewTest` placeholder assertions.

### Checks not performed

- A full compile of a component against a Joomla installation, and an HTTP
  round trip against the generated endpoints; no Joomla runtime with a
  database is available in this session.

## Risks, limitations, and rollback

- **Known risks:** the generated `getModel()` maps every model name other
  than the list name to the item model, so a custom API controller in the
  same component that relies on inflected names must map its own.
- **Known limitations:** routes are still registered by a `webservices`
  plugin the JCB user creates and links; the manifest `<api>` block is a
  separate change.
- **Rollback:** restore the four templates from the parent commit; the
  renderers then write placeholders no template carries, which is harmless.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_4/API_VIEW_CONTROLLER.php` | Itself | No | Not applicable — owner confirmed | Compiler template input, not generated output: `Compiler\Utilities\Structure::build()` copies it from `Paths::template_path`. The owner asked for these template edits in this session. No next action. |
| `admin/compiler/joomla_4/API_VIEW_JSON.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/API_VIEWS_CONTROLLER.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/API_VIEWS_JSON.php` | Itself | No | Not applicable — owner confirmed | As above. |

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
