# Compiler-aligned Power discovery

## Scope and baseline

This follow-up implements the version 2 extrusion handover: component-rooted,
read-only dependency discovery, direct candidate buckets and bounded approval
revalidation. The separately discussed local Powers Autoloader plugin and a
persistent full-compilation manifest platform are outside this repair.

The live `main` baseline on 29 September 2026 is
`1a6ab1a99c6f0296a222e807b59eddca17369874`, tree
`8e917b3169d8ab24e412c05c8fd66fcdf8cc550f`. PR #53 is merged. The follow-up
branch is `fix/extrusion-compiler-power-discovery`; no production definition
mutation, merge or deployment is part of this work.

## Confirmed call and ownership boundaries

- `Extrusion/Powers/Resolver/References::context()` currently enters the full
  component catalogue. Its `consumers()`, `complete()` and `fingerprint()` also
  enter that path. Selected-root discovery and global consumer coverage must
  become separate operations.
- `Powers/Resolver/Existing::power()` currently initializes a full catalogue
  before looking up one GUID. The installed schema already has non-unique
  `idx_guid` and `idx_namespace` indexes; indexed equality reads can retain
  duplicates without inventing a uniqueness constraint.
- `Powers/Resolver/Identity::resolve()` currently evaluates every stored Power
  before rejecting unrelated namespaces. `fingerprint()` also serializes the
  entire catalogue. Both are part of the repair, not only the matching loop.
- `Powers/Harvester`, `Powers/Assembler`, `Resolver/Commit` and `Registry/Plan`
  share responsibility for source observations, contextual decisions, effective
  writes and stale-approval protection. Reuse must retain these boundaries.
- `Compiler/Power` has GUID-addressed state and recursion guards, but can import
  missing definitions and processes build state. It is not a read-only loader.
  `Compiler/Power/Extractor::get()` is the existing pure token contract.
  Shared extraction must keep compiler events, output and target-version
  behavior unchanged.
- `Compiler/Extension/Files/Power` can discover Powers after initialization;
  an early active-set dump is not a complete compiler parity test.

Library changes belong under `libraries/vendor_jcb/**`. The approved interface
scope is limited to `admin/src/Model/AjaxModel.php`,
`admin/assets/js/extrusion.js` and `admin/tmpl/extrusion/default.php`, with a
same-change GUI record and browser coverage. External vendor libraries and
other protected media remain untouched. Generation-source reconciliation must
be reported rather than asserted without the external source records.

## Deterministic regression gates

`IndexedDiscoveryTest` rejects unbounded component/Power reads during a cold
selected-root operation, including its approval fingerprint. It also protects
negative GUID caching, explicit incomplete consumer coverage, and competing
GUIDs in one candidate bucket. The passive database fixture records requested
filters and returned row identities; it does not implement discovery logic.

These tests are intentionally introduced before the implementation. A passing
candidate must not weaken the catalogue-isolation assertions or move failures
into the known-defect group. Real SQL plans, full compiler comparisons and
installed HTTP/browser evidence remain additional acceptance gates.

## Safety contract

A correct selected-component match does not prove exclusive ownership.
Unavailable reverse-consumer evidence must remain incomplete, and effective
changed shared/foreign/unknown writes retain fingerprint-bound review. Genuine
no-op imports must not create auxiliary metadata or require an unnecessary
write. Literal namespace segments, custom aliases, manual pairing, source keys,
all competing GUIDs, skipped collision occupants and transaction preflight
remain protected.

## Verification status

The source tree above was recovered through the existing GUI workflow's source
provenance bundle and verified locally by Git tree hash. The local runtime has
PHP 8.4.23. Baseline probes use the historical in-memory loader and measure only
identity resolution after graph loading, not production SQL or HTTP. Final
acceptance requires evidence from the follow-up head, not those baseline probes
or PR #53's historical green checks.
