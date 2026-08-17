# Extrusion engine: from SQL dump to component source

This document records the current Extrusion contract and proposes the
implementation roadmap for pointing JCB at a component folder on disk and
extruding its administrator area into JCB definitions.

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
- **Bypasses the CRUD layer.** `VDM\Joomla\Data\{Item,Insert,Update,Load}`
  and `Data\Guid` exist and are the current placement rule for JCB writes.

## 2. Objective

Point JCB at a folder that contains a Joomla component — installed under
`administrator/components/com_x`, or merely unzipped anywhere reachable — and
have it build the component's administrator area as JCB definitions.

Property precedence, highest first:

1. **SQL column comment JSON** — the author's explicit JCB notes.
2. **Form XML attributes** — the component's real field definitions.
3. **Derived** — SQL type, column name, and naming heuristics.

Every value carries the tier that produced it, so the run can explain itself
and a low-confidence guess is visible rather than silent.

Language constants are never stored. `label="COM_X_ITEM_NAME_LABEL"` is
resolved through the component's own `en-GB` `.ini` files to `Name`, and only
the resolved English string enters JCB.

## 3. Proposed architecture

### 3.1 Bounded context, not an inheritance chain

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
├── Config.php                      source path, target major, options, caps
├── Extruder.php                    public orchestrator (the "entry class")
├── Service/
│   ├── Extrusion.php               Extruder, Config
│   ├── Discovery.php               Scanner, Manifest, Layout, Locators
│   ├── Reader.php                  Schema, Sql\*, Form, Language
│   ├── Resolver.php                Precedence, Fieldtype, Language, Role, …
│   └── Writer.php                  Field, AdminView, AdminFields, …
├── Interfaces/
│   ├── LocatorInterface.php
│   ├── LayoutInterface.php
│   ├── SchemaReaderInterface.php
│   ├── FormReaderInterface.php
│   ├── LanguageReaderInterface.php
│   ├── PrecedenceInterface.php
│   ├── FieldtypeMapperInterface.php
│   └── WriterInterface.php
├── Discovery/
│   ├── Scanner.php                 bounded, guarded recursive walk
│   ├── Manifest.php                find and read com_x.xml
│   ├── Inventory.php               resolved artifact set + provenance
│   └── Locator/{Schema,Form,Language}.php
├── Layout/
│   ├── Profile.php                 relative candidate paths per artifact kind
│   ├── JoomlaThree.php  JoomlaFour.php  JoomlaFive.php  JoomlaSix.php
│   └── Heuristic.php               content-signature fallback
├── Reader/
│   ├── Schema.php
│   ├── Sql/{Splitter,CreateTable,Insert}.php
│   ├── Form.php
│   └── Language.php
├── Model/
│   ├── Source.php  Table.php  Column.php
│   ├── Fieldset.php  FormField.php
│   └── ViewDefinition.php  FieldDefinition.php  Value.php
├── Resolver/
│   ├── Precedence.php  Fieldtype.php  Language.php
│   ├── ViewName.php  Role.php  Tab.php  Condition.php  FieldXml.php
├── Writer/
│   ├── Field.php  AdminView.php  AdminFields.php
│   ├── AdminFieldsConditions.php  AdminCustomTabs.php
│   └── ComponentAdminViews.php
└── Result/
    ├── Report.php                  found / resolved / guessed / skipped
    └── Message.php
```

### 3.2 Invert JCB's own structure knowledge

JCB already holds the authoritative per-version layout in
`admin/compiler/joomla_3/settings.json` and `admin/compiler/joomla_4/settings.json`:
a `create` folder tree and a `move` map of `template file → { path, newName,
type }`. Reversing that map — rather than hardcoding paths — is the honest way
to know where a component keeps things:

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

`Layout\Profile` also carries the build-root to installed-root translation the
`move` map's `c0mp0n3nt/` prefix implies: `admin` → `administrator/components/com_x`,
`site` → `components/com_x`, `media` → `media/com_x`, `api` →
`api/components/com_x`. That single indirection lets the same profile match an
installed tree, an unzipped package, and a package that still has its
top-level `admin/` and `site/` folders.

Per the AGENTS.md four-key rule, all of `JoomlaThree`, `JoomlaFour`,
`JoomlaFive`, and `JoomlaSix` are registered even though J5 and J6 are thin
variants of J4 today. Selection happens in the provider, not in consumers, and
the target major is validated against the supported catalogue before a
version-dispatched service is resolved.

### 3.3 Three-tier discovery

Components do not have to obey the layout. Discovery therefore tries, in
order, and records which tier answered:

1. **Profile lookup.** Ask each candidate `Layout\*` profile for its relative
   paths. Cheap, exact, and how a well-behaved component resolves.
2. **Bounded pattern scan.** `Scanner` walks the tree once with an explicit
   depth cap, file-count cap, extension allowlist, symlink refusal, and
   realpath containment check against the source root, collecting `*.sql`,
   `*.xml`, `*.ini`. Directory names that cannot hold what we want
   (`node_modules`, `.git`, `vendor`, `assets`, `media/js`) are pruned.
3. **Content signature.** Classify what tier 2 collected by looking inside: a
   `.sql` containing `CREATE TABLE`, an `.xml` whose document element is
   `<form>` and which contains `<field name=`, an `.ini` whose keys match
   `COM_<CODE>_`. This is what makes a non-standard component work.

Discovery produces an `Inventory` and stops. Nothing is interpreted and
nothing is written during discovery — that separation is what makes the whole
pipeline unit testable against fixture trees.

The `Manifest` reader supplies the rest of the identity: `com_x.xml` gives the
component code name, version, and (through `<extension … method>` plus the
presence of `src/`, `services/provider.php`, or `tmpl/`) the layout family.
The code name is what lets `Resolver\ViewName` strip the table prefix, and it
is also the language-constant prefix.

### 3.4 Pure SQL reading

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

### 3.5 Form XML reading

`Reader\Form` turns one `forms/<view>.xml` into an ordered list of
`Model\Fieldset` each holding `Model\FormField` objects. Per field it keeps
the raw attribute bag verbatim — not a curated subset — plus child `<option>`
elements, because JCB's `field.xml` is itself an attribute bag and the whole
point is to carry across attributes we do not have opinions about.

Two structural signals matter beyond the fields themselves and are worth
capturing in the same pass:

- `<fieldset name label>` → JCB tabs (`admin_view.addtabs`,
  `#__componentbuilder_admin_custom_tabs.tabs`);
- `showon="a:1[AND]b:2"` → `#__componentbuilder_admin_fields_conditions.addconditions`.

Field-to-column matching is by `name` against the parsed table columns.
Unmatched form fields (no column) and unmatched columns (no form field) are
both recorded in the report — those two lists are the most useful diagnostic
the feature can produce.

### 3.6 Language resolution

`Reader\Language` reads the `en-GB` `.ini` files with
`parse_ini_string($content, false, INI_SCANNER_RAW)` — from a string, so it is
testable, and raw, so Joomla's `_QQ_`/quoting survives — and merges
`com_x.ini` with `com_x.sys.ini` (J3: the `en-GB.`-prefixed names).

`Resolver\Language` then resolves any value matching
`/^[A-Z][A-Z0-9_]*$/` through that catalogue. On a miss the constant is kept
verbatim and recorded as unresolved. It runs over `label`, `description`,
`hint`, `message`, `<option>` bodies, `<fieldset label>`, and note field
content.

### 3.7 Field type mapping is data, not a hardcoded array

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
  represent. The source file path is captured for phase 4.

`Mapping::$dataTypes` (SQL type → field type) survives only as the last tier,
for columns with no form field at all.

### 3.8 The precedence engine

`Resolver\Precedence` is the heart of the feature and the cheapest thing to
test. It takes, per column:

- `?object $notes` — decoded JSON from the SQL comment;
- `?Model\FormField $form` — the matched XML field;
- `Model\Column $column` — the parsed SQL column;

and returns a `Model\FieldDefinition` in which each property is a
`Model\Value` carrying `value` and `origin` (`notes` | `xml` | `derived`).
Nothing else in the pipeline decides precedence, and the `Report` is generated
straight from the origin distribution.

`Resolver\FieldXml` then composes the JCB `field.xml` attribute string by
passing the resolved settings into
`ComponentbuilderHelper::getFieldTypeProperties()` — the same call the current
`Builder` makes, but with the full resolved attribute bag instead of six
hardcoded keys.

### 3.9 Writing

Writers use `VDM\Joomla\Data\{Item,Insert,Update}` and set `guid`. They are
also idempotent, which requires a source-to-definition identity the current
code has none of.

Proposal: a deterministic UUIDv5-style GUID from a fixed namespace plus
`component code name + table name [+ column name]`. `Data\Guid::getGuid()` is
v4 random and stays the generator for genuinely new records; extruded records
get the derived GUID so a second run over the same source updates in place
instead of producing another `(dynamic build)` set. The alternative — matching
on `name` — collides across components and is not viable.

Writers, in dependency order: `Field` → `AdminView` → `AdminFields` →
`AdminFieldsConditions` → `AdminCustomTabs` → `ComponentAdminViews`. Assets
continue through the existing helper.

### 3.10 API shape

```php
$report = Extrusion\Factory::_('Extruder')->extrude(
    new Extrusion\Model\Request(
        path: '/…/administrator/components/com_example',
        componentId: 42,
        options: ['overwrite' => false]
    )
);
```

- one public method, an explicit request object, a `Result\Report` return;
- no work in constructors;
- no `enqueueMessage()` below the orchestrator — parsers return messages in
  the report and only the outermost caller enqueues;
- resolvable from the interface layer, the console, or a future API without
  change.

The legacy `new Extrusion($data)` call in `Joomla_componentModel::save()` is
left untouched until the interface session wires the new entry point.

## 4. Phases

### Phase 0 — freeze and characterize

Fixture trees for a J3 component and a J4+ component (JCB's own compiled
output is the obvious source), plus characterization tests pinning the current
`Mapping`/`Builder` output so the new pipeline can be diffed against it rather
than compared by eye.

Touches: tests only.

### Phase 1 — folder in, inventory out

`Config`, `Factory`, `Service/Extrusion.php`, `Service/Discovery.php`,
`Discovery\{Scanner,Manifest,Inventory}`, `Discovery\Locator\{Schema,Form,Language}`,
`Layout\{Profile,JoomlaThree,JoomlaFour,JoomlaFive,JoomlaSix,Heuristic}`,
`Model\Source`, `Result\{Report,Message}`, and the matching interfaces.

Deliverable: given a path, a report naming the schema file, the per-view form
XMLs, the language files, the detected layout family, the component code name,
and which discovery tier found each. No interpretation, no writes, no database
— entirely unit testable against fixture trees.

This phase is where the containment work lives: realpath checks, symlink
refusal, depth/count caps, and the traversal, absolute-path, mixed-separator,
and symlink test cases that AGENTS.md already requires of remote file
operations.

### Phase 2 — pure readers

`Reader\Schema`, `Reader\Sql\{Splitter,CreateTable,Insert}`, `Reader\Form`,
`Reader\Language`, `Model\{Table,Column,Fieldset,FormField}`,
`Service/Reader.php`.

Deliverable: inventory → structured data, with the live-DDL temp table gone.
Gate: column-metadata parity against the temp-table implementation over
`admin/sql/install.mysql.utf8.sql`.

### Phase 3 — resolve and build the administrator area

`Resolver\{Precedence,Fieldtype,Language,ViewName,Role,Tab,Condition,FieldXml}`,
`Writer\*`, `Model\{FieldDefinition,ViewDefinition,Value}`, `Extruder`,
`Service/{Resolver,Writer}.php`.

Deliverable: the feature. Point at a component folder, get views and fields
with XML-sourced types and attributes, real English labels, fieldset-derived
tabs, `showon`-derived conditions, GUIDs, component linkage, and an idempotent
second run. Fix the `array_search()` list-flag defect here and retire it from
the defect ledger.

### Phase 4 — code extrusion

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

### Phase 5 — retire the legacy stack

Wire the interface to `Extruder`, reduce `Helper\{Mapping,Builder,Extrusion}`
to delegates, migrate `ExtrusionContractTest` ownership onto the new classes,
and remove the wrappers only when no reference remains — the same sequencing
the [helper refactoring playbook](helper-refactoring.md) prescribes.

## 5. Sequencing notes

- Phases 1 and 2 are independent of the database and of Joomla's application
  object. They can be built and tested in full isolation, which is the main
  argument for the discovery/reading/resolution split.
- Phase 3 is the only phase that writes, and every write goes through
  `Data\*`. No new `db->insertObject()` calls.
- Each new production declaration takes its `test-ownership.php` entry in the
  same change. Nothing enters `coverage-baseline.php`.
- `php bin/check-php-style.php --base=<merge-base>` and
  `php bin/check-test-ownership.php --base=<merge-base>` run from the test
  project on every phase.
- Nothing in phases 0–4 edits `admin/**` or `media/**`. The interface session
  will need those paths and therefore a GUI change record.

## 6. Open decisions

1. **Where does the folder path come from?** A text field, a server-relative
   browse, or a `#__componentbuilder_server` entry. Affects `Config` only, but
   the containment rules depend on the answer.
2. **Do we import site views and modules in the same pass?** The
   administrator area is the stated first objective; the same inventory
   already sees `site/` and would make the extension later rather than a
   rewrite.
3. **Language: import or resolve only?** Resolving to English strings is
   required. Additionally populating `#__componentbuilder_language_translation`
   from the component's other language packs is a cheap add-on with real
   value, but it is scope.
4. **Existing-component behavior.** Merge into the current definitions,
   replace them, or refuse and require an empty component. The idempotency
   design supports merge; the policy is a product decision.
5. **Collision override table for field types.** `Text`/`Tel` is the only
   collision in the seeded data today; whether that table is code or a new
   `fieldtype` column is worth deciding before phase 3.
