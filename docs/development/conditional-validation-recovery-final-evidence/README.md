# Recovery branch: final native conditional compiler comparison

## Result and scope

The final recovery candidate was installed natively and compiled for Joomla
targets 3, 4, 5 and 6 from the same immutable definition snapshot used for the
original recovery baseline. The original baseline archives were verified against
every hash in their previously recorded complete file manifests before reuse.
No main-branch output was used as a recovery baseline.

| Target | Files per package | Byte-identical paths | Modified paths | Added / removed paths | Candidate PHP / JS syntax checks |
| --- | ---: | ---: | ---: | ---: | ---: |
| Joomla 3 | 1,366 | 1,357 | 8 | 1 / 1 | 976 / 24 |
| Joomla 4 | 288 | 279 | 8 | 1 / 1 | 123 / 21 |
| Joomla 5 | 293 | 284 | 8 | 1 / 1 | 128 / 21 |
| Joomla 6 | 249 | 240 | 8 | 1 / 1 | 128 / 4 |

All **1,355 PHP files and 70 JavaScript files** in the final candidate packages
passed their syntax checks. Each added/removed pair is the requested generic
shared-rule rename; the package file counts remain equal. All other changes are
the expected conditional models, listeners, browser state and README statistics.
No output normalization or ignore rule was used.

Every full resolved-Power evidence document is byte-identical to its original
baseline counterpart: **65 final Powers, including one late-discovered Power**.
`original-baseline-verification.json` records the original archive digests and
the number of matching file hashes for each reused baseline.

## Measured source and input

- Recovery baseline commit: `2dab8eb3249185d18b11a55694adb559c349addc`.
- Final candidate local commit: `286675eeff046b925f2b94ee13bbe29f937edda1`.
- Corresponding remote candidate: `23f01da023e5f5dc79f3ff3af20eb6be9473c8e2`.
- Common candidate Git tree: `573e69ecfe14fd7cb76ad0e8842ddad7443f570d`.
- Private definition snapshot SHA-256:
  `e4954f24abdb1382991a3f03d90912f8877f088389acffb2cb94e1ccdf3476bc`.
- Disposable host: Joomla 6.2.0, PHP 8.3.6, MariaDB 10.11.14.

The candidate archive came from the complete committed Git tree, including
paths omitted by the sparse checkout. Joomla's native `extension:install`
command installed it. **5,407 deployed-source files** then matched their source
hashes exactly, covering libraries, administrator source/forms/templates/compiler
templates, API source and JavaScript. The deployment summary records the archive
and complete checksum-manifest digests.

The disposable host had just completed the separate main comparison. Before
the recovery runs, **219 main-only deployed files** were verified against the
main source archives and moved aside. This included the independent Joomla 6
template directory, whose presence could otherwise change recovery's intended
template fallback. No recovery source file was removed. The quarantine summary
records that the stale Joomla 6 template directory is absent and identifies
the complete path manifest by SHA-256.

The fixture remains the public HelloWorld component from `joomengine/packages`
with the synthetic authored-condition overlay in `fixture-overlay.php` and
`conditional-fixture.json`. It exercises real definition records, related
selectors, historical Is Not OR semantics, ordered shared targets and a final
positive Hide Only applicability condition. This is not a JCB self-compilation.

## Reviewed generated changes

Relative to the original recovery baseline, the four final packages contain:

- Readable model condition properties, native-parent validation wrappers and
  named context resolvers in administrator and site-edit models.
- A generic `ConditionalrequiredRule` runtime, including the native form's
  optional/disabled/unset target cap and `FormField::multiple` selector-shape
  checks before a condition can waive a requirement.
- Ordered browser refresh functions with captured display and rendered-required
  state. Originally optional or disabled controls cannot regain required, ARIA
  or required-class flags. Required wrappers remain supported.
- Aggregate listeners, removal of generated browser exemption transport and
  the required shared-rule filename rename.
- The two README files' derived source statistics.

No unrelated path or byte changed. Joomla 6 retains recovery's original shared
template architecture and asset locations; main's later native template work is
not included in this branch. `comparison-summary.json` lists all changed paths.

## Reproduction and limits

The public overlay driver requires the prepared golden stack's `bootstrap.php`
beside it and an `evidence` directory one level above it. The repository
bootstrap is `.github/golden-master/bootstrap.php`; the local copy changes
only the disposable site/marker paths. Run the overlay only against the
disposable fixture database. Its fixed GUIDs update the same synthetic
records when reapplied.

The scratch orchestration command was:

```bash
JCB_DISPOSABLE_TEST=1 python3 with-database.py python3 recovery-complete-compare.py
```

The driver first validates all original baseline archive entries against their
recorded manifests. It then installs and checksums the final candidate, isolates
verified main-only files, restores the immutable snapshot before each target and
runs the full compiler with
`--debug-line-nr=0 --add-build-date=2 --build-date=2026-01-01`.
It verifies the output target major, compares every filename and byte sequence,
and parses all candidate PHP and JavaScript files.

All measured work runs under the offline guard in `offline-policy.json`: remote
reads and network/process functions are disabled, and all approved repository
lists are empty. No dependency download or online fallback was performed.

The same pre-existing Joomla 3 `mainAccordionName` undefined-key warning occurs
on both baseline and final candidate. Other targets have no compiler diagnostics;
no candidate-only warning was introduced. PHP syntax checking uses the PHP 8.3
host and does not replace installed runtime tests on every Joomla version.

These results update the earlier recovery generated-output evidence for the
final generic names, live-form caps and malformed-selector checks. They do not
claim maintenance-source transfer or a complete JCB self-build. GUI, API and unit
tests remain separate evidence with their own source identities.

No SQL snapshot, site configuration, credential, raw installation log, runtime
binary or generated binary archive is included here. The complete unnormalized
diffs remain in scratch; their exact sizes and hashes are in
`full-diff-digests.json`.
