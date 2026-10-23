# Joomla 6 native compiler completion

Implementation baseline: `faa95cdeecdbdb4504f27d66870310aec1a3ec0f` (merged PR #59).

## Objective

Remove the remaining deprecated APIs and compatibility paths from generated
Joomla 6 extensions while retaining Joomla 3, 4 and 5 output. Changes belong to
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

## Future reusable JavaScript definitions

The proposed name is **JavaScript Powers**, with **Joomla JavaScript Powers** for
references to Joomla-provided scripts/modules. An eventual definition would
declare stable identity, asset kind, source, exports and dependencies, and compile
into Joomla's asset registry and media layout. This round prepares the native
asset loading and packaging paths only; it introduces no authoring UI, database
entity or parallel JavaScript compiler.

## Validation status

Implementation in progress. The unchanged baseline passes the blocking PHP 8.3
suite: 4,878 tests and 59,553 assertions. Final source ownership/style gates,
generated-runtime tests, Joomla 3–6 golden comparisons and hosted API/GUI checks
will be recorded here after the implementation is complete.
