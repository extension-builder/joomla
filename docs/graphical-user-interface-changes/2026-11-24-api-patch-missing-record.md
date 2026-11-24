# Return 404 for a missing Joomla 6 API PATCH target

## Change identity and permission

Codex prepared this change on 24 November 2026 for the main-branch Alpha
release repair. Item 4 of the maintainer's request explicitly identifies the
Joomla 6 item controller template and requests a missing-record check before
checkout and during PATCH preprocessing.

## Affected paths and implementation

Modified protected path: `admin/compiler/joomla_6/API_VIEW_CONTROLLER.php`.
There are no created, moved or deleted protected paths.

The generated `edit()` resolves numeric and unique keys and checks whether
a PATCH target with a positive ID exists before Joomla attempts checkout.
For a missing row it checks the existing record-level edit permission before
reporting 404. Joomla's native edit method handles existing records. If the
row disappears before
`preprocessSaveData()` can load its normalized values, the controller also
throws Joomla's `ResourceNotFound` exception.

## Impact and verification

The change affects compiled Joomla 6 item APIs. Authorized callers receive
404 for missing records, callers denied edit permission on a numeric target
receive 403, and lock conflicts keep their Joomla checkout error. These HTTP
responses have no graphical or accessibility surface. The installed API
suite exercises updates by numeric ID and GUID, compares omitted fields to
their stored bytes and sends a PATCH to a deleted record. The generated
controller lifecycle suite exercises the failed normalized read.

The local lifecycle suite passed 26 tests and 716 assertions on PHP 8.4.27.
A separate HTTP probe ran the rendered controller and Joomla 6.2.0 core
against an SQLite-backed fixture. The missing numeric ID changed from 400
to 404, and disappearance during hydration changed from 500 to 404. Denied
existing and missing targets both returned 403. An actual checkout refusal
in the fixture still returned 400. A successful PATCH returned 200 and
preserved omitted encoded bytes.

GUI specs: none. The changed entry point is an HTTP API.

## Reconciliation and rollback

| Repository path | Authoritative source | Transfer required | Status | Next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_6/API_VIEW_CONTROLLER.php` | Joomla 6 item API controller compiler template of the same path | Yes | Source identified | Carry the template into the maintained JCB distribution on synchronization and compile an item API. |

Revert the template and its missing-record regressions together to restore
the previous responses. Previously compiled components acquire the fix on
their next build.
