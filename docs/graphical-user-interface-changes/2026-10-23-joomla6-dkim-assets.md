# Joomla 6 DKIM configuration assets

## Change identity

- **Date first changed:** 2026-10-23
- **Author/implementer:** Codex MVC/native agent
- **Task/issue/PR:** Joomla 6 deprecation completion, PR #60
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** The active request authorizes migrating generated Joomla 6 JavaScript, including manually gathered scripts, through Web Asset Manager and keeping templates in the Joomla 6 compiler set.
- **Authorized paths:** `admin/compiler/joomla_6/ADMIN_DKIM_FIELD.php`, `admin/compiler/joomla_6/dkim.js`, and `admin/compiler/joomla_6/settings.json`.
- **Authorized outcome:** Move the optional generated DKIM description script into extension media, activated by its native configuration field.
- **Permission summary:** Preserve Joomla 3–5 output, existing DKIM controls and callbacks, and the conventional compiler template/Power pipeline.

## Purpose and rationale

The generated email-helper configuration embeds a script inside a translated note description. A Joomla 6 note-field subclass can activate the same script through WAM while keeping the visible description and existing global callback. The generated class and JavaScript belong in the established compiler template directory; settings publish them with the extension's existing field and media folders.

## Affected paths

### Created

- `admin/compiler/joomla_6/ADMIN_DKIM_FIELD.php`
- `admin/compiler/joomla_6/dkim.js`

### Modified

- `admin/compiler/joomla_6/settings.json`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/compiler/joomla_6/ADMIN_DKIM_FIELD.php`

- **Change type:** Created
- **Stable location(s):** `DkimField::getLabel()`
- **What changed:** Extend Joomla's NoteField and register/activate the component DKIM media script before rendering its unchanged note label.
- **Why:** Joomla's configuration form provides the native field rendering boundary for WAM activation.
- **Related paths/symbols:** Joomla 6 `Creator/ConfigFieldsetsEmailHelper`, Joomla `NoteField`, existing Joomla Factory Power.

### `admin/compiler/joomla_6/dkim.js`

- **Change type:** Created
- **Stable location(s):** `vdm_dkim`, DOM initialization and select-on-click handlers.
- **What changed:** Publish the existing DNS selector/value behavior as a standalone script, preserving the global function used by existing change callbacks and manually attached keyup callbacks.
- **Why:** WAM can own the generated script without executable markup in translation strings.
- **Related paths/symbols:** `DkimField`, generated `jform_dkim_*` controls.

### `admin/compiler/joomla_6/settings.json`

- **Change type:** Modified
- **Stable location(s):** `move.static` DKIM field/script mappings.
- **What changed:** Publish the field under `admin/src/Field` and script under `media/js/admin`; Joomla 6 Settings removes both entries when the email helper is disabled.
- **Why:** Use the existing conventional packaging and media manifest flow.
- **Related paths/symbols:** `Component/JoomlaSix/Settings::get()`.

## Impact

- **Behavioral impact:** The optional Joomla 6 DKIM settings keep their hostname default, DNS preview and click-to-select behavior; the script loads once through WAM.
- **Visual impact:** None intended; the native note markup and existing form fields remain unchanged.
- **Accessibility impact:** Existing labels and change callbacks are retained.
- **Compatibility impact:** Only Joomla 6 target output changes. Earlier-target description and field XML remain unchanged.
- **Generated-output impact:** Two conventional files are added only for components using the email helper; Joomla 6 language descriptions contain no script.

## Verification

### Automated checks

| Command/check | Environment | Result |
| --- | --- | --- |
| `php vendor/bin/phpunit -c phpunit.xml.dist --exclude-group known-defect` with NativeDkimConfigurationTest, SettingsTest, TemplatesTest and ConfigFieldsetLeafTest | PHP 8.3.6 / Joomla 6.1.2 | Pass — 25 tests, 1,167 assertions; native field resolves through Joomla Form, Power injection, WAM deduplication/subdirectory URI, optional packaging and earlier-target XML/language parity |
| `node --test libraries/vendor_jcb/tests/javascript/dkim.test.cjs` | Node 24.19.0 | Pass — 5 tests covering DOM-ready timing, existing values, global callbacks, output selection and missing controls |
| Independent merged-base XML/language comparison | PHP 8.3.6 | Pass — the complete earlier-target XML/language hash remains `5cbc334347c36e0d97729eb829470c31870148d3664c726e93b08d632cdb1b71` |
| New PHP declaration style checks and `git diff --check` | Local source checks | Pass |

### Manual scenarios

| Scenario | Environment | Result |
| --- | --- | --- |
| Installed generated component DKIM form | Maintainer environment | Not performed locally; no installed feature-enabled component is available |

### GUI test coverage

- **Spec files added/updated:** None in the installed JCB GUI suite: this changes compiler templates for generated extensions, not the installed JCB administrator application. Native field/WAM runtime and JavaScript behavior tests cover the generated boundary.

### Checks not performed

- Full installed feature-enabled component/browser scenario; local runtime is the compiler unit harness.

## Risks, limitations, and rollback

- **Known risks:** Custom DKIM fieldsets remain authoritative and must provide their own behavior.
- **Known limitations:** The global `vdm_dkim` callback remains part of the existing form contract.
- **Rollback:** Revert the Joomla 6 creator/provider selection, field/script templates, settings entries and their tests together.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source identity/path | Transfer required | Status | Evidence, owner, and next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_6/ADMIN_DKIM_FIELD.php` | Joomla 6 compiler template source | No | Not applicable — owner confirmed | User explicitly identified `admin/compiler` as authoritative; source created there, no external transfer required |
| `admin/compiler/joomla_6/dkim.js` | Joomla 6 compiler template source | No | Not applicable — owner confirmed | User explicitly identified `admin/compiler` as authoritative; source created there, no external transfer required |
| `admin/compiler/joomla_6/settings.json` | Joomla 6 compiler template packaging settings | No | Not applicable — owner confirmed | User explicitly identified the Joomla 6 `settings.json` as authoritative; mappings updated there |

## Final consistency check

- [x] Protected path lists match the final diff.
- [x] Automated results and skipped checks are recorded.
- [x] Conventional template paths, explicit permission and source ownership are identified.
