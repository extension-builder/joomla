# Integration test provenance

The API and GUI harnesses require Python 3 (standard library only) and
`sha256sum`. Their workflows run `python3 .github/test-infra/selftest.py`
before creating Joomla installations.

`package-manifest.py` extends the golden harness's archive-derived SHA256
check to the runtime destinations declared by component/plugin manifests:
administrator, site and API files, media, and JCB's shipped `vendor_jcb`
libraries. This includes admin controllers, models, views, templates and
compiler templates. Both harnesses verify the installed working-tree package;
the API harness also verifies each generated component and webservices plugin
after both installations. The API harness captures each checksum manifest before
installation, because Joomla may consume the ZIP from its temporary directory.
Verification then reads only that retained manifest and the installed files.
The harnesses retain the checksum manifests and verification
logs. Missing or different files fail the run. Languages, installer-only files,
and obsolete files left by an earlier release are outside this check.

`site-proof.py` creates a fresh random file inside that verified installation.
The HTTP target must return exactly those bytes with HTTP 200; redirects,
HTTP errors, other content and readiness timeouts fail. It sends no Joomla
credentials. The GUI browser repeats the proof before login, using the same
root-relative addressing as its administrator journeys. An explicitly set
`JCB_BASE_URL` must serve the disposable container's proof; the harness does
not accept another installation just because it has a login screen.

Before each API server start the chosen loopback port must be free. The new
server process must remain alive while its proof is fetched. A fresh proof is
created for the second compiled model variant. This prevents a previous or
unrelated server from satisfying readiness and receiving API tokens. Proof
files are removed at cleanup, including when the installation is retained.

The API harness now treats a nonzero compiler exit as failure even if a ZIP
was produced, and requires successful creation of the fresh API user. These
checks complement the unresolved-Power and behavioral scenario gates; they
do not imply that every deployed file has behavioral or line coverage.

The offline regression suite checks every mapped runtime category, changed
and missing installed files, malformed manifests/paths, plugin destinations,
wrong HTTP targets/statuses, redirect rejection, unavailable servers, occupied
ports and dead server processes. Full Docker/Joomla integration still runs in
the hosted GUI/API workflows.
