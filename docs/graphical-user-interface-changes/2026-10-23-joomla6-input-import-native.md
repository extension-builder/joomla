# Joomla 6 controller input and import services

## Change identity

- Date first changed: 2026-10-23
- Implementer: Codex
- Task: Joomla 6 native deprecation completion, branch `deprecation/joomla6-native-completion`.
- Status: Ready for review

## Explicit permission

- Permission reference: the current maintainer request authorizes all eight deprecation areas for Joomla 6 only and explicitly identifies `admin/compiler` as the authoritative template location.
- Authorized paths: the three Joomla 6 compiler templates listed below.
- Authorized outcome: use native Joomla 6 input and cache services while retaining existing import/export behavior and Joomla Power processing.
- Scope: Joomla 3, 4, and 5 templates and emitted behavior remain unchanged.

## Purpose and rationale

Generated import controllers still create their menu cache through deprecated `Factory::getCache()`. The standard import model still calls deprecated `jimport()` even though its generated header already imports `Joomla\Filesystem\File`. These are template bodies, so changing library renderers alone cannot correct them. Export and custom admin button input selection is changed separately through target-selected library renderers.

## Affected paths

### Created

- None

### Modified

- `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER.php`
- `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER_CUSTOM.php`
- `admin/compiler/joomla_6/ADMIN_IMPORT_MODEL.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER.php`

- Change type: Modified.
- Stable location: `ImportController::import()`, successful import cache cleanup.
- Change: resolve `CacheControllerFactoryInterface` from the container through the existing Factory Joomla Power, create the `callback` controller with `defaultgroup => mod_menu`, and retain `clean()`.
- Reason: replace the deprecated cache factory while keeping the same cache group and success-only cleanup.
- Related source: `admin/compiler/joomla_6/settings.json`, `move.dynamic` import mappings.

### `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER_CUSTOM.php`

- Change type: Modified.
- Stable location: `###View###importController::import()`, successful import cache cleanup.
- Change and reason: the same native factory migration, preserving the custom model name and redirect behavior.
- Related source: `admin/compiler/joomla_6/settings.json`, `move.dynamic` custom import mappings.

### `admin/compiler/joomla_6/ADMIN_IMPORT_MODEL.php`

- Change type: Modified.
- Stable location: `ImportModel::remove()`.
- Change: remove the obsolete `jimport('joomla.filesystem.file')` call.
- Reason: Joomla 6 resolves the already imported `File` class through its autoloader.
- Related source: `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/JoomlaSix/Header.php`, import model header.

## Impact

- Behavioral impact: successful imports still clear the menu cache, failed imports do not; uploaded spreadsheet cleanup remains identical.
- Visual impact: none; this changes generated controller/model execution, not markup.
- Accessibility impact: none; no controls, labels, focus behavior, or rendered markup change.
- Compatibility impact: Joomla 6 compile target only; existing import mappings remain enabled.
- Generated-output impact: two native cache service expressions replace deprecated factory calls; one redundant loader invocation disappears. Existing Joomla Power identities remain present.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php -d memory_limit=1G vendor/bin/phpunit -c phpunit.xml.dist --filter 'NativeInputImportTest\|VersionedControllerEximportMethodTest\|VersionedViewGuardsTest' --no-coverage` | PHP 8.3.6, test harness with Joomla 6.1.2 | Pass: 45 tests, 151 assertions. Native POST selection and successful/failed import cache cleanup execute generated code; Factory Power is resolved through the real injector. |
| Compare generated EximportMethod and CustomAdminDynamicButton SHA-256 output for Joomla 3/4/5 against detached base `faa95cdeecdbdb4504f27d66870310aec1a3ec0f` | Same local PHP runtime and renderer fixtures | Pass: all six generated-output hashes match the base exactly. |
| `git diff --check` restricted to the input/import changed sources | Local worktree | Pass. |

### Manual scenarios

None performed locally; this environment has no Docker or installed database-backed Joomla application.

### GUI test coverage

- Spec files added/updated: None. These compiler input changes do not alter the JCB administrator interface. Generated controller behavior is covered by native PHP execution contracts; hosted generated-extension installation remains the integration gate.

### Checks not performed

- Hosted golden comparison and installed extension checks are pending the completed branch.

## Risks, limitations, and rollback

- Known risks: custom user import scripts retain their own behavior and are outside these three template edits.
- Known limitations: unit tests do not replace a generated extension installation.
- Rollback: revert the three template edits and the matching target-selected renderer/provider changes together; retain all settings mappings.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER.php` | Same path, maintained compiler source | No | Not applicable — owner confirmed | Maintainer explicitly identified `admin/compiler` as the actual template location in this task; review the resulting generated PHP. |
| `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER_CUSTOM.php` | Same path, maintained compiler source | No | Not applicable — owner confirmed | Same explicit maintainer decision; review the custom-import generated PHP. |
| `admin/compiler/joomla_6/ADMIN_IMPORT_MODEL.php` | Same path, maintained compiler source | No | Not applicable — owner confirmed | Same explicit maintainer decision; verify existing generated File import and cleanup behavior. |

## Final consistency check

- [x] Final diff matches these three protected paths.
- [x] Verification records actual results and unperformed checks.
- [x] No import template or settings link is removed.
- [x] Joomla Power resolution remains part of the compiler pipeline.
- [x] Source reconciliation and permission refer to the maintainer's explicit template-location decision.
