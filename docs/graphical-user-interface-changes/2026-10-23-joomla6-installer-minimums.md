# Joomla 6 generated installer minimum versions

## Change identity

- Date first changed: 2026-10-23.
- Implementer: Codex.
- Task: Joomla 6 native deprecation completion, branch `deprecation/joomla6-native-completion`.
- Status: Ready for review.

## Explicit permission

- Permission reference: the current maintainer request makes Joomla 6 the cutoff for the native migrations and identifies `admin/compiler` as the authoritative template location.
- Authorized path: `admin/compiler/joomla_6/INSTALLER_SCRIPT.php`.
- Authorized outcome: generated Joomla 6 extensions require Joomla 6 and its PHP baseline before installation; Joomla 3–5 remain unchanged.

## Purpose and rationale

The Joomla 6 component installer still advertised Joomla 4.3 and read the compiler host's `JOOMLA_MINIMUM_PHP`. That permits installation on runtimes that cannot execute the generated native Joomla 6 API calls. The target-specific template must own fixed minimums. The associated module/plugin generator similarly required only Joomla 5.

## Affected paths

### Created

- None.

### Modified

- `admin/compiler/joomla_6/INSTALLER_SCRIPT.php`.

### Moved or renamed

- None.

### Deleted

- None.

## Implementation details

### `admin/compiler/joomla_6/INSTALLER_SCRIPT.php`

- Change type: Modified.
- Stable location: generated installer constructor, `minimumJoomla` and `minimumPhp` assignments.
- Change: assign Joomla `6.0.0` and PHP `8.3.0`, independent of the compiler host.
- Reason: reject incompatible installation targets before extension-specific preflight code runs.
- Related library: `Compiler/Extension/JoomlaSix/InstallScript::flight()` emits the Joomla `6.0.0` guard and matching failure message for modules/plugins.

## Impact

- Behavioral impact: a generated Joomla 6 component rejects Joomla 5 or PHP below 8.3 during preflight; module/plugin scripts reject Joomla below 6.
- Visual impact: no JCB administrator interface changes; an incompatible destination receives the existing minimum-version failure message with the correct required version.
- Accessibility impact: none; no markup, labels, focus, or controls change.
- Compatibility impact: Joomla 6 compile target only. Earlier target templates and generators remain unchanged.
- Generated-output impact: two component constructor assignments and the module/plugin preflight requirement/message change.

## Verification

Integrated source, unit, installed API, browser and compiler comparison evidence is recorded in the [completion report](../audits/joomla6-native-completion.md).

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| Targeted `InstallerTemplateTest` and `Extension/InstallScriptTest` | PHP 8.3.6, Joomla 6.1.2 test runtime | Pass: 46 tests, 282 assertions. Joomla 5.4 rejects before adapter access; Joomla 6.0 and 6.1.2 accept, and the PHP requirement stays 8.3 despite a lower host constant. |

### Manual scenarios

None; generated PHP executes in the isolated unit runtime.

### GUI test coverage

- Spec files added/updated: None. This change affects generated installer execution and does not change a JCB administrator view. Native generated PHP runtime tests cover the changed acceptance boundary.

### Checks not performed

- Installing a generated archive on an actual unsupported Joomla/PHP runtime; hosted installation workflows remain integration coverage on the supported runtime.
- Unsupported-runtime archive installation is not part of the hosted supported-runtime API checks.

## Risks, limitations, and rollback

- Known risks: projects compiled explicitly for Joomla 6 can no longer pass these checks on Joomla 5; this is the intended target boundary.
- Known limitations: custom user hook bodies retain their own requirements.
- Rollback: revert the two template assignments, the Joomla 6 module/plugin guard and matching tests together.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_6/INSTALLER_SCRIPT.php` | Same maintained compiler template | No | Not applicable — owner confirmed | Maintainer explicitly identified `admin/compiler` as the template source in this task; review the generated installer minimums. |

## Final consistency check

- [x] The protected-path list matches the final edit.
- [x] Generated behavior and target isolation are explicit.
- [x] Source reconciliation cites the maintainer's template-location decision.
- [x] GUI coverage exclusion identifies the non-interface execution contract.
- [x] Focused runtime validation recorded after execution.
