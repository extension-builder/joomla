# Joomla 6 native component assets

## Change identity

- **Date first changed:** 2026-10-23
- **Author/implementer:** Codex assets/templates agent
- **Task/issue/PR:** Joomla 6 deprecation completion after PR #59; branch `deprecation/joomla6-native-completion`
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** The active request explicitly authorizes Joomla 6 compiler template migration: “all those everywhere”, “only affects Joomla 6”, and “move all those files to the media folder”.
- **Authorized paths:** `admin/compiler/joomla_6/` templates and settings needed for asset relocation, deprecated DOM helpers, and native model error handling.
- **Authorized outcome:** Joomla 6 generated extensions use native assets and error handling while older targets remain unchanged.
- **Permission summary:** Extend the existing Joomla 6 template set and its settings; preserve the Joomla Power resolver and established compiler architecture.

## Purpose and rationale

The templates and settings define generated extension files and view code. Library changes alone cannot relocate these files or replace emitted HTMLHelper asset calls. The conventional `admin/compiler/joomla_6` template directory remains authoritative. No new template loader or template suffix is introduced. Stable services `Architecture.View.JavaScriptFile`, `Architecture.AdminViews.ViewBody`, and `Architecture.AdminViews.ListView` select their Joomla 6 variants in `Service/ArchitectureView.php`; older targets retain the shared output. The optional Chartbuilder template is installed as `admin/src/Helper/Chartbuilder.php`, inside the manifest’s existing `src` folder.

## Affected paths

### Created

- None

### Modified

- `admin/compiler/joomla_6/ADMIN_IMPORT_HTML.php`
- `admin/compiler/joomla_6/ADMIN_VIEWS.php`
- `admin/compiler/joomla_6/ADMIN_VIEWS_HTML.php`
- `admin/compiler/joomla_6/ADMIN_VIEWS_MODEL.php`
- `admin/compiler/joomla_6/ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_MODEL.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/DASH_MODEL.php`
- `admin/compiler/joomla_6/DASH_VIEW_HTML.php`
- `admin/compiler/joomla_6/SITE_ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/SITE_VIEWS_HTML.php`
- `admin/compiler/joomla_6/SITE_VIEWS_MODEL.php`
- `admin/compiler/joomla_6/SITE_VIEW_HTML.php`
- `admin/compiler/joomla_6/SITE_VIEW_MODEL.php`
- `admin/compiler/joomla_6/default_import.php`
- `admin/compiler/joomla_6/default_import_custom.php`
- `admin/compiler/joomla_6/default_toolbar.php`
- `admin/compiler/joomla_6/settings.json`
- `admin/compiler/joomla_6/submitbutton.js`
- `admin/compiler/joomla_6/submitbutton_site.js`

### Moved or renamed

- None (generated package destinations change through settings).

### Deleted

- None

## Implementation details

### `admin/compiler/joomla_6/ADMIN_IMPORT_HTML.php`

- **Change type:** Modified
- **Stable location(s):** display
- **What changed:** Enable native model exception mode before reads and remove the deferred legacy getErrors drain.
- **Why:** Propagate failures using the Joomla 6 view/model contract.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/ADMIN_VIEWS.php`

- **Change type:** Modified
- **Stable location(s):** saveOrder setup
- **What changed:** Activate dragula and joomla.draggable through Web Asset Manager; include the session token in the ordering URL using the existing Joomla Power.
- **Why:** Use native Joomla ordering with authenticated save requests.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/ADMIN_VIEWS_HTML.php`

- **Change type:** Modified
- **Stable location(s):** display and _prepareDocument
- **What changed:** Enable native model exception mode before reads; register and activate ordered model CSS/JS through WAM with deterministic URI identities and site-root URL resolution.
- **Why:** Propagate model failures at the view boundary and preserve asset-list order, deduplication, external URLs and subdirectory installations.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/ADMIN_VIEWS_MODEL.php`

- **Change type:** Modified
- **Stable location(s):** $styles and $scripts defaults
- **What changed:** Point generated CSS/JavaScript lists at the matching administrator/site component media package.
- **Why:** Keep model-supplied assets aligned with settings destinations and prevent name collisions between clients.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/ADMIN_VIEW_HTML.php`

- **Change type:** Modified
- **Stable location(s):** display and _prepareDocument
- **What changed:** Enable native model exception mode before reads; register and activate ordered model CSS/JS through WAM with deterministic URI identities and site-root URL resolution.
- **Why:** Propagate model failures at the view boundary and preserve asset-list order, deduplication, external URLs and subdirectory installations.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable location(s):** $styles and $scripts defaults
- **What changed:** Point generated CSS/JavaScript lists at the matching administrator/site component media package.
- **Why:** Keep model-supplied assets aligned with settings destinations and prevent name collisions between clients.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php`

- **Change type:** Modified
- **Stable location(s):** display and _prepareDocument
- **What changed:** Enable native model exception mode before reads; register and activate ordered model CSS/JS through WAM with deterministic URI identities and site-root URL resolution.
- **Why:** Propagate model failures at the view boundary and preserve asset-list order, deduplication, external URLs and subdirectory installations.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_MODEL.php`

- **Change type:** Modified
- **Stable location(s):** $styles and $scripts defaults
- **What changed:** Point generated CSS/JavaScript lists at the matching administrator/site component media package.
- **Why:** Keep model-supplied assets aligned with settings destinations and prevent name collisions between clients.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php`

- **Change type:** Modified
- **Stable location(s):** display and _prepareDocument
- **What changed:** Enable native model exception mode before reads; register and activate ordered model CSS/JS through WAM with deterministic URI identities and site-root URL resolution.
- **Why:** Propagate model failures at the view boundary and preserve asset-list order, deduplication, external URLs and subdirectory installations.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable location(s):** $styles and $scripts defaults
- **What changed:** Point generated CSS/JavaScript lists at the matching administrator/site component media package.
- **Why:** Keep model-supplied assets aligned with settings destinations and prevent name collisions between clients.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/DASH_MODEL.php`

- **Change type:** Modified
- **Stable location(s):** $styles and $scripts defaults
- **What changed:** Point generated CSS/JavaScript lists at the matching administrator/site component media package.
- **Why:** Keep model-supplied assets aligned with settings destinations and prevent name collisions between clients.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/DASH_VIEW_HTML.php`

- **Change type:** Modified
- **Stable location(s):** display and _prepareDocument
- **What changed:** Enable native model exception mode before reads; register and activate ordered model CSS/JS through WAM with deterministic URI identities and site-root URL resolution.
- **Why:** Propagate model failures at the view boundary and preserve asset-list order, deduplication, external URLs and subdirectory installations.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/SITE_ADMIN_VIEW_HTML.php`

- **Change type:** Modified
- **Stable location(s):** display and _prepareDocument
- **What changed:** Enable native model exception mode before reads; register and activate ordered model CSS/JS through WAM with deterministic URI identities and site-root URL resolution.
- **Why:** Propagate model failures at the view boundary and preserve asset-list order, deduplication, external URLs and subdirectory installations.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable location(s):** $styles and $scripts defaults
- **What changed:** Point generated CSS/JavaScript lists at the matching administrator/site component media package.
- **Why:** Keep model-supplied assets aligned with settings destinations and prevent name collisions between clients.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/SITE_VIEWS_HTML.php`

- **Change type:** Modified
- **Stable location(s):** display and _prepareDocument
- **What changed:** Enable native model exception mode before reads; register and activate ordered model CSS/JS through WAM with deterministic URI identities and site-root URL resolution.
- **Why:** Propagate model failures at the view boundary and preserve asset-list order, deduplication, external URLs and subdirectory installations.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/SITE_VIEWS_MODEL.php`

- **Change type:** Modified
- **Stable location(s):** $styles and $scripts defaults
- **What changed:** Point generated CSS/JavaScript lists at the matching administrator/site component media package.
- **Why:** Keep model-supplied assets aligned with settings destinations and prevent name collisions between clients.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/SITE_VIEW_HTML.php`

- **Change type:** Modified
- **Stable location(s):** display and _prepareDocument
- **What changed:** Enable native model exception mode before reads; register and activate ordered model CSS/JS through WAM with deterministic URI identities and site-root URL resolution.
- **Why:** Propagate model failures at the view boundary and preserve asset-list order, deduplication, external URLs and subdirectory installations.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/SITE_VIEW_MODEL.php`

- **Change type:** Modified
- **Stable location(s):** $styles and $scripts defaults
- **What changed:** Point generated CSS/JavaScript lists at the matching administrator/site component media package.
- **Why:** Keep model-supplied assets aligned with settings destinations and prevent name collisions between clients.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/default_import.php`

- **Change type:** Modified
- **Stable location(s):** asset setup and inline import handlers
- **What changed:** Activate native WAM scripts and capture PHP-rendered handlers in a named WAM inline asset, cleaning buffers on failures.
- **Why:** Keep import action globals and DOM-ready spinner behavior while removing legacy asset entry points.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/default_import_custom.php`

- **Change type:** Modified
- **Stable location(s):** Asset setup before `IMPORT_DEFAULT_VIEW_CUSTOM`.
- **What changed:** Activate jquery, Bootstrap popover, core, keepalive and form validation through the document Web Asset Manager; preserve the custom body placeholder verbatim.
- **Why:** The active custom-import template must use the same native asset entry points as the built-in import screen.
- **Related paths/symbols:** `default_import.php` and the existing custom import body dispenser.

### `admin/compiler/joomla_6/default_toolbar.php`

- **Change type:** Modified
- **Stable location(s):** search reset button
- **What changed:** Replace document.id with document.getElementById.
- **Why:** Remove the MooTools DOM lookup dependency.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/settings.json`

- **Change type:** Modified
- **Stable location(s):** create and move.static/move.dynamic
- **What changed:** Move static, view and edit-form JavaScript/CSS to media/{js,css}/{admin,site}; register the conditional native chart helper template.
- **Why:** Keep every generated asset packaged once and separate administrator/site view basenames.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/submitbutton.js`

- **Change type:** Modified
- **Stable location(s):** Joomla.submitbutton validation failure
- **What changed:** Use Joomla.Text._ in the retained administrator submit-button template.
- **Why:** Remove the deprecated Joomla.JText alias without enabling an unused template.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

### `admin/compiler/joomla_6/submitbutton_site.js`

- **Change type:** Modified
- **Stable location(s):** Joomla.submitbutton validation failure
- **What changed:** Use Joomla.Text._ in the retained site submit-button template.
- **Why:** Remove the deprecated Joomla.JText alias without enabling an unused template.
- **Related paths/symbols:** `Compiler/Architecture/JoomlaSix`, `Compiler/Component/JoomlaSix/Settings`, and the existing Joomla Power injection pipeline.

## Compiler-owned custom submit handler follow-up

`Compiler/Architecture/CustomView/SubmitButtonScript.php` now exposes a protected rendering hook that retains the original Joomla 3/4/5 output byte for byte. `Compiler/Architecture/JoomlaSix/CustomView/SubmitButtonScript.php` registers the identical global handler as a named inline WAM asset with `core` as its dependency. `Service/ArchitectureView.php` selects the variant through the stable `Architecture.CustomView.SubmitButtonScript` alias and injects the compiler configuration only into the native variant.

The handler's existing back navigation and task/form submission source is unchanged. It performs no DOM lookup until a person invokes the handler, so registration in the document head is safe. The existing authored `Joomla.submitbutton = function(` override detection and empty-view behavior remain unchanged; custom markup is not rewritten.

`Architecture/JoomlaSix/SubmitButtonScriptTest.php` executes the generated fragment against a real Joomla document/WAM, verifies dependency ordering and repeated registration, resolves the provider for targets 3–6, compares the complete older handler output, and verifies override/source preservation. The focused run including existing `VersionedViewPiecesTest` passes **35 tests / 76 assertions**. A Node 24.19.0 smoke run of the actual generated WAM handler confirms no early DOM access, `demo.back` navigation with `false` return, and `demo.save` task assignment/form submission. PHP syntax, new-file style inspection, and scoped whitespace checks pass.

## Impact

- **Behavioral impact:** Generated view assets are activated in declared order through WAM, duplicates share one URI-based identity, and both local query URLs and external URLs resolve correctly. Native draggable rows retain the existing ordering task and token.
- **Visual impact:** Intended rendered layout and styling are unchanged; the ordering behavior now uses native draggable handling.
- **Accessibility impact:** Native Joomla ordering controls remain in use; no form label or input semantics change.
- **Compatibility impact:** Only target Joomla 6 settings and variants change. Joomla 3/4/5 templates retain their original behavior. Exception mode matches Joomla 6 core views; inherited controller save/recovery behavior remains intact.
- **Generated-output impact:** CSS/JavaScript files move into `media/com_<component>/{css,js}/{admin,site}` after installation. Administrator/site image directories remain supported. Configured model script/style arrays remain the public extension point.

## Verification

Integrated source, unit, installed API, browser and compiler comparison evidence is recorded in the [completion report](../audits/joomla6-native-completion.md).

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| Combined native asset, package/settings, and related renderer tests | PHP 8.3.6 / PHPUnit 12.5.33 | Pass — 69 tests, 1,559 assertions, including the conventional template inventory. |
| PHPUnit selector checks for `view javascript file`, `admin views list assembly`, and `admin views view body` | PHP 8.3.6 / PHPUnit 12.5.33 | Pass — 3 tests, 43 assertions. |
| PHP syntax checks for shared and Joomla 6 JavaScriptFile, ViewBody, ListView and ArchitectureView | PHP 8.3.6 | Pass. |
| `AssetsTemplateTest` and `Architecture/JoomlaSix/AssetsTest` (included above) | PHP 8.3.6 / actual Joomla 6 WAM | Pass — 25 tests, 362 assertions, including ordering/deduplication, installed media paths, URI resolution, both import stages, footer PHP/failure cleanup and native draggable metadata. |
| Custom-import WAM entry point and updated conventional template inventory (`AssetsTemplateTest` + `Component/JoomlaSix/TemplatesTest`) | PHP 8.3.6 / PHPUnit 12.5.33 | Pass — 21 tests, 1,270 assertions. Custom body remains in its original view scope. |
| Scoped PHP style inspection for all three new generators and their two owning test files; scoped `git diff --check` | Repository checker / git | Pass — no diagnostics. |

### Manual scenarios

No manual generated-component browser journey was run. Hosted generated-component installation and API checks are recorded in the completion report.

### GUI test coverage

- **Spec files added/updated:** None. These compiler sources change generated component screens, not an installed JCB administrator screen. Existing GUI specs exercise JCB itself and cannot validate a freshly generated component's sortable rows. Runtime generation/package tests cover the changed contracts; generated Joomla 6 install/browser smoke remains a separate integration check.

### Checks not performed

- Browser drag/drop in a generated component. Hosted API checks cover generated package installation, not drag/drop interactions.

## Risks, limitations, and rollback

- **Known risks:** Existing custom administrator/footer JavaScript is now registered with WAM and rendered in the document head. Arbitrary custom bodies retain their source/global scope; immediate DOM access must wait for DOMContentLoaded. A precise Joomla 6 compilation warning is emitted at the custom-footer source boundary. The compiler does not use eval, dynamic script injection, or rewrite arbitrary custom JavaScript.
- **Known limitations:** Standalone help-document responses construct their own complete HTML documents and keep their explicit asset tags; they do not participate in Joomla's page document WAM. Custom CSS/JavaScript authored against old component-relative URLs must adopt the generated media paths. Future reusable JavaScript authoring is outside this change.
- **Rollback:** Revert the affected template/settings changes and their Joomla 6 provider-selected generator variants together; rebuild the extension package. Older-target output needs no rollback.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_6/ADMIN_IMPORT_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/ADMIN_VIEWS.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/ADMIN_VIEWS_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/ADMIN_VIEWS_MODEL.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/ADMIN_VIEW_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_MODEL.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_MODEL.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/DASH_MODEL.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/DASH_VIEW_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/SITE_ADMIN_VIEW_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/SITE_VIEWS_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/SITE_VIEWS_MODEL.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/SITE_VIEW_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/SITE_VIEW_MODEL.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/default_import.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/default_import_custom.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/default_toolbar.php` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/settings.json` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/submitbutton.js` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |
| `admin/compiler/joomla_6/submitbutton_site.js` | Same maintained compiler template | No | Not applicable — owner confirmed | The maintainer explicitly identified `admin/compiler` as the authoritative template source in this task; review the generated Joomla 6 output. |

## Final consistency check

- [x] The affected paths and stable locations are listed explicitly.
- [x] Behavioral, visual, compatibility and generated-output impacts are explicit.
- [x] Skipped browser/install checks are stated without claiming a pass.
- [x] Every protected path has a source reconciliation entry.
- [x] All changes remain within the explicit Joomla 6 scope.
- [x] New native asset and related tests are recorded after completion.
- [x] Repository-wide validation evidence is linked in the completion report.
