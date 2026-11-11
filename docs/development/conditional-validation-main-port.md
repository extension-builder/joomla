# Conditional validation port to main

## Baselines and scope

This port starts from main `86b1900a87ccd979233572e47f1f2513fc1493f1` and
adapts the reviewed recovery implementation at
`c6070dbc3f4879cdfbb2c4b9886992cc8b52181c` in
[PR #68](https://github.com/extension-builder/joomla/pull/68).

Recovery's final unit workflows pass on PHP 8.3 and 8.4, with 4,871 tests and
59,241 assertions. Its API, all four compiler targets and 40 installed GUI
tests pass. Pristine main separately passes 5,425 tests and 67,104 assertions
using its own locked dependencies and pinned Joomla 6.1.2 runtime.

## Integration contract

- Generated symbols remain generic: `conditionalRequirements`,
  `resolveConditionalData()`, `ConditionalrequiredRule` and
  `update<ViewCode>Conditions()`.
- Validation uses the same Form that getForm and native plugins have already
  configured. It preserves optional/disabled/unset target metadata and performs
  no additional ACL lookup.
- Main's target-specific ViewScript hooks, dedicated Joomla 6 templates,
  current asset paths, footer execution timing and API/PATCH behavior remain.
- Compiler-owned output follows main's line-by-line PHP_EOL/Indent standard,
  with split Power placeholders. Runtime behavior and readable output retain
  recovery's contract.
- All twelve condition operators, all four actions, arrays and chained
  predicates remain covered. One-way actions use current values and authored
  order; historical multi-option Is Not semantics remain unchanged.
- Views without conditional requirements receive no extra model validation.
- Field's missing policy is an ordinary final Is Note/Spacer -> Hide Only
  condition. No generic type-name/database exception or browser exemption
  authority is introduced.

## Source ownership

The main generated previews are demonstrations of documented normalized
inputs, not an export of JCB's unavailable maintenance package. Transfer the
ordinary Field condition and authored JavaScript changes into that source
before a self-build. The
[protected-path record](../graphical-user-interface-changes/2026-11-11-conditional-validation-main-port.md)
tracks the exact templates, models, rule and Field assets.

## Verification status

Implementation and port-specific verification are in progress. Recovery
results are evidence for the source implementation; they do not substitute
for main's own unit, native-script, GUI, API and full compiler checks.
