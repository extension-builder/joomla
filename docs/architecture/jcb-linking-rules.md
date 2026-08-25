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

A component states the relationship between its screens in its controllers,
and states it the same way whoever wrote it: the controller of a list
screen proxies `getModel()` to the model of the screen that edits one
record, while a screen that stands on its own names itself.

```php
class Admins_fieldsController extends AdminController
{
    public function getModel($name = 'Admin_fields', ...)   // the list of admin_fields
}

class CompilerController extends AdminController
{
    public function getModel($name = 'Compiler', ...)       // a screen of its own
}
```

Two things follow, and nothing else can supply either:

- **The real plural name.** JCB's own list names are irregular
  (`admin_fields` → `admins_fields`, `class_extends` → `class_extendings`,
  `component_config` → `components_config`), so a plural rule guesses
  wrong for most views. The component says the name outright.
- **Which folders are generated output.** A folder the component pairs
  with another view's model is that view's list screen and never a custom
  admin view, whatever it is called.

## What the edit screen states

A JCB-built edit screen states its own shape, and the compiler reads the
same shape back:

- The template opens one tab per section --
  `Html::_('uitab.addTab', '<view>Tab', '<key>', Text::_('<CONST>'))` --
  in the order the tabs are shown.
- Each tab renders its columns from layouts of the view's own folder,
  named `<tab>_<column>`; the column names are the compiler's own
  alignment table read in reverse (`Compiler\Architecture\AdminView\
  TabLayoutFields::$alignmentOptions`): 1 left, 2 right, 3 fullwidth,
  4 above, 5 under, 6 leftside, 7 rightside.
- Each of those layouts lists the fields of that column, in order, as the
  fallback of the value the model may override.

Two sections in that template are the compiler's own furniture rather
than tabs of the view: the publishing section, which renders the view's
layouts without a column in their name (its fields belong to tab 15), and
the permissions section, which renders the `rules` field. Storing either
as a tab gives the view two of each. A tab that renders neither -- markup
of its own instead -- is a custom tab (`admin_custom_tabs`), which is a
tab of html placed before or after a stated tab.

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

A compiled list screen says, in its own markup and forms, what JCB keeps
against every field of a view:

- `tmpl/<list>/default_head.php` gives each **listed** field a heading of
  its own through `Html::_('searchtools.sort', …, 'a.<column>', …)`, in the
  order the list shows them. What the body echoes beside them belongs to
  another field's cell and is not a column of the list.
- `tmpl/<list>/default_body.php` opens the record on one cell, and the
  field echoed there is the view's **title** field, which is also the one
  that carries the link.
- `forms/filter_<list>.xml` names the fields the screen **filters** on, and
  a filter declared `multiple="true"` is the multi-value filter.
- The list model's own query compares the fields the **search** box
  matches (`a.<column> LIKE`).

Two values must never be guessed:

- `list` = `2` means *no database column at all*
  (`Compiler/Model/Fields.php`, "2 = none database"), so a field merely
  absent from the list takes the form's empty default instead.
- A filter is built only for a field whose `list` is 1, 3 or 4
  (`Creator/Builders.php::appearsInList()`), while a column is rendered
  only for 1 or 3 (`Architecture/AdminViews/ListHead.php`). So a field the
  screen filters on but shows no column for is `4` -- which is what the
  source itself must hold for that filter to exist at all.

A get keyed on a view's table also carries what the query states beside it:
the conditions it keeps its records by, the order it returns them in, and
-- for an item get -- the filter that fetches the record asked for. JCB's
own gets carry exactly those, and a get without them returns every record,
unordered, or the first row of the table for every id.

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
linked by seventeen views rather than seventeen copies of it. Two harvested
columns stating the same field (same name, type, column behind it and xml)
are therefore one field: the first is written, and every other view links
it. Only a person's own pairing verdict overrides that.

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
