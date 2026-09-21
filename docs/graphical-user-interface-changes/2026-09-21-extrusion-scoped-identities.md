# Extrusion: scoped Power identities and reviewed write plans

## Authorization and purpose

The repository owner explicitly requested implementation of the attached
`JCB_Extrusion_Power_Fixes_Agent_Brief.md`, including its named administrator
paths, on `fix/extrusion-scoped-power-identity`, and continuation in PR #53.
The library resolver's source keys, target metadata and preflight status cannot
be represented safely by the old boolean `exists` interface. This record covers
only those requested integration seams, not a general administrator refactor.

## Affected protected paths

Created: none. Deleted: none. Moved or renamed: none.

Modified:
- `admin/src/Model/AjaxModel.php`
- `admin/assets/js/extrusion.js`
- `admin/tmpl/extrusion/default.php`

## Implementation details

### `admin/src/Model/AjaxModel.php`

Modified `extrusionHarvest`, `extrusionImport`, `extrusionEngines`,
`extrusionWeigh`, `extrusionDiff`, `extrusionProposals`, `extrusionPowersTree`;
added the focused `extrusionReview` response helper. Final detected/selected
context is established before Power results are returned. Source context remains
separate. One engine owns the component/Power plan, so Powers do not execute a
second time after the component import. Explicit decisions load after reset.
Malformed decisions are rejected. Real browser imports require a reviewed
fingerprint; scope acknowledgement flags are strict booleans and every target,
field and source snapshot is revalidated by the library boundary.

Only bounded plan metadata and candidate/target evidence are transported; raw
Power bodies and arbitrary placeholder maps are not added to the pairing tree.
Existing authorization, request token handling and the global picker remain.

### `admin/assets/js/extrusion.js`

Modified harvest, source indexing, `proposal`, `runConfig`, `loadCatalogue`,
weigh/diff/import handlers and row rendering; added review invalidation,
acceptance, readiness and actual-target detail functions. Source keys identify
rows independently of the selected Power GUID. Context changes clear old
explicit decisions and approvals and re-fetch complete Power pairings. Request
generation counters reject stale responses. The board displays actual target
GUID/system name/stored namespace separately from the source FQN, alternatives,
write scope and blockers. Ambiguity is not a default Create. One acknowledgement
covers the reviewed shared/foreign/unknown/remapping scopes; a changed plan
clears it. Unchanged and skipped existing records are not converted into Ignore.

### `admin/tmpl/extrusion/default.php`

Modified the pairing pane, explanatory text and natural-string language map;
added the live review notice and one labelled scope-acknowledgement checkbox.
The Import button is disabled until a valid current plan is reviewed. Small
view-local CSS makes source and target evidence readable; no external media
or unrelated generated administrator structures are changed.

## Impact

Behavior: preview and import use the same server decision and effective plan;
unsafe or stale imports are rejected before mutation. API callers must update
source-key decisions and reviewed-plan transport; no ambiguous GUID-as-row-key
compatibility fallback is introduced.

Visual/accessibility: readable source/target separation, status badges,
expandable alternatives, `aria-live` review feedback, labelled acknowledgement,
and disabled unsafe submission. All new text uses JCB's natural-string
`Text::_()` convention and escaped DOM output.

Host integration uses the installed Joomla application's existing APIs. Target
Power compilation continues through the existing compiler; this change does
not select target Joomla behavior from the host major.

## Verification

The owning browser spec is
`libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`. It drives real AJAX
harvest, target switching, ambiguity/manual pairing, shared-scope review and
dry-run import. `.github/gui-tests/extrusion-fixtures.php` is CLI-only, requires
a disposable-stack sentinel, seeds unique isolated records, checks actual Data
writes and Power compiler results, and restores fixture records before browser
journeys. The harness destroys its database/container volumes on completion.
No production definitions are used or modified.

Local checks completed while preparing this change: PHP syntax for the changed
model/template and integration fixture, JavaScript syntax, Bash syntax. Full
repository and installed-browser CI results are recorded in PR #53 after each
published checkpoint; unrun checks are not represented as passed here.

## Risks, limits and rollback

The public Power row keys intentionally change to stable source keys. Consumers
must consume the same version of the engine and administrator interface.
Rollback must revert the coordinated library and interface change, not just the
JavaScript; no historical Power records are automatically migrated or repaired.
The fixture compiler check exercises actual Power preparation and generated
namespace/path/code data; it does not claim a full-tree JCB self-compilation.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Model/AjaxModel.php` | JCB's maintained Ajax Model custom methods; exact database definition IDs are not in this repository | Yes | Pending source identification | Repository owner must map the named extrusion methods to the authoritative JCB records; PR #53 supplies their exact implementation diff. No access to production definitions was assumed. |
| `admin/assets/js/extrusion.js` | Extrusion view's maintained JavaScript source; exact JCB field identity not supplied | Yes | Pending source identification | Repository owner maps the extrusion JavaScript source and transfers the complete reviewed file before regeneration. |
| `admin/tmpl/extrusion/default.php` | Extrusion view's default template and language registrations; exact JCB view record not supplied | Yes | Pending source identification | Repository owner maps the view template and transfers its reviewed changes before regeneration. |

The repository implementation and source-transfer mapping are delivered together;
this record does not falsely claim that an unavailable authoritative JCB database
was updated. Verification of that external transfer remains a maintainer action.
