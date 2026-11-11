# Main branch: native conditional compiler comparison

## Result and scope

This is a fresh comparison of current main against the conditional-validation
port. Both baseline and candidate were installed natively into the disposable
Joomla host and compiled the same restored definition snapshot for Joomla
targets 3, 4, 5 and 6. No recovery output was used as a main baseline.

| Target | Files per package | Byte-identical paths | Modified paths | Added / removed paths | Candidate PHP / JS syntax checks |
| --- | ---: | ---: | ---: | ---: | ---: |
| Joomla 3 | 1,366 | 1,357 | 8 | 1 / 1 | 976 / 24 |
| Joomla 4 | 288 | 279 | 8 | 1 / 1 | 123 / 21 |
| Joomla 5 | 293 | 284 | 8 | 1 / 1 | 128 / 21 |
| Joomla 6 | 249 | 240 | 8 | 1 / 1 | 128 / 4 |

All **1,355 PHP files and 70 JavaScript files** in the candidate packages passed
their syntax checks. The added/removed pair in each target is the requested
rename of the shared generated rule from `JcbconditionalrequiredRule` to
`ConditionalrequiredRule` (the corresponding lowercase Joomla 3 paths are in
`comparison-summary.json`). The file counts remain equal.

Every complete resolved-Power evidence document was byte-identical within its
baseline/candidate pair: **65 final Powers, including one late-discovered
Power**. No timestamps, paths, whitespace, README statistics or other output
were normalized or ignored in the comparisons.

## Measured source and input

- Main baseline commit: `86b1900a87ccd979233572e47f1f2513fc1493f1`.
- Candidate local commit: `e88903dcbb2c63f4446fb47d6f62e0d37ccf5e24`.
- Corresponding remote candidate: `539a9562635fac4173d140971d0c8c883acfdbc5`.
- Candidate Git tree: `8848fb460a603776c5137edc1889748ed89e9c5b`.
- Private definition snapshot SHA-256:
  `e4954f24abdb1382991a3f03d90912f8877f088389acffb2cb94e1ccdf3476bc`.
- Disposable host: Joomla 6.2.0, PHP 8.3.6, MariaDB 10.11.14.

Both packages were produced from complete `git archive` snapshots. Sparse
worktrees, ignored test runtimes and uncommitted files were not packaged.
Joomla's native `extension:install` command installed each archive. Each
installation then passed **5,645 deployed-source checksum comparisons**, covering
the bundled libraries, administrator source/forms/templates/compiler templates,
API source and JavaScript. There were zero mismatches. The deployment summaries
record package and checksum-manifest digests.

The input is the public HelloWorld component from `joomengine/packages`, with
the explicitly synthetic authored-condition overlay in `fixture-overlay.php`
and `conditional-fixture.json`. It is not JCB's maintenance definition.

The fixture authors required storage-kind, length-mode, database-type and
database-length fields through normal JCB definition records. Its existing DSL
conditions exercise chained selectors, historical multiple-option Is Not OR
semantics, shared targets, source ordering and a final positive Hide Only
applicability rule. The same immutable database snapshot was restored before
each of the eight measured builds.

## Reviewed differences and preserved main behavior

The eight modified paths per target contain only:

- Administrator and site-edit Greeting models: readable conditional properties,
  the native-parent validation wrapper and the named data-context resolver.
- Administrator and site-edit Greeting templates: aggregate ordered-condition
  listeners replacing per-condition callbacks.
- Administrator and site-edit Greeting JavaScript: captured display and
  rendered-required baselines, ordered replay, required-state caps and removal
  of the retired browser exemption transport.
- The two README files: compiler-derived line counts and estimated time
  statistics resulting from the changed generated source.

The renamed shared rule contains the reusable native validation lifecycle,
condition evaluation, live-form target cap and native selector-shape checks.

Main's Joomla 6 output retains its own template family, the body-positioned
`jQuery(function () { ... })` listener wrapper and its relocated JavaScript
paths, `media/js/admin/greeting.js` and `media/js/site/greeting.js`. Those paths
were already present in the main baseline. No recovery template or old asset
location was substituted. The complete path lists and package digests are in
`comparison-summary.json`.

## Reproduction and limits

The public overlay driver requires the prepared golden stack's `bootstrap.php`
beside it and an `evidence` directory one level above it. The repository
bootstrap is `.github/golden-master/bootstrap.php`; the local copy changes
only the disposable site/marker paths. Run the overlay only against the
disposable fixture database. Its fixed GUIDs update the same synthetic
records when reapplied.

The measurement used the existing disposable local harness, its native compiler
driver and the frozen snapshot. The offline guard disables remote reads and
network/process functions; all three approved repository path lists are empty.
The exact settings are recorded in `offline-policy.json`.

The scratch orchestration command was:

```bash
JCB_DISPOSABLE_TEST=1 python3 with-database.py python3 main-compare.py
```

The driver installs the exact source archives, verifies their deployed hashes,
restores the snapshot independently before each target, and invokes the full
compiler with `--debug-line-nr=0 --add-build-date=2 --build-date=2026-01-01`.
It verifies the output manifest's target major, compares every archive entry and
byte sequence, and parses all candidate PHP and JavaScript files. The environment
and orchestration scripts remain in scratch; the retained metadata states their
portable reproduction contract and immutable source/input identities.

The Joomla 3 compiler emits the same pre-existing `mainAccordionName` undefined
key warning on both sides. All other targets have no compiler diagnostics, and
there are no candidate-only warnings. The exact sanitized diagnostics are
recorded separately.

These results demonstrate complete conditional-fixture generation. They do not
claim full JCB self-compilation, authoritative maintenance-source transfer or
execution of the generated component on all four target runtimes. PHP parsing
used the PHP 8.3 host. Installed GUI, API and native browser execution are separate
checks with their own tested-source identities.

The reviewable files contain no database dump, site configuration, credentials,
installation logs, runtime binaries or generated binary archives. Complete
unnormalized diffs remain in scratch; `full-diff-digests.json` identifies each
exact diff by size and SHA-256.
