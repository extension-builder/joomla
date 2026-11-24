# Return 404 for a missing Joomla 6 API PATCH target

## Change identity and permission

Codex prepared this change on 24 November 2026 for the main-branch Alpha
release repair. Item 4 of the maintainer's request explicitly identifies the
Joomla 6 item controller template and requests a missing-record check before
checkout and during PATCH preprocessing.

## Affected paths and implementation

Modified protected path: `admin/compiler/joomla_6/API_VIEW_CONTROLLER.php`.
There are no created, moved or deleted protected paths.

The generated `edit()` resolves numeric and unique keys and calls the
existing record-level edit permission once for the resolved ID. It checks
the row after authorization and before checkout. If checkout returns false,
it checks again to distinguish a concurrent deletion from a lock refusal.
Joomla's save and display methods handle successful edits.
`preprocessSaveData()` also throws `ResourceNotFound` if the row disappears
before the model loads its normalized values.

## Impact and verification

The change affects compiled Joomla 6 item APIs. Authorized callers receive
404 for missing records, callers denied edit permission on a numeric target
receive 403, and lock conflicts keep their Joomla checkout error. These HTTP
responses have no graphical or accessibility surface. The installed API
suite exercises updates by numeric ID and GUID, compares omitted fields to
their stored bytes and sends a PATCH to a deleted record. The generated
controller lifecycle suite exercises the permission override, table reads,
lock refusal, concurrent deletion and failed normalized read.

The local lifecycle suite passed 31 tests and 615 assertions on PHP 8.4.27.
A separate HTTP probe ran the rendered controller and Joomla 6.2.0 core
against an SQLite-backed fixture. All 14 before-and-after checks passed.
A missing numeric ID and a row deleted during checkout changed from 400 to
404. Disappearance during hydration changed from 500 to 404. Refusing
checkout of a surviving row returned 400.

The compiled and installed Joomla 6.2 Demo passed all 51 partial-PATCH
HTTP checks. A missing GUID returned 404. Denied existing and missing
numeric IDs both returned 403 and dispatched zero target table-load events,
as recorded by an installed `onTableBeforeLoad` observer.

GUI specs: none. The changed entry point is an HTTP API.

## Reconciliation and rollback

| Repository path | Authoritative source | Transfer required | Status | Next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_6/API_VIEW_CONTROLLER.php` | Joomla 6 item API controller compiler template of the same path | Yes | Source identified | Carry the template into the maintained JCB distribution on synchronization and compile an item API. |

Revert the template and its missing-record regressions together to restore
the previous responses. Previously compiled components acquire the fix on
their next build.
