# Conditional validation recovery

## Baseline and purpose

This patch starts at `2dab8eb3249185d18b11a55694adb559c349addc` on
`jcb-recovery-version`. It improves the compiler's conditional form validation
for generated components. Components without conditional requirements must not
receive an unused validator, model property, or validation override.

The implementation is being developed in separate commits for generated
runtime structure, model/template integration, condition behavior, and verified
generated output. This document records the source boundary and verification;
it does not claim that unrun checks have passed.

## Generated architecture

- `Architecture.Model.ValidationFix` emits a documented multiline model
  property, a short native validation wrapper, and a named stored-data resolver.
- `Architecture.Model.ConditionalRule` emits the shared runtime implementation
  into `JcbconditionalrequiredRule`, with named lifecycle and matching methods.
- `Compiler.Builder.Validation.Fix` remains the shared normalized definition
  store. The browser's submitted `not_required` field is never an authority.
- Native model plugins, filtering, required checks, validation and
  post-processing retain their order and use the original form instance.
- Temporary rule registration and required attributes are restored on every
  exit. State is isolated by the exact form object.

The generated rule must run after filtering and before target required checks.
The internal first-position XML rule field supplies that boundary; its
installation and removal belong inside the rule implementation.

## Browser and server contract

Both sides evaluate the same normalized definitions in source order. Every
browser refresh starts with the original display state and the authored
required flags, then evaluates all groups. The last applicable action on a
shared target wins. The generated `jcbUpdate<ViewCode>Conditions()` function is
also available to authored AJAX handlers after they assign selector values.

This deliberately replaces event-history-dependent one-way behavior. An
unmatched Show Only or Hide Only group makes no change within the current pass;
the baseline or an earlier applicable group remains in effect. It does not
retain an earlier browser event's result, which the server cannot reconstruct
from a save request. Show Toggle and Hide Toggle still apply the opposite
action when unmatched.

| Condition behavior | Retained meaning |
| --- | --- |
| Is / Is Not / Any Selection | Existing option comparisons and selection presence |
| Active / Unactive | Text presence / absence, retaining meaningful zero values |
| Keyword All / Any, with either case mode | Existing all/any matching and keyword normalization |
| Minimum / Maximum / Exact Length | JavaScript-compatible UTF-16 length; existing zero/omitted-length fallback of 5 |
| Chained matches | AND between related predicates; each predicate is parenthesized |
| Array selections | A matching selected entry satisfies its predicate |

For compatibility, **Is Not with several configured options retains the
historical OR of inequalities**, not a new NOT-IN meaning. Use a positive
multi-option Is rule when matching Note or Spacer. Changing the meaning of
existing authored operators needs its own migration.

Three compiler defects are corrected alongside the ordered refresh: scalar
chaining now groups OR predicates before AND, related fields use their own
`match_extends`, and string options/keywords are escaped for JavaScript.
Unsupported operator/type pairs fail compilation with the view, field,
operator and type instead of emitting invalid JavaScript.

Generated condition actions no longer send a `not_required` exemption list.
Only custom scripts that reference `updateFieldRequired` receive a deprecated,
inert compatibility function. It carries no server validation authority.
Optional supplied values still undergo Joomla's normal type and format checks.

## Conditions and source ownership

The supported selection, presence, contains, length and chained-condition
definitions are compiler inputs. Their generated browser and server behavior
must be verified together. Changes to an operator's meaning require explicit
regressions and documentation, separate from mechanical extraction.

JCB's checked-in Field model currently contains five structured groups. The
Note/Spacer decision that makes database settings inapplicable exists in its
custom `dbChecker()` JavaScript, not in those groups. Generic compilation cannot
derive that absent rule without an authored definition or trusted server-side
extension. Component-specific type names and database queries do not belong in
the generic validator.

The available checkout and public fixture repositories do not include JCB's
complete maintenance definition package. A generated preview and public fixture
compilation are useful evidence but cannot be described as JCB self-compilation.
The Field policy must be represented in the maintained definition before a
future JCB self-build can reproduce it. Any preview must identify its exact
input and the remaining source transfer.

The proposed Field migration uses the existing conditions editor: add a final,
isolated **Hide Only** group, watch **Field Type**, choose **Is**, and select
the actual Note and Spacer type identifiers. Target every database control,
including the custom length/default controls and expert storage handlers.
Place it after the five existing required-field groups. Matching Note/Spacer
then removes all database requirements, including those activated by stale
child values; other types keep the earlier groups' ordinary behavior.

The installed type catalogue identifies Note as
`f9ecacd0-8481-4157-8c71-d7aaefc2b7c3` and Spacer as
`626d0cba-a908-4a4f-a447-96d781aeaa0b`. These are option values, not the missing
maintenance definition's match/target field identifiers. Additional custom
non-database types must be included explicitly in the maintained condition.

## Verification contract

Verification must cover:

- Selection equality and inequality, presence, all/any contains, case handling,
  and minimum/maximum/exact length.
- Chained matches, target conflicts, one-way actions, arrays, checkboxes and
  meaningful zero values.
- Native plugin/filter timing, API PATCH omission versus explicit emptiness,
  protected selectors, selected form groups and stored-record failures.
- No browser exemption authority, normal validation of optional supplied
  values, and restoration after success, failure and exceptions.
- Multiline literals without scalar mutation, correct imports and deferred
  Joomla Power resolution, and no generated validation for unaffected views.
- PHP style, declaration ownership, the complete unit suite, generated output
  for supported Joomla targets and installed behavior where fixtures permit.

## Current verification status

The model/rule refactor and template integration pass 83 focused PHPUnit tests
with 791 assertions on PHP 8.3.6, PHPUnit 12.5.33 and pinned Joomla 6.1.2.
These tests execute emitted PHP against Joomla's native form lifecycle and
verify property placement for Joomla 3, 4, 5 and 6 administrator/site models.

A separate pristine worktree at `2dab8eb3` was tested before comparison. Its
normal full suite has 4,777 tests, 60,018 assertions, 40 errors and 33 failures.
The baseline failures are in ValidationFix (37 errors), AllowView (3 errors,
4 failures), Markdown Html (19 failures), SearchBoundaryAndOrchestration
(9 failures) and ProviderWiring (1 failure). The old ValidationFix fixture
did not resolve deferred Joomla Power references before executing emitted PHP;
the refactored test fixture now does so without changing condition literals.

The separate `node bin/check-conditional-browser.mjs` check executes freshly
emitted JavaScript and PHP against explicit expected results. It passes 37
fixtures and 111 transitions, covering all twelve operators, all four actions,
shared targets, repeated changes, chained predicates, escaping and empty views.
Its small DOM boundary isolates condition behavior; installed GUI tests remain
responsible for real Joomla widgets and AJAX. PHPUnit itself needs no Node
dependency. CI runs this check separately after PHP dependencies are installed.

On remote runtime/template commit `26985b1c`, the public Hello World golden
comparison passed for Joomla 3, 4, 5 and 6; the J6 artifact confirms identical
baseline/candidate trees against `2dab8eb3`. This public fixture does not prove
the new conditional path. The existing API and GUI workflows also passed.
The unit workflow stopped at `composer audit --locked` before PHPUnit, reporting
eight advisories affecting four unchanged locked test dependencies. Those
dependency findings are separate from the local baseline test failures above.

The complete candidate-suite comparison, conditional native compilation and
generated Field integration remain in progress. No JCB self-compilation is
claimed.
