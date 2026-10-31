# Restore localized SFTP key-loading errors

## Change identity and permission

- Date first changed: 2026-10-31.
- Implementer: Codex, PR #64 follow-up.
- Task/PR: https://github.com/extension-builder/joomla/pull/64.
- Status: Ready for review.
- Permission: the active request requires fixing every failing workflow, including
  failures already on main. The SFTP failure requires correcting its error
  diagnostic and providing the corresponding installed administrator translations.
- Protected path authorized for that outcome:
  `admin/language/en-GB/en-GB.com_componentbuilder.ini`.

## Purpose and rationale

The SFTP adapter selected the same raw English file-loading diagnostic for
both key files and pasted key text. It failed the existing key-text diagnostic
contract and could not resolve either message through Joomla's language catalog.
Correcting only the library's translation keys would display untranslated
identifiers, so the two English catalog entries are part of the fix.

## Affected protected paths

- Created: none.
- Modified: `admin/language/en-GB/en-GB.com_componentbuilder.ini`.
- Moved or renamed: none.
- Deleted: none.

### `admin/language/en-GB/en-GB.com_componentbuilder.ini`

- Change type: modified.
- Stable locations: `COM_COMPONENTBUILDER_LOADING_THE_PRIVATE_KEY_FILE_FAILED`
  and `COM_COMPONENTBUILDER_LOADING_THE_PRIVATE_KEY_TEXT_FAILED`.
- Change: add the two English messages, distinguishing a key file from pasted
  key text.
- Consumer: `VDM\Joomla\Componentbuilder\Server\Sftp::getClient()` in
  `libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Server/Sftp.php`.
- The consumer resolves literal translation keys before reporting errors, so
  the compiler's language extractor can recognize both messages.

## Impact

Key authentication modes 2/3 report a file error; modes 4/5 report a text error.
Missing, empty and malformed keys still return failure before opening a remote
connection. The change does not alter server forms, layout, focus behavior,
authentication credentials or SSH dependencies. The visible difference is the
correct localized diagnostic. Both Joomla host versions use the existing
`Text::_()` API; no target-version selection is added. Generated model output
is unaffected.

## Verification

`SftpTest::testMissingFileAndInvalidKeyDoNotConnect()` retains its original
assertion. `testInvalidKeysHaveLocalizedDiagnostics()` covers missing, empty
and malformed keys across all four key authentication modes; it loads the real
English catalog and asserts one exact translated message, `false`, and no SFTP
client creation.

| Command/check | Environment | Result |
| --- | --- | --- |
| `php -d memory_limit=1G vendor/bin/phpunit --filter SftpTest --exclude-group known-defect --no-coverage` from the test project | PHP 8.3.6, PHPUnit 12.5.33, Joomla 6.1.2 source, locked Composer dependencies | Pass: 26 tests, 211 assertions |
| `php -l` for the SFTP implementation and owning test | PHP 8.3.6 | Pass: both files parse |
| `PhpStyleChecker::inspectFile()` for the four changed production lines and complete owning test | PHP 8.3.6 | Pass: no diagnostics |
| `git diff --check` for this change's four paths | Local checkout | Pass |

- GUI spec files added or changed: none. No administrator view or input
  interaction changes. The observable message is checked at the real library
  application boundary with Joomla's language loader. An installed SFTP action
  would require external connection settings and is inappropriate for the
  read-only browser suite.
- Manual installed-site SFTP scenario: not performed; no external credentials
  or remote uploads are needed to verify key rejection.

## Risks, limitations and rollback

The two new messages currently have English translations only. No credentials
or key contents are included in either message. Rollback reverts the SFTP
consumer, its tests, both language entries and this record together.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source | Transfer required | Status | Evidence and next action |
| --- | --- | --- | --- | --- |
| `admin/language/en-GB/en-GB.com_componentbuilder.ini` | JCB's maintained SFTP Power and administrator language definitions | Yes | Pending source identification | Locate the maintained `Server\Sftp` definition, transfer the two literal translation calls and English messages, then self-compile JCB and verify both diagnostic tests. The authoritative definition was not modified by this repository patch. |

The protected-path inventory above is the complete protected diff for this
change. No third-party library is modified.
