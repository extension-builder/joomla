# Native conditional compiler verification

## Result

The full native compiler completed for Joomla targets **3, 4, 5 and 6** using the same restored local definition snapshot for every baseline and candidate run. Each complete output tree changed exactly **nine files**, with no additions or removals. The changes are the expected conditional-validation models, shared rule, browser listeners and scripts, plus two generated README statistics sections. No timestamps, paths, whitespace or other output were normalized or ignored.

| Target | Files in each package | Byte-identical files | Changed files | PHP files parsed | JavaScript files parsed |
| --- | ---: | ---: | ---: | ---: | ---: |
| Joomla 3 | 1,366 | 1,357 | 9 | 976 | 24 |
| Joomla 4 | 288 | 279 | 9 | 123 | 21 |
| Joomla 5 | 293 | 284 | 9 | 128 | 21 |
| Joomla 6 | 249 | 240 | 9 | 128 | 4 |

Every PHP and JavaScript file in all four candidate packages passed its syntax check: **1,355 PHP files and 70 JavaScript files**. The full compiler's resolved-Power completion JSON was byte-identical within each baseline/candidate pair: **65 final Powers, including one late-discovered Power**. Package creation and syntax checks do not assert that the generated component was exercised on every target Joomla runtime.

## Source and input identity

- Baseline: `2dab8eb3249185d18b11a55694adb559c349addc`.
- Candidate local commit: `689daff5b7ee2860d6887da9e533a3e4dc707fde`.
- Candidate remote PR commit: `2adeebb4c50907fe5d0509e12ed882e6b2e585dc`.
- Candidate Git tree: `0eec4b1626e7f452a67bca4319912e6d43e4b02b`.
- Shared private SQL snapshot SHA-256: `e4954f24abdb1382991a3f03d90912f8877f088389acffb2cb94e1ccdf3476bc`.
- Host: copied disposable Joomla **6.2.0**, PHP **8.3.6**, MariaDB **10.11.14**. The original site and database were not modified.

The local and remote commits have different commit metadata but the exact same
Git tree. The final verification-documentation commit follows this measured
implementation without changing production code, tests or templates.

Both packages were installed through Joomla's native `extension:install` command. Baseline first-party library verification checked **1,665** deployed files against that source. Candidate verification checked **5,407** deployed library, admin source/template/form/compiler, API source and JavaScript files against the complete committed package. Both had **zero mismatches**. The complete candidate came from `git archive`, which includes paths omitted by the sparse development checkout. An earlier incomplete package attempt was discarded and contributed no candidate measurement.

## Meaningful authored condition fixture

This is a public HelloWorld component with an explicitly synthetic condition overlay, not a JCB self-compilation or an unchanged public fixture.

- Public repository: `joomengine/packages`; repository GUID `562624ab-48bf-4979-9a14-6b10cf3635de`.
- Component GUID: `3745af8f-f96b-4e17-831e-eb4062cd4389`.
- Greeting view GUID: `65116558-be67-4931-95be-727fbfb16db7`.

The overlay creates required `storage_kind`, `length_mode`, `database_type` and `database_length` fields through native JCB definition rows and the ordinary admin-field links. It authors four existing-DSL condition rows: length mode `fixed|custom` AND nonempty Greeting controlling database length; the historical `Is Not` OR expression for `note|spacer` controlling database type; and a final positive `note|spacer` HideOnly rule for both database targets. This exercises related selectors, shared targets, authoring order, historical inequality semantics and final applicability through the actual definition-to-package pipeline.

`fixture-overlay.php` is the exact scratch augmentation driver; `conditional-fixture.json` records the authored data. The script requires the existing golden-harness `bootstrap.php` alongside it, and an `evidence` directory one level above that driver directory. Run it only in a disposable fixture database. Its GUIDs make reapplying the overlay update the same test fields rather than add duplicates.

The repository bootstrap is `.github/golden-master/bootstrap.php`; it expects
the disposable stack's `/var/www/html` site and marker. The local measurement
used the equivalent copied-site bootstrap with its scratch site path. The
numeric row IDs in the JSON are observed values from that disposable database;
the GUIDs identify the fixture records across installations.

## Inspected output differences

For targets 4–6, the nine changed paths are:

- `admin/src/Model/GreetingModel.php` and `site/src/Model/GreetingModel.php`: formatted declarative condition property, short native-parent validation wrapper and named data-context resolver.
- `admin/src/Rule/JcbconditionalrequiredRule.php`: named OO lifecycle and match methods, per-form state, native post-filter timing and cleanup.
- `admin/tmpl/greeting/default.php` and `site/tmpl/greeting/edit.php`: watched selectors call the common ordered refresh function.
- `media/js/greeting.js` and `site/forms/greeting.js`: recompute from captured display/required baseline in authored order, update required attributes directly, and remove generated browser CSV bookkeeping.
- `README.md` and `admin/README.txt`: generated line counts and derived estimated build-time statistics only.

Joomla 3 has the equivalent legacy model/rule/view paths, listed exactly in `comparison-summary.json`. All other output bytes are identical. Each generated admin and site model contains exactly one condition property, one validation wrapper and one named resolver. Existing `not_required` XML declarations may remain as inert form metadata; this result does not claim every declaration was removed.

## Offline reproduction procedure

The public definitions and all required Power records must already be present in the disposable database. The measured phase performs no dependency downloads. It uses the repository's native golden bootstrap, creation-date fixture and full compiler evidence driver; the scratch driver replaces remote repository configuration with the offline settings recorded in `offline-policy.json`.

1. Install the full baseline archive in a disposable Joomla site with the prepared public definitions. Apply `fixture-overlay.php`. Run the existing creation-date driver for the component and repository GUIDs above; it pins component and linked-view creation dates to `2026-10-07 00:00:00`.
2. Enable the offline PHP guard and set `approved_paths`, `approved_joomla_paths` and `approved_package_paths` to empty arrays. Set `backup=0`, `repository=0` and `add_super_powers=false`. The driver also verifies the exact local test database before entering the compiler.
3. Complete one offline warmup for every target, then take one local SQL snapshot. If a dependency is missing, stop rather than enable network access or fabricate a record.
4. Restore that exact snapshot before **each** measured target. Compile with `--debug-line-nr=0 --add-build-date=2 --build-date=2026-01-01`, retain the native package and full completion evidence, and require the generated manifest's major version to equal the requested target.
5. Install the full candidate archive natively and verify deployed source hashes. Restore the same snapshot before each candidate target, using identical compiler options and guard settings.
6. Compare every extracted file name and byte sequence, retain complete diffs, and compare the full resolved-Power evidence. Parse all candidate PHP files with `php -l` and JavaScript files with `node --check`.

The offline guard sets `allow_url_fopen=0` and disables cURL execution, PHP socket connections, FTP/SSH connections, mail and shell/process execution. The native MySQL driver connects only to the isolated local database. The precise disabled-function list and compiler settings are included in `offline-policy.json`.

The exact commands used for the measured runs, from the prepared scratch harness directory, were:

```bash
JCB_DISPOSABLE_TEST=1 python with-database.py python measure.py baseline
JCB_DISPOSABLE_TEST=1 python with-database.py runtime/php-offline \
  -d memory_limit=1536M -d upload_max_filesize=128M \
  -d post_max_size=128M -d max_input_vars=7000 \
  site/cli/joomla.php extension:install \
  --path=/workspace/scratch/caaabd34d2a6/conditional-golden/candidate-install.zip \
  --no-interaction
JCB_DISPOSABLE_TEST=1 python with-database.py python measure.py candidate
```

`with-database.py` starts and stops only the scratch MariaDB process. `measure.py` verifies the snapshot digest, restores it independently for each target, invokes the native compiler evidence driver and captures the single resulting component archive. These environment-specific orchestration scripts and private prerequisites remain in scratch; the procedure above states the portable reproduction contract. The captured snapshot makes the comparison exact, while a future fresh import may differ if the upstream public definitions evolve.

## Limits and retained evidence

The Joomla 3 baseline and candidate emit the same pre-existing `mainAccordionName` undefined-key warning in the dashboard compiler. Both complete successfully. There were no candidate-only compiler warnings in these runs. PHP parsing used the PHP 8.3 host; it does not replace runtime testing on Joomla 3, 4 or 5.

An earlier network-capable setup attempt was stopped by automatic approval review. It was not retried. All measured runs use the documented offline route with cached records. No database snapshot, installed-site configuration, credential, runtime binary, generated binary archive or raw installation log is included in the reviewable evidence.

Four complete, unnormalized text diffs remain in scratch; their exact sizes and SHA-256 values are recorded in `full-diff-digests.json`. Source, snapshot and deployment identities, per-target changed paths, resolved-Power equality and syntax counts are provided in the adjacent JSON summaries. A separate review of the generated OO lifecycle found no additional issue requiring a source change.

## Installed GUI evidence

The separate [GUI workflow](https://github.com/extension-builder/joomla/actions/runs/43081242615)
tested the same remote implementation head through merge `a1156962`. It passed
all 40 tests with zero failures, skipped tests or flaky results. Native
installation and the HTTP installed-site proof succeeded. All 4,951 deployed
file checksum checks passed, including exact matches for the nine conditional
models, shared rule, Field template and Field JavaScript. The downloaded
[artifact](https://github.com/extension-builder/joomla/actions/runs/43081242615/artifacts/13884766979)
also matched GitHub's SHA-256 digest. `installed-gui-summary.json` records the
run, artifact and tested-source identities and the twelve relevant file hashes.

## Local complete-suite comparison

`unit-comparison-summary.json` records independently parsed JUnit totals from
the same locked PHP 8.3.6 / PHPUnit 12.5.33 / Joomla 6.1.2 runtime. The recovery
baseline has 4,777 tests, 40 errors and 33 failures; the candidate has 4,852
tests, 3 errors and 33 failures. Comparing failed test identities finds zero
new errors or failures and 37 resolved baseline fixture errors. The remaining
failures are documented in the main development record. This local comparison
is separate from CI, whose unchanged dependency audit stops before PHPUnit.
