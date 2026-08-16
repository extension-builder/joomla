# Legacy helper refactoring playbook

## Objective

This document prepares the extraction of the remaining deprecated helper chain.
It does not authorize a big-bang rewrite and does not prescribe destinations
from method names alone. Its purpose is to preserve the compiler's current
architecture and behavior while code moves into injected, domain-local
services.

The active chain is:

```text
Helper\Fields
  └─ Helper\Interpretation
       └─ Helper\Infusion
            └─ final Componentbuilder\Compiler
```

All three helper classes carry `@deprecated 3.3`, but they are not dead. The
final compiler still calls the `Infusion` constructor after the injected
Initializer runs. `Infusion` then invokes `buildFileContent()`, and that method
calls a large portion of the inherited public API.

## Baseline scale

Point-in-time textual inventory at the documented baseline:

| Helper | Approximate lines | Declared methods | Static factory resolutions | Unique service-key expressions |
| --- | ---: | ---: | ---: | ---: |
| Fields | 822 | 9 | 67 | 12 |
| Interpretation | 18,241 | 175 | 1,381 live calls | 106 key expressions |
| Infusion | 2,549 | 4 | 670 | 60 |

Interpretation's 106 expressions comprise 103 complete literal service keys
and three dynamic expression shapes. Its 1,381 live-call count is the 1,385
textual occurrences minus four occurrences in comments or strings.

`Infusion::buildFileContent()` occupies most of its class and calls scores of
inherited Interpretation/Fields methods. This means line count alone badly
understates coupling: the orchestration method depends on the helper API,
shared factory services, and implicit Config/builder state at once.

More precisely, `buildFileContent()` spans about 2,225 lines, contains 610
factory calls, and has 111 direct helper call sites targeting 86 methods. One
dynamic second-pass call site has `setLinkedView` as its only known queued
target, giving 87 known targets across 112 syntactic call sites. The complete
method/state/event/version inventory is in
[helper method inventory](helper-method-inventory.md).

Roughly 95% of compiler-tree static factory resolution is concentrated in
these three files. Outside Helper, the service-provider and constructor-
injection migration is therefore already overwhelmingly established; the
legacy chain is the exception, not the model for new compiler code.

## Why extraction is delicate

### Observable output is distributed

A helper method can affect compilation by:

- returning a generated source fragment;
- setting or appending a ContentOne/ContentMulti placeholder;
- writing another focused Builder registry;
- changing Config temporarily so a downstream generator behaves differently;
- mutating an argument by reference;
- adding language strings or custom-code fragments;
- triggering events or messages; or
- creating/updating files and install metadata.

Tests must capture all applicable channels, not only the direct return value.

### Public does not mean independently movable

Many public methods call protected helpers, use public helper properties as
scratch state, or are called by `buildFileContent()` in a strict order. Build a
method cluster before selecting a destination. Moving only the visible method
can retain hidden dependence on the legacy object and produce an apparent new
class that still requires the superclass.

### Public compatibility can extend beyond this repository

Inside this repository, administrator and CLI entry points call only the final
compiler's `run()` method. Compiler events, however, pass the compiler object to
extensions, and consumers outside this tree may call inherited public methods
or read public properties. Keep a delegating compatibility method during
migration unless the external API break is explicit and versioned.

### Config is mutable state

The helper frequently reads `Config`, and `Infusion` changes values such as
`build_target` and `lang_target` while iterating objectives. Extracted services
must preserve both the value seen by callees and restoration timing. Avoid
capturing a mutable Config value too early in a constructor when the current
flow reads it later.

## Existing extraction precedent

Repository history shows the same migration already succeeding for the former
`Helper\Get` and `Helper\Structure` ancestors. Before their removal:

- raw counters moved to `Utilities.Counter`;
- path and file collections moved to `Utilities.Paths` and `Utilities.Files`;
- content arrays moved to ContentOne/ContentMulti and focused builders;
- component, library, Power, module, and plugin structure work moved to their
  corresponding `*/Structure` services;
- small legacy methods delegated to services such as `Model.Createdate`,
  `Extension.MoveFieldsRules`, and `Utilities.Dynamicpath`; and
- constructor orchestration was replaced by the injected `Initializer` and
  explicit structure services.

The obsolete ancestors could then be removed from the active chain. Continue
that approach: move state first or with its complete behavior, delegate across
a compatibility seam, migrate callers, and shorten the inheritance chain only
after the seam is unused.

Specifically, commit `636c708b836f374a49393744eb6619bed88f6ee8` removed the
2,745-line Get and 823-line Structure helpers, added the current Compiler and
Initializer, reduced Fields from 1,609 to 822 lines, and reduced Interpretation
from 21,218 to 18,975 lines. Later extractions brought Interpretation to 18,241
lines at this documentation baseline. The historical helper methods carried
explicit replacement annotations; that is the precedent for temporary
forwarders in the next extraction phase.

### Extraction progress

The following Interpretation methods have been extracted into injected
services and remain in the helper only as delegating shims:

| Legacy methods | Replacement service |
| --- | --- |
| `setLockLicense`, `setLockLicensePer`, `checkStatmentLicenseLocked`, `setBoolLicenseLock`, `setHelperLicenseLock`, `setInitLicenseLock` | `Architecture.Component.LicenseLock` |
| `setWHMCSCryption` | `Architecture.Component.Whmcs` |
| `setGetCryptKey` | `Architecture.ComHelperClass.CryptKey` |
| `setVersionController`, `setDynamicUpdateXMLSQL`, `setUpdateXMLSQL` | `Extension.VersionUpdate` (the shims synchronize the legacy public `$lastupdateURL` property with the service state in both directions) |
| `setHelperExelMethods` | `Architecture.ComHelperClass.ExcelMethods` (J3/J4/J5/J6 target family; the lost Joomla 4+ user assignment is preserved and recorded in the known-defect ledger) |
| `setUikitHelperMethods` | `Architecture.ComHelperClass.UikitMethods` |
| `setAdminViewMenu` | `Architecture.Menu.AdminView` |
| `setCustomViewMenu`, `setupFrontendParamFields` | `Architecture.Menu.CustomView` (J3/J4/J5/J6 target family for the fieldset rule/field lookup attributes) |

Each extraction ships with unit or family-contract tests, provider catalog
and interface-conformance fixture updates, and test-ownership entries. Use
these moves as the template for the remaining clusters.

## Interpretation domain map

The following ranges are orientation clusters, not automatic class boundaries.
Line numbers refer to the documented baseline and will drift.

| Approximate range | Cohesive concern visible in method names/calls | Likely domain to evaluate |
| --- | --- | --- |
| 216–928 | license locking, WHMCS encryption/key code | component helper/security generation; separate generated architecture from runtime cryptography |
| 929–1,259 | version controller and dynamic update SQL/XML | Extension install/update domain |
| 1,260–1,558 | spreadsheet/import-export helper methods | Creator/extension helper output, not Package repository distribution |
| 1,559–1,873 | administrator/custom-view menu and permission access | Architecture view/menu concern |
| 1,874–3,744 | display/document methods, CSS/JS, metadata, libraries, UIkit, custom view bodies/layouts | Architecture view and asset/document concerns |
| 3,745–3,813 | replacement-name parsing | Placeholder or focused parsing utility; external-use review required |
| 3,814–5,640 | item save/table/content types and post-install/update/uninstall scripts | Model/table and Extension lifecycle concerns; do not keep mixed |
| 5,641–7,489 | router, batch, alias/title, install and uninstall output | Architecture router/model plus Extension lifecycle concerns |
| 7,490–7,962 | admin/site/system language-file data | Language domain |
| 7,963–11,236 | list/edit bodies, tabs/layouts, linked views and linked queries | Architecture admin view/list/model concerns |
| 11,237–12,464 | dynamic buttons, GetItems, import/export, list/search/custom/filter queries | Architecture controller/model and Dynamicget concerns |
| 12,465–14,629 | conditional field JavaScript, relations, AJAX, validation and jQuery | Admin-view client behavior and controller/model AJAX concerns |
| 14,630–16,318 | unique fields, filter/batch helpers, permissions, stored state, populate state and sorting | Architecture model/view/filter concerns |
| 16,319–17,149 | GetItems string fix, model field relations and selection translation | Architecture model concern |
| 17,150–18,231 | router case, dashboard icons/data, submenus and main menus | Architecture dashboard/menu/router concerns |

Several ranges contain more than one bounded concern. They should produce
multiple small services, but a private call cluster and its shared state should
move together.

### Destination hypotheses to verify

The current tree suggests these homes:

| Cluster | Existing boundary to evaluate first |
| --- | --- |
| Fields filter XML and admin list filter output | target-specific `Architecture/Joomla*/AdminViews` behind `ArchitectureView` provider aliases |
| Admin edit form/display/layout | `Architecture/Joomla*/AdminView` |
| Site/custom view document/display | split across SiteView, CustomAdminView, and CustomAdminViews objectives |
| Controller batch/import-export/AJAX | `Architecture/Joomla*/Controller` |
| Model queries/populate/get-form/string generation | `Architecture/Joomla*/Model`, with common data transformation in Model/Dynamicget services |
| Dashboard output and queues | `Architecture/Joomla*/Dashboard` plus focused Builder state |
| Router output | extend the existing Creator Router collaboration; add target variants only where emitted APIs differ |
| Language assembly | `Compiler/Language` beside Set, Purge, Translation, and Multilingual |
| Layout/template generation | Creator/Layout and `Templatelayout.Data` |
| Component installer/content types | a component-owned target architecture service family |

Do not place component installer code in
`Extension/Joomla*/InstallScript` merely because of the name: that existing
service currently serves module/plugin flows. Confirm the generated objective
and callers before reusing or widening it.

## Fields map

`Fields` is smaller but demonstrates the target-version problem clearly:

| Current method group | Current shape | Refactoring direction to evaluate |
| --- | --- | --- |
| `getCustomFieldCode` | extracts custom field header/PHP fragments and updates placeholders | Field/custom-code generator with injected Placeholder |
| `setFieldFilterSet` and `setFieldFilterListSet` | top-level dispatchers | Stable interface/alias selected by compile target |
| `*J3` and `*J4` implementations | Joomla-specific generated filter XML/list output | Explicit J3/J4/J5/J6 Field or Architecture-view services; share stable mechanics through a common base while preserving separate target identities |
| `setFilterFieldFile` | builds a filter field structure/file | Field structure/generation domain |

Do not preserve inline version dispatch in the new consumer. Register concrete
target implementations and select through the service provider, following the
existing 40-family version matrix.

## Infusion map

`Infusion` is primarily orchestration:

- `buildFileContent()` seeds global ContentOne values, iterates admin/custom/site
  views, invokes Creator and Architecture services, changes build/language
  targets, and fills ContentMulti;
- `setViewPlaceholders()` updates the per-view placeholder context; and
- `setLangFileData()` finalizes language files.

It should be the last legacy layer removed, not the first. As Interpretation
and Fields behaviors move out, replace calls inside `buildFileContent()` with
injected domain orchestrators. Then split the large method by compiler phase or
objective while preserving the exact call/event/state order. Only after it no
longer depends on inherited methods should the final compiler stop extending
it.

### Hidden second-pass dispatch

`Interpretation::getEditBodyTabs()` queues `setLinkedView` work in
`$this->secondRunAdmin`. That property is declared only by the child Infusion
class. Later, `Infusion::buildFileContent()` executes the queued function name
with `$this->{$function}(...)`.

This creates both dynamic liveness and an inheritance inversion: the parent
requires storage supplied by its child. A normal direct-call graph can wrongly
classify `setLinkedView()` as unused. Replace this queue with an explicit,
focused builder or typed work item before separating the classes, and preserve
the second-pass order.

## Remaining helper-owned arrays

Interpretation still declares public or protected arrays such as import/export
views/scripts, uninstall-script pieces, list-column state, icon state,
validation/view scripts, relation controls, router completion, dashboard data,
custom-admin links, and trackers. Infusion adds language/second-pass state.

Some declarations now appear to have no direct `$this->property` use in the
current helpers, while similarly named Builder services already exist. That is
evidence of an incomplete migration, not permission to delete public
properties. For every property:

1. search the full repository and extension event surface;
2. map every read, write, by-reference pass, and expected value shape;
3. identify an existing focused Builder or create one if the state remains
   necessary;
4. inject the same shared object into all producers/consumers;
5. provide a compatibility accessor/delegate where external use is plausible;
   and
6. remove the property only after characterization and reference checks.

Do not move all remaining properties into one “Interpretation state” registry.
That would reproduce the original collision/leakage problem under a new name.

Four order-sensitive queues are currently created as undeclared dynamic
properties: `customAdminViewListId`, `lastCustomDashboardIcon`,
`lastCustomSubMenu`, and `lastCustomMainMenu`. Besides being hidden state, these
are PHP 8.2 dynamic-property risks. Move each semantic queue with its complete
producer/consumer cluster; do not simply add declarations and leave the design
implicit.

## Embedded version behavior to preserve first

Fields explicitly dispatches J3 to its J3 methods and J4/J5/J6 to its J4
methods. Interpretation still contains dozens of target checks across document,
metadata, installer, list, query, filter, AJAX, dashboard, and menu concerns.
Those branches should become provider-selected variants, but extraction must
first preserve current quirks:

- metadata and field filters deliberately route J4/J5/J6 through J4 behavior;
- several import/export/sidebar/batch paths are J3-only or return empty for
  later versions with existing “needs fixing” notes;
- the UIkit loader comment says J6+ while the condition is strictly J6;
- one J4 metadata fragment appears suspicious but must not be silently fixed
  during a move; and
- misspelled public names such as `setAjaxToke`, `setFadeInEfect`, and
  `checkStatmentLicenseLocked` remain compatibility-sensitive.

Capture current target output, then place intentional fixes in separate changes.

## Dependency inventory for one cluster

Create a compact extraction record with these columns before coding:

| Item | What to record |
| --- | --- |
| Entry methods | Public methods and every in-repository caller |
| Internal cluster | All helper methods transitively called, including conditional calls |
| Container services | Literal aliases, dynamically composed aliases, and whether each is read/mutated |
| Builder paths | Full path expressions, separator, `set` versus `add`, type and default |
| Config | Reads, writes, temporary switches, and restoration order |
| Inputs | Types, by-reference parameters, object/array shape assumptions |
| Outputs | Return values, generated fragments, placeholders, files, archives |
| Side effects | Events, messages, language, custom code, database, filesystem, counters |
| Version axis | Host, target, common, or mixed (mixed must be split) |
| Ordering | What must happen before/after the cluster |
| Destination | Closest existing objective/provider/interface and the evidence for it |

Textual `CFactory::_()` counts are useful for discovery but not sufficient.
Dynamic service names, repeated calls on a line, and service calls hidden in
another helper require manual tracing.

## Extraction sequence

### 1. Characterize

- Add a minimal definition fixture that reaches the cluster.
- Capture direct output, builder snapshots, Config before/after, events,
  messages, and generated files.
- Run every meaningful target-version branch.
- Capture dynamic second-pass work and the four undeclared queue properties.
- Assert that temporary Config switches are restored at the same point. In
  particular, document preparation temporarily changes `lang_target`, and
  Infusion backs up/restores `build_target`, `lang_target`, and `lang_prefix`.

### 2. Design the destination from the current tree

- Name the generated objective and lifecycle phase.
- Reuse an existing interface or create the narrow contract shared by genuine
  variants.
- Keep common algorithms outside `Joomla*` folders.
- Place concrete variants below the nearest domain.
- Put persistent semantic state in a focused Builder.

### 3. Build an injected service

- Constructor-inject typed collaborators.
- Register it in the closest provider with a stable alias.
- Share it if it coordinates shared mutable build state.
- Do not call `CFactory::_()` from the new class.

### 4. Delegate without changing behavior

The old method remains a compatibility shim while callers migrate. Its body
should become only argument forwarding and return forwarding. Preserve
by-reference signatures and visibility.

```php
public function setExample(&$view, string $target)
{
    return CFactory::_('Compiler.Example')->set($view, $target);
}
```

The static lookup in this example is tolerated only in the legacy shim. The
new service itself is injected where modern callers use it.

### 5. Migrate callers and prove equivalence

- Inject the new interface into modern consumers.
- Keep orchestration order unchanged.
- Compare normalized generated trees for affected targets.
- Assert builder/event/message state and failure paths.
- Search again for calls and public-property use.

### 6. Remove the shim later

Remove a method/property/ancestor only when repository callers are gone,
external compatibility has been addressed, and characterization/golden tests
cover the replacement. Shortening the inheritance chain is a milestone, not
the first edit.

## Recommended migration waves

1. **Safety harness:** factory/provider tests, registry contracts, and one
   golden component across targets.
2. **Small explicit version cluster:** Fields filter generation, proving the
   target-selector pattern end to end.
3. **Leaf Interpretation generators:** cohesive fragments with few helper-owned
   properties and an obvious existing Architecture/Extension/Language home.
4. **State-heavy clusters:** move each remaining array to a focused Builder and
   extract its complete producer/consumer cluster.
5. **Cross-cutting view/model/router clusters:** migrate the methods heavily
   called by Infusion while expanding golden fixtures.
6. **Infusion orchestration:** replace inherited calls with injected services,
   split by phase/objective, and preserve sequencing.
7. **Inheritance removal:** inject the final content-build orchestrator into
   `Componentbuilder\Compiler`, stop extending Infusion, and remove deprecated
   wrappers only after compatibility review.

Within a wave, prefer the smallest cluster whose tests exercise a real
generated artifact. Do not optimize for the largest line deletion.

## Definition of done for one extraction

- The destination follows the existing locality and naming conventions.
- The class has explicit typed dependencies and no new service-locator calls.
- Version selection is in a provider and uses the correct axis.
- State uses focused shared builders with unchanged path/value contracts.
- The old public method delegates or has a documented compatibility plan.
- Events, Config mutations, messages, language, and filesystem effects remain
  ordered and equivalent.
- Unit/contract/characterization tests pass.
- Golden generated trees match for every affected target, or intentional diffs
  are isolated and reviewed.
- Factory/provider registration and reset behavior are tested.
- The PR explains the method cluster, destination rationale, and proof of
  equivalence.
