# Repository guidance for coding agents

This repository contains Joomla Component Builder (JCB), a compiler that builds
native Joomla extensions from structured definitions. Treat it as an
enterprise compiler and distribution system: observable output and state-flow
contracts matter as much as individual return values.

These instructions apply repository-wide unless a more specific `AGENTS.md`
exists below the files being changed.

## Read before changing compiler or Package code

Read the architecture guide in this order:

1. [`docs/architecture/system-map.md`](docs/architecture/system-map.md)
2. [`docs/architecture/compiler.md`](docs/architecture/compiler.md)
3. [`docs/architecture/package-distribution.md`](docs/architecture/package-distribution.md)
4. [`docs/architecture/review-findings.md`](docs/architecture/review-findings.md)
5. [`docs/architecture/helper-refactoring.md`](docs/architecture/helper-refactoring.md)
6. [`docs/architecture/helper-method-inventory.md`](docs/architecture/helper-method-inventory.md)
7. [`docs/architecture/testing-strategy.md`](docs/architecture/testing-strategy.md)

Use [`docs/architecture/source-inventory.md`](docs/architecture/source-inventory.md)
for orientation, then verify every assertion against the current source. The
inventory is a snapshot, not a substitute for reading callers and providers.

## Architecture invariants

### Preserve compiler behavior

- Preserve generated paths and contents, placeholder keys, builder-registry
  paths and value shapes, event names/arguments/order, messages, filesystem
  side effects, and archive behavior unless a change explicitly targets one of
  them.
- Keep mechanical extraction separate from intentional behavior change.
- JCB builds itself. A successful local method test is not proof that the
  resulting extension tree is equivalent.

### Use the correct Joomla-version axis

- Use `Joomla\CMS\Version::MAJOR_VERSION` for behavior that integrates with
  the Joomla instance currently running JCB.
- Use `Compiler\Config->joomla_version` for code and structures generated for
  the requested target Joomla major.
- Do not infer the axis from a nearby class. Trace what the behavior acts on.
- Stable callers should depend on one logical alias/interface. A service
  provider selects `J3`, `J4`, `J5`, or `J6`; callers should not add inline
  major-version conditionals.
- Validate external target input against the supported catalogue before any
  dynamically composed version service is resolved.

### Follow domain-local placement

- Put broadly reusable abstractions, interfaces, and infrastructure high in
  `VDM.Joomla/src`.
- Put compiler-only behavior under the narrowest accurate
  `Componentbuilder/Compiler/<Domain>` objective.
- Put shared generated-architecture mechanics under
  `Compiler/Architecture/<Objective>` and target implementations under
  `Compiler/Architecture/JoomlaThree|JoomlaFour|JoomlaFive|JoomlaSix/<Objective>`.
- For every version-dispatched family, retain all four J3/J4/J5/J6 service keys
  and version identities. When output is currently identical, keep common
  mechanics outside the version folders and use thin variants or delegates.
- Put repository distribution behavior under
  `Componentbuilder/Package/<Entity>`. Do not place compilation/rendering in
  Package or repository synchronization in the compiler helper.

### Inject services and isolate state

- New and extracted classes declare typed constructor dependencies and are
  created by the closest service provider.
- Do not add `Compiler\Factory::_()` calls to new classes. Static factory
  resolution is a composition entry point and a temporary legacy bridge.
- Register mutable compiler state as a shared, focused Builder registry. Do not
  recreate historical arrays on an orchestrator and do not collect unrelated
  state in one global registry.
- Preserve builder separator, normalization, and set/add semantics.
- The compiler container remains process-static until
  `Compiler\Factory::unset()`. Treat it as one build scope and unset it between
  independent builds. Package has no equivalent factory reset API; a
  long-lived consumer must introduce an explicit reset/scope or construct a
  per-request container outside the static factory.
- Resolving the `Compiler` service starts initialization and legacy infusion;
  it is not a safe no-side-effect container-catalog probe.

### Preserve Powers contracts

- Super Powers and Joomla Powers are separate domains and catalogs.
- Preserve generated `Joomla___…___Power` placeholders and the existing Joomla
  Power resolution pipeline. Do not silently replace one with a runtime-native
  Joomla class import during unrelated work.
- Reuse the existing entity router and appropriate Power/JoomlaPower factory;
  do not add parallel maps.

## Refactoring the legacy helper chain

`Helper\Fields`, `Helper\Interpretation`, and `Helper\Infusion` are deprecated
but remain active ancestors of the final compiler. Do not treat deprecation as
dead code.

For each extraction:

1. Start from one public behavior or one cohesive generated artifact.
2. Build the complete internal call cluster, including protected helpers.
3. Inventory literal and dynamic factory resolutions, Config reads/writes,
   builder paths, by-reference mutations, events, messages, filesystem calls,
   and Joomla-version branches.
4. Identify an existing destination by generated objective and reuse its
   interface/provider/builder patterns.
5. Add characterization or golden-output coverage before changing the path.
6. Implement the new injected service and register it as shared where it holds
   or coordinates build state.
7. Leave the old public method as a narrow delegate while callers still use it.
8. Migrate callers, compare generated trees for every applicable target, then
   remove the wrapper only when references are gone.

Do not split merely by line count. A method and the private/protected methods
that share its state or output belong in one coherent move unless a tested
interface already separates them.

## Service-provider conventions

- Alias the class/interface to the stable logical service key.
- Use `share(..., true)` for the existing singleton-per-container lifecycle
  unless the service is intentionally transient and stateless.
- Keep concrete version keys consistent with the surrounding provider.
- Select version variants in the provider, not in consumers.
- Inject stable aliases/interfaces into downstream constructors.
- If registering a new distributable entity, update the authoritative
  `Componentbuilder\Factory` entity map before adding Get/Set services.

## Package safety and extension rules

- Treat the Package DI container as a capability registry: guard optional
  handlers consistently with the existing builders.
- Preserve tracker queue names, shapes, removal timing, recursion, categorized
  results, and message-bus behavior.
- Any API/MCP surface must validate canonical entities and repository settings;
  it must not expose arbitrary container aliases.
- Constrain remote file/folder operations to approved roots and test traversal,
  absolute-path, mixed-separator, and symlink cases before expanding remote
  write/restore behavior.
- Package's static factory has no reset API. Before using Package in a
  long-lived process, introduce an operation scope/reset contract or construct
  a per-request container outside that factory so results, queues, messages,
  and settings cannot leak between requests.

## Testing expectations

The documented baseline has no first-party test harness. Until one is added,
perform the strongest available verification and state the gap explicitly.

For compiler changes, the target standard is:

- unit tests for extracted services and registries;
- provider tests for host and target selectors;
- characterization tests for legacy wrappers;
- normalized full-tree golden comparisons for every affected Joomla target;
- Package dependency-recursion tests for distribution changes; and
- Joomla install/smoke coverage when packaging or runtime integration changes.

Never update a golden fixture before explaining and reviewing every semantic
diff.

## Change hygiene

- Keep commits coherent: architecture/mechanical extraction, tests, and
  intentional behavior changes should be independently reviewable.
- Cite source paths and service aliases in design notes and PR descriptions.
- Document why the destination namespace is correct and which version axis is
  used.
- Do not edit unrelated generated output or broad formatting while extracting
  a cluster.
- Run whitespace/diff checks and all available tests before committing.
- If PHP tooling is unavailable, say so; do not claim PHP validation from
  Markdown or textual checks.
