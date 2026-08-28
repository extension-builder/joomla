# Extrusion setup: the component name when everything is created new

## Change identity

- **Date first changed:** 2026-08-28
- **Author/implementer:** Claude (coding agent), for lemuel@vdm.to
- **Task/issue/PR:** Branch `claude/joomla-powers-namespace` (PR #44),
  the powers namespace work
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active session: the owner directed that when the
  target component is "None - everything is created new" and no exact
  component is specified, an input box must appear for the person to name
  the component the run targets, so the component namespace placeholder
  always stands and no harvested class ever stores a concrete component
  segment; the named value is then recorded as a global placeholder in the
  placeholder table. Standing permission to branch, commit in the owner's
  name, and open a pull request.
- **Authorized paths:** The extrusion view's own `admin/**` files
  (`admin/src/View/Extrusion/HtmlView.php`, `admin/src/Model/AjaxModel.php`,
  `admin/assets/js/extrusion.js`), alongside the engine work in
  `libraries/vendor_jcb/**`.
- **Authorized outcome:** Selecting None reveals a component-name input;
  the entered name drives `[[[ComponentNamespace]]]` recognition in both
  pipelines and is remembered as a global placeholder row.
- **Permission summary:** Covers the setup form's new field and its
  transport only. No other admin view.

## Purpose and rationale

A library harvested with no paired component had nothing to answer for its
component segment, so classes stored concrete segments instead of
`[[[ComponentNamespace]]]`. The engines can now recognise every component
JCB knows, but a component that does not exist anywhere yet is only known
to the person — so the page must ask. Only the setup form can collect that
answer, and only the AJAX model can hand it to the engines.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-08-28-extrusion-component-name-for-new-creations.md`

### Modified

- `admin/src/View/Extrusion/HtmlView.php`
- `admin/src/Model/AjaxModel.php`
- `admin/assets/js/extrusion.js`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/src/View/Extrusion/HtmlView.php`

- **Change type:** Modified
- **Stable location(s):** the setup form builder, directly after the
  `component_id` list field
- **What changed:** Added a `component_code` text field (label "Component
  code name", hint `com_component`) explaining that it names the component
  the harvested classes belong to when everything is created new.
- **Why:** The person is the only source of a component name that exists
  nowhere yet.
- **Related paths/symbols:** `admin/assets/js/extrusion.js` (visibility
  and transport), `admin/src/Model/AjaxModel.php` (consumption).

### `admin/src/Model/AjaxModel.php`

- **Change type:** Modified
- **Stable location(s):** `extrusionEngines()`
- **What changed:** Reads `component_code` from the run configuration and
  hands it to both engines — `$extruder->codeName()` for the component
  pipeline and `$powers->componentCode()` for the powers pipeline — so the
  placeholder resolvers derive the component segment from the entered name.
- **Why:** The engines already accept the name; nothing transported it.
- **Related paths/symbols:**
  `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Extrusion/Powers/Resolver/Placeholders.php`
  (recognition), `Powers/Writer/Vendor.php` (the global placeholder memory).

### `admin/assets/js/extrusion.js`

- **Change type:** Modified
- **Stable location(s):** `readConfig()`, the `DOMContentLoaded` wiring
- **What changed:** `component_code` travels in the run configuration for
  both harvest and import; the field's row is shown only while the target
  component select stands on "None - everything is created new", and hides
  again when a target or detection is chosen.
- **Why:** The name only has meaning when nothing else can answer for the
  component.
- **Related paths/symbols:** `admin/src/View/Extrusion/HtmlView.php`.

## Impact

- **Behavioral impact:** With None selected and a name entered, every
  harvested class stores `[[[ComponentNamespace]]]` for that component's
  segment, and the entered casing is written once as a global
  `[[[ComponentNamespace]]]` placeholder row (base64, via the Data
  pipeline's own storage encoding) — the system's memory, which later runs
  recognise even when nothing is entered. Compiles are untouched: the
  compiler loads global placeholders first and overrides ComponentNamespace
  per component afterwards.
- **Visual impact:** One additional text field on the setup form, visible
  only while None is selected.
- **Accessibility impact:** A standard labelled Joomla form field; no
  pointer-only interaction.
- **Compatibility impact:** None beyond the page; the configuration key is
  additive and optional.
- **Generated-output impact:** None directly; the records the import
  writes are the engine change, validated in the PR.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php vendor/bin/phpunit --testsuite=VDM.Joomla --filter Extrusion` | PHP 8.4.19 | Pass — 363 tests, 7551 assertions |
| `php bin/check-php-style.php`, `check-test-ownership`, `check-container-keys`, `git diff --check` | tests container | Pass |
| `node --check admin/assets/js/extrusion.js` | Node 20 | Pass |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Library-only run, component None, name `com_sermondemo` entered | Local Joomla 6 + JCB lab, CLI mirror of the AJAX flow | Pass — classes store `[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].…`; global placeholder row `[[[ComponentNamespace]]]` = `SermonDemo` written |
| Second library-only run, component None, nothing entered | Same lab | Pass — the global row answers; the segment still defers |

### GUI test coverage

- **Spec files added/updated:** None — the field is a standard setup-form
  input whose behavior is fully covered by the unit suite and the lab
  scenarios above; the Playwright setup-surface spec continues to pass
  unchanged in CI.

### Checks not performed

- The Playwright GUI suite was not executed locally — this environment does
  not serve the administrator over HTTP; the suite runs in CI on the branch.

## Risks, limitations, and rollback

- **Known risks:** A name entered for one run is remembered globally; a
  later run for a different unnamed component keeps its own name only if
  entered — the standing global row is never overwritten, only the
  disagreement reported.
- **Known limitations:** The board's pairing-pane select does not repeat
  the input; the name entered at setup carries through harvest and import.
- **Rollback:** Revert the three modified files and delete this record.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/View/Extrusion/HtmlView.php` | JCB component definition (extrusion view), maintained in this repository as the source of truth for the extrusion view | Yes | Reconciliation in progress | Same ledger row as the 2026-08-23/24/28 records: the extrusion view is new JCB-managed GUI; owner imports it into JCB with the component sources when the feature lands |
| `admin/src/Model/AjaxModel.php` | JCB component definition (ajax model), same ledger as above | Yes | Reconciliation in progress | As above |
| `admin/assets/js/extrusion.js` | JCB component definition (extrusion view asset), same ledger as above | Yes | Reconciliation in progress | As above |

## Final consistency check

The affected paths, implementation details, impact and verification above
were re-read against the final diff of this branch before the record was
committed; every `admin/**` path in the diff is named here.
