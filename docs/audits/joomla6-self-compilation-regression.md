# Joomla 6 self-compilation regression audit

## Evidence and release status

This audit compares the maintainer's generated
`com_componentbuilder_v6_2_1-alpha1__J6.zip` with repository revision
`2494b8dfb210b47dadbb982d2ed5fbd71ca161db` on 27 October 2026.
The uploaded ZIP SHA-256 is
`ed3224e0201b51e4ee2d9f057668077784ccc7a6da2f3950bd2f62f1e2cfe8bf`.
The subsequent `plugins.zip` upload has SHA-256
`9fcfad4e77305583b6585505d1557bd8d612efad6c86e3a409435567fc041a52`.
That second upload establishes the missing-header and Search dependency cause.

The package proves that JCB's source-to-definition-to-source round trip is
broken in multiple places. Three compiler/Power generation defects are now
identified. Fixing their emitters does not repair already-installed plugins or
saved Power bodies. The maintainer's complete build remains unvalidated for
rollout until the recovery cycle below succeeds.

## Proven defects and scope

| Finding | Evidence in the uploaded package | Cause or remaining boundary |
| --- | --- | --- |
| Empty subclasses acquire incompatible constructors | All 25 intentionally empty target variants gained an injected parent property and constructor. All 45 new target classes are present; the 20 nonempty variants did not acquire these constructors. | `Power/Generator` calls `ClassInjectorBuilder` when a saved Power has an empty body and selected imports. The parent alias was treated as an injected dependency. The new constructor suppresses inherited initialization and disagrees with existing provider arguments. |
| Plugin namespace placeholders resolve during JCB's own build | `Compiler/JoomlaFour/Header.php`, `JoomlaFive/Header.php`, and `JoomlaSix/Header.php` contain `Webservices\\ComponentbuilderApiRoutes` where their source generators require `[[[PluginGroupNamespace]]]` and `[[[PluginNamespace]]]`. | Complete placeholder literals in an emitter were consumed one generation too early. The fix splits those literals while preserving the runtime header output. |
| Compiler plugin handlers are never subscribed | All seven generated extension/compiler plugins retain their 26 public `jcb_ce_*` methods, but their J6 `getSubscribedEvents()` maps add no handlers. The uploaded Headers Compiler registers zero compiler listeners at runtime. | The J6 plugin emitter inferred only method names beginning with `on`. JCB's established `jcb_ce_*` compiler hooks were therefore excluded when JCB regenerated the native subscription map. |
| Shared ItemSave source is stale | The uploaded `Compiler/Architecture/Model/ItemSave.php` lacks the current `patchLoadFailure()` hook. The J6 subclass still overrides that hook, but the uploaded base never calls it. | The base Power definition must be reconciled with the authoritative current source. The ZIP alone does not establish why the older body persisted. |
| Search is absent and custom imports are lost | Exactly 24 tracked first-party library PHP files are missing, all under `Componentbuilder/Search`. The repository has 1,557 such files; the package has 1,533, with no extra files. `SearchFactory` calls remain in generated consumers. | The unsubscribed Headers Compiler cannot add custom imports or load their selected Power roots. This explains the simultaneous loss of Form/Session headers and the Search dependency tree. The corrected complete output still requires verification. |

Missing imports include `Form` in Compiler, Extrusion and Search HtmlViews;
`Session` in compiler and search templates; and `SearchFactory` in AjaxModel,
SearchModel and the Search HtmlView. Other custom imports are also absent.
These are broader symptoms than the two reported exceptions.

The maintainer confirms that Headers Compiler is enabled and selected. The
new upload explains why that was insufficient: enabling a native subscriber
with an empty map does not register its compiler handlers.

Earlier plugin checks passed with JSON and array parameters, but their source
fixtures already contained a previously generated subscription map. They did
not test JCB regenerating that map from authored handler code. The subsequent
upload reproduces the actual failure: methods are present, but no compiler
listeners are registered. The seven affected plugins are Action Log Compiler,
Export Compiler, Field Ordering Compiler, Headers Compiler, Language Packaging,
Powers Autoloader Compiler and Privacy Compiler. Their manifests and provider
class references are valid; the missing subscriptions are in the generated
extension classes.

## Affected empty definitions

All paths below are relative to
`libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/`.
Each listed version is a separate Power definition:

| Versions | Directory | Files relative to that directory | Count |
| --- | --- | --- | ---: |
| `JoomlaThree`, `JoomlaFour`, `JoomlaFive` | `Architecture/<version>/` | `CustomButtons.php`, `DynamicButtons.php`, `View/GoogleChartLoader.php`, `View/Jquery.php`, `AdminView/ViewScript.php` | 15 |
| `JoomlaFour`, `JoomlaFive` | `Architecture/<version>/` | `View/LibrariesLoader.php`, `AdminView/FootableScripts.php` | 4 |
| `JoomlaThree`, `JoomlaFour`, `JoomlaFive` | `Creator/<version>/` | `ConfigFieldsetsEmailHelper.php` | 3 |
| `JoomlaThree`, `JoomlaFour`, `JoomlaFive` | `Library/<version>/` | `IncludeHelper.php` | 3 |

The thin variants remain part of the version-dispatched architecture. Their
empty bodies are deliberate and must remain empty during Power save.

## Fix and verification

`Power/Generator/ClassInjectorBuilder.php` now preserves inherited constructors
when a definition has a GUID-linked parent or an active custom parent.
Filtering only the parent's selected import would be insufficient: generating
a constructor for other imports would still suppress unknown inherited
initialization. Ordinary classes without parents retain their existing DI
scaffolding, and the service-provider generation route remains unchanged.
Authored nonempty bodies are not rewritten.

The third repair is in
`Compiler/Architecture/JoomlaSix/Plugin/Extension.php`: native subscription
generation now recognizes public concrete instance methods for JCB's
established `jcb_ce_*` hooks and preserves their positional/by-reference event
argument contract. Ordinary `on*` handlers retain typed Joomla-event validation,
and explicit authored maps remain authoritative. Generated subscription methods
must not be pasted into manually maintained plugin code to work around the
emitter defect. Regression inputs must omit previous generated subscription
scaffolding and exercise generated plugins through the real event dispatcher.

`Power/GenerationContractTest.php` parses all 25 actual wrapper files with the
real extrusion ClassFile reader, retains their empty bodies and aliases in
extrusion-shaped save data, and exercises the real Generator graph. All 25
cases fail against the original builder with the unwanted constructor output;
all pass with the fix. The tests also cover linked/custom parents, additional
imports, stale inactive parent fields, existing bodies, service providers and
repeated generator use. The focused owner passes 32 tests / 195 assertions;
the blocking Power domain passes 71 tests / 743 assertions.

The three Header regressions independently fail against their original
emitters and pass after splitting the deferred namespace placeholders.
The subscription regression also fails against the original emitter: the
generated map contains only its ordinary `onReady` event and omits both JCB
hooks. With the fix, a real Joomla subscriber and the JoomlaSix compiler Event
adapter execute both zero-argument loading and three-argument reference
mutation. The explicit-map control remains unchanged. The renderer owner and
J6 plugin tests pass **68 tests / 393 assertions**.

The final complete blocking core suite, including the subscription repair,
passes on PHP 8.4.27: **5,166 tests / 63,854 assertions**. The documented
`known-defect` group is excluded. These are local results; CI gates and the
maintainer's complete self-build remain separate validation requirements.
No unit-suite result substitutes for compiling the complete JCB definitions.

## Recovery and rollout gate

1. Install the corrected compiler before restoring definitions. The save-time
   guard prevents new corruption; it cannot repair already nonempty stored
   bodies automatically.
2. Restore the 25 verified empty Power definitions through extrusion from the
   correct source. Reconcile all three Header Power records with the corrected
   emitters, and restore the shared ItemSave Power from authoritative current
   source. Do not clear arbitrary Power bodies or overwrite authored logic.
3. Recompile and reinstall all seven extension/compiler plugins with the
   corrected J6 component compiler. Verify their native maps and actual
   registered compiler listeners. Reinstalling only the component leaves the
   already-generated empty plugin maps unchanged.
4. Verify the real build's custom-header and selected-Power delivery using its
   saved records, including Form/Session imports and the complete Search tree.
5. Rebuild JCB, install that result in a disposable Joomla environment, and
   rebuild again. Inspect both generated trees for the 24 Search files, custom
   imports, preserved empty variants and provider constructor compatibility,
   and deferred Header placeholders. Exercise Compiler, Extrusion and Search.

Only a successful rebuild–install–rebuild cycle with the complete maintained
definitions can close this regression and establish readiness for rollout.
