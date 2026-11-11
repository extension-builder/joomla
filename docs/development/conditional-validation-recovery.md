# Conditional validation recovery

## Baseline and purpose

This patch starts at `2dab8eb3249185d18b11a55694adb559c349addc` on
`jcb-recovery-version`. It improves the compiler's conditional form validation
for generated components. Components without conditional requirements must not
receive an unused validator, model property, or validation override.

The implementation is delivered in separate commits for generated runtime
structure, model/template integration, condition behavior, generated previews
and verification. [PR #68](https://github.com/extension-builder/joomla/pull/68)
targets the recovery branch. This document records the source boundary and
actual verification; no JCB self-compilation is claimed.

## Generated architecture

- `Architecture.Model.ValidationFix` emits a documented multiline model
  property, a short native validation wrapper, and a named stored-data resolver.
- `Architecture.Model.ConditionalRule` emits the shared runtime implementation
  into `ConditionalrequiredRule`, with named lifecycle and matching methods.
- `Compiler.Builder.Validation.Fix` remains the shared normalized definition
  store. The browser's submitted `not_required` field is never an authority.
- Native model plugins, filtering, required checks, validation and
  post-processing retain their order and use the original form instance.
- Temporary rule registration and required attributes are restored on every
  exit. State is isolated by the exact form object.

The generated rule must run after filtering and before target required checks.
The internal first-position XML rule field supplies that boundary; its
installation and removal belong inside the rule implementation.

### The live form remains authoritative

`getForm()` and native plugins already apply permissions and form changes.
Conditional validation receives that same Form object and performs no ACL
lookup of its own. It cannot restore a requirement that the live form has
removed: absent fields remain absent, and optional, disabled or `filter="unset"`
targets cannot be promoted back to required. Joomla continues to filter unset
values and reject submitted disabled values through its native lifecycle.

The requirement cap follows the pinned native validation flag semantics;
`type="hidden"` or `readonly` alone is not a required-field exemption. Regression
tests execute the existing GetForm permission emitter first, then pass that
exact Form into the generated validator. Separate cases compare native and
conditional validation for the supported required-attribute representations.
This preserves the prior form decision instead of duplicating its ACL logic.

## Browser and server contract

Both sides evaluate the same normalized definitions in source order. Every
browser refresh starts with the original display state and the authored
required flags, then evaluates all groups. The last applicable action on a
shared target wins. The generated `update<ViewCode>Conditions()` function is
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

Numeric option values use `Number('...')` instead of bare JavaScript literals.
This fixes octal interpretation of `010` and invalid leading-zero decimals such
as `01.2`. The server's named numeric matcher follows the scalar conversion
rules for decimal exponents, non-decimal prefixes, Infinity and surrounding
whitespace, while rejecting trailing junk, signed non-decimal prefixes and separators.
This preserves numeric comparisons without accepting PHP's different string
coercion rules. The reference is ECMAScript's
[StringToNumber](https://tc39.es/ecma262/multipage/abstract-operations.html#sec-stringtonumber)
and [White Space](https://tc39.es/ecma262/multipage/ecmascript-language-lexical-grammar.html#sec-white-space)
definitions.

Generated condition actions no longer send a `not_required` exemption list.
Only custom scripts that reference `updateFieldRequired` receive a deprecated,
inert compatibility function. It carries no server validation authority.
Optional supplied values still undergo Joomla's normal type and format checks.

## Generated names

Generated runtime symbols use generic names: `conditionalRequirements`,
`resolveConditionalData()`, `ConditionalrequiredRule`, and the
`conditionalrequired` form-rule identifier. The internal field is named
`__conditional_required`. Browser refresh functions use the view name, such as
`updateFieldConditions()` and `updateFieldConditionsBaseline`. The existing
`Unique::get(7)` sequence still names the individual condition functions and
their variables; this rename introduces no alternative naming mechanism.

The initial compilation and GUI evidence below records the implementation
before this naming follow-up. Its source identities and hashes remain the
historical evidence for those exact runs. Follow-up verification is recorded
separately after the renamed output is checked.

## Conditions and source ownership

The supported selection, presence, contains, length and chained-condition
definitions are compiler inputs. Their generated browser and server behavior
must be verified together. Changes to an operator's meaning require explicit
regressions and documentation, separate from mechanical extraction.

The recovery baseline's checked-in Field model contains five structured groups. The
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

The Field preview demonstrates a migration using the existing conditions editor: add a final,
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

## Recovery suite repairs

The stability follow-up repairs three existing library defects exposed by the
normal unit suite, separately from conditional validation:

- Markdown conversion handles images before links and keeps generated tags
  intact, so linked images and bracketed alternative text are not corrupted.
- Search bundles use ascending IDs, matching their continuation cursor; this
  prevents duplicate or skipped rows and nonterminating pagination. Unlimited
  queries retain their existing title ordering.
- The `Crypt.FOF` provider constructs the constructorless adapter directly,
  without resolving unused constructor dependencies.

The existing focused tests reproduced 29 failures before these repairs and
pass afterward: 61 tests and 3,620 assertions. No assertions were weakened.
Dependency-audit and fixture updates are delivered in a separate commit. The
test lock updates only `enshrined/svg-sanitize` (1.1.1), `symfony/http-client`
(7.4.23), `symfony/validator` (6.4.50) and `symfony/yaml` (6.4.50). Composer
strict validation, platform requirements and the locked audit pass. Native
Joomla MediaHelper checks retain valid SVG handling and reject executable or
malformed SVG input.

The AllowView fixture now resolves its deferred NotAllowed Power to the native
class before executing generated code. Its existing 20 behavior tests pass
with 131 assertions; no production authorization behavior or assertion changes.
The existing known-defect recovery audit also passes with no clean quarantined
tests left behind. None of the workflow gates was disabled.

After the generic renames, live-form correction and suite repairs, the normal
local suite passes **4,871 tests and 59,241 assertions**, with zero errors,
failures or skips. The PHPUnit coverage declaration for the preview fixture
helper was removed because test helpers are outside the production coverage
filter; both production emitters remain declared coverage subjects and every
preview assertion remains executable. This resolves the PHP 8.4 coverage-only
warning found after its behavior tests had passed. Hosted workflow results are
tracked on PR #68; the initial results below describe the earlier implementation.

## Initial verification status

Native validation tests use PHP 8.3.6, PHPUnit 12.5.33 and pinned Joomla 6.1.2.
They execute emitted PHP against Joomla's form lifecycle and verify property
placement for Joomla 3, 4, 5 and 6 administrator/site models.

A separate pristine worktree at `2dab8eb3` was tested before comparison. Its
normal full suite has 4,777 tests, 60,018 assertions, 40 errors and 33 failures.
The baseline failures are in ValidationFix (37 errors), AllowView (3 errors,
4 failures), Markdown Html (19 failures), SearchBoundaryAndOrchestration
(9 failures) and ProviderWiring (1 failure). The old ValidationFix fixture
did not resolve deferred Joomla Power references before executing emitted PHP;
the refactored test fixture now does so without changing condition literals.

The complete candidate suite runs 4,852 tests and 61,338 assertions. Comparing
JUnit test identities against the pristine baseline shows **zero new errors or
failures**, with 37 old ValidationFix errors removed. Three AllowView errors and
the same 33 unrelated failures remain. No tests were quarantined or disabled.
The existing VersionedViewGuards expectation for a browser-only field list was
updated to require no unused override/property, matching the explicit new
generation contract; its 15 checks pass with 16 assertions.

| Full normal suite | Tests | Assertions | Errors | Failures |
| --- | ---: | ---: | ---: | ---: |
| Pristine recovery `2dab8eb3` | 4,777 | 60,018 | 40 | 33 |
| Candidate including generated previews | 4,852 | 61,338 | 3 | 33 |

The core, template and browser-emitter checks together pass 194 PHPUnit tests
with 1,113 assertions after the numeric conversion corrections.
The nine-model preview tests separately pass 11 tests and 494 assertions,
including exact emitter parity and the original-five/proposed-six Field cases.

The separate `node bin/check-conditional-browser.mjs` check executes freshly
emitted JavaScript and PHP against explicit expected results. It passes 46
fixtures and 142 transitions, covering all twelve operators, all four actions,
shared targets, repeated changes, chained predicates, escaping and empty views.
Its small DOM boundary isolates condition behavior; installed GUI tests remain
responsible for real Joomla widgets and AJAX. PHPUnit itself needs no Node
dependency. CI runs this check separately after PHP dependencies are installed.

All nine installed conditional models and the shared rule now reproduce the
reviewed normalized input through the real emitters. The Field browser prefix
and listeners also reproduce exactly. Both preview commands are CI checks.

### Installed application and CI

The following runs tested remote implementation head
`2adeebb4c50907fe5d0509e12ed882e6b2e585dc` through merge commit
`a11569627ac9d94e3824fdc133a4208498aa3cd1`, with recovery `2dab8eb3` as the base:

| Workflow | Result |
| --- | --- |
| [GUI tests](https://github.com/extension-builder/joomla/actions/runs/43081242615) | Pass: 40 tests, zero failures, skips or flaky results |
| [API tests](https://github.com/extension-builder/joomla/actions/runs/43081242638) | Pass: native installation, compilation and compiled demo JSON:API checks |
| [Compiler golden master](https://github.com/extension-builder/joomla/actions/runs/43081242690) | Pass for Joomla 3, 4, 5 and 6 |
| [Vendor JCB unit tests](https://github.com/extension-builder/joomla/actions/runs/43081242648) | Dependency audit fails before PHPUnit on PHP 8.3 and 8.4; see below |

The GUI run covers all nine conditional administrator views. Field includes
expert storage, Note/Spacer transitions back to Text, Other/Expert child
requirements, numeric-zero defaults and a delayed real AJAX response. The
installed-site HTTP proof and all 4,951 installed-file checksum checks pass.
All nine models, the rule, the Field template and Field JavaScript match the
reviewed implementation exactly. No manual browser execution is claimed.

The unit workflow's new browser/server comparison and both preview checks pass
on PHP 8.3 before the existing `composer audit --locked` step. That step reports
eight advisories affecting four unchanged locked test dependencies:
`enshrined/svg-sanitize`, `symfony/http-client`, `symfony/validator` and
`symfony/yaml`. PHPUnit is skipped in that workflow. No audit gate was disabled
and no dependency versions were changed. These dependency findings are separate
from the local baseline test failures recorded above.

The changed-file PHP style guard passes for 18 files. Declaration ownership
passes with 1,487 declarations owned and zero debt. Container-key checks pass
with 945 keys and five existing recorded exceptions. The moved-conditions
check, 40 golden-harness self-tests and `git diff --check` also pass.

### Full conditional component compilation

A disposable copy of the public Hello World definition was extended through
ordinary field-condition inputs. Its Greeting view includes two selectors,
two required targets, an AND relation, historical multi-option Is Not behavior,
and a final positive Note/Spacer Hide Only group. This exercises the conditional
path through the complete compiler, beyond the standard public fixture above.

Baseline and candidate compiled the same restored database snapshot separately
for Joomla 3, 4, 5 and 6. The candidate was installed through Joomla's native
installer from its complete committed package; all 5,407 checked deployed
library, model, template, form, API and JavaScript files matched that package.
The measured compilations used existing offline repository configuration and
disabled outbound PHP transports. No external compilation request or mocked
Power records were used.

| Target | Complete output files | Byte-identical files | Changed files | Added / removed |
| --- | ---: | ---: | ---: | ---: |
| Joomla 3 | 1,366 | 1,357 | 9 | 0 / 0 |
| Joomla 4 | 288 | 279 | 9 | 0 / 0 |
| Joomla 5 | 293 | 284 | 9 | 0 / 0 |
| Joomla 6 | 249 | 240 | 9 | 0 / 0 |

No output normalization was applied. The nine changed files in each target
are the administrator and site-edit models, shared conditional rule, two edit
templates, two condition JavaScript files and two README files. The README
changes are generated line-count/build-effort statistics. The model/rule and
browser/template differences correspond to the documented architecture and
ordered-condition changes. Power completion records are identical in every
pair: 65 final Powers, including one late dependency.

All generated PHP and JavaScript files in the four candidate packages pass
syntax checks: 1,355 PHP files and 70 JavaScript files in total. A pre-existing
Joomla 3 Dashboard/View.php `mainAccordionName` warning also occurs on the
baseline; it is not introduced by this patch.

The compact [compilation evidence](conditional-validation-evidence/README.md)
records source identities, fixture inputs, snapshot digest, complete changed
paths, output-manifest digests, syntax counts and reproduction instructions. The
database snapshot and site configuration are deliberately not published.
This proves same-input full component compilation; it does not substitute for
transferring the Field definition into JCB's unavailable maintenance package
and performing a JCB self-build.
