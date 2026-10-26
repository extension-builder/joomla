# Joomla 6 compilation regression repair

This repair coordinates native plugin definitions in `extension-builder/plugins`
with the Joomla 6 compiler. Existing Joomla 3–5 compilation behavior must remain
unchanged.

## Plugin boundary

The compiler consumes stored plugin `main_class_code`. Updating a method
catalogue or suppressing validation does not migrate that source. The companion
plugin changes supply reviewed native definitions for transfer back into JCB.
Tests must exercise a real affected plugin through compilation and native event
dispatch, alongside the existing negative validation cases.

## JavaScript boundary

Moving arbitrary authored footer code into the document head changed its
execution timing. Keeping its text unchanged did not preserve its behavior.
The repair restores the existing footer execution position and removes blanket
warnings while retaining the other Web Asset Manager and media migrations.

Ordinary footer script tags are not deprecated Joomla APIs. Full Web Asset
Manager migration of authored footer code remains a separate source migration:
static logic can use deferred media assets; dynamic PHP data can use Joomla
script options. Arbitrary source must not be silently wrapped, evaluated, or
converted into modules because that can change globals, ordering, or scope.

## Validation status

Implementation and regression coverage are in progress. Passing the existing
focused tests is not evidence that the reported failure is fixed. Final results,
remaining limitations, and transfer instructions will be recorded here before
review.
