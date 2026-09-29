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

## Implemented discovery and matching boundaries

- `Extrusion/Powers/Resolver/References::context()` reads the selected component
  directly and follows its supported relationships. GUID-addressed record and
  edge caches bound cycles and shared subgraphs. Consumers are an index of
  observed roots, and global consumer completeness is explicitly false.
- `Powers/Resolver/Existing::power()` uses a GUID equality lookup and retains
  negative results. Name and stored-namespace fallback use the existing
  non-unique `idx_name`, `idx_namespace` and `idx_guid` indexes. Duplicate GUID
  rows remain integrity failures; no new uniqueness constraint repairs history.
- `Powers/Resolver/Identity` indexes relevant definitions by contextual FQN and
  output destination. Candidate buckets retain competing GUIDs. Matching and
  collision checks use these buckets, including skipped existing occupants.
  Each component context shares one index across source vendors. Concrete
  names use direct buckets; variable `NamespacePrefix` representations use a
  reverse trie whose candidates still pass the existing semantic checks.
  Approval fingerprints cover the bounded read set, including negative queries,
  and replay that evidence before persistence to detect new competitors.
- `Powers/Harvester`, `Powers/Assembler`, `Resolver/Commit` and `Registry/Plan`
  share responsibility for source observations, contextual decisions, effective
  writes and stale-approval protection. Reuse must retain these boundaries.
- `Compiler/Power/Selection` shares pure enablement, selectors, inheritance,
  enabled code fields and literal custom-code/template/layout references with
  existing compiler callers. `Compiler/Power/Extractor::get()` remains the
  Super Power token contract. Discovery does not construct the compiler, fetch
  remote definitions, execute custom code or generate files.
- `Compiler/Extension/Files/Power` can discover Powers after initialization;
  an early active-set dump is not a complete compiler parity test.

Discovery also includes dependencies introduced by the compiler's own output.
The seven forced `Initializer::loadUtilityPowers()` roots are shared through
`Compiler/Power/Selection::utilityPowers()`. The permitted-actions dependency
is deliberately separate: Joomla 4–6 helper templates emit its token, while
Joomla 3 emits it through the batch/list builders of selected admin views.
`Selection::lateUtilityPowers()` describes those routes without moving the
ordinary compiler's load to initialization. Batch/list builders share the
token helper, and the owning contract verifies the modern templates retain
that same token. These late roots honor normal Power enablement; forced
initializer roots retain their independent behavior.

The installed Hello World comparison exposed this distinction: the complete
compiler emitted 65 Powers, including one late permitted-actions Power, while
the first discovery probe found only 64. The graph now follows the generated
route, with regressions for Joomla 3–6, selected versus absent admin views,
disabled Power emission and first import. The installed comparison remains
the acceptance oracle; successful unit coverage alone does not prove parity.

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

Final adversarial review also varied the number of source vendors, independently
of unrelated database growth. With 64 reachable definitions and 32 source
classes, the old vendor-partitioned index grew from 64 to 2,048 entries as
vendors increased from one to 32. It now indexes 64 records in either case.
Durable tests cover literal namespaces, templates, hidden-name aliases, target
occupants and marker collisions after namespace normalization. Independent
before/after comparisons preserve embedded/repeated prefixes and configured
prefixes containing multiple namespace segments.

## Operation scope and repeated work

`Registry/Parsed` holds content-addressed lexical Power observations for one
operation. Source bytes are reread and hashed, so an edit invalidates parsing
even when filesystem timestamps are unchanged. Independent `Scope::reset()`
calls clear this cache. Each new harvest refreshes database evidence; parsing
can be reused while matching is reevaluated under current target, source,
binding and manual-decision inputs.

Assembler bindings are deduplicated and validated once per source unit.
Dependency lookup is memoized per effective source context, and blocked
dependency propagation uses reverse edges rather than repeated full passes.
Metadata from duplicate physical source copies participates in preflight;
contradictory source identities block the operation instead of being discarded.

The administrator's preliminary component harvest and target-aware preview
remain separate semantic passes. Power parsing happens in the preview for that
HTTP flow. The parse cache does not claim cross-request persistence or complete
reuse of every component/XML/schema reader.

## Safety contract

A correct selected-component match does not prove exclusive ownership.
Unavailable reverse-consumer evidence must remain incomplete, and effective
changed shared/foreign/unknown writes retain fingerprint-bound review. Genuine
no-op imports must not create auxiliary metadata or require an unnecessary
write. Literal namespace segments, custom aliases, manual pairing, source keys,
all competing GUIDs, skipped collision occupants and transaction preflight
remain protected.

Without a complete reverse-consumer index, a selected component's Power can be
identified correctly while its write scope remains unknown. Changed definitions
then need the existing fingerprint-bound acknowledgement. A true no-op needs
neither a new approval nor auxiliary writes. An explicit create decision remains
available for first import; it does not bypass observed destination collisions.
Unknown unlinked template aliases are not fabricated into proof of absence.

## Coverage and fixture limits

Discovery exposes missing, malformed and duplicate references and unsupported
effective-input routes. Unavailable normalized template-alias indexes, external
code and unobserved integrations are uncertainty, not proof of exclusive Power
ownership. The separately deferred local plugin is not an acceptance gate.

The historical golden-master default component is **Service Directory**, not
JCB. The public Hello World definition is separately identified in the new
comparison harness. The current authoritative JCB self-compilation blueprint
has not been identified in the repository or recovered handover. Installed JCB
source, synthetic A/B records and complete Hello World compilation must not be
reported as a full JCB self-compilation comparison.

## Verification status

The initial failing PR commit is `74826acd544162440244fd123db4d59dfbfe7070`.
Both PHP 8.3 and 8.4 reported five failures among 4,222 tests. The independent
CI cache correction is `7e88d2b855eb948bbd47fbd74b4b4ba52e26ea02`.
Implementation verification uses PHP 8.3, the locked dependencies and the pinned
Joomla 6.1.2 test runtime. Final-head CI and installed evidence are tracked on
PR #54; historical PR #53 results do not satisfy this acceptance boundary.

`.github/gui-tests/discovery-scale.php` measures actual installed CLI Power
harvest, repeated harvest and dry-run preview over emitted sources. It records
SQL and work counts, parsing, memory, timings and real equality-query plans
while adding 0/100/1,000 unrelated component/Power records in a rolled-back
transaction. These CLI measurements exclude HTTP and browser rendering. The
GUI workflow separately tests actual transport and persistence, including
denial before scope acknowledgement, preview/write equality and stale approval.

## Installed evidence, 29 September 2026

Head `b2d198551691f399a75dc6e9c56a0b1b33130b20` passed both PHP 8.3 and
8.4 unit jobs, including coverage, ownership, changed-file style, dependency
audit and runtime cleanup. The normal suite contains 4,276 tests. The separate
known-defect suite remains unchanged and reproduces its documented failures.

[Compiler run 36594044741](https://github.com/extension-builder/joomla/actions/runs/36594044741)
passed all four generated targets, Joomla 3/4/5/6, on host Joomla 6. It verifies
the effective compiler configuration and actual output manifests independently
of the requested target. Baseline and candidate generated trees and final
Super/Joomla Power sets are identical. Read-only discovery agrees with all 65
emitted Super Powers, including the late-generated Actions dependency.

The following historical installed Hello World measurements precede the final
vendor-sharing correction and are from that run's Joomla 6
artifact `11044799344` (PHP 8.4.26, host Joomla 6.1.3, MariaDB 13.0.2). The
actual Power input has 65 PHP files and 379,655 bytes across all 88 files.

| Unrelated components and Powers added | Cold harvest | Repeat harvest | Dry preview | Harvest SQL | Preview SQL |
| --- | ---: | ---: | ---: | ---: | ---: |
| 0 each | 0.320 s | 0.253 s | 0.444 s | 694 | 1,222 |
| 1,000 each | 0.329 s | 0.296 s | 0.446 s | 694 | 1,222 |

The intermediate 100-record sample also preserves the same identities and
counts. Every sample visits one component context, 82 graph records and 159
edges through 92 graph queries; identity work then stayed at 202 contextual index
entries and 65 candidate evaluations. Repeat harvest parses zero files and
reuses all 65 observations. GUID, name and namespace equality queries use their
respective indexes (`ref`, estimated one row). Harvest and preview perform no
database writes, and the catalogue-growth transaction is verified rolled back.
The dry preview consistently proposes nine changes requiring unknown-scope
acknowledgement; it does not import them.

These timings include the SQL debug monitor and JSON serialization. They do
not include HTTP/browser time; cold means empty operation-local caches, not
empty operating-system or database caches. They are measured samples, not a
latency guarantee or a JCB-scale result.

Final-head workflow links and the current acceptance checklist are maintained
on [PR #54](https://github.com/extension-builder/joomla/pull/54). Historical
measurements above must not be substituted for that final-head verification.

The disposable image's pre-fetch component inventory contains only Demo J6.
The recovered project archives contain repository source and synthetic probes,
not a JCB definition export; the available package catalogue contains Service
Directory J5/J6. Thus the real JCB self-compilation and Power-scale fixture is
still missing. The GUI's installed JCB MVC harvest is separate evidence and
does not supply that missing Power graph. W02/W08 and final merge readiness
remain open until the authoritative fixture is supplied and those gates run.
