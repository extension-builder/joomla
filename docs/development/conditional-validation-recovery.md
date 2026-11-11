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

Browser parity corrections, complete candidate-suite comparison and generated
output verification remain in progress. No JCB self-compilation is claimed.
