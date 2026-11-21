# Return successful empty dynamic API collections on stable-patch

## Scope and permission

On 21 November 2026 the maintainer requested the complete backport of issue
#1330 from main to stable-patch. The source is commit
`5ca2d9e0a7ac059b94b46c35444c16d91ce08bd2` in PR #66.
The only modified protected path is
`admin/compiler/joomla_4/API_DYNAMIC_VIEWS_JSON.php`.
The change creates, renames and deletes no protected paths.

## Implementation and generated behavior

The dynamic API view template discovers fields from the records in
`JsonapiView::displayList()`. On an empty result, Joomla rejects the empty
field list before serializing the collection. The template now supplies
`['id']` when there are no discovered fields. The existing parent renderer
receives the original items and returns an empty JSON array. Populated lists
keep their discovered fields. Model errors still propagate.

This is the shared template selected by stable-patch's
`Config::getJoomlaversions()` for Joomla 4, 5 and 6. It produces the list
view for both read-only Site View and Custom Admin View resources. The
change affects generated PHP and API responses. It changes no administrative
screen, keyboard interaction or schema.

## Verification

- `DynamicListApiTemplatesTest` runs each target's configured template
  through the native Joomla field event and JSON:API serializer. It covers
  model-supplied empty results, explicitly empty collections, pagination,
  populated rows and error propagation.
- The installed Demo API workflow seeds the native site list and exercises
  HTTP responses before creating a matching record, while the record is
  published and after it is trashed, then cleans up the record. An authenticated
  reader with explicit site access fetches the collection. An administrator
  creates and removes the row. A separate denied user receives HTTP 403 from
  both the singular and plural routes. All 13 collection checks and all
  13 native permission checks passed locally on Joomla 6.1.2 and PHP 8.4.27.
- `bash -n .github/api-tests/run.sh` and `git diff --check` passed locally.
  The PR's unit and installed API checks report execution of the PHP cases.
  The changed interface is an API, so the installed HTTP scenario covers
  the user-visible surface directly.

## Reconciliation and rollback

The checked-in stable compiler distribution template carries the same field
assignment as the reviewed main-branch template. Maintain this assignment in
the authoritative JCB template before future regeneration. Existing compiled
extensions acquire the change after recompilation and installation. The Demo
also contains authored code that creates a frontend edit link for editors.
When its site list runs inside the API with an editor and returns a record,
that code invokes `Route::_()` on Joomla's API router, which lacks `build()`.
The resulting HTTP 500 is separate from the empty-result generator fix. Reverting
the issue commit restores the old generator and removes its fixtures.
