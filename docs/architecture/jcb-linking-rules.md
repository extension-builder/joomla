# How JCB links a component, and what the compiler reads

This is the evidence base for anything that writes JCB records directly --
the extrusion engine above all. Every rule below was read out of the
compiler, the form definitions, JCB's own shipped demo data, or the
official documentation, and each names where it came from. Nothing here is
convention or preference: it is what the code does.

## Identity

Every link between JCB records is a **guid**, never a row id. The compile
run itself is keyed on the component's integer id
(`Compiler/Config.php::getComponentid()`), but from there every join is
`joomla_component.guid = <child>.joomla_component`
(`Compiler/Component/Data.php`, the `$joiners` loop), and every view and
field reference inside a subform is the target's guid
(`Compiler/Adminview/Data.php::set()` -- a valid guid selects by `guid`,
anything else is treated as an integer id).

So two records are the same record when their guids agree. A shared name
is a resemblance and nothing more.

## What belongs to a component

The component's own link tables are its declaration of what it owns:

| table | payload | holds |
|---|---|---|
| `component_admin_views` | `addadmin_views` | its admin views |
| `component_site_views` | `addsite_views` | its site views |
| `component_custom_admin_views` | `addcustom_admin_views` | its custom admin views |

Each is joined **once** (`loadObject()`), so a second row for the same
component is silently ignored. The same holds for a view's own tables:
`admin_fields`, `admin_fields_conditions`, `admin_fields_relations` and
`admin_custom_tabs` are one row per admin view guid.

Reading those tables is how anything writing into JCB knows which records
to update rather than create.

## How a component names its own screens

A component's screens are the folders it ships them in, and its
administrator menu names the ones it offers. That is all any Joomla
component is obliged to state, and it is all extrusion reads.

A view's plural is derived, then checked: a derived plural the menu never
names is reported, not settled quietly. Nothing recovers an irregular
plural a component states nowhere -- JCB's own list names are irregular
(`admin_fields` → `admins_fields`), and where the source does not say,
the run says so rather than guess silently.

A folder named after the component itself is that component's dashboard,
not a screen someone built. It owes no view and no get.

> **Not read:** a controller's default `getModel()` argument, an
> `uitab.addTab` call, a layout file named for a tab and an alignment.
> Those are things a JCB-built component happens to have. Reading them
> makes an engine that only works on components JCB already built, which
> is the opposite of the point.

## What a view's screens are made of

A view's own PHP is its author's, not a record. JCB writes a view's files
from its fields and its settings, so there is nothing in those files a
record could be recovered from without first assuming the files were
JCB's own output.

So none of it is read. What is stored instead:

- **Tabs** are the view's own form fieldsets, which is where a Joomla
  component groups the fields of a record.
- **Field placement** within a tab is JCB's default; a person arranges it
  afterwards.
- **A recovered screen's body** is written generically -- it renders
  whatever the view's get returns and says plainly when there is nothing
  yet. It is a starting point, not a reproduction.

## Ordering

- Admin views are sorted by their link row's `order`
  (`Compiler/Model/Adminviews.php`). A row whose `order` is `0` or absent
  is unordered and the comparator handles it inconsistently, so **every
  row should carry a real order**.
- Site views and custom admin views are **not** sorted: stored order is
  build order.
- Fields are sorted by `order_list` for the list view and form XML, and
  placed within a tab by `order_edit` (`Compiler/Creator/Layout.php`).
  `order_edit = 0` is remapped to a single "first" slot per tab, so only
  one field per tab can rely on it.

## Tabs

Tab numbers are 1-based positions in the admin view's own `addtabs`
subform, in stored order (`Compiler/Model/Tabs.php`). Beyond that:

- **Tab 1 is `Details`** when `addtabs` says nothing.
- **Tab 15 is the Publishing section**, set unconditionally by the
  compiler and stated by the documentation: *"Choosing tab 15 ensures JCB
  places the field in its default 'Publishing' section."*
  (`How-to-overwrite-the-custom-fields.md`). JCB's own shipped data agrees
  -- every one of the views that links the Globally Unique ID field puts
  it on tab 15.
- A field whose tab number names no tab falls back to Details.
- A custom tab (`admin_custom_tabs`) renders only if a field also targets
  its number, or if that number is 15.

## Switches that build structure

The switches on an admin view's link row are not cosmetic -- each one
generates real structure, so each must be true of the source:

| switch | what it builds |
|---|---|
| `checkin` | the check-in method and call (needs `checked_out`, `checked_out_time`) |
| `history` | version history, and the `view.version` permission (needs `version`) |
| `metadata` | metakey/metadesc/robots/author/rights fields and a Metadata tab |
| `access` | the access field -- and without it every `*.edit.access` permission row is skipped |
| `port` | import/export, and the `view.export`/`view.import` permissions |
| `mainmenu`, `submenu`, `dashboard_list`, `dashboard_add` | menu and dashboard entries, each with its own permission |

A component's own table columns and its manifest's `<administration>`
menu are the evidence for these; switching one on that the source never
had adds columns and permissions the component does not want.

## Permissions

A component states its permissions in `access.xml`, and states the level
of each by where it puts it: an action in the `component` section is set
once for the whole component (implementation 2), an action in a view's own
section is set per record (implementation 1), and an action in both is
offered at both levels (implementation 3). An action named for another
view belongs to that view; a `core.*` action of the component section
belongs to the component itself, and one in a view's own section is view
level, because that is what the compiler makes of it.

**A permission row may only name an action JCB's own form offers.**
`admin/forms/admin_view.xml` declares `addpermissions.action` as a list with
a closed set of options (the `core.*` and `view.*` edit/create/delete/access
family). An action outside it cannot be selected by hand and is not
displayed either -- the browser shows the first option in its place -- so a
stored row naming one is a row nobody can read. Everything else a
component's access rules carry is the compiler's own output, written from
the view's switches: `view.batch` on every admin view
(`Creator/Permission.php::initBatch()`), `view.version` with `history`,
`view.export`/`view.import` with `port`, and the menu and dashboard actions
with `submenu`, `dashboard_list` and `dashboard_add`
(`Creator/AccessSections.php`). Those belong to the switches, never to this
list -- and the switches can be read back from the same rules.

A field's own permission is written the same way, as
`view.<edit|access|view>.<field>` at implementation 3, so an action of that
shape whose tail is not one of the view's standing actions names a field.

`admin_view.addpermissions` is rows of `action` plus `implementation`
(`Compiler/Model/Permissions.php` accepts a legacy parallel-array shape
too, but the form renders rows). `implementation` is `1` view-level,
`2` component-level, `3` both. A `view.*` action is rewritten to
`<name_single_code>.*`; a `core.*` action is forced to implementation `1`.
A view whose rows are all `2` gets **no Permissions tab**.

## What a link row carries, and what it leaves out

The switches on a link row are checkboxes, and JCB's own records hold
**only the ones that are on** -- an unchecked switch is absent, never an
empty string. This is not cosmetic: the compiler reads `port` and
`history` as integers (`Compiler\Creator\Permission::initPort(int)`), so
an empty string in their place stops the compile outright. The values that
are not checkboxes (`icomoon`, `add_api`, `filter`, `edit_create_site_view`,
`order`, and a custom admin view's `before`) are always carried, because
the compiler reads them whether or not they are set.

## What a list screen states about its fields

Every Joomla component that offers a list screen ships a filter form for
it, and that form is a full statement of the screen's settings without a
line of anyone's PHP being read:

- `<fields name="filter">` names the fields the screen **filters** on. A
  filter declared `multiple="true"` is the multi-value filter.
- `<fields name="list">`'s `fullordering` field names, option by option
  (`<option value="a.name ASC">`), every column the screen lets a person
  **sort** by -- which is the component stating which columns it puts on
  that screen at all.

A component shipping no such form has stated nothing, and every field
falls to JCB's default. Which field names a record, and so carries the
link, is the role resolver's reading of the view's own columns.

Two values must never be guessed:

- `list` = `2` means *no database column at all*
  (`Compiler/Model/Fields.php`, "2 = none database"), so a field merely
  absent from the list takes the form's empty default instead.
- A filter is built only for a field whose `list` is 1, 3 or 4
  (`Creator/Builders.php::appearsInList()`), while a column is rendered
  only for 1 or 3 (`Architecture/AdminViews/ListHead.php`). So a field the
  screen filters on but shows no column for is `4`.

## What a column states, and what a form states

A field record keeps two defaults, and they are different things. The
form's `default` attribute is what the form shows; `datadefault` is what
the column holds. `default="NOW"` on a calendar belongs to the first and
never the second.

The schema states the second, and states more that nothing else can:

- A column's **key rank** runs none, index, unique, primary. JCB's field
  form offers unique as `1` and a plain index as `2`, so the two scales
  must be mapped, never passed straight between each other. The primary
  key is not among them -- JCB writes the id column and its primary key
  itself.
- A column carrying **no `DEFAULT` clause** is not a column defaulting to
  nothing. JCB spells that difference `datadefault = 'Other'` with
  `datadefault_other = 'EMPTY'` (`InstallSql`: *"to get just null value
  add EMPTY to other value"*).
- The **engine, character set, collation and row format** are stated after
  the closing bracket and nowhere else, so nothing about the columns
  recovers them. Left unset they fall back to MyISAM and utf8.

## What a view's main get has to be

The compiler writes a custom admin view's or a site view's files only when
its `main_get` reads **one record or a list** -- `gettype` 1 or 2
(`Compiler\Component\Structuremultiple::isValidView()`). A get of any
other shape is passed over silently and the screen never reaches the
compiled component, however complete its record is. A screen with no table
behind it therefore takes its data from custom code (`main_source` 3) and
keeps the shape of an item get, which is exactly how JCB's own screens
without a table are built.

## Powers

- A power's `main_class_code` is the class **body only** -- no `<?php`,
  no namespace, no use statements, no class declaration or closing brace.
- `namespace` is stored placeholderised
  (`[[[NamespacePrefix]]]\Vendor\Folder.ClassName`); its last dot part
  **must** equal `name` or the compile errors out.
- One power references another by guid -- through `extends`, `implements`,
  `extendsinterfaces`, `use_selection` (which emits the `use` statement)
  or `load_selection` (which ships it without importing) -- or by the
  super power key `Super___<guid-with-underscores>___Power` written in the
  code, which the compiler swaps for the class name and imports. Both
  routes recompute the namespace at compile time, which is what lets the
  same power compile into different components.

**A power's identity is its stored namespace, not the class it compiles
to.** The prefix is deferred precisely so one class serves components
whose prefixes differ, so two classes are the same power exactly when
they fold to the same stored namespace, whatever they were built as.
Resolving both sides to concrete names instead makes every library whose
prefix differs from the run's look new -- which duplicates a whole library
on first harvest.

Folding a built class back:

- A Joomla library extension is a folder of **vendor folders**. The
  extension folder is what Joomla installs; the vendor folder inside it
  (`VDM.Joomla`, `JoomVenue.Joomla`) names the namespace head in its own
  dotted name and keeps its classes under `src`.
- The **first segment of every namespace is the vendor prefix**, and it is
  ALWAYS deferred to `[[[NamespacePrefix]]]`, whatever it reads -- that is
  the convention's own statement, and deferring it is what lets one class
  serve components whose prefixes differ.
- A **component segment answers by its word, not its casing** -- PHP
  namespaces are case-insensitive, so `SermonDistributor` and
  `Sermondistributor` are one component area. The set answered against
  holds every component namespace the run can know: the component being
  extruded, the component being paired against (its code name derived the
  way `Compiler\Component\Placeholder` does, plus its
  `component_placeholders` overrides, whose values travel **base64
  encoded** exactly as `applyComponentOverrides` decodes them). A match
  becomes `[[[ComponentNamespace]]]`.
- The **casing the library actually carries is witnessed and recorded**
  onto the paired component -- the vendor prefix onto the component row
  where none stands, and a differing component-segment casing as a
  ComponentNamespace override -- so compiling the component resolves every
  class back to the very folders it was harvested from. A person's
  standing values are never overwritten, only reported when the library
  disagrees.
- The `system_name` speaks JCB's own convention: the vendor prefix, then
  the dotted tail with the class -- `VDM.Data.Action.Load` -- never the
  connecting head between them.

Powers outside the libraries folder:

- The compiler's own core map (`Compiler\Joomla\Path`) places a power whose
  namespace opens with `[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator`
  (or `\Site`, or a `\Module\` or `\Plugin\` head) in that extension's own
  `src`. Under such a head only the **dot parts are folders**; a further
  backslash segment is not a folder at all, so `...\Administrator\Engine\Team`
  would be written to `src/Team.php` while declaring the Engine namespace.
  The stored form for `administrator/components/com_x/src/Engine/Team.php`
  is therefore `...\Administrator\Engine.Team`, and for `src/Team.php` it is
  `...\Administrator\Team`.
- **The seam is read from the file's real ancestry, not from the folder the
  run was aimed at.** The trailing namespace segments that mirror the file's
  parent folder names, name for name, are the dot parts; the mirroring stops
  at the source root (`src`) in every layout the compiler writes, and what
  is left is the head. Aiming the run at `.../src/Engine`, at `.../src`, or
  at the component folder lands on the same stored form. A folder below the
  aimed folder that the namespace does not mirror is still a contradiction,
  and falls back to the two-segment convention with a report entry.
- **A person's placeholders are resolved in the compiler's order.** The
  system-wide `placeholder` table (every target, base64 decoded), then the
  core values over it in place, then the paired component's
  `component_placeholders` overrides (every target). A power a person stores
  as `[[[ComponentEngineNamespace]]].Team`, with that placeholder standing for
  `[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine`,
  resolves to the very class the compiler writes, so the catalogue answers
  for it by class name as well as by identity.
- **Identity is the canonical form.** Every placeholder the person defined
  is unfolded, the core placeholders stay standing, and both wrapper forms
  become one -- so `[[[ComponentEngineNamespace]]].Team` and the long form
  it stands for are one power. A reference written under another prefix
  folds at every seam the written name allows, not only the conventional
  two-segment one, so an import of such a power still links by identity.
- **A power recognised by identity keeps the namespace the person stored**,
  through their placeholder or not; nothing is restated. A new power, or one
  recognised only by the class it compiles to (an earlier run's misplaced
  form, say), is written with the placement the file states, **expressed
  through the longest placeholder whose value stands for a leading run of
  the head** -- the joiner after the covered run kept, a dot where a folder
  follows -- so it lands beside the powers the person already keeps there:
  `[[[ComponentEngineNamespace]]].Match`. Only a value that is itself a
  namespace fragment can stand for a head; a restated namespace is reported
  as `powers.namespace.restated.<guid>` with both forms.

## What a table has to have to be a view

Every view JCB builds keeps its records by an `id` of their own, so a table
the source's schema declares without one is not a view's table: modelling
it as an admin view would give the component a screen it never had and add
Joomla's dozen columns to a table that carries none of them. Such a table
is passed over and reported. A table only a JCB definition class describes
says nothing either way -- such a class names the view's own fields and
never Joomla's columns.

Two roles must be read rather than guessed for the same reason, because
each rewrites the table:

- The **alias** is the column JCB itself names `alias`; a view's alias
  field becomes that column when the component is built, so a column
  merely named after one (`alias_builder`) would be renamed and the table
  would lose a column of its own.
- The **access** switch is proved by an `access` column, not by the access
  rules: they name an access action for every view whether or not the view
  has an access level.

## Fields are shared, not repeated

A field is a record of its own in JCB, and every view that needs it links
it -- which is why JCB's own components have one Globally Unique ID field
linked by seventeen views rather than seventeen copies of it.

Which harvested columns are one field is settled before anything is
written (`Extrusion/Resolver/Sharing`), so the list a person approves
already shows one field and the views it serves. The rule, in the order it
runs:

- **A stated Global Unique ID outranks everything.** The same guid is the
  same field always -- it will never differ for the same field -- and a
  column stating no guid whose statement matches a guid-stating column
  belongs to that guid.
- **Otherwise the sources' own statements decide, exactly.** The code
  name, the label, the field type, the database shape, and every stated
  XML property must match -- `required="true"` and `required="false"` are
  two different fields, and a per-view description is a statement like any
  other. The match runs on what the sources *stated*, never on the padding
  a field type's examples would add.

A settled group then takes **one written identity**, chosen in rank:

- **A person's verdict on the group outranks all.** The pairing board
  renders the group on its owner's row -- one field, and the views it
  serves -- and that row's verdict travels as a `field_group` decision the
  settle step applies to every member: point the group at a chosen field,
  create one fresh shared field, or set the whole group aside. A verdict on
  a single member (the board's *detach*) still moves exactly that view and
  nothing else.
- **When a component is paired, what already stands in it is recognised**
  (`Extrusion/Resolver/Standing`): a record standing under a member's own
  derived identity, under the fresh identity a create verdict once salted,
  or under the paired view's own link whose stored properties hash to
  exactly what this run would write (`Extrusion/Resolver/Record` -- the
  same composition the writer persists, distilled to one hash) IS this
  field already written. It is reused: the owner updates it, every view
  links it, and standing links a member once carried to another copy are
  **consolidated** onto it -- turned in place by the admin fields writer,
  with the newly unlinked records named in the report, never deleted. A
  lookalike whose hash differs is somebody's own field: its link stands,
  and the resemblance is reported for a person to decide.
- **Otherwise the first view in table order owns a fresh record**, and
  every later view links its guid.

The owner's write is steered through the same decision registry a person's
verdict lands in, so the engine's own reuse defaults can never detach a
member from its group -- only a person can. Nothing looks outside the
component being extruded and the component it is paired against: a field
that stands elsewhere in the system is linked by a pairing decision on the
board, never by resemblance.

## Language

**JCB stores the English string; its compiler makes the constant.** The
language extractor takes what stands between `Text::_('` and `')`, asks
the language builder for a key, writes that key into the compiled code and
the string into the language file (`Compiler/Language/Extractor.php`,
`Compiler/Language::key()`).

The corollary matters for anything importing code: `Language::key()`
deliberately refuses a value that is already an upper-case constant, so
`Text::_('COM_X_SOMETHING')` stored in a record registers **nothing** and
is left untouched -- the component then shows a raw constant. Code
harvested out of a compiled component must therefore have its constants
turned back into their English before being stored, which is what
`Extrusion\Resolver\Constants` does, using the catalogue read from the
source's own language files. The documentation states the same rule:
*"When automated imports capture code such as `Text::_('COM_EXAMPLE_LABEL')`,
JCB automatically translates the constant back to its human-readable
text"* (`JCB-Custom-Codes.md`).

A constant the catalogue cannot answer is left exactly as it stands and
reported, because inventing text for it would misstate the source. Two
further limits come from the extractor itself, and both leave the constant
standing rather than write something it cannot read:

- It re-keys only the call forms `Compiler/Config::getLangstringkeytargets()`
  names -- `Text::_`, `Text::sprintf`, `Text::script`, `JustTEXT::_` and the
  JavaScript `Joomla.JText._`. Turning any other call into text would leave
  a string the compiler never makes a constant of again.
- It takes what stands between the call's own quotes and knows nothing of
  escaping, so text carrying a quote is written in the other quoting (it
  reads both), and text carrying both quote marks keeps its constant.

The catalogue is not always filled by the run itself: a library harvested
on its own reads no component, and a class may name a constant of another
component altogether. A constant names the component it belongs to, and an
installed component keeps its translations in this site's own language
folders, so a constant the run cannot answer is looked up there --
`administrator/language/<tag>/` and `language/<tag>/`, under the component
the constant names.
