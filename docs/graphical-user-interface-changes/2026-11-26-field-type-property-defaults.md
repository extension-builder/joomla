# Restore Field type defaults after changing the selection

On 2026-11-26, PR #77 was asked to address the installed Field checks that also fail on main. The alpha build restored an older Field property handler that skips numeric zero defaults and applies responses after the user chooses another field type.

## Changes

- `media/js/admin/field.js` — `getFieldTypeProperties()` checks the selected type before touching the form, applies zero-valued index and storage defaults, then refreshes the generated conditions. `dbChecker()` clears database choices for Note and Spacer and toggles the explanatory notices. Generated conditions handle field visibility and required attributes. The condition functions at the top of this file are regenerated from the existing Field fixture.
- `admin/tmpl/field/default.php` — restore the generated Field condition listener block using the repository preview generator. The authored form listeners continue to request the selected type’s properties.
- `libraries/vendor_jcb/tests/gui/specs/field.spec.js` — the existing delayed-response test also checks that Text’s property editor survives the late Note response. The existing transition tests cover Note and Spacer switching to Text and back, including the resulting visibility and required state.

## Verification

The conditional browser preview reproducer passed. Browser/PHP parity passed for 100 fixtures and 312 transitions. Both JavaScript files passed Node syntax checks and the Field template passed PHP 8.4.27 lint. The installed Chromium scenarios run in the PR’s GUI workflow.

## Reconciliation and rollback

| Repository path | Authoritative source | Transfer required | Status | Owner and next action |
| --- | --- | --- | --- | --- |
| `media/js/admin/field.js` | Field admin view’s authored JavaScript and conditions, including the Note/Spacer Hide Only group | Yes | Source identified | Maintainer to transfer the two authored functions to the maintained Field view and align its condition target ordering with `field-input.json` before regenerating. |
| `admin/tmpl/field/default.php` | Field condition listeners emitted by `ViewScript` | Yes | Source identified | Maintainer to regenerate the Field view after reconciling its conditions. |

Revert the Field browser change as one commit to restore the previous installed JavaScript, template listeners and GUI assertion.
