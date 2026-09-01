# Component manifest installs the api folder

## Change identity

- **Date first changed:** 2026-09-01
- **Author/implementer:** Claude Code (agent), for Llewellyn van der Merwe
- **Task/issue/PR:** branch `claude/jcb-api-endpoint-generation-z0qcoe`,
  "complete the generated API endpoints for admin views", second step
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** the task request in this session: "To
  eventually link those to an installer so that it would also be installed
  is definitely the second step", after "if we can get this implemented".
- **Authorized paths:** `admin/compiler/joomla_4/component.xml`
- **Authorized outcome:** the generated component manifest tells the Joomla
  installer to copy the `api/` folder when the component has an API.
- **Permission summary:** one placeholder added to the manifest template;
  nothing else under `admin/**` or `media/js/**` is touched.

## Purpose and rationale

Joomla's component installer copies API files only from the manifest's
`<api><files folder="api">…</files></api>` element, and registers the
component's `Api` namespace only once `api/components/com_<name>/src`
exists. The generated manifest never had that element, so the API classes
the compiler now completes would never be installed. The block must vary
with the build (it is empty when no admin view asked for an API, because the
compiler removes the `api/` folder then), so it is a placeholder rendered by
`Compiler\Architecture\Component\Details`, which already writes the other
manifest placeholders.

## Affected paths

### Created

- None

### Modified

- `admin/compiler/joomla_4/component.xml`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/compiler/joomla_4/component.xml`

- **Change type:** Modified
- **Stable location(s):** the line closing `</administration>`, before
  `###UPDATESERVER###`
- **What changed:** `###API_FILES###` is inserted between
  `</administration>` and `###UPDATESERVER###`.
- **Why:** `Details::set()` renders it as the `<api>` files block when
  `Config->add_api` is set and as an empty string otherwise, so a component
  without an API keeps a byte-identical manifest.
- **Related paths/symbols:**
  `Compiler\Architecture\Component\Details::apiFiles()`,
  `Compiler::cleanupApiFolderIfRequired()`.

## Impact

- **Behavioral impact:** a component with an API now installs its `api/`
  folder into `api/components/com_<name>/`; a component without one is
  unchanged.
- **Visual impact:** None; a compiler template, not an interface file.
- **Accessibility impact:** None; no interface change.
- **Compatibility impact:** the `<api>` element is read by the installer of
  Joomla 4, 5 and 6; Joomla 3 never builds an API and the placeholder renders
  empty for it.
- **Generated-output impact:** the manifest of a component with an API gains
  the `<api>` block; every other generated file is unchanged.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `composer test` in `libraries/vendor_jcb/tests` | PHP 8.4.19, PHPUnit 12.5, Joomla 6.1.2 checkout | See the pull request description for the recorded run; `DetailsTest` asserts the block and its absence. |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Install a compiled component with an API and confirm `api/components/com_<name>/src` exists | not available in this session (no Joomla runtime with a database) | Not performed; see "Checks not performed". |

### GUI test coverage

- **Spec files added/updated:** None. The template is a compiler input with
  no browser-facing behaviour; the change is covered by the `DetailsTest`
  assertions on the rendered placeholder.

### Checks not performed

- An install of a compiled component against a Joomla installation; no
  Joomla runtime with a database is available in this session.

## Risks, limitations, and rollback

- **Known risks:** None identified; the placeholder is empty unless a view
  asked for an API.
- **Known limitations:** installing the folder does not register routes;
  that remains the linked `webservices` plugin's task.
- **Rollback:** remove `###API_FILES###` from the template line; the
  placeholder `Details` writes is then unused, which is harmless.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_4/component.xml` | Itself | No | Not applicable — owner confirmed | Compiler template input, not generated output: `Compiler\Utilities\Structure` copies it from `Paths::template_path`. The owner named the manifest step as the second step of this task. No next action. |

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
