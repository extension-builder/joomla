# PHP code standard for JCB vendor libraries

## Purpose

This document is the authoritative PHP style standard for first-party code in
`libraries/vendor_jcb`. It applies to production classes, service providers,
factories, test support code, and PHPUnit tests unless a narrower documented
exception applies.

The standard protects more than formatting. In JCB, service names, registry
paths, generated placeholders, Joomla-version selectors, event order, and
mutable container lifecycles are part of the code contract. A contribution is
not conforming merely because it passes a formatter.

The architecture guides remain authoritative for placement and behavior:

- [system map](../architecture/system-map.md);
- [compiler architecture](../architecture/compiler.md);
- [Package distribution](../architecture/package-distribution.md);
- [architecture review findings](../architecture/review-findings.md);
- [helper refactoring](../architecture/helper-refactoring.md); and
- [testing strategy](../architecture/testing-strategy.md).

The executable PHPUnit placement, ownership, isolation, and CI rules are in the
[vendor-library testing standard](testing.md).

## Scope

The first-party rules apply directly to:

- `VDM.Joomla/src`;
- `VDM.Joomla.Gitea/src`;
- `VDM.Joomla.Openai/src`;
- `VDM.Joomla.Github/src`;
- `VDM.Joomla.Git/src`; and
- all project-owned PHP under `libraries/vendor_jcb/tests`.

`VDM.Minify/src` is retained upstream-derived code and has a narrowly defined
[preservation exception](#vdmminify-upstream-preservation-exception). New JCB
tests for Minify still follow the first-party standard.

Generated extension source, templates, and embedded target-language strings
also have output contracts. Their internal whitespace and symbols must remain
exact when a change does not explicitly alter generated output.

## Authority and evidence

The rules were derived from the repository's explicit configuration and an
audit of the requested source roots.

| Evidence | Finding |
| --- | --- |
| [`.editorconfig`](../../.editorconfig) | Tabs are the PHP/default indentation; YAML has its required two-space override. LF, UTF-8, trimmed trailing whitespace, and a final newline are mandatory. |
| `VDM.Joomla/src` | 983 PHP files; the executable first-party style is tab-indented and uses Allman braces. |
| `VDM.Joomla.Gitea/src` | 114 PHP files; tab-indented first-party API and provider code. |
| `VDM.Joomla.Openai/src` | 17 PHP files; tab-indented first-party API and provider code. |
| `VDM.Joomla.Github/src` | 9 PHP files; tab-indented first-party API and provider code. |
| `VDM.Joomla.Git/src` | 1 PHP file; a tab-indented provider-neutral facade. |
| `VDM.Minify/src` | 8 upstream-derived PHP files with upstream formatting; preserved as an explicit exception. |

Across those 1,132 PHP files, no file declares `strict_types`, no file closes
with `?>`, and first-party classes use explicit properties rather than
constructor property promotion. The modern first-party source strongly favors
typed properties, parameters, and return values where the public contract is
known.

Existing source is evidence, but not every historical line is a rule. The
following recurring inconsistencies are not canonical:

- mixed CRLF/LF bytes that contradict `.editorconfig`;
- same-line method or `try`/`catch` braces;
- missing spaces after `if`, `foreach`, or `catch`;
- old `array(...)` syntax in first-party code;
- malformed tags such as `@input`, `@returns`, or `token|null`;
- `**/` as a docblock terminator;
- trailing whitespace and inconsistent blank lines;
- undocumented loose comparisons; and
- missing legacy return types that remain only for compatibility.

Do not broaden a focused change into a formatting sweep. Apply this standard
to new or materially changed first-party code, and isolate any intentional
normalization of existing files in a separate, reviewable change.

## Files and declarations

### PHP file structure

A first-party PHP file must use this order:

1. `<?php`;
2. the standard JCB file header;
3. namespace declaration;
4. imports;
5. the declaration docblock; and
6. one primary class, interface, trait, or exception declaration.

Do not add a closing PHP tag.

Use the established first-party header and update metadata truthfully:

```php
<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    4th September, 2022
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
```

Do not copy an inaccurate `@created` value into a hand-authored file. Use the
value supplied by the owning generator or the actual creation date agreed for
the contribution.

### Strict types

Do not introduce `declare(strict_types=1);` into an isolated vendor-library
file. None of the audited package files currently declares it, and adding it
piecemeal changes caller coercion behavior. Strict types require a separate,
repository-wide compatibility decision and test matrix.

This rule does not permit vague types. Use native types wherever the current
contract supports them.

### Namespaces and paths

Namespaces mirror source paths. Preserve the case of established namespace
segments, including public spellings such as `Componentbuilder`, `Openai`, and
`Github`; changing them is an autoloading and API change, not a style fix.

Use the existing vertical separation in these libraries:

- two blank lines between the namespace and the first import; and
- two blank lines between the final import and the declaration docblock.

When a file has no imports, retain the same clear separation between the
namespace and declaration docblock.

### Imports

Use one import per line. Put Joomla and other external dependencies before VDM
project dependencies. Within the VDM group, keep related contracts and
implementations together when that order explains the composition.

Use an alias when it removes a collision or makes an injected role clearer:

```php
use VDM\Joomla\Interfaces\Data\LoadInterface as Load;
use VDM\Joomla\Interfaces\Data\InsertInterface as Insert;
use VDM\Joomla\Gitea\Repository\Contents as Gitea;
use VDM\Joomla\Github\Repository\Contents as Github;
```

Do not use grouped imports. Remove unused imports. Do not mechanically
alphabetize an import block when its current provider or version grouping
communicates architecture, but keep the chosen grouping deterministic.

## Whitespace and layout

### Tabs are mandatory

Use one horizontal tab for each indentation level in all first-party PHP.
Spaces may align columns inside a line or docblock, but must never replace a
leading indentation tab.

This is a PHP rule, not a license to produce invalid non-PHP files. YAML uses
the two-space override in `.editorconfig` because the YAML grammar forbids tabs
as indentation. Follow the native grammar for JSON, XML, Markdown, and other
formats while retaining the repository's line-ending and trailing-whitespace
rules.

This requirement includes:

- production classes;
- PHPUnit tests;
- test doubles and fixtures;
- Composer-facing PHP bootstrap files;
- workflow helper PHP; and
- new JCB-owned adapters around upstream libraries.

Embedded JavaScript, CSS, XML, SQL, generated PHP, heredoc, or nowdoc content
may retain the whitespace required by the emitted target. The surrounding PHP
control structure still uses tabs. Never reindent a generated string merely
to make the host file appear uniform when the string is a golden-output
contract.

### Line endings and trailing whitespace

Use LF line endings and UTF-8. End every file with exactly one newline. Remove
trailing spaces and tabs, including on otherwise blank lines.

Mixed CRLF/LF in existing generated library files is historical output and is
not the standard. Avoid an unrelated whole-file line-ending diff while making
a focused behavior change; correct generation or normalization separately.

### Braces

Use Allman braces for namespaces with blocks, classes, methods, closures, and
all control structures. The opening brace is on the next line at the same
indentation level as its declaration.

```php
if ($value !== null)
{
	$this->registry->set($path, $value);
}
else
{
	$this->registry->remove($path);
}
```

Always use braces, even for a single statement. Do not use same-line braces
such as `try {`, `catch (...) {`, or `): object {` in new first-party code.

### Spacing

Use:

- one space after control keywords: `if (`, `foreach (`, `while (`,
  `switch (`, and `catch (`;
- one space around assignment, comparison, boolean, concatenation, and
  arithmetic operators;
- one space after a comma;
- no space between a function or method name and `(`; and
- no spaces immediately inside parentheses or square brackets.

Prefer strict comparisons (`===` and `!==`) and explicit null comparisons.
Use a loose comparison only when coercion is part of the documented contract
and covered by tests.

### Blank lines

Use blank lines to separate semantic steps, not every statement. Separate:

- validation from main work;
- independent mutation phases;
- setup from a returned result;
- branches whose effects are easier to review in blocks; and
- class members and methods.

Do not add padding inside an intentionally empty Builder class or exception.

### Line length and wrapping

The audited source does not establish a reliable numeric line limit. Prefer a
line that can be reviewed without horizontal scrolling, and wrap long
signatures, conditions, calls, and strings without changing their semantics.

For long method signatures, place each continuation parameter on its own
tab-indented line, keep `)` and the return type together, and put the body
brace on the following line:

```php
public function create(
	string $owner,
	string $repository,
	string $path,
	array $content
): ?object
{
	// ...
}
```

For multiline calls, put the closing `);` on its own line:

```php
$result = $this->response->get(
	$this->http->post(
		$this->uri->get($path),
		json_encode($data)
	)
);
```

Indent each continuation of a fluent chain by one tab:

```php
$container->alias(ExampleInterface::class, 'Example.Service')
	->share('Example.Service', [$this, 'getExample'], true);
```

## Naming

### Declarations

Use PascalCase for classes, interfaces, traits, and exceptions. Use the
following suffixes:

- `Interface` for a contract;
- `Trait` for a reusable trait;
- `Exception` for a domain exception;
- `Factory` for a composition root or established entity router; and
- `Test` for a PHPUnit test class.

Preserve legacy public names such as `Registryinterface`, `load_()`, and
`reset_()` until an explicit compatibility change migrates every caller.
Their existence is not a template for new names.

### Methods, properties, and variables

Use lowerCamelCase for new PHP methods, properties, parameters, and local
variables. Use descriptive nouns for state and verbs for operations.

Keep external or contract-bearing names exact when they represent:

- database columns;
- JSON payload fields;
- repository document keys;
- Builder paths;
- placeholder identifiers;
- Joomla event names; or
- generated source symbols.

Those values may legitimately use `snake_case`, dotted paths, pipes, GUIDs,
or an established legacy spelling. Do not recase them in the name of style.

Use uppercase snake case for constants. Spell acronyms consistently in prose
(`API`, `GUID`, `URL`, `OpenAI`, and `GitHub`) while preserving established
class and namespace names.

### Boolean names

Name new boolean methods and local flags as questions or clear switches, for
example `isLocal()`, `hasHandler()`, `$force`, or `$backup`. Avoid ambiguous
names such as `$check` when the checked condition is not evident.

## Types and declarations

### Native types

Type every new property, parameter, and return value when PHP can express the
contract accurately. Use nullable and union types only when each alternative
is a real state that callers must handle.

```php
protected ?string $target = null;

public function metadata(
	string $owner,
	string $repository,
	string $path,
	?string $reference = null
): null|array|object
{
	// ...
}
```

Do not add an inaccurate native type to satisfy a style rule. For complex
arrays or callable shapes, combine the broad native type with a precise
docblock.

Do not narrow, widen, or reorder an existing public signature as part of a
test-scaffolding or formatting change. Signature modernization is a behavior
change and requires callers, interfaces, implementations, and tests to move
together.

### Properties and constructor injection

Declare injected dependencies as explicit typed properties with docblocks and
assign them in the constructor. Constructor property promotion is not the
first-party JCB vendor-library style and hides the property-level documentation
used throughout the architecture.

```php
/**
 * The Config Class.
 *
 * @var   Config
 * @since 6.1.6
 */
protected Config $config;

/**
 * Constructor.
 *
 * @param   Config  $config  The Config Class.
 *
 * @since   6.1.6
 */
public function __construct(Config $config)
{
	$this->config = $config;
}
```

Do not introduce `readonly`, promoted properties, or enums casually into a
single class. They are absent from the audited baseline and require a deliberate
compatibility and generation decision.

### Member order

Use this class-member order unless an implemented interface requires a more
useful grouping:

1. trait uses;
2. constants;
3. injected dependency properties;
4. configuration and mutable state properties;
5. constructor;
6. public API;
7. protected extension points and helpers; and
8. private implementation helpers.

Always declare visibility. Keep related methods together and preserve an
interface's logical order when it improves comparison between variants.

## Classes, interfaces, traits, and inheritance

### Final classes

Use `final` when the class is a closed leaf implementation, immutable-style
definition, focused Builder registry, or facade that does not advertise
inheritance. Examples include:

- `VDM\Joomla\File\Definition`;
- `VDM\Joomla\Data\Item`;
- focused compiler Builder classes; and
- `VDM\Joomla\Git\Repository\Contents`.

Do not make every class final automatically. Provider classes and deliberate
extension points must retain the inheritance behavior required by their
contracts.

### Abstract classes

Use an abstract class only when implementations share state or executable
template behavior. Put broadly reusable abstractions high in the namespace
tree, and keep domain-specific abstractions near their objective.

An abstract base must document:

- the invariant it owns;
- hooks subclasses must implement;
- mutable state shared with subclasses; and
- observable exceptions or side effects.

### Interfaces

Use an interface for a genuinely substitutable boundary. Name it with the
`Interface` suffix and keep implementation signatures compatible. Consumers
should depend on the stable interface or logical service alias when multiple
Joomla versions or providers implement one operation.

Do not create a one-use interface merely to increase abstraction count. The
contract must support substitution, test isolation, or a stable architectural
boundary.

### Traits

Use a trait only for one cohesive behavior that requires no hidden, ambiguous
state. Document any properties or methods the consuming class must provide.
Trait tests must exercise the behavior through a purpose-built consuming
class, not reflection alone.

### Visibility

Use the narrowest visibility that satisfies the contract:

- `public` for the supported API;
- `protected` for deliberate subclass extension; and
- `private` for implementation details that subclasses must not couple to.

Never expose mutable state publicly only to make a test possible. Test through
observable behavior or extract a meaningful collaborator.

## Documentation

### General requirements

Every named first-party class, interface, trait, reusable test fixture,
property, constant with non-obvious meaning, constructor, and method must have
a meaningful docblock. Native types do not replace contract documentation.

A test-local anonymous class may rely on the owning test method's docblock for
its role because it has no named API surface to document. Its properties and
named methods still require their own contract docblocks. This narrow test
fixture exception does not apply to a reusable fixture or production class.

Docblocks must describe observable purpose, not repeat syntax. Use complete
sentences and the actual project or product name. Simple Markdown such as
backticks and lists is acceptable in extended descriptions.

Use `/**` and close with `*/`. Never use the historical `**/` variant.

### Class docblocks

Use a concise role statement, extended behavior when helpful, and a truthful
`@since`:

```php
/**
 * Git Repository Contents Facade.
 *
 * Delegates the common repository-contents contract to the selected Gitea or
 * GitHub adapter.
 *
 * @since  6.1.6
 */
final class Contents implements ContentsInterface
```

### Property docblocks

```php
/**
 * The dependency tracker.
 *
 * @var   Tracker
 * @since 6.1.6
 */
protected Tracker $tracker;
```

Even when the native type is present, retain `@var` so generated API
documentation and adjacent classes remain consistent.

### Method docblocks

Use tags in this order:

1. `@param` entries in signature order;
2. `@return`;
3. `@throws`;
4. `@since`, including additional change-specific `@since` entries;
5. `@deprecated`; and
6. `@removal` when the project has a removal horizon.

Align type, variable, and description columns with spaces inside the
docblock:

```php
/**
 * Select the repository provider.
 *
 * @param   string  $system  The provider name.
 *
 * @return  self
 *
 * @throws  \DomainException  If the provider is unsupported.
 *
 * @since   6.1.6
 */
public function setTarget(string $system): self
```

Use `@return  void` for a void method. Document by-reference parameters and
mutations explicitly.

### Array shapes and generics

Use PHPDoc shapes and generics when a broad `array` type would hide the
contract:

```php
/**
 * @return array{
 *     local: array<string, string>,
 *     not_found: array<string, string>,
 *     added: array<string, string>
 * }
 */
```

The shape must reflect runtime keys and value types exactly. Do not document a
list when keys are semantic GUIDs, and do not omit a nullable state that is
actually returned.

### Since tags

Every new first-party declaration and member needs the version in which it is
introduced. Do not copy a nearby historical version. When a later release
changes the contract materially, retain the original `@since` and add another
line with a concise explanation.

### Deprecation

A deprecation must identify:

- the version that deprecated the API;
- the exact supported replacement;
- any important migration difference; and
- the planned removal series when known.

```php
 * @deprecated 5.1.4 Use $this->definition(...).
 * @removal    x.2 (4.2, 5.2, or 6.2 for the matching JCB series)
```

Deprecated code remains part of the runtime contract and must stay tested
until it is removed.

### Comments

Use comments to explain why an operation exists, an invariant, a non-obvious
side effect, or a generated-output requirement. Do not narrate obvious code.

Good comments identify matters such as:

- why a queue is removed before it is processed;
- why Config is temporarily changed and restored;
- why an event must run before a file mutation;
- why a version selector uses the host instead of the compile target; or
- why whitespace in a generated fragment is exact.

Avoid new TODO comments as a substitute for complete behavior. Track deferred
work in an issue or explicit roadmap with enough context to implement it.

## Arrays, strings, and expressions

### Arrays

Use short array syntax in new first-party code:

```php
$results = [
	'local' => [],
	'not_found' => [],
	'added' => [],
];
```

Use one entry per line for multiline arrays. A trailing comma is encouraged in
new multiline arrays and argument lists because it produces safer diffs; omit
it from single-line arrays. Do not add trailing commas throughout an unrelated
legacy file merely for consistency.

Align `=>` only within a small local block when it materially improves reading.
Never reorder an array whose order affects generated output, event execution,
provider registration, dependency traversal, or serialization.

### Strings

Use single quotes when interpolation or special escaping is not needed. Use
double quotes or interpolation when they make a dynamic value clearer.

Do not alter quote style in:

- emitted PHP, XML, JavaScript, or SQL;
- placeholder tokens;
- translation keys;
- service aliases;
- JSON property names; or
- fixture/golden strings

unless the resulting output is intentionally changed and tested.

### Conditions

Keep complex conditions readable. Extract a named boolean or private method
when the condition represents a domain decision. Short-circuit ordering may be
observable when later terms call services; preserve and test it.

Assignments inside conditions are allowed only when they keep a single lookup
and its check together without obscuring behavior:

```php
if (($area = Factory::getArea($entity)) === null)
{
	return [];
}
```

Do not nest ternaries. Use an `if` block or an explicitly grouped expression.

## Exceptions and error handling

Throw the narrowest useful exception type and include actionable context in
its message. Built-in exceptions may be fully qualified, as established in
the libraries, or imported consistently within the file.

Document every observable exception with `@throws`. Do not catch an exception
only to suppress it. Catch when the method can:

- recover safely;
- translate to a domain exception;
- add required diagnostics before rethrowing; or
- implement a documented best-effort boundary.

Use Allman braces for `try` and `catch`:

```php
try
{
	$repository = $this->resolver->resolve($target);
}
catch (RepositoryException $error)
{
	$this->messages->add('error', $error->getMessage());
	throw $error;
}
```

Do not use error suppression in new first-party code. If an inherited upstream
implementation uses it, contain and test its observable failure behavior
rather than spreading the pattern.

## Dependency injection and service providers

### Constructor injection

New behavior classes receive dependencies through typed constructors. The
closest domain service provider constructs them. Do not resolve collaborators
from a static Factory inside new behavior code.

Static factories are composition entry points and legacy bridges. They are not
service locators for arbitrary method bodies.

### Provider registration

Follow the existing provider contract:

1. alias the concrete class or stable interface to the logical key;
2. register the logical key as shared with `share(..., true)` unless the
   lifecycle is intentionally transient;
3. use a named provider factory method;
4. type the factory method's return; and
5. resolve constructor dependencies through stable aliases.

```php
/**
 * Registers the service provider with a DI container.
 *
 * @param   Container  $container  The DI container.
 *
 * @return  void
 *
 * @since   6.1.6
 */
public function register(Container $container)
{
	$container->alias(ExampleInterface::class, 'Example.Service')
		->share('Example.Service', [$this, 'getExample'], true);
}

/**
 * Get the Example Class.
 *
 * @param   Container  $container  The DI container.
 *
 * @return  Example
 *
 * @since   6.1.6
 */
public function getExample(Container $container): Example
{
	return new Example(
		$container->get('Config'),
		$container->get('Example.Registry')
	);
}
```

The audited providers consistently implement `register(Container $container)`
without a native `: void`; preserve that signature while its Joomla interface
contract remains the repository baseline. The docblock still states `void`.

### Shared state

Shared services return the same object for one container lifecycle. This is a
behavioral requirement for Config, focused Builder registries, dependency
trackers, message buses, and orchestration state.

The compiler container is process-static until
`Componentbuilder\Compiler\Factory::unset()` is called. Package's static
factory has no equivalent reset API. Code and tests must not assume those two
lifecycles are interchangeable.

### Factory roles

Use the correct factory role:

- a bounded-context factory composes a Joomla DI container;
- `Componentbuilder\Factory` routes a canonical entity to its owning area and
  factory; and
- Power/JoomlaPower factories resolve their own catalogs.

Do not add a parallel entity map, arbitrary user-driven container lookup, or a
new global service locator.

## Builder registries

Compiler Builder classes replace historical arrays with focused shared
registries. A Builder is not boilerplate and must not be merged into a generic
global state bag.

When new compilation state is produced now and consumed later:

1. identify the semantic dataset;
2. reuse an existing Builder when its path contract matches;
3. otherwise create one focused Builder under `Compiler/Builder`;
4. extend the established registry abstraction;
5. register it as a shared `Compiler.Builder.*` service; and
6. inject the same instance into producers and consumers.

Preserve and document:

- the path separator;
- key normalization;
- set versus add semantics;
- array versus string accumulation;
- value types;
- placeholder normalization; and
- isolation between compiler factory lifecycles.

A simple leaf is intentionally concise:

```php
/**
 * Access Switch Builder Class.
 *
 * @since  3.2.0
 */
final class AccessSwitch extends Registry implements Registryinterface
{
}
```

Do not add accessors merely to make an empty registry look substantial. Add
domain behavior only when the dataset owns that behavior.

## Joomla version conventions

JCB has two independent version axes. Select the correct one before writing a
condition or provider:

| Axis | Source | Use |
| --- | --- | --- |
| Host/runtime Joomla | `Joomla\CMS\Version::MAJOR_VERSION` | Behavior that integrates with the Joomla instance currently running JCB. |
| Compile target Joomla | `Compiler\Config->joomla_version` | Source and structures generated for the requested Joomla major. |

Do not add inline J3/J4/J5/J6 conditions to a stable consumer. Define one
logical interface/alias and let the closest service provider select the
concrete version.

Every version-dispatched family retains all four service identities:

- J3 / `JoomlaThree`;
- J4 / `JoomlaFour`;
- J5 / `JoomlaFive`; and
- J6 / `JoomlaSix`.

If versions currently share output, put the common mechanics outside the
version folders and use thin version classes or delegates. Do not remove a
service identity because two implementations happen to be identical today.

Validate externally supplied target versions against the supported catalog
before composing a dynamic service key.

## Joomla Powers and Super Powers

Joomla Powers and Super Powers are separate contracts.

- Joomla Powers represent version-aware Joomla class references in generated
  code.
- Super Powers represent reusable user or domain code.

Preserve generated Joomla Power references such as
`Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power` and the existing Power
resolution pipeline. Do not replace a Joomla Power token with a native Joomla
import as an unrelated cleanup. JCB owns the generated import and class-name
resolution.

Where JCB-authored source already uses a Joomla Power class token, continue to
use that mechanism, including container access through the corresponding Power
when required by the surrounding code. Do not create a parallel alias map.

Compiler runtime infrastructure that intentionally imports Joomla runtime
classes directly, such as `Joomla\DI\Container`, follows its existing runtime
boundary. Do not mechanically convert an entire runtime library to Power
tokens or an entire generated-code path to native imports. Trace which side of
the compiler boundary the code belongs to.

## External adapters

Gitea, GitHub, OpenAI, and provider-neutral Git classes remain sibling bounded
contexts. Their namespaces, factories, and HTTP utilities must not be folded
into compiler helpers.

API method code should:

- build the path explicitly;
- add optional query values only when present;
- construct a documented request payload;
- call the injected HTTP boundary;
- pass the response through the injected response translator; and
- return the declared result.

Keep external API field names exact even when they use snake case. Never log
access tokens, authorization headers, or repository credentials.

The provider-neutral Git facade delegates to the selected Gitea or GitHub
implementation. New provider-specific conditions belong in composition or a
provider adapter, not throughout consumers.

## Security-sensitive code

Remote paths, archive paths, file uploads, restore operations, and API/MCP
inputs are trust boundaries. Code style must not obscure their safety checks.

Use named validation steps and test:

- `..` traversal;
- absolute paths;
- mixed separators;
- symlink escape where supported;
- invalid entity names;
- unsupported repository configuration; and
- missing authorization.

Never expose arbitrary DI aliases to user input. Validate a canonical entity
through the authoritative router and constrain filesystem work to approved
roots.

## PHPUnit PHP style

The full testing conventions belong in the test-suite documentation. PHPUnit
12 test files themselves follow this standard, with these additional rules:

- mirror the production relative path under `libraries/vendor_jcb/tests`;
- insert `Tests` after the package namespace;
- name the file and class `<Subject>Test` for a direct subject owner;
- declare test classes `final`;
- extend `PHPUnit\Framework\TestCase` directly or through the narrowest shared
  support base documented by the test harness;
- use PHPUnit attributes for coverage and data-provider metadata;
- type `setUp()`, `tearDown()`, test methods, and providers;
- use a descriptive `test...` method name for one observable contract; and
- use tabs even when the production subject is `VDM.Minify`.

A cohesive, data-driven family or architecture contract may use a descriptive
aggregate name such as `ProviderCatalogTest`, `InterfaceConformanceTest`, or
`Versioned...Test`. Every production declaration it owns must be mapped to that
file in `test-ownership.php`. Aggregate owners must represent one reviewed
contract family; arbitrary catch-all test files are not permitted.

Example:

```php
<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Utilities\ArrayHelper;


/**
 * Array Helper Test.
 *
 * @since  6.1.6
 */
#[CoversClass(ArrayHelper::class)]
final class ArrayHelperTest extends TestCase
{
	/**
	 * Clone nested objects and arrays without retaining mutable references.
	 *
	 * @return  void
	 *
	 * @since   6.1.6
	 */
	public function testCloneCreatesAnIndependentNestedValue(): void
	{
		$object = new \stdClass();
		$object->name = 'original';

		$source = [
			'object' => $object,
			'nested' => ['name' => 'original'],
		];
		$copy = ArrayHelper::clone($source);

		$copy['object']->name = 'changed';
		$copy['nested']['name'] = 'changed';

		$this->assertNotSame($source['object'], $copy['object']);
		$this->assertSame('original', $source['object']->name);
		$this->assertSame('original', $source['nested']['name']);
	}
}
```

Do not use attributes as a substitute for behavior assertions. A test that
only instantiates a class, checks `class_exists()`, or asserts a method exists
does not protect its contract.

## `VDM.Minify` upstream-preservation exception

`libraries/vendor_jcb/VDM.Minify/src` is derived from the Matthias Mullie
Minify and path-converter projects. Its source retains upstream conventions,
including four-space indentation, same-line braces in places, legacy
`array(...)` syntax, upstream headers, and no JCB `@since` tags.

This is an explicit preservation exception, not an alternative JCB style.

When changing `VDM.Minify/src`:

1. make the smallest behavioral patch possible;
2. retain the surrounding upstream style so a future upstream comparison or
   rebase remains reviewable;
3. do not reindent, modernize arrays, rewrite headers, or add JCB tags across
   untouched code;
4. keep the upstream copyright and license intact;
5. isolate any deliberate upstream resynchronization in its own commit; and
6. add regression tests under `tests/VDM.Minify/src` using tabs and the JCB
   PHPUnit style.

New JCB-owned integration or adapter code must not be added to the upstream
Minify namespace merely to inherit this exception. Put it in the correct
first-party VDM/JCB domain and follow the tab-based standard.

Automated style checks must distinguish the grandfathered upstream source
from project-owned tests and adapters. They must not either rewrite Minify or
silently allow four-space indentation throughout JCB.

## Review checklist

Before approving first-party PHP, verify all of the following.

### File and formatting

- [ ] The namespace and filename match the package path.
- [ ] The standard header is present and truthful.
- [ ] Indentation uses tabs, except inside preserved generated content.
- [ ] Line endings are LF, trailing whitespace is absent, and the file ends in a newline.
- [ ] Braces use Allman style and every control body is braced.
- [ ] Imports are explicit, grouped meaningfully, and used.
- [ ] No closing PHP tag or isolated `strict_types` declaration was added.

### Types and documentation

- [ ] New properties, parameters, and return values are accurately typed.
- [ ] Explicit dependency properties and constructor assignments are used.
- [ ] Every named declaration and member has a meaningful docblock and truthful `@since`.
- [ ] Array shapes, mutations, side effects, and exceptions are documented.
- [ ] Deprecations name an executable replacement and removal horizon when known.

### Architecture

- [ ] Dependencies are injected and constructed by the closest provider.
- [ ] Stable aliases/interfaces are used instead of new static Factory calls.
- [ ] Shared mutable state lives in a focused Builder/registry with preserved path semantics.
- [ ] Host Joomla and compile-target Joomla axes are not confused.
- [ ] J3/J4/J5/J6 service identities remain intact.
- [ ] Joomla Power and Super Power contracts are preserved.
- [ ] Service keys, events, placeholders, messages, and generated output remain exact unless explicitly changed.
- [ ] Package, compiler, adapters, and entity routing remain in their bounded contexts.

### Change quality

- [ ] The change does not include unrelated formatting churn.
- [ ] Failure paths and state transitions have meaningful tests.
- [ ] Static factory and shared-state lifecycle is reset or isolated correctly in tests.
- [ ] External HTTP, database, filesystem, time, and random boundaries are deterministic in unit tests.
- [ ] Security-sensitive paths and external inputs are validated before use.
- [ ] A `VDM.Minify` change follows the upstream-preservation exception and has a focused regression test.

## Representative first-party references

Use these classes as focused examples, while applying the canonical rules in
this document rather than copying their historical inconsistencies:

| Concern | Source |
| --- | --- |
| Typed final definition and precise documentation | [`VDM.Joomla/src/File/Definition.php`](../../libraries/vendor_jcb/VDM.Joomla/src/File/Definition.php) |
| Constructor DI and public/private method order | [`VDM.Joomla/src/Data/Item.php`](../../libraries/vendor_jcb/VDM.Joomla/src/Data/Item.php) |
| Registry abstraction and interface surface | [`VDM.Joomla/src/Abstraction/Registry.php`](../../libraries/vendor_jcb/VDM.Joomla/src/Abstraction/Registry.php) and [`Interfaces/Registryinterface.php`](../../libraries/vendor_jcb/VDM.Joomla/src/Interfaces/Registryinterface.php) |
| Focused Builder leaf | [`Compiler/Builder/AccessSwitch.php`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Builder/AccessSwitch.php) |
| Target-version provider | [`Compiler/Service/ArchitectureModel.php`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureModel.php) |
| Package orchestration and array shapes | [`Package/Builder/Get.php`](../../libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Package/Builder/Get.php) |
| Gitea API boundary | [`VDM.Joomla.Gitea/src/Repository/Contents.php`](../../libraries/vendor_jcb/VDM.Joomla.Gitea/src/Repository/Contents.php) |
| OpenAI API boundary | [`VDM.Joomla.Openai/src/Chat.php`](../../libraries/vendor_jcb/VDM.Joomla.Openai/src/Chat.php) |
| GitHub service provider | [`VDM.Joomla.Github/src/Service/Utilities.php`](../../libraries/vendor_jcb/VDM.Joomla.Github/src/Service/Utilities.php) |
| Provider-neutral Git facade | [`VDM.Joomla.Git/src/Repository/Contents.php`](../../libraries/vendor_jcb/VDM.Joomla.Git/src/Repository/Contents.php) |
| Preserved upstream exception | [`VDM.Minify/src/Abstraction/Minify.php`](../../libraries/vendor_jcb/VDM.Minify/src/Abstraction/Minify.php) |
