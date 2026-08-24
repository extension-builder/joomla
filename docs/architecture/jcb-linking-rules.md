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

`admin_view.addpermissions` is rows of `action` plus `implementation`
(`Compiler/Model/Permissions.php` accepts a legacy parallel-array shape
too, but the form renders rows). `implementation` is `1` view-level,
`2` component-level, `3` both. A `view.*` action is rewritten to
`<name_single_code>.*`; a `core.*` action is forced to implementation `1`.
A view whose rows are all `2` gets **no Permissions tab**.

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
reported, because inventing text for it would misstate the source.
