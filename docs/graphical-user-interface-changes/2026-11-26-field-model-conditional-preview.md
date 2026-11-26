# Restore the Field model’s conditional preview

PR #77 addresses the conditional-model check that also fails on main. The v6.3.0-alpha1 self-build reordered the nine required targets of the Field view’s Note/Spacer condition. The repository’s compiler input still emits the original order.

## Change and impact

In `admin/src/Model/FieldModel.php`, the final entry of `FieldModel::$conditionGroups` again lists the targets in compiler-preview order. The options, target names, Hide Only behavior and other alpha model changes are preserved. Field validation uses the same set of required targets. There is no change to rendering, keyboard behavior or the shared compiler.

## Verification

`php bin/regenerate-conditional-model-preview.php` compares the nine installed model previews and the shared rule with output from the production emitters. `ConditionalPreviewTest` exercises Joomla’s native form validation for Note, Spacer and Text. The existing installed `libraries/vendor_jcb/tests/gui/specs/field.spec.js` covers saving and reopening Note and Spacer and restoring Text requirements. Reverting this model change restores the alpha target ordering.

## Reconciliation

| Repository path | Authoritative source | Transfer required | Status | Owner and next action |
| --- | --- | --- | --- | --- |
| `admin/src/Model/FieldModel.php` | JCB maintenance component, Field admin view, final Fieldtype Is Note/Spacer → Hide Only condition | Yes | Source identified | Maintainer to align this condition’s target order with `tests/Fixtures/Compiler/ConditionalValidation/field-input.json` before the next self-build. |
