# Reviewed compiler output changes

The default gate requires identical generated trees. An intentional compiler
fix can instead declare one exact reviewed output change for a specific source
baseline, measured baseline tree, blueprint, repository, target and common
compile options. Each `joomla-N.context` records these inputs literally; its
adjacent `.patch.base64` contains the complete binary-capable, full-index Git
diff. Base64 preserves the literal CRLF and diff-context whitespace without
introducing whitespace exclusions in the repository. To inspect a fixture:

```bash
base64 --decode path/to/joomla-6.patch.base64
```

`compare_output` decodes and applies that patch to a temporary index containing the measured
baseline tree and requires its resulting tree to equal the staged candidate.
This compares every path, byte and executable mode. Nothing is normalized or
ignored: forced staging includes files covered by generated or global ignore
rules. A full or partial revert of the expected fix fails too. The raw
baseline-to-candidate diff remains in the log and artifact. Power identities,
installed-source verification, target verification and the subsequent installed
scale probe remain independent gates. `comparison.txt` records tree identities
and the selected expectation.

Add an expectation only after reviewing a failed hosted comparison. Never
generate or refresh one automatically in CI. A changed baseline or context
requires fresh review; unrecognized contexts retain strict tree equality.
The measured baseline tree also prevents a changed upstream blueprint or
dependency from silently altering an existing reviewed expectation.

For the reviewed Hello World component and public repository below, the harness
pins the imported component and its linked administrator, site and custom
administrator view `created` inputs to `2026-10-07 00:00:00` before warmup and
the shared database snapshot. The importer otherwise uses the current date;
`--build-date` controls the last build date separately. The guarded disposable
driver records the exact affected GUIDs in `fixture-created-date.json`. Other
components and repositories retain their original inputs. Generated files are
never rewritten or normalized.

## 6.2.0 partial-update preservation

Source baseline: `f748af3b0ebf6ae319571b3cce86f0f76c8c2c86`.
Reviewed candidate: `da222fe`, compiled by the PR merge commit
`965a3fabb91140d1c84318dbd0ba927fb847e641`.
Evidence: [workflow run 37682020469](https://github.com/extension-builder/joomla/actions/runs/37682020469).

| Target | Artifact | Compared files | Changed files | README line count |
| --- | --- | ---: | ---: | --- |
| Joomla 3 | `11509615992` | 1,369 | 4 | 29,178 → 29,348 |
| Joomla 4 | `11509625945` | 291 | 4 | 30,188 → 30,358 |
| Joomla 5 | `11509168383` | 296 | 4 | 30,448 → 30,618 |
| Joomla 6 | `11510155472` | 252 | 4 | 30,420 → 30,590 |

Every target changes only the administrator and site greeting model plus
`README.md` and `admin/README.txt`. The Joomla 3 model paths are
`admin/models/greeting.php` and `site/models/greeting.php`; the other targets
use `admin/src/Model/GreetingModel.php` and `site/src/Model/GreetingModel.php`.

All eight model deltas contain exactly the same 95 added and 10 removed lines:
API metadata controls remove unauthorized or omitted PATCH metadata from the
form, and the save path preserves omitted stored values without a lossy
decode/encode round trip. Existing administrator controls remain in the
non-API branch. No search, query, Power, asset, manifest or other generated file
changes in this blueprint. The more specialized fixes are covered separately
by their regression tests and installed API tests.

The two README files reflect the resulting 170 additional lines. Every changed
time estimate was recomputed from the unchanged `Utilities/Valuation.php`
formulas, including the Joomla 6 overhead estimate decreasing from 53 to 52
hours because base and total hours are truncated independently. These are exact
reviewed values in the patches, not ignored metrics.

All eight generated models pass PHP syntax checking. Both sides of all four
targets passed all 1,664 installed-source hashes and have byte-identical final
Power evidence. These first comparisons stopped at the output-difference gate;
the scale probe must still pass after the reviewed change is accepted.

## Audit follow-up: tag permissions and denial messages

The current patches supersede the initial preservation patches above. Reviewed
candidate: `9a69230802f23bb7998d94316fc671f200cc0aa5`, compiled by PR merge commit
`fe637ae1e8bca2dbec96f1dd22111458e6de1be6`.
Evidence: [workflow run 37741432763](https://github.com/extension-builder/joomla/actions/runs/37741432763).

| Target | Artifact | Compared files | Changed files | README line count |
| --- | --- | ---: | ---: | --- |
| Joomla 3 | `11533868466` | 1,369 | 4 | 29,178 → 29,378 |
| Joomla 4 | `11533149231` | 291 | 4 | 30,188 → 30,388 |
| Joomla 5 | `11534200699` | 296 | 4 | 30,448 → 30,648 |
| Joomla 6 | `11534270011` | 252 | 4 | 30,420 → 30,620 |

All four baseline trees exactly match their original checked-in contexts; every
baseline file byte and executable mode also matches the first reviewed build.
The creation-date input fixture restores that reproducibility across dates.
All four `.context` files remain unchanged.

Each greeting model adds four lines retaining its actual PATCH validation form
and eleven lines preventing omitted or forbidden tags from reaching Joomla's
automatic empty-tag fallback. Joomla 4–6 administrator greeting models also
replace the unavailable JCB denial translation with core `JERROR_ALERTNOAUTHOR`.
These are the only model changes beyond the first review. The same four paths
per target change; no additional generated files change in this blueprint.

The two README files now reflect 200 additional lines. All changed hours, days,
weeks and months were recomputed from the unchanged valuation formulas, including
the Joomla 5 office estimate increasing from 12 to 13 hours. All eight generated
models pass PHP syntax checks, and baseline/candidate Power evidence is identical
for every target. The full raw hosted diffs were encoded without modification;
applying each decoded patch to its exact baseline reproduces the measured
candidate tree, including every byte and executable mode. The hosted scale probes
still run after the exact output gate accepts these reviewed changes.
