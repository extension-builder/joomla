# Graphical user interface testing

The JCB administrator interface is tested through the browser, with
Playwright, against a real Joomla carrying a real install of this working
tree. This document is the authoritative guide for that suite: where it
lives, how it runs, how to write specs for it, and the governance rule that
keeps it growing with the interface.

## The rule

**Every change to the graphical user interface ships with GUI test coverage
of the changed behavior, in the same change.** A new view gets a new spec
file; a changed behavior gets its assertions updated; and the change's
[GUI change record](../graphical-user-interface-changes/README.md) names the
spec under its verification section. A record whose change has no coverage
must say so and why, the same way skipped checks are declared — silence is
not an option.

The interface is large and was not built under this rule, so most of it is
not yet covered. That is recorded debt, not precedent: coverage is added
view by view (the extrusion view was first), and every touched view pays its
way in as it is touched.

## The architecture

Three pieces, each with one job:

| Piece | Path | Job |
| --- | --- | --- |
| The suite | `libraries/vendor_jcb/tests/gui/` | Playwright specs, config, and helpers. One spec file per admin view. |
| The harness | `.github/gui-tests/` | `docker-compose.yml` + `run.sh`: stand up the site, install this working tree, guarantee a login, run the suite, keep the evidence. |
| The workflow | `.github/workflows/gui-tests.yml` | Runs the harness on pull requests that touch the interface, writes the log report into the step summary, and uploads the report, traces, screenshots and videos as an artifact. |

The container flow is the golden master's, on purpose:

1. `octoleo/joomengine:latest` starts; its entrypoint installs Joomla and the
   **released** JCB, so the environment is always the one people actually
   install into today.
2. The working tree is zipped with the same exclusions the golden master
   packages with, handed to the container, and installed with the same
   `extension:install` the entrypoint uses — JCB installing itself over the
   released JCB is exactly the update path users take.
3. The install is proven, not trusted: a file the released JCB does not ship
   must answer inside the container byte for byte before any test runs.
4. A known Super User account is guaranteed through the Joomla console
   (`user:add`, or `user:reset-password` when it exists), so the suite's
   login never depends on image defaults.
5. The suite logs in once (`specs/global.setup.js` saves the storage state)
   and every spec reuses that session.

## Running it locally

Requirements: docker with the compose plugin, node 20+, and ports 8080/3306
free.

```bash
# from the repository root — stands up the stack, installs the working tree,
# runs the whole suite, and tears the stack down again
bash .github/gui-tests/run.sh

# keep the containers running afterwards, for iterating on specs
KEEP_STACK=1 bash .github/gui-tests/run.sh

# then iterate against the running stack from the suite directory
cd libraries/vendor_jcb/tests/gui
npx playwright test                      # everything
npx playwright test specs/extrusion.spec.js
npx playwright test --ui                 # the Playwright UI mode
npm run report                           # open the last HTML report
```

The harness accepts `JCB_BASE_URL`, `JCB_ADMIN_USER`, `JCB_ADMIN_PASS` and
`PLAYWRIGHT_INSTALL_ARGS` through the environment; its defaults match the
suite's, so a plain run needs no exports. Everything a run saw — the
Playwright report, `results.json`, traces, the container log — lands in
`.gui-tests/` at the repository root.

## Writing specs

- **One spec file per admin view**, named after the view:
  `specs/extrusion.spec.js`, `specs/compiler.spec.js`, and so on. Shared
  mechanics belong in `helpers/`.
- **Select what a person sees.** Prefer roles and the natural-language
  strings the views print through `Text::_()`
  (see [user-interface-language-strings.md](user-interface-language-strings.md)),
  then the stable ids the views define. Never select on generated markup a
  template change would rename silently.
- **Drive the real AJAX.** JCB's views are AJAX-heavy: harvests, compiles,
  catalogue fetches, subform loads. Specs wait on the *outcome* the person
  waits on — the pane that appears, the board that fills, the report that
  renders — with timeouts sized for a cold container (the extrusion spec
  allows 120s for a harvest round-trip). Never wait on fixed sleeps, and
  never mock the endpoints: the point of this suite is that the installed
  extension answered.
- **Leave the site as found.** Specs must not depend on data an earlier spec
  wrote, and must not write durable data at all where the view offers a way
  around it — the extrusion spec runs its import as a dry run, which proves
  the whole pipeline and writes nothing. A future spec that must write real
  rows cleans them up, or the harness gains a database reset step first.
- **The suite is serial** (`workers: 1`): one shared login, one shared site,
  AJAX endpoints that hold server state. Do not switch on parallelism
  without giving every worker its own site.
- **Failures must explain themselves.** Assertion messages say what the
  interface promised ("the extrusion tile stands right next to the
  compiler"), so the log report reads as a list of broken promises, not a
  list of selectors.

## Reading a failed run

The workflow's step summary carries the log report: counts, then every
failed spec with its error. The `gui-tests` artifact carries the full
Playwright HTML report, the traces (`npx playwright show-trace <zip>`),
screenshots and videos of every failure, the container log, and the install
log — enough to act on any failure without reproducing it first.
