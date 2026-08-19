# Generated view output escaping

## Change identity

- **Date first changed:** 2026-08-19
- **Author/implementer:** Claude (coding agent), for llewellyn@vdm.io
- **Task/issue/PR:** Branch `claude/joomla-directory-vulnerabilities-9qnl3o`
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active session request. The repository owner was
  asked whether to grant `admin/**` permission for the compiler templates and
  answered "Yes — templates + change record", after stating the aim of the
  session is "to improve JCB" and to "improve JCB's overall footprint in
  deliverable secure systems".
- **Authorized paths:** `admin/compiler/joomla_3/**` and
  `admin/compiler/joomla_4/**`, limited to the view templates listed below.
- **Authorized outcome:** Correct the output-escaping defects identified in the
  generated views, and give every generated view the second escaping primitive
  the compiler now emits.
- **Permission summary:** Permission covers the compiler code templates for the
  identified escaping vulnerabilities only. It does not extend to the generated
  administrator application, to `media/**`, or to any other `admin/**` subtree.

## Purpose and rationale

Two defects live in the templates themselves and cannot be reached from
`libraries/vendor_jcb/**`:

1. `ADMIN_VIEW_MODAL_RETURN.php` and `SITE_ADMIN_VIEW_MODAL_RETURN.php` echo
   the record title twice with no escaping at all. The value is written into
   the template text, so no compiler class can intercept it.
2. The compiler change that accompanies this record makes a field which opts
   out of escaping render through a `sanitize()` view method instead of being
   emitted raw. That method has to exist on the generated view class, and the
   view class body is template text.

The accompanying in-scope change is in
`libraries/vendor_jcb/VDM.Joomla/src/Utilities/StringHelper.php`, which now
separates escaping from sanitising, and in
`Compiler/Architecture/AdminViews/ListItem/ItemCode.php` and
`Compiler/Field/Attributes.php`, which decide which primitive is emitted. The
templates supply the view-level entry point those decisions call.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-08-19-generated-view-output-escaping.md`

### Modified

- `admin/compiler/joomla_3/HtmlView_custom_admin.php`
- `admin/compiler/joomla_3/HtmlView_edit.php`
- `admin/compiler/joomla_3/HtmlView_edit_site.php`
- `admin/compiler/joomla_3/HtmlView_list.php`
- `admin/compiler/joomla_3/HtmlView_list_custom_admin.php`
- `admin/compiler/joomla_3/HtmlView_list_site.php`
- `admin/compiler/joomla_3/HtmlView_site.php`
- `admin/compiler/joomla_4/ADMIN_VIEWS_HTML.php`
- `admin/compiler/joomla_4/ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_4/ADMIN_VIEW_MODAL_RETURN.php`
- `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEWS_HTML.php`
- `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_4/SITE_ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODAL_RETURN.php`
- `admin/compiler/joomla_4/SITE_VIEWS_HTML.php`
- `admin/compiler/joomla_4/SITE_VIEW_HTML.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### The fourteen view templates

Applies to `admin/compiler/joomla_3/HtmlView_custom_admin.php`,
`HtmlView_edit.php`, `HtmlView_edit_site.php`, `HtmlView_list.php`,
`HtmlView_list_custom_admin.php`, `HtmlView_list_site.php`,
`HtmlView_site.php`, and `admin/compiler/joomla_4/ADMIN_VIEWS_HTML.php`,
`ADMIN_VIEW_HTML.php`, `CUSTOM_ADMIN_VIEWS_HTML.php`,
`CUSTOM_ADMIN_VIEW_HTML.php`, `SITE_ADMIN_VIEW_HTML.php`,
`SITE_VIEWS_HTML.php`, `SITE_VIEW_HTML.php`.

- **Change type:** Modified
- **Stable location(s):** The generated view class body, immediately after the
  existing `escape()` method.
- **What changed:** Added a `sanitize($var)` method that returns a non-string
  argument unchanged and otherwise delegates to
  `Super___1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::sanitize()`.
- **Why:** `escape()` encodes, so it cannot render a field that is meant to
  carry markup. Before this change the compiler's only alternative was to emit
  such a field raw, which let any content author place a script into a list
  view. The view now offers both primitives and the compiler picks one.
- **Related paths/symbols:** `VDM\Joomla\Utilities\StringHelper::sanitize()`
  (the Super Power the placeholder resolves to);
  `VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ListItem\ItemCode::getSanitizedItemCode()`
  (the caller the compiler now emits).

### `admin/compiler/joomla_4/ADMIN_VIEW_MODAL_RETURN.php`

- **Change type:** Modified
- **Stable location(s):** The `<h1 class="display-5 fw-bold">` heading and the
  `<p class="lead mb-4">` paragraph in the template body.
- **What changed:** `<?php echo $title_column; ?>` became
  `<?php echo $this->escape($title_column, false); ?>` in both places.
- **Why:** `$title_column` is the record title read straight from the item. It
  reached the rendered modal with no escaping, so a stored title carrying
  markup executed in the administrator. `false` is passed so the title is not
  shortened, matching the previous output.
- **Related paths/symbols:** `$data['title']` on the same template is left
  alone; it is consumed by `addScriptOptions`, which JSON encodes it, and
  escaping it there would double-encode the modal title.

### `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODAL_RETURN.php`

- **Change type:** Modified
- **Stable location(s):** Identical to the administrator variant above.
- **What changed:** Identical to the administrator variant above.
- **Why:** Identical to the administrator variant above. The two templates are
  byte-identical apart from the namespace in their `@var` docblock.
- **Related paths/symbols:** As above.

## Impact

- **Behavioral impact:** Generated views gain a `sanitize()` method. A record
  title rendered by the modal-return layout is now escaped, so a title
  containing markup is displayed as text instead of being parsed. A field whose
  XML opts out of escaping is sanitised rather than emitted raw, so authored
  markup still renders while `<script>`, `<iframe>`, `on*` handlers and
  `javascript:` URLs are removed from it.
- **Visual impact:** None for any value that does not contain markup. A record
  title that contains markup previously rendered as markup and now renders as
  the literal characters, which is the intended display.
- **Accessibility impact:** None. No structure, role, label or focus order
  changes; only the text content of two existing elements is now encoded.
- **Compatibility impact:** Applies to every generated target. The templates in
  `joomla_4` serve Joomla 4, 5 and 6, and the `joomla_3` templates serve
  Joomla 3, so all four targets receive `sanitize()`. The method needs the
  StringHelper Super Power, which every generated view already depends on for
  `escape()`, so no new dependency is introduced.
- **Generated-output impact:** Changed, and intentionally so. Every regenerated
  view carries one additional method. The two modal-return layouts carry two
  changed echo statements. List bodies for fields that opt out of escaping
  change from `$item->field` to `$this->sanitize($item->field)`.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php bin/check-php-style.php --base=<merge-base>` | PHP 8.4.19, from `libraries/vendor_jcb/tests` | Pass — style valid, 0 errors |
| `php bin/check-test-ownership.php --base=<merge-base>` | PHP 8.4.19 | Pass — 1322 production files, 0 baseline entries |
| `php bin/check-container-keys.php` | PHP 8.4.19 | Pass — 730 keys, bar the 5 recorded |
| `php bin/check-moved-conditions.php --base=<merge-base>` | PHP 8.4.19 | Pass — no legacy compiler helper changed |
| `composer test` | PHP 8.4.19, Joomla CMS 6.1.2, PHPUnit 12.5.33 | Pass for this change — the only failures are `FieldCreatorTest::testFieldAsStringNormalizesBothRendererBackends` and `PHPConfigurationCheckerTest::testRunReportsSatisfiedRequirementsWithoutHelpNotice`, both of which reproduce identically on a clean `6.x` tree and are environment-dependent (libxml self-closing-tag spacing, and the runner's PHP ini values) |
| `php -l` on every modified template | PHP 8.4.19 | Pass — no syntax errors |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Render a stored title of `" autofocus onfocus="alert(1)` through the escaping helper the modal-return layout now calls | PHP 8.4.19 harness against `StringHelper` | Pass — emitted as `&quot; autofocus onfocus=&quot;alert(1)`, inert inside the attribute |
| Sanitise `<script>alert(1)</script><b>bold</b>` and `<a href="javascript:alert(1)">click</a>` | PHP 8.4.19 harness against `StringHelper::sanitize()` | Pass — yields `alert(1)<b>bold</b>` and `<a>click</a>`; `<b>` survives, the script and the scheme do not |

### Checks not performed

- Installing a compiled component into a live Joomla site and opening the
  modal-return layout in a browser. No Joomla installation with a database is
  available in this environment, so the templates were verified by linting them
  and by exercising the helper methods they call, not by rendering a built
  extension.
- Full-tree golden comparison of generated output for each target. No golden
  fixture set for these templates exists in the repository to compare against.

## Risks, limitations, and rollback

- **Known risks:** A component that relied on a record title rendering as
  markup in the modal-return layout will now see that markup as text. A field
  that opts out of escaping in order to emit a `<script>` or an inline event
  handler will lose it; that is the intent of the change.
- **Known limitations:** `sanitize()` is added to every view template, but the
  compiler currently emits it only for admin and linked-view list bodies, which
  is where the escaping opt-out is read. Other generated output that echoes a
  value raw is untouched by this record.
- **Rollback:** Revert the sixteen modified templates listed above. The
  accompanying in-scope compiler change must be reverted with them, because
  `ItemCode::getSanitizedItemCode()` emits a call to the `sanitize()` method
  these templates define; reverting the templates alone would generate a call
  to a method that no longer exists.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_3/HtmlView_custom_admin.php` | Itself | No | Not applicable — owner confirmed | Compiler code template, not generated output. `Compiler\Utilities\Paths::setTemplatePath()` resolves the template root to `admin/compiler/joomla_<folder_key>` and reads these files at build time, so this file is the authoritative input. Owner granted the change in this session. No next action. |
| `admin/compiler/joomla_3/HtmlView_edit.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_3/HtmlView_edit_site.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_3/HtmlView_list.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_3/HtmlView_list_custom_admin.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_3/HtmlView_list_site.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_3/HtmlView_site.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/ADMIN_VIEWS_HTML.php` | Itself | No | Not applicable — owner confirmed | As above; `joomla_4` serves the Joomla 4, 5 and 6 targets. |
| `admin/compiler/joomla_4/ADMIN_VIEW_HTML.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/ADMIN_VIEW_MODAL_RETURN.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEWS_HTML.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/CUSTOM_ADMIN_VIEW_HTML.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/SITE_ADMIN_VIEW_HTML.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODAL_RETURN.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/SITE_VIEWS_HTML.php` | Itself | No | Not applicable — owner confirmed | As above. |
| `admin/compiler/joomla_4/SITE_VIEW_HTML.php` | Itself | No | Not applicable — owner confirmed | As above. |

## Final consistency check

- [x] The affected-path lists match the final diff exactly.
- [x] Every path has a stable location and exact what/why details.
- [x] Behavioral and visual impact are explicit.
- [x] Verification records actual results and identifies skipped checks.
- [x] Every path has an authoritative-source mapping and reconciliation status.
- [x] `Transfer required` is `No` only for an owner-confirmed `Not applicable`
  path; it is `Yes` for every other status.
- [x] The implementation remains within the cited permission.
