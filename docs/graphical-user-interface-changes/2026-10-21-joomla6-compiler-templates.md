# Joomla 6 compiler template set

## Change identity and permission

- Date: 2026-10-21
- Implementer: Codex
- PR: https://github.com/extension-builder/joomla/pull/59
- Status: Ready for review
- Explicit permission: the maintainer requested `admin/compiler/joomla_6` with its own `settings.json` in the current task, replacing the library template overlays.
- Authorized subtree: `admin/compiler/joomla_6/**`.

## Purpose

Use the existing compiler template directory and settings mechanism for Joomla 6. The complete Joomla 4 set supplies the initial files; eighteen files retain the already reviewed Joomla 6 deprecation changes. Config selects folder key 6. Existing Paths, Settings and structure builders perform loading and copying. Joomla Power tokens remain compiler input and go through the existing injector.

## Affected paths

Created (no existing administrator files modified or deleted):

- `admin/compiler/joomla_6/1.0.0.sql`
- `admin/compiler/joomla_6/ADMIN_AJAX_CONTROLLER.php`
- `admin/compiler/joomla_6/ADMIN_AJAX_MODEL.php`
- `admin/compiler/joomla_6/ADMIN_AUTOLOADER_CLASS.php`
- `admin/compiler/joomla_6/ADMIN_DISPLAY_CONTROLLER.php`
- `admin/compiler/joomla_6/ADMIN_EXTENSION_COMPONENT.php`
- `admin/compiler/joomla_6/ADMIN_HEADERCHECK.php`
- `admin/compiler/joomla_6/ADMIN_HELPER_CLASS.php`
- `admin/compiler/joomla_6/ADMIN_HELPER_CONTROLLER.php`
- `admin/compiler/joomla_6/ADMIN_HELPER_EMAIL.php`
- `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER.php`
- `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER_CUSTOM.php`
- `admin/compiler/joomla_6/ADMIN_IMPORT_HTML.php`
- `admin/compiler/joomla_6/ADMIN_IMPORT_HTML_CUSTOM.php`
- `admin/compiler/joomla_6/ADMIN_IMPORT_MODEL.php`
- `admin/compiler/joomla_6/ADMIN_IMPORT_MODEL_CUSTOM.php`
- `admin/compiler/joomla_6/ADMIN_LAYOUT.php`
- `admin/compiler/joomla_6/ADMIN_PROVIDER.php`
- `admin/compiler/joomla_6/ADMIN_VIEW.php`
- `admin/compiler/joomla_6/ADMIN_VIEWS.php`
- `admin/compiler/joomla_6/ADMIN_VIEWS_CONTROLLER.php`
- `admin/compiler/joomla_6/ADMIN_VIEWS_EMPTYSTATE.php`
- `admin/compiler/joomla_6/ADMIN_VIEWS_HTML.php`
- `admin/compiler/joomla_6/ADMIN_VIEWS_MODAL.php`
- `admin/compiler/joomla_6/ADMIN_VIEWS_MODEL.php`
- `admin/compiler/joomla_6/ADMIN_VIEW_CONTROLLER.php`
- `admin/compiler/joomla_6/ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_6/ADMIN_VIEW_MODAL.php`
- `admin/compiler/joomla_6/ADMIN_VIEW_MODAL_RETURN.php`
- `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/ADMIN_VIEW_TABLE.php`
- `admin/compiler/joomla_6/API_DYNAMIC_VIEWS_CONTROLLER.php`
- `admin/compiler/joomla_6/API_DYNAMIC_VIEWS_JSON.php`
- `admin/compiler/joomla_6/API_DYNAMIC_VIEW_CONTROLLER.php`
- `admin/compiler/joomla_6/API_DYNAMIC_VIEW_JSON.php`
- `admin/compiler/joomla_6/API_VIEWS_CONTROLLER.php`
- `admin/compiler/joomla_6/API_VIEWS_JSON.php`
- `admin/compiler/joomla_6/API_VIEW_CONTROLLER.php`
- `admin/compiler/joomla_6/API_VIEW_JSON.php`
- `admin/compiler/joomla_6/API_VIEW_SERIALIZER.php`
- `admin/compiler/joomla_6/CHANGELOG.md`
- `admin/compiler/joomla_6/CHANGELOG.xml`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_LAYOUT.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_CONTROLLER.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_MODEL.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_CONTROLLER.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/DASH_CONTROLLER.php`
- `admin/compiler/joomla_6/DASH_MODEL.php`
- `admin/compiler/joomla_6/DASH_VIEW.php`
- `admin/compiler/joomla_6/DASH_VIEW_HTML.php`
- `admin/compiler/joomla_6/FORM_CHECKBOXES_FIELD.php`
- `admin/compiler/joomla_6/FORM_CUSTOM_FIELD.php`
- `admin/compiler/joomla_6/FORM_LIST_FIELD.php`
- `admin/compiler/joomla_6/FORM_MODAL_SELECT_FIELD.php`
- `admin/compiler/joomla_6/FORM_RADIO_FIELD.php`
- `admin/compiler/joomla_6/FORM_RULE.php`
- `admin/compiler/joomla_6/FORM_USER_FIELD.php`
- `admin/compiler/joomla_6/INSTALLER_AUTOLOADER_CLASS.php`
- `admin/compiler/joomla_6/INSTALLER_SCRIPT.php`
- `admin/compiler/joomla_6/LAYOUT_OVERRIDE.php`
- `admin/compiler/joomla_6/LICENSE.txt`
- `admin/compiler/joomla_6/README.md`
- `admin/compiler/joomla_6/README.txt`
- `admin/compiler/joomla_6/SITE_ADMIN_VIEW.php`
- `admin/compiler/joomla_6/SITE_ADMIN_VIEW_CONTROLLER.php`
- `admin/compiler/joomla_6/SITE_ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODAL.php`
- `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODAL_RETURN.php`
- `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php`
- `admin/compiler/joomla_6/SITE_AJAX_CONTROLLER.php`
- `admin/compiler/joomla_6/SITE_AJAX_MODEL.php`
- `admin/compiler/joomla_6/SITE_AUTOLOADER_CLASS.php`
- `admin/compiler/joomla_6/SITE_DISPATCHER.php`
- `admin/compiler/joomla_6/SITE_DISPLAY_CONTROLLER.php`
- `admin/compiler/joomla_6/SITE_FORM_CHECKBOXES_FIELD.php`
- `admin/compiler/joomla_6/SITE_FORM_CUSTOM_FIELD.php`
- `admin/compiler/joomla_6/SITE_FORM_LIST_FIELD.php`
- `admin/compiler/joomla_6/SITE_FORM_MODAL_SELECT_FIELD.php`
- `admin/compiler/joomla_6/SITE_FORM_RADIO_FIELD.php`
- `admin/compiler/joomla_6/SITE_FORM_RULE.php`
- `admin/compiler/joomla_6/SITE_FORM_USER_FIELD.php`
- `admin/compiler/joomla_6/SITE_HEADERCHECK.php`
- `admin/compiler/joomla_6/SITE_HELPER_CLASS.php`
- `admin/compiler/joomla_6/SITE_HELPER_CONTROLLER.php`
- `admin/compiler/joomla_6/SITE_LAYOUT.php`
- `admin/compiler/joomla_6/SITE_ROUTER.php`
- `admin/compiler/joomla_6/SITE_ROUTE_HELPER.php`
- `admin/compiler/joomla_6/SITE_VIEW.php`
- `admin/compiler/joomla_6/SITE_VIEWS.php`
- `admin/compiler/joomla_6/SITE_VIEWS_HTML.php`
- `admin/compiler/joomla_6/SITE_VIEWS_MODEL.php`
- `admin/compiler/joomla_6/SITE_VIEW_CONTROLLER.php`
- `admin/compiler/joomla_6/SITE_VIEW_HTML.php`
- `admin/compiler/joomla_6/SITE_VIEW_MODEL.php`
- `admin/compiler/joomla_6/access.xml`
- `admin/compiler/joomla_6/admin.css`
- `admin/compiler/joomla_6/admin.js`
- `admin/compiler/joomla_6/ajax.gif`
- `admin/compiler/joomla_6/component.xml`
- `admin/compiler/joomla_6/config.xml`
- `admin/compiler/joomla_6/dashboard.css`
- `admin/compiler/joomla_6/default.xml`
- `admin/compiler/joomla_6/default_batch_body.php`
- `admin/compiler/joomla_6/default_batch_footer.php`
- `admin/compiler/joomla_6/default_body.php`
- `admin/compiler/joomla_6/default_custom_admin_template.php`
- `admin/compiler/joomla_6/default_foot.php`
- `admin/compiler/joomla_6/default_head.php`
- `admin/compiler/joomla_6/default_import.php`
- `admin/compiler/joomla_6/default_import_custom.php`
- `admin/compiler/joomla_6/default_list.xml`
- `admin/compiler/joomla_6/default_main.php`
- `admin/compiler/joomla_6/default_site_template.php`
- `admin/compiler/joomla_6/default_toolbar.php`
- `admin/compiler/joomla_6/default_vdm.php`
- `admin/compiler/joomla_6/edit.xml`
- `admin/compiler/joomla_6/en-GB.com_admin.ini`
- `admin/compiler/joomla_6/en-GB.com_admin.sys.ini`
- `admin/compiler/joomla_6/en-GB.com_site.ini`
- `admin/compiler/joomla_6/en-GB.com_site.sys.ini`
- `admin/compiler/joomla_6/filter_forms.xml`
- `admin/compiler/joomla_6/index.html`
- `admin/compiler/joomla_6/install.mysql.utf8.sql`
- `admin/compiler/joomla_6/layout.php`
- `admin/compiler/joomla_6/layoutfull.php`
- `admin/compiler/joomla_6/layoutitems.php`
- `admin/compiler/joomla_6/layoutlinkedview.php`
- `admin/compiler/joomla_6/layoutmetadata.php`
- `admin/compiler/joomla_6/layoutpublished.php`
- `admin/compiler/joomla_6/layouttitle.php`
- `admin/compiler/joomla_6/module_forms.js`
- `admin/compiler/joomla_6/module_forms.xml`
- `admin/compiler/joomla_6/settings.json`
- `admin/compiler/joomla_6/site.css`
- `admin/compiler/joomla_6/site.js`
- `admin/compiler/joomla_6/submitbutton.js`
- `admin/compiler/joomla_6/submitbutton_site.js`
- `admin/compiler/joomla_6/uninstall.mysql.utf8.sql`
- `admin/compiler/joomla_6/update_server.xml`
- `admin/compiler/joomla_6/view.css`
- `admin/compiler/joomla_6/viewFile.js`
- `admin/compiler/joomla_6/viewFileCustomAdmin.js`
- `admin/compiler/joomla_6/viewFileSite.js`
- `admin/compiler/joomla_6/view_custom_admin.css`
- `admin/compiler/joomla_6/view_site.css`
- `admin/compiler/joomla_6/view_site_edit.css`
- `admin/compiler/joomla_6/views.css`
- `admin/compiler/joomla_6/views_custom_admin.css`
- `admin/compiler/joomla_6/views_site.css`
- `admin/compiler/joomla_6/whmcs.php`

## Implementation details

### `admin/compiler/joomla_6/1.0.0.sql`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/1.0.0.sql`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_AJAX_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_AJAX_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_AJAX_MODEL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_AJAX_MODEL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_AUTOLOADER_CLASS.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_AUTOLOADER_CLASS.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_DISPLAY_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_DISPLAY_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_EXTENSION_COMPONENT.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_EXTENSION_COMPONENT.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_HEADERCHECK.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_HEADERCHECK.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_HELPER_CLASS.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_HELPER_CLASS.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_HELPER_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_HELPER_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_HELPER_EMAIL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_HELPER_EMAIL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_IMPORT_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER_CUSTOM.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_IMPORT_CONTROLLER_CUSTOM.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_IMPORT_HTML.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_IMPORT_HTML.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_IMPORT_HTML_CUSTOM.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_IMPORT_HTML_CUSTOM.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_IMPORT_MODEL.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_IMPORT_MODEL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_IMPORT_MODEL_CUSTOM.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_IMPORT_MODEL_CUSTOM.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_LAYOUT.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_LAYOUT.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_PROVIDER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_PROVIDER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEW.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEW.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEWS.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEWS.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEWS_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEWS_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEWS_EMPTYSTATE.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEWS_EMPTYSTATE.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEWS_HTML.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEWS_HTML.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEWS_MODAL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEWS_MODAL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEWS_MODEL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied from the Joomla 4 template set with whitespace-only indentation/trailing-space corrections required for new files. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEWS_MODEL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEW_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEW_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEW_HTML.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEW_HTML.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEW_MODAL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEW_MODAL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEW_MODAL_RETURN.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEW_MODAL_RETURN.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ADMIN_VIEW_TABLE.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ADMIN_VIEW_TABLE.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/API_DYNAMIC_VIEWS_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/API_DYNAMIC_VIEWS_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/API_DYNAMIC_VIEWS_JSON.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/API_DYNAMIC_VIEWS_JSON.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/API_DYNAMIC_VIEW_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/API_DYNAMIC_VIEW_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/API_DYNAMIC_VIEW_JSON.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/API_DYNAMIC_VIEW_JSON.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/API_VIEWS_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/API_VIEWS_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/API_VIEWS_JSON.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/API_VIEWS_JSON.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/API_VIEW_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/API_VIEW_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/API_VIEW_JSON.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/API_VIEW_JSON.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/API_VIEW_SERIALIZER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/API_VIEW_SERIALIZER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/CHANGELOG.md`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/CHANGELOG.md`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/CHANGELOG.xml`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/CHANGELOG.xml`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_LAYOUT.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/CUSTOM_ADMIN_LAYOUT.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEW.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEWS.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEWS_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEWS_HTML.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_MODEL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied from the Joomla 4 template set with whitespace-only indentation/trailing-space corrections required for new files. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEWS_MODEL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEW_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEW_HTML.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_MODEL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied from the Joomla 4 template set with whitespace-only indentation/trailing-space corrections required for new files. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEW_MODEL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/DASH_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/DASH_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/DASH_MODEL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied from the Joomla 4 template set with whitespace-only indentation/trailing-space corrections required for new files. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/DASH_MODEL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/DASH_VIEW.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/DASH_VIEW.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/DASH_VIEW_HTML.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/DASH_VIEW_HTML.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/FORM_CHECKBOXES_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/FORM_CHECKBOXES_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/FORM_CUSTOM_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/FORM_CUSTOM_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/FORM_LIST_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/FORM_LIST_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/FORM_MODAL_SELECT_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/FORM_MODAL_SELECT_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/FORM_RADIO_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/FORM_RADIO_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/FORM_RULE.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/FORM_RULE.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/FORM_USER_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/FORM_USER_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/INSTALLER_AUTOLOADER_CLASS.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/INSTALLER_AUTOLOADER_CLASS.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/INSTALLER_SCRIPT.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/INSTALLER_SCRIPT.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/LAYOUT_OVERRIDE.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/LAYOUT_OVERRIDE.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/LICENSE.txt`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/LICENSE.txt`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/README.md`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/README.md`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/README.txt`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/README.txt`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_ADMIN_VIEW.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_ADMIN_VIEW.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_ADMIN_VIEW_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_ADMIN_VIEW_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_ADMIN_VIEW_HTML.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_ADMIN_VIEW_HTML.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODAL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODAL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODAL_RETURN.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODAL_RETURN.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODEL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_AJAX_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_AJAX_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_AJAX_MODEL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_AJAX_MODEL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_AUTOLOADER_CLASS.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_AUTOLOADER_CLASS.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_DISPATCHER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_DISPATCHER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_DISPLAY_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied from the Joomla 4 template set with whitespace-only indentation/trailing-space corrections required for new files. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_DISPLAY_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_FORM_CHECKBOXES_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_FORM_CHECKBOXES_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_FORM_CUSTOM_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_FORM_CUSTOM_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_FORM_LIST_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_FORM_LIST_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_FORM_MODAL_SELECT_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_FORM_MODAL_SELECT_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_FORM_RADIO_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_FORM_RADIO_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_FORM_RULE.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_FORM_RULE.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_FORM_USER_FIELD.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_FORM_USER_FIELD.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_HEADERCHECK.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_HEADERCHECK.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_HELPER_CLASS.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_HELPER_CLASS.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_HELPER_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Retains the reviewed Joomla 6 deprecation replacements. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_HELPER_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_LAYOUT.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_LAYOUT.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_ROUTER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_ROUTER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_ROUTE_HELPER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_ROUTE_HELPER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_VIEW.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_VIEW.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_VIEWS.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_VIEWS.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_VIEWS_HTML.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_VIEWS_HTML.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_VIEWS_MODEL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied from the Joomla 4 template set with whitespace-only indentation/trailing-space corrections required for new files. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_VIEWS_MODEL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_VIEW_CONTROLLER.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_VIEW_CONTROLLER.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_VIEW_HTML.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_VIEW_HTML.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/SITE_VIEW_MODEL.php`

- Change: created; location: complete compiler input file.
- What/why: Copied from the Joomla 4 template set with whitespace-only indentation/trailing-space corrections required for new files. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/SITE_VIEW_MODEL.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/access.xml`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/access.xml`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/admin.css`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/admin.css`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/admin.js`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/admin.js`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/ajax.gif`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/ajax.gif`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/component.xml`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/component.xml`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/config.xml`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/config.xml`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/dashboard.css`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/dashboard.css`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default.xml`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default.xml`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_batch_body.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_batch_body.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_batch_footer.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_batch_footer.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_body.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_body.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_custom_admin_template.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_custom_admin_template.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_foot.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_foot.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_head.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_head.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_import.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_import.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_import_custom.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_import_custom.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_list.xml`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_list.xml`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_main.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_main.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_site_template.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_site_template.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_toolbar.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_toolbar.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/default_vdm.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/default_vdm.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/edit.xml`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/edit.xml`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/en-GB.com_admin.ini`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/en-GB.com_admin.ini`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/en-GB.com_admin.sys.ini`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/en-GB.com_admin.sys.ini`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/en-GB.com_site.ini`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/en-GB.com_site.ini`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/en-GB.com_site.sys.ini`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/en-GB.com_site.sys.ini`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/filter_forms.xml`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/filter_forms.xml`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/index.html`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/index.html`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/install.mysql.utf8.sql`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/install.mysql.utf8.sql`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/layout.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/layout.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/layoutfull.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/layoutfull.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/layoutitems.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/layoutitems.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/layoutlinkedview.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/layoutlinkedview.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/layoutmetadata.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/layoutmetadata.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/layoutpublished.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/layoutpublished.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/layouttitle.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/layouttitle.php`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/module_forms.js`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/module_forms.js`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/module_forms.xml`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/module_forms.xml`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/settings.json`

- Change: created; location: complete compiler input file.
- What/why: The complete Joomla 6 structure, static and dynamic template mappings are declared here. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/settings.json`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/site.css`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/site.css`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/site.js`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/site.js`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/submitbutton.js`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/submitbutton.js`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/submitbutton_site.js`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/submitbutton_site.js`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/uninstall.mysql.utf8.sql`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/uninstall.mysql.utf8.sql`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/update_server.xml`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/update_server.xml`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/view.css`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/view.css`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/viewFile.js`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/viewFile.js`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/viewFileCustomAdmin.js`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/viewFileCustomAdmin.js`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/viewFileSite.js`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/viewFileSite.js`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/view_custom_admin.css`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/view_custom_admin.css`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/view_site.css`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/view_site.css`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/view_site_edit.css`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/view_site_edit.css`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/views.css`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/views.css`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/views_custom_admin.css`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/views_custom_admin.css`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/views_site.css`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/views_site.css`; consumer: existing compiler Paths and Settings services.

### `admin/compiler/joomla_6/whmcs.php`

- Change: created; location: complete compiler input file.
- What/why: Copied unchanged from the Joomla 4 template set. Required for an independent Joomla 6 template set.
- Related source: `admin/compiler/joomla_4/whmcs.php`; consumer: existing compiler Paths and Settings services.

## Impact and verification

- Generated output: intended to match PR #59 before this correction; template source location changes and seven newly copied files receive whitespace-only corrections for the repository whitespace gate.
- Joomla 3–5: existing template trees and folder keys remain unchanged.
- Custom compiler roots: Joomla 6 uses `<compiler_path>/joomla_6/settings.json` or the existing component-specific settings filename in that directory.
- Visual/accessibility impact: none; these are compiler source templates, not the installed administrator views.
- GUI spec changes: none, because no installed interface behavior changes. Full compilation tests exercise the source templates and generated installation.
- Focused verification: 133 tests / 1,684 assertions pass for settings, templates, structure, installer and target selection; generated runtime checks use the Joomla Power injector. Hosted compilation results are recorded in the PR audit.
- JCB self-compilation: not performed; requires the maintainer's complete definition package.
- Rollback: revert the template-convention correction commit as a unit, including folder-key selection and resolver removal.

## Authoritative source reconciliation

The maintainer explicitly identifies `admin/compiler` as the authoritative compiler template location. These newly added inputs are maintained there; no generated administrator output is edited.

| Repository path | Authoritative source | Transfer required | Status | Evidence |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_6/1.0.0.sql` | `admin/compiler/joomla_6/1.0.0.sql` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_AJAX_CONTROLLER.php` | `admin/compiler/joomla_6/ADMIN_AJAX_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_AJAX_MODEL.php` | `admin/compiler/joomla_6/ADMIN_AJAX_MODEL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_AUTOLOADER_CLASS.php` | `admin/compiler/joomla_6/ADMIN_AUTOLOADER_CLASS.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_DISPLAY_CONTROLLER.php` | `admin/compiler/joomla_6/ADMIN_DISPLAY_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_EXTENSION_COMPONENT.php` | `admin/compiler/joomla_6/ADMIN_EXTENSION_COMPONENT.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_HEADERCHECK.php` | `admin/compiler/joomla_6/ADMIN_HEADERCHECK.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_HELPER_CLASS.php` | `admin/compiler/joomla_6/ADMIN_HELPER_CLASS.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_HELPER_CONTROLLER.php` | `admin/compiler/joomla_6/ADMIN_HELPER_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_HELPER_EMAIL.php` | `admin/compiler/joomla_6/ADMIN_HELPER_EMAIL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER.php` | `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER_CUSTOM.php` | `admin/compiler/joomla_6/ADMIN_IMPORT_CONTROLLER_CUSTOM.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_IMPORT_HTML.php` | `admin/compiler/joomla_6/ADMIN_IMPORT_HTML.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_IMPORT_HTML_CUSTOM.php` | `admin/compiler/joomla_6/ADMIN_IMPORT_HTML_CUSTOM.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_IMPORT_MODEL.php` | `admin/compiler/joomla_6/ADMIN_IMPORT_MODEL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_IMPORT_MODEL_CUSTOM.php` | `admin/compiler/joomla_6/ADMIN_IMPORT_MODEL_CUSTOM.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_LAYOUT.php` | `admin/compiler/joomla_6/ADMIN_LAYOUT.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_PROVIDER.php` | `admin/compiler/joomla_6/ADMIN_PROVIDER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEW.php` | `admin/compiler/joomla_6/ADMIN_VIEW.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEWS.php` | `admin/compiler/joomla_6/ADMIN_VIEWS.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEWS_CONTROLLER.php` | `admin/compiler/joomla_6/ADMIN_VIEWS_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEWS_EMPTYSTATE.php` | `admin/compiler/joomla_6/ADMIN_VIEWS_EMPTYSTATE.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEWS_HTML.php` | `admin/compiler/joomla_6/ADMIN_VIEWS_HTML.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEWS_MODAL.php` | `admin/compiler/joomla_6/ADMIN_VIEWS_MODAL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEWS_MODEL.php` | `admin/compiler/joomla_6/ADMIN_VIEWS_MODEL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEW_CONTROLLER.php` | `admin/compiler/joomla_6/ADMIN_VIEW_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEW_HTML.php` | `admin/compiler/joomla_6/ADMIN_VIEW_HTML.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEW_MODAL.php` | `admin/compiler/joomla_6/ADMIN_VIEW_MODAL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEW_MODAL_RETURN.php` | `admin/compiler/joomla_6/ADMIN_VIEW_MODAL_RETURN.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php` | `admin/compiler/joomla_6/ADMIN_VIEW_MODEL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ADMIN_VIEW_TABLE.php` | `admin/compiler/joomla_6/ADMIN_VIEW_TABLE.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/API_DYNAMIC_VIEWS_CONTROLLER.php` | `admin/compiler/joomla_6/API_DYNAMIC_VIEWS_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/API_DYNAMIC_VIEWS_JSON.php` | `admin/compiler/joomla_6/API_DYNAMIC_VIEWS_JSON.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/API_DYNAMIC_VIEW_CONTROLLER.php` | `admin/compiler/joomla_6/API_DYNAMIC_VIEW_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/API_DYNAMIC_VIEW_JSON.php` | `admin/compiler/joomla_6/API_DYNAMIC_VIEW_JSON.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/API_VIEWS_CONTROLLER.php` | `admin/compiler/joomla_6/API_VIEWS_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/API_VIEWS_JSON.php` | `admin/compiler/joomla_6/API_VIEWS_JSON.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/API_VIEW_CONTROLLER.php` | `admin/compiler/joomla_6/API_VIEW_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/API_VIEW_JSON.php` | `admin/compiler/joomla_6/API_VIEW_JSON.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/API_VIEW_SERIALIZER.php` | `admin/compiler/joomla_6/API_VIEW_SERIALIZER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/CHANGELOG.md` | `admin/compiler/joomla_6/CHANGELOG.md` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/CHANGELOG.xml` | `admin/compiler/joomla_6/CHANGELOG.xml` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_LAYOUT.php` | `admin/compiler/joomla_6/CUSTOM_ADMIN_LAYOUT.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW.php` | `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS.php` | `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_CONTROLLER.php` | `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php` | `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_MODEL.php` | `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_MODEL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_CONTROLLER.php` | `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php` | `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_MODEL.php` | `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_MODEL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/DASH_CONTROLLER.php` | `admin/compiler/joomla_6/DASH_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/DASH_MODEL.php` | `admin/compiler/joomla_6/DASH_MODEL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/DASH_VIEW.php` | `admin/compiler/joomla_6/DASH_VIEW.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/DASH_VIEW_HTML.php` | `admin/compiler/joomla_6/DASH_VIEW_HTML.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/FORM_CHECKBOXES_FIELD.php` | `admin/compiler/joomla_6/FORM_CHECKBOXES_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/FORM_CUSTOM_FIELD.php` | `admin/compiler/joomla_6/FORM_CUSTOM_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/FORM_LIST_FIELD.php` | `admin/compiler/joomla_6/FORM_LIST_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/FORM_MODAL_SELECT_FIELD.php` | `admin/compiler/joomla_6/FORM_MODAL_SELECT_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/FORM_RADIO_FIELD.php` | `admin/compiler/joomla_6/FORM_RADIO_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/FORM_RULE.php` | `admin/compiler/joomla_6/FORM_RULE.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/FORM_USER_FIELD.php` | `admin/compiler/joomla_6/FORM_USER_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/INSTALLER_AUTOLOADER_CLASS.php` | `admin/compiler/joomla_6/INSTALLER_AUTOLOADER_CLASS.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/INSTALLER_SCRIPT.php` | `admin/compiler/joomla_6/INSTALLER_SCRIPT.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/LAYOUT_OVERRIDE.php` | `admin/compiler/joomla_6/LAYOUT_OVERRIDE.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/LICENSE.txt` | `admin/compiler/joomla_6/LICENSE.txt` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/README.md` | `admin/compiler/joomla_6/README.md` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/README.txt` | `admin/compiler/joomla_6/README.txt` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_ADMIN_VIEW.php` | `admin/compiler/joomla_6/SITE_ADMIN_VIEW.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_ADMIN_VIEW_CONTROLLER.php` | `admin/compiler/joomla_6/SITE_ADMIN_VIEW_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_ADMIN_VIEW_HTML.php` | `admin/compiler/joomla_6/SITE_ADMIN_VIEW_HTML.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODAL.php` | `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODAL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODAL_RETURN.php` | `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODAL_RETURN.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php` | `admin/compiler/joomla_6/SITE_ADMIN_VIEW_MODEL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_AJAX_CONTROLLER.php` | `admin/compiler/joomla_6/SITE_AJAX_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_AJAX_MODEL.php` | `admin/compiler/joomla_6/SITE_AJAX_MODEL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_AUTOLOADER_CLASS.php` | `admin/compiler/joomla_6/SITE_AUTOLOADER_CLASS.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_DISPATCHER.php` | `admin/compiler/joomla_6/SITE_DISPATCHER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_DISPLAY_CONTROLLER.php` | `admin/compiler/joomla_6/SITE_DISPLAY_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_FORM_CHECKBOXES_FIELD.php` | `admin/compiler/joomla_6/SITE_FORM_CHECKBOXES_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_FORM_CUSTOM_FIELD.php` | `admin/compiler/joomla_6/SITE_FORM_CUSTOM_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_FORM_LIST_FIELD.php` | `admin/compiler/joomla_6/SITE_FORM_LIST_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_FORM_MODAL_SELECT_FIELD.php` | `admin/compiler/joomla_6/SITE_FORM_MODAL_SELECT_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_FORM_RADIO_FIELD.php` | `admin/compiler/joomla_6/SITE_FORM_RADIO_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_FORM_RULE.php` | `admin/compiler/joomla_6/SITE_FORM_RULE.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_FORM_USER_FIELD.php` | `admin/compiler/joomla_6/SITE_FORM_USER_FIELD.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_HEADERCHECK.php` | `admin/compiler/joomla_6/SITE_HEADERCHECK.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_HELPER_CLASS.php` | `admin/compiler/joomla_6/SITE_HELPER_CLASS.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_HELPER_CONTROLLER.php` | `admin/compiler/joomla_6/SITE_HELPER_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_LAYOUT.php` | `admin/compiler/joomla_6/SITE_LAYOUT.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_ROUTER.php` | `admin/compiler/joomla_6/SITE_ROUTER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_ROUTE_HELPER.php` | `admin/compiler/joomla_6/SITE_ROUTE_HELPER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_VIEW.php` | `admin/compiler/joomla_6/SITE_VIEW.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_VIEWS.php` | `admin/compiler/joomla_6/SITE_VIEWS.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_VIEWS_HTML.php` | `admin/compiler/joomla_6/SITE_VIEWS_HTML.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_VIEWS_MODEL.php` | `admin/compiler/joomla_6/SITE_VIEWS_MODEL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_VIEW_CONTROLLER.php` | `admin/compiler/joomla_6/SITE_VIEW_CONTROLLER.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_VIEW_HTML.php` | `admin/compiler/joomla_6/SITE_VIEW_HTML.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/SITE_VIEW_MODEL.php` | `admin/compiler/joomla_6/SITE_VIEW_MODEL.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/access.xml` | `admin/compiler/joomla_6/access.xml` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/admin.css` | `admin/compiler/joomla_6/admin.css` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/admin.js` | `admin/compiler/joomla_6/admin.js` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/ajax.gif` | `admin/compiler/joomla_6/ajax.gif` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/component.xml` | `admin/compiler/joomla_6/component.xml` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/config.xml` | `admin/compiler/joomla_6/config.xml` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/dashboard.css` | `admin/compiler/joomla_6/dashboard.css` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default.xml` | `admin/compiler/joomla_6/default.xml` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_batch_body.php` | `admin/compiler/joomla_6/default_batch_body.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_batch_footer.php` | `admin/compiler/joomla_6/default_batch_footer.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_body.php` | `admin/compiler/joomla_6/default_body.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_custom_admin_template.php` | `admin/compiler/joomla_6/default_custom_admin_template.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_foot.php` | `admin/compiler/joomla_6/default_foot.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_head.php` | `admin/compiler/joomla_6/default_head.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_import.php` | `admin/compiler/joomla_6/default_import.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_import_custom.php` | `admin/compiler/joomla_6/default_import_custom.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_list.xml` | `admin/compiler/joomla_6/default_list.xml` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_main.php` | `admin/compiler/joomla_6/default_main.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_site_template.php` | `admin/compiler/joomla_6/default_site_template.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_toolbar.php` | `admin/compiler/joomla_6/default_toolbar.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/default_vdm.php` | `admin/compiler/joomla_6/default_vdm.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/edit.xml` | `admin/compiler/joomla_6/edit.xml` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/en-GB.com_admin.ini` | `admin/compiler/joomla_6/en-GB.com_admin.ini` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/en-GB.com_admin.sys.ini` | `admin/compiler/joomla_6/en-GB.com_admin.sys.ini` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/en-GB.com_site.ini` | `admin/compiler/joomla_6/en-GB.com_site.ini` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/en-GB.com_site.sys.ini` | `admin/compiler/joomla_6/en-GB.com_site.sys.ini` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/filter_forms.xml` | `admin/compiler/joomla_6/filter_forms.xml` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/index.html` | `admin/compiler/joomla_6/index.html` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/install.mysql.utf8.sql` | `admin/compiler/joomla_6/install.mysql.utf8.sql` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/layout.php` | `admin/compiler/joomla_6/layout.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/layoutfull.php` | `admin/compiler/joomla_6/layoutfull.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/layoutitems.php` | `admin/compiler/joomla_6/layoutitems.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/layoutlinkedview.php` | `admin/compiler/joomla_6/layoutlinkedview.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/layoutmetadata.php` | `admin/compiler/joomla_6/layoutmetadata.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/layoutpublished.php` | `admin/compiler/joomla_6/layoutpublished.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/layouttitle.php` | `admin/compiler/joomla_6/layouttitle.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/module_forms.js` | `admin/compiler/joomla_6/module_forms.js` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/module_forms.xml` | `admin/compiler/joomla_6/module_forms.xml` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/settings.json` | `admin/compiler/joomla_6/settings.json` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/site.css` | `admin/compiler/joomla_6/site.css` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/site.js` | `admin/compiler/joomla_6/site.js` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/submitbutton.js` | `admin/compiler/joomla_6/submitbutton.js` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/submitbutton_site.js` | `admin/compiler/joomla_6/submitbutton_site.js` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/uninstall.mysql.utf8.sql` | `admin/compiler/joomla_6/uninstall.mysql.utf8.sql` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/update_server.xml` | `admin/compiler/joomla_6/update_server.xml` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/view.css` | `admin/compiler/joomla_6/view.css` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/viewFile.js` | `admin/compiler/joomla_6/viewFile.js` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/viewFileCustomAdmin.js` | `admin/compiler/joomla_6/viewFileCustomAdmin.js` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/viewFileSite.js` | `admin/compiler/joomla_6/viewFileSite.js` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/view_custom_admin.css` | `admin/compiler/joomla_6/view_custom_admin.css` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/view_site.css` | `admin/compiler/joomla_6/view_site.css` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/view_site_edit.css` | `admin/compiler/joomla_6/view_site_edit.css` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/views.css` | `admin/compiler/joomla_6/views.css` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/views_custom_admin.css` | `admin/compiler/joomla_6/views_custom_admin.css` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/views_site.css` | `admin/compiler/joomla_6/views_site.css` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
| `admin/compiler/joomla_6/whmcs.php` | `admin/compiler/joomla_6/whmcs.php` | No | Not applicable — owner confirmed | Maintainer current task explicitly requires templates here. |
