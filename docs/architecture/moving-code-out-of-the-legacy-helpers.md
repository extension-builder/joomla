# Moving code out of the legacy helpers

The compiler works. `Helper\Interpretation` and `Helper\Fields` are bloated, but
what they do, they do correctly. Breaking them into classes is about letting
someone else read and maintain that behaviour — not about improving it.

Everything below exists because that distinction was blurred once and had to be
corrected. It is written so it does not have to be corrected again.

## The one rule

**A method that moves must decide to act on exactly the same grounds it did
before.**

The compiler is a listener. Every method looks at what the component declares,
and if it finds nothing it can act on, it quietly produces nothing and lets the
next one look. That silence is the design. A component only declares a fraction
of what the compiler can build, so *most* methods find nothing on *most* runs.
Turning any of those quiet non-events into an error, a warning, or an exception
turns a normal compile into a failure.

So when moving a method:

- Keep every `isset`, `empty`, `!==`, `==`, `??` exactly as it was.
- Do not "tighten" a loose comparison. `isset()` is not `!empty()`. `==` is not
  `===`. These were chosen.
- Do not add a guard the original did not have.
- Do not raise anything the original did not raise. If the original silently
  skipped an unrecognised value, the moved code silently skips it too.

## What you should improve

Type declarations. The classes moved out before this effort declare what they
expect and what they return, and new ones should too:

```php
public function get(string $nameSingleCode, array $view): string
protected function cryptionField(string $cryptionType): ?Registry
```

This is a genuine quality gain and it is wanted. Two conditions:

1. **The declaration must describe what callers actually pass.** The legacy is
   untyped and tolerant. If a caller legitimately passes `null`, the signature
   says `?string`. Declaring `string` because it reads better converts a value
   the compiler quietly handled into a fatal error — the same defect as adding a
   `throw`, wearing a nicer hat.
2. **A type declaration is not a place to enforce policy.** It documents the
   contract that already exists; it does not create a stricter one.

## Version branches are the exception, and the only one

A `joomla_version` conditional is meant to disappear. It becomes a shared class
plus a class per target that genuinely differs:

```php
// before, inside one method
if ($this->config->get('joomla_version', 3) == 3) { … } else { … }

// after: a shared class carries the common form,
// and only the targets that differ carry their own
protected function getUserObject(): string
```

That removes exactly one comparison per collapsed branch. Any other change in
the comparison count means something was added or lost.

Only create a `Joomla*` class where the generated code actually differs. See
[system-map.md](system-map.md) — "A target class must earn its existence".

## How this is enforced

`tests/bin/check-moved-conditions.php` runs in CI beside the style and ownership
guards. It pairs every condition that leaves a legacy helper with one that
arrives in the tree, and fails the build on anything that does not pair up:

```
php bin/check-moved-conditions.php --base=<sha> [--head=<sha>]
```

It reduces each deciding line to its shape — whitespace, comments and the route
to a service removed — and counts those shapes per file at both ends of the
range. Counting files rather than diff lines means reindenting, rewrapping and
line-ending changes cannot look like a condition moving. It skips the range
entirely when no legacy helper changed.

Two things need no explanation, because they are the sanctioned collapse: a line
that only compares the Joomla version being compiled for, and the target
selector in the service provider that replaces it. Everything else must be
recorded in [`tests/moved-conditions.php`](../../libraries/vendor_jcb/tests/moved-conditions.php)
with the reason it still decides the same thing.

That ledger is the audit trail. It is short on purpose: 37 entries cover every
place in the whole effort where moved code reads differently from the legacy,
and each one names why. Adding an entry is a claim you have to be able to
defend in review, which is the point — the guard cannot tell a safe rewrite from
an unsafe one, but it can make sure nobody performs one silently.

Both failure modes it exists for are covered:

```
- A condition arrived that the legacy helper never had: &&!empty($filter['custom']['table'])
- A condition left the legacy helper and did not arrive: &&isset($filter['custom']['table'])
```

```
- A condition arrived that the legacy helper never had: thrownew\RuntimeException('nocustomfields');
```

### What the audit found

The whole effort was replayed through this check, commit by commit, before the
guard was wired in. Every difference fell into one of these, and none of them
changed what makes a method act:

- a version branch collapsing into a class per target, and the selector that
  replaces it
- a guard that wrapped a whole method body becoming an early return, so `!== null`
  reads `=== null` and `> 0` reads `<= 0`
- `isset($array[$key])` becoming a `Registry` read, which returns null for a key
  that was never set
- `isset($this->prop)` becoming `!== null` on a declared nullable property
- a service name built from a value becoming injected services and a map over
  the values the caller can actually set
- a variable or helper renamed on the way out
- a condition evaluated twice on values that cannot change in between, read once

Nothing was tightened, nothing was dropped, and nothing raises where the legacy
was silent.

The type declarations were audited separately, since a signature narrower than
its callers fails the same way a new guard does. Every shim was paired with the
service method it delegates to, and every call site that feeds a shim from a
`Registry` read was checked by hand. No parameter can be reached with a value
the legacy tolerated: the one method whose caller passes a registry read into a
`$Component` parameter is still untyped, and the one shim that forwards a
value which used to default to null casts it on the way through.

### Digging into one method

`tests/bin/refactor/semantics.php` compares the comparison tokens of a legacy
method family against the classes it became, which is the finer-grained view
when the guard reports something and you want to know where it came from:

```
php semantics.php <legacy.php> <method> [<method>…] <class.php> [<variant.php>…]
```

**Pass the whole legacy family, not one method.** A class usually absorbs
protected helpers that never had a shim. Comparing a five-method class against
one method reports drift that is not there — that mistake produced a list of
nine "defects", of which the first one checked was clean.

`tests/bin/refactor/methods.php` lists the methods a file really declares, for
the before-and-after check that nothing was lost. Grep cannot do this: `function
x(` also occurs inside the string literals the compiler emits, so it invents
methods that are not there and can mask one that is gone.

`tests/bin/refactor/verbatim.php` proves the opposite case: that a method lifted
whole out of a helper landed unchanged. Name the route each service took —
`CFactory::_('Utilities.Paths')` became `$this->paths` — and what is left must
be identical text:

```
php verbatim.php <old.php> <old signature> <new.php> <new signature> \
    [<what it was> <what it became>]...
```

Each route is two arguments, so either side may contain anything — a whole
wrapped statement, newlines, `=` — which is what a block lifted out of
`buildFileContent` needs: there a legacy array the helper filled by hand
becomes a `set()` on the builder that replaced it.

It needs no fixtures, so unlike a replica run it cannot pass because nothing was
exercised. It says nothing about *which* service reached which property, though:
that is what the provider registration and the constructor's type declarations
decide, and what building the class through the container proves.

## Where each method went

[`extraction-map.json`](extraction-map.json) records it, generated by
`tests/bin/refactor/trace.php`. It maps each legacy method to the class or classes its
code now lives in, matched on **content** rather than name, because methods were
renamed as they moved (`setEditBodyTabMainCenterPositionDiv` became
`setTabMainCenterPositionDiv` on the `EditBody` class).

```json
"setListQuery": {
  "fingerprints": 6,
  "moved_into": [ { "class": "Architecture/Model/ListQuery.php", "matched": 6 } ],
  "shares_generated_text_with": [ "Architecture/LinkedView/ListQuery.php" ]
}
```

`moved_into` is where the method went. `shares_generated_text_with` is a class
that emits similar generated text but did not receive this method — useful
context, not a destination.

## Writing the tests

Assert against **captured output**, never a guessed string. Add a temporary
`getenv`-guarded dump to the test helper, run it once per input shape, read what
the compiler really produced, and write the assertions from that.

Guessing has been wrong every time it was tried here, in ways that were not
obvious: a getter that turned out to emit a method *body* rather than a
signature; a category flag that swapped one helper for another of different
arity rather than adding a variable; a guard block that renders unconditionally
rather than behind the flag it appears to sit under.

A test written from expectation can pass for the wrong reason. That makes it
worse than no test, because it certifies a behaviour nobody checked.
