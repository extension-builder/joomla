# Extrusion engine: from SQL dump to component source

This document records the current Extrusion contract and the implementation
roadmap for pointing JCB at a component folder on disk and extruding its
administrator area into JCB definitions.

**Implementation status:** phases 0 through 5 are implemented under
`Componentbuilder/Extrusion` — 77 classes covering discovery, reading,
resolution, writing and code extraction. What remains is the graphical
interface and the retirement of the legacy `Helper` stack (§4, phase 6), both
of which need `admin/**` and therefore a separate, explicitly authorised
change. Section 7 records what the working implementation proved and the
places where reality differed from this design.

It uses the labels defined in the [architecture guide](README.md): **current
contract** is behavior found in the source; **placement rule** is inferred from
consistent organization in the tree; **proposed** is design that does not exist
yet.

The graphical user interface, the folder picker, and the request/permission
surface are explicitly out of scope here. This roadmap covers only the library
classes under `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Extrusion`
and their contracts, so that the interface work can be done against a finished
service API.

## 1. Current contract

### 1.1 Composition

Three classes in one linear inheritance chain, all doing their work in
constructors:

| Class | Lines | Responsibility |
| --- | --- | --- |
| [`Helper\Mapping`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Extrusion/Helper/Mapping.php) | 561 | Read `buildcompsql`, split it, parse `CREATE TABLE`/`INSERT INTO`, build `$this->map` |
| [`Helper\Builder`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Extrusion/Helper/Builder.php) | 347 | Write `#__componentbuilder_field`, `admin_view`, `admin_fields` rows |
| [`Helper\Extrusion`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Extrusion/Helper/Extrusion.php) | 103 | Link created views into `#__componentbuilder_component_admin_views` |

`Extrusion extends Builder extends Mapping`. There is no factory, no service
provider, no interface, and no injected dependency. `Factory::getApplication()`
and `Factory::getDbo()` are reached directly, and every failure path is an
`enqueueMessage()` from inside a parser.

Ownership: one test,
[`ExtrusionContractTest`](../../libraries/vendor_jcb/tests/VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionContractTest.php),
covers all three classes.
[`review-findings.md`](review-findings.md) already records that this stack
"should not be used as a pattern for compiler extraction, API work, or a new
service."

### 1.2 Entry point

`admin/src/Model/Joomla_componentModel.php:1406`, inside `save()`:

```php
if (isset($data['buildcomp']) && 1 == $data['buildcomp'])
{
    $extruder__ = new Extrusion($data);
}
```

The only input is `$data['buildcompsql']` — a base64 SQL dump pasted into the
`joomla_component` form (`admin/forms/joomla_component.xml:166`). `Builder`
zeroes `buildcomp` and `buildcompsql` back out of `$data` so the dump is not
persisted.

### 1.3 How field properties are guessed today

1. `Mapping::setMap()` splits the dump with `DatabaseDriver::splitSql()` and
   keeps `CREATE TABLE` and `INSERT INTO` statements.
2. `getTableName()` strips the component `name_code` prefix from the table
   name; the remainder becomes the view name.
3. `getColumns()` **creates a real temporary table in the live JCB database**
   (`jcb_extrusion_<uniqid>`) from the extracted column definitions, calls
   `getTableColumns()` to read MySQL's normalized metadata, then drops it.
4. `prepareFieldDetails()` reads the column `Comment`. **If the comment is
   JSON it becomes `$data->config`**, and `getLabel()`/`getType()` prefer
   `config->label` and `config->type` over anything derived. This is the
   "notes in the schema" mechanism the new work must preserve as the highest
   precedence tier.
5. Everything else is derived: `$dataTypes` maps a SQL type to a JCB field
   type (`VARCHAR`→`Text`, `DATETIME`→`Calendar`, …), size comes from the
   type's parenthesised length, and non-catalogue sizes/defaults collapse to
   `Other` plus an `…Other` value.
6. `Builder::setField()` guesses roles by substring on the column name:
   `name`/`title` → title, `alias` → alias, `desc` → description, and the
   first five remaining `Text` fields → list columns.
7. `Builder::setFieldXML()` calls
   `ComponentbuilderHelper::getFieldTypeProperties()` with only six settings
   (`name`, `description`, `message`, `label`, `default`, `hint`) and stores
   the resulting `<field … />` string JSON-encoded in `field.xml`.

### 1.4 Defects and limits that constrain the design

These are facts about the current code, not speculation, and each one is a
requirement on the replacement:

- **Live DDL from untrusted input.** Step 3 executes `CREATE TABLE` derived
  from a user-supplied file against the production JCB database, once per
  table. It also makes the parser untestable without a database.
- **No GUIDs.** `#__componentbuilder_admin_view.guid` and
  `#__componentbuilder_field.guid` exist and Package distribution depends on
  them, but `Builder` inserts with `db->insertObject()` and never sets one.
- **Not idempotent.** Re-running produces a second complete set of
  `(dynamic build)` views and fields. There is no source-to-definition
  identity.
- **`array_search()` returning `0`.** Recorded as a known defect in
  [testing.md](../development/testing.md): the first configured list field
  gets zeroed list/sort/search/filter flags.
- **No language awareness.** Labels are the column name in title case.
  Nothing reads `.ini`.
- **No form awareness.** A component's `forms/*.xml` — the authoritative
  record of field type, options, `showon`, validation, and fieldsets — is
  ignored entirely.
- **Bypasses the Data pipeline.** `VDM\Joomla\Data\{Item,Items}` is JCB's
  storage contract: it resolves insert versus update from the `guid` and encodes
  each value per the `store` declared in the Table class. `Builder` instead
  writes with `db->insertObject()` and hand-applies `base64_encode()` to
  `admin_view.sql`, which is exactly the encoding the pipeline would have
  applied itself (§3.12).

## 2. Objective

Point JCB at a folder that contains a Joomla component — installed under
`administrator/components/com_x`, or merely unzipped anywhere reachable — and
have it build the component's administrator area as JCB definitions.

Property precedence, highest first:

1. **Table definition class** — if the component was built by JCB, its
   `$tables` map is the source of truth for its whole infrastructure, and the
   only artifact carrying relationships, per-field GUIDs, and storage
   encoding (§3.5).
2. **SQL column comment JSON** — the author's explicit JCB notes.
3. **Form XML attributes** — the component's real field definitions.
4. **Derived** — SQL type, column name, and naming heuristics.

Every value carries the tier that produced it, so the run can explain itself
and a low-confidence guess is visible rather than silent.

Language constants are never stored. `label="COM_X_ITEM_NAME_LABEL"` is
resolved through the component's own `en-GB` `.ini` files to `Name`, and only
the resolved English string enters JCB.

## 3. Proposed architecture

### 3.1 Container rules

These are binding on every class in this roadmap, and they come before the
domain design because they determine its shape.

1. **Everything is resolved from the container.** A consumer never constructs
   a collaborator. It receives it, or it asks the container for it.
2. **`new` appears only inside `Service/*.php` providers.** The providers *are*
   the container's own construction code — "the containerized initialization of
   anything must still come from the codebase of the container." No `new` in a
   reader, resolver, writer, orchestrator, registry, or call site.
3. **`Factory::_()` only at the outermost entry seam.** Per AGENTS.md, static
   factory resolution is a composition entry point, not something new classes
   add. The interface layer, console command, or API controller resolves the
   one entry service; every class below it takes typed constructor
   dependencies injected by its provider.
4. **A service that needs no constructor arguments is still resolved from the
   container.** It gets an alias and a `share(..., true)` registration like
   any other, so it can gain a dependency later without touching a caller.
5. **No per-item value objects.** Anything else would mean a `new` per column,
   per field, and per fieldset — hundreds per run.

Rule 5 is the one with real design consequences, and JCB already provides the
answer.

### 3.2 State lives in focused registries, not in value objects

The placement rule from `Compiler/Builder` is a **focused, path-addressed
registry, shared in the container**, declared as an empty final class over the
existing abstraction:

```php
final class Schema extends Registry implements Registryinterface
{
}
```

`VDM\Joomla\Abstraction\Registry` already supplies fluent
`set()`/`add()`/`get()`/`remove()`/`exists()` over dotted paths and returns
`self`. So a parsed column becomes a path, not an object:

```php
$this->schema->set("table.{$table}.column.{$name}.type", $type);
```

AGENTS.md forbids collecting unrelated state in one global registry, so the
domain gets several small ones, each shared and each with one subject:

| Registry service | Holds | Written by |
| --- | --- | --- |
| `Extrusion.Registry.Source` | source path, code name, layout family, version | Discovery |
| `Extrusion.Registry.Inventory` | located artifacts and the tier that found each | Discovery |
| `Extrusion.Registry.Table` | the JCB `$tables` map: relationships, GUIDs, store, tabs | Reader |
| `Extrusion.Registry.Schema` | tables, columns, types, keys, decoded notes | Reader |
| `Extrusion.Registry.Form` | fieldsets, fields, raw attribute bags, options | Reader |
| `Extrusion.Registry.Language` | constant → English string catalogue | Reader |
| `Extrusion.Registry.View` | classified templates and layouts, split into PHP and HTML | Reader |
| `Extrusion.Registry.Resolved` | final per-field values, each with its origin | Resolver |
| `Extrusion.Registry.Report` | matched, unmatched, guessed, skipped, unresolved | all |

Indicative paths:

```
source.code_name                                  = 'com_example'
source.layout                                     = 'JoomlaFour'
inventory.form.item.path                          = '…/admin/forms/item.xml'
inventory.form.item.tier                          = 'profile'
schema.table.example_item.column.name.type        = 'VARCHAR'
schema.table.example_item.column.name.notes.type  = 'Text'
form.item.field.name.attribute.label              = 'COM_EXAMPLE_ITEM_NAME_LABEL'
language.COM_EXAMPLE_ITEM_NAME_LABEL              = 'Name'
resolved.item.field.name.label.value              = 'Name'
resolved.item.field.name.label.origin             = 'xml'
report.unmatched.column.example_item.legacy_flag  = 'no form field'
```

Because a registry is shared and mutable, **`reset()` is the run boundary.**
Every registry is cleared at the start of a run, so a second extrusion in the
same request cannot inherit the first one's state. This is the same hazard
AGENTS.md records for the Package factory, handled explicitly rather than left
to chance.

### 3.3 Bounded context, not an inheritance chain

Placement rule, from `Search`, `Fieldtype`, `File`, and `Power`: a domain gets
an abstract `Factory` extending `VDM\Joomla\Abstraction\Factory`, a `Config`,
one or more `Service/*.php` providers registering aliased shared services, an
`Interfaces/` folder, and small single-purpose classes.

Extrusion gets the same. The legacy `Helper\*` stack is frozen in place and
keeps its contract test until the new pipeline is proven, then becomes a thin
delegate.

```
Componentbuilder/Extrusion/
├── Factory.php                     abstract, extends Abstraction\Factory
├── Config.php                      extends Abstraction\ComponentConfig
├── Extruder.php                    the fluent entry service
├── Service/
│   ├── Extrusion.php               Extruder, Config          <- the only
│   ├── Registry.php                the nine registries         files that
│   ├── Discovery.php               Scanner, Manifest, Layout    contain `new`
│   ├── Reader.php                  Table, Schema, Sql\*, Form, Language, View
│   ├── Resolver.php                Precedence, Fieldtype, Role, …
│   └── Writer.php                  Field, AdminView, AdminFields, …
├── Interfaces/
│   ├── ExtruderInterface.php       LocatorInterface.php
│   ├── LayoutInterface.php         SchemaReaderInterface.php
│   ├── FormReaderInterface.php     LanguageReaderInterface.php
│   ├── TableReaderInterface.php    RelationResolverInterface.php
│   ├── PrecedenceInterface.php     FieldtypeMapperInterface.php
│   └── WriterInterface.php
├── Registry/
│   ├── Source.php  Inventory.php  Table.php  Schema.php
│   └── Form.php  Language.php  View.php  Resolved.php  Report.php
├── Discovery/
│   ├── Scanner.php                 bounded, guarded recursive walk
│   ├── Manifest.php                find and read com_x.xml
│   └── Locator/{Table,Schema,Form,Language,View}.php
├── Layout/
│   ├── JoomlaThree.php  JoomlaFour.php  JoomlaFive.php  JoomlaSix.php
│   └── Heuristic.php               content-signature fallback
├── Reader/
│   ├── Table.php                   static token parse of the $tables map
│   ├── Schema.php
│   ├── Sql/{Splitter,CreateTable,Insert}.php
│   ├── Form.php
│   ├── Language.php
│   └── View/{Template,Layout,Split}.php   the `?>` PHP/HTML split
├── Resolver/
│   ├── Precedence.php  Fieldtype.php  Language.php
│   ├── ViewName.php  Role.php  Tab.php  Condition.php  FieldXml.php
│   └── Relation.php                `link` -> JCB relationship definitions
└── Writer/
    ├── Field.php  AdminView.php  AdminFields.php
    ├── AdminFieldsConditions.php  AdminCustomTabs.php
    ├── Template.php  Layout.php
    └── ComponentAdminViews.php
```

`Layout\*` implementations are selected in the provider, never by a consumer
conditional, and all four version keys are registered per the AGENTS.md rule
even though J5 and J6 are thin variants of J4 today.

### 3.4 Invert the compiler's own placement map

We do not have to invent knowledge of where a component keeps its files. **The
compiler already carries that map, and it is the same map that put the files
there in the first place:**

- [`admin/compiler/joomla_3/settings.json`](../../admin/compiler/joomla_3/settings.json)
- [`admin/compiler/joomla_4/settings.json`](../../admin/compiler/joomla_4/settings.json)
  (also serving J5 and J6)

Each holds two objects: `create`, the folder tree the compiler makes, and
`move`, a map of `template file → { path, newName, type }` describing where
every generated artifact is written. Inverting `move` gives the read locations
directly, which is why no path in the table below is hand-written:

| Artifact | J3 (`joomla_3` move map) | J4/J5/J6 (`joomla_4` move map) |
| --- | --- | --- |
| Install schema | `admin/sql/install.mysql.utf8.sql` | `admin/sql/install.mysql.utf8.sql` |
| Schema updates | `admin/sql/updates/mysql/*.sql` | `admin/sql/updates/mysql/*.sql` |
| Edit form XML | `admin/models/forms/<view>.xml` | `admin/forms/<view>.xml` |
| Item model | `admin/models/<view>.php` | `admin/src/Model/<Name>Model.php` |
| List model | `admin/models/<views>.php` | `admin/src/Model/<Name>Model.php` |
| Controller | `admin/controllers/<view>.php` | `admin/src/Controller/<Name>Controller.php` |
| Table | `admin/tables/<view>.php` | `admin/src/Table/<Name>Table.php` |
| View class | `admin/views/<view>/view.html.php` | `admin/src/View/<Name>/HtmlView.php` |
| Templates | `admin/views/<view>/tmpl/*.php` | `admin/tmpl/<name>/*.php` |
| Custom fields | `admin/models/fields/*.php` | `admin/src/Field/<Key>Field.php` |
| Validation rules | `admin/models/rules/*.php` | `admin/src/Rule/<Key>Rule.php` |
| Language | `admin/language/en-GB/en-GB.com_x.ini` | `admin/language/en-GB/com_x.ini` |

Those are the compiler's build-folder paths, so each `Layout\*` service also
carries the build-root to installed-root translation the `move` map's
`c0mp0n3nt/` prefix implies: `admin` → `administrator/components/com_x`,
`site` → `components/com_x`, `media` → `media/com_x`, `api` →
`api/components/com_x`. That single indirection lets one layout service match an
installed tree, an unzipped package, and a package that still has its top-level
`admin/` and `site/` folders.

**But this map is the default placement, not a guarantee.** It is exactly right
for anything JCB compiled and for any component following Joomla convention, and
it will be wrong for components that did their own thing — which many do.
Inverting `move` is therefore only tier 1 of the three-tier search in §3.6, and
the pipeline must never read a tier-1 miss as "this component has no schema."

When the map does not answer, the search falls back to what the artifacts
intrinsically are. This is why the file kind matters more than its location, and
these three kinds — in this order of importance — are the whole minimum viable
input:

| Kind | What we are actually looking for | Why it is critical |
| --- | --- | --- |
| `*.sql` | a statement containing `CREATE TABLE` | the schema: tables, columns, types, keys, and the JSON notes in column comments |
| `*.xml` | a document whose root is `<form>` containing `<field name=` | the field definitions: type, options, `showon`, validation, fieldsets |
| `*.ini` | keys matching `COM_<CODE>_` | the language strings, so labels become English rather than constants |
| `*.php` | a class extending a base table class and declaring a `$tables` array | the JCB table definition map — relationships, GUIDs, storage, tabs (§3.5) |

A component that supplies the first three can have its complete administrator
area rebuilt regardless of where it chose to put them. The SQL alone gets a
usable result; SQL plus form XML gets an accurate one; adding the `.ini` is what
makes it readable rather than a wall of constants.

The fourth is different in kind: it is optional, present only on JCB-built
components, and when it is there it outranks everything else. It is also the
only one whose location cannot be predicted from the placement map at all, so
it is found purely by signature.

### 3.5 The Table definition class, when the component has one

A component built by JCB carries something far better than a schema and a set
of form files: a **table definition class**. JCB's own is
[`VDM\Joomla\Componentbuilder\Table`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Table.php)
— 15,630 lines describing 51 tables — and it extends the core
[`VDM\Joomla\Abstraction\BaseTable`](../../libraries/vendor_jcb/VDM.Joomla/src/Abstraction/BaseTable.php),
which supplies the accessors (`tables()`, `fields()`, `get()`, `title()`,
`titleName()`, `exist()`) and the implicit default columns (`id`, `asset_id`,
`published`, `created`, `ordering`, …).

Its `protected array $tables` map is the source of truth for the entire
infrastructure of that project, and it holds things **no other artifact
carries**:

```php
'joomla_component' => [
    'system_name' => [
        'name'     => 'system_name',
        'guid'     => 'acfe906b-6e61-4f94-ae66-359e4bc3e4cc',
        'label'    => 'COM_COMPONENTBUILDER_JOOMLA_COMPONENT_SYSTEM_NAME_LABEL',
        'type'     => 'text',
        'title'    => true,
        'list'     => 'joomla_components',
        'store'    => NULL,
        'tab_name' => 'Details',
        'db'       => [
            'type' => 'VARCHAR(255)', 'default' => '', 'null_switch' => 'NULL',
            'GUID' => 'acfe906b-…', 'unique_key' => false, 'key' => true,
        ],
        'link'     => NULL,
    ],
```

What that buys us, measured against the heuristics the rest of this roadmap
would otherwise need:

| Key | Replaces | Why it matters |
| --- | --- | --- |
| `link` | nothing — **unavailable elsewhere** | the foreign-key relationship: target `table`, `component`, `entity`, `value`, `key`. Form XML does not express this |
| `guid` | the derived UUIDv5 of §3.12 | stable per-field identity already assigned by the source project, so re-runs match exactly |
| `store` | nothing — **unavailable elsewhere** | `base64` / `json` / `basic_encryption`, mapping straight onto `#__componentbuilder_field.store` |
| `title` | the `stripos($name, 'title')` guess | authoritative title field |
| `list` | the "first five Text fields" guess | which list view the field belongs to |
| `tab_name` | inference from XML `<fieldset>` | the intended tab grouping |
| `db` | SQL parsing | type, default, null switch, unique/index flags |
| `type` | the SQL-type lookup table | the real form field type |
| `fields` | nothing | subform subfield definitions |

So when this class is present it is **tier 0 — above the SQL column notes** —
and the precedence chain becomes:

1. **Table definition class** `$tables` map;
2. SQL column comment JSON;
3. form XML attributes;
4. derived.

`link` in particular is the reason this tier is worth building. Relationships
are the one part of a component's design that neither the schema nor the form
files record, and reconstructing them by guessing at column names
(`*_id` → some table) is exactly the kind of unreliable inference this feature
should avoid when an authoritative answer is sitting in the source tree.

**Finding it is a search, not a path.** JCB's copy lives under
`libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/`, but a different
JCB-built component puts its own copy wherever that project's power namespace
resolves to. `Discovery\Locator\Table` therefore matches on signature:

- a `.php` file declaring a class that `extends` some base table class and
  declares a `$tables` array property; and
- that parent declaring `implements TableInterface`-shaped accessors
  (`tables()`, `fields()`, `get()`, `title()`), which confirms the family and
  yields the default-column set.

Both files are wanted: the child holds the map, the parent holds the defaults
that the map omits.

**Read it statically; never include it.** The source tree is untrusted — it may
be an unzipped upload — so `require`/`include` is not an option, and neither is
`eval` on the extracted literal. `Reader\Table` parses with `token_get_all()`
and accepts **only** literal tokens: strings, numbers, `[`/`]`, `=>`, `,`,
`NULL`, `true`, `false`. Any variable, constant, concatenation, or function call
inside the array aborts the read, records the reason in `Registry\Report`, and
drops the run to tier 1. Reflection over an already-autoloaded class stays
available as an explicit opt-in for the case where JCB is reading a component
installed on the same site, but it is never the default.

A component with no such class simply has no tier 0, and the pipeline proceeds
on tiers 1–3 exactly as described below. Nothing about this tier is required
for a non-JCB component to work.

### 3.6 Three-tier discovery

Two different things in this document are called tiers, and they are
independent: **discovery tiers** are how a file is *found* (this section), while
**precedence tiers** are which source *wins* once several describe the same
field (§2, §3.5, §3.11). A file found at discovery tier 3 can still be the
top-precedence source — the table definition class is exactly that case.

Components do not have to obey the placement map of §3.4, so a miss there is
normal rather than fatal. Discovery tries three tiers in order and records
which one answered in `Registry\Inventory`:

1. **Placement map.** Ask the selected `Layout\*` service for the inverted
   `move` paths. Cheap, exact, and how any JCB-compiled or convention-following
   component resolves.
2. **Bounded pattern scan.** `Scanner` walks the tree once with an explicit
   depth cap, file-count cap, extension allowlist, symlink refusal, and
   realpath containment check against the source root, collecting `*.sql`,
   `*.xml`, `*.ini`, and `*.php`. Directory names that cannot hold what we want
   (`node_modules`, `.git`, `assets`) are pruned — note that `vendor` cannot be
   pruned here, because a JCB-built component's table definition class often
   lives inside its vendored power namespace.
3. **Content signature.** `Layout\Heuristic` classifies what tier 2 collected
   by looking inside: a `.sql` containing `CREATE TABLE`, an `.xml` whose
   document element is `<form>` and which contains `<field name=`, an `.ini`
   whose keys match `COM_<CODE>_`, and a `.php` matching the table-class
   signature of §3.5. This is what makes a non-standard component work.

`Discovery\Locator\Table` runs only at tier 3 — its target has no predictable
location, so there is no placement-map shortcut for it.

Discovery writes an inventory and stops. Nothing is interpreted and nothing is
written during discovery — that separation is what makes the pipeline unit
testable against fixture trees.

`Manifest` supplies the rest of the identity: `com_x.xml` gives the component
code name, version, and (through the presence of `src/`,
`services/provider.php`, or `tmpl/`) the layout family. The code name is what
lets `Resolver\ViewName` strip the table prefix, and it is also the
language-constant prefix.

### 3.7 Pure SQL reading

`Reader\Sql\CreateTable` replaces `Mapping::getColumns()` with a parser that
never touches a database. It must reproduce what MySQL was giving us for free,
which is the only real cost of this change:

- column name, type keyword, size/precision, unsigned, charset/collation;
- `NOT NULL` / `NULL`, `DEFAULT` (including `CURRENT_TIMESTAMP` and quoted
  empty string), `AUTO_INCREMENT`;
- `COMMENT '…'` with correct unescaping, since the comment carries the JSON
  notes and is the highest-precedence input;
- **table-level key clauses** — `PRIMARY KEY (…)`, `UNIQUE KEY … (…)`,
  `KEY … (…)`, `INDEX`, and inline `PRIMARY KEY`/`UNIQUE` on a column — so
  that the `2 = primary, 1 = unique, 0 = none` contract of
  `Mapping::getKeyStatus()` survives.

Parity is provable: run both implementations over JCB's own
`admin/sql/install.mysql.utf8.sql` (53 tables) and diff the column metadata.
That is the gate for retiring the temp-table path.

`Reader\Sql\Insert` keeps the existing seed-data capture that feeds
`admin_view.add_sql`, `source`, and `sql`.

### 3.8 Form XML reading

`Reader\Form` walks one `forms/<view>.xml` into `Registry\Form`, keeping each
field's raw attribute bag verbatim — not a curated subset — plus child
`<option>` elements, because JCB's `field.xml` is itself an attribute bag and
the whole point is to carry across attributes we hold no opinion about.

Two structural signals matter beyond the fields themselves and are captured in
the same pass:

- `<fieldset name label>` → JCB tabs (`admin_view.addtabs`,
  `#__componentbuilder_admin_custom_tabs.tabs`);
- `showon="a:1[AND]b:2"` → `#__componentbuilder_admin_fields_conditions.addconditions`.

Field-to-column matching is by `name` against the parsed columns. Unmatched
form fields and unmatched columns both land in `Registry\Report` — those two
lists are the most useful diagnostic the feature can produce.

### 3.9 Language resolution

`Reader\Language` reads the `en-GB` `.ini` files with
`parse_ini_string($content, false, INI_SCANNER_RAW)` — from a string, so it is
testable, and raw, so Joomla's `_QQ_`/quoting survives — and merges
`com_x.ini` with `com_x.sys.ini` (J3: the `en-GB.`-prefixed names).

`Resolver\Language` then resolves any value matching `/^[A-Z][A-Z0-9_]*$/`
through that catalogue. On a miss the constant is kept verbatim and recorded as
unresolved. It runs over `label`, `description`, `hint`, `message`, `<option>`
bodies, `<fieldset label>`, and note field content.

### 3.10 Field type mapping is data, not a hardcoded array

`#__componentbuilder_fieldtype.properties` is a JSON object whose
`properties0` entry is the `type` property, and **its `example` is the Joomla
XML type string**. The 45 seeded rows therefore already are the mapping:

```
calendar → Calendar     list → List           subform → Subform
editor → Editor         radio → Radio         checkboxes → Checkboxes
media → Media           user → User           accesslevel → Accesslevel
modal_menu → Modal Menu ModalSelect → ModalSelect   …
```

`Resolver\Fieldtype` is built from that catalogue, keyed case-insensitively on
the XML `type`, with three policies that the data forces:

- **Collisions.** Both `Text` and `Tel` advertise `type="text"`. Prefer an
  exact case-insensitive match on the field type `name`, then an explicit
  override table, then the lowest id — and record the choice.
- **Version scope.** `repeatable` is J3-only, `subform` is J4+. The resolved
  target major decides.
- **Unknown types are custom, not failures.** An XML `type="subjects"` with a
  matching `src/Field/SubjectsField.php` (J4+) or `models/fields/subjects.php`
  (J3) is the component's own field type and maps to JCB `Custom` /
  `CustomUser` — exactly what the seeded `subjects`/`staffusers` examples
  represent. The source file path is captured for phase 5.

`Mapping::$dataTypes` (SQL type → field type) survives only as the last tier,
for columns with no form field at all.

### 3.11 The precedence engine

`Resolver\Precedence` is the heart of the feature and the cheapest thing to
test. For each column it reads four registry paths — the table map entry under
`table.<table>.field.<name>`, the decoded notes under
`schema.…column.<name>.notes`, the matched form field under
`form.<view>.field.<name>`, and the raw column metadata — and writes, per
property, both a value and its origin:

```
resolved.<view>.field.<name>.<property>.value
resolved.<view>.field.<name>.<property>.origin   table | notes | xml | derived
```

Some properties exist at only one tier, and those need no contest: `link`,
`store`, and the per-field `guid` come from `table` or not at all, while
`showon` conditions come from `xml` or not at all. Precedence only arbitrates
where two tiers both have an answer.

Nothing else in the pipeline decides precedence, and the report is generated
straight from the origin distribution. The tier order is itself an option
(§3.14), so an author whose notes have gone stale can promote XML above them.

`Resolver\FieldXml` then composes the JCB `field.xml` attribute string by
passing the resolved settings into
`ComponentbuilderHelper::getFieldTypeProperties()` — the same call the current
`Builder` makes, but with the full resolved attribute bag instead of six
hardcoded keys.

### 3.12 Storage: the Data pipeline is the only writer

This is the part not to reinvent. JCB already has a complete, stable, dynamic
storage and retrieval pipeline, and the extrusion writers are consumers of it —
they do not model columns, encode values, or decide insert versus update.

The pipeline is [`VDM\Joomla\Data`](../../libraries/vendor_jcb/VDM.Joomla/src/Data),
and the two classes to use are `Item` for one entity and `Items` for many:

```php
// one entity
$this->item->table('layout')->set($layout);          // insert or update, decided for us

// many entities
$this->items->table('field')->set($fields);          // keyed on guid

// reading back
$existing = $this->item->table('admin_view')->get($guid);
```

Both are resolved from the container (`Data.Item`, `Data.Items`) and injected,
never constructed. `table()` returns `self`, so the active table is set
fluently.

**What the pipeline does for us**, verified in the source:

| Behavior | Where | Consequence for extrusion |
| --- | --- | --- |
| Insert or update decided by whether the `guid` already exists | `Data\Item::action()` → `'update'` / `'insert'` | idempotency is free; we supply a stable guid and call `set()` once |
| Values encoded per the field's `store` | `Model\Upsert` reads `store` from the Table class and applies `base64_encode` / `json_encode` | **we must pass raw values.** Encoding ourselves would double-encode |
| Values decoded on read | `Model\Load` applies the inverse | round-tripping is symmetric |
| Column set and types | the Table class (§3.5) | we never hand-map a column list |

That third row is the trap worth naming: today's legacy `Builder` does
`base64_encode($this->sql[$name])` by hand before writing `admin_view.sql`.
Going through `Data\Item` that becomes wrong, because `Model\Upsert` will encode
it again. Every writer in this design passes plain values and lets the pipeline
apply `store`.

**Why this is the right coupling.** The Table class is generated from JCB's own
definitions, so when JCB gains a field or changes a `store`, the pipeline
follows automatically and the extrusion writers keep working without edits.
Binding to `Data\Item` + the Table class means binding to the one thing in JCB
that stays current by construction.

Out of scope for now, by decision: subform storage as one-to-one and one-to-many
relationships. `Data\Subform`, `Data\MultiSubform`, and `Data\UsersSubform`
exist and are the eventual answer for `addfields`, `addtabs`, and `addconditions`
shapes; until then those are written as the JSON the columns already hold.

**Identity.** `set()` needs a stable `guid` to key on, from the best available
source:

1. **The table map's own `guid`**, when tier 0 supplied one — the source project
   already assigned a stable per-field identity, so a re-run matches exactly and
   a component that came out of JCB can line up with its original definitions.
2. **A deterministic UUIDv5-style GUID** from a fixed namespace plus
   `component code name + table name [+ column name]`, for everything else.

`Data\Guid::getGuid()` is v4 random and stays the generator for genuinely new
records. Because `set()` already resolves insert versus update, the `onExisting`
option (§3.14) is a policy layer above that mechanism — `skip` short-circuits
before the call, `replace` clears dependents first — not a reimplementation of
it.

Writers, in dependency order: `Field` → `AdminView` → `AdminFields` →
`AdminFieldsConditions` → `AdminCustomTabs` → `ComponentAdminViews`, then
`Template` and `Layout` (§3.13). Two tier-0-only values write straight through:
`store` onto the field definition, and `tab_name` onto the tab structures
instead of the fieldset inference. How `link` should land is the one mapping
still open — see §6.

### 3.13 Views, templates, and layouts

The admin area of §3.11 and §3.12 is fields and views. The view *layer* — what a view
renders — lives in JCB's `template` and `layout` tables, and both have the same
two-part shape.

**The PHP/HTML split.** A JCB layout or template file is one PHP file that is
really two artifacts. Everything above the closing `?>` is PHP; everything after
it is HTML. JCB stores them in two columns, confirmed against the Table class:

| JCB table | PHP column | HTML column | Switch |
| --- | --- | --- | --- |
| `template` | `php_view` (`editor`, `store: base64`, MEDIUMTEXT) | `template` (`editor`, `store: base64`, TEXT) | `add_php_view` (TINYINT) |
| `layout` | `php_view` (`editor`, `store: base64`, MEDIUMTEXT) | `layout` (`editor`, `store: base64`, TEXT) | `add_php_view` (TINYINT) |

`Reader\Layout` and `Reader\Template` therefore split one source file into:

1. the header — `defined('_JEXEC')`, `use` statements, the file docblock —
   which is **discarded**, because JCB regenerates it;
2. the remaining PHP above the final top-level `?>` → `php_view`, with
   `add_php_view = 1`;
3. everything after that `?>` → `template` / `layout`.

Edge cases that need a stated rule rather than a guess: a file with no closing
`?>` is all PHP (`add_php_view = 1`, HTML empty); a file whose only PHP is the
`_JEXEC` guard is all HTML (`add_php_view = 0`); and `?>` occurrences inside
strings or heredocs must not split the file, which is why this is a token scan
(`token_get_all()`) and not `strrpos($source, '?>')`.

Both columns carry `store: base64`, so per §3.12 the reader hands over **raw
PHP and raw HTML** and `Model\Upsert` encodes them.

**Which file is which.** The placement map discriminates by folder, and the
`type` key in `move` is the label:

| Location (J4+, J3 in parentheses) | `type` | JCB meaning |
| --- | --- | --- |
| `admin/tmpl/<name>/default.php` (`admin/views/<view>/tmpl/default.php`) | `list` / `single` | the view's main template |
| `admin/tmpl/<name>/edit.php`, `modal.php`, `modalreturn.php` | `edit`, `*_modal` | edit and modal templates |
| `admin/tmpl/<name>/default_<x>.php` | `template` | a JCB **template** |
| `admin/layouts/*.php` | `layout` | a JCB **layout** |
| `admin/layouts/<name>/*.php` | `layoutoverride`, `layoutitems`, `layoutfull`, `layoutlinkedview`, `layouttitle`, `layoutpublished`, `layoutmetadata` | per-view layout overrides |
| `site/tmpl/<name>/…` (`site/views/<view>/tmpl/…`) | same set | site view equivalents |
| `site/layouts/…` | same set | site layouts |

Two discriminations the map cannot make on its own, and how to resolve them:

- **List versus edit view.** In J4 both `ADMIN_VIEW.php` (`type: single`) and
  `ADMIN_VIEWS.php` (`type: list`) are written as `default.php`, into
  `admin/tmpl/<name>/` and `admin/tmpl/<names>/` respectively. The singular
  and plural sibling folders are the signal, corroborated by what accompanies
  them — `emptystate.php` and `default_body.php` mark a list; `edit.php` and a
  matching `forms/<view>.xml` mark an edit view.
- **Admin view versus custom admin view.** These share identical paths in both
  J3 and J4 — `admin/tmpl/<name>/` and `admin/layouts/`. Nothing in the layout
  distinguishes them. The discriminator is backing data: an admin view has a
  database table and a form XML; a custom admin view has neither. So this
  inference depends on §3.7 having run, and where it stays ambiguous the view is
  recorded in the report as unclassified rather than guessed into the wrong
  table.

**Most components are not JCB components.** Tier 0 will usually be absent and
the source will often be an older J3 component, so this section's value does not
depend on the table class. The `?>` split and the folder discrimination work on
any component that follows Joomla's own layout, and the report names whatever
could not be classified so the remaining manual work is a short, explicit list
rather than a re-read of the whole tree.

### 3.14 Entry point and options

`Extruder` is the single entry service: resolved from the container, fluent,
with a terminal `extrude()`. There is no request object to construct, so there
is nothing to `new`. This follows the established fluent pattern in
`Import\Status::table()`, `File\Manager::table()`, and
`Abstraction\Remote\Config::table()`, each of which returns `self`.

```php
// the one permitted static resolution: the outermost entry seam
$report = Extrusion\Factory::_('Extruder')
    ->reset()
    ->path('/var/www/html/administrator/components/com_example')
    ->component(42)
    ->mode('update')
    ->layout('auto')
    ->languageTag('en-GB')
    ->precedence(['table', 'notes', 'xml', 'derived'])
    ->onExisting('update')
    ->tabs(true)
    ->conditions(true)
    ->dryRun(false)
    ->extrude();
```

Each setter validates and writes into the shared `Config`, which every
downstream service receives by injection. `reset()` clears `Config` and all
nine registries and is the run boundary; `extrude()` returns
`Registry\Report`.

The option catalogue, which is the part worth getting right up front:

**Intent**

| Option | Values | Default | Meaning |
| --- | --- | --- | --- |
| `mode` | `create`, `update` | `create` | Build a fresh definition set, or merge into what the component already has |
| `component` | int | — | Target JCB component id |
| `onExisting` | `skip`, `update`, `replace` | `update` | What to do when a derived GUID already exists |

**Scope**

| Option | Default | Meaning |
| --- | --- | --- |
| `admin` | `true` | Administrator views and fields |
| `site` | `false` | Site views (future extension) |
| `tabs` | `true` | Derive tabs from `tab_name`, else from XML fieldsets |
| `conditions` | `true` | Derive field conditions from `showon` |
| `language` | `true` | Resolve constants to English strings |
| `translations` | `false` | Also import other language packs into `language_translation` |
| `relations` | `true` | Import `link` relationships when a table class supplies them |
| `code` | `false` | PHP custom-code extrusion (phase 5) |
| `include` / `exclude` | `[]` | Table or view name filters |

**Interpretation**

| Option | Default | Meaning |
| --- | --- | --- |
| `precedence` | `['table','notes','xml','derived']` | Tier order; reorderable |
| `tableClass` | `auto` | `auto`, `off`, or an explicit path to the table definition class |
| `layout` | `auto` | `auto`, `j3`, `j4`, `j5`, `j6` |
| `languageTag` | `en-GB` | Which translation supplies the strings |

**Safety**

| Option | Default | Meaning |
| --- | --- | --- |
| `dryRun` | `false` | Produce the report, write nothing |
| `strict` | `false` | Fail on unresolved constants or unknown field types instead of degrading |
| `depth` / `maxFiles` | `12` / `20000` | Scan caps |

`dryRun` deserves emphasis: this feature writes to the definition tables that
every future compile depends on, so being able to see the full report before
committing anything is the difference between a safe feature and a risky one.

No parser or resolver calls `enqueueMessage()`. They write to
`Registry\Report`, and only the entry-seam caller surfaces messages. That is
what makes the same services usable from the interface, the console, and a
future API without change.

The legacy `new Extrusion($data)` call in `Joomla_componentModel::save()` is
left untouched until the interface session wires this entry point.

## 4. Phases

### Phase 0 — freeze and characterize

Fixture trees for a J3 component and a J4+ component (JCB's own compiled
output is the obvious source), plus characterization tests pinning the current
`Mapping`/`Builder` output so the new pipeline can be diffed against it rather
than compared by eye.

Touches: tests only.

### Phase 1 — folder in, inventory out

`Config`, `Factory`, `Service/{Extrusion,Registry,Discovery}.php`,
`Registry\{Source,Inventory,Report}`, `Discovery\{Scanner,Manifest}`,
`Discovery\Locator\{Table,Schema,Form,Language}`,
`Layout\{JoomlaThree,JoomlaFour,JoomlaFive,JoomlaSix,Heuristic}`, `Extruder`
with its fluent setters and `reset()`, and the matching interfaces.

Deliverable: given a path, a report naming the schema file, the per-view form
XMLs, the language files, the table definition class if the component has one,
the detected layout family, the component code name, and which discovery tier
found each. No interpretation, no writes, and no database — entirely unit
testable against fixture trees.

This phase carries the containment work: realpath checks, symlink refusal,
depth/count caps, and the traversal, absolute-path, mixed-separator, and
symlink test cases AGENTS.md already requires of remote file operations.

It also carries a provider test proving every service resolves from the
container and that `reset()` fully clears state between two runs in one
request.

### Phase 2 — pure readers

`Reader\Table`, `Reader\Schema`, `Reader\Sql\{Splitter,CreateTable,Insert}`,
`Reader\Form`, `Reader\Language`, `Registry\{Table,Schema,Form,Language}`,
`Service/Reader.php`.

`Reader\Table` carries its own gate: a literal-only token parser, with tests
proving that a `$tables` array containing anything non-literal is refused
rather than partially trusted, and that no file from the source tree is ever
included or evaluated.

Deliverable: inventory → populated registries, with the live-DDL temp table
gone. Gate: column-metadata parity against the temp-table implementation over
`admin/sql/install.mysql.utf8.sql`.

### Phase 3 — resolve and build the administrator area

`Resolver\{Precedence,Fieldtype,Language,ViewName,Role,Tab,Condition,FieldXml}`,
`Resolver\Relation`, `Writer\*`, `Registry\Resolved`,
`Service/{Resolver,Writer}.php`.

Deliverable: the feature. Point at a component folder, get views and fields
with table-map or XML-sourced types and attributes, real English labels, tabs,
`showon`-derived conditions, `link`-derived relationships and `store` settings
where a table class supplied them, GUIDs, component linkage, a working
`dryRun`, and an idempotent second run. Fix the `array_search()` list-flag
defect here and retire it from the defect ledger.

### Phase 4 — templates and layouts

`Reader\View\{Split,Template,Layout}`, `Discovery\Locator\View`,
`Registry\View`, `Writer\{Template,Layout}`.

Deliverable: the view layer. Each template and layout file classified by the
placement map, split at the final top-level `?>` into `php_view` and
`template`/`layout` with `add_php_view` set, and written through `Data\Item`
with raw values so `store: base64` is applied once by the pipeline.

This phase is deliberately ahead of code extrusion because the split is
mechanical and verifiable: reassembling `php_view` and the HTML column
reproduces the source body, which makes a round-trip test the gate. Extracting
method bodies from a model (phase 5) has no equivalent check.

Tests worth having here: the `?>`-inside-string and heredoc cases, a file with
no closing tag, a guard-only file, and the singular/plural folder pair that
separates a list view from an edit view.

### Phase 5 — code extrusion

Last, and explicitly the speculative one. `Reader\Php` (no `php-parser` in the
repository, so `token_get_all()`), `Discovery\Locator\{Model,Controller,View}`,
and a method-to-column map:

| Joomla method | JCB column |
| --- | --- |
| `getItem()` | `admin_view.php_getitem` |
| `getItems()` | `admin_view.php_getitems` |
| `getListQuery()` | `admin_view.php_getlistquery` |
| `save()` | `admin_view.php_save` |
| `postSaveHook()` | `admin_view.php_postsavehook` |
| `getForm()` | `admin_view.php_getform` |
| `allowAdd()` / `allowEdit()` | `php_allowadd` / `php_allowedit` |
| `batchCopy()` / `batchMove()` | `php_batchcopy` / `php_batchmove` |
| `publish()` / `delete()` | `php_before_*` / `php_after_*` |

Deliverable: candidate snippets attached with a confidence score and the
`add_php_*` switches left off, never silently enabled. A diff view belongs to
the interface session.

### Phase 6 — retire the legacy stack

Wire the interface to `Extruder`, reduce `Helper\{Mapping,Builder,Extrusion}`
to delegates, migrate `ExtrusionContractTest` ownership onto the new classes,
and remove the wrappers only when no reference remains — the same sequencing
the [helper refactoring playbook](helper-refactoring.md) prescribes.

## 5. Sequencing notes

- Phases 1 and 2 are independent of the database and of Joomla's application
  object. They can be built and tested in full isolation, which is the main
  argument for the discovery/reading/resolution split.
- Phases 3 and 4 are the only phases that write, and every write goes through
  an injected `Data\Item` or `Data\Items` with the active table set by
  `table()`. No `db->insertObject()`, no hand-applied `base64_encode()` or
  `json_encode()` — the pipeline applies `store` from the Table class, so
  encoding in a writer double-encodes.
- `new` must appear in no file outside `Extrusion/Service/*.php`. This is
  cheap to enforce mechanically and worth a guard in the test project.
- Each new production declaration takes its `test-ownership.php` entry in the
  same change. Nothing enters `coverage-baseline.php`.
- `php bin/check-php-style.php --base=<merge-base>` and
  `php bin/check-test-ownership.php --base=<merge-base>` run from the test
  project on every phase.
- Nothing in phases 0–5 edits `admin/**` or `media/**`. The interface session
  will need those paths and therefore a GUI change record.

## 6. Open decisions

1. **How does `link` land in JCB?** A table-map relationship names a target
   `table`, `component`, `entity`, `value`, and `key`. JCB can express that as a
   generated custom field type querying the linked view, as a `dynamic_get`, or
   through `#__componentbuilder_admin_fields_relations`. Picking the right
   target — and what to do when the linked view is not itself part of this
   extrusion — is the main open design question in phase 3, and the reason
   `Resolver\Relation` is listed separately from the other resolvers.
2. **Where does the folder path come from?** A text field, a server-relative
   browse, or a `#__componentbuilder_server` entry. Affects `Config` only, but
   the containment rules depend on the answer.
3. **Do we import site views and modules in the same pass?** The
   administrator area is the stated first objective; the `site` scope option
   is reserved so this becomes an extension rather than a rewrite.
4. **Language: import or resolve only?** Resolving to English strings is
   required; the `translations` option covers the wider import if wanted.
5. **`mode` default.** `create` is the safer default, but most real use is
   probably `update` against an existing component definition.
6. **Collision override table for field types.** `Text`/`Tel` is the only
   collision in the seeded data today; whether that table is code or a new
   `fieldtype` column is worth deciding before phase 3.


## 7. What the implementation proved

Building it changed four things in this design, each found by running the
pipeline against real component trees rather than by re-reading the plan.

**One table identity, or the tiers never meet.** A schema declares its tables as
`#__example_item`; a table definition class names them `example_item`. Keyed
literally, the two registries never joined: the same table produced two
unrelated views, each seeing only half the available truth, and the duplicate
was written twice. `Resolver\Precedence::canonical()` now reduces both to one
identity and the assembler carries each registry's own key, so a view is
assembled once from everything known about it. Nothing in §3 anticipated this,
and it is the single most consequential correction.

**Manifest identity is a ranking problem, not a search.** Taking the first file
containing an `<extension>` element picks a compiler template out of a real
tree. That hijacked the component code name, which stopped the table prefix
being stripped, which stopped every form matching its table — so the entire XML
tier silently vanished and every property fell back to `derived`. Candidates are
now ranked by depth and by whether the file is named after the component. The
failure was invisible in the output: the run reported success and wrote a full
definition set that was quietly much worse than it should have been.

**Form matching needs alternates.** A component whose table prefix differs from
its own code name yields a view name that does not match its form file name. The
XML tier now tries the obvious alternates, and a field still has to carry the
right column name to be accepted.

**The shared string helper cannot be used here.** `StringHelper::safe()` reaches
into the running Joomla application for its transliteration parameters. That
broke the stated contract that readers and resolvers work without an
application, and made a bare run die silently rather than fail. A small
`Resolver\Text` does the humanising instead, which is all that was ever needed.

Two further notes for whoever continues this:

- **Boilerplate columns must be skipped.** JCB generates `id`, `asset_id`,
  `published`, `created`, `ordering` and the rest for every view from its own
  switches. Extruding them produced duplicate, unusable field definitions. The
  skip list is a `Config` option so a component with a genuinely meaningful
  `params` column can keep it.
- **`Locator\Table` runs only at discovery tier 3, and that is correct.** Its
  target has no predictable location, so there is no placement-map shortcut —
  yet what it finds outranks every other source. A file found by the weakest
  discovery tier can still be the strongest precedence tier; the two axes are
  independent, which is why §3.6 states them separately.
