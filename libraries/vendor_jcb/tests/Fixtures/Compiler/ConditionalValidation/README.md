# Conditional validation preview inputs

These fixtures are explicit, normalized inputs for reproducing the conditional
parts of JCB's installed administrator models. They are **not** an export of the
authoritative JCB maintenance package and do not claim to rebuild the component.

The baseline is `2dab8eb3249185d18b11a55694adb559c349addc`. The groups were
extracted from the `$conditionGroups` literals in that revision's nine generated
models. Eight fixtures preserve those definitions exactly. `field-input.json`
preserves its first five groups and adds the declarative condition below.

| Fixture view | Generated model | Groups |
| --- | --- | ---: |
| `admin_view` | `Admin_viewModel.php` | 1 |
| `custom_code` | `Custom_codeModel.php` | 2 |
| `dynamic_get` | `Dynamic_getModel.php` | 9 |
| `field` | `FieldModel.php` | 5 existing + 1 new |
| `fieldtype` | `FieldtypeModel.php` | 7 |
| `help_document` | `Help_documentModel.php` | 5 |
| `joomla_component` | `Joomla_componentModel.php` | 3 |
| `library` | `LibraryModel.php` | 2 |
| `server` | `ServerModel.php` | 5 |

All nine models must use the same runtime interface as their shared
`JcbconditionalrequiredRule`. Updating only Field and that rule would leave the
other models calling the obsolete callback interface.

## Field condition to transfer into the maintenance source

Append this ordinary condition after the existing Field view conditions:

| Setting | Value |
| --- | --- |
| Match field | Fieldtype (`fieldtype`) |
| Match behavior | Is (`match_behavior = 1`) |
| Match options | Note or Spacer |
| Target behavior | Hide Only (`target_behavior = 4`) |
| Target relation | Independent (`target_relation = 0`) |
| Required targets | `datatype`, `datalenght`, `datalenght_other`, `datadefault_other`, `indexes`, `null_switch`, `store`, `on_get_model_field`, `on_save_model_field` |
| Additional visual targets | `datadefault`, `initiator_on_get_model`, `initiator_on_save_model`, `note_expert_field_save_mode` |

The normalized server group has `show = false` and `toggle = false`. Its positive
match hides these targets only for Note or Spacer. Its final position lets it
override earlier database conditions, including stale Other or Expert values.
Every refresh starts from the original requirement/display state, so switching
back to a data-storing type reapplies the ordinary conditions.

The option GUIDs come from the baseline installer fieldtype catalogue in
`admin/sql/install.mysql.utf8.sql`: Note is
`f9ecacd0-8481-4157-8c71-d7aaefc2b7c3` and Spacer is
`626d0cba-a908-4a4f-a447-96d781aeaa0b`. These are option identities, not the GUIDs
of the maintenance package's Fieldtype and target field definitions. Those
authoring records must be selected in the real maintenance package when this
condition is transferred. Numeric row IDs are installation-dependent and are
not substituted for these option GUIDs.

`browserExtraTargets` records optional visual targets associated with server
groups; its keys are zero-based group indices. `browserOnlyGroups` preserves the
four existing optional editor conditions. The browser renderer places the new
Note/Spacer group after those existing conditions. No PHP type-name exception or
provider is needed.

## Reproduce and verify

From `libraries/vendor_jcb/tests`, after installing its locked dependencies:

```bash
php bin/regenerate-conditional-model-preview.php
php bin/regenerate-conditional-browser-preview.php
php vendor/bin/phpunit --configuration phpunit.xml.dist --no-coverage \
  VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ConditionalPreviewTest.php
```

The two preview commands check by default. Add `--write` to apply emitted
fragments. The model tool calls the production `ValidationFix` and
`ConditionalRule` emitters, resolves their existing imported Power names, and
uses PHP token offsets to replace only the model property, validation methods,
and shared rule body. Unrelated generated/custom members remain byte-for-byte
unchanged. The browser tool calls the production `ViewScript` emitter and
preserves authored JavaScript outside the generated condition region.

The native tests cover all nine models' condition contracts, exact emitter
parity, cleanup after success and failure, and Field's actual XML definitions.
They do not replace the full same-input component compilation or installation
checks. The GUI change record documents the remaining transfer to the
authoritative maintenance source and the separate full-build evidence.
