# Joomla 6 view document assets

## Scope

This change consolidates generated custom-admin and site view styles and scripts
under one template-owned Web Asset Manager binding and one root URL lookup.
The implementation starts from `ceac1f8b7ca468e6d4dea4e57feb39988391606e`.

The affected templates are
`admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php`,
`CUSTOM_ADMIN_VIEWS_HTML.php`, `SITE_VIEW_HTML.php`, and
`SITE_VIEWS_HTML.php` in that directory. The active maintainer request on
23 November 2026 authorizes implementing the investigated plan, pushing
incremental commits to a new branch, and opening a pull request.

## Implementation contract

- Keep view JavaScript file creation, body expansion, dates, and installed paths.
- Move Joomla 6 view-file emission out of the libraries loader into a dedicated
  escaped script-list entry. Select behavior with the compiler target.
- Preserve Joomla 3–5 emission and ordinary admin list/edit behavior.
- Bind the manager before the conditional Joomla 6 jQuery loader uses it.
- Preserve loader, metadata, authored PHP, inline CSS, inline JS, and site-button
  hook order. Preserve the complete existing inline JS block and its receiver.
- Merge required dependencies with existing library seeds; preserve registry
  identities, guarded registration, activation, URLs, and version metadata.
- Keep generic configured-library and module include behavior unchanged.
- Exercise complete assembled blocks with recording and real Joomla managers,
  then run repository unit, style, ownership, GUI/API, and compiler golden gates.

## Compiler extension input

The Joomla 6 document-script emitter accepts two optional properties on the
current `$view['settings']` object. These are transient compiler extension
inputs, not database columns, authoring form fields, or persisted package
settings added by this change.

| Property | Type and default | Generated purpose |
| --- | --- | --- |
| `view_script_paths` | `list<string>`; absent means `[]` | Ordered extra script paths between the generated view file and the model script entries |
| `view_script_dependencies` | `list<string>`; absent means `[]` | Explicit Web Asset Manager script names merged with `core` and existing dependency seeds |

Inputs must be arrays containing nonempty strings. The compiler does not infer
them from PHP Document source or decode a JSON string supplied in their place.
Paths are escaped as PHP string literals after normal placeholder expansion;
they reference existing installed or external assets. They do not create or
copy script files. The normal JavaScript file generator continues to own the
generated view file's contents and installed location.

Existing compiler events provide the input before document preparation:

| Event | Source and timing |
| --- | --- |
| `jcb_ce_onBeforeBuildCustomAdminViewContent` | `Architecture/CustomAdminViews/Builder.php`; passes the current view and code by reference before `PrepareDocument::set()` |
| `jcb_ce_onBeforeBuildSiteViewContent` | `Architecture/SiteViews/Builder.php`; passes the current view and code by reference before `PrepareDocument::set()` |

Both paths receive the settings object produced by
`Compiler/Customview/Data.php`. Its model events also permit extra properties,
but the build events above provide the direct integration point for this
emission contract. A compiler plugin must scope its assignments to the
intended component and the view GUID verified from its authoritative package.
No Compiler GUID is inferred from a view name or from generated PHP.

For the Compiler acceptance fixture, the handler's assignments after selecting
the intended view are:

```php
$view['settings']->view_script_paths = [
	'administrator/components/com_componentbuilder/custom/marked.js',
];
$view['settings']->view_script_dependencies = ['jquery'];
```

This example supplies fixture data; it is not evidence that the maintained
Compiler definition or a deployed compiler plugin was changed. Supplying the
path also does not remove an existing authored legacy registration. That
removal must be reconciled with the actual source as described below.

`Service/ArchitectureView.php` selects the document-script implementation from
the compiler target through `Architecture.View.DocumentScripts`. The shared
implementation retains legacy placement; the Joomla 6 implementation supplies
`{TARGET}_VIEW_SCRIPT_ENTRY`, `{TARGET}_VIEW_SCRIPT_PATHS`, and
`{TARGET}_VIEW_SCRIPT_DEPENDENCIES`. Each call derives its output from the
current view. Per-view paths and dependencies are not retained on the shared
service for a later view to inherit.

## Source-to-output map

| Source | Generated result |
| --- | --- |
| `Architecture/View/JavaScriptFile.php` | Existing file creation, dates, version and expanded JavaScript body |
| `Architecture/JoomlaSix/View/JavaScriptFile.php` | Escaped media path entry inside the template script list |
| `Architecture/View/DocumentScripts.php` | Existing Joomla 3–5 library-loader placement |
| `Architecture/JoomlaSix/View/DocumentScripts.php` | Current view's file, extra path and dependency slots |
| `Architecture/View/PrepareDocument.php` | Existing document hooks plus target-selected script placement |
| Four Joomla 6 custom-admin/site HTML templates | One manager/root binding, styles, inline CSS, ordered scripts, original inline JS hook |
| `Architecture/JoomlaSix/View/LibrariesLoader.php` | Existing conditional jQuery policy using the template's manager |

## Compiler example and source ownership

The acceptance fixture uses the Compiler media path, its local Marked path,
and existing model scripts. It verifies the exact Compiler asset hash and
requires `core` and `jquery` before the local scripts. The generated-browser
fixture separately exercises all four templates through the real document
preparer, Joomla Power injector, Web Asset Manager and head renderers.

These inputs describe the supported compiler contract. This PR does not edit
`admin/src/View/Compiler/HtmlView.php` or a maintained JCB definition export.
The current generated Compiler file is not the authoritative authoring source.
A source integration must select the verified component/view GUID, supply the
structured inputs, and remove the original Marked registration in the same
definition change. If a definition is shared across targets, that integration
must also retain its Joomla 3–5 registration and placement.

The automatic jQuery setting keeps its existing meaning: enabling it activates
`jquery` and `jquery-noconflict`; disabling it adds neither automatically. A
view's explicit `jquery` dependency still loads jQuery when the setting is off,
without forcing `jquery-noconflict`.

## Verification

Focused tests execute the complete generated method, check registration guards
and asset identities, and resolve the real dependency graph. They cover seeded
libraries, duplicate paths, pre-registered assets, independent views, escaped
and external URLs, optional input validation, indentation, and unchanged hooks.
The browser suite checks executed script order and inline consumers; installed
Compiler GUI coverage checks its real component-details request and rendering.

Joomla 3–6 full-tree golden comparisons, installed GUI/API checks, and the PHP
matrix run in the repository workflows. Protected template changes are mapped
in the accompanying record under `docs/graphical-user-interface-changes/`.
