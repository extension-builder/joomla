# Source inventory

## Baseline

This point-in-time inventory was collected from `6.x` at
`bca4a1520484f3e2c2fbd12964a5995b0d058de1`. It exists to shorten orientation
time. Counts will change as the migration continues.

| Area | Files | Directories | Notes |
| --- | ---: | ---: | --- |
| `Componentbuilder/Compiler` | 659 | 127 including the root | Compiler implementation, providers, interfaces, builders, and legacy helpers |
| `Componentbuilder/Package` | 180 | 108 including the root | Definition Get/Set, remote, dependency, file/folder, and README services |
| `Compiler/Builder` | 108 PHP classes | 1 folder | All 108 extend the VDM registry abstraction |
| `Compiler/Service` | 31 PHP providers | 1 folder | Core/domain/architecture/builder composition |

The main compiler factory registers 66 service providers. The standalone
Package factory registers 40 providers, including infrastructure and both
directions for supported entity families.

The Compiler total consists of 542 PHP files and 117 placeholder `index.html`
files, with 153,587 PHP source lines at this baseline. The largest PHP strata
are Architecture (107 files), Builder (108), Model (42), Creator (34), Service
(31), and Interfaces (51); line volume is instead dominated by Architecture,
the three Helpers, Creator, module/plugin pipelines, and Service composition.

## Componentbuilder top-level map

| Folder | Primary responsibility |
| --- | --- |
| `Abstraction` | Componentbuilder-specific base classes and shared console/data/registry mechanics |
| `Api` | JCB-facing API clients/models |
| `Compiler` | Definition-to-extension compilation |
| `Console` | CLI commands for compile, Package, powers, and related operations |
| `Crypt` | Componentbuilder cryptographic services |
| `Data` | JCB data services and schema/GUID migration |
| `Extrusion` | Reverse/extrusion-related behavior |
| `Fieldtype` | Field-type repository/data domain and factory |
| `File` | File domain and factory |
| `Import` | Import domain and factory |
| `Interfaces` | Componentbuilder-wide contracts |
| `JoomlaPower` | Joomla class-reference catalog and factory |
| `Markdown` | Markdown handling |
| `Network` | Network resolution |
| `Package` | Definition distribution and repository synchronization |
| `Power` | Reusable Super Power catalog, repositories, and factory |
| `Remote` | Shared remote behavior |
| `Repository` | Repository definitions and factory |
| `Search` | Search domain and factory |
| `Server` | Server-transfer behavior |
| `Service` | Broad Componentbuilder providers |
| `Snippet`, `SnippetType` | Reusable snippet domains |
| `Spreadsheet` | Spreadsheet import/export helpers |
| `Table` | JCB table metadata |
| `User` | User-related domain services |
| `Utilities` | Componentbuilder-specific utilities |

## Compiler folder map

| Folder | Responsibility |
| --- | --- |
| `Adminview` | Admin-view data/build behavior |
| `Alias` | Alias generation/support |
| `Architecture` | Source fragments and structures shaped by Joomla architecture |
| `Builder` | Focused shared compilation-state registries |
| `Component` | Component data, structures, settings, dashboard, and placeholders |
| `Creator` | Generators for fields, permissions, helper fragments, router/config/install concerns, and other output |
| `Customcode` | Custom-code retrieval, dispensing, extraction, and external handling |
| `Customview` | Custom/site view data and structure behavior |
| `Dynamicget` | Dynamic-get handling |
| `Extension` | Install/update files, scripts, settings, and extension-wide behavior |
| `Field` | Field construction, rules, names, groups, and input controls |
| `Helper` | Deprecated active legacy chain: Fields, Interpretation, Infusion |
| `Interfaces` | Stable contracts, especially for version-selected implementations |
| `Joomla` | Joomla-related compiler support common across targets |
| `JoomlaThree` … `JoomlaSix` | Broad target/runtime variants: Event, Header, History |
| `JoomlaPower` | Joomla Power extraction and compiler integration |
| `Joomlamodule` | Module data, structures, infusion, and target variants |
| `Joomlaplugin` | Plugin data, structures, infusion, and target variants |
| `Language` | Language collection, extraction, and file data |
| `Library` | Library data and structures |
| `Model` | Compiler-side definition models and transformations |
| `Placeholder` | Placeholder storage/replacement support |
| `Power` | Power loading and compiler integration |
| `Service` | Joomla DI composition providers |
| `Templatelayout` | Template/layout data and structure behavior |
| `Utilities` | Compiler filesystem, paths, counters, indentation, line, minify, and related utilities |

## Compiler service providers

The 31 provider files are grouped here by role.

| Role | Providers |
| --- | --- |
| Core | `Compiler`, `Utilities`, `Event`, `Header`, `History`, `Language`, `Placeholder`, `Customcode`, `Package`, `Power`, `JoomlaPower` |
| Definition domains | `Model`, `Component`, `Adminview`, `Library`, `Customview`, `Templatelayout`, `Extension`, `Field`, `Joomlamodule`, `Joomlaplugin` |
| Build state/output | `BuilderAJ`, `BuilderLZ`, `Creator` |
| Architecture | `ArchitectureComponent`, `ArchitectureModel`, `ArchitectureView`, `ArchitectureController`, `ArchitectureDashboard`, `ArchitectureModule`, `ArchitecturePlugin` |

The factory additionally registers providers from generic VDM services,
Componentbuilder services, Power Git services, Github/Gitea libraries, and
Package entity Get services.

Those 31 compiler-local providers contain 509 alias-and-shared service
registrations. `BuilderAJ` contributes 52 builder services and `BuilderLZ` 56.
The number is useful for completeness checks, but the aliases and constructor
edges—not the raw count—are the compatibility surface.

## Versioned implementation inventory

There are 160 PHP implementation files in the explicit compiler target/runtime
variant folders covered by this inventory.

| Stratum | J3 | J4 | J5 | J6 | Total |
| --- | ---: | ---: | ---: | ---: | ---: |
| `Compiler/Joomla*` | 3 | 3 | 3 | 3 | 12 |
| `Compiler/Architecture/Joomla*` | 24 | 24 | 24 | 24 | 96 |
| `Compiler/Component/Joomla*` | 1 | 1 | 1 | 1 | 4 |
| `Compiler/Extension/Joomla*` | 2 | 2 | 2 | 2 | 8 |
| `Compiler/Field/Joomla*` | 3 | 3 | 3 | 3 | 12 |
| `Compiler/Joomlamodule/Joomla*` | 3 | 3 | 3 | 3 | 12 |
| `Compiler/Joomlaplugin/Joomla*` | 3 | 3 | 3 | 3 | 12 |
| `Compiler/Model/Joomla*` | 1 | 1 | 1 | 1 | 4 |

The 24 Architecture implementations per target are distributed across admin
single/list views, component helper code, controller, custom-admin single/list
views, dashboard, model, module, plugin, and site-view objectives.

These files form exactly 40 four-major dispatch families. Four are selected by
the host Joomla major (`Event`, `History`, `Field.Core.Field`, and
`Field.Core.Rule`); the remaining 36 are selected by the compiler target.

## Builder inventory characteristics

All 108 direct Builder PHP classes extend
[`VDM\Joomla\Abstraction\Registry`](../../libraries/vendor_jcb/VDM.Joomla/src/Abstraction/Registry.php).
Most contain no behavior beyond the semantic class name; others specialize
separator, accumulation, placeholder normalization, or domain convenience.

Representative specialized builders include:

- `ContentOne` and `ContentMulti`;
- permission action/component/core/dashboard/global/view registries; and
- `UpdateMysql`.

This “one semantic dataset, one shared registry object” convention is a core
part of the refactoring architecture.

`BuilderAJ` registers 52 services and `BuilderLZ` 56. A total of 106 classes
declare `Registryinterface` explicitly; `PermissionViews` and `UpdateMysql`
still satisfy it through their Registry ancestor.

## Legacy helper baseline

| File | Approximate lines | Declared methods | Static factory resolutions | Unique service-key expressions |
| --- | ---: | ---: | ---: | ---: |
| `Helper/Fields.php` | 822 | 9 | 67 | 12 |
| `Helper/Infusion.php` | 2,549 | 4 | 670 | 60 |
| `Helper/Interpretation.php` | 18,241 | 175 | 1,381 live calls | 106 key expressions |

Interpretation's 106 expressions comprise 103 complete literal service keys
and three dynamic expression shapes. Its 1,381 live-call count is the 1,385
textual occurrences minus four occurrences in comments or strings.

Method counts include constructors. Factory counts are token-aware live-call
counts where recorded and are intended as scale indicators. They are not a
substitute for a PHP call graph: multiple calls can share a line and dynamic
service expressions require manual review.

## Factories in and near Componentbuilder

Container factories exist for Compiler, Package, Fieldtype, File, Import,
JoomlaPower, Power, Repository, Search, Snippet, and Data Migrator. The
top-level `Componentbuilder\Factory` is the entity router described in the
[system map](system-map.md), not another service container.

Sibling libraries have their own factories where their API surface is broad:
Gitea registers organization, user, repository, package, issue, notification,
miscellaneous, settings, admin, and utility providers; Github and OpenAI keep
their smaller adapter graphs separate.

## Sibling vendor-library scale

Point-in-time PHP file counts under each `src` folder:

| Library | PHP files | Architectural role |
| --- | ---: | --- |
| `VDM.Joomla` | 983 | Foundation and Componentbuilder domain |
| `VDM.Joomla.Gitea` | 114 | Full Gitea API adapter |
| `VDM.Joomla.Openai` | 17 | OpenAI API adapter |
| `VDM.Joomla.Github` | 9 | Github adapter used by Power/repository flows |
| `VDM.Joomla.FOF` | 8 | FOF compatibility/support |
| `VDM.Minify` | 8 | Minification support |
| `VDM.Joomla.Git` | 1 | Provider-neutral Git repository-contents facade |
| `VDM.Psr` | 1 | PSR support |

Counts do not express importance. The small Git library, for example, provides
the provider-neutral facade that bridges Gitea and Github repository-content
implementations; the interface contract it implements lives in `VDM.Joomla`.
