# Public Power dependencies in compiler tests

The API and golden-master harnesses use the official public `joomengine`
mirrors of `git.vdm.dev/joomla/joomla-powers` and
`git.vdm.dev/joomla/super-powers`. The fixture repository configuration is
shared in `.github/golden-master/power-repositories.php` and pinned to:

| Catalog | Repository | Revision |
| --- | --- | --- |
| Joomla Powers | [joomengine/joomla-powers](https://github.com/joomengine/joomla-powers) | `e38ad0600bdd82513021ec7e5b2b9eecfe7f2f0b` |
| Super Powers | [joomengine/super-powers](https://github.com/joomengine/super-powers) | `adf335173201edeaf95f0ef6c3dc1bcabd23f3bc` |

Both repositories identify themselves as mirrors of the original catalogs.
The revisions were checked against the missing dependency GUIDs before being
selected. Updating these pins is a reviewed fixture change.

## Failure evidence

The [API run 37679474659](https://github.com/extension-builder/joomla/actions/runs/37679474659)
failed before the HTTP scenarios: its `compile.log` reported that Gitea only
allowed signed-in users to call its API, then left unresolved Joomla Power
placeholders in the generated Demo files. The missing Joomla Power GUIDs were:

- `ca5456e1-552c-45fb-bf4c-b751ba6e9fa1`
- `a87c432d-b5b4-428e-b7ff-14b51664c624`
- `193deb3e-0c3e-4610-8e55-450e463095b4`
- `46ba6c12-361e-4f7d-b6d9-70450e7cd7c2`
- `a6ee04f5-33c7-4a9b-aa6d-6a03f3715a88`
- `47ee1f2b-9902-4f26-a856-04930ac9ddc3`
- `f5a65880-6185-4f43-8d55-770616692a40`

The [golden run 37679474379](https://github.com/extension-builder/joomla/actions/runs/37679474379)
failed for targets 4 and 5 during baseline dependency warmup, before installing
the candidate. The baseline source was
`f748af3b0ebf6ae319571b3cce86f0f76c8c2c86`. Target 4 lacked Joomla Power
`ca5456e1-552c-45fb-bf4c-b751ba6e9fa1`; target 5 lacked Super Power
`7d95ce74-53dc-4672-bd8a-3b71cdacabea` (Actions). Those logs did not include the
underlying HTTP response, so the API run supplies the direct authentication
failure evidence.

The pinned mirrors contain all seven Joomla definitions and Actions with its
GetHelper and StringHelper dependencies. The Joomla definitions retain their
native version-zero fallback mappings; the compiler still selects namespaces
for the requested generated Joomla target through its existing resolver.

## Harness boundary

The overrides apply only to each disposable compile process. Golden warmup,
baseline and candidate runs load the same file after resetting the compiler
container and before resolving the compiler. The API driver boots the installed
Joomla console, applies the same configuration, and executes the existing
`componentbuilder:compile:component` command for each compilation. It requires
the disposable-site environment flag and site marker. Component, target and
build options continue through the normal command.

Both use the existing GitHub repository client with pinned `read_branch`
revisions. An optional read-only `GITHUB_TOKEN` stays in process memory; it is
never written into definitions or evidence. No production repository defaults,
Power definitions or generated imports are changed. Missing dependencies still
fail the API placeholder scans and golden final-Power checks. Golden output
and identity comparisons remain unchanged, using the same database snapshot
for both measured builds.

PHP lint, Bash syntax checks, disposable-driver rejection and the existing
golden-harness self-test can run without Docker. Full dependency resolution and
the installed API scenarios require their hosted Joomla/MySQL jobs; complete
golden compilation requires Docker/Compose.
