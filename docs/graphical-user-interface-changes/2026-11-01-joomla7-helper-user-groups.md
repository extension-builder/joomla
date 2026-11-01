# Read helper user groups without Joomla compatibility mode

## Change identity

- **Date first changed:** 2026-11-01
- **Author/implementer:** Codex
- **Task/issue/PR:** Joomla 7 readiness request accompanying `Deprecated_Joomla7_JCB.txt`; branch `fix/joomla7-php85-deprecations`.
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** The active request explicitly asks to fix the deprecated user getter in the Component Builder helper and implement the attached notices in `extension-builder/joomla`.
- **Authorized paths:** `admin/src/Helper/ComponentbuilderHelper.php`.
- **Authorized outcome:** Read the Joomla user's groups directly, without the deprecated `User::get()` compatibility method.
- **Permission summary:** Apply the documented helper fix and preserve its help-document access checks.

## Purpose and rationale

Joomla 7 restricts `User::get()` to compatibility mode. The installed helper calls it whenever a view resolves its help URL. A library-only fix would leave the shipped administrator helper calling the deprecated method.

## Affected paths

### Created

- None

### Modified

- `admin/src/Helper/ComponentbuilderHelper.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/src/Helper/ComponentbuilderHelper.php`

- **Change type:** Modified
- **Stable location(s):** `ComponentbuilderHelper::getHelpUrl()`.
- **What changed:** Replace `$user->get('groups')` with `$user->groups`.
- **Why:** Joomla exposes this array as a public property; direct access avoids its deprecated compatibility getter.
- **Related paths/symbols:** `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Creator/Helper.php`, the help-method generator.

## Impact

- **Behavioral impact:** Resolve help URLs without invoking `User::get()`; retain group intersection and published/location/view filters.
- **Visual impact:** No layout or text change; the compatibility notice no longer interrupts this path.
- **Accessibility impact:** None; markup and interactions are unchanged.
- **Compatibility impact:** Host runtime user-property access works on the existing supported Joomla user object and Joomla 7.
- **Generated-output impact:** The matching compiler generator emits direct group-property access so regeneration retains this fix.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `composer test:compatibility` | PHP 8.5.12; pinned Joomla 6.1.2 source | Pass: 74 tests, 400 assertions, zero diagnostics, including six administrator help-selection cases and generated admin/site filtering for Joomla 3–6. |
| Changed-file PHP style, ownership, moved-condition and container-key guards; `git diff --check` | PHP 8.3.6; base `03da7d65d78a4c455b6fd17a7f4653400a82d3a5` | Pass: 19 styled PHP files, 1,546 owned declarations, 969 service keys. |
| `node --check libraries/vendor_jcb/tests/gui/specs/compiler.spec.js` | Node.js | Pass. |

### Manual scenarios

No manual installed-Joomla session has been run.

### GUI test coverage

- **Spec files added/updated:** `libraries/vendor_jcb/tests/gui/specs/compiler.spec.js` now checks successful real compiler-page navigation and absence of legacy getter diagnostics. The toolbar invokes `getHelpUrl()` even without a configured help record.
- The existing integration harness runs Joomla 6. The six PHPUnit help-selection cases explicitly forbid `User::get()`, protecting the Joomla 7 replacement while checking allowed/denied groups and general help fallback.

### Checks not performed

- A full installed Joomla 7 smoke test is not available in the existing Joomla 6 integration harness.
- Local GUI and golden integration runs require Docker, which this workspace does not provide; the PR workflows run those gates.

## Risks, limitations, and rollback

- **Known risks:** Low: both expressions read the same public group array; authorization filtering is unchanged.
- **Known limitations:** This fixes the reported compatibility path; it does not certify all JCB functionality on Joomla 7.
- **Rollback:** Revert this PR as a unit, including the helper, matching generator, tests, and this change record.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Helper/ComponentbuilderHelper.php` | `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Creator/Helper.php`, help-method generation | Yes | Reconciliation in progress | The generator is updated in this branch and emitted admin/site help filtering executes successfully for Joomla 3–6. The maintainer must carry these repository changes into the authoritative upstream maintenance system; full JCB self-compilation is not claimed. |

## Final consistency check

- [x] The affected-path lists identify the protected diff.
- [x] The path has a stable location and exact what/why details.
- [x] Behavioral and visual impact are explicit.
- [x] Verification records actual results and identifies skipped checks.
- [x] The path has an authoritative-source mapping and reconciliation status.
- [x] Transfer required is Yes.
- [x] GUI coverage is recorded.
- [x] The implementation remains within the cited permission.
