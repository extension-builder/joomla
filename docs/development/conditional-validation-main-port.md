# Conditional validation port to main

Implementation: [PR #69](https://github.com/extension-builder/joomla/pull/69).

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
  no additional ACL lookup. The browser refresh also preserves the original
  rendered optional/disabled state after evaluating conditions.
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

## Main-specific integration

The compiler emits condition definitions into a protected model property and
keeps orchestration in `validate()` plus `resolveConditionalData()`. Its shared
rule owns evaluation, temporary form state and cleanup. The models invoke the
native parent validator once, so the existing filtering and plugin lifecycle
remain in charge. The rule cannot re-enable a requirement removed from that
same live Form by getForm or a plugin; it does not repeat permission decisions.

Main has six edit-model templates, including separate administrator and site
Joomla 6 templates. Each receives only the property placeholder after
`typeAlias`. The shared browser producer retains all four target hooks, and the
Joomla 6 renderer retains its DOM-ready listener wrapper, media path and footer
timing. The installed Field asset remains `media/js/admin/field.js`; the loading
overlay and authored footer outside the condition listeners are unchanged.

The nine original normalized condition-group literals were checked against main
and recovery and are identical. Token-based preview generation replaces only
conditional members, preserving main's native database queries, unique-copy
logic and API behavior. Main's Field save/reopen scenarios and their record
cleanup remain, alongside the added condition-transition scenarios.

## Verification

The implementation at remote commit `539a9562635fac4173d140971d0c8c883acfdbc5`
has the same source tree as local native-compile candidate
`e88903dcbb2c63f4446fb47d6f62e0d37ccf5e24`.

| Check | Result |
| --- | --- |
| Main PHP 8.3 and 8.4 unit jobs | 5,530 tests / 69,067 assertions on each; no failures, errors, warnings or skips |
| PHP 8.5 readiness lane | 74 compatibility tests / 400 assertions |
| Installed native Field contract | 36 tests / 207 assertions, including malformed single-select arrays |
| Browser/server parity | 100 fixtures / 312 transitions, Joomla 3 and 6 renderers |
| Installed GUI | 46 passed, including existing save/reopen/cleanup and new conditional transitions |
| Real Chromium authored-footer checks | All five passed |
| API integration | Passed, including compiled resources, partial PATCH and denied-user checks |
| Hosted full compiler comparison | Passed for Joomla 3, 4, 5 and 6 |
| Separate full native conditional comparison | Fresh main baseline and candidate installs; all four targets passed; no output normalization |
| Generated-package syntax | 1,355 PHP files and 70 JavaScript files passed |
| Contribution gates | Style, ownership, container keys, moved conditions and whitespace passed |

The hosted implementation results are in the
[unit](https://github.com/extension-builder/joomla/actions/runs/43107705104),
[GUI](https://github.com/extension-builder/joomla/actions/runs/43107705057),
[API](https://github.com/extension-builder/joomla/actions/runs/43107705138) and
[golden](https://github.com/extension-builder/joomla/actions/runs/43107705073)
workflows. Dependency audits are clean, and PHP 8.4 coverage was generated and
uploaded. Subsequent documentation-only commits retain this implementation;
the PR checks show the latest exact-head workflow results.

The separate [native compilation evidence](conditional-validation-main-evidence/README.md)
records the restored immutable input, full native source installation,
comparison and syntax results. Both installs verified 5,645 deployed source
hashes. Each target changed eight expected files plus the generic rule-file
rename; every remaining generated byte and all 65 resolved-Power completion
records were identical. The same pre-existing Joomla 3 dashboard warning occurs
on both sides. This is a full fixture build, not a full JCB self-build.

The main source comparison is independent of recovery results. Recovery's
additional selector-shape and rendered-form protections also pass its final
4,880-test / 59,320-assertion suite and 40 installed GUI tests.
