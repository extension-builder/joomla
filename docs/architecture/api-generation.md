# API generation: complete web-services output for admin views

This document records the contract for the `api/` area the compiler emits for
a component, the Joomla web-services contract it must satisfy, and the design
that completes the four generated API classes so that every admin view with
the API option set gets a working, permission-aware JSON:API surface for
Joomla 4, 5 and 6.

**Implementation status:** phase 1 (the four generated classes) is
implemented under `Compiler/Architecture/Api` and wired through
`Architecture/AdminViews/EditView` and `ListView`. Phase 2 (the manifest
`<api>` block, written by `Architecture/Component/Details` when a view asked
for an API) is implemented. Phase 3 (route registration) is implemented as
placeholders: routes live in a `webservices` plugin that a JCB user creates in
the plugin area and links to the component, and the compiler fills the
`API_ROUTES` placeholders inside it (§4.7). The compiler never generates a
plugin on its own. Phase 4 (§8) extends the same API to the site views and
custom admin views, read-only resources whose shape is the dynamic get of
the view.

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
| `API_VIEW_SERIALIZER.php` | `api/src/Serializer/<View>Serializer.php` | any `add_api` (built by phase 1) |

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
	protected $relationship = [ /* linked, user, category fields, created_by, modified_by, tags */ ];
	public function __construct($config = [])  { /* bind ArticleSerializer, parent */ }
	public function displayItem($item = null) { /* drop fields the user may not view/access, parent */ }
	protected function prepareItem($item)     { /* decode stored values the model left raw, tags */ }
}

// api/src/Serializer/ArticleSerializer.php  (shared by both views)
class ArticleSerializer extends JoomlaSerializer
{
	use TagApiSerializerTrait;                 // when the view has tags
	public function author($item)    { return $this->related($item->author ?? null, 'authors'); }
	public function createdBy($item) { return $this->related($item->created_by ?? null, 'users'); }
	protected function related($value, string $type): Relationship { /* one Resource, or a Collection for many ids */ }
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
- Relationships are emitted from the field map. Every field that links to
  another table (`ComponentFields[...]['link']`), every user and category
  field, the users who created and last changed the record, and the tags
  become JSON:API relationships. A generated `api/src/Serializer/<View>Serializer.php`
  extends `JoomlaSerializer` with one method per relationship, and both
  JSON views bind it in their constructor, as Joomla's own components do.
  The related type is the list name of the linked admin view when the view
  is this component's, else the linked view's own name, else the linked
  table's name without the database and component prefix. The tags come from
  Joomla's `TagApiSerializerTrait` and are only related on the item, since
  the list model yields tag names rather than ids.

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
| `###API_VIEW_JSON_RELATIONSHIP###` | `EditView` | `Api\View\Relationships` | entries of `$relationship` (item) |
| `###API_VIEWS_JSON_RELATIONSHIP###` | `ListView` | `Api\View\Relationships` | entries of `$relationship` (list) |
| `###API_VIEW_SERIALIZER_HEADER###` | `EditView` | `Header` (`api.view.serializer`) | imports of the serializer |
| `###API_VIEW_SERIALIZER_RELATIONS###` | `EditView` | `Api\Serializer\Relations` | relationship methods of the serializer |

`###JCONTROLLERFORM_ALLOWADD###`, `###JCONTROLLERFORM_ALLOWEDIT###` and the
four header keys are unchanged. Everything that does not vary per view
(`edit()`, `delete()`, the read-only guards, the filter cleaning helper) is
template text, as the templates already do for the class shells.

Two more placeholders are not template keys but entries of the `Placeholder`
registry, the one `Joomlaplugin\*\Data` applies to the main class code of a
linked plugin. They are registered in both the `[[[KEY]]]` and the
`###KEY###` form:

| Placeholder | Set by | Renderer | Content |
| --- | --- | --- | --- |
| `[[[API_ROUTES]]]` | `Model\Joomlaplugins` | `Api\Plugin\Routes::get()` | body of `onBeforeApiRoute()`: every route of every view with an API |
| `[[[API_ROUTES_METHOD]]]` | `Model\Joomlaplugins` | `Api\Plugin\Routes::getMethod()` | the whole `onBeforeApiRoute()` method, with the signature of the compile target |

### 4.3 Renderer family

**Placement rule.** The API area is one generated objective, so its renderers
live under `Compiler/Architecture/Api/Controller`,
`Compiler/Architecture/Api/View`, `Compiler/Architecture/Api/Serializer` and
`Compiler/Architecture/Api/Plugin`. Their output is identical for Joomla 4, 5
and 6 and never built for Joomla 3 (the one signature that differs, the route
method of the plugin, is selected on `Config->joomla_version` inside the
renderer), so, following the system map's rule that a target class must earn
its existence, each renderer is one root class with no `Joomla*` variant.
They are registered by
[`Service/ArchitectureApi`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureApi.php)
as shared services keyed `Architecture.Api.Controller.<Name>`,
`Architecture.Api.View.<Name>`, `Architecture.Api.Serializer.<Name>` and
`Architecture.Api.Plugin.<Name>`, and injected into `EditView`, `ListView`
and `Model\Joomlaplugins` by their existing provider factories. Every
renderer takes typed constructor dependencies and reads only `Config` and
Builder registries; none resolves a factory.

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
| `Api\View\Relationships` | `ComponentFields` (type and link), `FieldNames`, `Tags`, `Component->admin_views` |
| `Api\Serializer\Relations` | `Api\View\Relationships` |
| `Api\Plugin\Routes` | the admin view links (`add_api`, the two names), `Api\Controller\RecordId::keys()`, `Config->joomla_version`, `Placeholder` |

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

Pagination needs nothing from the generated code: `ApiController::displayList()`
turns `page[offset]` and `page[limit]` into `list.start` and `list.limit` on
the list model (twenty items when no limit is given, 404 when the offset is
past the total), and `JsonApiView::displayList()` answers with the
`total-pages` meta and the `self`, `first`, `previous`, `next` and `last`
links from the model's pagination. The ordering a request asks for is kept
only when the list model's `filter_fields` carry the column expression, which
the generated `filter_fields` do for every entry of the ordering map.

### 4.6 Key resolution contract

`getRecordId()` returns the integer `id` from the input when present, else
loads the table through the item model by the first key present in the input,
in this order: `guid` when the table has one, then every column indexed as
`UNIQUE KEY`. A key that matches nothing yields `0`, which the base methods
turn into 404. The key routes of §4.7 rely on this: a route variable named
after the key lands in the request input, where `getRecordId()` finds it.

### 4.7 Route registration in the linked plugin

The compiler does not generate a plugin. The JCB user creates a plugin of the
`webservices` group in the plugin area, links it to the component, and writes
one of the two placeholders of §4.2 into its main class code: `[[[API_ROUTES]]]`
inside an `onBeforeApiRoute()` method of their own, or `[[[API_ROUTES_METHOD]]]`
in place of the whole method. `Model\Joomlaplugins::set()` registers both in
the `Placeholder` registry through `Api\Plugin\Routes::set()` right before
the linked plugins load, so `Joomlaplugin\*\Data` resolves them together with
`[[[Component]]]` and the other component placeholders; the Joomla 5 and 6
plugin assembler then finds the `on*` method by parsing the class code and
needs no `getSubscribedEvents()` entry for it.

The routes of one view, from its `add_api` option and the keys of §4.6,
`v1/<component>/<views>` being the resource path and the JSON:API type:

| Option | Method and path | Controller task | Variable pattern |
| --- | --- | --- | --- |
| List, Both | `GET v1/demo/articles` | `articles.displayList` | |
| Item, Both | `GET v1/demo/articles/:id` | `article.displayItem` | `(\d+)` |
| Item, Both | `GET v1/demo/articles/<key>/:<key>` | `article.displayItem` | `([0-9a-fA-F-]{36})` for the guid, `([^/]+)` for another key |
| Item, Both | `POST v1/demo/articles` | `article.add` | |
| Item, Both | `PATCH v1/demo/articles/:id`, `.../<key>/:<key>` | `article.edit` | as above |
| Item, Both | `DELETE v1/demo/articles/:id`, `.../<key>/:<key>` | `article.delete` | as above |

Every route carries `['component' => 'com_demo']`; the `GET` routes also carry
`'public' => false`, as `createCRUDRoutes()` does by default, so every request
authenticates. The routes are `\Joomla\Router\Route` objects passed to
`$router->addRoutes()`, fully qualified so the plugin needs no import.

The method placeholder selects its signature on the compile target: Joomla 4
gets `onBeforeApiRoute(&$router): void`, Joomla 5 and 6 get
`onBeforeApiRoute(\Joomla\CMS\Event\Application\BeforeApiRouteEvent $event): void`
and take the router from the event. The body placeholder assumes `$router`
is in scope, which both signatures provide. Both placeholders render their
first line bare and indent the following lines as a method body (two tabs)
or a class member (one tab), so the placeholder is written where the code
starts. A component whose views have no API renders a comment saying so.

### 4.8 Version axis

All selection is on the compile target, `Config->joomla_version`.
`Structuremultiple::hasApi()` already limits the API to targets at or above 4.
The renderers are not version-dispatched (§4.3); the `use` statements come
from the four target-selected `Header` classes, whose `api.*` cases gain the
exception, filter and helper imports the generated bodies need.

### 4.9 Record keys on save

**Current contract.** Creating and updating through the API runs Joomla's
`ApiController::save()` against the generated administrator model. Core hands
that model a shape the administrator form never produces: on `POST` it sets
the primary key to `null` and the form filter then drops the key altogether
(`Registry::exists()` is false for a null value), so the model receives no
`id` key and no `guid` key unless the client sent one; on `PATCH` it merges
every column the body omits from the stored row. The model state is
populated lazily from the application input, which in the API application
is the decoded JSON body.

Three generated pieces make that shape safe for every JCB component:

- **`Architecture/Model/RecordKeyFix`** (service `Architecture.Model.RecordKeyFix`,
  one root class, no `Joomla*` variant, no version branch) emits the opening
  block of every generated `save()`. `Architecture/Model/ItemSave` composes
  it before the `php_before_save` custom code, so it precedes every custom
  code, field script and generated line that reads the keys, in the
  administrator model and the site edit model alike, on Joomla 3 to 6:

  ```php
  $data['id'] = (int) ($data['id'] ?? 0);
  ```

  The primary key is never taken from the model state: under the API that
  state is populated from the body, and a body `id` would otherwise turn a
  create into an update of another record past `allowAdd()` alone. A create
  binds `id = 0`, as the administrator form already does. Callers that save
  a partial array under a model state must pass the key.

  On a table with a `guid` column (registered by the field builders either
  as the unique guid or, when the field carries a unique index, among the
  unique keys) the guid is the server's:

  ```php
  if ($data['id'] > 0)
  {
      $data['guid'] = (string) GetHelper::var('<view>', $data['id'], 'id', 'guid', '=', '<component>');
  }
  elseif (Factory::getApplication()->isClient('api'))
  {
      $data['guid'] = '';
  }
  else
  {
      $data['guid'] = (string) ($data['guid'] ?? '');
  }

  while (!GuidHelper::valid($data['guid'], '<view>', $data['id'], '<component>'))
  {
      $data['guid'] = (string) GuidHelper::get();
  }
  ```

  An existing record keeps the guid it was stored with, whatever the request
  carries; the API never takes a guid from the request; the administrator
  form keeps a valid unique guid it was seeded with; and a record without a
  valid unique guid, new or stored that way, gets one. The component code is
  passed explicitly, so the check does not depend on the request's `option`.
  `Creator/Builders` force-loads the GuidHelper power
  (`9c513baf-b279-43fd-ae29-a585c8cbc4f0`) the moment it registers a guid
  column, as it does the encryption power for an encrypted field, so the
  call resolves whether or not the component adds powers itself. The shipped
  `saveGUIDPower` custom code keeps working unchanged after this block (it
  finds a valid guid and passes); it is no longer needed for the guid.

  On a view with an alias, a new record without the alias key gets it as an
  empty string, so the generated table's `check()` builds the alias from the
  title, as a form submit with an empty alias does; the model's own alias
  generation is gated on administrator task names and never runs under the
  API.

- **`Api\Controller\GetModel`** builds the model with `ignore_request` unless
  the caller set it, as Joomla's `FormController` does. Core's `save()`
  builds the model without it and reads the state only after the save; the
  lazy `populateState()` would then replace the new id with the request's,
  and `add()` would answer "Check-in failed".

- **`Api\Controller\RecordId::keysOfFields()`** derives the unique keys from
  the view's field definitions for `Api\Plugin\Routes`, which renders while
  the component data loads, before any field builder has filled the key
  registries `keys()` reads. The generated controller and the plugin routes
  therefore agree on the guid and unique-key routes of §4.7.

The JSON body remains a body, not a form: a field the client omits stays
absent, custom code that reads such a key unguarded logs a warning (printed
into the response where errors are displayed), and a `NOT NULL` column
without a default fails the insert under Joomla's strict SQL mode. Completing
a create body with the form's defaults in `preprocessSaveData()` is a
possible follow-up, recorded in §9.

## 5. Phases

**Phase 1 — the four classes and the serializer (this change).** Touches:
the four API templates keep their paths and names and gain the placeholders
of §4.2; `settings.json` gains the `API_VIEW_SERIALIZER.php` template, built
by `Structuremultiple::buildApi()` for every view with an API; `JoomlaFour|Five|Six/Header` gain imports for
the `api.*` contexts; new `Architecture/Api/*` renderers, `Service/ArchitectureApi`,
`Factory` registration; `AdminViews/EditView` and `ListView` set the new keys.
Deliverable: a component compiled with `add_api` on a view produces
controllers and views that answer list, item, create, update and delete
requests with the admin permissions, once a route reaches them.

**Phase 2 — install the folder (implemented).** `component.xml` carries an
`###API_FILES###` placeholder after the administration block, which
`Architecture/Component/Details::set()` renders as
`<api><files folder="api"><filename>index.html</filename><folder>src</folder></files></api>`
when `Config->add_api` is set and as nothing otherwise. Deliverable: the
installer copies `api/` and the namespace map registers the `Api` namespace.

**Phase 3 — routes (implemented).** A `webservices` plugin created in the
JCB plugin area and linked to the component carries the `[[[API_ROUTES]]]` or
`[[[API_ROUTES_METHOD]]]` placeholder, which `Api\Plugin\Routes` fills with
the routes of §4.7 for every view that has an API, registered by
`Model\Joomlaplugins` before the plugins load. The compiler does not generate
a plugin on its own.

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
- End-to-end proof: the API test harness (`.github/api-tests/run.sh`, run by
  the `API tests` workflow) installs Joomla 6.1.2 and the released JCB
  package on a local database, installs this working tree over it, links a
  `webservices` plugin carrying `[[[API_ROUTES_METHOD]]]` to the shipped
  demo component (`libraries/vendor_jcb/tests/api/seed-webservices-plugin.php`),
  compiles and installs the demo, mints a token
  (`libraries/vendor_jcb/tests/api/token.php`) and drives
  `v1/demo/looks` through PHP's built-in server with
  `libraries/vendor_jcb/tests/api/scenarios.php`: create without keys,
  create with a body id and guid, read by id and by guid, update without
  and with a client guid, list, a concurrent burst of creates that must
  yield distinct guids, trash, delete and the final 404. Its `--reproduce`
  mode asserts the failure the fix removed, so the harness is known to see
  the defect. The built-in server's SAPI name contains `cli`, which Joomla's
  `Uri::base()` treats as a console run, so the harness sets `live_site`
  while serving and clears it for console commands.

## 7. Open decisions

- Whether `GET` routes of a resource should ever be public; the generated
  routes set `'public' => false` and the generated controllers assume an
  authenticated user. A per-view option would need a GUI change.
- Whether encrypted fields should be decrypted in list output (they are, to
  match `getItem()` and the admin export) or withheld.
- Whether a per-field API selector in the GUI is wanted; the current answer is
  no: every column is rendered and the field permissions decide who sees it.
- A per-link API switch for site views and custom admin views (§8.9); until
  the GUI carries one, every such view of a component that has an admin API
  gets its resource.

## 8. Dynamic get resources: site views and custom admin views

### 8.1 Current contract

A site view and a custom admin view share one data model
([`Customview\Data`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Customview/Data.php)):
a safe `code` unique within its area, a `main_get` and optional `custom_get`
dynamic gets, and the PHP, JavaScript and CSS the developer adds. The main
get's `gettype` selects the generated model: `1` builds an item model whose
`getItem($pk)` reads the id from the `<code>.id` state, `2` builds a list
model whose `getListQuery()` and `getItems()` serve the list, paginated when
the get's `pagination` flag is set and otherwise with `list.limit` forced to
`0`. Custom gets of type `3` and `4` become model methods named by
`getcustom`, and the HTML view assigns each to a property named after the
method without its `get` prefix. Multi-row joins of the main get become
per-item methods `get<Name>($value)` that the templates call with a field of
the row ([`Dynamicget\JoinStructure`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/JoinStructure.php)).

The query is built from the dynamic get alone
([`Model\Dynamicget`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Model/Dynamicget.php)
and the `Dynamicget\*` renderers): the source table, the selection or
every column, joins merged into the row, the filters by id, user, access
levels, user groups, request variables and other expressions, the where,
order, group and global clauses, the calculations, and the post-processing
that decodes JSON and base64, resolves list values and linked names,
decrypts and prepares content. A main get whose source is custom SQL
(`main_source` 3) bypasses all of it.

Two behaviours of the generated models assume the web application: the
access check the link's `access` flag adds to `getItem()` and `getItems()`
redirects when `site.<code>.access` (or `<code>.access` for a custom admin
view) is not granted, and the empty-result fail-safe of `getItem()` enqueues
"Not found, or access denied" and redirects.

### 8.2 Objective

Every site view and custom admin view of a component that already has an
admin API gets a read-only resource whose result is exactly what the view's
model returns: the main get's item or list after every step of its
processing, with the custom gets attached. Permissions run before the model
so the API answers with status codes, filters and custom PHP stay the
model's, and pagination follows the get's own flag.

### 8.3 Naming and collisions

API names are resolved once per compile by `Architecture\Api\Resources`,
in this order:

1. Admin views reserve their single and list codes, with or without an API,
   so a later API on the admin view never changes a name.
2. Custom admin views take their code. A code that is already reserved is a
   defect of the component: the resource is skipped and the compiler emits a
   warning naming the collision.
3. Site views take their code. A code that is already reserved takes the
   `site_` prefix (`truck` becomes `site_truck`, class `Site_truckController`,
   type and path `site_truck`). A prefixed name that still collides is
   skipped with a warning.

The resolved name is the JSON:API type, the resource path segment, the
controller class and the JSON view folder. The routes of §4.7 and the file
build read the same map, so they always agree.

### 8.4 Generated output

| File | Template | Built for |
| --- | --- | --- |
| `api/src/Controller/<Name>Controller.php` | `API_DYNAMIC_VIEW_CONTROLLER.php` | item views (`gettype` 1), type `dynamic_single` |
| `api/src/View/<Name>/JsonapiView.php` | `API_DYNAMIC_VIEW_JSON.php` | item views, type `dynamic_single` |
| `api/src/Controller/<Name>Controller.php` | `API_DYNAMIC_VIEWS_CONTROLLER.php` | list views (`gettype` 2), type `dynamic_list` |
| `api/src/View/<Name>/JsonapiView.php` | `API_DYNAMIC_VIEWS_JSON.php` | list views, type `dynamic_list` |

`Component\Structuremultiple` builds them beside the view's own files in the
site and custom admin loops; the serializer is Joomla's `JoomlaSerializer`,
since these resources declare no relationships.

The `ContentMulti` keys, `<api name>|<KEY>`:

| Placeholder | Renderer | Content |
| --- | --- | --- |
| `###ApiName###`, `###apiname###` | `Api\Dynamic\Resource` | the resolved name, class case and lower case |
| `###API_DYNAMIC_VIEW(S)_CONTROLLER_HEADER###`, `###API_DYNAMIC_VIEW(S)_JSON_HEADER###` | `Header` (`api.dynamic.*`) | imports |
| `###API_DYNAMIC_VIEW(S)_CONTROLLER_GETMODEL###` | `Api\Dynamic\GetModel` | body of `getModel()`: the view's model from the `Site` or `Administrator` namespace, request state ignored |
| `###API_DYNAMIC_VIEW(S)_CONTROLLER_ALLOWVIEW###` | `Api\Dynamic\AllowView` | body of `allowView()` |
| `###API_DYNAMIC_VIEW(S)_CONTROLLER_EXPECTATIONS###` | `Api\Dynamic\Expectations` | docblock lines describing what the request must carry (§8.7) |
| `###API_DYNAMIC_VIEW(S)_JSON_PREPAREITEM###` | `Api\Dynamic\PrepareItem` | id guard, per-item join methods, and on the item the custom gets |
| `###API_DYNAMIC_VIEWS_JSON_META###` | `Api\Dynamic\Meta` | the custom gets of a list view as document meta |

Everything that does not vary per view is template text: the read-only
guards, the runtime field discovery, the model call.

### 8.5 Renderers

| Renderer | Reads |
| --- | --- |
| `Api\Resources` | the admin, custom admin and site view links, `Config->joomla_version` |
| `Api\Dynamic\Resource` | `Resources`, `Header`, `ContentMulti`, the renderers below |
| `Api\Dynamic\GetModel` | the view code and area |
| `Api\Dynamic\AllowView` | the link's `access` flag, the area, `Config->component_code_name` |
| `Api\Dynamic\Expectations` | the main get's filter, where, order, group, pagination and PHP hook flags |
| `Api\Dynamic\PrepareItem` | the main get's multi-row joins through `Dynamicget\JoinStructure`, the custom gets |
| `Api\Dynamic\Meta` | the custom gets |

They are registered by `Service/ArchitectureApi` as
`Architecture.Api.Resources` and `Architecture.Api.Dynamic.<Name>`, and
injected into `Architecture\SiteViews\Builder`,
`Architecture\CustomAdminViews\Builder`, `Component\Structuremultiple` and
`Api\Plugin\Routes`.

### 8.6 Permissions and public access

`allowView()` runs before the model, so a refusal is a 403 and never a
redirect. A site resource requires `site.<code>.access` when the link sets
`access`, and nothing otherwise beyond the API token. A custom admin
resource requires `core.manage` on the component, and `<code>.access` when
the link sets `access`. A site view whose link sets `public_access` gets its
GET routes registered with `'public' => true`, so no token is needed and the
model runs as the guest, with the guest's access levels in every
access-level and user-group filter of the get.

### 8.7 Request contract

Item resources answer on `GET v1/<component>/<name>` and
`GET v1/<component>/<name>/:id`; the id, when given, is the `<code>.id`
state `getItem()` reads and the `$pk` of the get's id filters. A get whose
filters do not use the id ignores it. List resources answer on
`GET v1/<component>/<name>`, paginated with `page[offset]` and `page[limit]`
when the get paginates, else with every record.

Filters, where, order and group are the get's own; nothing of the request
is mapped onto model state. A filter that reads a request variable (the
function-variable type, or another expression) reads it from the API
request under the same name the site URL would use. The custom PHP of the
get, before and after the item or the items and in the list query, runs
unchanged. When the result is empty the item resource answers 404 with the
component's "Not found, or access denied" message and the list resource
answers an empty list.

What the compiler can see, it documents: the generated `displayItem()` or
`displayList()` docblock lists each filter and clause of the get in words,
names the request variables it reads, says whether the resource paginates,
and notes where custom PHP may add conditions the compiler cannot describe.

### 8.8 Response shape

The resource attributes are the keys of the object the model returns,
discovered at runtime (`array_keys(get_object_vars($item))` for an item,
the union over the page for a list), so the dynamic get's selection, joins,
globals, calculations and post-processing decide the shape without a
compile-time field map. JSON:API needs an id: when the selection carries
none, the item takes the requested id and a list row its position on the
page.

Custom gets ride along: on an item resource each becomes an attribute named
as the HTML view names it (the method without its `get` prefix, made safe);
on a list resource each becomes a document `meta` entry under that name,
since they belong to the view and not to a row. The multi-row join methods
of the main get are called for every row with the row's own field and
attached under the joined table's name.

### 8.9 Enablement and the model change

There is no GUI switch yet. The rule is: a component whose admin views ask
for an API (§1) gets a resource for every site view and custom admin view
whose main get is not custom SQL. `Resources` carries the future per-link
check as a commented, noted hook, so the switch lands without moving code.

One generated behaviour changes: the empty-result fail-safe of `getItem()`
in site and custom admin models throws the existing "Not found, or access
denied" text as a 404 exception when the running client is the API, and
keeps enqueueing the message and redirecting otherwise. The access check
needs no change because the API controller refuses first.

### 8.10 Proof

Unit tests per renderer and for `Resources` under
`tests/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api`; the
site views, custom admin views and structure tests assert the new keys and
builds; the routes test covers the dynamic and public routes; the item
orchestration test covers the API fail-safe; provider catalogue, ownership
ledger and gates as before. The golden proof stays the Joomla 6 golden
master with a component carrying an admin API, a site view and a custom
admin view.

## 9. Known defects and follow-ups

Everything here was seen while driving the compiled demo through the harness
of §6 and is recorded so the next change starts from the observed contract,
not from the templates. None of it fails the scenarios the harness runs.

### 9.1 In the generated component

- **`getItem()` redirects under the API.** The generated administrator
  model's `getItem()` (`admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php`, a
  protected template) answers a record the user may not edit with an
  enqueued message and `$app->redirect('index.php?option=com_<component>')`.
  Under the API application that redirect is a `303` with an HTML location
  where JSON:API expects a `403`, and core's `save()` on `PATCH` reads
  through it. The site and custom admin models already throw a `404` when
  the running client is the API (§8.9); the administrator model needs the
  same branch, which touches the protected template and so waits for that
  permission.
- **`PATCH` re-encodes stored fields.** Core back-fills a `PATCH` body from
  the table's raw columns (`ApiController::save()` copies every column the
  body omits from the loaded table). The generated `save()` then encodes
  every json, base64 and encrypted field it finds set, so an omitted field
  of those kinds is stored encoded twice. The administrator form never sees
  this because it loads the decoded item first. The fix is to back-fill in
  `preprocessSaveData()` of the generated controller from the model's
  decoded `getItem()` instead, or to make the generated encoders idempotent;
  either way it is a contract of `Architecture/Model/ItemSave` and needs its
  own scenario in the harness (a view with a json or base64 field).
- **`getForm()` reads a body `id`.** When the body carries no positive `id`
  the generated `getForm()` falls back to the application input, which in
  the API application is the decoded body, so a create whose body names an
  existing record builds its form under that record's edit-state
  permissions. The save itself ignores the body id (§4.9); the form
  permissions should read the same `id` the save will bind, which is `0`
  for a create.
- **Omitted `NOT NULL` columns.** A create body is not completed with the
  form's defaults, so a `NOT NULL` column without a database default that
  the client omits fails the insert under strict SQL mode with a `500` that
  names the column. Completing a create body with the form defaults in the
  generated `preprocessSaveData()` (`Form::getData()` of a freshly loaded
  form, `array_replace`d with the body) would make the API accept the same
  minimal body the administrator form accepts.
- **Custom code that reads absent keys.** A `php_before_save` or `php_save`
  script that reads a body key unguarded logs a warning under the API, and
  a warning is printed into the JSON response where error display is on. The
  shipped `saveGUIDPower` custom code (JCB custom code 360) is the case in
  point: it is no longer needed for the guid once §4.9 is compiled, but a
  component that keeps it should guard its reads.

### 9.2 In the shipped demo data

- **The `guid` validation rule** (JCB validation rule `guid`, compiled to
  `src/Rule/GuidRule.php`) calls `trim($value)` before it checks whether the
  field is required, so a body without a `guid` logs a PHP deprecation on
  every API create. The rule should read `trim((string) $value)`. This is
  data, changed in JCB itself, not in the compiler.
- **The demo carries no `webservices` plugin**, so its API cannot be reached
  without the seeding step of the harness. Shipping one in the demo data
  (a plugin of the `webservices` group linked to the demo component with
  `[[[API_ROUTES_METHOD]]]` in its main class code, as
  `libraries/vendor_jcb/tests/api/seed-webservices-plugin.php` creates it)
  would let the harness, and any owner, compile a working API out of the box.

### 9.3 Owner actions

1. Update custom code 360 (`saveGUIDPower`) in JCB, or drop it from the demo
   views: the generated `save()` now owns the guid.
2. Correct the `guid` validation rule as above.
3. Add a `webservices` plugin to the demo data, then remove the seeding step
   from `.github/api-tests/run.sh`.
4. Decide the `getItem()` change to the protected administrator model
   template, and record it in the GUI change record if the template's
   permission is granted.
