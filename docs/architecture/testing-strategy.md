# Testing and conformance strategy

## Current baseline

At the documented source baseline, no first-party PHPUnit/PHPStan/Psalm
configuration or repository workflow is present outside vendored dependencies.
This document is therefore a proposed staged strategy, not a description of an
existing test suite.

The first goal is not maximum coverage. It is a deterministic safety harness
around the contracts most likely to change during helper extraction:

- generated file content and structure;
- placeholder and builder-registry paths;
- target-versus-host Joomla selection;
- event names, order, and arguments;
- language and custom-code side effects;
- Package dependency recursion; and
- cleanup, file, repository, and archive boundaries.

## Test layers

| Layer | Subject | Primary value |
| --- | --- | --- |
| Pure unit | Small generators, normalizers, resolvers, registries | Fast local feedback and edge-case coverage |
| Service contract | Interfaces and stable aliases across J3/J4/J5/J6 implementations | Proves interchangeable variants and selector correctness |
| Provider/container | Service registration, sharing, dependency construction, factory reset | Detects broken aliases and state-lifecycle regressions |
| Golden compilation | Representative definition fixture to normalized generated tree | Protects exact compiler output during extraction |
| Package integration | Entity Get/Set with in-memory/fake repository and dependency graph | Protects recursive distribution behavior |
| Joomla smoke | Install or invoke generated output on supported Joomla/PHP combinations | Detects host integration and packaging failures |

Unit tests alone are insufficient for a source compiler: a method may return
the expected fragment while writing it to the wrong placeholder, in the wrong
order, or for the wrong target. Golden and integration tests close that gap.

## Deterministic golden compilation

A golden fixture should include enough features to exercise the compiler's
major seams without trying to reproduce the full JCB component at first:

- one component with one admin single/list view;
- fields covering core and custom field generation;
- ACL, category, filter, batch, toolbar, and routing behavior;
- one site or custom-admin view;
- one module and one plugin;
- language strings and a custom-code insertion;
- one Power and one Joomla Power reference; and
- install/update/uninstall metadata.

For each supported target, compile the same definition and compare a manifest
of relative paths plus normalized file contents. Normalize only values that
are intentionally non-deterministic, such as configured output roots or an
explicit build timestamp. Do not normalize semantic differences, whitespace
that ships in generated code, placeholder expansion, archive paths, or event
effects merely to make a test pass.

Store fixtures separately from expected output so the input definition is not
mistaken for a generated result. A failure report should show added, removed,
and changed paths before showing content diffs.

## Joomla version matrix

Test selection and generated output as two dimensions:

| Scenario | Host axis | Target axis | Assertion |
| --- | --- | --- | --- |
| Runtime integration | J3/J4/J5/J6 where supported | irrelevant or fixed | Event, History, and installed-core introspection select host implementation |
| Code generation | one supported host | J3/J4/J5/J6 | Header, Architecture, Extension, Field output, module, and plugin services select target implementation |
| Cross-axis guard | host differs from target | a different supported major | Host services remain host-bound and generators remain target-bound |
| Default target | each supported host | no explicit target input | Config defaults the target to the host major |

Not every CI job must run the full Cartesian product on every commit. Fast
provider tests should cover every selector combination; slower Joomla smoke
jobs can use a risk-based matrix plus a scheduled full matrix.

## Registry contract tests

Every builder used by extracted code should have focused tests for:

- its separator and path normalization;
- `set`, `add`, `get`, `exists`, and `remove` semantics actually used by the
  compiler;
- array versus string accumulation;
- placeholder normalization in ContentOne/ContentMulti;
- service sharing within a build; and
- isolation after `Compiler\Factory::unset()`.

When migrating a legacy array, capture representative key/value shapes before
the move and assert the same registry state afterward.

## Versioned service contract tests

For each stable logical interface:

1. construct every concrete Joomla implementation with test doubles;
2. assert it implements the same interface;
3. exercise shared behavior and variant-specific fixtures;
4. construct the provider with Config targeting each major;
5. assert the logical alias resolves the expected concrete class; and
6. separately test any host-major selector.

These tests should fail if a new Joomla major is added to Config but omitted
from an applicable provider.

## Helper extraction characterization tests

Before moving a cluster from `Fields`, `Interpretation`, or `Infusion`:

1. capture its direct return value;
2. snapshot every builder path it mutates;
3. record Config values it temporarily changes and verify restoration;
4. record event/message calls and their order;
5. include each meaningful branch and Joomla target; and
6. compare generated artifacts before and after delegation.

A compatibility wrapper test should call the old public method and the new
service with identical fixtures. Both must produce the same return value and
observable state. Remove the wrapper only after its callers have migrated.

## Package tests

Use fake entity Remote Get/Set handlers in a Joomla DI container to verify:

- central entity-to-area routing;
- capability-driven skipping of missing services;
- alias-to-GUID resolution;
- `local`, `added`, and `not_found` result merging;
- recursive entity dependency discovery;
- file and folder queue draining;
- reset recursion along inbound (`direction: in`) dependency edges, excluding
  outbound/parent edges;
- message-bus output; and
- reproduction of operation-state leakage through the current static factory,
  followed by isolation tests for any explicit scope/reset contract once one
  is introduced.

Filesystem tests must use a dedicated temporary root. Add adversarial path
fixtures (`..`, absolute paths, mixed separators, symlink escapes where the
platform permits) before exposing Package operations through an API or MCP
surface.

## Proposed rollout

### Stage 1 — harness and contracts

- Add project-owned test bootstrap and coding/static-analysis configuration.
- Test the VDM registry and factory lifecycle used by the compiler.
- Add provider tests for both Joomla version axes.
- Add one minimal golden component fixture for one target.

### Stage 2 — legacy seam

- Add characterization coverage for the next coherent helper cluster.
- Expand the golden fixture to every supported target.
- Add Package Get/Set recursion tests.

### Stage 3 — extraction cadence

- Require characterization plus unit/contract coverage in each extraction PR.
- Grow fixtures by compiler objective, not by arbitrary line coverage.
- Run fast tests per change and the full compilation matrix on merge/schedule.

### Stage 4 — platform confidence

- Install generated packages in Joomla test environments.
- Exercise administrator and CLI compile entry points.
- Add API/MCP operation-scope, authorization, and path-containment tests when
  those surfaces are introduced.

## Pull-request evidence

A compiler refactoring PR should report:

- source cluster moved and destination rationale;
- old and new service dependencies;
- builder paths and events touched;
- host/target versions exercised;
- golden-output diff result;
- intentional behavior changes, if any, isolated from mechanical extraction;
  and
- factory/provider registrations added or removed.

“No output difference” should be demonstrated by a normalized tree comparison,
not asserted from visual inspection alone.
