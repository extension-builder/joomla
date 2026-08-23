# Vendor JCB library testing

The vendor-library test suite protects the public behavior, dependency-injection
wiring, state boundaries, and generated-code contracts of the reusable JCB
libraries. It is intentionally separate from the installable component so that
development dependencies never enter a production package.

## Supported runtime

The suite uses the Joomla CMS 6.1.2 source tree and PHPUnit 12.5. Joomla 6
requires PHP 8.3 or later; continuous integration runs the supported minimum,
PHP 8.3, and the recommended runtime, PHP 8.4. The committed Composer lock must
always be generated against the configured PHP 8.3 platform so both jobs
install the same dependency graph.

The test project is located at `libraries/vendor_jcb/tests`. Its Composer
autoload map loads production classes directly from their existing source
folders and loads the repository's bundled PhpSpreadsheet and phpseclib3
autoloaders. It does not copy or rewrite production classes.

Every committed Composer test script sets PHP's memory limit to `1G`. Keep
that limit when invoking PHPUnit directly: discovery of the complete compiler
service graph and coverage instrumentation can exceed a default CLI limit even
though focused tests are small. CI sets the same `1G` limit in PHP configuration
as well as on each Composer script, so local and hosted runs have the same
resource contract.

The bootstrap models an installed Joomla administrator request without starting
a Joomla application or database. Joomla root and platform constants point to
the pinned Joomla 6.1.2 source checkout in `.runtime/joomla-cms`;
`JPATH_LIBRARIES` and component administrator constants point to this
repository's deployable library and `admin` trees. The test Composer project
installs the Joomla Framework and CMS-runtime packages required by the imported
class graph, using constraints synchronized with Joomla 6.1.2. Its PSR-4 map
loads `Joomla\CMS` classes from the separate source checkout. The bootstrap
rejects a checkout whose reported Joomla version is not exactly 6.1.2.

The test project deliberately does not install or boot Joomla's application
plugins. This split preserves Joomla class discovery and JCB's bundled-library
paths without making an authentication or web application runtime part of a
unit test. Tests must inject the application, container, database, and HTTP
boundaries they exercise.

## Install and run locally

PHP 8.3 or 8.4, Composer 2.10.2 or a compatible Composer 2 release, Git, and the
PHP extensions listed in the workflow are required. Prepare the ignored Joomla
source runtime before installing the test dependencies:

```bash
cd libraries/vendor_jcb/tests
git clone --branch 6.1.2 --depth 1 https://github.com/joomla/joomla-cms.git .runtime/joomla-cms
composer install
composer test
```

Run one package suite while developing:

```bash
composer test:joomla
composer test:gitea
composer test:openai
composer test:github
composer test:minify
composer test:git
```

Run the explicitly documented existing contract defects separately:

```bash
composer test:known-defects
```

This command is expected to return a non-zero status until each named defect is
fixed in an authorized production-code change. The normal and coverage suites
exclude the `known-defect` group so the test-foundation branch remains
mergeable; CI still runs that group on PHP 8.3 as a visible, non-blocking step.
When a known-defect test starts passing, make it blocking immediately. Remove
its method-level group; if a class-level group covers other failing contracts,
move those annotations to the remaining methods or split the class. A passing
assertion must never remain hidden in the non-blocking lane.

## Graphical user interface suite

The browser-driven Playwright suite is a separate track from this PHPUnit
project: it lives in `libraries/vendor_jcb/tests/gui`, runs against a real
Joomla container carrying this working tree, and has its own authoritative
document — [gui-testing.md](gui-testing.md) — including the rule that every
GUI change ships GUI test coverage in the same change. Nothing in this
PHPUnit project's ownership ratchet tracks the GUI suite; the GUI change
record is where its coverage is declared.

### Known-defect debt ledger

The following entries correspond to every executable
`#[Group('known-defect')]` contract in the suite. Data-provider cases are
grouped only when they exercise the same production defect; each affected
family is named explicitly. This is a debt ledger, not a compatibility promise:
the protected contract is the desired behavior. Fix the production behavior in
an authorized change, retain the assertion, remove its group, and delete its
ledger entry in the same change.

#### Foundation, data, database, and utility contracts

| Subject | Protected contract | Observed production symptom |
| --- | --- | --- |
| `Abstraction/BaseTable::exist()` | Looking up an unknown table without a field is normal invalid input and returns `false`. | The absent field is passed as `null` to `isDefault(string)`, raising a `TypeError`. |
| `Abstraction/Registry/Traits/VarExport::varExport()` | Exporting an empty registry without selecting a path returns `null`. | The trait passes a `null` path to `Registry::get(string)`, raising a `TypeError`. |
| `Abstraction/Schema::significantTypeChange()` | Whitespace inside equivalent decimal precision declarations is insignificant. | The decimal-specific comparison runs before whitespace normalization, so `DECIMAL(10, 2)` and `decimal(10,2)` are reported as a schema change. |
| `Data/Items::set()` | An empty persistence batch is rejected as `false` without invoking an insert action. | The empty batch is reported as a successful write. |
| `Data/Migrator/Guid::process()` | A non-empty basic scalar is an integer ID or valid GUID; any other value raises the documented contextual migration exception. | Validation is entered only for numeric values, so an invalid non-numeric legacy value is silently ignored. |
| `Data/MultiSubform::set()` | A nested `_core` set-map uses the root set contract: `table`, `indexKey`, `linkKey`, and `linkValue`. | Nested core processing calls the get-map validator, which instead requires `field` and `get` and rejects the valid set-map. |
| `Database/Insert::{row,rows}()` | A failed database `execute()` is propagated to the caller as `false`. | Query construction is treated as success and the failed execution result is discarded. |
| `Database/Update::items()` | A batch in which every row update fails returns `false`. | Per-row results are discarded and every non-empty batch returns `true`. |
| `Utilities/DateHelper::fancyDateTime()` | Formatting uses the caller's normalized timestamp. | The formatter reads undefined `$time`, emits a warning, and formats the current date instead of the supplied timestamp. |
| `Utilities/GuidHelper::get(false)` | Untrimmed GUID generation surrounds the value with balanced braces. | The generated value ends with `{` instead of `}`. |
| `Utilities/String/ComponentCodeNameHelper::safe()` | A generated component code name is legal as a PHP namespace segment. | A leading numeric character is retained, for example `6component`. |
| `Utilities/String/NamespaceHelper::safe()` | Normalization removes empty namespace segments. | Repeated internal separators survive as repeated backslashes. |

#### Compiler contracts

| Subject | Protected contract | Observed production symptom |
| --- | --- | --- |
| `Compiler/Adminview/DefaultOrdering::get()` | A missing configured ordering field is skipped so a later valid field can be selected. | `DatabaseName::get()` returns `null`, but the caller checks only for `false` and returns a null field with the first direction. |
| `Compiler/Architecture/{JoomlaFour,JoomlaFive,JoomlaSix}/ComHelperClass/ExcelMethods::get()` | The generated `xls()` helper method assigns the identity `$user` variable it later reads for its modified-by default. | The legacy generator appended the Joomla 4+ identity assignment to an unused buffer, so the generated method reads `$user->name` without any `$user` assignment. |
| `Compiler/Architecture/JoomlaSix/CustomAdminViews/AddToolBar::get()` | The list-toolbar title uses the language key passed to `buildTitle()`. | `buildTitle()` accepts `$langView` but interpolates undefined `$langViews`, emitting a warning and generating an empty title key. |
| `Compiler/Architecture/JoomlaThree/Dashboard/View::get()` | The default dashboard is warning-free and retains its main accordion state. | Initial state stores `mainAccordianName` but rendering reads `mainAccordionName`, producing an undefined-key warning. |
| `Compiler/Builder/ContentOne::flatten()` | The single-content registry flattens placeholder keys using its constructor-selected no-separator mode. | Its `null` separator is passed to `Registry::flattenArray(string)`, raising a `TypeError`. |
| `Compiler/Component/Dashboard::set()` | The resolved dashboard name and target type coexist in registry state. | `build.dashboard` is first stored as a scalar, so the later `build.dashboard.type` child cannot be persisted and reads as `null`. |
| `Compiler/Creator/ConfigFieldsetsUikit::set()` | Generated UIkit option elements are well-formed XML without stray text quotes. | Each generated option is followed by an extra `"` after `</option>`. |
| `Compiler/Creator/Layout::set()` | Consecutive zero-order fields receive distinct negative positions. | The zero-order counter reuses `-999`, so both fields receive the same order instead of the second receiving `-998`. |
| `Compiler/Dynamicget/FilterColumn::get()` | A group filter renders its guard and removal strategy once per row key. | The renderer calls `Indent::_()` without importing it, resolving a nonexistent class in the Dynamicget namespace. |
| `Compiler/Dynamicget/QueryFilter::get()` | User-group array filters register their decode metadata through the shared array helper. | `ArrayHelper` is not imported, so the filter resolves a nonexistent class in the Dynamicget namespace. |
| `Compiler/Dynamicget/Selection::get()` | An unaliased source column uses its column name without runtime diagnostics. | The missing alias is represented as `null` and passed to `trim()`, causing a PHP 8.3 deprecation. |
| `Compiler/Field/Name::get()` | Capitalized field-type metadata still recognizes a category, produces `catid`, records its related-view metadata, and remains stable by hash. | Category type matching is case-sensitive, so a `Category` field keeps the ordinary `category` name and bypasses the category relationship path. |
| `Compiler/Field/Customcode::update()` | List-view scripts are stored under the supplied list-view registry key. | The list branch passes the nullable single-view name to the dispenser, producing a `null` key instead of `articles`. |
| `Compiler/Joomlamodule/JoomlaThree/Infusion::setDispatcherCode()` | Joomla 3 dispatcher infusion stores the generated dispatcher without diagnostics. | The method concatenates undefined local `$header`, emitting a warning before storing `MODCODE`. |
| `Compiler/Language::key()` | An already-normalized uppercase key returns the legacy `false` sentinel used by extractor call sites. | The declared `string` return type weakly coerces the explicit `false` return to an empty string. |
| `Compiler/Model/Sql::set()` | When a generated dump already exists, redundant `tables` and `sql` source properties are still consumed from the item. | The existing-dump guard returns before cleanup, leaving both raw properties on the model. |
| `Compiler/Power/Extractor::search()` | Stateful token discovery records each valid Super Power GUID for the forced-load pass. | The load path strips no token prefix or suffix before GUID validation, so every discovered token is discarded. |
| `Compiler/Utilities/ComplexityEngine::get()` | A zero-complexity project receives the configured low market multiplier. | A BCMath comparison result is cast to float and strictly compared with integer `-1`; the low branch is skipped and interpolation produces `-2.0` instead of `0.8`. |

#### Component-builder domain contracts

| Subject | Protected contract | Observed production symptom |
| --- | --- | --- |
| `Componentbuilder/FactoryTrait::getFactory()` | Calling the factory without selecting a primary entity raises the documented `InvalidArgumentException`. | `getEntity()` reads an uninitialized typed property first, so PHP raises `Error` before public validation runs. |
| `Componentbuilder/{JoomlaPower,Repository,Snippet,Fieldtype,Package}/Config::__construct()` | Every domain configuration constructor accepts Joomla's shared `Joomla\Input\Input`. | Each file omits the import, so `Input` resolves to a nonexistent class inside that domain namespace. |
| `Componentbuilder/Crypt/FOF::encrypt()` | Encryption produces non-empty ciphertext that the same adapter can decrypt. | `encrypt()` calls `decryptString()` instead of `encryptString()`, so ordinary plaintext does not produce round-trippable ciphertext. |
| `Componentbuilder/Data/Migrator/Guid` relationship map | Plugin-group links target `joomla_plugin_group`, and the Power method map uses the `method_selection` column. | Both class mappings point at `class_property`, and the Power method mapping repeats `property_selection`. |
| `Componentbuilder/File/Definition::__construct()` | Filesystem metadata derives filename, extension, size, path, and persistent identity from the supplied record. | Filename derivation reads undefined `$full_path` instead of `$file_path`, emitting a warning/deprecation and storing an empty filename. |
| `Componentbuilder/File/Image::info()` | A documented absolute image path is used as-is even when it is outside `JPATH_SITE`. | Path normalization treats every path not prefixed by `JPATH_SITE` as relative, prepends the Joomla root, and reports the real external image as missing. |
| `Componentbuilder/Import/Status::__construct()` | Constructor arguments initialize both the table and status-field selectors. | The field argument is assigned to undeclared `$field`; the typed `$fieldName` remains uninitialized and fails when read. |
| `Componentbuilder/Markdown/Html::convert()` (images) | Markdown image syntax renders an `<img>` element with source and alternative text. | Link conversion consumes the image's inner link before the image expression can match it. |
| `Componentbuilder/Markdown/Html::convert()` (blockquotes) | Consecutive quote lines render as one blockquote. | Early HTML escaping replaces `>` before blockquote recognition runs. |
| `Componentbuilder/Repository/Config::getRepositoryInitRepos()` | A configured user repository is present without requiring a prior magic-property read. | Initialization checks the uninitialized username cache before resolving `gitea_username`, omitting the user's repository. |
| `Componentbuilder/Search/Model/Insert::validateBefore()` | Whole-item modeling validates ordinary string fields and returns the modeled object. | `StringHelper` is not imported, so PHP resolves a nonexistent class in the model namespace. |
| `Componentbuilder/Table/Validator::getValid()` | Unknown field metadata is normal invalid input and returns `null`. | A metadata miss returns `null` through `getDatabaseField(): array`, raising a return-type `TypeError`. |
| `Componentbuilder/Utilities/Normalize::full()` | Reconstructed paths cannot escape the selected filesystem scope. | Parent segments are concatenated without containment validation, returning a path containing `../../outside-jcb-root`. |
| `Componentbuilder/Utilities/Permitted/Actions::safe()` | A non-empty plural view survives nullable normalization so category-scope ACL checks remain enabled. | The `allowNull` condition is inverted and converts a valid `articles` value to `null`. |

#### Console and package contracts

| Subject | Protected contract | Observed production symptom |
| --- | --- | --- |
| `Componentbuilder/Console/Compiler::normalizeCompilerOptions()` | Allowed value `0` is explicit input, distinct from an omitted/global option. | `empty()` removes string `"0"` from environment, bundle, and CLI sources, so the documented zero-valued choice becomes `null`. |
| `Componentbuilder/Abstraction/Console/Package::resolveItems()` | Highest-priority `--items` input suppresses lower-priority `--items-file` input. | Inline and file values are merged whenever both are present. |
| `Componentbuilder/Console/Package/{Init,Pull,Push,Reset}::doExecuteAction()` | Strict commands accept only GUID items unless their explicit resolution mode is enabled. | All four commands pass arbitrary non-GUID values to their builders and report success. |
| `Componentbuilder/Package/Builder/Get::reset()` | File and folder reset queues are drained within the current operation even when the reduced container lacks those capabilities. | Unsupported queue entries remain in the shared tracker and can leak into a later operation. |
| `Componentbuilder/Package/Builder/Get::get()` | Each public get operation returns only its own categorized results. | Builder result state is retained, so a second operation includes the first operation's items. |
| `Componentbuilder/Package/Builder/Set::items()` | The remote entity handler's failure status is returned to automation callers. | The handler returns `false`, but the builder drops it and returns `null`. |
| `Componentbuilder/Package/Dependency/Resolver` scalar normalization | Integer, float, and boolean relationship identifiers are normalized and preserved. | All non-string scalars are filtered out, so an integer parent ID produces no dependency. |
| `Componentbuilder/Package/Remote/Get/File` outcome cache | A failed batch initialization is still reported as failure by a later item lookup. | `init()` writes a true processed marker before path resolution, and `item()` misreads that marker as a successful cached outcome. |
| `Componentbuilder/Package/Remote/Get/Folder` restore | Invalid replacement archives leave the last known-good destination intact. | The destination is deleted before archive validation/extraction, so a failed update destroys the existing folder. |
| `Componentbuilder/Package/Remote/Set/DynamicGet` custom-source mapping | Custom-source mode keeps every query-builder field cleared. | Null reset values fail the parent mapper's `isset()` guard, allowing raw `filter` and `where` values to be repopulated. |

#### Gitea, GitHub, OpenAI, and provider-neutral Git contracts

| Subject | Protected contract | Observed production symptom |
| --- | --- | --- |
| `VDM.Joomla.Gitea/Service/Issue` deadline service | `Gitea.Issue.Deadline` and its class alias resolve a shared, fully wired `Issue\Deadline` endpoint. | The provider registers `getDeadline` but implements no such factory method, so the container resolves the callback array instead of a `Deadline` instance. |
| `VDM.Joomla.Gitea/Repository/Mirrors::add()` | Optional push-mirror credentials remain optional and no required parameter follows them. | Required `$interval` and `$syncOnCommit` follow the optional credential parameters, so PHP treats the credentials as required and deprecates the signature. |
| `VDM.Joomla.Gitea/Admin/Cron::run()`, `Repository/{Mirror,Mirrors}::sync()`, `Repository/Hooks::test()`, `Repository/Reviews::undismiss()`, and `Repository/Transfer::{accept,reject}()` | Every bodyless POST mutation supplies an explicit empty request body and maps its documented response. | All seven families call Joomla HTTP `post()` with only the URI, raising `ArgumentCountError` before a request is sent. |
| `VDM.Joomla.Gitea/User/Following::check()` | PSR response status `204` means followed and `404` means not followed. | The method reads nonexistent `$response->code` instead of `getStatusCode()`, emits a warning, and reports `204` as false. |
| `VDM.Joomla.Gitea/Utilities/Http::setToken()` and `VDM.Joomla.Github/Utilities/Http::setToken()` | Clearing authorization removes both the request header and the reported in-memory token identity. | Both implementations remove the header but leave the previous token cached, so `getToken()` reports stale credentials. |
| `VDM.Joomla.Gitea/Repository/Patch::applyDiffPatch()` | The supplied option array is JSON-encoded as the diff-patch body. | The method encodes undefined `$options` instead of `$option`, emits a warning, and sends `null`. |
| `VDM.Joomla.Gitea/Repository/Pulls::list()`, `Stargazers::list()`, `Wiki::revisions()`, `Commits::diff()`, `Releases::{list,getByTag}()`, and `Branch/Protection::delete()` | Endpoint query parameters are applied to the concrete request URI returned by `Uri::get()`. | Each family calls `setVar()` on the URI factory itself, which has no such method, so all seven fail before sending a request. |
| `VDM.Joomla.Openai/Utilities/Http::setOrgToken()` | Updating the organization token changes the effective `OpenAI-Organization` request header without diagnostics. | The method reads undefined `$defaultHeader`, so an otherwise effective mutation emits a runtime warning. |
| `VDM.Joomla.Openai/Utilities/Response::get_()` | A recognized empty response may intentionally map to a configured `null` default. | `isset()` treats the status's null map value as absent, so the response is rejected with `DomainException`. |
| `VDM.Joomla.Github/Repository/Wiki::get()` | API authorization is never forwarded to the separate `raw.githubusercontent.com` host. | The authenticated GitHub HTTP client is reused unchanged and leaks its `Authorization` header to the raw-content request. |
| `VDM.Joomla.Git/Repository/Contents::api()` | The provider-neutral facade returns the selected provider's API URL. | The provider call is delegated but its return value is dropped, so the facade returns `null`. |

Before committing a change to an eligible production class, verify that the
corresponding owned test is present. Pass a real base revision from the Git
repository:

```bash
php bin/check-test-ownership.php --base="$(git merge-base HEAD origin/6.x)"
```

Run this command from `libraries/vendor_jcb/tests`. The ownership check is
independent of Composer and Joomla bootstrap state.

Run the contribution-style guard over the same merge-base range:

```bash
php bin/check-php-style.php --base="$(git merge-base HEAD origin/6.x)"
```

Added, copied, and renamed in-scope PHP files are checked as complete files.
For a modified legacy production file, only added lines are style-gated so an
unrelated contribution is not forced to reformat historical code. Without a
base SHA, the command checks every first-party PHP file in the test project.
It deliberately preserves the upstream-derived formatting of production files
under `VDM.Minify/src`; JCB-owned Minify tests remain fully checked.

Generate a Clover report and a terminal coverage summary with PCOV or Xdebug
enabled:

```bash
composer test:coverage
```

The Clover report is written to
`libraries/vendor_jcb/tests/build/coverage/clover.xml`. Test dependencies,
PHPUnit caches, and generated coverage files are ignored by Git.

When dependencies are deliberately updated, use PHP 8.3 and commit the
resulting lock file:

```bash
cd libraries/vendor_jcb/tests
composer update
composer validate --strict
composer check-platform-reqs
composer test
```

Never use `composer update` as the normal CI or local test command. Normal runs
use `composer install` so they execute against the reviewed lock file.

## Source-to-test mapping

Tests mirror the production directory below the requested package test root.
The package namespace is preserved and `Tests` is inserted before the mirrored
class path.

| Production class | Test class | Test file |
| --- | --- | --- |
| `VDM\Joomla\Utilities\StringHelper` | `VDM\Joomla\Tests\Utilities\StringHelperTest` | `tests/VDM.Joomla/src/Utilities/StringHelperTest.php` |
| `VDM\Joomla\Gitea\Repository\Contents` | `VDM\Joomla\Gitea\Tests\Repository\ContentsTest` | `tests/VDM.Joomla.Gitea/src/Repository/ContentsTest.php` |
| `VDM\Joomla\Openai\Chat` | `VDM\Joomla\Openai\Tests\ChatTest` | `tests/VDM.Joomla.Openai/src/ChatTest.php` |
| `VDM\Joomla\Github\Repository\Tags` | `VDM\Joomla\Github\Tests\Repository\TagsTest` | `tests/VDM.Joomla.Github/src/Repository/TagsTest.php` |
| `VDM\Minify\Css` | `VDM\Minify\Tests\CssTest` | `tests/VDM.Minify/src/CssTest.php` |
| `VDM\Joomla\Git\Repository\Contents` | `VDM\Joomla\Git\Tests\Repository\ContentsTest` | `tests/VDM.Joomla.Git/src/Repository/ContentsTest.php` |

Exactly three legacy compiler helpers are excluded:
`Componentbuilder/Compiler/Helper/Fields.php`,
`Componentbuilder/Compiler/Helper/Infusion.php`, and
`Componentbuilder/Compiler/Helper/Interpretation.php`, all below
`VDM.Joomla/src`. They require characterization coverage as part of their
planned refactoring rather than superficial unit tests against their current
inheritance chain.

### Test-ownership ratchet

`coverage-baseline.php` is an explicit ledger of existing, untested production
debt. An entry in that file never means that the class is tested.
`test-ownership.php` records production paths that have a real test, the kind
of coverage supplied, and the existing owning `*Test.php` file. A production
path must be in exactly one ledger.

The completed foundation inventory contains exactly 1,129 eligible production
declarations: `coverage-baseline.php` has zero entries and
`test-ownership.php` owns all 1,129. `SourceInventory` enforces one named class,
interface, trait, or enum per discovered source file. The inventory excludes
only the three named legacy Compiler Helper files above. This is a ratchet
snapshot, not a fixed ceiling: adding or renaming an in-scope declaration must
change the discovered count and its owned count together, without reintroducing
baseline debt.

An ownership record is valid only when its owner is under one of the six
configured package suite roots, resolves through a static inheritance chain to
PHPUnit's `TestCase`, and exposes at least one public blocking test outside the
`known-defect` group. Classes and enums require an exact `CoversClass` target or
a containing `CoversNamespace`; traits require `CoversTrait` or a containing
namespace. Interfaces have no executable lines for PHPUnit to target, so an
interface in `contract` mode is owned by its exact ledger entry and structural
assertions without targeting the interface itself. Its owner must still carry
coverage metadata: use `CoversNothing` for a pure structural test, or retain the
concrete class, trait, or namespace target for an aggregate behavioral owner.
Never put `CoversClass` on an interface. Contract tests keep package suite roots,
coverage source roots, and the three source exclusions synchronized with
`phpunit.xml.dist`; a path that merely exists is not ownership evidence.

Choose the ownership mode by the contract being protected:

- `unit` exercises one subject's public behavior with external collaborators
  replaced at their interfaces;
- `contract` runs a shared behavioral, interface, trait, or coherent family
  contract against one or more implementations;
- `provider` protects DI aliases, keys, shared identity, injected dependencies,
  and host/target version selection;
- `characterization` records observable legacy behavior needed to refactor
  safely without claiming that the behavior is an ideal design; and
- `integration` records an in-process composition contract across several real
  library collaborators while network, database, Joomla-runtime, and
  persistent-filesystem boundaries remain mocked or recorded.

The ledger's `integration` mode is not permission to call external systems.
External integration tests still belong in a separately configured suite.

When adding the first real test for an existing production class, remove its
path from `coverage-baseline.php` and add its ownership entry in the same
change. New production classes cannot enter the baseline: the change that adds
the class must add meaningful test ownership immediately. The `Contracts`
suite validates the complete inventory and both ledgers on every run.

## Test contract

Every test must protect observable behavior or an architectural contract. A
test is not complete merely because it constructs a class, repeats the
implementation in its assertion, or checks that a method exists.

- Test successful behavior, boundary values, invalid input, and failure paths.
- Mock interfaces and external boundaries, not the class under test.
- Do not call live Gitea, GitHub, OpenAI, remote/shared/persistent filesystem,
  or database services. Isolated temporary-file behavior through
  `FilesystemTestCase` is allowed when the filesystem is the contract.
- Assert API request method, URI, query, body, headers, and response mapping at
  the mocked transport boundary.
- Use real lightweight value objects and collaborators where that produces a
  clearer contract than a mock.
- Do not use a final-class bypass tool. The library has many final classes;
  test them through their injected interfaces and public behavior.
- Verify service-provider aliases, shared lifecycles, constructor wiring, and
  Joomla-version selection without resolving side-effectful compiler services.
- Use explicit expected generated strings or reviewed golden fixtures for
  compiler output. Never regenerate a fixture merely to make a failure pass.
- Tests must be order-independent. PHPUnit randomizes execution to expose
  leaked static state.

PHPUnit coverage metadata is mandatory. Put `#[CoversClass(Target::class)]` on
behavioral class tests and `#[CoversTrait(Target::class)]` on trait tests. When
important collaborators are declared, use the type-correct `#[UsesClass(...)]`
or `#[UsesTrait(...)]` form. Pure structural interface contracts use
`#[CoversNothing]`: interface ownership is enforced by `test-ownership.php` and
`SourceInventory`, because PHPUnit cannot collect executable coverage from an
interface declaration. An aggregate test that owns both an interface contract
and concrete behavior keeps only its concrete `CoversClass`, `CoversTrait`, or
valid namespace target.

Coverage reports are telemetry, not the ownership authority. PHPUnit therefore
does not enforce an exhaustive `Uses*` list for every incidental collaborator;
the inventory validator remains the exact declaration-to-owner gate. This does
not relax runtime diagnostics: invalid metadata targets and warnings, notices,
deprecations, risky tests, and failures still fail their blocking jobs. Data
providers use the PHPUnit 12 `#[DataProvider('methodName')]` attribute;
annotation metadata is not supported. Indirect third-party deprecations are
ignored.

### Compiler architecture renderer contracts

The compiler Architecture suite owns every shared renderer and every concrete
Joomla 3, 4, 5, and 6 implementation below
`VDM.Joomla/src/Componentbuilder/Compiler/Architecture`. Keep the four target
implementations in the same data-driven contract whenever they represent one
logical renderer family. A target-specific assertion belongs beside the shared
assertions, so a selector or generated-code change cannot silently flatten a
real Joomla-version difference.

`ArchitectureTestCase` supplies one deterministic compiler Config,
Placeholder, Language, and Permission graph per test. It also snapshots and
restores initialized process-static `Indent`, `Line`, and `StringHelper` state.
PHP cannot return a typed static property to an uninitialized state, so the
first formatting fixture establishes tab indentation as the suite's canonical
`Indent` sentinel; every formatting fixture installs that value before use.
Reuse that base for Architecture output tests; do not boot `Compiler\Factory`
or an installed Joomla application to render a source fragment.

Architecture tests must protect the emitted contract, not merely successful
construction. Assert reviewed code fragments or complete strings, including
power placeholders, indentation, blank lines, terminal newlines, manifest XML,
filesystem destinations, builder mutations, language registrations, and
by-reference state such as checkout activation. Exercise both the common path
and the branch that distinguishes Joomla targets. If an existing renderer
cannot satisfy an unambiguous intended contract, keep the desired assertion in
the `known-defect` group and add its production symptom to the defect ledger
above; never encode the broken output as the desired result.

### Repository-discovery contracts

The Componentbuilder repository-discovery suite covers the complete Search,
Network, Power, JoomlaPower, Remote, Repository, Snippet, SnippetType, and
Markdown domain outside the compiler and package engines. Keep collaborating
classes in behavioral contracts that follow the public workflow: enqueue and
drain remote work, preserve search and replacement state, map repository
indexes, resolve network candidates, transform stored values, and render
reviewed README or generated-code output.

External repositories remain a hard unit-test boundary. Record HTTP and Git
collaborator calls, return deterministic fixtures, and assert the selected
repository, organization, branch, path, mapping, failure, and fallback. Never
make a live network request. Factory tests must isolate each static container
and protect catalog lookup, shared identity, and cross-domain isolation rather
than merely asking the factory for an object. Configuration contracts must
exercise magic-property caching in realistic access orders because repository
lists are built lazily. README, Markdown, parser, and generator assertions use
reviewed exact fragments and ordering; broken but unambiguous intended output
belongs in the documented `known-defect` lane.

### Data, database, and import contracts

Data-layer tests follow the public pipeline from caller input through modeling,
query construction, persistence, and affected-ID retrieval. Mock the
`Joomla\Database\DatabaseInterface` and `QueryInterface` boundaries; assert the
resolved component table, selected columns, joins, conditions, ordering,
limits, quoted values, result loader, and returned shape. Exercise identifier
reset semantics and multi-query batch boundaries. Never replace a failed
`execute()` result with a successful fixture merely to keep a test green; put
the desired failure propagation in the known-defect lane when the existing
implementation drops it.

Subform tests use the data interfaces and protect projection, single-versus-
multiple row shapes, generated identifiers, parent-link injection, stale-row
purging, nested link resolution, and aggregate failure status. User subforms
keep Joomla account creation at a mocked or isolated boundary; unit tests must
not write real users. Power-backed data items use a test-owned entity container
and assert the bounded local-miss/remote-fetch/local-retry sequence, including
the rule that a failed remote lookup is attempted only once per entity, key,
and value.

Import tests treat `Row`, `Mapper`, `ParentTable`, and `JoinTables` as a stateful
flow. Protect row initialization and clearing, mapper reset between imports,
parent-versus-join grouping, required link validation, existing-record update
selection, new-record defaults, GUID/ID validation, and created/modified user
attribution. Use deterministic row, registry, item, and load doubles; no unit
test may connect to a database or mutate an installed Joomla import queue.

### Console command contracts

Console tests bind `ArrayInput` to the command's real definition before testing
protected parsing helpers, and use the public `execute()` lifecycle for status,
exception, action-wiring, and rendered-output contracts. Cover every declared
CLI, file, and environment source; their documented precedence; normalization;
invalid input; and the distinction between an omitted option and an explicit
zero-valued option. Environment variables must be changed through the shared
test case so random test order cannot leak process state.

Package commands receive test-owned Get/Set builders and a `MessageBus`; they do
not resolve a live entity container or repository. Assert the exact entity,
items, repository, force/resolve flags, categorized results, builder call, and
message-bus lifecycle. When exercising lazy factory behavior itself, install an
explicit test container and isolate the corresponding Package factory for the
whole test. File inputs belong below a `FilesystemTestCase` temporary root.

Compiler command tests use a real lightweight Joomla `Input` object, a mocked
CMS console application, and separate machine-output and human-error streams.
The compiler factory may contain only the exact test services needed for a
dispatch or path-collection contract, and must be reset afterward. Unit tests
must not run the real compiler, installer, database, or presentation delays;
test missing-artifact installation and compiler failures at those boundaries.

`ConsoleSleepFixture.php` installs a process-wide function shim for the
Componentbuilder Console namespace so command tests never perform real delays.
If another timing contract is added in that namespace, isolate it in a separate
process or make both tests share one explicit clock/sleeper boundary; do not
rely on test order around the shim.

## Shared test support

Use the narrowest support base that meets the test's needs:

- `VDM\Tests\Support\TestCase` restores environment-variable changes made with
  `setEnvironmentVariable()`, the process timezone, and the exact working
  directory captured before each test.
- `FactoryTestCase` adds `isolateFactory()` for VDM factories whose shared
  containers would otherwise leak between tests, then restores the exact prior
  container identity or initialized-null state.
- `JoomlaTestCase` starts from Joomla Factory defaults and restores all Joomla
  Factory static properties after each test. It provides helpers for a mocked
  application and DI container.
- `FilesystemTestCase` adds a unique temporary root, path traversal protection,
  file/directory helpers, and recursive cleanup that does not follow symlinks.

The bootstrap assigns `JPATH_CACHE` to a unique directory for each PHP process;
it never points Joomla cache behavior at the shared system temporary root. The
owned cache tree is recursively removed at process shutdown without following
symbolic links. Cleanup is appended during shutdown so later Joomla language
cache writers cannot recreate the directory after it has been removed.

Static factories other than the compiler do not currently expose a public
reset contract. The reflection used by `FactoryTestCase` is therefore confined
to test infrastructure. Production code must not copy that technique.

Tests that cannot safely reset global state may use PHPUnit's
`#[RunInSeparateProcess]` method attribute or the class-level
`#[RunTestsInSeparateProcesses]`, together with
`#[PreserveGlobalState(false)]`. This is a last resort, not the default.
Parallel test execution must not be enabled until factory, Joomla-global,
filesystem, and environment isolation have been demonstrated across the
complete suite.

## Unit and integration boundaries

The default workflow is a unit suite. Tests that require a database, installed
Joomla application, network, or persistent filesystem are integration tests
and must be placed in a separately configured suite before being enabled. Do
not hide an integration dependency inside a unit test or silently skip it in
CI.

Coverage is reported on PHP 8.4. Complete declaration ownership does not imply
complete line or branch coverage, so no global percentage gate is imposed yet.
A premature aggregate threshold across these heterogeneous libraries encourages
low-value tests. Record reviewed per-package coverage baselines and ratchet each
one upward without reducing existing coverage.

## Continuous integration

`.github/workflows/vendor-jcb-tests.yml` runs for every pull request, for
pushes and merges into `6.x`, and when manually dispatched. It does not use
path filters, so every proposed change is checked, including stacked branches
whose immediate target is not `6.x`. Pull requests run with read-only
repository permissions; the workflow deliberately does not use
`pull_request_target`, because test code from a pull request must never execute
with privileged credentials.

CI checks out the exact Joomla CMS 6.1.2 tag into the ignored test-runtime
directory before Composer installation. Changing that tag is a reviewed
compatibility decision: synchronize the required Joomla runtime package
constraints, regenerate the lock file, update the bootstrap version guard, and
update both CI and local setup instructions together.

Third-party workflow actions are pinned to reviewed commit SHAs, and Composer
is pinned by version and SHA-256. Update those pins deliberately from their
official repositories; do not replace them with a floating branch while fixing
an unrelated test.

The PHP 8.3 job runs the complete suite without a coverage driver. The PHP 8.4
job runs the same suite under PCOV and uploads the Clover report for fourteen
days. Before installing Composer dependencies, both jobs run the changed-file
PHP style guard, compare changed eligible production classes with their owned
tests, and run Git's whitespace error check over the same change range. Pull
requests use the pull request base SHA; pushes use the event's pre-push SHA.
Composer then validates the test project, installs the reviewed lock, and
checks actual platform requirements before auditing the complete locked
dependency graph and starting PHPUnit. PHP 8.3 also runs the known-defect group
with `continue-on-error` and writes its expected failure to the workflow
summary; if that group becomes entirely green, the reporting step fails so the
recovered contracts must be promoted into the blocking suite. This visibility
must not be interpreted as permission to add new known defects. A final
always-run hygiene step also rejects any per-process Joomla cache root left by
the normal, coverage, or known-defect run.
