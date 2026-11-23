# Joomla 6 view document assets

- **Date / implementer:** 2026-11-23; Codex at the maintainer's request.
- **PR:** https://github.com/extension-builder/joomla/pull/72
- **Permission:** The active request authorizes the four templates below and
  their compiler changes; the follow-up requests a smaller implementation.
- **Purpose:** Put generated view files and model assets in one ordered WAM
  block. Template placement is required to keep inline CSS after styles.

## Protected paths and implementation

All modified locations are `HtmlView::_prepareDocument()`. No protected files
are created, moved, renamed, or deleted.

| Modified path | Change and reason |
| --- | --- |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php` | Bind WAM/root before loaders; collect view file, extra paths and model scripts in order for single custom-admin views. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php` | Apply the same binding, dependency and script-list contract to custom-admin lists. |
| `admin/compiler/joomla_6/SITE_VIEW_HTML.php` | Apply the block to single site views while retaining metadata, authored PHP, inline assets and site-button hooks. |
| `admin/compiler/joomla_6/SITE_VIEWS_HTML.php` | Apply the same contract and preserved hooks to site lists. |

The existing `Architecture.View.JavaScriptFile` service owns file generation
and document placement. Its shared `set()` retains Joomla 3–5's library-loader
append; its existing Joomla 6 variant fills the template script slots.
`PrepareDocument` retains its original injected service and call position.
No new production class or DI registration is introduced.

## Optional view inputs

The existing `jcb_ce_onBeforeBuildCustomAdminViewContent` and
`jcb_ce_onBeforeBuildSiteViewContent` hooks may supply these settings properties:

| Property | Contract |
| --- | --- |
| `view_script_paths` | Ordered nonempty strings inserted after the generated file and before model scripts. |
| `view_script_dependencies` | Ordered WAM names merged with `core` and existing script seeds. |

Both default to empty arrays. Entries undergo placeholder expansion and PHP
literal escaping; invalid lists are rejected before file generation. Slots are
replaced for each view. These inputs add no database columns or GUI fields.
An explicit `jquery` requirement loads jQuery with automatic loading disabled;
`jquery-noconflict` remains controlled by the existing automatic-loading setting.

## Impact and verification

- **Behavior:** One WAM/root binding per block; styles, inline CSS, then ordered
  scripts. Existing registrations, normalized hashes, URLs and version options
  are preserved. The original inline JavaScript receiver and body remain intact.
- **Compatibility:** Joomla 6 site/custom-admin output changes. Joomla 3–5 and
  ordinary admin list/edit/module behavior remain unchanged.
- **Visual/accessibility:** No controls, labels, layout or interaction markup change.
- **PHP checks:** Full vendor suite; changed-file style, ownership and container
  guards; exact output, validation and independent-view assertions.
- **Browser coverage:** `tests/javascript/view-assets-browser.cjs` executes
  generated assets; `tests/gui/specs/compiler.spec.js` verifies the installed
  Compiler's real details request, token, target/events, DOM and Marked loading.
  Both paths are relative to `libraries/vendor_jcb/`.
- **Compiler comparisons:** Joomla 3–5 output equality and the exact reviewed
  Joomla 6 delta in `.github/golden-master/expected/README.md`; installed probes
  run at 0, 100 and 1,000 records. Current results: [PR checks](https://github.com/extension-builder/joomla/pull/72/checks).
- **Source scope:** Generated fixtures and the installed repository are tested;
  the maintained Compiler definition export and a JCB self-build are outside
  this diff.
- **Rollback:** Revert the paired template/emitter changes together. Retain
  external registrations unchanged even when their dependency graph differs.

## Authoritative source reconciliation

| Repository path | Source identity | Transfer required | Status / next action |
| --- | --- | --- | --- |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php` | Maintainer's template source | Yes | Pending source identification; maintainer maps this change to the current export and verifies regeneration. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php` | Maintainer's template source | Yes | Pending source identification; maintainer maps this change to the current export and verifies regeneration. |
| `admin/compiler/joomla_6/SITE_VIEW_HTML.php` | Maintainer's template source | Yes | Pending source identification; maintainer maps this change to the current export and verifies regeneration. |
| `admin/compiler/joomla_6/SITE_VIEWS_HTML.php` | Maintainer's template source | Yes | Pending source identification; maintainer maps this change to the current export and verifies regeneration. |
