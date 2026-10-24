# Compiler emission convention repair

Review date: 24 October 2026. Scope: the runtime compiler under
`libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler`, including existing
shared and earlier-target emitters as requested during PR #60 review.

## Required convention

Compiler-owned code is assembled line by line using `PHP_EOL . Indent::_(n)`.
Generated logic comments use `Line::_(__LINE__, __CLASS__)`. Configurable
indentation must reach every generated indentation level. The host PHP follows
the repository's tab-based style. No new renderer, template location, or block
normalization layer is introduced.

## Corrections

| Area | Repair |
| --- | --- |
| Joomla 6 views and assets | Replace raw PHP/JavaScript blocks and hardcoded indentation in footer capture, loading overlays, ordering, Footable, toolbar, submit handlers, library loading and module wrappers. WAM registration, dependencies and activation retain their behavior. |
| Shared model generation | Replace PATCH, conditional-required and validation nowdocs with explicit lines. Generate callback indentation directly instead of replacing indentation after rendering. Preserve authored multiline condition values. |
| Configuration and helpers | Generate DKIM JavaScript with configurable indentation; replace literal alignment tabs in generated model/helper/docblock output. |
| Plugins and installers | Generate native subscription/privacy scaffolding with Indent and Line; retain deferred Power resolution and installer lifecycle contracts. |
| SQL and Power support | Use PHP_EOL and Line for generated SQL structure/comments and Indent for Power configuration files. Preserve quoted SQL values, batch boundaries and Apache/IIS directives. |
| Earlier-target emitters | Restore missing generated comment provenance in module defaults, field buttons and helpers; expand the compact missing-field return and remove stray authorization-guard spaces. Compatibility APIs remain unchanged. |

The audit scanned all 852 runtime compiler PHP files with PHP tokens, then
reviewed candidate literals and comments in context. Static templates,
translation prose, diagnostic HTML, parser/decoder strings and opaque authored
payloads are data, not generated code to rewrite. Existing Indent-aware line
arrays, including by-reference extension-event contracts, are preserved.

Power placeholders remain in emitted code and retain their normal import and
collision handling. Complete placeholders in compiler source literals are split
to prevent premature resolution during JCB self-compilation. No cryptography or
FOF behavior is changed.

## Regression protection and output review

Focused tests exercise tabs, two spaces and four spaces; enabled/disabled debug
comments; executable generated PHP and JavaScript; authored payload preservation;
and existing native Joomla behavior. The contribution style checker rejects
heredoc/nowdoc in runtime compiler source and complete Power placeholders in
source literals. A repository-wide test applies those token rules to every
compiler file. These narrow checks supplement emitter tests and review; they do
not attempt to interpret arbitrary stored code or translation text.

Exact generated-output comparisons remain enabled. Expected formatting changes
include SQL/model line endings, configurable Power configuration indentation,
DKIM indentation, expanded field-button JavaScript and Joomla 6 inline asset
serialization. Earlier-target API behavior is preserved, although this explicit
compiler-wide repair can change formatting for Joomla 3, 4 and 5. Debug-enabled
source references change when an emitter line moves.

Hosted workflow evidence and exact reviewed output expectations are recorded in
the PR checks and `.github/golden-master/expected/README.md`. No whitespace
normalization, broad exclusions or new known-defect quarantine is used to pass
those checks.

## Verified results

The implementation is `6e00a7259d558c4c5d4e7f9dbf5e58a4705e1e99`.

- [PHP 8.3 and 8.4](https://github.com/extension-builder/joomla/actions/runs/40088586100):
  5,129 tests / 63,591 assertions pass on each runtime. Style passes for 157
  changed files; all 1,545 production files retain test ownership. Container and
  moved-condition checks pass; the separate 84-case known-defect audit is unchanged.
- [Installed API](https://github.com/extension-builder/joomla/actions/runs/40088586090):
  222 CRUD, permission and partial-PATCH checks pass.
- [Browser and JavaScript](https://github.com/extension-builder/joomla/actions/runs/40088586116):
  29 Playwright journeys and five DKIM JavaScript checks pass.
- The complete measured diffs for all four targets were reviewed and encoded
  without alteration in the existing golden-master expectation mechanism.
  They reproduce the archived candidate trees exactly; generated PHP syntax,
  deployed compiler-source hashes and unchanged Power evidence were verified.
  The [review record](../../.github/golden-master/expected/README.md#compiler-emission-convention-repair-across-targets)
  identifies every output difference, artifact and tree. Final workflow results
  are on [PR #60](https://github.com/extension-builder/joomla/pull/60/checks).
