# Reuse the Joomla 6 view document Web Asset Manager

## Change identity

- **Date first changed:** 2026-11-20
- **Author/implementer:** Jeff
- **Task/issue/PR:** `fix/joomla6-generated-asset-document` against `main`
- **Change record status:** Draft

## Explicit permission

- **Permission reference:** Active maintainer request to implement the suggested targeted compiler fix in `extension-builder/joomla` on a branch from `main`. The approved proposal identifies the Joomla 6 site and custom-admin single/list template scopes.
- **Authorized paths:** The four Joomla 6 HTML compiler templates listed below. No installed JCB views, media, or other targets.
- **Authorized outcome:** Bind the native view document manager before generated Library code uses it, without changing the asset contract.

## Purpose and rationale

The Joomla 6 include renderer went back to the global application for every asset. Using the existing `$wa` requires adjusting template timing: in these four templates the Library placeholder precedes the previous initialization. The regular admin list already initializes `$wa` before it uses the generated view JavaScript.

## Affected paths

### Created

- This change record.

### Modified

- `admin/compiler/joomla_6/SITE_VIEW_HTML.php`
- `admin/compiler/joomla_6/SITE_VIEWS_HTML.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php`

### Moved or renamed

- None.

### Deleted

- None.

## Implementation details

### `admin/compiler/joomla_6/SITE_VIEW_HTML.php`

- **Change type:** Modified
- **Stable location:** `_prepareDocument()`, immediately before the Library loader placeholder.
- **What changed:** Moved the existing `$wa` assignment ahead of its first generated consumer.
- **Why:** The early Library fragments must use the same native manager as the existing model asset loop.
- **Related symbol:** `Compiler\Library\JoomlaSix\IncludeHelper::get()`.

### `admin/compiler/joomla_6/SITE_VIEWS_HTML.php`

- **Change type:** Modified
- **Stable location:** `_prepareDocument()`, immediately before the Library loader placeholder.
- **What changed:** Moved the existing `$wa` assignment ahead of its first generated consumer.
- **Why:** The early Library fragments must use the same native manager as the existing model asset loop.
- **Related symbol:** `Compiler\Library\JoomlaSix\IncludeHelper::get()`.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php`

- **Change type:** Modified
- **Stable location:** `_prepareDocument()`, immediately before the Library loader placeholder.
- **What changed:** Moved the existing `$wa` assignment ahead of its first generated consumer.
- **Why:** The early Library fragments must use the same native manager as the existing model asset loop.
- **Related symbol:** `Compiler\Library\JoomlaSix\IncludeHelper::get()`.

### `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php`

- **Change type:** Modified
- **Stable location:** `_prepareDocument()`, immediately before the Library loader placeholder.
- **What changed:** Moved the existing `$wa` assignment ahead of its first generated consumer.
- **Why:** The early Library fragments must use the same native manager as the existing model asset loop.
- **Related symbol:** `Compiler\Library\JoomlaSix\IncludeHelper::get()`.

## Impact

- **Behavioral impact:** Generated HTML views use the actual view document for those assets; module libraries use the document in the module scope.
- **Visual and accessibility impact:** No intentional visual, layout, or accessibility changes. With the standard Joomla document, the same assets should appear in the same order.
- **Compatibility impact:** Joomla 6 compilation target only; no changes to authored hooks, other asset emitters, or Joomla 3–5.
- **Generated-output impact:** One binding supplies the asset include fragments in each scope. The asset registration keys, flags, URLs, dependencies, and activation are retained.

## Verification

- Static inspection of the four template edits confirms the existing placeholder sequence is unchanged and the binding precedes it.
- `AssetsTemplateTest` checks template scope; `JoomlaSixAssetLoadersTest` exercises distinct manager instances over a shared registry, registration and activation order, and both module entry paths.
- **GUI spec:** None added. These files are compiler inputs, not installed JCB screens. The installed-JCB browser suite does not cover the generated component views; the compiler regression tests target those outputs.
- PHP tests, full golden comparison, and generated extension install/browser smoke were not run before PR creation. The draft should not be merged without checking CI and reviewing the Joomla 6 generated changes.

## Risks, limitations, and rollback

- Verify a real generated view with combined Library and view assets and authored document code before release.
- **Rollback:** Revert the compiler patch and this record together; regenerate affected Joomla 6 extensions if already built from it.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_6/SITE_VIEW_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | [Existing source ownership record](2026-10-23-joomla6-assets-native.md) identifies this same file as authoritative. |
| `admin/compiler/joomla_6/SITE_VIEWS_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | [Existing source ownership record](2026-10-23-joomla6-assets-native.md) identifies this same file as authoritative. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | [Existing source ownership record](2026-10-23-joomla6-assets-native.md) identifies this same file as authoritative. |
| `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php` | Same maintained compiler template | No | Not applicable — owner confirmed | [Existing source ownership record](2026-10-23-joomla6-assets-native.md) identifies this same file as authoritative. |
