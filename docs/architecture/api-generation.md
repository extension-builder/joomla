# API generation: complete web-services output for admin views

This document records the contract for the `api/` area the compiler emits for
a component, the Joomla web-services contract it must satisfy, and the design
that completes the four generated API classes so that every admin view with
the API option set gets a working, permission-aware JSON:API surface for
Joomla 4, 5 and 6.

**Implementation status:** phase 1 (the four generated classes) is
implemented under `Compiler/Architecture/Api` and wired through
`Architecture/AdminViews/EditView` and `ListView`. Phase 2 (the manifest
`<api>` block) is a separate, small change. Phase 3 (route registration) is
deliberately out of scope: routes live in a `webservices` plugin that a JCB
user creates in the plugin area and links to the component, and the compiler
will only fill placeholders inside it. Section 7 records that footnote.

It uses the labels defined in the [architecture guide](README.md): **current
contract** is behavior found in the source; **placement rule** is inferred from
consistent organization in the tree; **proposed** is design that does not
exist yet.

## 1. Current contract

**Current contract.** When an admin view is linked to a component with the
`add_api` selector set (form
[`component_admin_views.xml`](../../admin/forms/component_admin_views.xml):
`0` None, `1` List, `3` Item, `2` Both),
[`Component/Structuremultiple::buildApi()`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Component/Structuremultiple.php)
copies four templates from
[`admin/compiler/joomla_4`](../../admin/compiler/joomla_4) into the component
build folder, as mapped by the `move.dynamic.api` bucket of
[`settings.json`](../../admin/compiler/joomla_4/settings.json):

| Template | Destination | Built for |
| --- | --- | --- |
| `API_VIEW_CONTROLLER.php` | `api/src/Controller/<View>Controller.php` | `add_api` = 3 or 2 |
| `API_VIEW_JSON.php` | `api/src/View/<View>/JsonapiView.php` | `add_api` = 3 or 2 |
| `API_VIEWS_CONTROLLER.php` | `api/src/Controller/<Views>Controller.php` | `add_api` = 1 or 2 |
| `API_VIEWS_JSON.php` | `api/src/View/<Views>/JsonapiView.php` | `add_api` = 1 or 2 |

Joomla 4, 5 and 6 all compile from the `joomla_4` template folder
(`Config::getJoomlaversions()` maps every target major at or above 4 to
`folder_key` 4); Joomla 3 has no API templates and `hasApi()` returns 0 for
it. `Config->add_api` is set to `1` when any view asked for an API and
`Compiler::cleanupApiFolderIfRequired()` removes the `api/` folder otherwise.

Every placeholder those templates contain is already populated: the
`###API_*_HEADER###` keys from the target-selected `Header` service
(`api.view.controller`, `api.view.json`, `api.views.controller`,
`api.views.json`), the `View/view/Views/views` names from
`Architecture/View/Placeholders`, and `###JCONTROLLERFORM_ALLOWADD###` /
`###JCONTROLLERFORM_ALLOWEDIT###` from the same renderers that fill the admin
form controller. What the templates do **not** contain is the problem: both
`JsonapiView` classes have empty bodies, the list controller only names its
content type, and nothing maps request filters, keys or permissions.

Joomla refuses an empty JSON view outright: `JsonApiView::displayList()` and
`displayItem()` construct an `OnGetApiFields` event whose `fields` argument
must be a non-empty array, so every request to a generated view ends in a
`BadMethodCallException` today.

## 2. The Joomla contract

Verified against the `joomla-cms` source for 4.4, 5.4-dev and 6.1-dev.

A third-party component exposes a resource through three parts:

1. `api/src/Controller/<Name>Controller.php` extending
   `Joomla\CMS\MVC\Controller\ApiController`, which already implements
   `displayList()`, `displayItem()`, `add()`, `edit()`, `save()` and
   `delete()`. It resolves models through the component's MVC factory; for
   the `api` client the core `MVCFactory` service provider (which the generated
   `services/provider.php` already registers) creates an `ApiMVCFactory`, whose
   `createModel()` and `createTable()` fall back to the `Administrator`
   namespace. The generated administrator `ListModel`, `AdminModel`, table
   and form are therefore what the API runs on.
2. `api/src/View/<Name>/JsonapiView.php` extending
   `Joomla\CMS\MVC\View\JsonApiView`, declaring `$fieldsToRenderList` and
   `$fieldsToRenderItem` (only declared fields are serialized) and optionally
   overriding `displayList()`, `displayItem()` and `prepareItem()`.
3. A plugin in the `webservices` group. `ApiApplication::route()` imports that
   group and dispatches `onBeforeApiRoute` before any component is booted;
   the route table is only filled there. `ApiRouter::createCRUDRoutes()` binds
   `GET base`, `GET base/:id`, `POST base`, `PATCH base/:id` and
   `DELETE base/:id` to one controller with the id rule `(\d+)`; route
   variables and defaults are copied into the request input.

The component manifest needs `<api><files folder="api">…</files></api>` for
the installer to copy the folder (`ComponentAdapter` reads
`$manifest->api->files`), and the namespace map registers
`<Prefix>\Component\<Name>\Api` automatically once
`api/components/com_<name>/src` exists.

Behaviour the design relies on:

- `displayList()` reads `page[offset]` and `page[limit]` only. Anything under
  `filter[...]` or `list[...]` must be copied into `$this->modelState` by the
  controller; the model is created with `ignore_request` so its own
  `populateState()` never runs.
- `displayItem()`, `edit()` and `delete()` read the integer `id` input, and
  `AdminModel::getItem()` casts the state id to an integer. Any other key has
  to be resolved to the integer id before the base methods run.
- The model name is derived by inflecting `$contentType` (singularize for the
  item model, the content type itself for the list model). JCB names are
  explicit and not always regular English plurals, so the generated
  controllers must never depend on that inflection.
- `save()` decodes the JSON body, validates it against the administrator form
  (`Form::addFormPath(JPATH_ADMINISTRATOR/components/com_x/forms)`), calls
  `$model->save()`, and checks the record in.
- `allowDelete()` exists only since 5.4.8 and 6.1.3; 4.4 checks
  `core.delete` inline. Both check the **core** action names.
- The exception code becomes the HTTP status for the API client
  (`ExceptionHandler`), so `NotAllowed` is 403, `ResourceNotFound` 404, and a
  `RuntimeException` carrying 409 is 409.

Version differences that matter are confined to the plugin (the legacy
`onBeforeApiRoute(&$router)` signature on 4, `SubscriberInterface` with
`BeforeApiRouteEvent` on 5 and 6) and to internals the generated code does
not touch (`$modelState` is a `CMSObject`, `State` or `Registry`; all three
answer `set()` and `get()`). The generated controllers and views are
therefore identical for Joomla 4, 5 and 6.

## 3. Objective

The generated API must be a one-to-one image of the admin views:

- The **edit view** (`view`, the single name) becomes the item resource: read,
  create, update and delete, addressable by `id` and by every unique key the
  table carries (`guid` when the view has one, and every column indexed as
  `UNIQUE KEY`).
- The **list view** (`views`, the list name) becomes a read-only list resource
  that returns every database column of the table, not only the columns the
  admin list shows, and honours the list model's search, filters and ordering.
- The **permission layer is the same code**. Nothing is hard-coded in the API:
  the controller-level checks come from `Creator\Permission` exactly as the
  admin controllers get them, the model-level access filtering and field-level
  form guards run because the API reuses the administrator models, and the
  field-level view permissions are applied to the rendered field lists from
  the same `PermissionFields` registry. A component built without permissions
  gets an API without them.
- No GUI change is needed. The existing `add_api` selector already decides
  which of the four classes are built.

## 4. Design

### 4.1 Generated output

For a view named `article` / `articles` in `com_demo`:

```php
// api/src/Controller/ArticleController.php  (item resource)
class ArticleController extends ApiController
{
	protected $contentType = 'articles';   // JSON:API "type", shared with the list
	protected $default_view = 'article';   // View/Article/JsonapiView

	public function getModel($name = '', $prefix = '', $config = [])
	{ /* explicit: list name => ArticlesModel, anything else => ArticleModel */ }

	public function displayItem($id = null) { /* resolve key, allowView(), parent */ }
	public function edit()                  { /* resolve key into input id, parent */ }
	public function delete($id = null)      { /* allowDelete(), resolve key, model delete, 404/409 */ }

	protected function getRecordId(): int   { /* id, then guid, then each unique key */ }
	protected function allowView(int $id): bool     { /* generated from Permission */ }
	protected function allowAdd($data = [])         { /* existing renderer */ }
	protected function allowEdit($data = [], $key = 'id') { /* existing renderer */ }
	protected function allowDelete(): bool          { /* generated from Permission */ }
}

// api/src/Controller/ArticlesController.php  (list resource, read-only)
class ArticlesController extends ApiController
{
	protected $contentType = 'articles';
	protected $default_view = 'articles';

	public function getModel(...)  { /* same explicit mapping */ }
	public function displayList()  { /* filter[...] and list[...] into modelState, parent */ }
	public function displayItem($id = null) { throw new NotAllowed(...); }  // and add(), edit(), delete()
}

// api/src/View/Article/JsonapiView.php and api/src/View/Articles/JsonapiView.php
class JsonapiView extends BaseApiView
{
	protected $fieldsToRenderItem = [ /* every table column */ ];   // or $fieldsToRenderList
	public function displayItem($item = null) { /* drop fields the user may not view/access, parent */ }
	protected function prepareItem($item)     { /* decode stored values the model left raw, tags */ }
}
```

**Decisions recorded here.**

- Both controllers carry the list name as `$contentType`, so the JSON:API
  `type` is the same for a list and an item of the same resource, as Joomla's
  own components do. `$default_view` keeps the two `JsonapiView` classes apart.
- `getModel()` is overridden in both controllers to map the list name to the
  list model and everything else to the item model. This removes the inflector
  from the picture and also stops a client from selecting an arbitrary model
  through the `model` input.
- The list controller is read-only by contract, not by omission: the four
  item tasks throw `NotAllowed`, so a route that is wired to the wrong
  controller fails loudly with 403 instead of half-working.
- `delete()` is generated in full because the base implementation checks the
  core action names inline on Joomla 4 and older 5, which bypasses per-view
  permission names.
- Relationships (`$relationship`) are not emitted in phase 1. Joomla's
  serializer resolves them through plugins or a per-type serializer, and the
  linker data in `ComponentFields[...]['link']` is the input for that later
  step.

### 4.2 Placeholders

The placeholders are `ContentMulti` keys, `<view code>|<KEY>`, set beside the
existing `API_*_HEADER` keys.

| Placeholder | Set by | Renderer | Content |
| --- | --- | --- | --- |
| `###API_VIEW_CONTROLLER_GETMODEL###` | `EditView` | `Api\Controller\GetModel` | body of `getModel()` |
| `###API_VIEW_CONTROLLER_RECORDID###` | `EditView` | `Api\Controller\RecordId` | body of `getRecordId()` |
| `###API_VIEW_CONTROLLER_ALLOWVIEW###` | `EditView` | `Api\Controller\AllowView` | body of `allowView()` |
| `###API_VIEW_CONTROLLER_ALLOWDELETE###` | `EditView` | `Api\Controller\AllowDelete` | body of `allowDelete()` |
| `###API_VIEW_JSON_FIELDS###` | `EditView` | `Api\View\Fields` | entries of `$fieldsToRenderItem` |
| `###API_VIEW_JSON_PERMISSIONS###` | `EditView` | `Api\View\FieldPermissions` | guard lines in `displayItem()` |
| `###API_VIEW_JSON_PREPAREITEM###` | `EditView` | `Api\View\PrepareItem` | body of `prepareItem()` |
| `###API_VIEWS_CONTROLLER_GETMODEL###` | `ListView` | `Api\Controller\GetModel` | body of `getModel()` |
| `###API_VIEWS_CONTROLLER_DISPLAYLIST###` | `ListView` | `Api\Controller\DisplayList` | state mapping in `displayList()` |
| `###API_VIEWS_JSON_FIELDS###` | `ListView` | `Api\View\Fields` | entries of `$fieldsToRenderList` |
| `###API_VIEWS_JSON_PERMISSIONS###` | `ListView` | `Api\View\FieldPermissions` | guard lines in `displayList()` |
| `###API_VIEWS_JSON_PREPAREITEM###` | `ListView` | `Api\View\PrepareItem` | body of `prepareItem()` |

`###JCONTROLLERFORM_ALLOWADD###`, `###JCONTROLLERFORM_ALLOWEDIT###` and the
four header keys are unchanged. Everything that does not vary per view
(`edit()`, `delete()`, the read-only guards, the filter cleaning helper) is
template text, as the templates already do for the class shells.

### 4.3 Renderer family

**Placement rule.** The API area is one generated objective, so its renderers
live under `Compiler/Architecture/Api/Controller` and
`Compiler/Architecture/Api/View`. Their output is identical for Joomla 4, 5
and 6 and never built for Joomla 3, so, following the system map's rule that a
target class must earn its existence, each renderer is one root class with no
`Joomla*` variant. They are registered by
[`Service/ArchitectureApi`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureApi.php)
as shared services keyed `Architecture.Api.Controller.<Name>` and
`Architecture.Api.View.<Name>`, and injected into `EditView` and `ListView`
by their existing provider factories. Every renderer takes typed constructor
dependencies and reads only `Config` and Builder registries; none resolves a
factory.

| Renderer | Reads |
| --- | --- |
| `Api\Controller\GetModel` | the two view names |
| `Api\Controller\RecordId` | `DatabaseUniqueKeys`, `DatabaseUniqueGuid`, `FieldNames` |
| `Api\Controller\AllowView` | `Creator\Permission` (`core.access`) |
| `Api\Controller\AllowDelete` | `Creator\Permission` (`core.access`, `core.delete`) |
| `Api\Controller\DisplayList` | `Filter`, `Sort`, `Search`, `Category`, `AccessSwitch`, `FieldNames` |
| `Api\View\Fields` | `ComponentFields`, `Config->default_fields`, `AccessSwitch`, `MetaData`, `FieldNames` |
| `Api\View\FieldPermissions` | `PermissionFields`, `Config->component_code_name` |
| `Api\View\PrepareItem` | `JsonString`, `JsonItem`, `JsonItemArray`, `BaseSixFour`, `ModelBasicField`, `ModelMediumField`, `ModelWhmcsField`, `ItemsMethodListString`, `Tags`, `ContentOne`, `Config->cryption_types` |

`ComponentFields` is the table map the compiler already builds per view in
`Creator/Builders::configureLayoutAndComponentField()`: for every stored
column its name, type, label, store encoding, tab, database definition with
`unique_key` and `key` flags, and the linker relation to another table. It is
the single source for the field lists, and the same map the component's
helper exports for runtime use.

### 4.4 Permissions

| Admin behaviour | Where it is generated | API behaviour |
| --- | --- | --- |
| `allowAdd()` / `allowEdit()` with per-view action names, `core.access` gate, `edit.own` ownership test | `Architecture/Joomla*/Controller/AllowAdd`, `AllowEdit` | same renderers fill the same methods on the item controller; `add()` and `edit()` call them |
| `canDelete()` per record, trashed-only rule | `Architecture/Joomla*/Model/CanDelete` | runs inside `AdminModel::delete()`; the API `delete()` adds the component-level `allowDelete()` from `Permission::getGlobal(view, 'core.delete')` |
| items the user cannot access removed from lists, access-level join, per-field strict emptying | `Model/ItemsStringFix`, `Model/ListQuery` | runs unchanged inside `getItems()` because the API list uses the admin `ListModel` |
| item access (`core.access` per record and globally) | `ItemsStringFix` (lists) | `allowView()` on `displayItem()`, generated from the same `Permission` lookups |
| field `edit` / `access` / `view` permissions on the form | `Model/GetForm` | run unchanged inside `save()` because the API validates against the admin form; additionally `FieldPermissions` drops `access` and `view` fields from the rendered field lists |
| no permissions configured | `Permission::actionExist()` false | the renderers emit the plain fallback, nothing extra |

The admin list only empties per-field values when the component parameter
`strict_permission_per_field` is on; the API applies the field `access` and
`view` permissions unconditionally to both the item and the list output,
matching the edit form, which is the stricter of the two.

### 4.5 List query contract

`GET v1/.../articles?filter[search]=x&filter[published]=1&list[ordering]=name&list[direction]=asc&page[limit]=20&page[offset]=40`

| Request | Model state | Source |
| --- | --- | --- |
| `filter[search]` | `filter.search` | always |
| `filter[published]` | `filter.published`, defaults to `''` (published and unpublished, no trashed) as in the admin | always |
| `filter[access]`, `filter[created_by]`, `filter[created]` | same names | when the view does not override the default field |
| `filter[<code>]` | `filter.<code>` | every `Filter` and `Sort` entry of the list, the values the admin filter form accepts, arrays only for multi filters |
| `filter[category]`, `filter[category_id]` | same names | when the view has a category |
| `list[ordering]` | `list.ordering` = the column expression (`a.<code>`, `<db>.<text>`, `c.title`) | the columns `SortFields` and `FilterFields` offer |
| `list[direction]` | `list.direction`, `asc` or `desc` | always |
| `page[limit]`, `page[offset]` | handled by `ApiController` | always |

Values are cleaned with `InputFilter` as strings (element-wise for arrays);
the generated list query already treats numeric and string values correctly.

### 4.6 Key resolution contract

`getRecordId()` returns the integer `id` from the input when present, else
loads the table through the item model by the first key present in the input,
in this order: `guid` when the table has one, then every column indexed as
`UNIQUE KEY`. A key that matches nothing yields `0`, which the base methods
turn into 404. This is what the future routes rely on:

```php
$router->addRoute(new Route(['GET'], 'v1/demo/articles/guid/:guid', 'article.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $defaults));
$router->addRoute(new Route(['PATCH'], 'v1/demo/articles/guid/:guid', 'article.edit', [...], $defaults));
$router->addRoute(new Route(['DELETE'], 'v1/demo/articles/guid/:guid', 'article.delete', [...], $defaults));
```

### 4.7 Version axis

All selection is on the compile target, `Config->joomla_version`.
`Structuremultiple::hasApi()` already limits the API to targets at or above 4.
The renderers are not version-dispatched (§4.3); the `use` statements come
from the four target-selected `Header` classes, whose `api.*` cases gain the
exception, filter and helper imports the generated bodies need.

## 5. Phases

**Phase 1 — the four classes (this change).** Touches: the four API
templates and `settings.json` are unchanged in path and name; the templates
gain the placeholders of §4.2; `JoomlaFour|Five|Six/Header` gain imports for
the `api.*` contexts; new `Architecture/Api/*` renderers, `Service/ArchitectureApi`,
`Factory` registration; `AdminViews/EditView` and `ListView` set the new keys.
Deliverable: a component compiled with `add_api` on a view produces
controllers and views that answer list, item, create, update and delete
requests with the admin permissions, once a route reaches them.

**Phase 2 — install the folder.** Touches: `component.xml` gains an
`###API_FILES###` placeholder rendered as
`<api><files folder="api"><folder>src</folder></files></api>` when
`Config->add_api` is set and as nothing otherwise, alongside the existing
`EXSTRA_*` placeholders. Deliverable: the installer copies `api/` and the
namespace map registers the `Api` namespace.

**Phase 3 — routes (footnote, not scheduled).** A `webservices` plugin created
in the JCB plugin area and linked to the component, carrying placeholders the
compiler fills with `createCRUDRoutes()` and the key routes of §4.6 for every
view that has an API. The compiler must not generate a plugin on its own.

## 6. Proof

- Unit tests per renderer under
  `tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api`, asserting
  exact generated fragments from deterministic registries, in the
  `ArchitectureTestCase` style.
- `EditViewTest` and `ListViewTest` assert the new keys are written.
- Provider catalogue, interface manifest, version-selection and container-key
  guards updated for the new provider.
- Golden proof: the Joomla 6 golden master with a component that sets
  `add_api` on at least one view; the diff must be limited to the four API
  files.

## 7. Open decisions

- Whether `GET` routes of a resource should ever be public (`createCRUDRoutes`
  fourth argument); the generated controllers assume an authenticated user.
- Whether encrypted fields should be decrypted in list output (they are, to
  match `getItem()` and the admin export) or withheld.
- Route prefix convention for the plugin: `v1/<component>/<views>` is
  assumed in this document.
