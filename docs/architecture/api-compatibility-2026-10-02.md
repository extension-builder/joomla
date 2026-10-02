# API and Extrusion compatibility — 2 October 2026

This ledger separates observed failures, source-proven defects and release
acceptance. The branch `fix/generated-api-contracts-and-extrusion` starts
from JCB source `22bbe87`. Corrections belong to the compiler, its maintained
templates and Extrusion source; compiled API files are not patched directly.
The MCP changes are a separate delivery and do not override native JCB
validation or permissions.

## Recorded evidence

The supplied isolated JCB 6.1.6 test report completed 47 of 51 writable API
resource lifecycles. Both MCP and direct native requests returned HTTP 500
on creation of `components_config`, `components_dashboard`,
`language_translations` and `libraries_config`. Configuration and dashboard
item reads also failed on seeded rows. The report captured neither the
exception stacks nor standalone complete fixture requests, so these four
failures must not all be attributed to one mechanism.

The user subsequently supplied the translation creation body. It includes
`source: "JCBAPITEST261002 source"`,
`translation: [{language: "qaa-QA", translation: "JCBAPITEST261002 translated"}]`,
`published: 1`, `access: 1`, and empty `components`, `modules` and `plugins`
arrays. The request therefore supplied `source`; omission is not an
explanation for the recorded failure. A native Joomla 6.1.2 reproduction using the full current compiled form
preserves both values during safehtml/subform filtering, then raises
`RuntimeException: Invalid field: Source String` at the disabled-field
guard in `Form::validate()` (`libraries/src/Form/Form.php`). The actual
`LangField` preserves and validates `qaa-QA`; no invalid-language cause was
established. This proves the disabled-source mechanism for this body and
form, while the historical installed exception response remains uncaptured.

| Finding | Evidence and correction | Remaining acceptance |
| --- | --- | --- |
| Item controllers can choose list models | The previous generated `getModel()` selected the list model when its incoming name equaled the content type. Inflection can leave `components_config`, `components_dashboard` and `libraries_config` unchanged. The generator now selects the explicit single/list model by controller role. | Recompile and install JCB, then repeat direct API creation and item reads for these three resources with captured failures and owned fixtures. This source defect is deterministic; attributing every recorded 500 to it still requires the native rerun. |
| Translation `source` is disabled | `admin/forms/language_translation.xml` declares `source` read-only and disabled. Native processing of the exact supplied body retains `source` then raises its disabled-present-field exception. The actual language field accepts `qaa-QA`. This current compiled-form reproduction establishes the validation mechanism, not the historical installed exception stack or the intended API write policy. | Investigate the authoritative field definition, GUID `c2f5d193-ef76-422b-aae5-421cd0a4b22b`, in JCB's `#__componentbuilder_field` definition table (`admin/src/Table/FieldTable.php`). Its intended API creation and PATCH policy has not been established. Do not invent a generic bypass or hard-code a compiled form fix; any policy correction belongs to the maintained field definition and regeneration. |
| Read-only item access redirects | The generated administrator model applied its edit guard to an API item read, producing native HTTP 303. The API-only guard now uses mapped native access permissions and viewing-access levels, using Joomla's native access exception for HTTP 403 on denial. Administrator editing keeps its existing guard/redirect. | Compiled API cases for permitted read-only, denied access and denied view levels, by numeric ID and GUID, plus unchanged administrator denial behavior. |
| Optional descriptions crash toolbar generation | Six Joomla 4/5/6 toolbar and modal-toolbar renderers pass an omitted/null description into the string-only language service. They now treat an absent description as the native empty description. | Compile an otherwise valid component with omitted and null descriptions; inspect its artifacts and compare unaffected output. |
| Extrusion's own marker literals are consumed during self-compilation | Runtime namespace markers and the external-code detector previously appeared as contiguous compiler tokens in Extrusion source. Three Extrusion classes now construct those literals by concatenation so compilation preserves their runtime values. | Rebuild JCB from its authoritative definitions and run real Extrusion harvest/review/import with the preserved markers. Unit preservation is not authoritative definition reconciliation. |
| Extrusion import requires an extra access action | The template and AJAX import method required `extrusion.import` in addition to the custom view's `extrusion.access`. The maintained entry points now use the view-access contract and remove the extra access action/labels. | Installed GUI cases for access-only users and denied users, including direct AJAX denial and native cleanup. Transfer the change to the external layout/AJAX/permission definitions before self-regeneration. |

## Current implementation boundaries

`Architecture.Api.Controller.GetModel` selects the item/list model from the
compiler's known controller role. It preserves `ignore_request` behavior
and does not depend on incoming inflected names. `EditView` and `ListView`
pass their roles explicitly.

`Architecture.Api.Controller.AllowView` supplies the shared mapped access
condition and the API item-model guard. The maintained
`ADMIN_VIEW_MODEL.php` template distinguishes API reads from administrator
editing. The native `AccessSwitch` controls viewing-access levels,
including customized access fields, matching the list model and preserving
the existing native `core.options` policy. A column name alone is not
evidence of a field's type or permission policy.
Mutation authorization, checkout and validation remain native responsibilities.

For translations, Joomla's native `ApiController` merges stored table fields
into PATCH data before form processing. A stored disabled `source` can
therefore reach the same validation guard even when the client omits it.
Client-side omission is not a complete repair. JCB's language extraction
(`Compiler/Language/Set` and `Insert`) populates source strings and origin
GUID relationships; `Multilingual` matches by source. Those mechanisms
explain why the administrator protects this identity, but do not establish
whether manual API creation or source changes should be allowed. The
authoritative definition must express the intended API write policy before
changing it; this PR introduces no generic disabled/read-only bypass.

The optional-description correction is confined to the six Joomla
Four/Five/Six `AddToolBar` and `AddModalToolBar` renderers. The Extrusion
marker correction is confined to `Resolver/Placeholders`,
`Resolver/References` and `Writer/Vendor`; concatenation yields the same
runtime marker strings rather than replacing their meaning.

No new custom-admin API builder, automatic route plugin or endpoint is
introduced. Existing dynamic resources described in
[API generation](api-generation.md) are not proof that every arbitrary
custom-admin workflow is exposed or tested through the API. Native entity
API operations and command/job execution remain separate surfaces.

## Validation and delivery

Focused local model-role tests passed 86 tests with 471 assertions;
toolbar tests passed 56 tests with 306 assertions; Extrusion marker tests
passed 78 tests with 666 assertions; Extrusion import ACL tests passed five
tests with 20 assertions. These counts cover the selected test groups, not
a completed installed API/GUI acceptance of this branch. API-read guard
and provider tests passed 120 tests with 16,442 assertions, including actual
template-method execution, administrator denial/edit behavior and the native
JSON:API denial handler. A generic exception carrying code 403 is not
sufficient: Joomla renders it as HTTP 500, so the guard emits its native
access exception. The
complete API-disabled template was materialized and matched the base
template's bytes without weakening golden comparisons. Installed compiled
API acceptance at `25f453d` passed [run `37041259754`](https://github.com/extension-builder/joomla/actions/runs/37041259754):
50 CRUD/GUID/cleanup checks and 11 permission checks for each of the native
`looks` and renamed `libraries_config` Demo fixtures (122 checks total).
This demonstrates explicit model roles and read policy in compiled/installed
APIs; it does not recreate all historical JCB resources. The installed GUI
journeys remain pending and must record their executed results before handoff.

The complete local `composer test` passed 4,399 tests with 47,730
assertions on PHP 8.3.6 and PHPUnit 12.5.33 against Joomla 6.1.2 source.
The complete test PHP style scan passed 534 files; ownership covers all
1,487 production declarations with no untested baseline debt, and all
944 requested container keys resolve apart from the five existing recorded
exceptions. These checks do not replace installed compilation, API or
browser acceptance.

Delivery uses cohesive changes: document the contracts and unresolved
evidence first, then carry API model/read-permission corrections, toolbar
null handling and Extrusion marker/import-permission corrections with their
own tests and protected-path records. Compiler/library changes must pass
the repository's style, ownership and full PHPUnit gates. Changed generated
output must pass the applicable golden/compiled API checks; administrator
entry points must pass their installed GUI journeys.

The protected changes are recorded separately in
[generated API item-read permissions](../graphical-user-interface-changes/2026-10-02-generated-api-item-read-permissions.md)
and [Extrusion view-access import](../graphical-user-interface-changes/2026-10-02-extrusion-view-access-import.md).
Those records retain their authoritative-source transfer requirements.

The maintainer's authoritative JCB blueprint is not present in this
checkout. The translation field and Extrusion layout/AJAX/permission
definitions therefore remain unreconciled. A source PR, passing focused
tests or rebuilt package is not evidence that those external definitions
were updated. Complete API-only compatibility requires a fresh installed
run covering all 51 writable families, GUID routes, conditional forms,
relationships, strict read-back and cleanup through native API contracts,
with remaining specialized operations explicitly accounted for.
