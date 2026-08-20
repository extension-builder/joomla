# Generated view sanitize replaces escape

## Change identity

- **Date first changed:** 2026-08-20
- **Author/implementer:** Claude (coding agent), for llewellyn@vdm.io
- **Task/issue/PR:** Branch `claude/escape-sanitize-correction`
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active session request. The repository owner
  reported that the escaping change merged in PR #30 was wrong, stated that the
  method "was supposed to remove any tags in the text ... it literally needs to
  convert the text into text only", instructed that the view classes must drop
  their `escape()` override and gain `sanitize()`, that `html()` in the string
  helper must become an alias of `sanitize()`, and that "everywhere where we
  used to call the escape method, we must now sanitize". They asked for this to
  be fixed and for a new pull request.
- **Authorized paths:** `admin/compiler/joomla_3/**` and
  `admin/compiler/joomla_4/**`, limited to the view templates and the two modal
  return layouts listed below.
- **Authorized outcome:** Restore the original tag-stripping objective under
  the name `sanitize()`, remove the `escape()` override from the generated view
  classes so the method falls through to Joomla's own, and move the generated
  call sites onto `sanitize()`.
- **Permission summary:** Permission covers the compiler view templates for
  this correction only. It does not extend to the generated administrator
  application, to `media/**`, or to any other `admin/**` subtree.

## Purpose and rationale

The previous change misread the intent of the helper. `escape()` in these
generated views was never an HTML encoder; it delegated to
`StringHelper::html()`, whose `InputFilter` is a whitelist over an empty tag
list and therefore removes every tag. Reducing a value to plain text was the
objective, and the previous change replaced it with `htmlspecialchars()`, which
keeps the markup visible instead of removing it.

Joomla's own `HtmlView::escape()` already provides HTML encoding, so a JCB view
does not need to define one. Removing the override lets any remaining
`escape()` call fall through to core, and frees the name to mean what it means
everywhere else in Joomla.

The in-scope part of this correction is in
`libraries/vendor_jcb/VDM.Joomla/src/Utilities/StringHelper.php`, where
`sanitize()` becomes the implementation and `html()` becomes its alias, and in
the three compiler renderers that emit the call sites. The templates carry the
view-level method those renderers call, and template text cannot be reached
from a compiler class.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-08-20-generated-view-sanitize-replaces-escape.md`

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
- **Stable location(s):** The generated view class body.
- **What changed:** The `escape()` override was removed. The `sanitize()` method
  added by the previous change was replaced by the removed `escape()` method
  itself, renamed to `sanitize()` and pointed at
  `Super___1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::sanitize()`. Each
  template therefore keeps its own `$shorten` and `$length` defaults, and the
  Joomla 3 templates keep their per-file length branching, exactly as the
  removed `escape()` had them.
- **Why:** `escape()` here was a sanitiser, and the name conflicts with
  Joomla's, which is an encoder. Removing the override lets `escape()` resolve
  to `Joomla\CMS\MVC\View\HtmlView::escape()`. Carrying the old method's
  signature into `sanitize()` keeps the shortening behaviour of every generated
  view unchanged.
- **Related paths/symbols:** `VDM\Joomla\Utilities\StringHelper::sanitize()`;
  the emitting renderers `AdminViews\ListItem\ItemCode`,
  `AdminViews\ListItem\LinkLogic` and `AdminViews\DisplayMethod`.

### `admin/compiler/joomla_4/ADMIN_VIEW_MODAL_RETURN.php` and `SITE_ADMIN_VIEW_MODAL_RETURN.php`

- **Change type:** Modified
- **Stable location(s):** The `<h1 class="display-5 fw-bold">` heading and the
  `<p class="lead mb-4">` paragraph.
- **What changed:** `$this->escape($title_column, false)` became
  `$this->sanitize($title_column, false)` in both places in each file.
- **Why:** A record title rendered into a modal should be plain text. These two
  echoes were unescaped before the previous change and are now sanitised, which
  is the objective the surrounding views use.
- **Related paths/symbols:** `$data['title']` on the same template is still left
  alone; it is consumed by `addScriptOptions`, which JSON encodes it.

## Impact

- **Behavioral impact:** A generated view no longer declares `escape()`, so that
  call resolves to Joomla's encoder. It declares `sanitize()`, which removes
  every tag and encodes what remains. Generated list bodies, the modal link
  attributes, the list ordering values and the modal return title all call
  `sanitize()`. A field flagged not to be escaped, which means its HTML is to be
  kept rather than stripped, now calls `escape()`, so its markup is preserved
  and shown rather than parsed; it was emitted raw before the previous change.
- **Visual impact:** A stored value containing markup now displays as the text
  inside that markup, which is the long-standing behaviour this restores. Under
  the previous change it briefly displayed as the literal markup.
- **Accessibility impact:** None. No structure, role, label or focus order
  changes.
- **Compatibility impact:** All generated targets. `joomla_4` serves Joomla 4, 5
  and 6; `joomla_3` serves Joomla 3. Both `HtmlView::escape()` (views) and
  `BaseLayout::escape()` (layouts) exist in core, so the two hand-written
  template call sites that still use `escape()` continue to resolve.
- **Generated-output impact:** Changed. Every regenerated view loses its
  `escape()` method and keeps `sanitize()`. Call sites in generated list bodies,
  modal link attributes, display methods and modal return layouts move from
  `escape(` to `sanitize(`.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php bin/check-php-style.php --base=<merge-base>` | PHP 8.4.19, from `libraries/vendor_jcb/tests` | Pass |
| `php bin/check-test-ownership.php --base=<merge-base>` | PHP 8.4.19 | Pass |
| `php bin/check-container-keys.php` | PHP 8.4.19 | Pass |
| `php bin/check-moved-conditions.php --base=<merge-base>` | PHP 8.4.19 | Pass |
| `composer test` | PHP 8.4.19, Joomla CMS 6.1.2, PHPUnit 12.5.33 | 3898 tests, 42069 assertions, 1 failure — `FieldCreatorTest::testFieldAsStringNormalizesBothRendererBackends`, which reproduces on a clean `6.x` tree and is a libxml self-closing-tag spacing difference in this container |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Reduce `Smith & <b>Sons</b> "Ltd" <script>alert(1)</script>` through the generated view's `sanitize()` | PHP 8.4.19 harness mirroring the generated class | Pass — `Smith &amp; Sons &quot;Ltd&quot; alert(1)`; every tag removed, remainder encoded |
| Same value through the inherited core `escape()`, as a flagged field now uses | PHP 8.4.19 harness | Pass — markup preserved and encoded, so it displays rather than parses |
| Both methods into a `data-title="…"` attribute | PHP 8.4.19 harness | Pass — quote encoded in both, so neither breaks the attribute |
| Confirm `BaseLayout` has `escape()` but no `sanitize()` | Joomla CMS 6.1.2 source | Pass — confirms `layoutmetadata.php` must keep `escape()` |

### Checks not performed

- Installing a compiled component into a live Joomla site and viewing an
  administrator list. No Joomla installation with a database is available in
  this environment, so the templates were verified by linting, by the test
  suite, and by exercising the helper and view chain directly.
- Full-tree golden comparison of generated output. No golden fixture set for
  these templates exists in the repository.

## Risks, limitations, and rollback

- **Known risks:** Any component or custom code that called the JCB view's
  `escape()` expecting tags to be stripped now gets Joomla's encoder instead and
  will see markup rendered as literal text. Such callers should move to
  `sanitize()`.
- **Known limitations:** Two hand-written template call sites still use
  `escape()` and were deliberately left: `default_toolbar.php`, where the search
  box echoes the user's own query and encoding preserves it, and
  `layoutmetadata.php`, which runs in a layout where `BaseLayout::escape()`
  exists and `sanitize()` does not, so changing it would be fatal.
- **Rollback:** Revert the sixteen modified templates together with the
  in-scope changes to `StringHelper.php`, `ItemCode.php`, `LinkLogic.php` and
  `DisplayMethod.php`. The two sets are one behaviour and must not be reverted
  separately, because the renderers emit calls to the view method these
  templates define.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_3/HtmlView_custom_admin.php` | Itself | No | Not applicable — owner confirmed | Compiler code template, not generated output. `Compiler\Utilities\Paths::setTemplatePath()` resolves the template root to `admin/compiler/joomla_<folder_key>` and reads these files at build time. Owner requested this change in this session. No next action. |
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
