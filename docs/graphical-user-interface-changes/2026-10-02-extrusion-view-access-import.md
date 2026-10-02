# Extrusion import uses the custom-view access permission

## Change identity

- **Date first changed:** 2026-10-02
- **Author/implementer:** Codex, under repository-owner direction
- **Task/issue/PR:** Current owner request to carry the confirmed Extrusion migration corrections into `extension-builder/joomla`; branch `fix/generated-api-contracts-and-extrusion`.
- **Change record status:** Ready for review; installed browser verification pending CI

## Explicit permission

- **Permission reference:** The current owner instruction authorizes the Extrusion migration corrections in this repository, including the import button permission correction from the linked migration conversation.
- **Authorized paths:** `admin/tmpl/extrusion/default.php`, `admin/src/Model/AjaxModel.php`, `admin/access.xml`, `admin/language/en-GB/en-GB.com_componentbuilder.ini`, `admin/language/en-GB/en-GB.com_componentbuilder.sys.ini`.
- **Authorized outcome:** A user granted the custom admin view's existing `extrusion.access` permission can review and import through its existing guarded workflow without a separate hand-added `extrusion.import` action.
- **Permission summary:** Align the Extrusion template and AJAX permission check with the permission JCB generates for access to the custom admin view.

## Purpose and rationale

JCB's linked custom admin view permission generates `extrusion.access`. Requiring an additional `extrusion.import` action hides the import button and rejects the AJAX call unless the generated access XML is edited manually. The correction belongs to the maintained view and AJAX definitions; an Extrusion library change alone cannot change those administrator entry points.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-10-02-extrusion-view-access-import.md`
- `.github/gui-tests/extrusion-acl.php`
- `libraries/vendor_jcb/tests/Contract/ExtrusionImportAccessTest.php`

### Modified

- `admin/tmpl/extrusion/default.php`
- `admin/src/Model/AjaxModel.php`
- `admin/access.xml`
- `admin/language/en-GB/en-GB.com_componentbuilder.ini`
- `admin/language/en-GB/en-GB.com_componentbuilder.sys.ini`
- `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`
- `.github/gui-tests/run.sh`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/tmpl/extrusion/default.php`

- **Change type:** Modified
- **Stable locations:** Existing outer `extrusion.access` template gate, `#extrusion-import-button`, `window.JCBExtrusion.canImport`.
- **What changed:** Render the import button inside the existing view-access gate and set `canImport: true` only inside that authorized branch.
- **Why:** The custom-view access permission is the supported JCB-generated contract.
- **Related paths/symbols:** `AjaxModel::extrusionImport()`.

### `admin/src/Model/AjaxModel.php`

- **Change type:** Modified
- **Stable location:** `AjaxModel::extrusionImport()` authorization guard.
- **What changed:** Require `extrusion.access` without a second import action.
- **Why:** The actual AJAX call must honour the same permission as the template.
- **Related paths/symbols:** Existing configuration parsing, dry-run branch and approved-plan guard remain active.

### `admin/access.xml`

- **Change type:** Modified
- **Stable location:** Component-level Extrusion action block.
- **What changed:** Remove the redundant `extrusion.import` action while retaining `extrusion.access`, dashboard and submenu permissions.
- **Why:** The view no longer depends on a permission outside its generated access contract.
- **Related paths/symbols:** Both English administrator language files.

### `admin/language/en-GB/en-GB.com_componentbuilder.ini`

- **Change type:** Modified
- **Stable locations:** `COM_COMPONENTBUILDER_EXTRUSION_IMPORT_BUTTON_ACCESS` and its description.
- **What changed:** Remove the two labels belonging exclusively to the removed action.
- **Why:** Removed access actions must not leave obsolete permission labels.
- **Related paths/symbols:** `admin/access.xml`.

### `admin/language/en-GB/en-GB.com_componentbuilder.sys.ini`

- **Change type:** Modified
- **Stable locations:** The same Extrusion import permission label and description.
- **What changed:** Remove the two obsolete labels.
- **Why:** Keep the system language catalogue consistent with the access actions.
- **Related paths/symbols:** `admin/access.xml`.

### Test and harness paths

`ExtrusionImportAccessTest` exercises the real AJAX model without its installed constructor and isolates only the native User ACL boundary. The Extrusion browser spec uses two real non-super-user accounts, one granted view access and one denied. `.github/gui-tests/extrusion-acl.php` initializes the native extension namespaces and console HTTP origin before user plugins, creates and removes those isolated accounts/groups with their native `allowTourAutoStart` preference disabled, and restores asset ACL rules; group cleanup verifies each owned group remains a leaf with no remaining user memberships before native deletion. `run.sh` invokes cleanup even when setup or browser assertions fail.

## Impact

- **Behavioral impact:** View access now authorizes the import entry point; denied users remain denied before configuration processing. CSRF, session identity, write-plan fingerprints, required scope acknowledgements and engine validation are unchanged.
- **Visual impact:** The import button is available to authorized view users; its existing disabled/review state is preserved.
- **Accessibility impact:** Preserve existing button roles, natural labels and confirmation dialog.
- **Compatibility impact:** Existing Joomla 6 administrator workflow; no API or compile-target behavior added.
- **Generated-output impact:** These maintained administrator artifacts change. Their corresponding authoritative JCB definitions still require reconciliation below.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| PHP syntax on changed PHP files | Local PHP 8.3.6 | Passed: AJAX model, Extrusion template, isolated ACL helper and contract test |
| PHPUnit `ExtrusionImportAccessTest` | PHP 8.3.6, PHPUnit 12.5.33, Joomla 6.1.2 class runtime, no installed application | Passed: 5 tests, 20 assertions |
| PHPUnit `ExtrusionImportAccessTest` and `ExtrusionAjaxDiagnosticsTest` | Same local class runtime | Passed together: 10 tests, 72 assertions |
| `composer test` | PHP 8.3.6, PHPUnit 12.5.33, Joomla 6.1.2 source runtime | Passed: 4,399 tests, 47,730 assertions |
| `php bin/check-php-style.php` | Local test runtime, complete style scan | Passed: 534 files, including the new contract test |
| JavaScript and shell syntax; `git diff --check` | Local Node, Bash and Git | Passed |
| Playwright Extrusion permission journeys | Disposable installed Joomla/JCB GUI harness | Pending |

### Manual scenarios

- None; browser-driven cases provide the planned installed verification.

### GUI test coverage

- **Spec files added/updated:** `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js`.
- Covers access-only import visibility and real dry-run harvest/import; denied view access retains its native dashboard redirect/message and a direct authenticated AJAX import attempt remains blocked.

### Checks not performed

- Installed browser execution and the PHP 8.4 CI matrix remain pending the branch's CI run.
- JCB self-regeneration is unavailable: the maintainer's authoritative custom-view/AJAX definition package is not present in this checkout.

## Risks, limitations, and rollback

- **Known risks:** Users previously granted `extrusion.access` but intentionally denied the additional import action now gain the view's import capability; this is the owner-requested permission contract.
- **Known limitations:** Editing this generated administrator application does not reconcile its absent authoritative definition package.
- **Rollback:** Revert this coherent permission change and its record; the isolated harness performs account/group and asset-rule cleanup.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/tmpl/extrusion/default.php` | Extrusion custom admin view layout definition; exact maintained record absent | Yes | Pending source identification | Maintainer to identify the custom-view layout record, carry over the button/bootstrap change, and regenerate. |
| `admin/src/Model/AjaxModel.php` | JCB AJAX custom-code definition for `extrusionImport`; exact maintained record absent | Yes | Pending source identification | Maintainer to identify the method's custom-code record, update its ACL guard, and regenerate. |
| `admin/access.xml` | Component/custom-admin-view linked permission definitions; exact maintained records absent | Yes | Pending source identification | Maintainer to remove the redundant import-action definition and verify generated access XML. |
| `admin/language/en-GB/en-GB.com_componentbuilder.ini` | Language records derived from the removed permission; exact maintained records absent | Yes | Pending source identification | Maintainer to remove obsolete labels in the definition package and verify generated language files. |
| `admin/language/en-GB/en-GB.com_componentbuilder.sys.ini` | System language records derived from the removed permission; exact maintained records absent | Yes | Pending source identification | Maintainer to reconcile the same permission-label removal and verify system language output. |

## Final consistency check

- [x] The affected paths name the scoped implementation and tests.
- [x] Protected paths have stable locations and exact what/why details.
- [x] Behavioral and visual impact are explicit.
- [x] Verification records executed results; installed browser checks remain explicitly pending.
- [x] Every protected path has a reconciliation status and concrete transfer step.
- [x] Transfer remains required until authoritative definitions are verified.
- [x] Changed behavior ships GUI coverage.
- [x] Implementation remains within the cited permission.
