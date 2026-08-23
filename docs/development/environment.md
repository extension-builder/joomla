# Development environment

Everything a fresh machine — or a fresh coding agent session — needs to work
on this repository for production development: what to install, how each test
track is stood up, which gates run before handoff, and where the
authoritative documentation for each area lives.

## What this repository is

Joomla Component Builder (JCB): a compiler that builds native Joomla
extensions from structured definitions. It is developed as an installable
Joomla package whose only JCB-owned library tree is
`libraries/vendor_jcb/**`; the administrator application under `admin/**` is
generated from JCB's own definitions and is protected by the
[change-boundary policy](change-boundaries.md). Read the repository-root
`AGENTS.md` first — it carries the non-negotiable ownership boundaries and
the architecture invariants every change answers to.

## Prerequisites

| Tool | Needed for | Version |
| --- | --- | --- |
| PHP | unit tests, style/ownership gates | 8.3 (supported minimum) or 8.4 |
| Composer | the unit test project | 2.10.x (CI pins 2.10.2) |
| Git | everything | any current release |
| Docker + compose plugin | golden master, GUI tests | any current release |
| Node.js + npm | GUI tests | 20+ |
| jq, zip, curl, md5sum | the CI harnesses' scripts | any current release |

## One-time setup, per track

### Unit tests (PHPUnit, no Joomla application)

The first-party test project lives in `libraries/vendor_jcb/tests` and runs
against a pinned Joomla source checkout, without booting Joomla:

```bash
cd libraries/vendor_jcb/tests
git clone --branch 6.1.2 --depth 1 https://github.com/joomla/joomla-cms.git .runtime/joomla-cms
composer install
composer test
```

[testing.md](testing.md) is authoritative for the runtime contract, the
package suites, the known-defect ledger, and the source-to-test ownership
ratchet.

### GUI tests (Playwright, real Joomla in docker)

The suite lives in `libraries/vendor_jcb/tests/gui`; the harness in
`.github/gui-tests` stands up `octoleo/joomengine:latest` (Joomla with the
released JCB), installs this working tree over it, and runs the suite:

```bash
bash .github/gui-tests/run.sh              # the whole thing, once
KEEP_STACK=1 bash .github/gui-tests/run.sh # keep the site up for iterating
```

[gui-testing.md](gui-testing.md) is authoritative for the architecture, the
spec-writing rules, and the rule that GUI changes ship GUI tests.

### Compiler golden master (manual, heavy)

`.github/golden-master/run.sh` compiles one component with the released
compiler and with this working tree and diffs the results. It runs from the
`Compiler golden master` workflow on demand; locally it needs docker and
~10 minutes. Read the header of `run.sh` for its knobs.

## The gates before handoff

From `libraries/vendor_jcb/tests`, with `<base>` the merge-base commit:

```bash
git diff --check <base>...HEAD
php bin/check-php-style.php --base=<base>
php bin/check-test-ownership.php --base=<base>
php bin/check-moved-conditions.php --base=<base>
php bin/check-container-keys.php
composer test
```

These are the same checks `vendor-jcb-tests.yml` runs on every pull request.
A change that touches the interface also runs the GUI suite (the
`GUI tests` workflow runs it on such pull requests automatically), and a
change under `admin/**` or `media/js/**` ships its
[GUI change record](../graphical-user-interface-changes/README.md) in the
same change.

## The documentation map

Where to look before implementing, by area:

| Working on | Read first |
| --- | --- |
| Anything at all | `AGENTS.md` (repository root) |
| Library code under `libraries/vendor_jcb/**` | `AGENTS.md` architecture invariants; [php-code-style.md](php-code-style.md) |
| Unit tests, ownership, known defects | [testing.md](testing.md) |
| The administrator interface (`admin/**`, `media/js/**`) | [change-boundaries.md](change-boundaries.md); [../graphical-user-interface-changes/README.md](../graphical-user-interface-changes/README.md); [gui-testing.md](gui-testing.md); [user-interface-language-strings.md](user-interface-language-strings.md) |
| GUI test specs and harness | [gui-testing.md](gui-testing.md) |
| Compiler behavior | `docs/architecture/` (per-domain documents); the golden master workflow |
| The extrusion pipeline | `docs/architecture/extrusion.md` |

## Continuous integration, at a glance

| Workflow | Trigger | What it proves |
| --- | --- | --- |
| `vendor-jcb-tests.yml` | every pull request and push to `6.x` | style, ownership, hygiene gates and the full PHPUnit suite on PHP 8.3 and 8.4 |
| `gui-tests.yml` | pull requests touching the interface; on demand | the installed extension drives correctly through a real browser |
| `compiler-golden-master.yml` | on demand | this working tree's compiler produces the same components the released one does |
