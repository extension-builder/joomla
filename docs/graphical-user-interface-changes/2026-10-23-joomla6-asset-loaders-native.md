# Joomla 6 native asset loaders

## Change identity

- **Date first changed:** 2026-10-23
- **Author/implementer:** Codex, asset loader implementation
- **Task/issue/PR:** Joomla 6 deprecation completion following PR #59
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active user instruction to implement all deprecation areas for Joomla 6, specifically Footable, Google Charts, WAM registration/activation/inline assets and extension media placement.
- **Authorized paths:** `admin/compiler/joomla_6/ADMIN_HEADERCHECK.php`, `admin/compiler/joomla_6/SITE_HEADERCHECK.php`, `admin/compiler/joomla_6/ADMIN_CHARTBUILDER.php`.
- **Authorized outcome:** Generated Joomla 6 loaders use native assets without changing Joomla 3–5 output.
- **Permission summary:** Update the existing compiler template set and target-selected renderers, preserving JCB Power resolution.

## Purpose and rationale

The header checker only inspected legacy document script arrays, so it could not recognize assets registered through WAM. The historical chart helper emitted deprecated Google loader calls and raw script tags. These template sources determine generated helper behavior and cannot be replaced solely through compiler service wiring.

## Affected paths

### Created

- `admin/compiler/joomla_6/ADMIN_CHARTBUILDER.php`

### Modified

- `admin/compiler/joomla_6/ADMIN_HEADERCHECK.php`
- `admin/compiler/joomla_6/SITE_HEADERCHECK.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/compiler/joomla_6/ADMIN_HEADERCHECK.php`

- **Change type:** Modified
- **Stable location(s):** `HeaderCheck::isLoaded`
- **What changed:** Inspect active WAM asset names and URIs before legacy document arrays.
- **Why:** Prevent duplicate loading when assets use native registration.
- **Related paths/symbols:** `Architecture/JoomlaSix/View/LibrariesLoader`.

### `admin/compiler/joomla_6/SITE_HEADERCHECK.php`

- **Change type:** Modified
- **Stable location(s):** `HeaderCheck::isLoaded`
- **What changed:** Same native asset detection on the site side.
- **Why:** Site library detection requires the same active-asset semantics.
- **Related paths/symbols:** `Architecture/JoomlaSix/View/LibrariesLoader`.

### `admin/compiler/joomla_6/ADMIN_CHARTBUILDER.php`

- **Change type:** Created
- **Stable location(s):** `Chartbuilder::load`, `Chartbuilder::draw`
- **What changed:** Preserve the historical constructor/load/draw API, register the official Google Charts loader, submit chart initialization through WAM, and use `google.charts.load`/`setOnLoadCallback` with native resize/tab events.
- **Why:** Loading the modern external library alone cannot execute historical `google.load` code.
- **Related paths/symbols:** `Architecture/JoomlaSix/View/GoogleChartLoader`, Joomla 6 component settings.

## Impact

- **Behavioral impact:** Active WAM dependencies count as loaded; charts initialize after their loader and DOM are ready.
- **Visual impact:** Existing chart options and Footable styles remain selected by project definitions.
- **Accessibility impact:** No new controls or authoring UI.
- **Compatibility impact:** Joomla 6 target only; earlier target renderers remain unchanged.
- **Generated-output impact:** Native WAM output replaces legacy loader calls and raw generated asset tags.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| PHPUnit filter: JoomlaSixAssetLoadersTest, AdminViewScriptTest, VersionedFootableScriptsRendererTest, VersionedModulePluginRendererTest, InputButtonTest, Library tests, VersionedStructureTest | PHP 8.3.6, Joomla 6.1.2 native runtime | Pass — 143 tests, 731 assertions. |
| Generated chart JavaScript executed with DOM and Google Charts test doubles | Node 24.19.0 | Pass — DOM-ready gating, current/corechart loading, data, responsive dimensions, resize/tab/icon redraw. |
| git diff --check | Working branch | Pass. |

### Manual scenarios

No installed component/browser scenario run yet.

### GUI test coverage

- **Spec files added/updated:** None. Changes are compiler source templates, not JCB administrator GUI controls; generated PHP and asset graph contracts cover the output. Full rendered chart behavior still requires an installed extension and network access to Google.

### Checks not performed

- Installed Joomla browser test, since this workspace provides compiler test fixtures rather than a deployed example component.

## Risks, limitations, and rollback

- **Known risks:** Custom user-supplied scripts may require their own DOM-ready handling and explicit library dependencies.
- **Known limitations:** Custom chart types outside Google's corechart package must register/load their additional package in custom code, as before.
- **Rollback:** Revert the target-specific compiler/template changes and associated settings entries together.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_6/ADMIN_HEADERCHECK.php` | Joomla 6 compiler template set | Yes | Source identified | Repository compiler template is the maintained source; owner reviews generated runtime contract. |
| `admin/compiler/joomla_6/SITE_HEADERCHECK.php` | Joomla 6 compiler template set | Yes | Source identified | Repository compiler template is the maintained source; owner reviews generated runtime contract. |
| `admin/compiler/joomla_6/ADMIN_CHARTBUILDER.php` | Joomla 6 compiler template set, preserving historical chart helper API | Yes | Source identified | Historical API verified against joomengine/Joomla-Component-Builder commit cbbbd54c815ef84e4638c77ab978759c75c28c61 admin/custom/chartbuilder.php; owner reviews new native template mapping. |

## Final consistency check

- [x] The affected-path lists match the intended protected-path changes.
- [x] Every path has a stable location and exact what/why details.
- [x] Behavioral and visual impact are explicit.
- [x] Verification records actual results and identifies skipped checks.
- [x] Every path has an authoritative-source mapping and reconciliation status.
- [x] Transfer required remains Yes pending owner reconciliation.
- [x] GUI coverage omission is explained.
- [x] The implementation remains within the cited permission.
