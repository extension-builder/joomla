# JCB architecture guide

This guide records the architecture of Joomla Component Builder (JCB) as it
exists on the `6.x` branch. It is intended to be the starting point for humans
and coding agents changing the compiler, extracting the remaining legacy
helpers, adding tests, or extending JCB with API, CLI, MCP, and AI-facing
capabilities.

The central design idea is simple: JCB turns structured extension definitions
into native Joomla extensions through a shared, version-aware compiler graph.
Its identity is defined by that controlled transformation, not by any one kind
of extension it can produce. The implementation is deliberately organized
around small domain services, shared state registries, stable interfaces, and
service-provider selectors. Its source-ordered execution stack must be
understood before changing the legacy helper chain.

## Reading order

1. [Project identity](project-identity.md) — what JCB is, the outcome it owns,
   why it is self-hosting, and the enduring tests for an appropriate change.
2. [System map](system-map.md) — library boundaries, namespaces, factories,
   and the rules that determine where a class belongs.
3. [Compiler execution flow](compiler-execution-flow.md) — the exact
   construction, initialization, infusion, materialization, and finalization
   chronology, including deferred work and failure boundaries.
4. [Compiler architecture](compiler.md) — composition, shared state builders,
   Joomla-version dispatch, interfaces, and placement decisions.
5. [Package distribution engine](package-distribution.md) — repository-backed
   import/export of JCB definitions, which is separate from compilation.
6. [Architecture review findings](review-findings.md) — verified
   discrepancies, lifecycle constraints, and trust boundaries to consider
   before API/MCP work.
7. [Legacy helper refactoring playbook](helper-refactoring.md) — evidence,
   extraction boundaries, sequencing, and compatibility constraints for
   `Fields`, `Interpretation`, and `Infusion`.
8. [Helper method inventory](helper-method-inventory.md) — the exact remaining
   API, hidden dynamic calls, state, version branches, events, and dependencies.
9. [Testing and conformance strategy](testing-strategy.md) — a staged path from
   the present baseline to unit, contract, golden-output, and integration tests.
10. [Source inventory](source-inventory.md) — a compact map of the relevant
   folders, service families, versioned implementations, and sibling libraries.
11. [Extrusion engine](extrusion.md) — the reverse direction: the current SQL
   dump contract and the proposed roadmap for extruding an existing component's
   source tree into JCB definitions.

Repository-wide instructions for coding agents are in [`AGENTS.md`](../../AGENTS.md).
Before changing any path, read the authoritative
[repository change boundaries](../development/change-boundaries.md). In
particular, `admin/**` and `media/js/**` require explicit task-specific
permission and a same-change record, while the dependency trees and all other
`media/**` paths are prohibited.
The executable test harness and contribution rules are documented in the
[testing standard](../development/testing.md), and PHP formatting, declaration,
documentation, DI, and provider conventions are defined in the
[PHP code standard](../development/php-code-style.md).

## Scope and evidence

The initial inventory was made from commit
`bca4a1520484f3e2c2fbd12964a5995b0d058de1` on `6.x`. Counts in the inventory
are a point-in-time orientation aid, not architectural limits. Source links in
these documents are relative to the repository and should remain useful as the
code moves.

The documents use these labels:

- **Current contract** describes behavior found in the source and relied upon
  by the running compiler.
- **Placement rule** is inferred from repeated, consistent organization in the
  current tree.
- **Refactoring guidance** describes how to continue the existing migration
  without changing behavior.
- **Proposed test** or **future extension** is not claimed to exist today.

## Non-negotiable architecture invariants

- Preserve generated output, event order, registry paths, placeholder keys,
  side effects, and public behavior while extracting code.
- Preserve the construction and execution chronology. Resolving `Compiler`
  already initializes the component and builds content before `run()` starts
  materializing and packaging the result.
- Select code-generation variants by the **target Joomla version** and
  runtime integrations by the **host Joomla version**. These axes are not
  interchangeable.
- Put persistent build state in a focused shared builder/registry service, not
  in new arrays on orchestrators and not in a single global registry.
- Inject dependencies into new classes. A factory is the composition entry
  point and a compatibility bridge for legacy code, not a reason to add new
  service-locator calls.
- Keep a stable logical service alias in front of Joomla-specific concrete
  implementations and implement a shared interface where variants exist.
- Place broadly reusable abstractions high in the tree and objective-specific
  implementations beside the domain they serve.
- Keep the compiler and Package distribution engine conceptually separate,
  even though the compiler container imports Package **Get** capabilities.
- Keep Super Powers and Joomla Powers distinct. Preserve the established power
  placeholders and resolution pipeline in generated code.
