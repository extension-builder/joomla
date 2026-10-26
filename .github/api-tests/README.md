# Installed API and native plugin regression

`run.sh` installs Joomla 6.1.2 and the released JCB package on a disposable
MySQL database, installs the current working tree, then compiles and installs
the shipped Demo component and its linked plugins. The `API tests` workflow
runs this on PHP 8.4. The vendor PHPUnit matrix separately covers PHP 8.3 and
8.4.

The existing webservices definition retains `[[[API_ROUTES_METHOD]]]`, so the
installed HTTP scenarios exercise normal generated API routes. A second linked
definition stores the migrated FieldOrderingTabs plugin's actual class body
and imports, with its real forms attached through JCB's folder records. These
come from `extension-builder/plugins` commit
`8456005f1de725ff30bb928accb20fd86eb37488`, checked out during workflow setup.
No emitted PHP, provider, form or archive is patched after compilation.

This is a representative source-definition fixture. It does not constitute a
complete maintained JCB definition export or a full JCB self-compilation.

## Run locally

Use PHP 8.4 with the extensions listed in `../workflows/api-tests.yml`, MySQL
credentials authorized to create a disposable database, Python 3, Git, curl,
zip and unzip. The harness recreates its output `site` directory and writes the
named database; use dedicated test locations and credentials.

From the repository root, prepare the fixture once:

```sh
git clone https://github.com/extension-builder/plugins.git .api-tests/plugin-source
git -C .api-tests/plugin-source checkout --detach 8456005f1de725ff30bb928accb20fd86eb37488
```

Then run the normal installed harness:

```sh
JCB_DB_HOST=127.0.0.1 JCB_DB_USER=root JCB_DB_PASS=root \
  JCB_PLUGINS_SOURCE="$PWD/.api-tests/plugin-source" \
  bash .github/api-tests/run.sh "$PWD/.api-tests"
```

`JCB_PLUGINS_SOURCE` can point to an existing checkout; it defaults to
`<output-directory>/plugin-source`. Missing class/form fixtures fail before
site installation. The source hashes are recorded in `native-content-seed.log`.
Changing the pinned fixture requires reviewing and updating both this document
and the workflow checkout SHA.

## What the installed checks prove

- JCB loads stored plugin bodies/imports and component links from its database,
  copies attached forms, and produces both plugin archives alongside Demo.
- Joomla installs those archives; installed file hashes must match their ZIPs.
- Joomla boots the generated content service provider and registers the actual
  installed class through `PluginHelper::importPlugin`, using an isolated real
  dispatcher. Class identity and loaded file path must match the manifest.
- A real `PrepareFormEvent` adds the four ordering fields, preserves nested
  subform options and conditions and existing form data, and leaves unrelated
  forms untouched. It emits no legacy results or plugin deprecations.
- The content check runs again after the second compile/install, which changes
  the Demo list-model names and reruns API CRUD and permission checks.

Deprecation capture covers provider boot, native subscriber registration and
event dispatch, including suppressed PHP deprecations. Only Joomla application
bootstrap and published-plugin metadata loading happen before capture: Joomla
6.1.2's own metadata loader still calls deprecated Factory accessors. No plugin
class is loaded in that preparatory metadata call, and no deprecation is
filtered out once provider boot begins.

`native-content-dispatch.log` and `model-role-native-content-dispatch.log`
record the installed class path and behavior assertions. The workflow retains
these with compiler, installer and package-provenance logs on failure or success.
