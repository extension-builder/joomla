# Administrator layouts folder manifest

## Change identity

- **Date first changed:** 2026-08-19
- **Author/implementer:** Claude (coding agent), for llewellyn@vdm.io
- **Task/issue/PR:** Branch `claude/core-improvements-9qnl3o`
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active session request. The repository owner was
  asked whether to grant `admin/**` permission for the compiler templates and
  answered "Yes — templates + change record", scoped in that answer to "the
  identified vulnerabilities only", then asked to "keep finishing this".
- **Authorized paths:** `admin/compiler/joomla_4/settings.json`.
- **Authorized outcome:** Correct the identified directory-listing exposure by
  restoring the administrator `layouts` folder to the folder-creation manifest.
- **Permission summary:** Permission covers the compiler input for one
  identified finding. It does not extend to the generated administrator
  application, to `media/**`, or to any other `admin/**` subtree.

## Purpose and rationale

`Component\Structure::folders()` walks the `create` tree in this settings file
and calls the compiler's own `Utilities\Folder::create()`, which seeds every
directory it makes with an `index.html`. Any directory not declared there is
created implicitly later, without that file, and is left listable on a server
with directory indexes enabled.

`create.admin` declares assets, forms, language, presets, services, sql, src
and tmpl, but not `layouts`. `create.site` in the same file does declare it,
and `admin/compiler/joomla_3/settings.json` declares it under both admin and
site, so this is a Joomla 4 only regression rather than a design decision.

The accompanying in-scope change is in
`Compiler/Component/Structuresingle.php`, which was calling Joomla's raw
`Folder::create()` for dynamically placed files and so had the same gap for
any directory it created. No compiler class can add the missing manifest key,
because the manifest is this data file.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-08-19-admin-layouts-folder-manifest.md`

### Modified

- `admin/compiler/joomla_4/settings.json`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/compiler/joomla_4/settings.json`

- **Change type:** Modified
- **Stable location(s):** The `create.admin` object.
- **What changed:** Added one key, `"layouts": "layouts",`, between `language`
  and `presets`, matching the key ordering and tab indentation already used in
  the object and the identical key already present in `create.site`.
- **Why:** Without the key the administrator `layouts` directory is never
  created through the compiler's folder utility, so it ships with no
  `index.html` and its file names are listable where the server has directory
  indexes enabled.
- **Related paths/symbols:**
  `VDM\Joomla\Componentbuilder\Compiler\Component\Structure::folders()` reads
  this tree; `VDM\Joomla\Componentbuilder\Compiler\Utilities\Folder::create()`
  is what adds the `index.html`; `admin/compiler/joomla_3/settings.json`
  already carries the same key.

## Impact

- **Behavioral impact:** One additional directory is created through the
  compiler's folder utility during a Joomla 4, 5 or 6 build, and one additional
  `index.html` is written into it. The folder and file counters therefore each
  increase by one for a build that did not already create that directory.
- **Visual impact:** None. No administrator screen changes; only a static
  placeholder file is added to a shipped directory.
- **Accessibility impact:** None. No user interface is involved.
- **Compatibility impact:** Joomla 4, 5 and 6 targets, which all build from
  `admin/compiler/joomla_4`. Joomla 3 is unaffected because its manifest
  already declares the key.
- **Generated-output impact:** Changed. A rebuilt component gains
  `admin/layouts/index.html`. No existing generated file changes content.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php bin/check-php-style.php --base=<merge-base>` | PHP 8.4.19, from `libraries/vendor_jcb/tests` | Pass — style valid |
| `php bin/check-test-ownership.php --base=<merge-base>` | PHP 8.4.19 | Pass — 1322 production files, 0 baseline entries |
| `php bin/check-container-keys.php` | PHP 8.4.19 | Pass — every requested container key registered |
| `composer test` | PHP 8.4.19, Joomla CMS 6.1.2, PHPUnit 12.5.33 | Pass for this change — the only failure is `FieldCreatorTest::testFieldAsStringNormalizesBothRendererBackends`, which reproduces identically on a clean `6.x` tree (libxml self-closing-tag spacing) |
| JSON parse of the modified manifest | PHP 8.4.19 / Python 3 | Pass — parses, and `create.admin` now lists assets, forms, language, layouts, presets, services, sql, src, tmpl |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Build a single static file into a directory the compiler has to create, and confirm the directory is seeded | PHPUnit fixture in `StructureTest::testSingleStructureCopiesAndRegistersTemplateFile` | Pass — `index.html` is present in the created directory and the file counter reflects it |

### Checks not performed

- Compiling a component end to end against a live Joomla installation and
  inspecting the installed `administrator/components/com_*/layouts` directory.
  No Joomla installation with a database is available in this environment, so
  the manifest was verified by parsing it and by exercising the folder
  utility's behaviour in the test suite.

## Risks, limitations, and rollback

- **Known risks:** None identified. The added key only causes a directory that
  is already part of every build to be created earlier and seeded.
- **Known limitations:** This addresses the administrator `layouts` directory.
  Directories created by paths that still bypass the compiler's folder utility
  are covered by the accompanying `Structuresingle.php` change, not by this
  manifest.
- **Rollback:** Remove the `"layouts": "layouts",` line from `create.admin` in
  `admin/compiler/joomla_4/settings.json`. The accompanying in-scope change is
  independent and does not need to be reverted with it.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_4/settings.json` | Itself | No | Not applicable — owner confirmed | Compiler input data, not generated output. `Compiler\Utilities\Paths::setTemplatePath()` resolves the template root to `admin/compiler/joomla_<folder_key>`, and `Component\Structure::folders()` reads this file at build time, so this file is the authoritative manifest. Owner granted the change in this session. No next action. |

## Final consistency check

- [x] The affected-path lists match the final diff exactly.
- [x] Every path has a stable location and exact what/why details.
- [x] Behavioral and visual impact are explicit.
- [x] Verification records actual results and identifies skipped checks.
- [x] Every path has an authoritative-source mapping and reconciliation status.
- [x] `Transfer required` is `No` only for an owner-confirmed `Not applicable`
  path; it is `Yes` for every other status.
- [x] The implementation remains within the cited permission.
