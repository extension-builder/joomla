# Admin view extraction records

## Purpose

This document holds the pre-coding extraction records required by the
[refactoring playbook](helper-refactoring.md#dependency-inventory-for-one-cluster)
for the admin-view clusters that remain in `Helper\Interpretation`. It records
version axes, state, and the behavioural quirks that must survive a mechanical
move.

Line numbers drift as clusters are extracted; treat them as navigation hints and
re-locate by method name. The already-extracted clusters are listed in the
playbook's [extraction progress](helper-refactoring.md#extraction-progress) table.

## Cross-cutting contracts

These apply to every remaining admin-view cluster.

### Split power placeholders are deliberate

Every Power reference in the helper is written as two concatenated literals, for
example `'Joomla__' . '_39403062_84fb_46e0_bac4_0023f766e827___Power'` and
`'Super_' . '__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power'`. The split stops
JCB's own Power scanner from rewriting the compiler's source when JCB compiles
itself. The same trick guards the language function: `'Text:' . ':_('`.

**Joining these literals corrupts the compiler on its next self-compile.** Keep
the concatenation exactly as written when moving code.

### Debug line markers change on every move

`Line::_(__LINE__, __CLASS__)` emits ` [<CLASS> <LINE>]` into generated code when
the debug line-number option is enabled. Relocating a generator necessarily
changes those comments. This is accepted and already established by earlier
extractions; golden comparisons must run with that option off, or accept the
marker diff explicitly. Both `__LINE__` and the mixed-case `__Line__` spelling
appear in the helper and behave identically.

### Two independent axes

The Joomla compile target is not the only switch. `Config->footable_version`
(2 versus 3) drives roughly fourteen further conditionals in the linked-view and
list clusters. Keep it an internal switch or an injected value object; folding it
into the target-version family would create a needless 2×4 matrix.

## Remaining clusters

### View bodies — `setDefaultViewsBody`, `setModalViewsBody`

Emits four `jcb_ce_onSet*` events. The modal body's bottom event currently
triggers `jcb_ce_onSetDefaultViewsBodyBottom` despite the neighbouring modal
naming; the inventory already records this. Preserve the emitted name and treat
a correction as a separate, tested change.

### Edit body and tabs — `setEditBody` and helpers

`getEditBodyTabs()` queues deferred work by appending to
`$this->secondRunAdmin['setLinkedView']`. That property is declared only on the
`Infusion` subclass while being written from the parent, so from
`Interpretation` it is an undeclared property that works through
auto-vivification. The extraction must give the deferred queue a declared,
injected home, and must preserve the replay position documented in the
[execution flow](compiler-execution-flow.md#the-exact-second-run-contract).

### Layout and fade — `setFadeInEfect`, `setLayout`, layout overrides

Self-contained generators. Extract with their override lookup as one cluster.

### Linked views — `setLinkedView` and the linked list generators

The largest remaining cluster and the most quirk-dense.

**Version axis is a three-way split, not four.** Seven of the eight target
conditionals are plain `== 3` checks, so Joomla 4, 5 and 6 emit identical output.
Exactly one conditional (`> 4`, additionally gated on the parent key being
`guid`) separates Joomla 5/6 from Joomla 4. Joomla 6 has no behaviour of its own
and should be a thin variant of Joomla 5 while keeping its own service identity.

**Dispatch by method name.** `setLinkedView` is never called directly. It is
queued under the literal string key `setLinkedView` and invoked as
`$this->{$function}($array)`. Renaming or moving it without updating the queue
produces a silent no-op — an empty linked-view tab — rather than a fatal error.

**Misspelling is load-bearing.** The queued payload key and the parameter are
`addNewButon` (one `t`), while the source key it reads is spelled correctly.
Renaming it silently stops the new-button block from emitting.

**Argument naming is inverted.** `setListHeadLinked` and `setListBodyLinked`
receive the *linked* view's codes as their first arguments and the *parent*
view's single code as `$refview`. Renaming these for clarity is the easiest way
to scramble every generated link.

**`set` versus `add` asymmetry.** `LINKEDVIEWTABLESCRIPTS` uses `set()` while its
siblings `LINKEDVIEWITEMS`, `LINKEDVIEWGLOBAL` and `LINKEDVIEWMETHODS` use
`add(..., false)`, which is string concatenation rather than array append. With
several linked views on one parent the scripts key is overwritten while the
others accumulate. Changing `set()` to `add()` would repeat the whole loader
block.

**By-reference item class.** `$itemClass` is initialised once outside the column
loop and passed by reference into the list-item builder, which mutates it. The
mutation carries across columns. Re-initialising per iteration changes the
generated markup.

**Type polymorphism.** `$globalKey` is a string on the normal path and an array
keyed by parent key on the `-OR>` path. Any typed signature must accept
`string|array`.

**Whole-string post-pass.** The linked list query runs a `str_replace` over the
entire accumulated query, which by then includes user custom code. It must stay a
whole-string pass; applying it per fragment changes behaviour.

## Latent defects found during mapping

These are pre-existing. They are recorded so an extraction neither hides them nor
silently fixes them. A deliberate fix belongs in its own change with a
characterization test, following the
[known-defect ledger](../development/testing.md#known-defect-debt-ledger) process.

| Location | Symptom |
| --- | --- |
| `setLinkedView` view lookup | `$name_single_code` is read when no admin view matches the queued GUID, emitting an undefined-variable warning. This is the path that produces the `oops! error.....` layout text, so it fires in production. |
| `setLinkedView` `-OR>` path | `$parent_key` is never assigned on this path, so the Joomla 5 GUID branch can never be taken for OR keys; the later loop leaks the last OR key into the linked query call. |
| `setFootableScripts` | The returned buffer is only assigned for footable versions 2 and 3; any other configured value returns null from a method documented as returning a string. |
| Linked list query OR fallback | The `-5` fallback where-clause uses the loop variable after its loop has closed, so only the last OR key receives it. |
| Linked list ordering | When linked ordering is enabled but every field lookup fails, no ordering clause is emitted at all, because the default lives in the else branch. |
| Linked list head language | The status and id language keys are registered even when the corresponding headings are suppressed, inflating generated language files. |

## Dead code observed

Several assignments are never read: a component-helper name computed in three
linked-view generators, an unused single-view variable, and discarded
destructuring targets. Removing them is safe, but it also removes registry reads,
so note it in the change rather than treating it as a pure no-op.
