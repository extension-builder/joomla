# Architecture review findings

## Purpose

This is a point-in-time source review, not a refactoring commit and not a claim
that every item is currently exploitable. It records discrepancies and design
constraints that should be reproduced in focused tests before a fix is made.
The branch that introduced this document changes no runtime code.

## Operation and status lifecycle

| Priority | Current observation | Source evidence | Why it matters |
| --- | --- | --- | --- |
| High before long-lived services | Package Get retains categorized results; Tracker, MessageBus, and remote Set settings are shared mutable state in a static factory container. There is no complete per-operation reset. | [`Package\Builder\Get`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Package/Builder/Get.php), [`Package\Service\Power`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Package/Service/Power.php), [`Abstraction\Remote\Set`](../../libraries/vendor_jcb/VDM.Joomla/src/Abstraction/Remote/Set.php) | A reused API/MCP/worker container can carry results, skip markers, messages, or repository settings into the next request. |
| High for automation correctness | Package Builder Set returns `void` and discards the boolean returned by an entity Remote Set handler; CLI Push returns exit code zero after the call. | [`Package\Builder\Set::items`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Package/Builder/Set.php), [`Console\Package\Push`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Console/Package/Push.php) | A failed or partial remote write can appear successful to automation unless messages are separately inspected. |
| Medium | During `Get::reset()`, `resetAssets()` returns before draining `file.get` or `folder.get` when the corresponding capability is absent. | [`Package\Builder\Get`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Package/Builder/Get.php) | A reduced shared container can retain queued reset work for a later operation. |
| Medium | Missing Package capabilities are intentionally skipped after `Container::has()` checks. | Package Get and Set builders | This is useful for reduced containers, but an external API needs an explicit “unsupported/no-op” result rather than implied success. |

**Recommended characterization before design:** execute two unrelated Get and
Set operations through the same factory in one process and record which
results, tracker keys, messages, and repository settings persist into the
second operation. Once a scope/reset contract exists, turn that reproduction
into an isolation assertion.

The compiler has the same scope principle. Its shared generic version aliases
cache their first concrete selection, and resolving `Compiler` immediately
runs Initializer plus legacy Infusion. A long-lived process must validate the
target and create/reset one compiler container per build; a catalog test must
exclude side-effectful `Compiler` instantiation.

Dynamic version keys currently accept the integer supplied by Config but only
J3–J6 services are registered. Unsupported target input can therefore become a
missing service lookup rather than a domain-level validation error.

`Compiler::run()` returns early when extension-file update fails or when
`zipComponent()` fails, whether because ZIP creation failed or because the
final component-directory removal failed. Both top-level paths return before
`endCompilationTimer()`, so failed builds do not execute the nominal
timer-finalization path.

## Filesystem restore trust boundary

| Priority | Current observation | Source evidence | Why it matters |
| --- | --- | --- | --- |
| High review priority | File restore deletes an existing file before writing the replacement. Folder restore deletes the existing folder before writing/unpacking the downloaded archive. | [`GetFile::store`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Package/Remote/GetFile.php), [`GetFolder::store`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Package/Remote/GetFolder.php) | A failed write or unzip can destroy the last valid local copy; the operation is not atomic. |
| High review priority | Normalize canonicalizes slashes but does not collapse `..` segments or prove that the resulting path is contained by the selected base before restore/reset code uses it. | [`Componentbuilder\Utilities\Normalize`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Utilities/Normalize.php), [`Package\Remote\GetContent`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Package/Remote/GetContent.php) | Repository dependency metadata is a supply-chain input. Approved repositories reduce exposure but do not replace containment validation. |

Before API/MCP remote restore is enabled, tests should cover `..`, absolute and
drive paths, mixed separators, expanded Joomla constants, symlink escapes, ZIP
entry traversal, write/unzip failure, rollback, and final real-path containment.

## Data and migration discrepancies

Two mappings near the end of
[`Componentbuilder\Data\Migrator\Guid`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Data/Migrator/Guid.php)
map `class_method.joomla_plugin_group` and
`class_property.joomla_plugin_group` to `class_property`. The authoritative
[`Componentbuilder\Table`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Table.php)
metadata identifies both fields as links to `joomla_plugin_group`. This should
be validated against real migration intent and fixtures before changing data.

The Componentbuilder wrapper also passes an extra argument to the generic
[`Data\Migrator\Guid::process(array $config)`](../../libraries/vendor_jcb/VDM.Joomla/src/Data/Migrator/Guid.php)
contract. PHP currently ignores that redundant argument in this call path, but
the mismatch obscures the interface.

**Future architecture:** move the large declarative mapping catalog out of the
Joomla message adapter. Then validate every declared link automatically against
Table metadata and test the catalog as data.

## Transport and network observations

| Priority | Current observation | Source evidence |
| --- | --- | --- |
| Medium | `VDM.Joomla.Git\Repository\Contents::api()` calls the selected provider but does not return its API URL; Package error formatting receives `null`. | [`VDM.Joomla.Git/Repository/Contents.php`](../../libraries/vendor_jcb/VDM.Joomla.Git/src/Repository/Contents.php), [`Package\Grep`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Package/Grep.php) |
| Medium | `Network\Resolve::active()` can return an uninitialized local when Status throws. | [`Componentbuilder\Network\Resolve`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Network/Resolve.php) |
| Medium | Network API path construction uses `!empty($status)`, so numeric status `0` is omitted. | [`Componentbuilder\Api\Network`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Api/Network.php) |

Provider-neutral Git behavior should have one contract suite run against both
Github and Gitea adapters, including API URL, create/update/get/delete,
credential reset, and error behavior.

## Placement and dependency anomalies

The dominant layering remains sound, but these downward dependencies deserve a
future placement decision:

- `Componentbuilder/Service/CoreRules.php` registers implementations located
  under the compiler. This is the fourth host-version selector and lives
  outside `Compiler/Service`.
- `Componentbuilder/Power/Generator` imports `Compiler\Utilities\Indent`, even
  though code indentation is useful outside the compiler.
- server loaders import the compiler factory.
- root-level remote abstractions type against Componentbuilder Package
  Tracker/MessageBus and Componentbuilder Network/Power contracts. If those
  abstractions are intended for reuse beyond JCB, neutral interfaces would
  better match their location; if not, their current high-level placement may
  overstate generality.

These are not reasons for a broad namespace move. Each requires caller,
compatibility, autoload, and service-provider analysis before action.

## Markdown boundary

[`Componentbuilder\Markdown\Html`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Markdown/Html.php)
escapes the source text, then creates `href` and `src` attributes using regular
expressions. Because link conversion runs first and its expression also
matches the `[alt](src)` portion of image syntax, `![alt](src)` becomes a
leading `!` plus an anchor and image conversion is bypassed. URL schemes are
also not allowlisted. The method's “sanitized” description therefore
overstates both conversion correctness and URL safety.

Repository- or AI-supplied Markdown needs tests for image/link ordering,
malformed markup, entity encoding, and unsafe URL schemes before its output is
treated as trusted HTML.

## Legacy areas not to copy

`Componentbuilder/Extrusion/Helper` remains a small legacy inheritance stack
that performs parsing and database mutation in constructors, accesses Joomla
globals directly, and keeps raw mutable arrays. It has no bounded-context
factory/provider. It should not be used as a pattern for compiler extraction,
API work, or a new service.

## Review order before API, MCP, or AI mutation

1. Enforce `Compiler\Factory::unset()` as the build boundary and introduce an
   equivalent operation scope/reset contract for Package.
2. Make write/push outcomes structured and propagate partial failure.
3. prove path containment and atomic restore/rollback.
4. add entity/factory capability and Git-driver contract tests.
5. validate migrator mappings against Table metadata.
6. add authorization, repository allowlists, redaction, dry-run, and audit
   output at the external application-service boundary.
7. keep AI advisory/planning behavior outside deterministic Compiler and
   Package execution services.
