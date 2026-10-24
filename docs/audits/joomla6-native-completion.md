# Joomla 6 native compiler completion

Implementation baseline: `faa95cdeecdbdb4504f27d66870310aec1a3ec0f` (merged PR #59).

## Objective

Remove the remaining deprecated APIs and compatibility paths from generated
Joomla 6 extensions while retaining Joomla 3, 4 and 5 runtime behavior. Changes belong to
the compiler and its conventional `admin/compiler/joomla_6` template set.

## Implementation scope

1. Web Asset Manager registration, activation, inline assets and ordered
   dependencies; package generated CSS and JavaScript under extension media.
2. Model identity injection and supported user properties.
3. Native table, database and cache access; remove the generated table's legacy
   static database cache without changing record or index behavior.
4. Supported controller input access and active import/bootstrap paths.
5. Coordinated model/table/controller/view error handling using the supported
   Joomla 6 runtime contract, preserving form recovery and API responses.
6. Native plugin event subscriptions and clear definition migration diagnostics.
7. Native module/plugin installers with custom lifecycle hooks.
8. Toolbar buttons without the deprecated hover-icon argument.

Joomla Powers and JCB Powers retain their existing resolution/import/alias
pipeline. Earlier targets retain their compatibility branches. FOF and unrelated
generated administrator code remain outside this change.

The subsequent [compiler convention repair](compiler-emission-conventions.md)
also corrects existing emitters across targets, as requested in review. Its
formatting changes are reviewed explicitly; native API migration remains limited
to Joomla 6.

## Future reusable JavaScript definitions

The proposed name is **JavaScript Powers**, with **Joomla JavaScript Powers** for
references to Joomla-provided scripts/modules. An eventual definition would
declare stable identity, asset kind, source, exports and dependencies, and compile
into Joomla's asset registry and media layout. This round prepares the native
asset loading and packaging paths only; it introduces no authoring UI, database
entity or parallel JavaScript compiler.

## Implemented behavior

| Area | Joomla 6 implementation |
| --- | --- |
| CSS and JavaScript | Component scripts/styles live under `media/com_component/{js,css}/{admin,site}`; module assets live under module media. The view document activates ordered, deduplicated model assets through WAM. Generated field/view code, inline scripts/styles, Footable, Google Charts, loading overlays, submit handlers, DKIM configuration and import assets use WAM. |
| Identity | Models read the current user after MVC injection, refresh derived permission state on identity changes and use supported public user properties. |
| Tables and database | Tables use their injected database, including date conversion. The generated static connection cache and fallback are removed. Existing record, asset and index operations are retained. |
| Input and imports | Native controllers use their injected input. Active import controllers use the cache-controller factory; obsolete bootstrap calls are removed. Import templates remain because settings still link them. |
| Error handling | Read/view/API/helper boundaries use the supported Joomla 6 exception mode. Generated table failures propagate into the native model/controller boundary. Form-save validation failures retain entered data and edit redirects. |
| Plugin events | Native subscribers and event maps replace legacy listener discovery. The three unchanged bundled privacy definitions compile into native event handlers. Invalid custom handler contracts receive precise compiler migration errors. |
| Installers | Generated module/plugin scripts implement InstallerScriptInterface directly, including custom hooks, without Joomla's legacy installer adapter. Joomla 6 packages require Joomla 6 at installation. |
| Toolbar | Custom buttons use the document toolbar's fluent button objects with explicit selection requirements. Save-as-copy/new use the supported helper methods. |

The template source remains `admin/compiler/joomla_6`, selected by its own
`settings.json`. Shared mechanics and target-specific subclasses use existing
compiler service providers. No Joomla 3, 4 or 5 template file is changed.

## Stability and definition migration boundaries

- Joomla 6.1.2 still has internal legacy error storage in core AdminModel.
  The compiler preserves the core FormController save/recovery contract instead
  of replacing core internals. Read/view/API boundaries follow Joomla 6's own
  exception-mode pattern. This is a Joomla 6 deprecation migration, not a claim
  that Joomla 7 runtime compatibility has already been proved.
- Stored custom view and field footer JavaScript keeps its source and global scope when
  registered with WAM. WAM renders it in the document head; immediate DOM access
  must be moved into a DOMContentLoaded handler. The Joomla 6 compiler reports
  the affected view or field and its collection target, including module field
  scripts. Generated scripts are adapted directly.
- Stored plugin code is not rewritten arbitrarily. Inferred event handlers must
  accept native event objects and write results to the event. Explicit subscriber
  maps and inherited native maps retain their intended contracts; maintainers
  must migrate positional handlers and incompatible constructors themselves.
  Merely adding a subscriber map does not migrate a positional handler.
- The three bundled privacy handlers are migrated only when both the selected
  method-store GUID and the complete executable default contract match.
  Formatting and comments may differ; edited executable bodies retain the
  migration diagnostic. The stored definitions and earlier-target output are
  unchanged. The native Joomla PrivacyPlugin parent is retained; its inherited
  database-property compatibility in Joomla 6.1.2 remains a core boundary.
- Custom installer constructor code runs once, at the first lifecycle call when
  Joomla provides its adapter. Existing custom hook bodies are preserved:
  no return means success, explicit false remains failure, and exceptions
  propagate. The initialization timing is deliberate and covered by tests.
- Standalone help HTML responses retain their own HTML asset tags; they are
  separate documents and do not invoke deprecated Joomla document loaders.
- Arbitrary stored layouts, custom source and third-party libraries remain their
  authors' responsibility. Moving generated assets into WAM does not rewrite
  opaque JavaScript or prove every external dependency free of deprecated APIs.
- No reusable JavaScript authoring UI/entity is included in this round.

## Native implementation validation before the convention repair

The unchanged baseline passes 4,878 tests / 59,553 assertions on PHP 8.3.
Focused native tests exercise real Joomla 6.1.2 objects and the actual Joomla/JCB
Power extraction, import and collision-resolution pipeline. Earlier-target
toolbar and input-controller snapshots are byte-identical to the merged base.

The complete implementation is commit
`c15c66aaefc467d150d591abc7b408b321f6fc74`. Its verified results are:

| Validation | Evidence |
| --- | --- |
| Full unit suite | 5,024 tests / 62,421 assertions pass on both PHP 8.3 and 8.4 in [run 40028709199](https://github.com/extension-builder/joomla/actions/runs/40028709199). |
| Source gates | PHP style passes for 115 changed files. All 1,545 production files have test ownership, with zero untested baseline entries. Container-key and moved-condition checks pass; the separate 84-case known-defect audit is unchanged. |
| Installed Joomla 6 API | [Run 40028709193](https://github.com/extension-builder/joomla/actions/runs/40028709193) compiles and installs the demo and exercises both Demo and Library Config models: 222 CRUD, permission and partial-PATCH checks pass. |
| Joomla 3–5 generated output | [Run 40028709237](https://github.com/extension-builder/joomla/actions/runs/40028709237) passes strict byte/path/mode equality and installed Power discovery probes at corpus sizes 0, 100 and 1,000 for each earlier target. |
| Joomla 6 generated output | The complete reviewed native diff reproduces the measured candidate tree exactly. All 127 generated PHP files pass syntax checks, all 1,723 deployed candidate source hashes verify, and final Power evidence is unchanged. The [review record](../../.github/golden-master/expected/README.md#joomla-6-native-assets-and-remaining-deprecated-paths) documents the exact expectation. |
| Browser and JavaScript coverage | The GUI workflow runs 29 Playwright journeys plus five dependency-free DKIM JavaScript behavior checks. The original implementation passed all 29 browser journeys; the five new JavaScript checks pass locally and in the hosted workflow. |

The final branch runs all required workflows again after the reviewed output
expectation is committed, including the Joomla 6 installed discovery probe.
Their current results are published on [PR #60](https://github.com/extension-builder/joomla/pull/60/checks).
The initial expectation is limited to the reviewed Joomla 6 blueprint and baseline.
The compiler convention repair is validated again with exact output comparisons,
including explicit review of any earlier-target formatting differences. No check
or known-defect quarantine is relaxed.

The public demo and generated-runtime fixtures are regression evidence. They do
not replace a maintainer's full JCB self-compile using private/full definitions.
