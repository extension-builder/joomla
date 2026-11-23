# Joomla 6 view document asset consolidation

## Change identity

- **Date first changed:** 2026-11-23
- **Author/implementer:** Codex, at the maintainer's request
- **Task/issue/PR:** https://github.com/extension-builder/joomla/pull/72
- **Change record status:** Draft

## Explicit permission

- **Permission reference:** The maintainer's active 23 November 2026 request
  approves the preceding investigated implementation plan and directs creation
  of a branch and PR, incremental pushes, green workflows, and review readiness
  once the work is complete.
- **Authorized paths:** The four Joomla 6 custom-admin/site HTML templates
  listed below, for the described asset-loading change.
- **Authorized outcome:** One template-owned manager binding and root lookup
  for the in-scope view asset block, preserving other hooks and consumers.
- **Permission summary:** Implement the approved compiler/template plan and
  document the separate authoritative-definition and self-build prerequisites.
  This does not authorize hand-editing generated Compiler output or unrelated
  administrator/media files.

## Purpose and rationale

Generated custom-admin and site JavaScript files currently enter through the
library loader, separately from the template's model asset loops. The template
must own the combined style/script phase to provide one manager binding and
ordered registration. A library-only change cannot move the template slots or
keep inline CSS between the style and script phases.

## Affected paths

### Created

- None

### Modified

- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php`
- `admin/compiler/joomla_6/SITE_VIEW_HTML.php`
- `admin/compiler/joomla_6/SITE_VIEWS_HTML.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php`

- **Change type:** Modified
- **Stable location(s):** `HtmlView::_prepareDocument()`.
- **What changed:** Bind the manager/root before loaders, preserve dependency
  seeds, and place the optional view file and view-local paths before model
  scripts in the guarded registration loop.
- **Why:** Unify custom-admin single-view assets without moving authored hooks.
- **Related paths/symbols:** `Architecture/View/PrepareDocument`,
  `Architecture/JoomlaSix/View/JavaScriptFile`,
  `Architecture/JoomlaSix/View/LibrariesLoader`.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php`

- **Change type:** Modified
- **Stable location(s):** `HtmlView::_prepareDocument()`.
- **What changed:** Apply the same custom-admin block to the list template.
- **Why:** Single/list custom-admin generation must share the asset contract.
- **Related paths/symbols:** The same compiler emitters as the single template.

### `admin/compiler/joomla_6/SITE_VIEW_HTML.php`

- **Change type:** Modified
- **Stable location(s):** `HtmlView::_prepareDocument()`.
- **What changed:** Apply the block while retaining site metadata, authored PHP,
  inline CSS/JS, and site-button hooks in their existing relative order.
- **Why:** Site single views use the same view-file generator.
- **Related paths/symbols:** The same compiler emitters with the `SITE` target.

### `admin/compiler/joomla_6/SITE_VIEWS_HTML.php`

- **Change type:** Modified
- **Stable location(s):** `HtmlView::_prepareDocument()`.
- **What changed:** Apply the site block to the list template.
- **Why:** Site list views must not retain the old split view-file path.
- **Related paths/symbols:** The same compiler emitters with the `SITE` target.

## Impact

- **Behavioral impact:** Optional view files and supplied view-local paths join
  the model asset dependency chain after styles. Required core dependencies
  survive an already-initialized dependency array.
- **Visual impact:** No intended layout change. Asset ordering is an intentional
  generated-output change, covered by the generated browser contract.
- **Accessibility impact:** No controls, labels, or interaction markup changed.
- **Compatibility impact:** Joomla 6 generated custom-admin/site views only;
  Joomla 3–5 and ordinary admin list/edit/module emission remain unchanged.
- **Generated-output impact:** New dedicated script-entry slots, one in-scope
  manager/root binding, multiline guarded registration, and cumulative unique
  dependency arrays. Existing inline JS placeholders and receivers are intact.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| Focused generation and real WAM contracts | PHP 8.4.28 / real Joomla WAM | Passed: template, emitter, placement and target-selection tests |
| Unit/style/ownership gates | Hosted PHP matrix | Pending |
| Joomla 3–6 compiler golden comparisons | Hosted installed Joomla | Pending |
| Installed GUI/API workflows | Hosted disposable site | Pending |

### GUI test coverage

- `libraries/vendor_jcb/tests/javascript/view-assets-browser.cjs` executes all
  four generated templates, automatic and explicit jQuery dependencies,
  optional assets, duplicate URLs, subdirectory paths, styles and inline code.
- `libraries/vendor_jcb/tests/gui/specs/compiler.spec.js` remains the installed
  Compiler interface owner. Its component-details flow is verified separately
  from the generated-template contract.

### Verification scope

The generated fixture uses the compiler's actual document preparation and
Joomla renderers. The installed GUI suite loads the repository distribution.
Neither is represented as a JCB self-build from a maintained definition export.

## Risks, limitations, and rollback

- **Known risks:** WAM order depends on the registered dependency graph. An
  existing registration must retain its URI/options/dependencies; this change
  cannot silently repair a conflicting external registration.
- **Source scope:** The maintained Compiler definition and the checked-in
  generated Compiler view are outside this diff; reconciliation is tracked
  below.
- **Rollback:** Revert this PR's compiler/template/test commits together. Do not
  retain a template placeholder without its matching producer.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php` | Maintainer's JCB template maintenance source | Yes | Pending source identification | Maintainer supplies current source package; transfer this template change and verify regeneration |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php` | Maintainer's JCB template maintenance source | Yes | Pending source identification | Maintainer supplies current source package; transfer this template change and verify regeneration |
| `admin/compiler/joomla_6/SITE_VIEW_HTML.php` | Maintainer's JCB template maintenance source | Yes | Pending source identification | Maintainer supplies current source package; transfer this template change and verify regeneration |
| `admin/compiler/joomla_6/SITE_VIEWS_HTML.php` | Maintainer's JCB template maintenance source | Yes | Pending source identification | Maintainer supplies current source package; transfer this template change and verify regeneration |

## Final consistency check

- [x] The affected-path lists match the protected-path diff exactly.
- [x] Every path has a stable location and exact what/why details.
- [x] Behavioral and visual impact are explicit.
- [ ] Verification records actual results and identifies skipped checks.
- [x] Every path has an authoritative-source mapping and reconciliation status.
- [x] Transfer is required for every unresolved source mapping.
- [x] Changed behavior has generated-browser coverage and a named GUI owner.
- [x] The implementation remains within the cited permission.
